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
        'task_description',
        'duration_minutes',
        'status',
        'notes',
        'task_date',
    ];

    // Relasi ke User (Staff yang submit)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Monthly Target
    public function monthlyTarget()
    {
        return $this->belongsTo(MonthlyTarget::class);
    }
}