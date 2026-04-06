<?php

use App\Models\Voter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            Voter::query()
                ->whereNotNull('assigned_delegate_id')
                ->update([
                    'assigned_user_id' => DB::raw('assigned_delegate_id'),
                ]);

            Voter::query()
                ->whereNull('assigned_delegate_id')
                ->whereNotNull('supervisor_id')
                ->update([
                    'assigned_user_id' => DB::raw('supervisor_id'),
                ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            Voter::query()->update([
                'assigned_user_id' => null,
            ]);
        });
    }
};
