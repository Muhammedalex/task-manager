# Task Management System API

A robust and scalable RESTful API for managing tasks with role-based access control, task dependencies, and comprehensive filtering capabilities.

## Overview

This is a Laravel-based Task Management System API that provides a complete solution for task management with the following features:

- **Authentication**: Stateless authentication using Laravel Sanctum with refresh token mechanism
- **Task Management**: Full CRUD operations for tasks
- **Task Dependencies**: Support for task dependencies with business rules validation
- **Role-Based Access Control**: Different permissions for Managers and Users
- **Advanced Filtering**: Filter tasks by status, due date, assigned user, and search
- **Unique Task Codes**: Each task has a unique code (TSK-XXXXXXXXXXXX) instead of using IDs in routes

## Technology Stack

- **Framework**: Laravel 12.0
- **PHP**: 8.2+
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Authorization**: Spatie Laravel Permission
- **Architecture**: Repository-Service Pattern with Interfaces

## Prerequisites

Before you begin, ensure you have the following installed:

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Node.js & NPM (for frontend assets, if needed)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd task-manager
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Update the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Database Setup

Run migrations:

```bash
php artisan migrate
```

Seed the database with initial data:

```bash
php artisan db:seed
```

This will create:
- **Roles**: Manager, User
- **Permissions**: All task-related permissions
- **Users**: 
  - 2 Manager accounts (manager@softxpert.com, sarah.manager@softxpert.com)
  - 4 User accounts (alice@softxpert.com, bob@softxpert.com, charlie@softxpert.com, diana@softxpert.com)
  - Default password for all users: `password`
- **Tasks**: 14 sample tasks with various statuses and dependencies

### 5. Start the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Endpoints

### Base URL
```
http://localhost:8000/api
```

### Authentication Endpoints

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "manager@softxpert.com",
  "password": "password",
  "remember_me": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "1|...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Manager",
      "email": "manager@softxpert.com",
      "roles": ["Manager"]
    }
  }
}
```

#### Refresh Token
```http
POST /api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "refresh_token_here"
}
```

#### Get Current User
```http
GET /api/auth/me
Authorization: Bearer {access_token}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
```

### Task Management Endpoints

All task endpoints require authentication. Include the access token in the Authorization header:
```
Authorization: Bearer {access_token}
```

#### List Tasks
```http
GET /api/tasks?status=pending&page=1&per_page=15
```

**Query Parameters:**
- `status`: Filter by status (pending, in_progress, completed, canceled)
- `assigned_to`: Filter by assigned user ID
- `due_date_from`: Filter tasks due from this date (YYYY-MM-DD)
- `due_date_to`: Filter tasks due until this date (YYYY-MM-DD)
- `search`: Search in title and description
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

**Authorization:**
- Managers: Can view all tasks
- Users: Can only view tasks assigned to them

#### Create Task
```http
POST /api/tasks
Content-Type: application/json

{
  "title": "New Task Title",
  "description": "Task description",
  "due_date": "2025-12-31",
  "assigned_to": 2
}
```

**Authorization:** Managers only

#### Get Task Details
```http
GET /api/tasks/{code}
```

**URL Parameters:**
- `code`: Task code (e.g., TSK-XXXXXXXXXXXX)

**Response includes:**
- Task details
- Dependencies list
- Dependencies statistics (completion percentage, remaining count)
- Assignee and creator information

**Authorization:**
- Managers: Can view any task
- Users: Can only view tasks assigned to them

#### Update Task
```http
PUT /api/tasks/{code}
Content-Type: application/json

{
  "title": "Updated Title",
  "description": "Updated description",
  "due_date": "2025-12-31",
  "assigned_to": 3
}
```

**Authorization:** Managers only

#### Update Task Status
```http
PATCH /api/tasks/{code}/status
Content-Type: application/json

{
  "status": "completed"
}
```

**Authorization:**
- Managers: Can update any task status
- Users: Can only update status of tasks assigned to them

**Business Rules:**
- Task cannot be completed if dependencies are not completed

#### Delete Task
```http
DELETE /api/tasks/{code}
```

**Authorization:** Managers only

**Note:** Uses soft delete, task can be restored if needed

#### Assign Task to User
```http
POST /api/tasks/{code}/assign
Content-Type: application/json

