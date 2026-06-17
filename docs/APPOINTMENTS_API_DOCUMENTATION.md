# Appointments API Documentation

## Base URL
`/api/appointments`

## Endpoints

### 1. Get All Appointments
**GET** `/`

**Response:**
```json
{
  "success": true,
  "message": "Appointments retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 10
  }
}
```

### 2. Get Filter Options
**GET** `/filter-options`

**Response:**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully",
  "data": {...}
}
```

### 3. Get All Appointments (Role-based)
**GET** `/all-appointments`

**Response:**
```json
{
  "success": true,
  "message": "All appointments retrieved successfully",
  "data": [...]
}
```

### 4. Get My Appointments
**GET** `/my-appointments`

**Response:**
```json
{
  "success": true,
  "message": "My appointments retrieved successfully",
  "data": [...]
}
```

### 5. Get Today's Appointments
**GET** `/today-appointments`

**Response:**
```json
{
  "success": true,
  "message": "Today's appointments retrieved successfully",
  "data": [...]
}
```

### 6. Get Appointment Details
**GET** `/{id}`

Retrieves a single appointment with complete related data.

**Mandatory `followup_business` block (always present when business is linked):** all business fields, `creator`, `auth_persons` (array), `business_service` (object or `null`), `lead_qualification` (object or `null`). Module-specific fields (`time_slot`, `quality`, `consultations`, `comments`, etc.) are returned in addition.

Includes full business profile on `followup_business`:
- Business details (including `priority`)
- Authorized persons (full profile)
- Business service profile (`business_service` with `primary_service` and `interested_services_list`)
- Lead qualification profile (`lead_qualification` — temperature + BANT)
- Business comments (with creators)
- Time slot, creator, quality, and consultations

**Response:**
```json
{
  "success": true,
  "message": "Appointment retrieved successfully",
  "data": {
    "id": "FRMID00000001",
    "followup_business_id": 1,
    "date": "2026-04-16",
    "time_slot_id": 1,
    "current_status": "Booked",
    "source": "web",
    "status": "Appointment Booked",
    "followup_business": {
      "id": 1,
      "name": "ABC Corporation",
      "trading_name": "ABC Trading",
      "company_type": "Private Limited",
      "priority": "high",
      "source_name": "Website",
      "sub_source": "Google Ads",
      "auth_persons": [...],
      "business_service": {
        "interested_service_ids": [1, 2, 4],
        "interested_services_list": [
          { "id": 1, "name": "SEO" },
          { "id": 2, "name": "PPC" }
        ],
        "primary_service": { "id": 1, "name": "SEO" }
      },
      "lead_qualification": {
        "temperature": "hot",
        "budget": true,
        "authority": false,
        "need": true,
        "timeline": false
      },
      "comments": [...]
    },
    "time_slot": {...},
    "creator": {...},
    "quality": {...},
    "consultations": [...]
  }
}
```

### 7. Get Available Slots
**GET** `/slots/available?date=2026-04-16`

**Response:**
```json
{
  "success": true,
  "message": "Available slots retrieved successfully",
  "data": {
    "date": "2026-04-16",
    "available_slots": [...],
    "statistics": {...}
  }
}
```

### 8. Get Available Time Slots
**GET** `/available-slots?date=2026-04-16`

**Response:**
```json
{
  "success": true,
  "message": "Available time slots retrieved successfully",
  "data": [...]
}
```

### 9. Get Direct Appointment
**GET** `/direct/{appointmentId}`

Returns detailed information about a specific direct appointment. The nested `followup_business` object includes the same full business profile as **Get Appointment Details** (business fields, `auth_persons`, `business_service`, `lead_qualification`, and `comments`).

**Response:**
```json
{
  "success": true,
  "message": "Direct appointment retrieved successfully",
  "data": {
    "id": "FRMID00000001",
    "followup_business_id": 1,
    "date": "2026-04-16",
    "followup_business": {
      "id": 1,
      "name": "ABC Corporation",
      "priority": "high",
      "auth_persons": [...],
      "business_service": {
        "interested_services_list": [{ "id": 1, "name": "SEO" }],
        "primary_service": { "id": 1, "name": "SEO" }
      },
      "lead_qualification": {
        "temperature": "warm",
        "budget": false,
        "authority": true,
        "need": true,
        "timeline": false
      },
      "comments": [...]
    },
    "time_slot": {...},
    "creator": {...}
  }
}
```

### 10. Create Appointment
**POST** `/`

**Request Body:**
```json
{
  "followup_business_id": 1,
  "business": {
    "name": "ABC Corp",
    "company_type": "Private Limited",
    "category": "Technology",
    "type": "Company",
    "website": "https://abc.com",
    "phone": "1234567890",
    "email": "contact@abc.com"
  },
  "auth_persons": [...],
  "source": "web",
  "date": "2026-04-16",
  "time_slot_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Appointment created successfully",
  "data": {...}
}
```

### 11. Hold Time Slot
**POST** `/slots/hold`

**Request Body:**
```json
{
  "date": "2026-04-16",
  "time_slot_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Time slot held successfully",
  "data": {...}
}
```

### 12. Confirm Appointment
**POST** `/slots/confirm`

**Request Body:**
```json
{
  "followup_business_id": 1,
  "business": {...},
  "auth_persons": [...],
  "source": "web",
  "date": "2026-04-16",
  "time_slot_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Appointment confirmed successfully",
  "data": {...}
}
```

### 13. Create Direct Appointment
**POST** `/direct`

Creates an appointment for an existing business.

**Request Body:**
```json
{
  "followup_business_id": 1,
  "appointment": {
    "date": "2026-04-16",
    "time_slot_id": 1,
    "current_status": "Booked",
    "status": "Appointment Booked",
    "source": "Direct",
    "notes": "Optional notes"
  },
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
  "message": "Direct appointment created successfully",
  "data": {
    "business": {
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
      "authPersons": [...],
      "comments": [...]
    },
    "appointment": {
      "id": "FRMID00000001",
      "followup_business_id": 1,
      "date": "2026-04-16",
      "time_slot_id": 1,
      "current_status": "Booked",
      "source": "Direct",
      "status": "Appointment Booked",
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
      }
    }
  }
}
```

### 14. Create Appointment for Existing Business
**POST** `/business/{businessId}`

**Request Body:**
```json
{
  "date": "2026-04-16",
  "time_slot_id": 1,
  "source": "web"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Appointment created successfully",
  "data": {...}
}
```

### 15. Update Customer Availability for Consultation
**POST** `/{id}/update-availability`

Updates the customer availability status for the latest consultation of an appointment and optionally adds comments to the business.

**Request Body:**
```json
{
  "is_customer_available": 1,
  "comments": [
    {
      "followup_business_id": 5,
      "comment": "Follow-up call scheduled for next week.",
      "old_status": "Followup",
      "new_status": "Scheduled"
    }
  ]
}

