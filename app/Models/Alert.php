<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'polling_center_id',
        'meta',
        'is_active',
        'detected_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_active' => 'boolean',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function pollingCenter()
    {
        return $this->belongsTo(PollingCenter::class);
    }
}
