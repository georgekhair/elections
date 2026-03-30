<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        // ADMIN
        $admin = User::updateOrCreate(
            ['email' => 'admin@election.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('admin123'),
                'polling_center_id' => null,
                'is_active' => true
            ]
        );

        $admin->syncRoles(['admin']);


        // OPERATIONS
        $operations = User::updateOrCreate(
            ['email' => 'operations@election.local'],
            [
                'name' => 'Operations Room',
                'password' => Hash::make('operations123'),
                'polling_center_id' => null,
                'is_active' => true
            ]
        );

        $operations->syncRoles(['operations']);


        // SUPERVISOR
        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@election.local'],
            [
                'name' => 'Center Supervisor',
                'password' => Hash::make('supervisor123'),
                'polling_center_id' => 1,
                'is_active' => true
            ]
        );

        $supervisor->syncRoles(['supervisor']);


        // DELEGATE
        $delegate = User::updateOrCreate(
            ['email' => 'delegate@election.local'],
            [
                'name' => 'Polling Delegate',
                'password' => Hash::make('delegate123'),
                'polling_center_id' => 1,
                'is_active' => true
            ]
        );

        $delegate->syncRoles(['delegate']);
    }
}
