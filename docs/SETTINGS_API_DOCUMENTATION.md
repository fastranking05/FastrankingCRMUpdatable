# Settings API Documentation

## Overview

The Settings API provides functionality to manage the software configuration, including logos, company name, favicon, color palette, and whitelist IP addresses.

## Base URL

```
http://127.0.0.1:8000/api
```

## Authentication

All endpoints require JWT authentication. Include the JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Permissions

Access is hardcoded to users with `user_type` equal to `admin` or `master_admin` only. Any other user type will receive a `403 Forbidden` response.

---

## API Endpoints

### 1. List Settings

```http
GET /api/admin/settings
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**

```json
{
  "success": true,
  "message": "Settings retrieved successfully",
  "data": [
    {
      "id": 1,
      "dark_mode_logo": "uploads/logos/dark-logo.png",
      "light_mode_logo": "uploads/logos/light-logo.png",
      "sidepanel_logo": "uploads/logos/sidepanel-logo.png",
      "company_name": "Fastranking CRM",
      "favicon": "uploads/logos/favicon.ico",
      "primary_color": "#1E40AF",
      "secondary_color": "#F59E0B",
      "whitelist_ips": "192.168.1.1, 10.0.0.5",
      "created_by": 2,
      "created_at": "2026-08-05T14:30:00.000000Z",
      "updated_at": "2026-08-05T14:30:00.000000Z",
      "creator": {
        "id": 2,
        "first_name": "Admin",
        "last_name": "User"
      }
    }
  ]
}
```

---

### 2. Create Setting

```http
POST /api/admin/settings
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:**

```json
{
  "dark_mode_logo": "uploads/logos/dark-logo.png",
  "light_mode_logo": "uploads/logos/light-logo.png",
  "sidepanel_logo": "uploads/logos/sidepanel-logo.png",
  "company_name": "Fastranking CRM",
  "favicon": "uploads/logos/favicon.ico",
  "primary_color": "#1E40AF",
  "secondary_color": "#F59E0B",
  "whitelist_ips": "192.168.1.1, 10.0.0.5"
}
```

**Validation Rules:**

- `dark_mode_logo` - Optional, string, max 255
- `light_mode_logo` - Optional, string, max 255
- `sidepanel_logo` - Optional, string, max 255
- `company_name` - Optional, string, max 255
- `favicon` - Optional, string, max 255
- `primary_color` - Optional, string, max 20
- `secondary_color` - Optional, string, max 20
- `whitelist_ips` - Optional, string (comma-separated or any format)

**Success Response (201 Created):**

```json
{
  "success": true,
  "message": "Setting created successfully",
  "data": {
    "id": 1,
    "dark_mode_logo": "uploads/logos/dark-logo.png",
    "light_mode_logo": "uploads/logos/light-logo.png",
    "sidepanel_logo": "uploads/logos/sidepanel-logo.png",
    "company_name": "Fastranking CRM",
    "favicon": "uploads/logos/favicon.ico",
    "primary_color": "#1E40AF",
    "secondary_color": "#F59E0B",
    "whitelist_ips": "192.168.1.1, 10.0.0.5",
    "created_by": 2,
    "created_at": "2026-08-05T14:30:00.000000Z",
    "updated_at": "2026-08-05T14:30:00.000000Z"
  }
}
```

**Error Response (403 Forbidden):**

```json
{
  "success": false,
  "message": "Access denied. Only Admin or Master Admin can manage settings."
}
```

---

### 3. Get Single Setting

```http
GET /api/admin/settings/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**

```json
{
  "success": true,
  "message": "Setting retrieved successfully",
  "data": {
    "id": 1,
    "dark_mode_logo": "uploads/logos/dark-logo.png",
    "light_mode_logo": "uploads/logos/light-logo.png",
    "sidepanel_logo": "uploads/logos/sidepanel-logo.png",
    "company_name": "Fastranking CRM",
    "favicon": "uploads/logos/favicon.ico",
    "primary_color": "#1E40AF",
    "secondary_color": "#F59E0B",
    "whitelist_ips": "192.168.1.1, 10.0.0.5",
    "created_by": 2,
    "created_at": "2026-08-05T14:30:00.000000Z",
    "updated_at": "2026-08-05T14:30:00.000000Z",
    "creator": {
      "id": 2,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Setting not found"
}
```

---

### 4. Update Setting

```http
PUT /api/admin/settings/{id}
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:**

```json
{
  "company_name": "Fastranking Solutions",
  "primary_color": "#111827",
  "secondary_color": "#10B981"
}
```

**Validation Rules (all optional):**

- Same rules as Create Setting, but with `sometimes` prefix for partial updates.

**Success Response:**

```json
{
  "success": true,
  "message": "Setting updated successfully",
  "data": {
    "id": 1,
    "dark_mode_logo": "uploads/logos/dark-logo.png",
    "light_mode_logo": "uploads/logos/light-logo.png",
    "sidepanel_logo": "uploads/logos/sidepanel-logo.png",
    "company_name": "Fastranking Solutions",
    "favicon": "uploads/logos/favicon.ico",
    "primary_color": "#111827",
    "secondary_color": "#10B981",
    "whitelist_ips": "192.168.1.1, 10.0.0.5",
    "created_by": 2,
    "created_at": "2026-08-05T14:30:00.000000Z",
    "updated_at": "2026-08-05T14:31:00.000000Z"
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Setting not found"
}
```

---

### 5. Delete Setting

```http
DELETE /api/admin/settings/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

**Success Response:**

```json
{
  "success": true,
  "message": "Setting deleted successfully",
  "data": null
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Setting not found"
}
```

---

## Data Fields Description

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | Integer | Auto | Primary key, auto-increment |
| `dark_mode_logo` | String | No | Path/URL to dark mode logo |
| `light_mode_logo` | String | No | Path/URL to light mode logo |
| `sidepanel_logo` | String | No | Path/URL to sidepanel logo |
| `company_name` | String | No | Company or application name |
| `favicon` | String | No | Path/URL to favicon |
| `primary_color` | String | No | Primary color (hex or name) |
| `secondary_color` | String | No | Secondary color (hex or name) |
| `whitelist_ips` | Text | No | Whitelisted IP addresses |
| `created_by` | Integer | Auto | Foreign key to `users.id` (creator) |
| `created_at` | Timestamp | Auto | Record creation timestamp |
| `updated_at` | Timestamp | Auto | Record last update timestamp |

## Database Schema

**Table:** `settings`

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dark_mode_logo VARCHAR(255) NULL,
    light_mode_logo VARCHAR(255) NULL,
    sidepanel_logo VARCHAR(255) NULL,
    company_name VARCHAR(255) NULL,
    favicon VARCHAR(255) NULL,
    primary_color VARCHAR(20) NULL,
    secondary_color VARCHAR(20) NULL,
    whitelist_ips TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

## Relationships

- **creator** - BelongsTo relationship to User model (creator)

## Error Codes

| HTTP Status | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 401 | Unauthorized (invalid or missing JWT token) |
| 403 | Forbidden (only `admin` or `master_admin`) |
| 404 | Setting not found |
| 422 | Validation error |
| 500 | Internal server error |

## Notes

- The `created_by` field is automatically set to the authenticated user.
- Access is hardcoded in the controller to `admin` and `master_admin` user types.
- All timestamps are returned in UTC.
