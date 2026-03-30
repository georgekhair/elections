<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_projection_snapshots', function (Blueprint $table) {

            $table->id();

            // الأصوات المدخلة لكل قائمة
            $table->json('input_votes');

            // المقاعد المتوقعة
            $table->json('projected_seats');

            // من قام بالحساب
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_projection_snapshots');
    }
};
