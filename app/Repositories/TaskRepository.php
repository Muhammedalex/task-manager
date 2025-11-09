<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks with optional filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with(['assignee', 'creator', 'dependencies']);

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by assigned user
        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Filter by due date range
        if (isset($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (isset($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        // Search in title and description
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Order by due date (ascending) then by created_at (descending)
        $query->orderBy('due_date', 'asc')
              ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get tasks assigned to a specific user
     */
    public function getByAssignedUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['assigned_to'] = $userId;
        return $this->getAll($filters, $perPage);
    }

    /**
     * Find a task by ID with relationships
     */
    public function findById(int $id, array $with = []): ?Task
    {
        $defaultWith = ['assignee', 'creator', 'dependencies'];
        $with = !empty($with) ? $with : $defaultWith;

        return Task::with($with)->find($id);
    }

    /**
     * Find a task by code with relationships
     */
    public function findByCode(string $code, array $with = []): ?Task
    {
        $defaultWith = ['assignee', 'creator', 'dependencies'];
        $with = !empty($with) ? $with : $defaultWith;

        return Task::with($with)->where('code', $code)->first();
    }

    /**
     * Create a new task
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Update a task by code
     */
    public function updateByCode(string $code, array $data): ?Task
    {
        $task = Task::where('code', $code)->first();
        
        if (!$task) {
            return null;
        }

        $task->update($data);
        return $task->fresh(['assignee', 'creator', 'dependencies']);
    }

    /**
     * Update a task
     */
    public function update(int $id, array $data): ?Task
    {
        $task = Task::find($id);
        
        if (!$task) {
            return null;
        }

        $task->update($data);
        return $task->fresh(['assignee', 'creator', 'dependencies']);
    }

    /**
     * Delete a task by code (soft delete)
     */
    public function deleteByCode(string $code): bool
    {
        $task = Task::where('code', $code)->first();
        
        if (!$task) {
            return false;
        }

        return $task->delete();
    }

    /**
     * Delete a task (soft delete)
     */
    public function delete(int $id): bool
    {
        $task = Task::find($id);
        
        if (!$task) {
            return false;
        }

        return $task->delete();
    }

    /**
     * Update task status by code
     */
    public function updateStatusByCode(string $code, string $status): ?Task
    {
        $task = Task::where('code', $code)->first();
        
        if (!$task) {
            return null;
        }

        $updateData = ['status' => $status];

        // Set completed_at or canceled_at based on status
        if ($status === 'completed') {
            $updateData['completed_at'] = now();
            $updateData['canceled_at'] = null;
        } elseif ($status === 'canceled') {
            $updateData['canceled_at'] = now();
            $updateData['completed_at'] = null;
        } else {
            // For pending or in_progress, clear both timestamps
            $updateData['completed_at'] = null;
            $updateData['canceled_at'] = null;
        }

        $task->update($updateData);
        return $task->fresh(['assignee', 'creator', 'dependencies']);
    }

    /**
     * Update task status
     */
    public function updateStatus(int $id, string $status): ?Task
    {
        $task = Task::find($id);
        
        if (!$task) {
            return null;
        }

        $updateData = ['status' => $status];

        // Set completed_at or canceled_at based on status
        if ($status === 'completed') {
            $updateData['completed_at'] = now();
            $updateData['canceled_at'] = null;
        } elseif ($status === 'canceled') {
            $updateData['canceled_at'] = now();
            $updateData['completed_at'] = null;
        } else {
            // For pending or in_progress, clear both timestamps
            $updateData['completed_at'] = null;
            $updateData['canceled_at'] = null;
        }

        $task->update($updateData);
        return $task->fresh(['assignee', 'creator', 'dependencies']);
    }

    /**
     * Assign task to a user by code
     */
    public function assignToUserByCode(string $code, int $userId): ?Task
    {
        $task = Task::where('code', $code)->first();
        
        if (!$task) {
            return null;
        }

        $task->update(['assigned_to' => $userId]);
        return $task->fresh(['assignee', 'creator', 'dependencies']);
    }

    /**
     * Assign task to a user
     */
    public function assignToUser(int $id, int $userId): ?Task
    {
        $task = Task::find($id);
        
        if (!$task) {
            return null;
        }

        $task->update(['assigned_to' => $userId]);
        return $task->fresh(['assignee', 'creator', 'dependencies']);
    }

    /**
     * Get task dependencies by code
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
     * Get task dependencies
     */
    public function getDependencies(int $id): Collection
    {
        $task = Task::find($id);
        
        if (!$task) {
            return new Collection();
        }

        return $task->dependencies()->with(['assignee', 'creator'])->get();
    }

    /**
     * Check if task exists by code
     */
    public function existsByCode(string $code): bool
    {
        return Task::where('code', $code)->exists();
    }

    /**
     * Check if task exists
     */
    public function exists(int $id): bool
    {
        return Task::where('id', $id)->exists();
    }
}

