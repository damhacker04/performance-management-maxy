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
        'task_description',
        'priority',
        'duration_minutes',
        'actual_duration_minutes',
        'status',
        'percent_done',
        'notes',
        'task_date',
    ];

    protected $casts = [
        'task_date'              => 'date',
        'duration_minutes'       => 'integer',
        'actual_duration_minutes'=> 'integer',
        'percent_done'           => 'integer',
    ];

    protected $appends = ['is_overdue'];

    public const STATUSES = [
        'belum_mulai'  => 'Belum Mulai',
        'dalam_proses' => 'Dalam Proses',
        'terhambat'    => 'Terhambat',
        'selesai'      => 'Selesai',
    ];

    public const PRIORITIES = [
        'critical' => 'Critical',
        'high'     => 'High',
        'medium'   => 'Medium',
        'low'      => 'Low',
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

    /**
     * Apakah task terlambat? task_date sudah lewat tapi belum selesai.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->task_date) return false;
        if ($this->status === 'selesai') return false;
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
     * Aturan: belum selesai DAN masih hari ini.
     * (Auth check ditangani terpisah di controller.)
     */
    public function canBeEdited(): bool
    {
        if ($this->status === 'selesai') return false;
        if (!$this->task_date) return false;
        return $this->task_date->isToday();
    }
}
