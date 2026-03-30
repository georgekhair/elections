<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('polling_center_id')
                ->nullable()
                ->constrained('polling_centers')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type'); // mobilization, supervisor_call, delegate_followup, etc.
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->text('description');

            $table->string('status')->default('pending'); // pending, in_progress, done, cancelled

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['polling_center_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_tasks');
    }
};
