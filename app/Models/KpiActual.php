<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiActual extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_target_id',   // FK ke kpi_targets (L3 staff KPI)
        'staff_id',        // FK ke users
        'department',      // cache dept untuk query cepat
        'month',
        'year',
        'actual_value',    // nilai realisasi (input C-Level/HR)
        'source',          // 'manual' atau 'auto_detected'
        'notes',
        'created_by',      // user_id yang input (c_level/super_admin)
    ];

    protected $casts = [
        'actual_value' => 'decimal:2',
        'month'        => 'integer',
        'year'         => 'integer',
    ];

    // ═══ Relasi ══════════════════════════════════════════════════════

    /** KPI target (L3) yang jadi acuan */
    public function kpiTarget()
    {
        return $this->belongsTo(KpiTarget::class);
    }

    /** Staf yang dinilai */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /** User yang menginput actual ini */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ═══ Accessors ═══════════════════════════════════════════════════

    /** Selisih actual vs target (negatif = di bawah target) */
    public function getGapValueAttribute(): ?float
    {
        if (!$this->kpiTarget) return null;
        return $this->actual_value - $this->kpiTarget->target_value;
    }

    /** Persentase gap (negatif = di bawah target) */
    public function getGapPercentAttribute(): ?float
    {
        if (!$this->kpiTarget || $this->kpiTarget->target_value == 0) return null;
        return round(($this->actual_value / $this->kpiTarget->target_value) * 100, 1);
    }

    /** Label sumber data */
    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'manual' ? 'Manual Input' : 'Auto-detected';
    }

    /** Status warna berdasarkan pencapaian */
    public function getStatusColorAttribute(): string
    {
        $pct = $this->gap_percent;
        if ($pct === null) return 'gray';
        if ($pct >= 80) return 'green';
        if ($pct >= 60) return 'yellow';
        return 'red';
    }
}
