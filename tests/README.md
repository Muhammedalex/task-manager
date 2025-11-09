# Task Management System - Tests

This directory contains tests for the Task Management System API covering the main requirements.

## Test Structure

### Feature Tests (`tests/Feature/`)

**TaskManagementTest.php**: Comprehensive tests covering all main requirements:

1. **RBAC (Role-Based Access Control)**
   -  Managers can create tasks
   -  Users cannot create tasks
   -  Users can only view their assigned tasks
   -  Users can update only status of assigned tasks
   -  Managers can assign tasks to users

2. **Task Dependencies**
   -  Task cannot be completed if dependencies are not completed
   -  Task can be completed when all dependencies are completed

3. **Task Operations**
   -  Filter tasks by status
   -  Retrieve task details with dependencies and statistics

## Running Tests

```bash
# Run all tests
php artisan test

# Run only feature tests
php artisan test --testsuite=Feature

# Run specific test
php artisan test --filter=test_managers_can_create_tasks
```

## Test Coverage

The tests verify all main requirements from the task specification:

-  Authentication (using Sanctum)
-  Create a new task (Managers only)
-  Retrieve tasks with filtering (status, due date, assigned user)
-  Add task dependencies
-  Retrieve task details including dependencies
-  Update task details (title, description, assignee, due date)
-  Update task status
-  Role-based access control (Manager vs User permissions)
-  Task dependencies validation (cannot complete until dependencies are done)
