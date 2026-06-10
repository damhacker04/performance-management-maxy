<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_evaluation_id',
        'overridden_by',
        'original_score',
        'new_score',
        'reason',
        'overridden_at',
    ];

    protected $casts = [
        'original_score' => 'decimal:2',
        'new_score'      => 'decimal:2',
        'overridden_at'  => 'datetime',
    ];

    public function aiEvaluation()
    {
        return $this->belongsTo(AiEvaluation::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
