<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_import_runs', function (Blueprint $table) {
            $table->unsignedInteger('updated_rows')->default(0)->after('imported_rows');
            $table->unsignedInteger('skipped_already_updated_rows')->default(0)->after('updated_rows');
            $table->unsignedInteger('skipped_no_change_rows')->default(0)->after('skipped_already_updated_rows');
            $table->unsignedInteger('not_found_rows')->default(0)->after('skipped_no_change_rows');
        });
    }

    public function down(): void
    {
        Schema::table('voter_import_runs', function (Blueprint $table) {
            $table->dropColumn([
                'updated_rows',
                'skipped_already_updated_rows',
                'skipped_no_change_rows',
                'not_found_rows',
            ]);
        });
    }
};
