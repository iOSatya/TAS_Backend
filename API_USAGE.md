# API Usage Examples

## Base URL
All API endpoints are relative to your server base URL. For local development:
```
http://localhost:8000/api
```

## Authentication
This API uses Bearer token authentication with Laravel Sanctum.

1. First, obtain an access token via `/login` or `/register` endpoints
2. Include the token in the Authorization header for protected endpoints:
   ```
   Authorization: Bearer <access_token>
   ```

## Response Formats

### Success Responses
- `200 OK`: Request succeeded
- `201 Created`: Resource created successfully
- `204 No Content`: Success with no response body

### Error Responses
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Missing or invalid authentication
- `403 Forbidden`: Authenticated but insufficient permissions (non-admin accessing admin endpoints)
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server error

---

## Authentication Endpoints

### 1. Register a New User
Creates a new user account and returns an authentication token.

**Endpoint:** `POST /register`

**Authentication:** None required

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123"
}
```

**Success Response (201 Created):**
```json
{
  "access_token": "1|abcdefghijklmnopqrstuvwxyz123456",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "is_admin": false,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:00:00.000000Z",
    "updated_at": "2026-04-18T12:00:00.000000Z"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword123"
  }'
```

---

### 2. Login
Authenticates a user and returns an authentication token.

**Endpoint:** `POST /login`

**Authentication:** None required

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "securepassword123"
}
```

**Success Response (200 OK):**
```json
{
  "access_token": "1|abcdefghijklmnopqrstuvwxyz123456",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "is_admin": false,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:00:00.000000Z",
    "updated_at": "2026-04-18T12:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized` (Invalid credentials):
  ```json
  { "message": "Invalid credentials" }
  ```
- `422 Unprocessable Entity` (Validation errors):
  ```json
  {
    "errors": {
      "email": ["The email field is required."],
      "password": ["The password field is required."]
    }
  }
  ```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "securepassword123"
  }'
```

---

### 3. Logout
Revokes the current authentication token.

**Endpoint:** `POST /logout`

**Authentication:** Required (Bearer token)

**Request Body:** None

**Success Response (200 OK):**
```json
{ "message": "Logged out successfully" }
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456"
```

---

### 4. Get Current User Profile
Retrieves the authenticated user's profile information.

**Endpoint:** `GET /profile`

**Authentication:** Required (Bearer token)

**Success Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "is_admin": false,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:00:00.000000Z",
    "updated_at": "2026-04-18T12:00:00.000000Z"
  }
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456"
```

---

### 5. Update Current User Profile
Updates the authenticated user's profile information.

**Endpoint:** `PUT /profile`

**Authentication:** Required (Bearer token)

**Request Body:** (All fields optional, but `current_password` required when changing password)
```json
{
  "name": "John Smith",
  "email": "john.smith@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123",
  "current_password": "securepassword123"
}
```

**Success Response (200 OK):**
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "name": "John Smith",
    "email": "john.smith@example.com",
    "is_admin": false,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:00:00.000000Z",
    "updated_at": "2026-04-18T12:05:00.000000Z"
  }
}
```

**Error Responses:**
- `422 Unprocessable Entity` (Incorrect current password):
  ```json
  { "message": "Current password is incorrect" }
  ```
- `422 Unprocessable Entity` (Validation errors):
  ```json
  {
    "errors": {
      "email": ["The email has already been taken."],
      "password": ["The password confirmation does not match."]
    }
  }
  ```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Smith",
    "current_password": "securepassword123"
  }'
```

---

### 6. Delete Current User Account
Deletes the authenticated user's account. Requires password confirmation.

**Endpoint:** `DELETE /profile`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "password": "securepassword123"
}
```

**Success Response (200 OK):**
```json
{ "message": "Account deleted successfully" }
```

**Error Responses:**
- `422 Unprocessable Entity` (Incorrect password):
  ```json
  { "message": "Password is incorrect" }
  ```
- `422 Unprocessable Entity` (Validation errors):
  ```json
  {
    "errors": {
      "password": ["The password field is required."]
    }
  }
  ```

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "securepassword123"
  }'
```

