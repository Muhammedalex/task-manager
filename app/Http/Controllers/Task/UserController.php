<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\ListUsersRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private UserService $userService
    ) {
    }

    /**
     * List all users (for Managers to assign tasks)
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        try {
            $perPage = $request->validated()['per_page'] ?? 15;

            // Build filters array
            $filters = [];
            if ($request->filled('role')) {
                $filters['role'] = $request->role;
            }
            if ($request->filled('search')) {
                $filters['search'] = $request->search;
            }

            $users = $this->userService->getAllUsers($filters, $perPage);

            return $this->paginatedResponse($users, 'Users retrieved successfully');
        } catch (Throwable $e) {
            Log::error('Error retrieving users: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id,
            ]);

            return $this->serverErrorResponse('Failed to retrieve users');
        }
    }
}

