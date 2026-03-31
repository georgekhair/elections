<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoterImportRun extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'valid_rows',
        'imported_rows',
        'updated_rows',
        'skipped_already_updated_rows',
        'skipped_no_change_rows',
        'not_found_rows',
        'skipped_rows',
        'error_rows',
        'created_by',
        'started_at',
        'finished_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function errors()
    {
        return $this->hasMany(VoterImportError::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
