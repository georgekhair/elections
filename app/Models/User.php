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
    | Polling Center Relationship
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
    public function assignedTasks()
    {
        return $this->hasMany(FieldTask::class, 'user_id');
    }

    public function createdTasks()
    {
        return $this->hasMany(FieldTask::class, 'created_by');
    }
}
