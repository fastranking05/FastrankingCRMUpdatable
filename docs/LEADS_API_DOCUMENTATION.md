# Leads API Documentation

## Base URL
`/api/leads`

---

## 1. Create Lead
**POST** `/`

Creates a new lead with business, auth persons, comments, and optional service and qualification profiles.

> **Payload schema:** Create (Section 1) and Update (Section 8) accept the **same fields and nested objects**. On update, send only the sections you want to change; validation rules are identical except `business_name` and `auth_persons` are optional on update.

**Request Body:**
```json
{
  "business_name": "Example Company Ltd",
  "trading_name": "Example Trading",
  "company_registration_number": "12345678",
  "address_line1": "123 Main Street",
  "city": "London",
  "postcode": "SW1A 1AA",
  "country": "United Kingdom",
  "company_size": "11-50",
  "company_type": "Private Limited",
  "category": "Technology",
  "sub_category": "SaaS",
  "type": "Software",
  "source_name": "Website",
  "sub_source": "Google Ads",
  "priority": "high",
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
  ],
  "business_service": {
    "interested_services": [1, 2, 4],
    "primary_service_id": 1,
    "current_agency": "ABC Marketing Ltd",
    "current_monthly_spend": "2500",
    "planned_monthly_budget": "5000",
    "existing_website_platform": "WordPress",
    "previous_experience": 1,
    "previous_services": [2, 4],
    "challenges": ["Low traffic", "Poor conversion rate"],
    "expectation": ["Increase leads", "Improve ROI"]
  },
  "lead_qualification": {
    "temperature": "hot",
    "budget": true,
    "authority": false,
    "need": true,
    "timeline": false
  }
}
```

**Response:** Same structure as **Get Lead Details** (Section 7), including the mandatory business profile block.

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
| `address_line1` | string | No | Address line 1 (max 50) |
| `city` | string | No | City (max 50) |
| `postcode` | string | No | Postcode (max 50) |
| `country` | string | No | Country (max 50) |
| `company_size` | string | No | Company size, e.g. `1-10`, `11-50` (max 100) |
| `company_type` | string | No | Company legal type (max 50) |
| `category` | string | No | Business category (max 255) |
| `sub_category` | string | No | Business sub-category (max 255) |
| `type` | string | No | Business type (max 255) |
| `source_name` | string | No | Lead source (max 50) |
| `sub_source` | string | No | Lead sub-source (max 50) |
| `priority` | string | No | Lead priority: `low`, `medium`, `high`, or `urgent` |
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
| `id` | integer | Update only | Existing auth person ID (omit on create; include on update to modify existing contact) |

All auth person fields except `firstname`, `lastname`, and `primaryemail` are optional (nullable).

**Comment fields (within `comments` array):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `comment` | string | Yes | Comment text |
| `old_status` | string | No | Previous status (max 255) |
| `new_status` | string | No | New status (max 255) |
| `followup_business_id` | integer | No | Defaults to the lead/business ID if omitted |

All business fields except `business_name` are optional (nullable).

**Business service fields (within `business_service` object):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `interested_services` | array of integers | No | Service IDs the lead is interested in (must exist in `services` table) |
| `primary_service_id` | integer | No | Primary service ID (must exist in `services` table) |
| `current_agency` | string | No | Current marketing/SEO agency (max 255) |
| `current_monthly_spend` | string | No | Current monthly spend (max 30) |
| `planned_monthly_budget` | string | No | Planned monthly budget (max 30) |
| `existing_website_platform` | string | No | Existing website platform, e.g. WordPress, Shopify (max 255) |
| `previous_experience` | integer | No | Whether the lead has previous experience: `0` (no) or `1` (yes) |
| `previous_services` | array of integers | No | Service IDs for previous services used (must exist in `services` table); stored comma-separated |
| `challenges` | array of strings | No | Business challenges; stored comma-separated (each item max 255) |
| `expectation` | array of strings | No | Business expectations; stored comma-separated (each item max 255) |

