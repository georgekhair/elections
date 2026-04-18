<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFamilyAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'family_name',
        'priority'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
