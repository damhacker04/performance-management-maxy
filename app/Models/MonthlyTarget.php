<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department',
        'title',
        'description',
        'month',
        'year',
    ];

    // Relasi ke User (Leader yang buat)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Daily Task Entries
    public function dailyTaskEntries()
    {
        return $this->hasMany(DailyTaskEntry::class);
    }
}