<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks with optional filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get tasks assigned to a specific user
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAssignedUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a task by ID with relationships
     *
     * @param int $id
     * @param array $with
     * @return Task|null
     */
    public function findById(int $id, array $with = []): ?Task;

    /**
     * Find a task by code with relationships
     *
     * @param string $code
     * @param array $with
     * @return Task|null
     */
    public function findByCode(string $code, array $with = []): ?Task;

    /**
     * Create a new task
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task;

    /**
     * Update a task by code
     *
     * @param string $code
     * @param array $data
     * @return Task|null
     */
    public function updateByCode(string $code, array $data): ?Task;

    /**
     * Update a task
     *
     * @param int $id
     * @param array $data
     * @return Task|null
     */
    public function update(int $id, array $data): ?Task;

    /**
     * Delete a task by code (soft delete)
     *
     * @param string $code
     * @return bool
     */
    public function deleteByCode(string $code): bool;

    /**
     * Delete a task (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Update task status by code
     *
     * @param string $code
     * @param string $status
     * @return Task|null
     */
    public function updateStatusByCode(string $code, string $status): ?Task;

    /**
     * Update task status
     *
     * @param int $id
     * @param string $status
     * @return Task|null
     */
    public function updateStatus(int $id, string $status): ?Task;

    /**
     * Assign task to a user by code
     *
     * @param string $code
     * @param int $userId
     * @return Task|null
     */
    public function assignToUserByCode(string $code, int $userId): ?Task;

    /**
     * Assign task to a user
     *
     * @param int $id
     * @param int $userId
     * @return Task|null
     */
    public function assignToUser(int $id, int $userId): ?Task;

    /**
     * Get task dependencies by code
     *
     * @param string $code
     * @return Collection
     */
    public function getDependenciesByCode(string $code): Collection;

    /**
     * Get task dependencies
     *
     * @param int $id
     * @return Collection
     */
    public function getDependencies(int $id): Collection;

    /**
     * Check if task exists by code
     *
     * @param string $code
     * @return bool
     */
    public function existsByCode(string $code): bool;

    /**
     * Check if task exists
     *
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;
}

