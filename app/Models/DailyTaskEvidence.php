<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTaskEvidence extends Model
{
    use HasFactory;

    protected $table = 'daily_task_evidences';

    protected $fillable = [
        'daily_task_entry_id',
        'type',
        'label',
        'path_or_url',
    ];

    public function entry()
    {
        return $this->belongsTo(DailyTaskEntry::class, 'daily_task_entry_id');
    }
}