{
  "user_id": 5
}
```

**Authorization:** Managers only

### Task Dependencies Endpoints

#### Get Task Dependencies
```http
GET /api/tasks/{code}/dependencies
```

#### Add Task Dependencies
```http
POST /api/tasks/{code}/dependencies
Content-Type: application/json

{
  "dependency_ids": ["TSK-XXXXXXXXXXXX", "TSK-YYYYYYYYYYYY"]
}
```

**Authorization:** Managers only

**Business Rules:**
- Cannot create circular dependencies
- Cannot add self as dependency
- Cannot add duplicate dependencies

#### Remove Task Dependency
```http
DELETE /api/tasks/{code}/dependencies/{dependencyCode}
```

**Authorization:** Managers only

### User Management Endpoints

#### List Users
```http
GET /api/users?role=User&page=1&per_page=15
```

**Query Parameters:**
- `role`: Filter by role (Manager, User)
- `search`: Search by name or email
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

**Authorization:** Managers only

## Role-Based Access Control

### Manager Role
- ✅ Create tasks
- ✅ Update tasks (title, description, due_date, assigned_to)
- ✅ Update any task status
- ✅ Delete tasks
- ✅ Assign tasks to users
- ✅ View all tasks
- ✅ Manage task dependencies

### User Role
- ✅ View tasks assigned to them
- ✅ Update status of tasks assigned to them
- ❌ Cannot create, update, or delete tasks
- ❌ Cannot assign tasks
- ❌ Cannot view other users' tasks

## Task Dependencies

### Business Rules

1. **Completion Rule**: A task cannot be completed until all its dependencies are completed
2. **Circular Dependencies**: Cannot create circular dependencies (A depends on B, B depends on A)
3. **Self-Dependency**: A task cannot depend on itself
4. **Duplicate Dependencies**: Cannot add the same dependency twice

### Dependencies Statistics

When retrieving a task, the response includes dependencies statistics:

```json
{
  "dependencies_stats": {
    "total": 3,
    "completed": 1,
    "pending": 1,
    "in_progress": 1,
    "canceled": 0,
    "remaining": 2,
    "can_be_completed": false,
    "completion_percentage": 33.33
  },
  "dependencies_summary": "1 of 3 dependencies completed (2 remaining) - 33%"
}
```

## Task Codes

Each task has a unique code in the format `TSK-XXXXXXXXXXXX` (12 random alphanumeric characters). This code is used in all API routes instead of numeric IDs for better security and usability.

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 100,
    "has_more_pages": true
  }
}
```

## Testing with Postman

1. Import the Postman collection from `docs/postman/task-manager-api.postman_collection.json`
2. Set the `base_url` variable to `http://localhost:8000`
3. Start by logging in using the "Login" request
4. The access token will be automatically saved to collection variables
5. Use the token in subsequent requests

### Sample Users

**Managers:**
- Email: `manager@softxpert.com`
- Password: `password`

- Email: `sarah.manager@softxpert.com`
- Password: `password`

**Users:**
- Email: `alice@softxpert.com`
- Password: `password`

- Email: `bob@softxpert.com`
- Password: `password`

- Email: `charlie@softxpert.com`
- Password: `password`

- Email: `diana@softxpert.com`
- Password: `password`

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   └── Task/
│   │       ├── TaskController.php
│   │       ├── TaskDependencyController.php
│   │       └── UserController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── RefreshTokenRequest.php
│   │   └── Task/
│   │       ├── StoreTaskRequest.php
│   │       ├── UpdateTaskRequest.php
│   │       ├── UpdateTaskStatusRequest.php
│   │       ├── AssignTaskRequest.php
│   │       ├── StoreTaskDependencyRequest.php
│   │       ├── ListTasksRequest.php
│   │       └── ListUsersRequest.php
│   └── Traits/
│       └── ApiResponseTrait.php
├── Models/
│   ├── User.php
│   ├── Task.php
│   └── RefreshToken.php
├── Repositories/
│   ├── Contracts/
│   │   ├── TaskRepositoryInterface.php
│   │   ├── TaskDependencyRepositoryInterface.php
│   │   └── UserRepositoryInterface.php
│   ├── TaskRepository.php
│   ├── TaskDependencyRepository.php
│   └── UserRepository.php
└── Services/
    ├── TaskService.php
    ├── TaskDependencyService.php
    └── UserService.php

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 2025_11_08_173819_create_tasks_table.php
│   ├── 2025_11_08_173820_create_task_dependencies_table.php
│   ├── 2025_11_08_180828_create_refresh_tokens_table.php
│   └── 2025_11_08_215243_add_code_to_tasks_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── RolePermissionSeeder.php
    ├── UserSeeder.php
    └── TaskSeeder.php

