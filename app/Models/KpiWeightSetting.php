<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiWeightSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'weight_achievement',
        'weight_efficiency',
        'weight_contribution',
        'weight_problem_solving',
        'set_by',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'weight_achievement'    => 'decimal:2',
        'weight_efficiency'     => 'decimal:2',
        'weight_contribution'   => 'decimal:2',
        'weight_problem_solving'=> 'decimal:2',
        'effective_from'        => 'date',
        'is_active'             => 'boolean',
    ];

    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * Ambil setting bobot aktif yang berlaku saat ini.
     * Prioritas: setting per departemen > setting global.
     */
    public static function getActive(?int $departmentId = null): self
    {
        // Coba ambil setting khusus departemen
        if ($departmentId) {
            $setting = self::where('department_id', $departmentId)
                ->where('is_active', true)
                ->where('effective_from', '<=', now()->toDateString())
                ->latest('effective_from')
                ->first();
            if ($setting) return $setting;
        }

        // Fallback ke setting global
        $global = self::whereNull('department_id')
            ->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->latest('effective_from')
            ->first();

        // Jika belum ada setting apapun, return default 25% semua
        return $global ?? new self([
            'weight_achievement'    => 25.00,
            'weight_efficiency'     => 25.00,
            'weight_contribution'   => 25.00,
            'weight_problem_solving'=> 25.00,
        ]);
    }

    /**
     * Validasi bahwa total bobot = 100%.
     */
    public function isTotalValid(): bool
    {
        $total = $this->weight_achievement
               + $this->weight_efficiency
               + $this->weight_contribution
               + $this->weight_problem_solving;
        return abs($total - 100.00) < 0.01;
    }
}
