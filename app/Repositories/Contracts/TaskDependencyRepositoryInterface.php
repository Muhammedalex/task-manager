<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskDependencyRepositoryInterface
{
    /**
     * Get all dependencies for a task
     *
     * @param int $taskId
     * @return Collection
     */
    public function getDependencies(int $taskId): Collection;

    /**
     * Get all dependencies for a task by code
     *
     * @param string $code
     * @return Collection
     */
    public function getDependenciesByCode(string $code): Collection;

    /**
     * Add dependencies to a task
     *
     * @param int $taskId
     * @param array $dependencyIds
     * @return Task
     */
    public function addDependencies(int $taskId, array $dependencyIds): Task;

    /**
     * Remove a dependency from a task
     *
     * @param int $taskId
     * @param int $dependencyId
     * @return bool
     */
    public function removeDependency(int $taskId, int $dependencyId): bool;

    /**
     * Check if a dependency exists
     *
     * @param int $taskId
     * @param int $dependencyId
     * @return bool
     */
    public function dependencyExists(int $taskId, int $dependencyId): bool;

    /**
     * Check for circular dependencies
     *
     * @param int $taskId
     * @param int $dependencyId
     * @return bool
     */
    public function hasCircularDependency(int $taskId, int $dependencyId): bool;

    /**
     * Get all tasks that depend on a specific task
     *
     * @param int $taskId
     * @return Collection
     */
    public function getDependents(int $taskId): Collection;
}

