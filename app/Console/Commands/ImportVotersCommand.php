<?php

namespace App\Console\Commands;

use App\Imports\VotersImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportVotersCommand extends Command
{
    protected $signature = 'voters:import {file}';
    protected $description = 'Import voters from CSV or Excel file';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("الملف غير موجود: {$file}");
            return self::FAILURE;
        }

        Excel::import(new VotersImport, $file);

        $this->info('تم استيراد الناخبين بنجاح.');
        return self::SUCCESS;
    }
}
