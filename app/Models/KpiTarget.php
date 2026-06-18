<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    protected $fillable = [
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
    ];

    // ── Relasi ──────────────────────────────────────────────────

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

    /** Monthly targets yang mengacu KPI ini */
    public function monthlyTargets()
    {
        return $this->hasMany(MonthlyTarget::class);
    }

    // ── Scopes ──────────────────────────────────────────────────

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
