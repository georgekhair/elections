<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'polling_center_id',
        'device_fingerprint',
        'is_active',
        'last_login_at',
        'supervisor_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getSystemTitleAttribute()
    {
        if ($this->hasRole('delegate')) return '🟢 وضع يوم الاقتراع';
        if ($this->hasRole('supervisor')) return '🟡 إدارة الميدان';
        if ($this->hasRole('operations')) return '🔴 غرفة العمليات';
        if ($this->hasRole('admin')) return '⚙️ لوحة التحكم';

        return 'Election System';
    }
    /*
    |--------------------------------------------------------------------------
    | Filament Access
    |--------------------------------------------------------------------------
    */

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            'admin',
            'operations',
            'supervisor'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Polling Center
    |--------------------------------------------------------------------------
    */

    public function pollingCenter()
    {
        return $this->belongsTo(PollingCenter::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isOperations(): bool
    {
        return $this->hasRole('operations');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isDelegate(): bool
    {
        return $this->hasRole('delegate');
    }

    /*
    |--------------------------------------------------------------------------
    | Hierarchy (FINAL VERSION)
    |--------------------------------------------------------------------------
    */

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function delegates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function hasSupervisor(): bool
    {
        return !is_null($this->supervisor_id);
    }

    public function hasDelegates(): bool
    {
        return $this->delegates()->exists();
    }

    public function isUnder(User $supervisor): bool
    {
        return $this->supervisor_id === $supervisor->id;
    }

    public function allDelegateIds(): array
    {
        return $this->delegates()->pluck('id')->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Voters Relationships
    |--------------------------------------------------------------------------
    */

    public function assignedVoters()
    {
        return $this->hasMany(Voter::class, 'assigned_user_id');
    }

    public function delegatedVoters()
    {
        return $this->hasMany(Voter::class, 'assigned_delegate_id');
    }

    public function supervisedVoters()
    {
        return $this->hasMany(Voter::class, 'supervisor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Tasks
    |--------------------------------------------------------------------------
    */

    public function assignedTasks()
    {
        return $this->hasMany(FieldTask::class, 'user_id');
    }

    public function createdTasks()
    {
        return $this->hasMany(FieldTask::class, 'created_by');
    }
}