```

**Response:**
```json
{
  "success": true,
  "message": "Customer availability updated successfully",
  "data": {...}
}
```

### 16. Reschedule Consultation and Update Appointment
**POST** `/{id}/reschedule-consultation`

Creates a new consultation record for an appointment and updates the appointment details. The assigned user is automatically determined using round robin logic.

**Request Body:**
```json
{
  "is_customer_available": 0,
  "status": "rescheduled",
  "meeting_date": "2026-04-16",
  "meeting_time_slot_id": 1,
  "appointments": [
    {
      "date": "2026-04-16",
      "time_slot_id": 1,
      "current_status": "rescheduled"
    }
  ],
  "comments": [
    {
      "followup_business_id": 5,
      "comment": "Follow-up call scheduled for next week.",
      "old_status": "Scheduled",
      "new_status": "rescheduled"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Consultation rescheduled successfully",
  "data": {...}
}
```

### 17. Reject Consultation and Update Appointment
**POST** `/{id}/reject-consultation`

Creates a new consultation record with rejected status for an appointment and updates the appointment details. The assigned user is automatically determined using round robin logic.

**Request Body:**
```json
{
  "is_customer_available": 0,
  "reason": "Cx-declined",
  "meeting_date": "2026-04-16",
  "appointments": [
    {
      "current_status": "not conducted"
    }
  ],
  "comments": [
    {
      "followup_business_id": 7,
      "comment": "Follow-up call scheduled for next week.",
      "old_status": "Rescheduled",
      "new_status": "not conducted"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Consultation rejected successfully",
  "data": {...}
}
```

## Permissions
- **Read:** `Appointment,read`
- **Create:** `Appointment,create`
