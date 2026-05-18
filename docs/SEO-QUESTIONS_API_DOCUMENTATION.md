# SEO Questions API Documentation

## Overview

The SEO Questions API provides full CRUD (Create, Read, Update, Delete) operations for managing SEO audit questions. Each question supports configurable answer types (`text`, `textarea`, `number`, `date`, `dropdown`) and dropdown options for select-based answers. These questions are used during SEO audits to evaluate website SEO performance. The API follows the same patterns as the Quality Questions module and integrates with the existing CRM system.

## Answer Types

| Type | Description | Requires `dropdown_options` |
|------|-------------|---------------------------|
| `text` | Single-line text input | No |
| `textarea` | Multi-line text input | No |
| `number` | Numeric input | No |
| `date` | Date picker input | No |
| `dropdown` | Select dropdown with predefined options | **Yes** |

## Base URL

```
https://your-domain.com/api/seo-questions
```

## Authentication

All endpoints require:
- **JWT Authentication**: Valid JWT token in `Authorization: Bearer <token>` header
- **Permission**: Module-level `SEO` permission with appropriate action (`read`, `create`, `update`, `delete`)

## API Endpoints

---

### 1. List All SEO Questions

**Endpoint:** `GET /api/seo-questions`

**Permission:** `SEO:read`

**Description:** Retrieves a paginated list of all SEO questions with optional search and active-status filtering.

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | — | Search questions by name |
| `is_active` | boolean | No | — | Filter by active status (`1`/`0` or `true`/`false`) |
| `per_page` | integer | No | `50` | Number of records per page |

**Example Request:**
```
GET /api/seo-questions?search=meta&is_active=1&per_page=20
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO questions retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Does the website have a meta title?",
                "answer_type": "text",
                "dropdown_options": null,
                "is_active": true,
                "created_by": 1,
                "created_at": "2026-05-12T10:00:00.000000Z",
                "updated_at": "2026-05-12T10:00:00.000000Z",
                "creator": {
                    "id": 1,
                    "first_name": "Suraj",
                    "last_name": "Kumar"
                }
            },
            {
                "id": 2,
                "name": "Select the SEO score range",
                "answer_type": "dropdown",
                "dropdown_options": ["Poor (0-25)", "Average (26-50)", "Good (51-75)", "Excellent (76-100)"],
                "is_active": true,
                "created_by": 1,
                "created_at": "2026-05-12T11:00:00.000000Z",
                "updated_at": "2026-05-12T11:00:00.000000Z",
                "creator": {
                    "id": 1,
                    "first_name": "Suraj",
                    "last_name": "Kumar"
                }
            }
        ],
        "first_page_url": "http://127.0.0.1:8000/api/seo-questions?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://127.0.0.1:8000/api/seo-questions?page=1",
        "links": [],
        "next_page_url": null,
        "path": "http://127.0.0.1:8000/api/seo-questions",
        "per_page": 20,
        "prev_page_url": null,
        "to": 2,
        "total": 2
    }
}
```

---

### 2. Get Active SEO Questions

**Endpoint:** `GET /api/seo-questions/active`

**Permission:** `SEO:read`

**Description:** Retrieves all active SEO questions (non-paginated). Useful for dropdowns and form selections.

**Note:** This route must be registered **before** `/{id}` to avoid Laravel interpreting `active` as an ID parameter.

**Example Request:**
```
GET /api/seo-questions/active
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Active SEO questions retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Does the website have a meta title?",
            "answer_type": "text",
            "dropdown_options": null,
            "is_active": true,
            "created_by": 1,
            "created_at": "2026-05-12T10:00:00.000000Z",
            "updated_at": "2026-05-12T10:00:00.000000Z",
            "creator": {
                "id": 1,
                "first_name": "Suraj",
                "last_name": "Kumar"
            }
        }
    ]
}
```

---

### 3. Get Single SEO Question

**Endpoint:** `GET /api/seo-questions/{id}`

**Permission:** `SEO:read`

**Description:** Retrieves a specific SEO question by its ID.

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the SEO question |

**Example Request:**
```
GET /api/seo-questions/1
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question retrieved successfully",
    "data": {
        "id": 1,
        "name": "Does the website have a meta title?",
        "answer_type": "text",
        "dropdown_options": null,
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-05-12T10:00:00.000000Z",
        "updated_at": "2026-05-12T10:00:00.000000Z",
        "creator": {
            "id": 1,
            "first_name": "Suraj",
            "last_name": "Kumar"
        }
    }
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question not found"
}
```

---

### 4. Create SEO Question

**Endpoint:** `POST /api/seo-questions`

**Permission:** `SEO:create`

**Description:** Creates a new SEO question with a specified answer type. When `answer_type` is `dropdown`, `dropdown_options` is required.

