# Learning Management System REST API

## Project Overview

Complete REST API for Learning Management System built with Laravel with the following features:

✅ **Authentication & Authorization** - Register, Login, JWT Token Generation, Middleware Protection  
✅ **Request Validation** - Middleware-based validation with clear error messages  
✅ **Error Handling** - Global error handler for 400, 401, 404, 500 status codes  
✅ **Caching** - TTL-based caching (30-60 seconds) for GET endpoints  
✅ **Advanced Queries** - Aggregation (course count per instructor) & JOIN multi-table queries  
✅ **API Testing** - Complete Postman collection with variables and flow

## Quick Start

### 1. Installation

```bash
# Install dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Install Sanctum for API authentication
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Start server
php artisan serve
```

### 2. Environment Configuration

Create `.env` file with:

```env
APP_NAME="LMS API"
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

CACHE_DRIVER=file
```

### 3. API Base URL

```
http://localhost:8000/api
```

## API Endpoints

### Authentication

| Method | Endpoint | Protected | Description |
|--------|----------|-----------|-------------|
| POST | `/auth/register` | ❌ | Register new user |
| POST | `/auth/login` | ❌ | Login and get token |
| POST | `/auth/logout` | ✅ | Logout and revoke token |
| GET | `/auth/me` | ✅ | Get authenticated user |

### Categories

| Method | Endpoint | Protected | Description |
|--------|----------|-----------|-------------|
| GET | `/categories` | ❌ | Get all categories (cached) |
| GET | `/categories/{id}` | ❌ | Get category by ID (cached) |
| POST | `/categories` | ✅ | Create category |
| PUT | `/categories/{id}` | ✅ | Update category |
| DELETE | `/categories/{id}` | ✅ | Delete category |

### Courses

| Method | Endpoint | Protected | Description |
|--------|----------|-----------|-------------|
| GET | `/courses` | ❌ | Get all courses (cached) |
| GET | `/courses?search=keyword` | ❌ | Search courses (cached) |
| GET | `/courses/{id}` | ❌ | Get course by ID (cached) |
| POST | `/courses` | ✅ | Create course (instructor only) |
| PUT | `/courses/{id}` | ✅ | Update course (owner only) |
| DELETE | `/courses/{id}` | ✅ | Delete course (owner only) |

### Advanced Queries

| Method | Endpoint | Protected | Description |
|--------|----------|-----------|-------------|
| GET | `/instructors/course-count` | ❌ | Get instructor statistics (aggregation) |
| GET | `/transactions/detail` | ❌ | Get detailed transactions (JOIN multi-table) |

## Authentication

### Register User

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Instructor",
    "email": "instructor@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "instructor"
  }'
```

Response:
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Instructor",
      "email": "instructor@example.com",
      "role": "instructor"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz"
  }
}
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "instructor@example.com",
    "password": "password123"
  }'
```

### Protected Endpoint Example

```bash
curl -X POST http://localhost:8000/api/categories \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Web Development",
    "description": "Learn web development"
  }'
```

## Caching Implementation

All GET endpoints use Laravel's Cache facade with 60-second TTL:

- `Cache::remember()` - Retrieves from cache or stores in cache
- Cache is automatically cleared on CREATE, UPDATE, DELETE operations
- Cache keys are hash-based on query parameters for flexibility

```php
$cache_key = 'courses_' . md5(json_encode($request->query()));
$courses = Cache::remember($cache_key, 60, function () {
    return Course::with(['instructor', 'category'])->get();
});
```

## Error Handling

### Standard Error Response

All errors follow this format:

```json
{
  "status": "error",
  "message": "Error description"
}
```

### Status Codes

