# Services API Documentation

Complete CRUD API for managing services (`services` table).

## Base URL
`/api/services`

## Authentication & Permissions

All endpoints require JWT authentication:

```http
Authorization: Bearer YOUR_JWT_TOKEN
```

| Operation | Endpoints | Permission Required |
|-----------|-----------|---------------------|
| **Read** (open) | `GET /api/services`, `GET /api/services/{id}` | JWT only — any authenticated user |
| **Create** | `POST /api/services` | `Administration,create` |
| **Update** | `PUT /api/services/{id}` | `Administration,update` |
| **Delete** | `DELETE /api/services/{id}` | `Administration,delete` |

Users with the **Administration** module can manage services (create, update, delete). List and view endpoints are open to all logged-in users.

---

## 1. List Services
**GET** `/api/services`

Returns all services ordered by name (ascending).

**Query Parameters (optional):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | boolean | Filter by status (`1` = active, `0` = inactive) |
| `search` | string | Search by service name (partial match) |

**Example:**
```http
GET /api/services?status=1&search=seo
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response (200):**
```json
{
  "success": true,
  "message": "Services retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "SEO Optimization",
      "status": true,
      "created_by": 1,
      "created_at": "2026-06-09T10:00:00.000000Z",
      "updated_at": "2026-06-09T10:00:00.000000Z",
      "creator": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe"
      }
    }
  ]
}
```

---

## 2. Create Service
**POST** `/api/services`

**Request Body:**
```json
{
  "name": "SEO Optimization",
  "status": true
}
```

**Validation Rules:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Unique service name (max 255) |
| `status` | boolean | Yes | `true` / `1` = active, `false` / `0` = inactive |

`created_by` is set automatically from the authenticated user.

**Response (201):**
```json
{
  "success": true,
  "message": "Service created successfully",
  "data": {
    "id": 1,
    "name": "SEO Optimization",
    "status": true,
    "created_by": 1,
    "created_at": "2026-06-09T10:00:00.000000Z",
    "updated_at": "2026-06-09T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  }
}
```

---

## 3. Get Service
**GET** `/api/services/{id}`

**Example:**
```http
GET /api/services/1
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response (200):**
```json
{
  "success": true,
  "message": "Service retrieved successfully",
  "data": {
    "id": 1,
    "name": "SEO Optimization",
    "status": true,
    "created_by": 1,
    "created_at": "2026-06-09T10:00:00.000000Z",
    "updated_at": "2026-06-09T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  }
}
```

---

## 4. Update Service
**PUT** `/api/services/{id}`

**Request Body:**
```json
{
  "name": "SEO & Content Marketing",
  "status": false
}
```

**Validation Rules:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Unique service name (max 255) |
| `status` | boolean | Yes | Active/inactive flag |

**Response (200):**
```json
{
  "success": true,
  "message": "Service updated successfully",
  "data": {
    "id": 1,
    "name": "SEO & Content Marketing",
    "status": false,
    "created_by": 1,
    "created_at": "2026-06-09T10:00:00.000000Z",
    "updated_at": "2026-06-09T11:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  }
}
```

---

## 5. Delete Service
**DELETE** `/api/services/{id}`

**Example:**
```http
DELETE /api/services/1
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response (200):**
```json
{
  "success": true,
  "message": "Service deleted successfully",
  "data": null
}
```

---

## Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name has already been taken."]
  }
}
```

**Not Found (404):**
```json
{
  "message": "No query results for model [App\\Models\\Service] 99"
}
```

**Unauthorized (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Forbidden (403) — missing Administration permission:**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action"
}
```

---

## Frontend Integration Example

```javascript
const API_BASE = '/api/services';
const headers = {
  'Content-Type': 'application/json',
  Authorization: `Bearer ${token}`,
};

// List active services
const services = await fetch(`${API_BASE}?status=1`, { headers }).then((r) => r.json());

// Create service
await fetch(API_BASE, {
  method: 'POST',
  headers,
  body: JSON.stringify({ name: 'PPC Management', status: true }),
});

// Update service
await fetch(`${API_BASE}/1`, {
  method: 'PUT',
  headers,
  body: JSON.stringify({ name: 'PPC Management', status: false }),
});

// Delete service
await fetch(`${API_BASE}/1`, { method: 'DELETE', headers });
```

---

## API Endpoints Summary

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/api/services` | List all services | JWT only |
| GET | `/api/services/{id}` | Get single service | JWT only |
| POST | `/api/services` | Create a service | `Administration,create` |
| PUT | `/api/services/{id}` | Update a service | `Administration,update` |
| DELETE | `/api/services/{id}` | Delete a service | `Administration,delete` |

---

## File Structure

```
app/Http/Controllers/Api/Admin/ServiceController.php
app/Models/Service.php
routes/api/admin/services.php
database/migrations/2026_06_09_150000_create_services_table.php
```
