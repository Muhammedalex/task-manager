<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\TaskDependencyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskDependencyRepositoryInterface $dependencyRepository
    ) {
    }

    /**
     * Get all tasks with filters (respects user permissions)
     */
    public function getAllTasks(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Managers can see all tasks, users can only see their assigned tasks
        if ($user->hasRole('Manager')) {
            return $this->taskRepository->getAll($filters, $perPage);
        }

        // Regular users can only see tasks assigned to them
        return $this->taskRepository->getByAssignedUser($user->id, $filters, $perPage);
    }

    /**
     * Get a specific task by code (respects user permissions)
     */
    public function getTaskByCode(User $user, string $code): ?Task
    {
        $task = $this->taskRepository->findByCode($code);

        if (!$task) {
            return null;
        }

        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return null;
        }

        return $task;
    }

    /**
     * Get task details with dependencies statistics
     * 
     * @return array|null 
     */
    public function getTaskDetailsWithStats(User $user, string $code): ?array
    {
        $task = $this->taskRepository->findByCode($code);

        // Check if task exists
        if (!$task) {
            return null; 
        }

        // Check permissions
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return ['has_permission' => false]; // Task exists but no permission
        }

       
        $task->load(['assignee', 'creator', 'dependencies.assignee', 'dependencies.creator']);

        $filteredDependencies = $task->dependencies;
        if (!$user->hasRole('Manager')) {
            $filteredDependencies = $task->dependencies->filter(function ($dependency) use ($user) {
                return $dependency->assigned_to === $user->id;
            });
        }

        // Get dependencies statistics (only for filtered dependencies)
        $dependenciesStats = $this->calculateDependenciesStats($filteredDependencies, $task);

        // Format dependencies with status for better visibility
        $dependencies = $filteredDependencies->map(function ($dependency) {
            return [
                'id' => $dependency->id,
                'code' => $dependency->code,
                'title' => $dependency->title,
                'status' => $dependency->status,
                'due_date' => $dependency->due_date?->format('Y-m-d'),
                'is_completed' => $dependency->status === 'completed',
                'assignee' => $dependency->assignee ? [
                    'id' => $dependency->assignee->id,
                    'name' => $dependency->assignee->name,
                    'email' => $dependency->assignee->email,
                ] : null,
            ];
        });

        // Format task data
        $taskData = $task->toArray();
        $taskData['dependencies'] = $dependencies;
        $taskData['dependencies_stats'] = $dependenciesStats;
        
        // Add a summary message
        if ($dependenciesStats['total'] > 0) {
            $taskData['dependencies_summary'] = sprintf(
                '%d of %d dependencies completed (%d remaining) - %.0f%%',
                $dependenciesStats['completed'],
                $dependenciesStats['total'],
                $dependenciesStats['remaining'],
                $dependenciesStats['completion_percentage']
            );
        } else {
            $taskData['dependencies_summary'] = 'No dependencies';
        }

        return $taskData;
    }

    /**
     * Calculate dependencies statistics for filtered dependencies
     */
    private function calculateDependenciesStats($dependencies, Task $task): array
    {
        $total = $dependencies->count();
        $completed = $dependencies->where('status', 'completed')->count();
        $pending = $dependencies->where('status', 'pending')->count();
        $inProgress = $dependencies->where('status', 'in_progress')->count();
        $canceled = $dependencies->where('status', 'canceled')->count();

        $completionPercentage = $total > 0
            ? round(($completed / $total) * 100, 2)
            : 100;

        // Check if task can be completed (all dependencies must be completed)
        $canBeCompleted = $dependencies->where('status', '!=', 'completed')->count() === 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'canceled' => $canceled,
            'remaining' => $total - $completed,
            'can_be_completed' => $canBeCompleted,
            'completion_percentage' => $completionPercentage,
        ];
    }

    /**
     * Get a specific task (respects user permissions)
     */
    public function getTaskById(User $user, int $taskId): ?Task
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            return null;
        }

        // Managers can see any task, users can only see tasks assigned to them
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return null;
        }

        return $task;
    }

    /**
     * Create a new task (Managers only)
     */
    public function createTask(User $user, array $data): Task
    {
        // Set the creator
        $data['created_by'] = $user->id;

        return $this->taskRepository->create($data);
    }

    /**
     * Update a task by code (Managers only)
     */
    public function updateTaskByCode(User $user, string $code, array $data): ?Task
    {
        return $this->taskRepository->updateByCode($code, $data);
    }

    /**
     * Update a task (Managers only)
     */
    public function updateTask(User $user, int $taskId, array $data): ?Task
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            return null;
        }

        return $this->taskRepository->update($taskId, $data);
    }

    /**
     * Update task status by code (Managers can update any, Users can update only assigned tasks)
     */
    public function updateTaskStatusByCode(User $user, string $code, string $status): ?Task
    {
        $task = $this->taskRepository->findByCode($code);

        if (!$task) {
            return null;
        }

        // Users can only update status of tasks assigned to them
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return null;
        }

        // Validate status value
        $validStatuses = ['pending', 'in_progress', 'completed', 'canceled'];
        if (!in_array($status, $validStatuses)) {
            return null;
        }

        // Business rule: Task cannot be completed if dependencies are not completed
        if ($status === 'completed' && !$task->canBeCompleted()) {
            return null;
        }

        return $this->taskRepository->updateStatusByCode($code, $status);
    }

    /**
     * Update task status (Managers can update any, Users can update only assigned tasks)
     */
    public function updateTaskStatus(User $user, int $taskId, string $status): ?Task
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            return null;
        }

        // Users can only update status of tasks assigned to them
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return null;
        }

        // Validate status value
        $validStatuses = ['pending', 'in_progress', 'completed', 'canceled'];
        if (!in_array($status, $validStatuses)) {
            return null;
        }

        // Business rule: Task cannot be completed if dependencies are not completed
        if ($status === 'completed' && !$task->canBeCompleted()) {
            return null;
        }

        return $this->taskRepository->updateStatus($taskId, $status);
    }

    /**
     * Delete a task by code (Managers only, soft delete)
     */
    public function deleteTaskByCode(string $code): bool
    {
        return $this->taskRepository->deleteByCode($code);
    }

    /**
     * Delete a task (Managers only, soft delete)
     */
    public function deleteTask(int $taskId): bool
    {
        return $this->taskRepository->delete($taskId);
    }

    /**
     * Assign task to a user by code (Managers only)
     */
    public function assignTaskToUserByCode(string $code, int $userId): ?Task
    {
        return $this->taskRepository->assignToUserByCode($code, $userId);
    }

    /**
     * Assign task to a user (Managers only)
     */
    public function assignTaskToUser(int $taskId, int $userId): ?Task
    {
        return $this->taskRepository->assignToUser($taskId, $userId);
    }

    /**
     * Get task dependencies by code
     */
    public function getTaskDependenciesByCode(User $user, string $code): array
    {
        $task = $this->taskRepository->findByCode($code);

        if (!$task) {
            return [];
        }

        // Managers can see dependencies of any task, users can only see dependencies of assigned tasks
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return [];
        }

        $dependencies = $this->taskRepository->getDependenciesByCode($code);

        // Filter dependencies based on user permissions
        // Managers can see all dependencies, Users can only see dependencies assigned to them
        if (!$user->hasRole('Manager')) {
            $dependencies = $dependencies->filter(function ($dependency) use ($user) {
                return $dependency->assigned_to === $user->id;
            });
        }

        return $dependencies->map(function ($dependency) {
            return [
                'id' => $dependency->id,
                'code' => $dependency->code,
                'title' => $dependency->title,
                'status' => $dependency->status,
                'due_date' => $dependency->due_date?->format('Y-m-d'),
                'assignee' => $dependency->assignee ? [
                    'id' => $dependency->assignee->id,
                    'name' => $dependency->assignee->name,
                    'email' => $dependency->assignee->email,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Get task dependencies
     */
    public function getTaskDependencies(User $user, int $taskId): array
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            return [];
        }

        // Managers can see dependencies of any task, users can only see dependencies of assigned tasks
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return [];
        }

        $dependencies = $this->taskRepository->getDependencies($taskId);

        // Filter dependencies based on user permissions
        // Managers can see all dependencies, Users can only see dependencies assigned to them
        if (!$user->hasRole('Manager')) {
            $dependencies = $dependencies->filter(function ($dependency) use ($user) {
                return $dependency->assigned_to === $user->id;
            });
        }

        return $dependencies->map(function ($dependency) {
            return [
                'id' => $dependency->id,
                'code' => $dependency->code,
                'title' => $dependency->title,
                'status' => $dependency->status,
                'due_date' => $dependency->due_date?->format('Y-m-d'),
                'assignee' => $dependency->assignee ? [
                    'id' => $dependency->assignee->id,
                    'name' => $dependency->assignee->name,
                    'email' => $dependency->assignee->email,
                ] : null,
            ];
        })->toArray();
    }
}

