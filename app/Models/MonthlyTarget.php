<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',        // Leader yang membuat (tetap)
        'assigned_to',    // Staf pemilik target (BARU, nullable)
        'kpi_target_id',  // Acuan KPI departemen (BARU, nullable)
        'department',
        'title',
        'description',
        'month',
        'year',
    ];

    // ── Relasi ──────────────────────────────────────────────────

    /** Leader yang membuat target ini */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Staf pemilik target (individu) */
    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** KPI departemen yang jadi acuan target ini */
    public function kpiTarget()
    {
        return $this->belongsTo(KpiTarget::class);
    }

    /** Daily Task Entries yang terkait (via weekly targets) */
    public function dailyTaskEntries()
    {
        return $this->hasMany(DailyTaskEntry::class);
    }

    /** Weekly Targets (breakdown bulanan) */
    public function weeklyTargets()
    {
        return $this->hasMany(WeeklyTarget::class)->orderBy('week_number');
    }
}