The entire `business_service` object is optional. If omitted or all fields are empty, no `business_services` row is created. On create, `followup_business_id` is set automatically from the new lead.

**Lead qualification fields (within `lead_qualification` object):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `temperature` | string | No | Lead temperature, e.g. `hot`, `warm`, `cold` (max 255) |
| `budget` | boolean | No | BANT: budget confirmed (`true`/`false` or `1`/`0`) |
| `authority` | boolean | No | BANT: decision-maker identified (`true`/`false` or `1`/`0`) |
| `need` | boolean | No | BANT: business need confirmed (`true`/`false` or `1`/`0`) |
| `timeline` | boolean | No | BANT: timeline confirmed (`true`/`false` or `1`/`0`) |

The entire `lead_qualification` object is optional. If omitted or all fields are empty, no `lead_qualifications` row is created. On create, `followup_business_id` is set automatically from the new lead. Omitted boolean fields default to `false` when a qualification row is created.

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
  "priority": "high",
  "search": "ABC",
  "per_page": 15
}
```

`GET /filter-options` includes `priority_options`: `["low", "medium", "high", "urgent"]`

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
- `priority`: Filter by priority (`low`, `medium`, `high`, `urgent`)
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
- `priority`: Filter by priority (`low`, `medium`, `high`, `urgent`)
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

Checks for duplicate lead data before creating or updating a lead. At least **one** field must be sent in the request.

**Authentication:** Required (JWT)  
**Permission:** `Leads,read`

**Request Body:**
```json
{
  "business_name": "Example Company Ltd",
  "website": "https://example.com",
  "phone": "+1234567890",
  "mobile": "+1122334455",
  "email": "john.doe@example.com"
}
```

**Request Parameters (send at least one):**

| Field | Type | Checks against |
|-------|------|----------------|
| `business_name` | string | `followup_businesses.name` and `trading_name` (case-insensitive, trimmed) |
| `website` | url | `followup_businesses.website` (normalized: lowercase, trailing `/` ignored) |
| `phone` | string | Auth person `primaryphone` and `altphone` |
| `mobile` | string | Auth person `primarymobile` and `altmobile` |
| `email` | email | Auth person `primaryemail` and `altemail` (case-insensitive) |

**Legacy aliases (still supported):** `auth_person_phone` → `phone`, `auth_person_mobile` → `mobile`, `auth_person_email` → `email`

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
      "business_name": {
        "exists": true,
        "lead_id": 123,
        "business_name": "ABC Corporation"
      },
      "website": {
        "exists": true,
        "lead_id": 123,
        "business_name": "ABC Corporation",
        "website": "https://example.com"
      },
      "email": {
        "exists": true,
        "lead_id": 456,
        "business_name": "XYZ Industries",
        "auth_person_name": "John Doe"
      },
      "phone": {
        "exists": true,
        "lead_id": 123,
        "business_name": "ABC Corporation",
        "auth_person_name": "Jane Smith"
      },
      "mobile": {
        "exists": true,
        "lead_id": 789,
        "business_name": "Another Co",
        "auth_person_name": "Alex Lee"
      }
    }
  }
}
```

**Duplicate result fields:**

| Key in `duplicates` | Always includes | Also includes when relevant |
|---------------------|-----------------|-----------------------------|
| `business_name` | `exists`, `lead_id`, `business_name` | — |
| `website` | `exists`, `lead_id`, `business_name` | `website` |
| `phone` | `exists`, `lead_id`, `business_name` | `auth_person_name` |
| `mobile` | `exists`, `lead_id`, `business_name` | `auth_person_name` |
| `email` | `exists`, `lead_id`, `business_name` | `auth_person_name` |

**Validation errors (422):**
- No fields provided
- Invalid `email` or `website` format

**Usage:** Call before lead create/update to warn users about existing business names, websites, or contact details already linked to another lead.

---

## 7. Get Lead Details
**GET** `/{id}`

