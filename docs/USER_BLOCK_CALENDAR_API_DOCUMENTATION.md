# User Block Calendar API Documentation

## Overview

The User Block Calendar API provides functionality to manage user calendar blocking, allowing administrators to block specific time slots for users on specific dates. This is useful for managing user availability, scheduling conflicts, and ensuring proper resource allocation.

## Base URL

```
http://127.0.0.1:8000/api
```

## Authentication

All API endpoints require JWT authentication. Include the JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Permissions

The API requires only JWT authentication. No specific permissions are required for any endpoint.

**Note:** All endpoints require a valid JWT token in the Authorization header.

---

## API Endpoints

### 1. Get Available Time Slots

```http
GET /api/user-block-calendar/available-slots?date=2026-04-15
Authorization: Bearer YOUR_JWT_TOKEN
```

**Query Parameters:**
- `date` (required) - The date to check for available time slots (YYYY-MM-DD format)

**Response:**
```json
{
  "success": true,
  "message": "Available time slots retrieved successfully",
  "data": [
    {
      "id": 1,
      "start_time": "2026-04-13T09:00:00.000000Z",
      "end_time": "2026-04-13T09:30:00.000000Z"
    },
    {
      "id": 2,
      "start_time": "2026-04-13T09:30:00.000000Z",
      "end_time": "2026-04-13T10:00:00.000000Z"
    }
  ]
}
```

**Logic:**
- Returns all time slots for the given date
- Excludes time slots that have appointments with `current_status` = "scheduled" or "rescheduled" on the same date and slot
- Only slots without conflicting appointments are returned as available

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "date": ["The date field is required."]
  }
}
```

---

### 3. List User Block Calendar Entries

```http
GET /api/user-block-calendar
Authorization: Bearer YOUR_JWT_TOKEN
```

**Query Parameters:**
- `user_id` (optional) - Filter by user ID
- `date` (optional) - Filter by specific date (YYYY-MM-DD format)
- `start_date` (optional) - Filter by date range start (YYYY-MM-DD format)
- `end_date` (optional) - Filter by date range end (YYYY-MM-DD format)
- `slot_id` (optional) - Filter by time slot ID
- `created_by` (optional) - Filter by creator user ID

**Response:**
```json
{
  "success": true,
  "message": "User block calendar entries retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "date": "2026-04-13",
      "slot_id": 5,
      "comments": "User on vacation",
      "created_by": 2,
      "created_at": "2026-04-13T10:00:00.000000Z",
      "updated_at": "2026-04-13T10:00:00.000000Z",
      "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com"
      },
      "time_slot": {
        "id": 5,
        "start_time": "2026-04-13T11:00:00.000000Z",
        "end_time": "2026-04-13T11:30:00.000000Z"
      },
      "created_by_user": {
        "id": 2,
        "first_name": "Admin",
        "last_name": "User",
        "email": "admin@example.com"
      }
    }
  ]
}
```

**Example with Filters:**
```http
GET /api/user-block-calendar?user_id=1&start_date=2026-04-01&end_date=2026-04-30
Authorization: Bearer YOUR_JWT_TOKEN
```

---

### 4. Create User Block Calendar Entry

```http
POST /api/user-block-calendar
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:**
```json
{
  "user_id": 1,
  "date": "2026-04-15",
  "slot_id": 5,
  "comments": "User on vacation"
}
```

**Validation Rules:**
- `user_id` - Required, must exist in users table
- `date` - Required, must be a valid date (YYYY-MM-DD format)
- `slot_id` - Required, must exist in time_slots table
- `comments` - Optional, string

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "User block calendar entry created successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "date": "2026-04-15",
    "slot_id": 5,
    "comments": "User on vacation",
    "created_by": 2,
    "created_at": "2026-04-13T10:05:00.000000Z",
    "updated_at": "2026-04-13T10:05:00.000000Z",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com"
    },
    "time_slot": {
      "id": 5,
      "start_time": "2026-04-13T11:00:00.000000Z",
      "end_time": "2026-04-13T11:30:00.000000Z"
    },
    "created_by_user": {
      "id": 2,
      "first_name": "Admin",
      "last_name": "User",
      "email": "admin@example.com"
    }
  }
}
```

**Error Response (409 Conflict) - Duplicate Entry:**
```json
{
  "success": false,
  "message": "User already has a block calendar entry for this date and slot"
}
```

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "user_id": ["The user id field is required."],
    "date": ["The date field is required."]
  }
}
```

---

### 5. Get Single User Block Calendar Entry

