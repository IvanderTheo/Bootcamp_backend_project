# Postman Testing Guide

## Complete Testing Flow

Follow these steps in Postman to test the entire API flow:

## Step 1: Import Collection

1. Open Postman
2. Click **File** → **Import**
3. Select `LMS-API-Postman.collection.json`
4. Click **Import**

The collection will be imported into your workspace with all folders and requests.

## Step 2: Configure Environment Variables

1. Click **Environments** in the left sidebar
2. Create a new environment named "LMS Local"
3. Add the following variables:

| Variable | Initial Value | Type |
|----------|---------------|------|
| base_url | http://localhost:8000/api | string |
| token | (empty) | string |
| user_id | (empty) | string |
| category_id | (empty) | string |
| course_id | (empty) | string |

4. Set this as your active environment

## Step 3: Test Authentication Flow

### 3.1 Register a New User

1. Navigate to **Authentication** folder
2. Open **Register User** request
3. Update the email in the request body to ensure uniqueness:
   ```json
   {
     "name": "John Instructor",
     "email": "instructor.{{$timestamp}}@example.com",
     "password": "password123",
     "password_confirmation": "password123",
     "role": "instructor"
   }
   ```
4. Click **Send**
5. Verify the response:
   - Status: 201 Created
   - Response contains token
   - Token is automatically saved to `{{token}}` variable

### 3.2 Login User

1. Open **Login User** request
2. Update email if needed:
   ```json
   {
     "email": "instructor@example.com",
     "password": "password123"
   }
   ```
3. Click **Send**
4. Verify:
   - Status: 200 OK
   - Token is returned and saved

### 3.3 Get Current User

1. Open **Get Current User** request
2. Click **Send**
3. Verify:
   - Status: 200 OK
   - Response contains authenticated user data
   - Token in Authorization header is working

## Step 4: Test Categories (CRUD Operations)

### 4.1 Get All Categories (Public, Cached)

1. Navigate to **Categories** folder
2. Open **Get All Categories (Cached)**
3. Click **Send**
4. Verify:
   - Status: 200 OK
   - Response contains array of categories
   - Response time should be fast (cached)

### 4.2 Get Category by ID (Public)

1. Open **Get Category by ID**
2. Click **Send**
3. Verify: Status 200 OK and category data returned

### 4.3 Create Category (Protected)

1. Open **Create Category (Protected)**
2. Verify Authorization header has token: `Bearer {{token}}`
3. Update name for uniqueness:
   ```json
   {
     "name": "Web Development {{$timestamp}}",
     "description": "Learn web development",
     "icon": "web"
   }
   ```
4. Click **Send**
5. Verify:
   - Status: 201 Created
   - Response contains created category
   - Category ID is saved to `{{category_id}}`
   - Cache was cleared (next GET will fetch fresh data)

### 4.4 Update Category (Protected)

1. Open **Update Category (Protected)**
2. Verify URL has `{{category_id}}`
3. Update body:
   ```json
   {
     "name": "Web Development Updated",
     "description": "Learn modern web development"
   }
   ```
4. Click **Send**
5. Verify: Status 200 OK and category updated

### 4.5 Delete Category (Protected)

1. Open **Delete Category (Protected)**
2. Verify URL uses `{{category_id}}`
3. Click **Send**
4. Verify:
   - Status: 200 OK
   - Cache cleared for deletion

## Step 5: Test Courses (CRUD Operations)

### 5.1 Get All Courses (Public, Cached)

1. Navigate to **Courses** folder
2. Open **Get All Courses (Cached)**
3. Click **Send**
4. Verify:
   - Status: 200 OK
   - Response time should be fast (first time might cache, second time faster)

### 5.2 Search Courses with Query Parameters

1. Open **Get Courses with Search**
2. Click **Send**
3. Verify:
   - Status: 200 OK
   - Results filtered by search term
   - Different cache key than unfiltered list

### 5.3 Get Course by ID

1. Open **Get Course by ID**
2. Click **Send**
3. Verify: Status 200 OK with course details

### 5.4 Create Course (Protected - Instructor Only)

1. Open **Create Course (Protected - Instructor Only)**
2. Verify token in Authorization header
3. Update course name for uniqueness:
   ```json
   {
     "name": "Advanced Laravel {{$timestamp}}",
     "description": "Learn advanced Laravel concepts",
     "price": 199000,
     "category_id": 1,
     "quota": 50
   }
   ```
4. Click **Send**
5. Verify:
   - Status: 201 Created
   - Course created with authenticated user as instructor (id_user)
   - Course ID saved to `{{course_id}}`

### 5.5 Update Course (Protected - Owner Only)

1. Open **Update Course (Protected)**
2. Verify you're logged in as the course creator
3. Update body:
   ```json
   {
     "price": 299000,
     "quota": 100
   }
   ```
4. Click **Send**
5. Verify:
   - Status: 200 OK
   - Only course owner can update (check 403 if not owner)

### 5.6 Delete Course (Protected - Owner Only)

1. Open **Delete Course (Protected)**
2. Click **Send**
3. Verify:
   - Status: 200 OK
   - Only course owner can delete

## Step 6: Test Advanced Queries

### 6.1 Instructor Course Count (Aggregation)

1. Navigate to **Advanced Queries** folder
2. Open **Get Instructor Course Count (Aggregation)**
3. Click **Send**
4. Verify response includes:
   - Instructor ID, name, email
   - `course_count` - Total courses created
   - `total_students_enrolled` - Total unique students enrolled in all their courses
   - Results ordered by course count descending

