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
  "business_name": "ABC Corporation",
  "category": "Technology",
  "type": "Software",
  "website": "https://abccorp.com",
  "phone": "+1234567890",
  "email": "contact@abccorp.com",
  "auth_persons": [
    {
      "title": "Mr.",
      "firstname": "John",
      "lastname": "Doe",
      "is_primary": true,
      "designation": "CEO",
      "primaryemail": "john.doe@abccorp.com"
    }
  ],
  "comments": [
    {
      "comment": "Initial contact made",
      "old_status": "New",
      "new_status": "Contacted"
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

---

## 2. Get All Leads
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

## 3. Get My Leads
**GET** `/my-leads`

Retrieves only leads created by the logged-in user.

**Query Parameters:**
- `per_page`: Number of results per page (default: 15)
- `category`: Filter by category
- `type`: Filter by type
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

## 3. Get All Business Names
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

## 4. Get Lead Details
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
    "category": "Technology",
    "type": "Enterprise",
    "website": "https://example.com",
    "phone": "+1234567890",
    "email": "info@example.com",
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
        "designation": "Chief Executive Officer",
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

## 6. Update Lead
**PUT** `/{id}`

**Request Body:**
```json
{
  "business_name": "Updated Name",
  "category": "Updated Category",
  "auth_person_ids": [1, 2]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lead updated successfully",
  "data": {...}
}
```

---

## 7. Delete Lead
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
