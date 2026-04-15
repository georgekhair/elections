<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Services\UserHierarchyService;
use App\Models\VoterContactLog;

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
        'assigned_user_id',
        'support_status',
        'is_voted',
        'voted_at',
        'voted_by',
        'notes',
    ];

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

    public function delegate()
    {
        return $this->belongsTo(User::class, 'assigned_delegate_id');
    }

    public function assignedDelegate()
    {
        return $this->belongsTo(User::class, 'assigned_delegate_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
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

    public function getSupportStatusLabelAttribute(): string
    {
        return match ($this->support_status) {
            'supporter' => 'مضمون',
            'leaning' => 'يميل',
            'undecided' => 'متردد',
            'opposed' => 'ضد',
            'traveling' => 'مسافر',
            default => 'غير معروف',
        };
    }

    public function getCurrentAssigneeAttribute(): ?User
    {
        if ($this->relationLoaded('assignedUser') && $this->assignedUser) {
            return $this->assignedUser;
        }

        if ($this->relationLoaded('delegate') && $this->delegate) {
            return $this->delegate;
        }

        if ($this->relationLoaded('supervisor') && $this->supervisor) {
            return $this->supervisor;
        }

        return $this->assignedUser ?? $this->delegate ?? $this->supervisor;
    }

    public function getCurrentAssigneeTypeAttribute(): ?string
    {
        if ($this->assigned_delegate_id) {
            return 'delegate';
        }

        if ($this->supervisor_id) {
            return 'supervisor';
        }

        if ($this->assigned_user_id && $this->assignedUser) {
            return $this->assignedUser->hasRole('supervisor') ? 'supervisor' : 'delegate';
        }

        return null;
    }

    public function scopeTarget(Builder $query): Builder
    {
        return $query->whereIn('support_status', ['undecided', 'leaning'])
            ->orWhere(function ($q) {
                $q->where('support_status', 'supporter')
                    ->where('is_voted', false)
                    ->where('priority_level', 'high');
            });
    }

    public function scopeVisibleTo($query, User $user)
    {
        // ADMIN → sees everything
        if ($user->hasRole('admin')) {
            return $query;
        }

        // OPERATIONS → sees everything
        if ($user->hasRole('operations')) {
            return $query;
        }

        // SUPERVISOR
        if ($user->hasRole('supervisor')) {

            $delegateIds = $user->delegates()->pluck('id');

            return $query->where(function ($q) use ($user, $delegateIds) {
                $q->where('supervisor_id', $user->id)
                ->orWhereIn('assigned_delegate_id', $delegateIds);
            });
        }

        // DELEGATE
        if ($user->hasRole('delegate')) {
            return $query->where('assigned_delegate_id', $user->id);
        }

        // fallback → nothing
        return $query->whereRaw('1=0');
    }
    public function votedByUser()
    {
        return $this->belongsTo(User::class, 'voted_by');
    }
    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        $search = trim($search);

        // normalize Arabic
        $search = str_replace(['أ','إ','آ'], 'ا', $search);

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function ($q) use ($terms, $search) {

            // numeric search
            if (is_numeric($search)) {
                $q->orWhere('national_id', 'like', "%{$search}%")
                ->orWhere('voter_no', $search);
            }

            // every word must exist somewhere in the full name
            foreach ($terms as $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('full_name', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('father_name', 'like', "%{$term}%")
                        ->orWhere('grandfather_name', 'like', "%{$term}%")
                        ->orWhere('family_name', 'like', "%{$term}%");
                });
            }
        });
    }

    public function contactLogs()
    {
        return $this->hasMany(VoterContactLog::class);
    }
}
