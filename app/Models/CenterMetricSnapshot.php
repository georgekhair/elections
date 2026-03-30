<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterMetricSnapshot extends Model
{
    protected $fillable = [
        'polling_center_id',
        'voters_total',
        'voted_count',
        'supporters_total',
        'supporters_voted',
        'supporters_remaining',
        'supporter_turnout',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
        ];
    }

    public function pollingCenter()
    {
        return $this->belongsTo(PollingCenter::class);
    }
}
