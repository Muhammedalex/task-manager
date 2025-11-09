<?php

use App\Http\Controllers\Task\TaskController;
use App\Http\Controllers\Task\TaskDependencyController;
use App\Http\Controllers\Task\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Include authentication routes
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Task Management API Routes
|--------------------------------------------------------------------------
|
| All task management endpoints. Routes are protected with authentication
| and role-based access control middleware.
|
| Authorization Rules:
| - Managers: Can create, update, assign, and delete tasks
| - Users: Can only view their assigned tasks and update status
|
*/

// Task Management Endpoints
Route::prefix('tasks')->middleware('auth:sanctum')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | List Tasks (with filtering)
    |--------------------------------------------------------------------------
    | GET /api/tasks
    | 
    | Query Parameters:
    | - status: Filter by status (pending, in_progress, completed, canceled)
    | - assigned_to: Filter by assigned user ID
    | - due_date_from: Filter tasks due from this date (YYYY-MM-DD)
    | - due_date_to: Filter tasks due until this date (YYYY-MM-DD)
    | - search: Search in title and description
    | - page: Page number for pagination
    | - per_page: Items per page (default: 15)
    |
    | Authorization:
    | - Managers: Can view all tasks
    | - Users: Can only view tasks assigned to them
    |
    | Response: Paginated list of tasks with relationships
    */
    Route::get('/', [TaskController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Create New Task
    |--------------------------------------------------------------------------
    | POST /api/tasks
    |
    | Body:
    | {
    |   "title": "Task title (required)",
    |   "description": "Task description (optional)",
    |   "due_date": "2025-12-31 (optional, YYYY-MM-DD format)",
    |   "assigned_to": 2 (optional, user ID)
    | }
    |
    | Authorization:
    | - Managers only
    |
    | Response: Created task object with dependencies
    */
    Route::post('/', [TaskController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Get Task Details
    |--------------------------------------------------------------------------
    | GET /api/tasks/{id}
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Authorization:
    | - Managers: Can view any task
    | - Users: Can only view tasks assigned to them
    |
    | Response: Task object with dependencies, assignee, and creator details
    */
    Route::get('/{code}', [TaskController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Update Task
    |--------------------------------------------------------------------------
    | PUT/PATCH /api/tasks/{code}
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Body (all fields optional):
    | {
    |   "title": "Updated title",
    |   "description": "Updated description",
    |   "due_date": "2025-12-31",
    |   "assigned_to": 3
    | }
    |
    | Authorization:
    | - Managers only
    |
    | Response: Updated task object
    */
    Route::put('/{code}', [TaskController::class, 'update']);
    Route::patch('/{code}', [TaskController::class, 'update']);

    /*
    |--------------------------------------------------------------------------
    | Update Task Status
    |--------------------------------------------------------------------------
    | PATCH /api/tasks/{code}/status
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Body:
    | {
    |   "status": "completed" (pending, in_progress, completed, canceled)
    | }
    |
    | Authorization:
    | - Managers: Can update any task status
    | - Users: Can only update status of tasks assigned to them
    |
    | Business Rules:
    | - Task cannot be completed if dependencies are not completed
    | - Automatically sets completed_at or canceled_at timestamps
    |
    | Response: Updated task object
    */
    Route::patch('/{code}/status', [TaskController::class, 'updateStatus']);

    /*
    |--------------------------------------------------------------------------
    | Delete Task
    |--------------------------------------------------------------------------
    | DELETE /api/tasks/{code}
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Authorization:
    | - Managers only
    |
    | Note: Uses soft delete, task can be restored if needed
    |
    | Response: Success message
    */
    Route::delete('/{code}', [TaskController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Add Task Dependencies
    |--------------------------------------------------------------------------
    | POST /api/tasks/{code}/dependencies
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Body:
    | {
    |   "dependency_ids": ["TSK-XXXXXXXXXXXX", "TSK-YYYYYYYYYYYY"] (array of task codes that must be completed first)
    | }
    |
    | Authorization:
    | - Managers only
    |
    | Business Rules:
    | - Cannot create circular dependencies
    | - Cannot add self as dependency
    | - Cannot add duplicate dependencies
    |
    | Response: Task object with updated dependencies
    */
    Route::post('/{code}/dependencies', [TaskDependencyController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Remove Task Dependencies
    |--------------------------------------------------------------------------
    | DELETE /api/tasks/{code}/dependencies/{dependencyCode}
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    | - dependencyCode: Dependency task code to remove
    |
    | Authorization:
    | - Managers only
    |
    | Response: Task object with updated dependencies
    */
    Route::delete('/{code}/dependencies/{dependencyCode}', [TaskDependencyController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Get Task Dependencies
    |--------------------------------------------------------------------------
    | GET /api/tasks/{code}/dependencies
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Authorization:
    | - Managers: Can view dependencies of any task
    | - Users: Can view dependencies of tasks assigned to them
    |
    | Response: Array of dependency tasks with their status
    */
    Route::get('/{code}/dependencies', [TaskDependencyController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Assign Task to User
    |--------------------------------------------------------------------------
    | POST /api/tasks/{code}/assign
    |
    | URL Parameters:
    | - code: Task code (e.g., TSK-XXXXXXXXXXXX)
    |
    | Body:
    | {
    |   "user_id": 5 (user ID to assign task to)
    | }
    |
    | Authorization:
    | - Managers only
    |
    | Response: Updated task object with new assignee
    */
    Route::post('/{code}/assign', [TaskController::class, 'assign']);
});

/*
|--------------------------------------------------------------------------
| User Management Routes (for Managers)
|--------------------------------------------------------------------------
|
| Routes for managers to view users for task assignment purposes.
|
*/

Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | List Users
    |--------------------------------------------------------------------------
    | GET /api/users
    |
    | Query Parameters:
    | - role: Filter by role (Manager, User)
    | - search: Search by name or email
    | - page: Page number for pagination
    | - per_page: Items per page (default: 15)
    |
    | Authorization:
    | - Managers only
    |
    | Response: Paginated list of users (for task assignment)
    */
    Route::get('/', [UserController::class, 'index']);
});