```http
GET /api/user-block-calendar/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "User block calendar entry retrieved successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "date": "2026-04-13",
    "slot_id": 5,
    "comments": "User on vacation",
    "created_by": 2,
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:00:00.000000Z",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com"
    },
    "time_slot": {
      "id": 5,
      "start_time": "2026-04-13T11:00:00.000000Z",
      "end_time": "2026-04-13T11:30:00.000000Z"
    },
    "created_by_user": {
      "id": 2,
      "first_name": "Admin",
      "last_name": "User",
      "email": "admin@example.com"
    }
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "User block calendar entry not found"
}
```

---

### 6. Update User Block Calendar Entry

```http
PUT /api/user-block-calendar/{id}
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:**
```json
{
  "user_id": 1,
  "date": "2026-04-16",
  "slot_id": 6,
  "comments": "Updated: User on sick leave"
}
```

**Validation Rules:**
- `user_id` - Optional, must exist in users table
- `date` - Optional, must be a valid date (YYYY-MM-DD format)
- `slot_id` - Optional, must exist in time_slots table
- `comments` - Optional, string

**Success Response:**
```json
{
  "success": true,
  "message": "User block calendar entry updated successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "date": "2026-04-16",
    "slot_id": 6,
    "comments": "Updated: User on sick leave",
    "created_by": 2,
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:10:00.000000Z",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com"
    },
    "time_slot": {
      "id": 6,
      "start_time": "2026-04-13T12:00:00.000000Z",
      "end_time": "2026-04-13T12:30:00.000000Z"
    },
    "created_by_user": {
      "id": 2,
      "first_name": "Admin",
      "last_name": "User",
      "email": "admin@example.com"
    }
  }
}
```

**Error Response (409 Conflict) - Duplicate Entry:**
```json
{
  "success": false,
  "message": "User already has a block calendar entry for this date and slot"
}
```

---

### 7. Delete User Block Calendar Entry

```http
DELETE /api/user-block-calendar/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "message": "User block calendar entry deleted successfully",
  "data": null
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "User block calendar entry not found"
}
```

---

## Data Fields Description

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | Integer | Auto | Primary key, auto-increment |
| `user_id` | Integer | Yes | Foreign key to users table |
| `date` | Date | Yes | Date of the block (YYYY-MM-DD format) |
| `slot_id` | Integer | Yes | Foreign key to time_slots table |
| `comments` | Text | No | Optional comments or reason for the block |
| `created_by` | Integer | Auto | Foreign key to users table (creator) |
| `created_at` | Timestamp | Auto | Record creation timestamp |
| `updated_at` | Timestamp | Auto | Record last update timestamp |

## Database Schema

**Table:** `user_block_calender`

```sql
CREATE TABLE user_block_calender (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    slot_id BIGINT UNSIGNED NOT NULL,
    comments TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX user_block_calender_unique (user_id, date, slot_id),
    INDEX (date, slot_id),
    INDEX (created_by)
);
```

## Relationships

- **user** - BelongsTo relationship to User model
- **timeSlot** - BelongsTo relationship to TimeSlot model
- **createdBy** - BelongsTo relationship to User model (creator)

## Error Codes

| HTTP Status | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 401 | Unauthorized (invalid or missing JWT token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Resource not found |
| 409 | Conflict (duplicate entry) |
| 422 | Validation error |
| 500 | Internal server error |

## Common Use Cases

### 1. Get available time slots for a specific date
```http
GET /api/user-block-calendar/available-slots?date=2026-04-15
```
This returns all time slots that are available for booking on the specified date, excluding slots that already have appointments with status "scheduled" or "rescheduled".

### 2. Block a user for a specific date and time slot
```http
POST /api/user-block-calendar
{
  "user_id": 1,
  "date": "2026-04-15",
  "slot_id": 5,
  "comments": "User on vacation"
}
```

### 2. View all blocked slots for a user in a date range
```http
GET /api/user-block-calendar?user_id=1&start_date=2026-04-01&end_date=2026-04-30
```

### 3. Check if a user is blocked for a specific date and slot
```http
GET /api/user-block-calendar?user_id=1&date=2026-04-15&slot_id=5
```

### 4. Update a block entry with new details
```http
PUT /api/user-block-calendar/1
{
  "comments": "Extended vacation"
}
```

### 5. Remove a block entry
```http
DELETE /api/user-block-calendar/1
```

## Notes

- The API prevents duplicate entries for the same user, date, and slot combination
- When a user is deleted, all their block calendar entries are automatically deleted (cascade)
- When a time slot is deleted, all associated block calendar entries are automatically deleted (cascade)
- The `created_by` field is automatically set to the authenticated user who created the entry
- All timestamps are in UTC timezone
- The API uses permission-based access control to ensure only authorized users can perform specific operations
