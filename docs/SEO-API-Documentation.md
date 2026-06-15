# SEO Audit API Documentation

## Overview

The SEO Audit API provides four endpoints for managing and retrieving SEO audit data with role-based access control. The API follows the same patterns as the Quality module and is designed to work seamlessly with the existing CRM system.

## Base URL

```
https://your-domain.com/api/seo-audit
```

## Authentication

All endpoints require:
- **JWT Authentication**: Valid JWT token in Authorization header
- **Permission**: `Administration,read` permission required

## Role-Based Access Control

The API implements role-based access control similar to the Quality module:

| Role | Department | Access Level |
|------|------------|-------------|
| Admin | Any | Full access to all SEO data |
| Manager | Digital Marketing | Can see own + team members' data |
| Executive | Digital Marketing | Can see only own data |
| Other roles | Any | No access to SEO data |

## API Endpoints

### 1. Get Pending SEO Audits

**Endpoint:** `GET /api/seo-audit/audit-pending`

**Description:** Retrieves SEO records with status "Pending"

**Response Format:**
```json
{
    "success": true,
    "message": "SEO audit pending data retrieved successfully",
    "data": [
        {
            "id": 1,
            "followup_business_id": 1,
            "status": "Pending",
            "reason": "Auto-assigned on appointment creation",
            "audited_website": "https://example.com",
            "audited_date": "2026-05-12",
            "auditor": "John Doe",
            "assigned_user": {
                "id": 3,
                "first_name": "Test",
                "last_name": "User",
                "email": "test@example.com"
            },
            "created_at": "2026-05-12T10:00:00.000000Z",
            "updated_at": "2026-05-12T10:00:00.000000Z",
            "question_answers": [
                {
                    "id": 10,
                    "seo_details_id": 1,
                    "seo_question_id": 1,
                    "answer": "The website has well-optimized meta tags",
                    "comments": "Consider adding OG tags",
                    "created_at": "2026-05-14T15:00:00.000000Z",
                    "updated_at": "2026-05-14T15:00:00.000000Z",
                    "question": {
                        "id": 1,
                        "name": "How would you rate the meta tags?",
                        "answer_type": "text",
                        "dropdown_options": null
                    }
                },
                {
                    "id": 11,
                    "seo_details_id": 1,
                    "seo_question_id": 3,
                    "answer": "Option A",
                    "comments": null,
                    "created_at": "2026-05-14T15:00:00.000000Z",
                    "updated_at": "2026-05-14T15:00:00.000000Z",
                    "question": {
                        "id": 3,
                        "name": "Select the SEO strategy",
                        "answer_type": "dropdown",
                        "dropdown_options": ["Option A", "Option B", "Option C"]
                    }
                }
            ],
            "business": {
                "id": 1,
                "name": "Example Business",
                "category": "Technology",
                "type": "Company",
                "website": "https://example.com",
                "phone": "+1234567890",
                "email": "business@example.com",
                "auth_persons": [
                    {
                        "id": 1,
                        "title": "Mr",
                        "firstname": "John",
                        "middlename": "",
                        "lastname": "Doe",
                        "job_title": "CEO",
                        "primaryemail": "john@example.com",
                        "primarymobile": "+1234567890",
                        "is_primary": true
                    }
                ]
            }
        }
    ]
}
```

### 2. Get Completed SEO Audits

**Endpoint:** `GET /api/seo-audit/audit-completed`

**Description:** Retrieves SEO records with status "Audit Completed"

**Response Format:** Same as pending endpoint, but filters for `status: "Audit Completed"`

### 3. Get Not Applicable SEO Audits

**Endpoint:** `GET /api/seo-audit/not-applicable`

**Description:** Retrieves SEO records with status "Not Applicable"

**Response Format:** Same as pending endpoint, but filters for `status: "Not Applicable"`

### 4. Get All SEO Audits

**Endpoint:** `GET /api/seo-audit/all`

**Description:** Retrieves all SEO records regardless of status

**Response Format:** Same as pending endpoint, but includes all statuses

### 5. Get Comprehensive SEO View

**Endpoint:** `GET /api/seo-view/comprehensive`

**Description:** Retrieves comprehensive SEO data including full business profile (`business_details`), auth persons, business service, lead qualification, comments, appointments, and SEO details

