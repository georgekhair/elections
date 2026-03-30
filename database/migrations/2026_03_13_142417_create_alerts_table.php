<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            $table->string('type');          // turnout_low, stagnation, projection_change...
            $table->string('severity');      // info, warning, danger, critical
            $table->string('title');
            $table->text('message');

            $table->foreignId('polling_center_id')
                ->nullable()
                ->constrained('polling_centers')
                ->nullOnDelete();

            $table->json('meta')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['polling_center_id', 'is_active']);
            $table->index(['severity', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