- **400** - Bad Request (validation failed)
- **401** - Unauthorized (no/invalid token)
- **403** - Forbidden (insufficient permissions)
- **404** - Not Found (resource doesn't exist)
- **500** - Internal Server Error

### Validation Response

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

## Advanced Queries

### 1. Aggregation - Instructor Course Count

Endpoint: `GET /api/instructors/course-count`

Response:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "John Instructor",
      "email": "john@example.com",
      "course_count": 5,
      "total_students_enrolled": 142
    }
  ]
}
```

**Implementation Details:**
- Uses `withCount('courses')` for aggregation
- Joins with enrollments to count total students
- Ordered by course count descending

### 2. JOIN Multi-Table - Transaction Details

Endpoint: `GET /api/transactions/detail?page=1&per_page=10`

Joins:
- Users (students)
- Courses
- Course Categories
- Instructors (users as instructors)
- Enrollments (transactions)

Response:
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "student_name": "Jane Student",
        "student_email": "jane@example.com",
        "course_name": "Advanced Laravel",
        "price": 199000,
        "category_name": "Web Development",
        "instructor_name": "John Instructor",
        "status": "active",
        "enrollment_date": "2026-04-23T10:30:00"
      }
    ],
    "pagination": {
      "total": 50,
      "per_page": 10,
      "current_page": 1
    }
  }
}
```

## Postman Collection

### Importing the Collection

1. Open Postman
2. Click **Import** → **Upload Files**
3. Select `LMS-API-Postman.collection.json`
4. Collection will be imported with all endpoints and tests

### Setting up Environment Variables

The collection uses these variables:
- `base_url` - Default: `http://localhost:8000/api`
- `token` - Auto-set by Register/Login tests
- `user_id` - Auto-set by Register/Login tests
- `category_id` - Auto-set by Create Category test
- `course_id` - Auto-set by Create Course test

### Test Flow

1. **Register User** - Creates account and sets token
2. **Login User** - Gets token (alternative to register)
3. **Get Auth User** - Verifies token works
4. **Create Category** - Tests protected endpoint
5. **Get Categories** - Tests caching
6. **Create Course** - Tests course creation
7. **Advanced Queries** - Tests aggregation and JOINs
8. **Error Tests** - Validates error handling

## Implementation Details

### Controllers

1. **AuthController** - Authentication (register, login, logout, me)
2. **CategoryController** - Categories with caching
3. **CourseController** - Courses with caching and permission checks
4. **InstructorController** - Instructor statistics with aggregation
5. **TransactionController** - Multi-table JOINs

### Middleware

- **auth:sanctum** - Protects routes requiring authentication
- Global exception handler - Formats all errors

### Models

- **User** - Extended with `HasApiTokens` for Sanctum
- **Course** - Relationships with User (instructor), Category
- **Enrollment** - Transaction records linking users to courses
- **CourseCategory** - Category for courses

## Testing Checklist

✅ Register new user  
✅ Login with credentials  
✅ Generate JWT token  
✅ Protect endpoints with middleware  
✅ Create category (protected)  
✅ Update category (protected)  
✅ Delete category (protected)  
✅ Create course (protected, instructor only)  
✅ Update course (owner only)  
✅ Delete course (owner only)  
✅ Validate required fields (400 error)  
✅ Reject invalid token (401 error)  
✅ Handle not found resources (404 error)  
✅ Cache GET endpoints (60 second TTL)  
✅ Clear cache on write operations  
✅ Aggregation query (course count)  
✅ Multi-table JOIN query  

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── CategoryController.php
│   │   ├── CourseController.php
│   │   ├── InstructorController.php
│   │   └── TransactionController.php
│   └── Middleware/
│       └── ValidateRequest.php
└── Models/
    ├── User.php (updated with Sanctum)
    ├── Course.php
    ├── CourseCategory.php
    ├── Enrollment.php
    └── Assignment.php

routes/
└── api.php (updated with auth and protected routes)

bootstrap/
└── app.php (updated with error handling)
```

## Notes

- Password hashing is handled automatically by Laravel (uses bcrypt)
- Caching uses file driver - can be switched to Redis in production
- Sanctum uses personal access tokens for stateless API authentication
- All timestamps are in UTC format
- Pagination uses Laravel's default (15 items per page)

## Next Steps (Optional)

For production deployment:

1. Switch cache driver to Redis
2. Add rate limiting middleware
3. Implement API documentation with Swagger
4. Add comprehensive logging
5. Set up CORS properly
6. Use environment-specific configurations

## Support

For issues or questions, check:
- `.env` configuration
- Database migrations have run
- Sanctum is properly installed
- Token format is correct: `Bearer {token}`
