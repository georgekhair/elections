<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_relationships', function (Blueprint $table) {
            $table->foreignId('related_voter_id')->nullable()->change();
            $table->string('related_name')->nullable()->after('related_voter_id');
            $table->boolean('is_unconfirmed')->default(false)->after('is_primary_influencer');
        });
    }

    public function down(): void
    {
        Schema::table('voter_relationships', function (Blueprint $table) {
            $table->dropColumn(['related_name', 'is_unconfirmed']);
            $table->foreignId('related_voter_id')->nullable(false)->change();
        });
    }
};
