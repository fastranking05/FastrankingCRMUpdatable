# Departments & Module Permissions API Documentation

## Overview

Departments manage organisational units and **module-level permissions** through the `module_department` pivot table. Each department can be assigned CRUD permissions per module (`can_create`, `can_read`, `can_update`, `can_delete`).

Users receive module access through their assigned departments (`department_user` pivot), not through roles.

## Base URL

`/api/departments`

## Authentication

All endpoints require JWT:

```http
Authorization: Bearer {token}
```

## Permission Model

| Table | Purpose |
|-------|---------|
| `departments` | Department records |
| `modules` | CRM modules (Leads, Deals, Appointment, etc.) |
| `module_department` | Module permissions per department (`department_id`, `module_id`, CRUD flags) |
| `department_user` | Users assigned to departments |

When the API middleware checks `permission:Leads,read`, it resolves the user's active departments and looks up matching rows in `module_department`.

---

## 1. List Departments

**GET** `/api/departments`

**Query parameters (optional):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by `active` or `inactive` |
| `name` | string | Partial name search |
| `per_page` | integer | Cursor page size (default 15) |

**Required permission:** `Administration,read`

**Response:**

```json
{
  "success": true,
  "message": "Departments retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Lead Generation",
        "description": "Lead generation team",
        "status": "active",
        "created_by": 1,
        "created_at": "2026-04-27T10:00:00.000000Z",
        "updated_at": "2026-04-27T10:00:00.000000Z",
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        },
        "users": [],
        "modules": [
          {
            "id": 2,
            "name": "Leads",
            "pivot": {
              "department_id": 1,
              "module_id": 2,
              "can_create": true,
              "can_read": true,
              "can_update": true,
              "can_delete": false
            }
          }
        ]
      }
    ],
    "path": "http://127.0.0.1:8000/api/departments",
    "per_page": 15,
    "next_cursor": null,
    "prev_cursor": null
  }
}
```

---

## 2. Create Department

**POST** `/api/departments`

**Required permission:** `Administration,create`

**Request body:**

```json
{
  "name": "Lead Generation",
  "description": "Lead generation team",
  "status": "active",
  "user_ids": [2, 3],
  "module_permissions": [
    {
      "module_id": 2,
      "can_create": true,
      "can_read": true,
      "can_update": true,
      "can_delete": false
    },
    {
      "module_id": 5,
      "can_create": false,
      "can_read": true,
      "can_update": false,
      "can_delete": false
    }
  ]
}
```

**Validation rules:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Unique department name (max 255) |
| `description` | string | No | Department description |
| `status` | string | No | `active` or `inactive` (default `active`) |
| `user_ids` | array of integers | No | User IDs to assign to this department |
| `module_permissions` | array | No | Module permission blocks |
| `module_permissions.*.module_id` | integer | Yes (when array present) | Must exist in `modules` table |
| `module_permissions.*.can_create` | boolean | No | Default `false` |
| `module_permissions.*.can_read` | boolean | No | Default `false` |
| `module_permissions.*.can_update` | boolean | No | Default `false` |
| `module_permissions.*.can_delete` | boolean | No | Default `false` |

**Response (201):**

```json
{
  "success": true,
  "message": "Department created successfully",
  "data": {
    "id": 1,
    "name": "Lead Generation",
    "status": "active",
    "users": [...],
    "modules": [...]
  }
}
```

---

## 3. Get Department

**GET** `/api/departments/{id}`

**Required permission:** `Administration,read`

**Response:** Same department object shape as create/list, including `users` and `modules` with pivot permissions.

---

## 4. Update Department

**PUT** `/api/departments/{id}`

**Required permission:** `Administration,update`

**Request body:**

```json
{
  "name": "Lead Generation Updated",
  "description": "Updated description",
  "status": "active",
  "user_ids": [2, 4],
  "module_permissions": [
    {
      "module_id": 2,
      "can_create": true,
      "can_read": true,
      "can_update": true,
      "can_delete": true
    }
  ]
}
```

When `user_ids` is sent, department users are **synced** to the provided list.

When `module_permissions` is sent, module permissions are **synced** to the provided list (replaces existing module links for that department).

**Response:**

```json
{
  "success": true,
  "message": "Department updated successfully",
  "data": {...}
}
```

---

## 5. Delete Department

**DELETE** `/api/departments/{id}`

**Required permission:** `Administration,delete`

Detaches all users and module permissions, then deletes the department.

**Response:**

```json
{
  "success": true,
  "message": "Department deleted successfully",
  "data": null
}
```

---

## Modules API (related)

**Base URL:** `/api/modules`

Module list/show responses now include linked `departments` (instead of `roles`).

**GET** `/api/modules/{id}` example fragment:

```json
{
  "id": 2,
  "name": "Leads",
  "status": "active",
  "departments": [
    {
      "id": 1,
      "name": "Lead Generation",
      "pivot": {
        "department_id": 1,
        "module_id": 2,
        "can_create": true,
        "can_read": true,
        "can_update": true,
        "can_delete": false
      }
    }
  ]
}
```

---

## Database: `module_department`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `department_id` | bigint | FK → `departments` |
| `module_id` | bigint | FK → `modules` |
| `can_create` | boolean | Create permission |
| `can_read` | boolean | Read permission |
| `can_update` | boolean | Update permission |
| `can_delete` | boolean | Delete permission |
| Unique | `(department_id, module_id)` | One permission row per pair |

---

## cURL Examples

**Create department with module permissions:**

```bash
curl -X POST http://127.0.0.1:8000/api/departments \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sales",
    "status": "active",
    "user_ids": [2],
    "module_permissions": [
      {
        "module_id": 3,
        "can_create": true,
        "can_read": true,
        "can_update": true,
        "can_delete": false
      }
    ]
  }'
```

**Update module permissions:**

```bash
curl -X PUT http://127.0.0.1:8000/api/departments/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "module_permissions": [
      {
        "module_id": 3,
        "can_create": false,
        "can_read": true,
        "can_update": false,
        "can_delete": false
      }
    ]
  }'
```

---

## Security model

Module access is enforced on every protected API route:

1. User must be authenticated (JWT).
2. User must belong to at least one **active** department (`department_user`).
3. That department must have the required permission on the module in `module_department` (`can_create`, `can_read`, `can_update`, `can_delete`).

There is **no admin bypass** for module permissions — even admin users need department module permissions assigned.

**Role hierarchy (unchanged):** Roles still control **data visibility scope** within allowed modules (admin / manager / executive via teams and roles). Department permissions control **which modules** a user can access; role hierarchy controls **whose records** they see inside those modules.

Example:
- Department permission: `Leads,read` → user can call Leads list APIs.
- Role hierarchy: manager → sees team members' leads; executive → sees only own leads.

---

## Notes

- **Roles** (`/api/roles`) still exist for user role assignment but no longer store module permissions.
- Assign users to departments via `user_ids` on department create/update, or through the Users API department fields.
- Permission middleware resolves access from `department_user` + `module_department`.