---

## Admin User Management Endpoints

**Important:** All admin endpoints require:
1. Valid Bearer token authentication
2. User must have `is_admin = true`

### 7. List All Users (Admin)
Retrieves a paginated list of all users with optional filtering and search.

**Endpoint:** `GET /admin/users`

**Authentication:** Required (Bearer token + Admin privileges)

**Query Parameters:**
- `search` (optional): Search term for name or email
- `is_admin` (optional): Filter by admin status (`true` or `false`)
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "is_admin": true,
      "email_verified_at": null,
      "created_at": "2026-04-18T12:00:00.000000Z",
      "updated_at": "2026-04-18T12:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com",
      "is_admin": false,
      "email_verified_at": null,
      "created_at": "2026-04-18T12:01:00.000000Z",
      "updated_at": "2026-04-18T12:01:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/admin/users?page=1",
    "last": "http://localhost:8000/api/admin/users?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/admin/users?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "links": [...],
    "path": "http://localhost:8000/api/admin/users",
    "per_page": 15,
    "to": 15,
    "total": 45
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Missing or invalid token
- `403 Forbidden`: Authenticated but not admin

**cURL Examples:**

List all users:
```bash
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer 1|admin_token_here"
```

Search for users:
```bash
curl -X GET "http://localhost:8000/api/admin/users?search=john&is_admin=false&per_page=10" \
  -H "Authorization: Bearer 1|admin_token_here"
```

---

### 8. Get User Details (Admin)
Retrieves details of a specific user.

**Endpoint:** `GET /admin/users/{id}`

**Authentication:** Required (Bearer token + Admin privileges)

**URL Parameters:**
- `id`: User ID (integer)

**Success Response (200 OK):**
```json
{
  "user": {
    "id": 2,
    "name": "John Doe",
    "email": "john@example.com",
    "is_admin": false,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:01:00.000000Z",
    "updated_at": "2026-04-18T12:01:00.000000Z"
  }
}
```

**Error Responses:**
- `404 Not Found`: User not found
- `403 Forbidden`: Not admin

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/admin/users/2 \
  -H "Authorization: Bearer 1|admin_token_here"
```

---

### 9. Create New User (Admin)
Creates a new user account. Admin can specify admin status.

**Endpoint:** `POST /admin/users`

**Authentication:** Required (Bearer token + Admin privileges)

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "securepassword123",
  "is_admin": false
}
```

**Success Response (201 Created):**
```json
{
  "message": "User created successfully",
  "user": {
    "id": 3,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "is_admin": false,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:02:00.000000Z",
    "updated_at": "2026-04-18T12:02:00.000000Z"
  }
}
```

**Error Responses:**
- `422 Unprocessable Entity`: Validation errors
- `403 Forbidden`: Not admin

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer 1|admin_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "securepassword123",
    "is_admin": false
  }'
```

---

### 10. Update User (Admin)
Updates an existing user's information. Admin can change admin status.

**Endpoint:** `PUT /admin/users/{id}`

**Authentication:** Required (Bearer token + Admin privileges)

**URL Parameters:**
- `id`: User ID (integer)

**Request Body:** (All fields optional)
```json
{
  "name": "Jane Doe",
  "email": "jane.doe@example.com",
  "password": "newpassword123",
  "is_admin": true
}
```

**Success Response (200 OK):**
```json
{
  "message": "User updated successfully",
  "user": {
    "id": 3,
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "is_admin": true,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:02:00.000000Z",
    "updated_at": "2026-04-18T12:03:00.000000Z"
  }
}
```

**Error Responses:**
- `404 Not Found`: User not found
- `422 Unprocessable Entity`: Validation errors or attempting to remove own admin status
- `403 Forbidden`: Not admin

**Special Case:** Admin cannot remove their own admin status:
```json
{ "message": "You cannot remove your own admin status" }
```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/admin/users/3 \
  -H "Authorization: Bearer 1|admin_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "is_admin": true
  }'
```

