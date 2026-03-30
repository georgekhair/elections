<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoterImportError extends Model
{
    protected $fillable = [
        'voter_import_run_id',
        'row_number',
        'error_type',
        'message',
        'row_data',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
        ];
    }

    public function run()
    {
        return $this->belongsTo(VoterImportRun::class, 'voter_import_run_id');
    }
}
