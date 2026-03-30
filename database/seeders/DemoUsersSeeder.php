<?php

namespace Database\Seeders;

use App\Models\PollingCenter;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $centers = PollingCenter::all();

        foreach ($centers as $center) {
            // مشرف لكل مركز
            $supervisor = User::firstOrCreate(
                ['email' => 'supervisor-' . $center->code . '@ops.local'],
                [
                    'name' => 'مشرف ' . $center->name,
                    'password' => bcrypt('Password123!'),
                    'polling_center_id' => $center->id,
                    'is_active' => true,
                ]
            );
            $supervisor->syncRoles(['supervisor']);

            // 5 مندوبين تجريبيين لكل مركز
            for ($i = 1; $i <= 5; $i++) {
                $delegate = User::firstOrCreate(
                    ['email' => 'delegate' . $i . '-' . $center->code . '@ops.local'],
                    [
                        'name' => 'مندوب ' . $i . ' - ' . $center->name,
                        'password' => bcrypt('Password123!'),
                        'polling_center_id' => $center->id,
                        'is_active' => true,
                    ]
                );
                $delegate->syncRoles(['delegate']);
            }
        }

        // مستخدم غرفة عمليات
        $ops = User::firstOrCreate(
            ['email' => 'operations@ops.local'],
            [
                'name' => 'غرفة العمليات',
                'password' => bcrypt('Password123!'),
                'is_active' => true,
            ]
        );
        $ops->syncRoles(['operations']);
    }
}
