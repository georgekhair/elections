<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoterContactLog extends Model
{
    protected $fillable = [
        'voter_id',
        'user_id',
        'result',
        'note'
    ];

    public function voter()
    {
        return $this->belongsTo(Voter::class);
    }
}
