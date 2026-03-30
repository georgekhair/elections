<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_relationships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voter_id')
                ->constrained('voters')
                ->cascadeOnDelete();

            $table->foreignId('related_voter_id')
                ->constrained('voters')
                ->cascadeOnDelete();

            $table->enum('relationship_type', [
                'spouse',
                'son',
                'daughter',
                'brother',
                'sister',
                'father',
                'mother',
                'relative',
                'friend',
                'neighbor',
                'influencer',
                'other',
            ])->default('other');

            $table->enum('influence_level', ['low', 'medium', 'high'])->default('medium');

            $table->boolean('is_primary_influencer')->default(false);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['voter_id', 'related_voter_id', 'relationship_type'], 'voter_relationship_unique');
            $table->index(['related_voter_id', 'is_primary_influencer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_relationships');
    }
};