**Response Format:**
```json
{
    "success": true,
    "message": "Comprehensive SEO view retrieved successfully",
    "data": [
        {
            "seo_details": {
                "id": 1,
                "followup_business_id": 1,
                "status": "Pending",
                "reason": "Auto-assigned on appointment creation",
                "audited_website": "https://example.com",
                "audited_date": "2026-05-12",
                "auditor": "John Doe",
                "assigned_user": {
                    "id": 3,
                    "first_name": "Test",
                    "last_name": "User",
                    "email": "test@example.com"
                },
                "created_at": "2026-05-12T10:00:00.000000Z",
                "updated_at": "2026-05-12T10:00:00.000000Z",
                "question_answers": [
                    {
                        "id": 10,
                        "question": {
                            "id": 1,
                            "name": "How would you rate the meta tags?",
                            "answer_type": "text",
                            "dropdown_options": null
                        },
                        "answer": "The website has well-optimized meta tags",
                        "comments": "Consider adding OG tags",
                        "created_at": "2026-05-14T15:00:00.000000Z",
                        "updated_at": "2026-05-14T15:00:00.000000Z"
                    },
                    {
                        "id": 11,
                        "question": {
                            "id": 3,
                            "name": "Select the SEO strategy",
                            "answer_type": "dropdown",
                            "dropdown_options": ["Option A", "Option B", "Option C"]
                        },
                        "answer": "Option A",
                        "comments": null,
                        "created_at": "2026-05-14T15:00:00.000000Z",
                        "updated_at": "2026-05-14T15:00:00.000000Z"
                    }
                ]
            },
            "business_details": {
                "id": 1,
                "name": "Example Business",
                "trading_name": "Example Trading",
                "company_registration_number": "12345678",
                "address": "123 Main Street",
                "company_size": "11-50",
                "category": "Technology",
                "sub_category": "SaaS",
                "type": "Company",
                "source_name": "Website",
                "sub_source": "Google Ads",
                "priority": "high",
                "annual_revenue": "500000.00",
                "number_of_locations": 3,
                "website": "https://example.com",
                "created_by": 1,
                "created_at": "2026-05-12T09:00:00.000000Z",
                "updated_at": "2026-05-12T09:00:00.000000Z",
                "creator": {
                    "id": 1,
                    "first_name": "Admin",
                    "last_name": "User",
                    "email": "admin@example.com"
                },
                "auth_persons": [
                    {
                        "id": 1,
                        "title": "Mr",
                        "firstname": "John",
                        "middlename": "",
                        "lastname": "Doe",
                        "job_title": "CEO",
                        "primaryemail": "john@example.com",
                        "primarymobile": "+1234567890",
                        "is_primary": true
                    }
                ],
                "business_service": {
                    "id": 1,
                    "interested_service_ids": [1, 2],
                    "interested_services_list": [
                        { "id": 1, "name": "SEO" },
                        { "id": 2, "name": "PPC" }
                    ],
                    "primary_service_id": 1,
                    "primary_service": { "id": 1, "name": "SEO" }
                },
                "lead_qualification": {
                    "id": 1,
                    "temperature": "hot",
                    "budget": true,
                    "authority": false,
                    "need": true,
                    "timeline": false
                },
                "comments": [
                    {
                        "id": 1,
                        "comment": "Initial contact made",
                        "old_status": null,
                        "new_status": "Contacted",
                        "created_at": "2026-05-12T09:30:00.000000Z",
                        "updated_at": "2026-05-12T09:30:00.000000Z",
                        "creator": {
                            "id": 1,
                            "first_name": "Admin",
                            "last_name": "User",
                            "email": "admin@example.com"
                        }
                    }
                ],
                "appointments": [
                    {
                        "id": 1,
                        "date": "2026-05-12",
                        "current_status": "Booked",
                        "source": "Direct",
                        "time_slot": {
                            "id": 1,
                            "start_time": "10:00:00",
                            "end_time": "11:00:00"
                        },
                        "created_at": "2026-05-12T10:00:00.000000Z"
                    }
                ]
            }
        }
    ]
}
```

### 6. Get Comprehensive SEO View by Business

**Endpoint:** `GET /api/seo-view/business/{businessId}`

**Description:** Retrieves comprehensive SEO data for a specific business (single-view).

