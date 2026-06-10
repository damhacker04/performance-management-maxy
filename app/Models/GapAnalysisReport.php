<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GapAnalysisReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'root_cause_type',
        'narrative',
        'recommendation',
        'tasks_analyzed',
        'generated_at',
    ];

    protected $casts = [
        'tasks_analyzed' => 'integer',
        'generated_at'   => 'datetime',
    ];

    /**
     * Polymorphic relation — bisa ke WeeklyTarget atau MonthlyTarget.
     */
    public function reportable()
    {
        return $this->morphTo();
    }

    /**
     * Label tipe akar masalah dalam Bahasa Indonesia.
     */
    public function getRootCauseLabelAttribute(): string
    {
        return match($this->root_cause_type) {
            'internal' => 'Faktor Internal (Kinerja Staf)',
            'external' => 'Faktor Eksternal (Sistem/Birokrasi)',
            'mixed'    => 'Gabungan (Internal & Eksternal)',
            default    => 'Tidak Teridentifikasi',
        };
    }

    /**
     * Chip warna untuk badge tipe akar masalah di UI.
     */
    public function getRootCauseChipAttribute(): string
    {
        return match($this->root_cause_type) {
            'internal' => 'danger',
            'external' => 'warning',
            'mixed'    => 'neutral',
            default    => 'neutral',
        };
    }
}
