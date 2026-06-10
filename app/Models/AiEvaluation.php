<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_task_entry_id',
        'score_achievement',
        'score_efficiency',
        'score_contribution',
        'score_problem_solving',
        'final_score',
        'ai_feedback',
        'link_status',
        'is_overridden',
        'raw_response',
    ];

    protected $casts = [
        'score_achievement'    => 'decimal:2',
        'score_efficiency'     => 'decimal:2',
        'score_contribution'   => 'decimal:2',
        'score_problem_solving'=> 'decimal:2',
        'final_score'          => 'decimal:2',
        'is_overridden'        => 'boolean',
        'raw_response'         => 'array',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function dailyTaskEntry()
    {
        return $this->belongsTo(DailyTaskEntry::class);
    }

    public function overrides()
    {
        return $this->hasMany(LeaderOverride::class);
    }

    public function latestOverride()
    {
        return $this->hasOne(LeaderOverride::class)->latestOfMany();
    }

    // ── Helper Methods ───────────────────────────────────────────────────────

    /**
     * Mengembalikan skor yang efektif (override jika ada, jika tidak pakai skor AI).
     */
    public function getEffectiveScoreAttribute(): float
    {
        if ($this->is_overridden && $this->latestOverride) {
            return (float) $this->latestOverride->new_score;
        }
        return (float) $this->final_score;
    }

    /**
     * Label chip warna berdasarkan skor.
     */
    public function getScoreChipAttribute(): string
    {
        $score = $this->effective_score;
        if ($score >= 8) return 'success';
        if ($score >= 6) return 'warning';
        return 'danger';
    }

    /**
     * Label teks skor.
     */
    public function getScoreLabelAttribute(): string
    {
        $score = $this->effective_score;
        if ($score >= 8) return 'Baik';
        if ($score >= 6) return 'Cukup';
        return 'Perlu Peningkatan';
    }

    /**
     * Apakah link yang dilampirkan staf terkunci/restricted?
     */
    public function isLinkRestricted(): bool
    {
        return $this->link_status === 'restricted';
    }
}