**Mandatory `business_details` block:** always includes business fields, `creator`, `auth_persons` (array), `business_service` (object or `null`), and `lead_qualification` (object or `null`). `comments` and `appointments` are returned in addition. When `business_service` is present, it includes `primary_service` and `interested_services_list`.

**Response Format:** Same structure as comprehensive view, but returns a single record for one business:

```json
{
    "success": true,
    "message": "Comprehensive SEO view for business retrieved successfully",
    "data": {
        "seo_details": { ... },
        "business_details": {
            "id": 1,
            "name": "Example Business",
            "priority": "high",
            "auth_persons": [...],
            "business_service": {
                "interested_services_list": [{ "id": 1, "name": "SEO" }],
                "primary_service": { "id": 1, "name": "SEO" }
            },
            "lead_qualification": {
                "temperature": "hot",
                "budget": true,
                "authority": false,
                "need": true,
                "timeline": false
            },
            "comments": [...],
            "appointments": [...]
        }
    }
}
```

### 7. Get SEO Filter Options

**Endpoint:** `GET /api/seo/filter-options`

**Description:** Retrieves available filter options for SEO data

**Response Format:**
```json
{
    "success": true,
    "message": "SEO filter options retrieved successfully",
    "data": {
        "date_filters": [
            "today",
            "yesterday",
            "this_week",
            "last_week",
            "this_month",
            "last_month",
            "this_year",
            "last_year",
            "custom"
        ],
        "date_columns": {
            "created_at": "SEO Created Date",
            "updated_at": "SEO Updated Date",
            "audited_date": "Audit Date"
        },
        "status_options": [
            "Pending",
            "Audit Completed", 
            "Not Applicable",
            "In Progress",
            "On Hold",
            "Cancelled"
        ],
        "assigned_user_options": [
            {
                "id": 1,
                "name": "Test User",
                "email": "test@example.com"
            },
            {
                "id": 2,
                "name": "John Doe",
                "email": "john@example.com"
            }
        ],
        "business_options": [
            {
                "id": 1,
                "name": "Example Business"
            },
            {
                "id": 2,
                "name": "Test Company"
            }
        ],
        "auditor_options": [
            "Test User",
            "John Doe",
            "Jane Smith"
        ]
    }
}
```

### 8. Filter SEO Records

**Endpoint:** `POST /api/seo/seo-filter`

**Description:** Filters SEO records based on provided criteria

**Request Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| status | String | Filter by SEO status |
| statuses | Array | Filter by multiple SEO statuses |
| assigned_user_id | Integer | Filter by assigned user ID |
| business_id | Integer | Filter by business ID |
| business_name | String | Search by business name (contains) |
| auditor | String | Search by auditor name (contains) |
| audit_date_from | Date | Filter by audit date (from) |
| audit_date_to | Date | Filter by audit date (to) |
| audited_website | String | Search by audited website (contains) |
| date_column | String | Date column to filter on (created_at, updated_at, audited_date) |
| per_page | Integer | Number of records per page |

**Response Format:**
```json
{
    "success": true,
    "message": "SEO records retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            // SEO records with applied filters
        ],
        "first_page_url": "http://example.com/api/seo/seo-filter?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://example.com/api/seo/seo-filter?page=1",
        "next_page_url": null,
        "path": "http://example.com/api/seo/seo-filter",
        "per_page": 15,
        "to": 1,
        "total": 42
    }
}
```

## Data Model

### SeoDetail Fields

| Field | Type | Description |
|-------|------|-------------|
| id | Integer | Primary key |
| followup_business_id | Integer | Foreign key to followup_businesses table |
| status | String | Current status (Pending, Audit Completed, Not Applicable) |
| reason | String | Reason for status |
| audited_website | String | Website that was audited |
| audited_date | Date | Date of audit |
| auditor | String | Name of auditor |
| assigned_user | Integer | Foreign key to users table |
| created_at | Timestamp | Creation timestamp |
| updated_at | Timestamp | Last update timestamp |

### Business Fields

| Field | Type | Description |
|-------|------|-------------|
| id | Integer | Primary key |
| name | String | Business name |
| category | String | Business category |
| type | String | Business type |
| website | String | Business website |
| phone | String | Business phone |
| email | String | Business email |
| auth_persons | Array | Array of authorized persons |

### Auth Person Fields

