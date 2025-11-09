<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users with optional filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with('roles');

        // Filter by role
        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }

        // Search by name or email
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Order by name
        $query->orderBy('name', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    /**
     * Find users by role
     */
    public function findByRole(string $roleName): Collection
    {
        return User::role($roleName)->with('roles')->get();
    }

    /**
     * Check if user exists
     */
    public function exists(int $id): bool
    {
        return User::where('id', $id)->exists();
    }
}

