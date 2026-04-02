<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    DB::statement("
        ALTER TABLE voters
        MODIFY support_status
        ENUM('supporter','leaning','undecided','opposed','unknown','traveling')
        DEFAULT 'unknown'
    ");
}

public function down()
{
    DB::statement("
        ALTER TABLE voters
        MODIFY support_status
        ENUM('supporter','leaning','undecided','opposed','unknown')
        DEFAULT 'unknown'
    ");
}
};
