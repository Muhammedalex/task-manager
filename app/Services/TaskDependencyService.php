<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskDependencyRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TaskDependencyService
{
    public function __construct(
        private TaskDependencyRepositoryInterface $dependencyRepository,
        private TaskRepositoryInterface $taskRepository
    ) {
    }

    /**
     * Get all dependencies for a task
     */
    public function getDependencies(int $taskId): Collection
    {
        return $this->dependencyRepository->getDependencies($taskId);
    }

    /**
     * Get task dependencies by code with permission check
     */
    public function getTaskDependenciesByCode(User $user, string $code): ?array
    {
        $task = $this->taskRepository->findByCode($code);

        if (!$task) {
            return null;
        }

        // Managers can see dependencies of any task, users can only see dependencies of assigned tasks
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return null;
        }

        $dependencies = $this->dependencyRepository->getDependenciesByCode($code);

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
     * Get task dependencies with permission check
     */
    public function getTaskDependencies(User $user, int $taskId): ?array
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            return null;
        }

        // Managers can see dependencies of any task, users can only see dependencies of assigned tasks
        if (!$user->hasRole('Manager') && $task->assigned_to !== $user->id) {
            return null;
        }

        $dependencies = $this->dependencyRepository->getDependencies($taskId);

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
     * Add dependencies to a task by code
     * 
     * Business rules:
     * - Cannot add self as dependency
     * - Cannot create circular dependencies
     * - Cannot add duplicate dependencies
     */
    public function addDependenciesByCode(string $taskCode, array $dependencyCodes): array
    {
        // Get task by code
        $task = $this->taskRepository->findByCode($taskCode);
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found',
                'task' => null,
            ];
        }

        // Convert dependency codes to IDs
        $dependencyIds = [];
        foreach ($dependencyCodes as $code) {
            $depTask = $this->taskRepository->findByCode($code);
            if ($depTask) {
                $dependencyIds[] = $depTask->id;
            }
        }

        return $this->addDependencies($task->id, $dependencyIds);
    }

    /**
     * Add dependencies to a task
     * 
     * Business rules:
     * - Cannot add self as dependency
     * - Cannot create circular dependencies
     * - Cannot add duplicate dependencies
     */
    public function addDependencies(int $taskId, array $dependencyIds): array
    {
        // Validate that task exists
        if (!$this->taskRepository->exists($taskId)) {
            return [
                'success' => false,
                'message' => 'Task not found',
                'task' => null,
            ];
        }

        // Remove duplicates
        $dependencyIds = array_unique($dependencyIds);

        $added = [];
        $skipped = [];
        $errors = [];

        foreach ($dependencyIds as $dependencyId) {
            // Check if dependency task exists
            if (!$this->taskRepository->exists($dependencyId)) {
                $errors[] = "Dependency task with ID {$dependencyId} not found";
                continue;
            }

            // Cannot add self as dependency
            if ($taskId === $dependencyId) {
                $skipped[] = [
                    'id' => $dependencyId,
                    'reason' => 'Cannot add self as dependency',
                ];
                continue;
            }

            // Check if dependency already exists
            if ($this->dependencyRepository->dependencyExists($taskId, $dependencyId)) {
                $skipped[] = [
                    'id' => $dependencyId,
                    'reason' => 'Dependency already exists',
                ];
                continue;
            }

            // Check for circular dependencies
            if ($this->dependencyRepository->hasCircularDependency($taskId, $dependencyId)) {
                $skipped[] = [
                    'id' => $dependencyId,
                    'reason' => 'Circular dependency detected',
                ];
                continue;
            }

            // Add dependency
            $added[] = $dependencyId;
        }

        // If there are dependencies to add, add them
        if (!empty($added)) {
            $task = $this->dependencyRepository->addDependencies($taskId, $added);
        } else {
            $task = $this->taskRepository->findById($taskId, ['dependencies']);
        }

        return [
            'success' => true,
            'message' => 'Dependencies processed',
            'task' => $task,
            'added' => $added,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Remove a dependency from a task by code
     */
    public function removeDependencyByCode(string $taskCode, string $dependencyCode): array
    {
        // Get task by code
        $task = $this->taskRepository->findByCode($taskCode);
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found',
            ];
        }

        // Get dependency task by code
        $dependencyTask = $this->taskRepository->findByCode($dependencyCode);
        if (!$dependencyTask) {
            return [
                'success' => false,
                'message' => 'Dependency task not found',
            ];
        }

        return $this->removeDependency($task->id, $dependencyTask->id);
    }

    /**
     * Remove a dependency from a task
     */
    public function removeDependency(int $taskId, int $dependencyId): array
    {
        // Validate that task exists
        if (!$this->taskRepository->exists($taskId)) {
            return [
                'success' => false,
                'message' => 'Task not found',
            ];
        }

        // Check if dependency exists
        if (!$this->dependencyRepository->dependencyExists($taskId, $dependencyId)) {
            return [
                'success' => false,
                'message' => 'Dependency not found',
            ];
        }

        $removed = $this->dependencyRepository->removeDependency($taskId, $dependencyId);

        if ($removed) {
            $task = $this->taskRepository->findById($taskId, ['dependencies']);
            return [
                'success' => true,
                'message' => 'Dependency removed successfully',
                'task' => $task,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to remove dependency',
        ];
    }
}

