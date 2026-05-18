# SEO Data Submission API Documentation

## Overview

The SEO Data Submission API allows SEO users (Digital Marketing department) to submit audit forms against existing SEO detail records. It supports saving question answers, updating audit metadata, and handling re-submissions.

## Base URL

```
http://127.0.0.1:8000/api
```

## Authentication

All endpoints require JWT authentication. Include the token in the `Authorization` header:

```
Authorization: Bearer {your_jwt_token}
```

## Permissions

| Action | Permission Required |
|--------|-------------------|
| Submit Audit | `SEO:create` |
| Get Active Questions | `SEO:create` |

---

## API Endpoints

### 1. Submit SEO Audit Form

Submits the complete SEO audit form with question answers. Updates the existing `seo_details` record and saves all answers. Supports re-submission (old answers are deleted and replaced).

**Endpoint:** `POST /api/seo-data-submission`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `seo_detail_id` | integer | Yes | ID of the existing SEO detail record |
| `audited_website` | string | Yes | URL of the audited website (max 500 chars) |
| `audited_date` | date | Yes | Date the audit was performed (YYYY-MM-DD) |
| `auditor` | string | Yes | Name of the auditor (max 255 chars) |
| `status` | string | Yes | Audit status: `Pending`, `Audit Completed`, or `Not Applicable` |
| `reason` | string | Conditional | Required when status is `Not Applicable` (max 2000 chars) |
| `answers` | array | Yes | Array of question answers (min 1 item) |
| `answers.*.seo_question_id` | integer | Yes | ID of the SEO question |
| `answers.*.answer` | string | Yes | Answer text (max 5000 chars) |
| `answers.*.comments` | string | No | Optional comments on the answer (max 5000 chars) |
| `comments` | array | No | Array of status change comments |
| `comments.*.followup_business_id` | integer | Yes (if comments present) | ID of the followup business |
| `comments.*.comment` | string | Yes (if comments present) | Comment text |
| `comments.*.old_status` | string | Yes (if comments present) | Previous status |
| `comments.*.new_status` | string | Yes (if comments present) | New status |

**Example Request — Audit Completed:**
```json
{
    "seo_detail_id": 1,
    "audited_website": "https://example.com",
    "audited_date": "2026-05-14",
    "auditor": "John Doe",
    "status": "Audit Completed",
    "answers": [
        {
            "seo_question_id": 1,
            "answer": "The website has well-optimized meta tags",
            "comments": "Consider adding OG tags for social sharing"
        },
        {
            "seo_question_id": 2,
            "answer": "2026-01-15",
            "comments": null
        },
        {
            "seo_question_id": 3,
            "answer": "Option A",
            "comments": "Selected based on current strategy"
        }
    ],
    "comments": [
    {
      "followup_business_id": 1,
      "comment": "Quality assessment completed successfully. All criteria met.",
      "old_status": "QA-Pending",
      "new_status": "QA-Approved"
    }
  ]
}
```

**Example Request — Not Applicable:**
```json
{
    "seo_detail_id": 2,
    "audited_website": "https://example2.com",
    "audited_date": "2026-05-14",
    "auditor": "Jane Smith",
    "status": "Not Applicable",
    "reason": "Business does not have a website",
    "answers": [
        {
            "seo_question_id": 1,
            "answer": "N/A - No website",
            "comments": null
        }
    ]
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "SEO audit submitted successfully",
    "data": {
        "seo_detail": {
            "id": 1,
            "followup_business_id": 5,
            "status": "Audit Completed",
            "reason": null,
            "audited_website": "https://example.com",
            "audited_date": "2026-05-14",
            "auditor": "John Doe",
            "assigned_user": 3,
            "created_at": "2026-05-12T10:00:00.000000Z",
            "updated_at": "2026-05-14T15:00:00.000000Z",
            "followup_business": {
                "id": 5,
                "business_name": "Example Corp",
                "website": "https://example.com"
            },
            "assigned_user": {
                "id": 3,
                "first_name": "John",
                "last_name": "Doe"
            },
            "question_answers": [
                {
                    "id": 10,
                    "seo_details_id": 1,
                    "seo_question_id": 1,
                    "answer": "The website has well-optimized meta tags",
                    "comments": "Consider adding OG tags for social sharing",
                    "seo_question": {
                        "id": 1,
                        "name": "How would you rate the meta tags?",
                        "answer_type": "text"
                    }
                }
            ]
        },
        "answers": [
            {
                "id": 10,
                "seo_details_id": 1,
                "seo_question_id": 1,
                "answer": "The website has well-optimized meta tags",
                "comments": "Consider adding OG tags for social sharing"
            }
        ],
        "comments": [
            {
                "id": 5,
                "followup_business_id": 1,
                "comment": "SEO audit completed successfully. All criteria met.",
                "old_status": "Pending",
                "new_status": "Audit Completed",
                "created_by": 3,
                "created_at": "2026-05-14T15:00:00.000000Z"
            }
        ],
        "execution_time_ms": 45.23
    }
}
```

