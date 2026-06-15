<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    protected $fillable = [
        'user_id',
        'kpi_name',
        'target_value',
        'unit',
        'month',
        'year'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
