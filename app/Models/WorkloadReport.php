<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkloadReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'month',
        'year',
        'score',
        'summary_flag',
        'report_data',
    ];

    protected $casts = [
        'report_data' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
