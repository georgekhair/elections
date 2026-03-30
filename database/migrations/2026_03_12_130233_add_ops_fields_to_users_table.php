<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('polling_center_id')
                ->nullable()
                ->after('password')
                ->constrained('polling_centers')
                ->nullOnDelete();

            $table->string('device_fingerprint')
                ->nullable()
                ->after('polling_center_id');

            $table->boolean('is_active')
                ->default(true)
                ->after('device_fingerprint');

            $table->timestamp('last_login_at')
                ->nullable()
                ->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('polling_center_id');
            $table->dropColumn([
                'device_fingerprint',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