**Request Body:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `name` | string | Yes | — | The question text (max 1000 characters) |
| `answer_type` | string | Yes | — | Answer input type: `text`, `textarea`, `number`, `date`, `dropdown` |
| `dropdown_options` | array | Conditional | — | Required when `answer_type` is `dropdown`. Array of option strings |
| `is_active` | boolean | No | `true` | Whether the question is active |

**Example Request (Text type):**
```json
{
    "name": "Does the website have proper heading structure?",
    "answer_type": "text",
    "is_active": true
}
```

**Example Request (Dropdown type):**
```json
{
    "name": "Select the SEO score range",
    "answer_type": "dropdown",
    "dropdown_options": ["Poor (0-25)", "Average (26-50)", "Good (51-75)", "Excellent (76-100)"],
    "is_active": true
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "SEO question created successfully",
    "data": {
        "id": 3,
        "name": "Select the SEO score range",
        "answer_type": "dropdown",
        "dropdown_options": ["Poor (0-25)", "Average (26-50)", "Good (51-75)", "Excellent (76-100)"],
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-05-14T12:00:00.000000Z",
        "updated_at": "2026-05-14T12:00:00.000000Z",
        "creator": {
            "id": 1,
            "first_name": "Suraj",
            "last_name": "Kumar"
        }
    }
}
```

**Validation Error Response (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "answer_type": ["The answer type field is required."],
        "dropdown_options": ["The dropdown options field is required when answer type is dropdown."]
    }
}
```

---

### 5. Update SEO Question

**Endpoint:** `PUT /api/seo-questions/{id}`

**Permission:** `SEO:update`

**Description:** Updates an existing SEO question including its answer type and dropdown options.

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the SEO question to update |

**Request Body:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `name` | string | Yes | — | The updated question text (max 1000 characters) |
| `answer_type` | string | Yes | — | Answer input type: `text`, `textarea`, `number`, `date`, `dropdown` |
| `dropdown_options` | array | Conditional | — | Required when `answer_type` is `dropdown`. Array of option strings |
| `is_active` | boolean | No | (unchanged) | Whether the question is active |

**Example Request:**
```json
{
    "name": "Does the website have proper heading structure (H1-H6)?",
    "answer_type": "textarea",
    "is_active": true
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question updated successfully",
    "data": {
        "id": 3,
        "name": "Does the website have proper heading structure (H1-H6)?",
        "answer_type": "textarea",
        "dropdown_options": null,
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-05-14T12:00:00.000000Z",
        "updated_at": "2026-05-14T12:30:00.000000Z",
        "creator": {
            "id": 1,
            "first_name": "Suraj",
            "last_name": "Kumar"
        }
    }
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question not found"
}
```

---

### 6. Toggle Question Active Status

**Endpoint:** `POST /api/seo-questions/{id}/toggle-status`

**Permission:** `SEO:update`

**Description:** Toggles the `is_active` status of a question (active → inactive, inactive → active).

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the SEO question |

**Example Request:**
```
POST /api/seo-questions/3/toggle-status
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Question status updated successfully",
    "data": {
        "is_active": false
    }
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question not found"
}
```

---

### 7. Delete SEO Question

**Endpoint:** `DELETE /api/seo-questions/{id}`

**Permission:** `SEO:delete`

**Description:** Permanently deletes an SEO question.

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the SEO question to delete |

**Example Request:**
```
DELETE /api/seo-questions/3
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO question deleted successfully",
    "data": null
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "SEO question not found"
}
```

---

## Database Schema

The `seo_questions` table structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED (PK) | Auto-increment primary key |
| `name` | VARCHAR(255) | The question text |
| `answer_type` | VARCHAR(255) | Answer input type: `text`, `textarea`, `number`, `date`, `dropdown` (default: `text`) |
| `dropdown_options` | JSON (nullable) | Array of option strings when `answer_type` is `dropdown` |
| `is_active` | BOOLEAN | Active status flag (default: `true`) |
| `created_by` | BIGINT UNSIGNED (FK) | References `users.id` |
| `created_at` | TIMESTAMP | Auto-set on creation |
| `updated_at` | TIMESTAMP | Auto-updated on modification |

## Related Models

- **SeoQuestion** (`app/Models/SeoQuestion.php`) — The question model
- **SeoQuestionAnswer** (`app/Models/SeoQuestionAnswer.php`) — Answers linked to questions during audits
- **SeoDetail** (`app/Models/SeoDetail.php`) — The SEO audit record that references answers

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Seo/
│               └── SeoQuestionController.php    # CRUD controller
├── Models/
│   ├── SeoQuestion.php                          # Question model
│   └── SeoQuestionAnswer.php                    # Answer model
routes/
└── api/
    └── admin/
        └── seo/
            └── questions.php                    # Route definitions
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
    "errors": {}  // Optional: validation error details
}
