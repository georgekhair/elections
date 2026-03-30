<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldTask extends Model
{
    protected $fillable = [
        'user_id',
        'polling_center_id',
        'created_by',
        'type',
        'priority',
        'description',
        'source',
        'status',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pollingCenter()
    {
        return $this->belongsTo(PollingCenter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
