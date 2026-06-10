# Leads API Documentation

## Base URL
`/api/leads`

---

## 1. Create Lead
**POST** `/`

Creates a new lead with business, auth persons, and comments.

**Request Body:**
```json
{
  "business_name": "Example Company Ltd",
  "trading_name": "Example Trading",
  "company_registration_number": "12345678",
  "address": "123 Main Street, London, UK",
  "company_size": "11-50",
  "category": "Technology",
  "sub_category": "SaaS",
  "type": "Software",
  "source_name": "Website",
  "sub_source": "Google Ads",
  "annual_revenue": 500000.00,
  "number_of_locations": 3,
  "website": "https://example.com",
  "auth_persons": [
    {
      "title": "Mr",
      "firstname": "John",
      "middlename": "Robert",
      "lastname": "Doe",
      "is_primary": true,
      "job_title": "CEO",
      "seniority_level": "Executive",
      "extension": "101",
      "linkedin_profile": "https://linkedin.com/in/johndoe",
      "facebook_profile": "https://facebook.com/johndoe",
      "preferred_contact_method": "email",
      "preferred_contact_time": "Weekdays 9am-5pm",
      "gender": "male",
      "dob": "1980-01-15",
      "primaryphone": "+1234567890",
      "altphone": "+0987654321",
      "primarymobile": "+1122334455",
      "altmobile": "+5544332211",
      "primaryemail": "john.doe@example.com",
      "altemail": "john.alternate@example.com"
    },
    {
      "title": "Mr",
      "firstname": "John",
      "middlename": "Robert",
      "lastname": "Doe",
      "is_primary": true,
      "job_title": "CEO",
      "seniority_level": "Executive",
      "extension": "101",
      "linkedin_profile": "https://linkedin.com/in/johndoe",
      "facebook_profile": "https://facebook.com/johndoe",
      "preferred_contact_method": "email",
      "preferred_contact_time": "Weekdays 9am-5pm",
      "gender": "male",
      "dob": "1980-01-15",
      "primaryphone": "+1234567890",
      "altphone": "+0987654321",
      "primarymobile": "+1122334455",
      "altmobile": "+5544332211",
      "primaryemail": "john.doe@example.com",
      "altemail": "john.alternate@example.com"
    }
  ],
  "comments": [
    {
      "comment": "Initial contact made via website form",
      "old_status": "new",
      "new_status": "contacted"
    },
    {
      "comment": "Follow-up scheduled for next week",
      "followup_business_id": 123
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lead created successfully",
  "data": {...}
}
```

**Business fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `business_name` | string | Yes | Legal / registered business name (max 255) |
| `trading_name` | string | No | Trading name (max 255) |
| `company_registration_number` | string | No | Company registration number (max 100) |
| `address` | string | No | Business address (max 1000) |
| `company_size` | string | No | Company size, e.g. `1-10`, `11-50` (max 100) |
| `category` | string | No | Business category (max 255) |
| `sub_category` | string | No | Business sub-category (max 255) |
| `type` | string | No | Business type (max 255) |
| `source_name` | string | No | Lead source (max 50) |
| `sub_source` | string | No | Lead sub-source (max 50) |
| `annual_revenue` | number | No | Annual revenue (min 0) |
| `number_of_locations` | integer | No | Number of locations (min 0) |
| `website` | url | No | Business website |

Contact phone and email are stored on **auth persons** (`primaryphone`, `primarymobile`, `primaryemail`, etc.), not on the business record.

**Auth person fields (within `auth_persons` array):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | No | Salutation (max 50) |
| `firstname` | string | Yes | First name |
| `middlename` | string | No | Middle name |
| `lastname` | string | Yes | Last name |
| `is_primary` | boolean | No | Primary contact flag |
| `job_title` | string | No | Job title (max 255) |
| `seniority_level` | string | No | Seniority level (max 100) |
| `extension` | string | No | Phone extension (max 50) |
| `linkedin_profile` | url | No | LinkedIn profile URL |
| `facebook_profile` | url | No | Facebook profile URL |
| `preferred_contact_method` | string | No | e.g. email, phone, mobile (max 100) |
| `preferred_contact_time` | string | No | Preferred contact time (varchar) |
| `gender` | string | No | `male`, `female`, or `other` |
| `dob` | date | No | Date of birth |
| `primaryphone` | string | No | Primary phone |
| `altphone` | string | No | Alternate phone |
| `primarymobile` | string | No | Primary mobile |
| `altmobile` | string | No | Alternate mobile |
| `primaryemail` | email | Yes | Primary email |
| `altemail` | email | No | Alternate email |

All auth person fields except `firstname`, `lastname`, and `primaryemail` are optional (nullable).

All business fields except `business_name` are optional (nullable).

---

## 2. Leads Filter APIs