**Error Responses:**

**422 Validation Error:**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "audited_website": ["The audited website field is required."],
        "answers": ["The answers field is required."]
    }
}
```

**404 Not Found:**
```json
{
    "success": false,
    "message": "SEO detail record not found"
}
```

**500 Server Error:**
```json
{
    "success": false,
    "message": "An error occurred while processing your request: ...",
    "errors": {
        "error_code": "SEO_SUBMISSION_FAILED"
    }
}
```

---

### 2. Get Active SEO Questions for Form

Returns all active SEO questions that can be used to build the submission form. Includes `answer_type` and `dropdown_options` for dynamic form rendering.

**Endpoint:** `GET /api/seo-data-submission/questions`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Active SEO questions retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "How would you rate the meta tags?",
            "answer_type": "text",
            "dropdown_options": null,
            "is_active": true
        },
        {
            "id": 2,
            "name": "When was the last SEO update?",
            "answer_type": "date",
            "dropdown_options": null,
            "is_active": true
        },
        {
            "id": 3,
            "name": "Select the SEO strategy",
            "answer_type": "dropdown",
            "dropdown_options": ["Option A", "Option B", "Option C"],
            "is_active": true
        }
    ]
}
```

---

## Status Workflow

| Status | Description |
|--------|-------------|
| `Pending` | Initial state when SEO record is created (auto-assigned during appointment booking) |
| `Audit Completed` | Audit form has been submitted with answers |
| `Not Applicable` | SEO audit is not applicable for this business (requires `reason`) |

---

## Re-Submission Behavior

When a user submits the audit form for an SEO detail that already has answers:
1. All existing `seo_question_answers` for that `seo_details_id` are deleted
2. New answers from the request are created
3. The `seo_details` record is updated with the new metadata

This allows users to correct or update their audit submissions.

---

## Database Tables Involved

### `seo_details`
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT PK | Auto-increment ID |
| `followup_business_id` | BIGINT FK | Reference to followup_businesses |
| `status` | VARCHAR | `Pending`, `Audit Completed`, `Not Applicable` |
| `reason` | TEXT | Reason when status is `Not Applicable` |
| `audited_website` | VARCHAR(500) | URL of audited website |
| `audited_date` | DATE | Date audit was performed |
| `auditor` | VARCHAR(255) | Name of auditor |
| `assigned_user` | BIGINT FK | Reference to users (Digital Marketing) |

### `seo_question_answers`
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT PK | Auto-increment ID |
| `seo_details_id` | BIGINT FK | Reference to seo_details |
| `seo_question_id` | BIGINT FK | Reference to seo_questions |
| `answer` | TEXT | Answer text |
| `comments` | TEXT | Optional comments |

### `comments`
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT PK | Auto-increment ID |
| `followup_business_id` | BIGINT FK | Reference to followup_businesses |
| `comment` | TEXT | Comment text |
| `old_status` | VARCHAR | Previous status before change |
| `new_status` | VARCHAR | New status after change |
| `created_by` | BIGINT FK | Reference to users (comment author) |

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Seo/
│               └── SeoDataSubmissionController.php   # Submission logic
├── Models/
│   ├── SeoDetail.php                                  # SEO detail model
│   ├── SeoQuestion.php                                # SEO question model
│   └── SeoQuestionAnswer.php                          # Answer pivot model
routes/
└── api/
    └── admin/
        └── seo/
            └── submission.php                         # Route definitions
```

---

## Error Handling

| Scenario | HTTP Code | Behavior |
|----------|-----------|----------|
| Missing required fields | 422 | Validation errors returned |
| Invalid `seo_detail_id` | 422 | Validation error |
| SEO detail not found | 404 | Not found error |
| Answer creation fails | 500 | Transaction rolled back, all changes reverted |
| Database deadlock | Auto-retry | Up to 3 retries before failing |

---

## cURL Examples

### Submit Audit Form:
```bash
curl -X POST http://127.0.0.1:8000/api/seo-data-submission \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "seo_detail_id": 1,
    "audited_website": "https://example.com",
    "audited_date": "2026-05-14",
    "auditor": "John Doe",
    "status": "Audit Completed",
    "answers": [
        {
            "seo_question_id": 1,
            "answer": "Well optimized",
            "comments": "Good work"
        }
    ]
}'
```

### Get Active Questions:
```bash
curl -X GET http://127.0.0.1:8000/api/seo-data-submission/questions \
  -H "Authorization: Bearer YOUR_TOKEN"
