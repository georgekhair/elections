<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {

            $table->index([
                'support_status',
                'is_voted',
                'polling_center_id'
            ], 'support_tracking_index');

        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {

            $table->dropIndex('support_tracking_index');

        });
    }

};
