<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $managerRole = Role::where('name', 'Manager')->first();
        $userRole = Role::where('name', 'User')->first();

        if (!$managerRole || !$userRole) {
            $this->command->error('Roles not found! Please run RolePermissionSeeder first.');
            return;
        }

        // Create Manager users
        $managers = [
            [
                'name' => 'John Manager',
                'email' => 'manager@softxpert.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sarah Manager',
                'email' => 'sarah.manager@softxpert.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($managers as $managerData) {
            $manager = User::firstOrCreate(
                ['email' => $managerData['email']],
                $managerData
            );
            $manager->assignRole($managerRole);
            $this->command->info("Created Manager: {$manager->email}");
        }

        // Create regular User accounts
        $users = [
            [
                'name' => 'Alice Developer',
                'email' => 'alice@softxpert.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Bob Developer',
                'email' => 'bob@softxpert.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Charlie Tester',
                'email' => 'charlie@softxpert.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Diana Designer',
                'email' => 'diana@softxpert.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            $user->assignRole($userRole);
            $this->command->info("Created User: {$user->email}");
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Default password for all users: password');
    }
}
