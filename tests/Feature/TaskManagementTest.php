<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        DB::table('roles')->insert([
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['name' => 'User', 'guard_name' => 'web'],
        ]);
    }

    /**
     * Test: Managers can create tasks
     * Requirement: Managers can create/update a task
     */
    public function test_managers_can_create_tasks(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        Sanctum::actingAs($manager);
        
        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => '2025-12-31',
        ]);
        
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'created_by' => $manager->id,
        ]);
    }

    /**
     * Test: Users cannot create tasks
     * Requirement: Managers can create/update a task
     */
    public function test_users_cannot_create_tasks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task',
        ]);
        
        $response->assertStatus(403);
    }

    /**
     * Test: Users can only view their assigned tasks
     * Requirement: Users can retrieve only tasks assigned to them
     */
    public function test_users_can_only_view_assigned_tasks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        $assignedTask = Task::factory()->create([
            'assigned_to' => $user->id,
            'created_by' => $manager->id,
        ]);
        
        $otherTask = Task::factory()->create([
            'assigned_to' => $manager->id,
            'created_by' => $manager->id,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        $taskIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($assignedTask->id, $taskIds);
        $this->assertNotContains($otherTask->id, $taskIds);
    }

    /**
     * Test: Users can update only status of assigned tasks
     * Requirement: Users can update only the status of the task assigned to them
     */
    public function test_users_can_update_status_of_assigned_tasks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        
        $task = Task::factory()->create([
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->patchJson("/api/tasks/{$task->code}/status", [
            'status' => 'in_progress',
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Test: Task cannot be completed if dependencies are not completed
     * Requirement: A task cannot be completed until all its dependencies are completed
     */
    public function test_cannot_complete_task_with_incomplete_dependencies(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        $dependency = Task::factory()->create([
            'status' => 'pending', // Not completed
            'created_by' => $manager->id,
        ]);
        
        $task = Task::factory()->create([
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);
        
        $task->dependencies()->attach($dependency->id);
        
        Sanctum::actingAs($manager);
        
        $response = $this->patchJson("/api/tasks/{$task->code}/status", [
            'status' => 'completed',
        ]);
        
        $response->assertStatus(400)
            ->assertJson(['success' => false]);
        
        $this->assertStringContainsString('dependencies', $response->json('message'));
    }

    /**
     * Test: Task can be completed when all dependencies are completed
     * Requirement: A task cannot be completed until all its dependencies are completed
     */
    public function test_can_complete_task_when_all_dependencies_completed(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        $dependency = Task::factory()->create([
            'status' => 'completed',
            'created_by' => $manager->id,
        ]);
        
        $task = Task::factory()->create([
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);
        
        $task->dependencies()->attach($dependency->id);
        
        Sanctum::actingAs($manager);
        
        $response = $this->patchJson("/api/tasks/{$task->code}/status", [
            'status' => 'completed',
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test: Managers can assign tasks to users
     * Requirement: Managers can assign tasks to a user
     */
    public function test_managers_can_assign_tasks_to_users(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        $user = User::factory()->create();
        $user->assignRole('User');
        
        $task = Task::factory()->create([
            'created_by' => $manager->id,
        ]);
        
        Sanctum::actingAs($manager);
        
        $response = $this->postJson("/api/tasks/{$task->code}/assign", [
            'user_id' => $user->id,
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $user->id,
        ]);
    }

    /**
     * Test: Filter tasks by status
     * Requirement: Retrieve a list of all tasks, and allow filtering based on status
     */
    public function test_can_filter_tasks_by_status(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        Task::factory()->create(['status' => 'pending', 'created_by' => $manager->id]);
        Task::factory()->create(['status' => 'completed', 'created_by' => $manager->id]);
        Task::factory()->create(['status' => 'in_progress', 'created_by' => $manager->id]);
        
        Sanctum::actingAs($manager);
        
        $response = $this->getJson('/api/tasks?status=completed');
        
        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        foreach ($tasks as $task) {
            $this->assertEquals('completed', $task['status']);
        }
    }

    /**
     * Test: Retrieve task details with dependencies
     * Requirement: Retrieve details of a specific task including dependencies
     */
    public function test_can_retrieve_task_details_with_dependencies(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        
        $dependency1 = Task::factory()->create([
            'status' => 'completed',
            'created_by' => $manager->id,
        ]);
        
        $dependency2 = Task::factory()->create([
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);
        
        $task = Task::factory()->create([
            'created_by' => $manager->id,
        ]);
        
        $task->dependencies()->attach([$dependency1->id, $dependency2->id]);
        
        Sanctum::actingAs($manager);
        
        $response = $this->getJson("/api/tasks/{$task->code}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'dependencies',
                    'dependencies_stats' => [
                        'total',
                        'completed',
                        'remaining',
                        'completion_percentage',
                        'can_be_completed',
                    ],
                ],
            ]);
        
        $data = $response->json('data');
        $this->assertCount(2, $data['dependencies']);
        $this->assertEquals(2, $data['dependencies_stats']['total']);
        $this->assertEquals(1, $data['dependencies_stats']['completed']);
        $this->assertEquals(1, $data['dependencies_stats']['remaining']);
        $this->assertFalse($data['dependencies_stats']['can_be_completed']);
    }
}

