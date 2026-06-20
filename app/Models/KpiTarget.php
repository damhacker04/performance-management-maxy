<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    protected $fillable = [
        'parent_id',     // FK ke kpi_targets (null = L2 dept, ada = L3 staff)
        'kpi_level',     // 2 = dept benchmark, 3 = staff individual
        'user_id',       // legacy — nullable, data lama per staf
        'department',    // primary: per departemen
        'kpi_name',
        'target_value',
        'unit',
        'month',
        'year',
        'set_by',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'target_value' => 'decimal:2',
        'kpi_level'    => 'integer',
    ];

    // ═══ Relasi ══════════════════════════════════════════════════════

    /** KPI parent (L2 dept) dari KPI L3 staff ini */
    public function parent()
    {
        return $this->belongsTo(KpiTarget::class, 'parent_id');
    }

    /** KPI anak (L3 staff) dari KPI L2 dept ini */
    public function children()
    {
        return $this->hasMany(KpiTarget::class, 'parent_id');
    }

    /** User yang set KPI ini (C-Level / Admin HR) */
    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /** Legacy: user individual (data lama) */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Staff yang di-assign KPI L3 ini */
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Monthly targets yang mengacu KPI ini */
    public function monthlyTargets()
    {
        return $this->hasMany(MonthlyTarget::class);
    }

    /** KPI Actual realisasi per bulan */
    public function actuals()
    {
        return $this->hasMany(KpiActual::class);
    }

    // ═══ Scopes ═══════════════════════════════════════════════════════

    /** KPI Level 2 (dept benchmark) */
    public function scopeLevel2($query)
    {
        return $query->where('kpi_level', 2);
    }

    /** KPI Level 3 (staff individual) */
    public function scopeLevel3($query)
    {
        return $query->where('kpi_level', 3);
    }

    /** KPI untuk staf tertentu (L3) */
    public function scopeForStaff($query, int $userId)
    {
        return $query->where('kpi_level', 3)->where('user_id', $userId);
    }

    /** KPI aktif untuk departemen tertentu di bulan/tahun tertentu */
    public function scopeActiveForDept($query, string $dept, ?int $month = null, ?int $year = null)
    {
        $query->where('department', $dept)->where('is_active', true);

        if ($month && $year) {
            $query->where('month', $month)->where('year', $year);
        }

        return $query;
    }

    /** Semua KPI aktif (per-dept, bukan legacy per-user) */
    public function scopeDepartmentLevel($query)
    {
        return $query->whereNotNull('department')->where('is_active', true);
    }
}
