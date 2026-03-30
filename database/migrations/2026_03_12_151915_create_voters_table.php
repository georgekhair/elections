<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->id();

            // من ملف CSV
            $table->unsignedInteger('voter_no')->nullable();           // No
            $table->string('location')->nullable();                    // النص الأصلي Location
            $table->string('national_id')->nullable();                 // ID
            $table->string('full_name');                              // FullName

            // الربط بالمركز
            $table->foreignId('polling_center_id')
                ->constrained('polling_centers')
                ->cascadeOnDelete();

            // تصنيف الناخب بالنسبة للقائمة
            $table->enum('support_status', [
                'supporter',
                'leaning',
                'neutral',
                'opponent',
                'unknown',
            ])->default('unknown');

            // حالة التصويت
            $table->boolean('is_voted')->default(false);
            $table->timestamp('voted_at')->nullable();
            $table->foreignId('voted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ملاحظات
            $table->text('notes')->nullable();

            $table->timestamps();

            // فهارس مهمة جداً للأداء
            $table->index('polling_center_id');
            $table->index('national_id');
            $table->index('full_name');
            $table->index('support_status');
            $table->index('is_voted');
            $table->index(['polling_center_id', 'is_voted']);
            $table->index(['polling_center_id', 'support_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
