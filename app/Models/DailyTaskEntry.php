<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTaskEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'monthly_target_id',
        'weekly_target_id',
        'parent_entry_id',
        'task_description',
        'priority',
        'duration_minutes',
        'actual_duration_minutes',
        'status',
        'notes',
        'task_date',
        // Bukti laporan
        'proof_url',
        'proof_file',
        // Verification fields
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_note',
        'reviewed_at',
        'revision_history',
    ];

    protected $casts = [
        'task_date'              => 'date',
        'duration_minutes'       => 'integer',
        'actual_duration_minutes'=> 'integer',
        'verified_at'            => 'datetime',
        'reviewed_at'            => 'datetime',
        'revision_history'       => 'array',
    ];

    protected $appends = ['is_overdue'];

    public const STATUSES = [
        'belum_mulai'  => 'Belum Mulai',
        'dalam_proses' => 'Dalam Proses',
        'terhambat'    => 'Terhambat',
        'selesai'      => 'Selesai',
    ];

    public const PRIORITIES = [
        'critical' => 'Kritis',
        'high'     => 'Tinggi',
        'medium'   => 'Sedang',
        'low'      => 'Rendah',
    ];

    public const VERIFICATION_STATUSES = [
        'pending'  => 'Menunggu Verifikasi',
        'approved' => 'Diverifikasi',
        'revision' => 'Perlu Revisi',
        'rejected' => 'Ditolak',
    ];

    // Relasi ke User (Staff yang submit)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Monthly Target (akan otomatis diisi dari Weekly Target's parent)
    public function monthlyTarget()
    {
        return $this->belongsTo(MonthlyTarget::class);
    }

    // Relasi ke Weekly Target
    public function weeklyTarget()
    {
        return $this->belongsTo(WeeklyTarget::class);
    }

    // Relasi ke User yang memverifikasi (leader/c_level)
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Relasi ke Bukti Laporan (Multi-Evidence)
    public function evidences()
    {
        return $this->hasMany(DailyTaskEvidence::class, 'daily_task_entry_id');
    }

    /**
     * Apakah task terlambat? task_date sudah lewat tapi belum selesai.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->task_date) return false;
        if ($this->status === 'selesai') return false;
        // Laporan yang sudah diverifikasi/approved tidak tampil sebagai terlambat
        if ($this->verification_status === 'approved') return false;
        return $this->task_date->isPast() && !$this->task_date->isToday();
    }

    /**
     * Label durasi yang user-friendly: "2j", "45m", "1j 30m"
     */
    public function getDurationLabelAttribute(): string
    {
        $mins = $this->actual_duration_minutes ?? $this->duration_minutes;
        if (!$mins) return '-';
        if ($mins < 60) return $mins . 'm';
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return $m === 0 ? "{$h}j" : "{$h}j {$m}m";
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Apakah entry ini masih bisa di-edit?
     *
     * Aturan (sesuai notul rapat 12 Mei 2026):
     * - Task yang sudah 'selesai' TIDAK bisa diubah (final & historis).
     * - Task yang berstatus lain (belum_mulai, dalam_proses, terhambat)
     *   TETAP bisa di-edit meski sudah lewat dari hari submit.
     *   Alasan: pekerjaan operasional bisa berlangsung berhari-hari / berminggu-minggu.
     *   Staff perlu bisa update catatan & status secara ongoing.
     *
     * (Auth check — hanya pemilik — ditangani terpisah di controller.)
     */
    public function canBeEdited(): bool
    {
        // Laporan yang sudah approved tidak bisa diedit sama sekali
        if ($this->verification_status === 'approved') return false;

        // Pengecualian khusus: jika diminta revisi oleh leader, staf TETAP BISA mengedit
        // meskipun statusnya sudah 'selesai' (asalkan masih dalam batas waktu revisi).
        if ($this->verification_status === 'revision') {
            return $this->canBeRevised();
        }

        return $this->status !== 'selesai';
    }

    /**
     * Apakah laporan ini bisa direvisi oleh staff?
     * Hanya jika verification_status = 'revision' dan masih dalam window 48 jam.
     */
    public function canBeRevised(): bool
    {
        if ($this->verification_status !== 'revision') return false;
        if (!$this->reviewed_at) return false;
        return $this->reviewed_at->addHours(10)->isFuture();
    }

    /**
     * Label teks + chip untuk badge verifikasi di UI.
     */
    public function getVerificationStatusLabelAttribute(): string
    {
        return self::VERIFICATION_STATUSES[$this->verification_status ?? 'pending'] ?? 'Menunggu Verifikasi';
    }

    /**
     * Chip color untuk badge verifikasi.
     */
    public function getVerificationChipAttribute(): string
    {
        return match($this->verification_status ?? 'pending') {
            'approved' => 'success',
            'revision' => 'warning',
            'rejected' => 'danger',
            default    => 'neutral',
        };
    }

    // ── Relasi progress multi-hari ────────────────────────────────────────────

    /** Entri induk (jika ini adalah lanjutan dari hari sebelumnya) */
    public function parentEntry()
    {
        return $this->belongsTo(DailyTaskEntry::class, 'parent_entry_id');
    }

    /** Entri-entri lanjutan dari entri ini */
    public function childEntries()
    {
        return $this->hasMany(DailyTaskEntry::class, 'parent_entry_id')->orderBy('task_date');
    }

    /** Bukti laporan (dinamis) */
    public function evidences()
    {
        return $this->hasMany(DailyTaskEvidence::class, 'daily_task_entry_id');
    }

    /**
     * Mengembalikan seluruh rantai progress multi-hari untuk entri ini,
     * dari entri paling awal (root) hingga entri paling baru,
     * diurutkan ascending berdasarkan tanggal.
     */
    public function progressHistory(): \Illuminate\Support\Collection
    {
        // Naik ke root
        $root = $this;
        while ($root->parent_entry_id) {
            $root = $root->parentEntry()->first();
            if (!$root) break;
        }
        if (!$root) return collect([$this]);

        // Kumpulkan semua dalam satu rantai
        $chain = collect();
        $current = $root;
        while ($current) {
            $chain->push($current);
            $current = $current->childEntries()->first();
        }
        return $chain;
    }
}