Flexible filtering (same pattern as Appointments and Follow-Up).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/filter-options` | Get filter dropdown options |
| POST | `/leads-filter` | Filter leads with POST body |

**Full documentation:** [LEADS_FILTER_API_DOCUMENTATION.md](./LEADS_FILTER_API_DOCUMENTATION.md)

**Quick example:**
```json
POST /api/leads/leads-filter
{
  "scope": "all",
  "date_filter": "this_month",
  "category": "Technology Services",
  "search": "ABC",
  "per_page": 15
}
```

---

## 3. Get All Leads
**GET** `/all-leads`

Retrieves all leads with role-based hierarchy access.

**Hierarchy:**
- **Admin:** Can see all leads
- **Manager (Lead Generation dept + has team):** Can see team members' leads + own
- **Executive (Lead Generation dept):** Can see only own leads

**Query Parameters:**
- `per_page`: Number of results per page (default: 15)
- `category`: Filter by category
- `type`: Filter by type
- `source_name`: Filter by source name
- `sub_source`: Filter by sub-source
- `name`: Search by business name

**Response:**
```json
{
  "success": true,
  "message": "All leads retrieved successfully",
  "data": {
    "leads": {...},
    "user_role": {...},
    "access_level": "admin|manager|executive"
  }
}
```

---

## 4. Get My Leads
**GET** `/my-leads`

Retrieves only leads created by the logged-in user.

**Query Parameters:**
- `per_page`: Number of results per page (default: 15)
- `category`: Filter by category
- `type`: Filter by type
- `source_name`: Filter by source name
- `sub_source`: Filter by sub-source
- `name`: Search by business name

**Response:**
```json
{
  "success": true,
  "message": "My leads retrieved successfully",
  "data": {
    "leads": {...},
    "created_by": 1,
    "user_name": "John Doe"
  }
}
```

---

## 5. Get All Business Names
**GET** `/business-names`

Retrieves a list of all business names and IDs from the followup business table.

**Authentication:** None (Public endpoint)

**Response:**
```json
{
  "success": true,
  "message": "Business names retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "ABC Corporation"
    },
    {
      "id": 2,
      "name": "XYZ Industries"
    },
    {
      "id": 3,
      "name": "Test Business"
    }
  ]
}
```

---

## 6. Check Duplicate Lead Data
**POST** `/check-duplicate`

Checks for duplicate authorized person contact information before creating a new lead.

**Authentication:** Required (JWT)
**Permission:** `Leads,read`

**Request Body:**
```json
{
  "auth_person_phone": "+1234567890",
  "auth_person_mobile": "+1122334455",
  "auth_person_email": "john.doe@example.com"
}
```

**Request Parameters (all optional):**
- `auth_person_phone` (string): Authorized person phone number (checks both primaryphone and altphone fields)
- `auth_person_mobile` (string): Authorized person mobile number (checks both primarymobile and altmobile fields)
- `auth_person_email` (email): Authorized person email address (checks both primaryemail and altemail fields)

**Response (No Duplicates Found):**
```json
{
  "success": true,
  "message": "No duplicates found",
  "data": {
    "has_duplicates": false,
    "duplicates": {}
  }
}
```

**Response (Duplicates Found):**
```json
{
  "success": true,
  "message": "Duplicates found",
  "data": {
    "has_duplicates": true,
    "duplicates": {
      "auth_person_email": {
        "exists": true,
        "lead_id": 456,
        "business_name": "XYZ Industries",
        "auth_person_name": "John Doe"
      },
      "auth_person_phone": {
        "exists": true,
        "lead_id": 123,
        "business_name": "ABC Corporation",
        "auth_person_name": "Jane Smith"
      }
    }
  }
}
```

**Response Fields:**
- `has_duplicates` (boolean): Indicates whether any duplicates were found
- `duplicates` (object): Contains details of each duplicate found (auth person phone, mobile, or email)
  - Includes `exists`, `lead_id`, `business_name`, and `auth_person_name` where applicable

**Usage Example:**
This endpoint should be called before creating a new lead to prevent duplicate entries. If duplicates are found, the frontend can display appropriate warnings to the user.

---

## 7. Get Lead Details
**GET** `/{id}`

Retrieves detailed information about a specific lead including all related data:
- Business details
- Authorized persons
- Comments (with creators)
- Follow-up details
- Emails (with creators)
- Appointments (with time slots, creators, quality assessments, and consultations)

**Response:**
```json
{
  "success": true,
  "message": "Lead retrieved successfully",
  "data": {
    "id": 1,
    "name": "ABC Corporation",
    "trading_name": "ABC Trading",
    "company_registration_number": "12345678",
    "address": "123 Main Street, London, UK",
    "company_size": "51-200",
    "category": "Technology",
    "sub_category": "SaaS",
    "type": "Enterprise",
    "source_name": "Website",
    "sub_source": "Google Ads",
    "annual_revenue": "750000.00",
    "number_of_locations": 5,
    "website": "https://example.com",
    "created_by": 1,
    "created_at": "2026-04-28T10:00:00.000000Z",
    "updated_at": "2026-04-28T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    },
    "authPersons": [
      {
        "id": 1,
        "title": "Mr.",
        "firstname": "John",
        "middlename": "William",
        "lastname": "Doe",
        "is_primary": true,
        "job_title": "Chief Executive Officer",
        "seniority_level": "Executive",
        "extension": "101",
        "linkedin_profile": "https://linkedin.com/in/johndoe",
        "facebook_profile": null,
        "preferred_contact_method": "email",
        "preferred_contact_time": "Weekdays 9am-5pm",
        "gender": "male",
        "dob": "1980-05-15",
        "primaryphone": "+1-555-0101",
        "altphone": "+1-555-0102",
        "primarymobile": "+1-555-0201",
        "altmobile": "+1-555-0202",
        "primaryemail": "john.doe@example.com",
        "altemail": "john.alternate@example.com",
        "created_by": 1,
        "created_at": "2026-04-28T10:00:00.000000Z",
        "updated_at": "2026-04-28T10:00:00.000000Z",
        "pivot": {
          "followup_business_id": 1,
          "followup_auth_person_id": 1,
          "created_at": "2026-04-28T10:00:00.000000Z",
          "updated_at": "2026-04-28T10:00:00.000000Z"
        }
      }
    ],
    "comments": [
      {
        "id": 1,
        "followup_business_id": 1,
        "comment": "Initial contact made",
        "old_status": null,
        "new_status": "Contacted",
        "created_by": 1,
        "created_at": "2026-04-28T10:00:00.000000Z",
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        }
      }
    ],
    "followupDetails": [
      {
        "id": 1,
        "followup_business_id": 1,
        "source": "LinkedIn",
        "status": "In Progress",
        "date": "2026-04-28",
        "time": "14:30",
        "created_by": 1,
        "created_at": "2026-04-28T10:00:00.000000Z"
      }
    ],
    "emails": [
      {
        "id": 1,
        "followup_business_id": 1,
        "to": ["contact@example.com", "info@example.com"],
        "cc": ["manager@example.com"],
        "bcc": null,
        "type": "Follow-up",
        "created_by": 1,
        "created_at": "2026-04-28T10:00:00.000000Z",
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        }
      }
    ],
    "appointments": [
      {
        "id": "FRMID00000001",
        "followup_business_id": 1,
        "source": "Cold Call",
        "status": "Booked",
        "date": "2026-05-01",
        "time_slot_id": 1,
        "current_status": "Booked",
        "created_by": 1,
        "created_at": "2026-04-28T10:00:00.000000Z",
        "timeSlot": {
          "id": 1,
          "name": "Morning Slot",
          "start_time": "09:00:00",
          "end_time": "10:00:00",
          "duration_minutes": 60
        },
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        },
        "quality": {
          "id": 1,
          "appointment_id": "FRMID00000001",
          "quality_score": 85,
          "feedback": "Good quality",
          "created_at": "2026-04-28T10:00:00.000000Z"
        },
        "consultations": [
          {
            "id": 1,
            "appointment_id": "FRMID00000001",
            "status": "Completed",
            "is_customer_available": true,
            "meeting_date": "2026-05-01",
            "meeting_slot": 1,
            "assigned_user": 2,
            "created_at": "2026-04-28T10:00:00.000000Z",
            "meetingSlot": {
              "id": 1,
              "start_time": "09:00:00",
              "end_time": "10:00:00"
            },
            "assignedUser": {
              "id": 2,
              "first_name": "Sales",
              "last_name": "Executive",
              "username": "sales_exec"
            }
          }
        ]
      }
    ]
  }
}
```

---

## 8. Update Lead
**PUT** `/{id}`

**Request Body (all fields optional except when provided):**
```json
{
  "business_name": "Updated Name",
  "trading_name": "Updated Trading Name",
  "company_registration_number": "87654321",
  "address": "456 New Road, Manchester, UK",
  "company_size": "201-500",
  "category": "Updated Category",
  "sub_category": "Enterprise Software",
  "type": "Enterprise",
  "source_name": "Referral",
  "sub_source": "Partner Network",
  "annual_revenue": 1200000.00,
  "number_of_locations": 8,
  "website": "https://updated-example.com",
  "auth_person_ids": [1, 2]
}
```

Only include the fields you want to update. All business detail fields are nullable.

**Response:**
```json
{
  "success": true,
  "message": "Lead updated successfully",
  "data": {...}
}
```

---

## 9. Delete Lead
**DELETE** `/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Lead deleted successfully"
}
```

---

## Permissions
- **Read:** `Leads,read`
- **Create:** `Leads,create`
- **Update:** `Leads,update`
- **Delete:** `Leads,delete`