Example response:
```json
{
  "status": "success",
  "message": "Data instructor course count berhasil diambil",
  "data": [
    {
      "id": 1,
      "name": "John Instructor",
      "email": "john@example.com",
      "course_count": 3,
      "total_students_enrolled": 45
    }
  ]
}
```

### 6.2 Transactions Detail (Multi-Table JOIN)

1. Open **Get Transactions Detail (JOIN Multi-table)**
2. Click **Send**
3. Verify response includes:
   - `student_name`, `student_email` (from users)
   - `course_name`, `price` (from courses)
   - `category_name` (from course_categories)
   - `instructor_name` (from users via courses)
   - `status`, `enrollment_date` (from enrollments)
   - Pagination metadata

Example response:
```json
{
  "status": "success",
  "message": "Data transaksi detail berhasil diambil",
  "data": {
    "data": [
      {
        "id": 1,
        "status": "active",
        "enrollment_date": "2026-04-23T10:30:00",
        "student_id": 2,
        "student_name": "Jane Student",
        "student_email": "jane@example.com",
        "course_id": 1,
        "course_name": "Advanced Laravel",
        "price": 199000,
        "description": "Learn advanced Laravel",
        "category_id": 1,
        "category_name": "Web Development",
        "instructor_name": "John Instructor",
        "instructor_email": "john@example.com"
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

## Step 7: Test Error Handling

### 7.1 Test 400 Bad Request (Validation)

1. Navigate to **Error Handling Tests** folder
2. Open **Test 400 Bad Request**
3. Click **Send**
4. Verify:
   - Status: 400 Bad Request
   - Response: `{"status": "error", "message": "Validation failed", "errors": {...}}`
   - Empty name field causes validation error

### 7.2 Test 401 Unauthorized

1. Open **Test 401 Unauthorized**
2. Notice: No Authorization header
3. Click **Send**
4. Verify:
   - Status: 401 Unauthorized
   - Response: `{"status": "error", "message": "Unauthorized - Please provide a valid token"}`

### 7.3 Test 404 Not Found

1. Open **Test 404 Not Found**
2. Click **Send**
3. Verify:
   - Status: 404 Not Found
   - Response: `{"status": "error", "message": "Resource not found"}`
   - Invalid category ID (99999) returns 404

## Step 8: Run Full Collection Test

### Using Postman Collection Runner

1. Click on the collection name "Learning Management System API"
2. Click **Run** button
3. Collection Runner opens with all requests
4. Configure:
   - Environment: "LMS Local"
   - Delay: 100ms (between requests)
   - Number of Iterations: 1
5. Click **Run Learning Management System API**

**Results:**
- All tests should pass ✅
- Each test validates:
  - Correct status code
  - Response format (status field)
  - Required data fields
  - Variable assignments

## Testing Checklist

- [ ] Register user successfully
- [ ] Login and receive token
- [ ] Get authenticated user
- [ ] Logout successfully
- [ ] Get all categories (no auth needed)
- [ ] Create category (requires auth)
- [ ] Update category
- [ ] Delete category
- [ ] Get all courses (cached)
- [ ] Create course (instructor only)
- [ ] Update course (owner only)
- [ ] Delete course (owner only)
- [ ] Search courses
- [ ] Get instructor course count (aggregation)
- [ ] Get transactions detail (multi-table JOIN)
- [ ] Receive 400 Bad Request on validation failure
- [ ] Receive 401 Unauthorized without token
- [ ] Receive 404 Not Found for invalid resource
- [ ] Caching works (repeated requests are faster)
- [ ] Cache clears after create/update/delete

## Troubleshooting

### "Cannot GET /api/auth/register" (404)

**Solution:** 
- Ensure Laravel server is running: `php artisan serve`
- Check base_url variable is correct
- Verify API routes include auth routes

### "Unauthorized" on protected endpoint

**Solution:**
- Run Register or Login request first to get token
- Verify token is set in environment variables
- Check Authorization header format: `Bearer {{token}}`

### Caching appears not to work

**Solution:**
- Verify CACHE_DRIVER=file in .env
- Check storage/framework/cache directory exists
- First request caches data, second should be faster
- Compare response times

### Cannot create course (403 Forbidden)

**Solution:**
- User must be registered with `role: instructor`
- Create student account with `role: student` and try (should fail)

### Validation errors not returning

**Solution:**
- Ensure global exception handler is configured in bootstrap/app.php
- Check ValidationException is caught properly
- Send empty/invalid data to trigger validation

## Advanced Testing

### Testing Concurrency

1. Duplicate requests in collection
2. Run same request multiple times in parallel
3. Verify cache behavior and token validity

### Testing Permissions

1. Create course as instructor
2. Try to update/delete as different user
3. Should receive 403 Forbidden

### Testing Pagination

1. Modify `per_page` parameter in transactions endpoint
2. Modify `page` parameter
3. Verify pagination metadata is correct

## Performance Testing

### Response Time Monitoring

1. First request to GET endpoint - ~100-200ms (includes DB query + caching)
2. Second request within 60s - ~5-20ms (from cache)
3. After 60s - ~100-200ms again (cache expired)

### Load Testing with Collection Runner

1. Set iterations to 10
2. Set delay to 0ms
3. Run collection
4. Monitor response times and cache behavior