---

### 11. Delete User (Admin)
Deletes a user account. Admin cannot delete their own account.

**Endpoint:** `DELETE /admin/users/{id}`

**Authentication:** Required (Bearer token + Admin privileges)

**URL Parameters:**
- `id`: User ID (integer)

**Success Response (200 OK):**
```json
{ "message": "User deleted successfully" }
```

**Error Responses:**
- `404 Not Found`: User not found
- `422 Unprocessable Entity`: Attempting to delete own account
- `403 Forbidden`: Not admin

**Special Case:** Admin cannot delete themselves:
```json
{ "message": "You cannot delete your own account" }
```

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/admin/users/3 \
  -H "Authorization: Bearer 1|admin_token_here"
```

---

### 12. Reset User Password (Admin)
Resets a user's password and generates a new random password.

**Endpoint:** `POST /admin/users/{id}/reset-password`

**Authentication:** Required (Bearer token + Admin privileges)

**URL Parameters:**
- `id`: User ID (integer)

**Success Response (200 OK):**
```json
{
  "message": "Password reset successfully",
  "new_password": "R4nd0mP@ssw0rd!23",
  "user": {
    "id": 3,
    "email": "jane@example.com"
  }
}
```

**Important:** The new password is returned in the response. In production, consider sending it via email instead of returning in the API response.

**Error Responses:**
- `404 Not Found`: User not found
- `403 Forbidden`: Not admin

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/admin/users/3/reset-password \
  -H "Authorization: Bearer 1|admin_token_here"
```

---

### 13. Toggle Admin Status (Admin)
Toggles a user's admin status (grant/revoke admin privileges).

**Endpoint:** `PUT /admin/users/{id}/admin-status`

**Authentication:** Required (Bearer token + Admin privileges)

**URL Parameters:**
- `id`: User ID (integer)

**Success Response (200 OK):**
```json
{
  "message": "Admin status updated successfully",
  "user": {
    "id": 3,
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "is_admin": true,
    "email_verified_at": null,
    "created_at": "2026-04-18T12:02:00.000000Z",
    "updated_at": "2026-04-18T12:04:00.000000Z"
  }
}
```

**Error Responses:**
- `404 Not Found`: User not found
- `422 Unprocessable Entity`: Attempting to remove own admin status
- `403 Forbidden`: Not admin

**Special Case:** Admin cannot remove their own admin status:
```json
{ "message": "You cannot remove your own admin status" }
```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/admin/users/3/admin-status \
  -H "Authorization: Bearer 1|admin_token_here"
```

---

## Example Workflow

### 1. Register as Admin (First Time)
```bash
# Register a new admin user (if no admin exists yet)
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "System Admin",
    "email": "admin@example.com",
    "password": "AdminPass123"
  }'
```

### 2. Manually Set Admin Status
After registration, you need to manually set `is_admin = true` in the database for the first admin user, or use the admin user created by the seeder (`admin@example.com` with password `password`).

### 3. Login as Admin
```bash
# Login with admin credentials
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### 4. Use Admin Token for Admin Operations
```bash
# Save the token from login response
TOKEN="1|admin_token_here"

# List all users
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer $TOKEN"

# Create a new regular user
curl -X POST http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Regular User",
    "email": "user@example.com",
    "password": "UserPass123",
    "is_admin": false
  }'
```

---

## Testing with Seeded Data
The database seeder creates two users:
1. **Regular User**: `test@example.com` (password: `password`)
2. **Admin User**: `admin@example.com` (password: `password`)

Use these credentials for testing the API endpoints.

---

## Notes

1. **Password Security**: Always use strong passwords and consider implementing additional security measures in production.
2. **Token Management**: Tokens don't expire automatically. Implement token expiration and refresh tokens for production use.
3. **Rate Limiting**: Consider implementing rate limiting to prevent abuse.
4. **CORS**: Configure CORS appropriately for your frontend application.
5. **Environment**: The examples use `localhost:8000`. Update the base URL for your deployment environment.