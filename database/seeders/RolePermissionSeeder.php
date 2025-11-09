<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Task permissions
            'tasks.create',
            'tasks.view',
            'tasks.view.own',
            'tasks.update',
            'tasks.update.own',
            'tasks.update.status',
            'tasks.update.status.own',
            'tasks.delete',
            'tasks.assign',
            'tasks.view.all',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Create roles
        $managerRole = Role::firstOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['name' => 'Manager', 'guard_name' => 'web']
        );

        $userRole = Role::firstOrCreate(
            ['name' => 'User', 'guard_name' => 'web'],
            ['name' => 'User', 'guard_name' => 'web']
        );

        // Assign permissions to Manager role
        $managerRole->givePermissionTo([
            'tasks.create',
            'tasks.view',
            'tasks.view.all',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',
        ]);

        // Assign permissions to User role
        $userRole->givePermissionTo([
            'tasks.view.own',
            'tasks.update.status.own',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Manager role created with full task management permissions.');
        $this->command->info('User role created with own task viewing and status update permissions.');
    }
}
