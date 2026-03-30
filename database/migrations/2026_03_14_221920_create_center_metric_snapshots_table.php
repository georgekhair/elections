<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_metric_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('polling_center_id')
                ->constrained('polling_centers')
                ->cascadeOnDelete();

            $table->unsignedInteger('voters_total')->default(0);
            $table->unsignedInteger('voted_count')->default(0);

            $table->unsignedInteger('supporters_total')->default(0);
            $table->unsignedInteger('supporters_voted')->default(0);
            $table->unsignedInteger('supporters_remaining')->default(0);

            $table->unsignedTinyInteger('supporter_turnout')->default(0);

            $table->timestamp('captured_at')->nullable();

            $table->timestamps();

            $table->index(['polling_center_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_metric_snapshots');
    }
};
