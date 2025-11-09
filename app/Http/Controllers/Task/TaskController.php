<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AssignTaskRequest;
use App\Http\Requests\Task\ListTasksRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TaskController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private TaskService $taskService
    ) {
    }

    /**
     * List all tasks with filters
     */
    public function index(ListTasksRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->validated()['per_page'] ?? 15;

            // Build filters array
            $filters = [];
            if ($request->filled('status')) {
                $filters['status'] = $request->status;
            }
            if ($request->filled('assigned_to')) {
                $filters['assigned_to'] = $request->assigned_to;
            }
            if ($request->filled('due_date_from')) {
                $filters['due_date_from'] = $request->due_date_from;
            }
            if ($request->filled('due_date_to')) {
                $filters['due_date_to'] = $request->due_date_to;
            }
            if ($request->filled('search')) {
                $filters['search'] = $request->search;
            }

            $tasks = $this->taskService->getAllTasks($user, $filters, $perPage);

            return $this->paginatedResponse($tasks, 'Tasks retrieved successfully');
        } catch (Throwable $e) {
            Log::error('Error retrieving tasks: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id,
            ]);

            return $this->serverErrorResponse('Failed to retrieve tasks');
        }
    }

    /**
     * Create a new task
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $task = $this->taskService->createTask($user, $data);

            return $this->createdResponse($task->load(['assignee', 'creator', 'dependencies']), 'Task created successfully');
        } catch (Throwable $e) {
            Log::error('Error creating task: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id,
            ]);

            return $this->serverErrorResponse('Failed to create task');
        }
    }

    /**
     * Get task details
     */
    public function show(string $code): JsonResponse
    {
        try {
            $user = request()->user();
            $taskData = $this->taskService->getTaskDetailsWithStats($user, $code);

            if (!$taskData) {
                return $this->notFoundResponse('Task not found', 'Task');
            }

            // Check if user doesn't have permission (task exists but no permission)
            if (isset($taskData['has_permission']) && $taskData['has_permission'] === false) {
                return $this->forbiddenResponse('You do not have permission to view this task');
            }

            return $this->successResponse($taskData, 'Task retrieved successfully');
        } catch (Throwable $e) {
            Log::error('Error retrieving task: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to retrieve task');
        }
    }

    /**
     * Update task
     */
    public function update(UpdateTaskRequest $request, string $code): JsonResponse
    {
        try {
            $data = $request->validated();
            $task = $this->taskService->updateTaskByCode($request->user(), $code, $data);

            if (!$task) {
                return $this->notFoundResponse('Task not found', 'Task');
            }

            return $this->updatedResponse($task, 'Task updated successfully');
        } catch (Throwable $e) {
            Log::error('Error updating task: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to update task');
        }
    }

    /**
     * Update task status
     */
    public function updateStatus(UpdateTaskStatusRequest $request, string $code): JsonResponse
    {
        try {
            $user = $request->user();
            $status = $request->validated()['status'];

            $task = $this->taskService->updateTaskStatusByCode($user, $code, $status);

            if (!$task) {
                // Check if task doesn't exist or user doesn't have permission
                $existingTask = $this->taskService->getTaskByCode($user, $code);
                if (!$existingTask) {
                    return $this->notFoundResponse('Task not found', 'Task');
                }

                // Check if user has permission
                if (!$user->hasRole('Manager') && $existingTask->assigned_to !== $user->id) {
                    return $this->forbiddenResponse('You do not have permission to update this task status');
                }

                // Check if dependencies are not completed
                if ($status === 'completed' && !$existingTask->canBeCompleted()) {
                    return $this->badRequestResponse('Cannot complete task. All dependencies must be completed first');
                }

                return $this->badRequestResponse('Invalid status or task cannot be updated');
            }

            return $this->updatedResponse($task, 'Task status updated successfully');
        } catch (Throwable $e) {
            Log::error('Error updating task status: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to update task status');
        }
    }

    /**
     * Delete task
     */
    public function destroy(string $code): JsonResponse
    {
        try {
            $user = request()->user();

            if (!$user->hasRole('Manager')) {
                return $this->forbiddenResponse('Only managers can delete tasks');
            }

            $deleted = $this->taskService->deleteTaskByCode($code);

            if (!$deleted) {
                return $this->notFoundResponse('Task not found', 'Task');
            }

            return $this->deletedResponse('Task deleted successfully');
        } catch (Throwable $e) {
            Log::error('Error deleting task: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to delete task');
        }
    }

    /**
     * Assign task to user
     */
    public function assign(AssignTaskRequest $request, string $code): JsonResponse
    {
        try {
            $userId = $request->validated()['user_id'];
            $task = $this->taskService->assignTaskToUserByCode($code, $userId);

            if (!$task) {
                return $this->notFoundResponse('Task not found', 'Task');
            }

            return $this->updatedResponse($task, 'Task assigned successfully');
        } catch (Throwable $e) {
            Log::error('Error assigning task: ' . $e->getMessage(), [
                'exception' => $e,
                'task_code' => $code,
            ]);

            return $this->serverErrorResponse('Failed to assign task');
        }
    }
}

