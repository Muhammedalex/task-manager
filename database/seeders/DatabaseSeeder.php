<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');
        $this->command->newLine();

        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);
        $this->command->newLine();

        // Seed users
        $this->call(UserSeeder::class);
        $this->command->newLine();

        // Seed tasks
        $this->call(TaskSeeder::class);
        $this->command->newLine();

        $this->command->info('Database seeding completed successfully!');
    }
}
