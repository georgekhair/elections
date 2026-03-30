<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoterRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'voter_id',
        'related_voter_id',
        'related_name',
        'relationship_type',
        'influence_level',
        'is_primary_influencer',
        'is_unconfirmed',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_primary_influencer' => 'boolean',
    ];

    public function voter()
    {
        return $this->belongsTo(Voter::class, 'voter_id');
    }

    public function relatedVoter()
    {
        return $this->belongsTo(Voter::class, 'related_voter_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
