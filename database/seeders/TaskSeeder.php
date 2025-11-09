<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users
        $manager = User::where('email', 'manager@softxpert.com')->first();
        $sarahManager = User::where('email', 'sarah.manager@softxpert.com')->first();
        $alice = User::where('email', 'alice@softxpert.com')->first();
        $bob = User::where('email', 'bob@softxpert.com')->first();
        $charlie = User::where('email', 'charlie@softxpert.com')->first();
        $diana = User::where('email', 'diana@softxpert.com')->first();

        if (!$manager || !$alice) {
            $this->command->error('Users not found! Please run UserSeeder first.');
            return;
        }

        // Create tasks with various statuses and scenarios
        $tasks = [
            // Completed tasks (for dependencies)
            [
                'title' => 'Setup Development Environment',
                'description' => 'Install and configure development tools, IDE, and necessary software for the project.',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(10),
                'assigned_to' => $alice->id,
                'created_by' => $manager->id,
                'completed_at' => Carbon::now()->subDays(8),
            ],
            [
                'title' => 'Database Schema Design',
                'description' => 'Design the complete database schema including all tables, relationships, and constraints.',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(8),
                'assigned_to' => $alice->id,
                'created_by' => $manager->id,
                'completed_at' => Carbon::now()->subDays(6),
            ],
            [
                'title' => 'API Authentication Setup',
                'description' => 'Implement JWT/Sanctum authentication system with role-based access control.',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(5),
                'assigned_to' => $bob->id,
                'created_by' => $manager->id,
                'completed_at' => Carbon::now()->subDays(3),
            ],

            // Pending tasks with dependencies
            [
                'title' => 'Implement Task CRUD Operations',
                'description' => 'Create RESTful API endpoints for creating, reading, updating, and deleting tasks.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(5),
                'assigned_to' => $alice->id,
                'created_by' => $manager->id,
            ],
            [
                'title' => 'Task Dependencies Feature',
                'description' => 'Implement task dependency system where tasks cannot be completed until dependencies are completed.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(7),
                'assigned_to' => $bob->id,
                'created_by' => $sarahManager?->id ?? $manager->id,
            ],
            [
                'title' => 'Task Filtering and Search',
                'description' => 'Implement filtering by status, due date range, and assigned user. Add search functionality.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(6),
                'assigned_to' => $alice->id,
                'created_by' => $manager->id,
            ],

            // In progress tasks
            [
                'title' => 'Role-Based Access Control Implementation',
                'description' => 'Implement RBAC system where managers can create/update/assign tasks, and users can only view and update status of assigned tasks.',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(3),
                'assigned_to' => $bob->id,
                'created_by' => $manager->id,
            ],
            [
                'title' => 'API Documentation',
                'description' => 'Create comprehensive API documentation with Postman collection and endpoint descriptions.',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(4),
                'assigned_to' => $charlie->id,
                'created_by' => $sarahManager?->id ?? $manager->id,
            ],

            // Tasks with different assignees
            [
                'title' => 'UI Design Mockups',
                'description' => 'Create UI/UX mockups for the task management system frontend.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(10),
                'assigned_to' => $diana->id,
                'created_by' => $manager->id,
            ],
            [
                'title' => 'Unit Testing',
                'description' => 'Write comprehensive unit tests for all API endpoints and business logic.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(8),
                'assigned_to' => $charlie->id,
                'created_by' => $sarahManager?->id ?? $manager->id,
            ],
            [
                'title' => 'Integration Testing',
                'description' => 'Perform integration testing for the complete system including database, API, and authentication.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(12),
                'assigned_to' => $charlie->id,
                'created_by' => $manager->id,
            ],

            // Canceled task
            [
                'title' => 'Legacy System Migration',
                'description' => 'Migrate data from legacy task management system (cancelled due to scope change).',
                'status' => 'canceled',
                'due_date' => Carbon::now()->subDays(2),
                'assigned_to' => $bob->id,
                'created_by' => $manager->id,
                'canceled_at' => Carbon::now()->subDay(),
            ],

            // Tasks with near due dates
            [
                'title' => 'Code Review',
                'description' => 'Review all code changes and ensure code quality standards are met.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDay(),
                'assigned_to' => $alice->id,
                'created_by' => $sarahManager?->id ?? $manager->id,
            ],
            [
                'title' => 'Performance Optimization',
                'description' => 'Optimize database queries and API response times.',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(2),
                'assigned_to' => $bob->id,
                'created_by' => $manager->id,
            ],
        ];

        $createdTasks = [];
        foreach ($tasks as $taskData) {
            $task = Task::create($taskData);
            $createdTasks[] = $task;
            $this->command->info("Created task: {$task->title} (Status: {$task->status})");
        }

        // Create task dependencies
        // Task "Implement Task CRUD Operations" depends on "Database Schema Design" and "API Authentication Setup"
        $crudTask = collect($createdTasks)->firstWhere('title', 'Implement Task CRUD Operations');
        $schemaTask = collect($createdTasks)->firstWhere('title', 'Database Schema Design');
        $authTask = collect($createdTasks)->firstWhere('title', 'API Authentication Setup');

        if ($crudTask && $schemaTask && $authTask) {
            $crudTask->dependencies()->attach([$schemaTask->id, $authTask->id]);
            $this->command->info("Added dependencies for 'Implement Task CRUD Operations'");
        }

        // Task "Task Dependencies Feature" depends on "Implement Task CRUD Operations"
        $dependenciesTask = collect($createdTasks)->firstWhere('title', 'Task Dependencies Feature');
        if ($dependenciesTask && $crudTask) {
            $dependenciesTask->dependencies()->attach($crudTask->id);
            $this->command->info("Added dependency for 'Task Dependencies Feature'");
        }

        // Task "Task Filtering and Search" depends on "Implement Task CRUD Operations"
        $filterTask = collect($createdTasks)->firstWhere('title', 'Task Filtering and Search');
        if ($filterTask && $crudTask) {
            $filterTask->dependencies()->attach($crudTask->id);
            $this->command->info("Added dependency for 'Task Filtering and Search'");
        }

        // Task "Integration Testing" depends on "Unit Testing" and "Task Dependencies Feature"
        $integrationTask = collect($createdTasks)->firstWhere('title', 'Integration Testing');
        $unitTestTask = collect($createdTasks)->firstWhere('title', 'Unit Testing');
        if ($integrationTask && $unitTestTask && $dependenciesTask) {
            $integrationTask->dependencies()->attach([$unitTestTask->id, $dependenciesTask->id]);
            $this->command->info("Added dependencies for 'Integration Testing'");
        }

        // Task "Code Review" depends on "Role-Based Access Control Implementation"
        $codeReviewTask = collect($createdTasks)->firstWhere('title', 'Code Review');
        $rbacTask = collect($createdTasks)->firstWhere('title', 'Role-Based Access Control Implementation');
        if ($codeReviewTask && $rbacTask) {
            $codeReviewTask->dependencies()->attach($rbacTask->id);
            $this->command->info("Added dependency for 'Code Review'");
        }

        $this->command->info('Tasks seeded successfully with dependencies!');
        $this->command->info('Total tasks created: ' . count($createdTasks));
    }
}
