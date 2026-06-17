# SEO Question Categories API Documentation

## Overview

The SEO Question Categories API provides full CRUD (Create, Read, Update, Delete) operations for managing categories used to group SEO audit questions. Categories help organize questions by topic (e.g. On-Page SEO, Technical SEO, Content). The API follows the same patterns as the SEO Questions module.

## Base URL

```
https://your-domain.com/api/seo-question-categories
```

## Authentication

All endpoints require:
- **JWT Authentication**: Valid JWT token in `Authorization: Bearer <token>` header
- **Permission**: Module-level `SEO` permission with appropriate action (`read`, `create`, `update`, `delete`)

## API Endpoints

---

### 1. List All SEO Question Categories

**Endpoint:** `GET /api/seo-question-categories`

**Permission:** `SEO:read`

**Description:** Retrieves all SEO question categories with optional search and active-status filtering.

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | — | Search categories by name |
| `is_active` | boolean | No | — | Filter by active status (`1`/`0` or `true`/`false`) |

**Example Request:**
```
GET /api/seo-question-categories?search=on-page&is_active=1
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question categories retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "On-Page SEO",
            "is_active": true,
            "created_at": "2026-06-15T10:00:00.000000Z",
            "updated_at": "2026-06-15T10:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Technical SEO",
            "is_active": true,
            "created_at": "2026-06-15T10:05:00.000000Z",
            "updated_at": "2026-06-15T10:05:00.000000Z"
        }
    ]
}
```

---

### 2. Get Active SEO Question Categories

**Endpoint:** `GET /api/seo-question-categories/active`

**Permission:** `SEO:read`

**Description:** Retrieves all active categories (non-paginated). Useful for dropdowns when assigning a category to a question.

**Note:** This route is registered **before** `/{id}` to avoid Laravel interpreting `active` as an ID parameter.

**Example Request:**
```
GET /api/seo-question-categories/active
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Active SEO question categories retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "On-Page SEO",
            "is_active": true,
            "created_at": "2026-06-15T10:00:00.000000Z",
            "updated_at": "2026-06-15T10:00:00.000000Z"
        }
    ]
}
```

---

### 3. Get Single SEO Question Category

**Endpoint:** `GET /api/seo-question-categories/{id}`

**Permission:** `SEO:read`

**Description:** Retrieves a specific SEO question category by its ID.

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the category |

**Example Request:**
```
GET /api/seo-question-categories/1
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question category retrieved successfully",
    "data": {
        "id": 1,
        "name": "On-Page SEO",
        "is_active": true,
        "created_at": "2026-06-15T10:00:00.000000Z",
        "updated_at": "2026-06-15T10:00:00.000000Z"
    }
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question category not found"
}
```

---

### 4. Create SEO Question Category

**Endpoint:** `POST /api/seo-question-categories`

**Permission:** `SEO:create`

**Description:** Creates a new SEO question category.

**Request Body:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `name` | string | Yes | — | Category name (max 255 characters, unique) |
| `is_active` | boolean | No | `true` | Whether the category is active |

**Example Request:**
```json
{
    "name": "On-Page SEO",
    "is_active": true
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "SEO question category created successfully",
    "data": {
        "id": 1,
        "name": "On-Page SEO",
        "is_active": true,
        "created_at": "2026-06-15T10:00:00.000000Z",
        "updated_at": "2026-06-15T10:00:00.000000Z"
    }
}
```

**Validation Error Response (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."]
    }
}
```

---

### 5. Update SEO Question Category

**Endpoint:** `PUT /api/seo-question-categories/{id}`

**Permission:** `SEO:update`

**Description:** Updates an existing SEO question category.

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the category to update |

**Request Body:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `name` | string | Yes | — | Updated category name (max 255 characters, unique) |
| `is_active` | boolean | No | (unchanged) | Whether the category is active |

**Example Request:**
```json
{
    "name": "On-Page SEO & Content",
    "is_active": true
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question category updated successfully",
    "data": {
        "id": 1,
        "name": "On-Page SEO & Content",
        "is_active": true,
        "created_at": "2026-06-15T10:00:00.000000Z",
        "updated_at": "2026-06-15T10:30:00.000000Z"
    }
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question category not found"
}
```

---

### 6. Delete SEO Question Category

**Endpoint:** `DELETE /api/seo-question-categories/{id}`

**Permission:** `SEO:delete`

**Description:** Permanently deletes an SEO question category. Linked questions will have their `seo_question_category_id` set to `null`.

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the category to delete |

**Example Request:**
```
DELETE /api/seo-question-categories/1
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question category deleted successfully",
    "data": null
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question category not found"
}
```

---

## Database Schema

The `seo_question_categories` table structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED (PK) | Auto-increment primary key |
| `name` | VARCHAR(255) | Category name |
| `is_active` | BOOLEAN | Active status flag (default: `1`) |
| `created_at` | TIMESTAMP | Auto-set on creation |
| `updated_at` | TIMESTAMP | Auto-updated on modification |

## Related Models

- **SeoQuestionCategory** (`app/Models/SeoQuestionCategory.php`) — The category model
- **SeoQuestion** (`app/Models/SeoQuestion.php`) — Questions linked via `seo_question_category_id`

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Seo/
│               ├── SeoQuestionController.php
│               └── SeoQuestionCategoryController.php
├── Models/
│   ├── SeoQuestion.php
│   └── SeoQuestionCategory.php
routes/
└── api/
    └── admin/
        └── seo/
            ├── questions.php
            └── categories.php
```

## Error Handling

All endpoints return standardized JSON responses:

| Status Code | Meaning |
|-------------|---------|
| `200` | Success |
| `201` | Resource created |
| `404` | Resource not found |
| `422` | Validation failed |
| `500` | Server error |

All error responses follow the format:
```json
{
    "success": false,
    "message": "Human-readable error message",
    "errors": {}
}
```

## Usage Examples

### cURL — Create Category
```bash
curl -X POST http://127.0.0.1:8000/api/seo-question-categories \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Technical SEO", "is_active": true}'
```

### cURL — Assign Category to Question
```bash
curl -X POST http://127.0.0.1:8000/api/seo-questions \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Does the site have a robots.txt file?",
    "seo_question_category_id": 1,
    "answer_type": "text",
    "is_active": true
  }'
```
