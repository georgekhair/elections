<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatProjectionSnapshot extends Model
{
    protected $fillable = [
        'input_votes',
        'projected_seats',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'input_votes' => 'array',
            'projected_seats' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