Retrieves complete lead information including all related data:
- Business details (including `priority`)
- Authorized persons (full profile)
- Business service profile (with `primary_service` and `interested_services_list`)
- Lead qualification profile (BANT + temperature)
- Comments (with creators)
- Follow-up details (with creators)
- Emails (with creators)
- Appointments (with time slots, creators, quality assessments, and consultations)
- Deals (with auth person and creator)
- SEO details (with assigned user)

**Mandatory business profile (always in single-view response):**

These keys are **always present** at the root of `data` (same structure used by Appointment, Follow-up, Quality, Consultation, and SEO single views — nested as `followup_business` or `business_details` where applicable):

| Key | Always present | Notes |
|-----|----------------|-------|
| Business scalar fields | Yes | `id`, `name`, `trading_name`, `priority`, `source_name`, etc. |
| `creator` | Yes | `null` if missing |
| `auth_persons` | Yes | Array (empty `[]` if none) |
| `business_service` | Yes | Object when saved; otherwise `null` |
| `lead_qualification` | Yes | Object when saved; otherwise `null` |

Module-specific data (`comments`, `appointments`, `deals`, etc.) is returned **in addition** to this mandatory block.

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
    "address_line1": "123 Main Street",
    "city": "London",
    "postcode": "SW1A 1AA",
    "country": "United Kingdom",
    "company_size": "51-200",
    "company_type": "Private Limited",
    "category": "Technology",
    "sub_category": "SaaS",
    "type": "Enterprise",
    "source_name": "Website",
    "sub_source": "Google Ads",
    "priority": "high",
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
    "auth_persons": [
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
    "business_service": {
      "id": 1,
      "followup_business_id": 1,
      "interested_services": "1,2,4",
      "interested_service_ids": [1, 2, 4],
      "primary_service_id": 1,
      "current_agency": "ABC Marketing Ltd",
      "current_monthly_spend": "2500",
      "planned_monthly_budget": "5000",
      "existing_website_platform": "WordPress",
      "previous_experience": 1,
      "previous_services": "2,4",
      "previous_service_ids": [2, 4],
      "challenges": "Low traffic,Poor conversion rate",
      "challenges_list": ["Low traffic", "Poor conversion rate"],
      "expectation": "Increase leads,Improve ROI",
      "expectation_list": ["Increase leads", "Improve ROI"],
      "created_at": "2026-04-28T10:00:00.000000Z",
      "updated_at": "2026-04-28T10:00:00.000000Z",
      "primary_service": {
        "id": 1,
        "name": "SEO"
      },
      "interested_services_list": [
        { "id": 1, "name": "SEO" },
        { "id": 2, "name": "PPC" },
        { "id": 4, "name": "Web Design" }
      ],
      "previous_services_list": [
        { "id": 2, "name": "PPC" },
        { "id": 4, "name": "Web Design" }
      ]
    },
    "lead_qualification": {
      "id": 1,
      "followup_business_id": 1,
      "temperature": "hot",
      "budget": true,
      "authority": false,
      "need": true,
      "timeline": false,
      "created_at": "2026-04-28T10:00:00.000000Z",
      "updated_at": "2026-04-28T10:00:00.000000Z"
    },
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
    ],
    "deals": [
      {
        "id": "FRDID00000001",
        "followup_business_id": 1,
        "name": "SEO Retainer",
        "deal_stage": "Proposal",
        "probability": "75.00",
        "priority": "high",
        "created_by": 1,
        "created_at": "2026-04-28T10:00:00.000000Z",
        "auth_person": {
          "id": 1,
          "title": "Mr.",
          "firstname": "John",
          "lastname": "Doe",
          "primaryemail": "john.doe@example.com",
          "primarymobile": "+1-555-0201"
        },
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        }
      }
    ],
    "seo_details": [
      {
        "id": 1,
        "followup_business_id": 1,
        "status": "Completed",
        "audited_website": "https://example.com",
        "audited_date": "2026-04-28",
        "assigned_user": 2,
        "created_at": "2026-04-28T10:00:00.000000Z",
        "assignedUser": {
          "id": 2,
          "first_name": "SEO",
          "last_name": "Analyst",
          "username": "seo_analyst"
        }
      }
    ]
  }
}
```

---

## 8. Update Lead
**PUT** `/{id}`

Updates an existing lead using the **exact same payload fields as Create Lead** (Section 1). Send only the sections you want to change.

**Field reference:** Use the same tables as Section 1 for:
- Business fields
- `auth_persons` (add optional `id` when updating an existing contact)
- `comments`
- `business_service`
- `lead_qualification`

**Request Body** (same shape as create; example shows full payload):
```json
{
  "business_name": "Updated Name",
  "trading_name": "Updated Trading Name",
  "company_registration_number": "87654321",
  "address_line1": "456 New Road",
  "city": "Manchester",
  "postcode": "M1 1AE",
  "country": "United Kingdom",
  "company_size": "201-500",
  "company_type": "Public Limited",
  "category": "Updated Category",
  "sub_category": "Enterprise Software",
  "type": "Enterprise",
  "source_name": "Referral",
  "sub_source": "Partner Network",
  "priority": "urgent",
  "annual_revenue": 1200000.00,
  "number_of_locations": 8,
  "website": "https://updated-example.com",
  "auth_persons": [
    {
      "id": 1,
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
      "title": "Ms",
      "firstname": "Jane",
      "middlename": "Anne",
      "lastname": "Smith",
      "is_primary": false,
      "job_title": "Operations Manager",
      "seniority_level": "Manager",
      "extension": "102",
      "linkedin_profile": "https://linkedin.com/in/janesmith",
      "facebook_profile": null,
      "preferred_contact_method": "mobile",
      "preferred_contact_time": "Weekdays 10am-4pm",
      "gender": "female",
      "dob": "1985-03-20",
      "primaryphone": "+1234567891",
      "altphone": null,
      "primarymobile": "+1122334456",
      "altmobile": null,
      "primaryemail": "jane.smith@example.com",
      "altemail": null
    }
  ],
  "comments": [
    {
      "comment": "Updated lead details after discovery call",
      "old_status": "contacted",
      "new_status": "qualified"
    }
  ],
  "business_service": {
    "interested_services": [1, 3],
    "primary_service_id": 3,
    "current_agency": "New Agency Ltd",
    "current_monthly_spend": "3000",
    "planned_monthly_budget": "6000",
    "existing_website_platform": "Shopify",
    "previous_experience": 0,
    "previous_services": [1, 2],
    "challenges": ["Brand awareness"],
    "expectation": ["Monthly reporting", "Dedicated account manager"]
  },
  "lead_qualification": {
    "temperature": "warm",
    "budget": true,
    "authority": true,
    "need": true,
    "timeline": false
  }
}
```

**Create vs update (same fields, different rules):**

| Field / section | Create | Update |
|-----------------|--------|--------|
| `business_name` | Required | Optional (`sometimes` — only validated when sent) |
| All other business fields | Optional | Optional — partial update |
| `auth_persons` | Required array | Optional array — omit to leave contacts unchanged |
| `auth_persons[].id` | Not used | Optional — include to update existing contact |
| `comments` | Optional — creates rows | Optional — appends new rows |
| `business_service` | Optional — creates row | Optional — upserts row |
| `lead_qualification` | Optional — creates row | Optional — upserts row |

**Update behaviour:**

| Section | On update |
|---------|-----------|
| Business fields | Only sent fields are changed |
| `auth_persons` | Upsert: `id` = update; no `id` = create. Contacts not in the array are detached |
| `comments` | Appends new rows (does not replace existing) |
| `business_service` | Upserts the single row for this lead |
| `lead_qualification` | Upserts the single row for this lead |

**Response:** Same structure as **Get Lead Details** (Section 7), including the mandatory business profile block (`auth_persons`, `business_service`, `lead_qualification`).

```json
{
  "success": true,
  "message": "Lead updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Name",
    "priority": "urgent",
    "auth_persons": [...],
    "business_service": {...},
    "lead_qualification": {...},
    "comments": [...],
    "followup_details": [...]
  }
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
