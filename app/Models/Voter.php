<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voter extends Model
{
    protected $fillable = [
        'voter_no',
        'location',
        'national_id',
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'full_name',
        'polling_center_id',
        'priority_level',
        'assigned_delegate_id',
        'supervisor_id',
        'support_status',
        'is_voted',
        'voted_at',
        'voted_by',
        'notes',
    ];

    public function delegate()
    {
        return $this->belongsTo(User::class, 'assigned_delegate_id');
    }

    protected function casts(): array
    {
        return [
            'is_voted' => 'boolean',
            'voted_at' => 'datetime',
        ];
    }

    public function pollingCenter()
    {
        return $this->belongsTo(PollingCenter::class);
    }

    public function votedBy()
    {
        return $this->belongsTo(User::class, 'voted_by');
    }

    // مفيد لاحقاً للعرض بالعربي
    public function getSupportStatusLabelAttribute(): string
    {
        return match ($this->support_status) {
            'supporter' => 'مضمون',
            'leaning' => 'يميل',
            'neutral' => 'محايد',
            'opponent' => 'ضد',
            default => 'غير معروف',
        };
    }
    public function scopeTarget($query)
    {
        return $query->whereIn('support_status', ['undecided', 'leaning'])
            ->orWhere(function ($q) {
                $q->where('support_status', 'supporter')
                ->where('is_voted', false)
                ->where('priority_level', 'high');
            });
    }

    public function voterNotes()
    {
        return $this->hasMany(VoterNote::class, 'voter_id');
    }

    public function actionableVoterNotes()
    {
        return $this->hasMany(VoterNote::class, 'voter_id')
            ->where('requires_action', true);
    }

    public function relationships()
    {
        return $this->hasMany(VoterRelationship::class, 'voter_id');
    }

    public function influencedBy()
    {
        return $this->hasMany(VoterRelationship::class, 'voter_id')
            ->where('is_primary_influencer', true);
    }

    public function relatedTo()
    {
        return $this->hasMany(VoterRelationship::class, 'related_voter_id');
    }

    public function assignedDelegate()
    {
        return $this->belongsTo(User::class, 'assigned_delegate_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
