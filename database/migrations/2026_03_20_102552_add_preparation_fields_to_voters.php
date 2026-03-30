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
        Schema::table('voters', function (Blueprint $table) {

            if (!Schema::hasColumn('voters', 'support_status')) {
                $table->string('support_status')->default('unknown')->index();
            }

            if (!Schema::hasColumn('voters', 'priority_level')) {
                $table->string('priority_level')->default('low')->index();
            }

            if (!Schema::hasColumn('voters', 'assigned_delegate_id')) {
                $table->foreignId('assigned_delegate_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->index();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            //
        });
    }
};
