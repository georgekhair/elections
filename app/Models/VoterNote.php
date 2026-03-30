<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoterNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'voter_id',
        'note_type',
        'content',
        'priority',
        'requires_action',
        'action_due_at',
        'created_by',
    ];

    protected $casts = [
        'requires_action' => 'boolean',
        'action_due_at' => 'datetime',
    ];

    public function voter()
    {
        return $this->belongsTo(Voter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
