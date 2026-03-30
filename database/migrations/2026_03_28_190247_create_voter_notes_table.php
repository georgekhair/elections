<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voter_id')
                ->constrained('voters')
                ->cascadeOnDelete();

            $table->enum('note_type', [
                'general',
                'transportation',
                'persuasion',
                'health',
                'contact',
                'risk',
                'family',
                'influencer',
            ])->default('general');

            $table->text('content');

            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            $table->boolean('requires_action')->default(false);

            $table->timestamp('action_due_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['voter_id', 'note_type']);
            $table->index(['requires_action', 'priority']);
            $table->index(['action_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_notes');
    }
};
