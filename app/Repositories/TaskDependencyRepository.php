<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Contracts\TaskDependencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskDependencyRepository implements TaskDependencyRepositoryInterface
{
    /**
     * Get all dependencies for a task
     */
    public function getDependencies(int $taskId): Collection
    {
        $task = Task::find($taskId);
        
        if (!$task) {
            return new Collection();
        }

        return $task->dependencies()->with(['assignee', 'creator'])->get();
    }

    /**
     * Get all dependencies for a task by code
     */
    public function getDependenciesByCode(string $code): Collection
    {
        $task = Task::where('code', $code)->first();
        
        if (!$task) {
            return new Collection();
        }

        return $task->dependencies()->with(['assignee', 'creator'])->get();
    }

    /**
     * Add dependencies to a task
     */
    public function addDependencies(int $taskId, array $dependencyIds): Task
    {
        $task = Task::findOrFail($taskId);
        
        // Attach dependencies (sync will prevent duplicates)
        $task->dependencies()->syncWithoutDetaching($dependencyIds);
        
        return $task->fresh(['dependencies']);
    }

    /**
     * Remove a dependency from a task
     */
    public function removeDependency(int $taskId, int $dependencyId): bool
    {
        $task = Task::find($taskId);
        
        if (!$task) {
            return false;
        }

        return $task->dependencies()->detach($dependencyId) > 0;
    }

    /**
     * Check if a dependency exists
     */
    public function dependencyExists(int $taskId, int $dependencyId): bool
    {
        return DB::table('task_dependencies')
            ->where('task_id', $taskId)
            ->where('depends_on_task_id', $dependencyId)
            ->exists();
    }

    /**
     * Check for circular dependencies
     * 
     * A circular dependency exists if:
     * - The dependency task (or any of its dependencies) depends on the original task
     */
    public function hasCircularDependency(int $taskId, int $dependencyId): bool
    {
        // Direct circular: dependency depends on task
        if ($this->dependencyExists($dependencyId, $taskId)) {
            return true;
        }

        // Indirect circular: check if any dependency of the dependency depends on task
        $visited = [];
        return $this->checkCircularRecursive($dependencyId, $taskId, $visited);
    }

    /**
     * Recursive helper to check for circular dependencies
     */
    private function checkCircularRecursive(int $currentTaskId, int $targetTaskId, array &$visited): bool
    {
        // Prevent infinite loops
        if (in_array($currentTaskId, $visited)) {
            return false;
        }

        $visited[] = $currentTaskId;

        // Get all dependencies of current task
        $dependencies = DB::table('task_dependencies')
            ->where('task_id', $currentTaskId)
            ->pluck('depends_on_task_id')
            ->toArray();

        foreach ($dependencies as $depId) {
            if ($depId == $targetTaskId) {
                return true;
            }

            if ($this->checkCircularRecursive($depId, $targetTaskId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all tasks that depend on a specific task
     */
    public function getDependents(int $taskId): Collection
    {
        $task = Task::find($taskId);
        
        if (!$task) {
            return new Collection();
        }

        return $task->dependents()->with(['assignee', 'creator'])->get();
    }
}

