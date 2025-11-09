<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskDependencyRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\TaskDependencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TaskDependencyController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private TaskDependencyService $dependencyService
    ) {
    }

    /**
     * Get task dependencies
     */
    public function index(string $code): JsonResponse
    {
        try {
            $user = request()->user();
            $dependencies = $this->dependencyService->getTaskDependenciesByCode($user, $code);

            if ($dependencies === null) {
                return $this->notFoundResponse('Task not found', 'Task');
            }

            return $this->successResponse($dependencies, 'Dependencies retrieved successfully');
        } catch (Throwable $e) {
            Log::error('Error retrieving task dependencies: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to retrieve task dependencies');
        }
    }

    /**
     * Add task dependencies
     */
    public function store(StoreTaskDependencyRequest $request, string $code): JsonResponse
    {
        try {
            $dependencyCodes = $request->validated()['dependency_ids'];
            $result = $this->dependencyService->addDependenciesByCode($code, $dependencyCodes);

            if (!$result['success']) {
                return $this->notFoundResponse($result['message'], 'Task');
            }

            // If there are errors or skipped items, include them in the response
            if (!empty($result['errors']) || !empty($result['skipped'])) {
                return $this->successResponse([
                    'task' => $result['task'],
                    'added' => $result['added'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                ], 'Dependencies processed with some issues');
            }

            return $this->successResponse($result['task'], 'Dependencies added successfully');
        } catch (Throwable $e) {
            Log::error('Error adding task dependencies: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to add task dependencies');
        }
    }

    /**
     * Remove task dependency
     */
    public function destroy(string $code, string $dependencyCode): JsonResponse
    {
        try {
            $result = $this->dependencyService->removeDependencyByCode($code, $dependencyCode);

            if (!$result['success']) {
                return $this->notFoundResponse($result['message']);
            }

            return $this->successResponse($result['task'], 'Dependency removed successfully');
        } catch (Throwable $e) {
            Log::error('Error removing task dependency: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
                'dependency_code' => $dependencyCode,
            ]);

            return $this->serverErrorResponse('Failed to remove task dependency');
        }
    }
}

