<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingCenter extends Model
{
    protected $fillable = [
        'name',
        'code',
        'lat',
        'lng',
        'latitude',
        'longitude',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function voters()
    {
        return $this->hasMany(Voter::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
    public function fieldTasks()
    {
        return $this->hasMany(FieldTask::class);
    }
}
