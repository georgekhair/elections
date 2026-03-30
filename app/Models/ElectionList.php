<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionList extends Model
{
    protected $fillable = [
        'name',
        'code',
        'estimated_votes',
        'is_our_list',
    ];

    protected function casts(): array
    {
        return [
            'estimated_votes' => 'integer',
            'is_our_list' => 'boolean',
        ];
    }
}
