<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_target_id',
        'category',          // 'planned' | 'other' — sesuai notul rapat 12 Mei 2026
        'user_id',
        'title',
        'description',
        'target_type',
        'target_value',
        'target_unit',
        'week_number',
        'month',
        'year',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'week_number'  => 'integer',
        'month'        => 'integer',
        'year'         => 'integer',
    ];

    /**
     * Range tanggal untuk tiap nomor minggu (skema sederhana: 1-7, 8-14, 15-21, 22-28, 29-31).
     */
    public const WEEK_RANGES = [
        1 => [1, 7],
        2 => [8, 14],
        3 => [15, 21],
        4 => [22, 28],
        5 => [29, 31],
    ];

    /**
     * SENGAJA tidak ada cascade delete ke dailyTaskEntries.
     *
     * Alasan: data laporan harian staff adalah historis KPI yang tidak boleh
     * hilang hanya karena leader menghapus weekly target. Ketika weekly target
     * dihapus, kolom weekly_target_id di daily_task_entries akan menjadi NULL
     * (ON DELETE SET NULL — lihat migration 2026_05_13_100000).
     *
     * Referensi: notul rapat 12 Mei 2026 — "Sistem harus membantu evaluasi
     * objektif, bukan sekadar checklist target."
     */

    public function monthlyTarget()
    {
        return $this->belongsTo(MonthlyTarget::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dailyTaskEntries()
    {
        return $this->hasMany(DailyTaskEntry::class);
    }

    /**
     * Label tampilan untuk minggu: "Minggu 2 (8–14 Mei 2026)"
     */
    public function getWeekLabelAttribute(): string
    {
        [$start, $end] = self::WEEK_RANGES[$this->week_number] ?? [1, 7];
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)
            ->isoFormat('MMM');
        return "Minggu {$this->week_number} ({$start}–{$end} {$monthName} {$this->year})";
    }

    /**
     * Label target: "50 leads" atau "Kualitatif"
     */
    public function getTargetLabelAttribute(): string
    {
        if ($this->target_type === 'qualitative') {
            return 'Kualitatif';
        }
        $value = rtrim(rtrim((string) $this->target_value, '0'), '.');
        return trim("{$value} {$this->target_unit}");
    }

    /**
     * Apakah ini weekly target kategori "Other" (tidak terikat monthly target)?
     * Digunakan untuk filtering planned vs. unplanned di dashboard leader.
     */
    public function isOther(): bool
    {
        return $this->category === 'other';
    }

    public function isPlanned(): bool
    {
        return $this->category === 'planned';
    }
}