routes/
├── api.php
└── auth.php

docs/
└── postman/
    └── task-manager-api.postman_collection.json
```

## Database Schema

### Tables

1. **users**: User accounts with roles and permissions
2. **tasks**: Task information with status, due dates, and assignments
3. **task_dependencies**: Many-to-many relationship for task dependencies
4. **refresh_tokens**: Refresh token management
5. **roles**: User roles (Manager, User)
6. **permissions**: System permissions
7. **model_has_roles**: User-role assignments
8. **model_has_permissions**: User-permission assignments
9. **role_has_permissions**: Role-permission assignments

See `docs/ERD.md` for complete Entity Relationship Diagram with Mermaid diagram and detailed table descriptions.

## Error Handling

The API uses a centralized Exception Handler that ensures all API errors return a consistent JSON format, regardless of the exception type.

### Standardized Error Responses

All API errors follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }  // Only present for validation errors
}
```

### HTTP Status Codes

- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Missing or invalid authentication token
- **403 Forbidden**: User doesn't have permission
- **404 Not Found**: Resource or route not found
- **405 Method Not Allowed**: HTTP method not allowed for endpoint
- **422 Validation Error**: Validation failed (includes `errors` object)
- **500 Server Error**: Internal server error

### Exception Types Handled

The custom Exception Handler (`app/Exceptions/Handler.php`) automatically handles:

- **ValidationException**: Returns 422 with validation errors
- **ModelNotFoundException**: Returns 404 with model name
- **AuthenticationException**: Returns 401 unauthorized
- **AuthorizationException**: Returns 403 forbidden
- **NotFoundHttpException**: Returns 404 route not found
- **MethodNotAllowedHttpException**: Returns 405 method not allowed
- **QueryException**: Returns 500 database error (details hidden in production)
- **General Exceptions**: Returns 500 with error message (details hidden in production)

**Note:** In production environment, detailed error messages for database and internal errors are hidden for security. Full error details are logged for debugging purposes.

## Security Features

- Stateless authentication using Laravel Sanctum
- Refresh token mechanism with HTTP-only cookies
- Role-based access control (RBAC)
- Input validation on all endpoints
- SQL injection protection (Eloquent ORM)
- XSS protection
- CSRF protection for web routes

## Docker Setup (Optional)

The project includes Docker configuration for easy deployment and development.

### Prerequisites

- Docker
- Docker Compose

### Running with Docker

1. **Build and start containers:**

```bash
docker-compose up -d --build
```

2. **Install dependencies:**

```bash
docker-compose exec app composer install
```

3. **Generate application key:**

```bash
docker-compose exec app php artisan key:generate
```

4. **Run migrations:**

```bash
docker-compose exec app php artisan migrate
```

5. **Seed the database:**

```bash
docker-compose exec app php artisan db:seed
```

### Docker Services

- **App**: PHP 8.2 CLI with Laravel built-in server (port 8000)
- **MySQL**: Database server (port 3306)

### Access Services

- **API**: http://localhost:8000
- **Database**: localhost:3306
  - Database: `task_manager`
  - Username: `task_user`
  - Password: `password`
  - Root Password: `root`

### Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Execute commands in container
docker-compose exec app php artisan migrate

# Rebuild containers
docker-compose up -d --build
```

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

The project uses Laravel Pint for code formatting:

```bash
./vendor/bin/pint
```

### Clear Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## License

This project is proprietary software developed for SOFTXPERT Inc.

## Support

For issues or questions, please contact the development team.

---

**Developed by:** Muhammed Aymen Kamal.  
**Version:** 1.0  
**Framework:** Laravel 12.0