| Field | Type | Description |
|-------|------|-------------|
| id | Integer | Primary key |
| title | String | Person's title |
| firstname | String | First name |
| middlename | String | Middle name |
| lastname | String | Last name |
| job_title | String | Job title |
| primaryemail | String | Primary email |
| primarymobile | String | Primary mobile |
| is_primary | Boolean | Whether primary contact |

### SeoQuestion Fields

| Field | Type | Description |
|-------|------|-------------|
| id | Integer | Primary key |
| name | String | Question text |
| answer_type | String | Answer type: `text`, `textarea`, `number`, `date`, `dropdown` |
| dropdown_options | JSON | Array of options when answer_type is `dropdown` |
| is_active | Boolean | Whether question is active |
| created_by | Integer | Foreign key to users table |
| created_at | Timestamp | Creation timestamp |
| updated_at | Timestamp | Last update timestamp |

### SeoQuestionAnswer Fields

| Field | Type | Description |
|-------|------|-------------|
| id | Integer | Primary key |
| seo_details_id | Integer | Foreign key to seo_details table |
| seo_question_id | Integer | Foreign key to seo_questions table |
| answer | Text | Answer text |
| comments | Text | Optional comments on the answer |
| created_at | Timestamp | Creation timestamp |
| updated_at | Timestamp | Last update timestamp |

## Error Responses

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Unauthenticated",
    "data": null
}
```

### 403 Forbidden
```json
{
    "success": false,
    "message": "You do not have permission to access this resource",
    "data": null
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Internal server error",
    "data": null
}
```

## Usage Examples

### Using cURL

```bash
# Get pending SEO audits
curl -X GET "https://your-domain.com/api/seo-audit/audit-pending" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Get completed SEO audits
curl -X GET "https://your-domain.com/api/seo-audit/audit-completed" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Get not applicable SEO audits
curl -X GET "https://your-domain.com/api/seo-audit/not-applicable" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Get all SEO audits
curl -X GET "https://your-domain.com/api/seo-audit/all" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Get comprehensive SEO view
curl -X GET "https://your-domain.com/api/seo-view/comprehensive" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Get comprehensive SEO view for specific business
curl -X GET "https://your-domain.com/api/seo-view/business/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Using JavaScript/Fetch

```javascript
const token = 'YOUR_JWT_TOKEN';

// Get pending SEO audits
fetch('https://your-domain.com/api/seo-audit/audit-pending', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Get all SEO audits
fetch('https://your-domain.com/api/seo-audit/all', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Get comprehensive SEO view
fetch('https://your-domain.com/api/seo-view/comprehensive', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Get comprehensive SEO view for specific business
fetch('https://your-domain.com/api/seo-view/business/1', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## Integration Notes

### Automatic Assignment

SEO records are automatically assigned to Digital Marketing department users when appointments are created using round-robin with workload balancing.

### Status Workflow

1. **Pending** - Initial status when SEO record is created
2. **Audit Completed** - When SEO audit is finished
3. **Not Applicable** - When SEO audit is not applicable

### Data Relationships

- Each SEO record is linked to a followup business
- Each SEO record has an assigned user from Digital Marketing department
- Each SEO record can have multiple question answers
- Each business can have multiple authorized persons

## Performance Considerations

- All endpoints are optimized with proper database relationships
- Role-based filtering is applied at database level
- Results are ordered by creation/update date
- Pagination can be implemented if needed for large datasets

## Security Features

- JWT-based authentication
- Role-based access control
- Department-based filtering
- Permission-based endpoint protection
- SQL injection protection through ORM

## Testing

Use the provided Artisan commands for testing:

```bash
# Test SEO assignment functionality
php artisan seo:test-assignment

# Test SEO API endpoints
php artisan seo:test-api-endpoints

# Test round-robin assignment
php artisan seo:test-roundrobin
```

## Troubleshooting

### Common Issues

1. **401 Unauthorized**: Check JWT token validity
2. **403 Forbidden**: Verify user has required permissions and role
3. **Empty Results**: Check if user has access to requested data based on role
4. **Slow Response**: Check database indexes and relationships

### Debug Information

Enable debug mode to get detailed error information:
```bash
php artisan config:cache --env=local
```

## Version History

- **v1.0.0** - Initial implementation with 4 endpoints and role-based access control
- **v1.0.1** - Added comprehensive testing and documentation

## Support

For technical support or questions about the SEO API, please contact the development team or refer to the system documentation.
