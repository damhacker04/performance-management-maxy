<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',     // FK ke kpi_targets (null = L2 dept, ada = L3 staff)
        'kpi_level',     // 2 = dept benchmark, 3 = staff individual
        'aggregation',   // sum | average | shared | milestone (jenis KPI)
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

    /**
     * Jenis KPI (cara agregasi staf → dept). Label bahasa awam untuk dropdown.
     */
    public const AGGREGATIONS = [
        'sum'       => 'Dijumlahkan dari tiap staff',
        'average'   => 'Rata-rata pencapaian staff',
        'shared'    => 'Target tim bersama (tak dibagi)',
        'milestone' => 'Milestone — progress %',
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

    // ═══ Jenis KPI (aggregation) ══════════════════════════════════════

    /** sum & average dipecah per staf (punya L3); shared & milestone tidak. */
    public function hasStaffBreakdown(): bool
    {
        return in_array($this->aggregation ?: 'sum', ['sum', 'average'], true);
    }

    /** shared & milestone diukur di level departemen (actual staff_id = null). */
    public function isDeptLevel(): bool
    {
        return in_array($this->aggregation, ['shared', 'milestone'], true);
    }

    public function isMilestone(): bool
    {
        return $this->aggregation === 'milestone';
    }

    public function aggregationLabel(): string
    {
        return self::AGGREGATIONS[$this->aggregation] ?? self::AGGREGATIONS['sum'];
    }

    /**
     * Rollup capaian KPI level departemen — SATU sumber kebenaran, dipakai
     * _body.blade, WorkloadReportDataService, dan actuals index.
     *
     * Return: ['aggregation','target','allocated','actual','pct','has_data','is_percent','unallocated'].
     * Hanya bermakna dipanggil pada KPI L2 (parent).
     */
    public function deptRollup(?int $month = null, ?int $year = null): array
    {
        $month ??= (int) $this->month;
        $year  ??= (int) $this->year;
        $agg = $this->aggregation ?: 'sum';

        // Pilih actual dari koleksi: cocok periode, fallback ke terbaru.
        $pick = function ($actuals) use ($month, $year) {
            $actuals = $actuals ?? collect();
            return $actuals->first(fn ($a) => (int) $a->month === $month && (int) $a->year === $year)
                ?? $actuals->sortByDesc(fn ($a) => $a->year * 100 + $a->month)->first();
        };

        // ── Level dept (shared / milestone): actual milik L2 sendiri (staff_id null) ──
        if ($agg === 'shared' || $agg === 'milestone') {
            $act    = $pick($this->actuals);
            $isMile = $agg === 'milestone';
            $target = $isMile ? 100.0 : (float) $this->target_value;
            $actual = $act ? (float) $act->actual_value : null;
            $pct    = $act === null ? null
                : ($isMile ? (int) round(min(100, max(0, $actual)))
                           : ($target > 0 ? (int) round($actual / $target * 100) : null));

            return [
                'aggregation' => $agg,
                'target'      => $isMile ? null : $target,
                'allocated'   => null,
                'actual'      => $actual,
                'pct'         => $pct,
                'has_data'    => $act !== null,
                'is_percent'  => $isMile,
                'unallocated' => null,
            ];
        }

        // ── Per-staf (sum / average) ──
        $children = $this->children->where('is_active', true);
        $pairs = $children->map(function ($ch) use ($pick) {
            $act = $pick($ch->actuals);
            return [
                'target' => (float) $ch->target_value,
                'actual' => $act ? (float) $act->actual_value : null,
                'has'    => $act !== null,
            ];
        });
        $hasAny = $pairs->contains(fn ($p) => $p['has']);

        if ($agg === 'average') {
            $rated = $pairs->filter(fn ($p) => $p['has'] && $p['target'] > 0)
                ->map(fn ($p) => $p['actual'] / $p['target'] * 100);
            return [
                'aggregation' => 'average',
                'target'      => (float) $this->target_value,
                'allocated'   => null,
                'actual'      => null,               // pakai pct (rata-rata capaian)
                'pct'         => $rated->isNotEmpty() ? (int) round($rated->avg()) : null,
                'has_data'    => $hasAny,
                'is_percent'  => true,
                'unallocated' => null,
            ];
        }

        // sum (default, perilaku lama)
        $deptTarget  = (float) $this->target_value;
        $allocated   = (float) $pairs->sum('target');
        $totalActual = (float) $pairs->sum(fn ($p) => $p['actual'] ?? 0);

        return [
            'aggregation' => 'sum',
            'target'      => $deptTarget,
            'allocated'   => $allocated,
            'actual'      => $totalActual,
            'pct'         => $hasAny && $allocated > 0 ? (int) round($totalActual / $allocated * 100) : null,
            'has_data'    => $hasAny,
            'is_percent'  => false,
            'unallocated' => $deptTarget - $allocated,
        ];
    }
}
