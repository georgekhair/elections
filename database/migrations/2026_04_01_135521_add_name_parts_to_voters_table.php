<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('national_id');
            $table->string('father_name')->nullable()->after('first_name');
            $table->string('grandfather_name')->nullable()->after('father_name');
            $table->string('family_name')->nullable()->after('grandfather_name');

            $table->index('family_name');
            $table->index(['family_name', 'polling_center_id']);
        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->dropIndex(['family_name']);
            $table->dropIndex(['family_name', 'polling_center_id']);

            $table->dropColumn([
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
            ]);
        });
    }
};
