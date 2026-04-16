# Schedule Details API Documentation

## Overview

This API endpoint retrieves comprehensive schedule details for a specific date, including appointments, consultations, and user block calendar entries. This is designed to be used when a user clicks on a date in a calendar interface to view all scheduled activities for that date.

## Base URL

```
http://127.0.0.1:8000/api
```

## Authentication

All endpoints require JWT authentication. Include the JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Permissions

The API requires only JWT authentication. No specific permissions are required for this endpoint.

**Note:** All endpoints require a valid JWT token in the Authorization header.

---

## Endpoints

### Get Schedule Details

```http
GET /api/user-block-calendar/schedule-details?date=2026-04-15
Authorization: Bearer YOUR_JWT_TOKEN
```

**Query Parameters:**
- `date` (required) - The date to retrieve schedule details for (YYYY-MM-DD format)

**Response:**
```json
{
  "success": true,
  "message": "Schedule details retrieved successfully",
  "data": {
    "date": "2026-04-15",
    "appointments": [
      {
        "id": "FRMID00000001",
        "followup_business_id": 1,
        "business_name": "ABC Corporation",
        "contact_person": "John Doe",
        "contact_number": "1234567890",
        "date": "2026-04-15",
        "time_slot": {
          "id": 1,
          "start_time": "09:00:00",
          "end_time": "09:30:00"
        },
        "current_status": "Booked",
        "created_by": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User",
          "email": "admin@example.com",
          "username": "admin"
        }
      }
    ],
    "consultations": [
      {
        "id": 1,
        "appointment_id": "FRMID00000001",
        "business_name": "ABC Corporation",
        "contact_person": "John Doe",
        "contact_number": "1234567890",
        "date": "2026-04-15",
        "time_slot": {
          "id": 1,
          "start_time": "09:00:00",
          "end_time": "09:30:00"
        },
        "status": "Completed",
        "custom_status": "Pending Review",
        "assigned_user": {
          "id": 2,
          "first_name": "Sales",
          "last_name": "Executive",
          "username": "sales_exec"
        },
        "closer": {
          "id": 3,
          "first_name": "Quality",
          "last_name": "Controller",
          "username": "qc_user"
        }
      }
    ],
    "scheduled_rescheduled_appointments": [
      {
        "id": "FRMID00000002",
        "followup_business_id": 2,
        "business_name": "XYZ Industries",
        "contact_person": "Jane Smith",
        "contact_number": "9876543210",
        "date": "2026-04-15",
        "time_slot": {
          "id": 3,
          "start_time": "10:00:00",
          "end_time": "10:30:00"
        },
        "current_status": "scheduled",
        "created_by": {
          "id": 2,
          "first_name": "Sales",
          "last_name": "Executive",
          "email": "sales@example.com",
          "username": "sales_exec"
        }
      }
    ],
    "user_block_calendars": [
      {
        "id": 1,
        "user_id": 4,
        "date": "2026-04-15",
        "slot_id": 2,
        "comments": "User unavailable for this slot",
        "user": {
          "id": 4,
          "first_name": "Jane",
          "last_name": "Smith",
          "email": "jane@example.com"
        },
        "time_slot": {
          "id": 2,
          "start_time": "09:30:00",
          "end_time": "10:00:00"
        },
        "created_by": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User",
          "email": "admin@example.com"
        }
      }
    ]
  }
}
```

**Response Structure:**
- `date` - The requested date
- `appointments` - Array of all appointments scheduled for the date
  - `id` - Appointment ID
  - `followup_business_id` - Associated business ID
  - `business_name` - Name of the business
  - `contact_person` - Contact person name
  - `contact_number` - Contact phone number
  - `date` - Appointment date
  - `time_slot` - Time slot details (id, start_time, end_time)
  - `current_status` - Current status of the appointment
  - `created_by` - User who created the appointment (id, first_name, last_name, email, username)
- `scheduled_rescheduled_appointments` - Array of appointments with status 'scheduled' or 'rescheduled' for the date
  - `id` - Appointment ID
  - `followup_business_id` - Associated business ID
  - `business_name` - Name of the business
  - `contact_person` - Contact person name
  - `contact_number` - Contact phone number
  - `date` - Appointment date
  - `time_slot` - Time slot details (id, start_time, end_time)
  - `current_status` - Current status (will be either 'scheduled' or 'rescheduled')
  - `created_by` - User who created the appointment (id, first_name, last_name, email, username)
- `consultations` - Array of consultations for the date
  - `id` - Consultation ID
  - `appointment_id` - Associated appointment ID
  - `business_name` - Name of the business
  - `contact_person` - Contact person name
  - `contact_number` - Contact phone number
  - `date` - Consultation date
  - `time_slot` - Time slot details (id, start_time, end_time)
  - `status` - Consultation status
  - `custom_status` - Custom status if applicable
  - `assigned_user` - User assigned to the consultation (id, first_name, last_name, username)
  - `closer` - Quality controller/closer (id, first_name, last_name, username)
- `user_block_calendars` - Array of user block calendar entries for the date
  - `id` - Block calendar entry ID
  - `user_id` - User ID who blocked the slot
  - `date` - Blocked date
  - `slot_id` - Time slot ID
  - `comments` - Comments/reason for blocking
  - `user` - User details (id, first_name, last_name, email)
  - `time_slot` - Time slot details (id, start_time, end_time)
  - `created_by` - User who created the block entry (id, first_name, last_name, email)

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "date": ["The date field is required."]
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": "Token not provided or invalid"
}
```

---

## Use Cases

1. **Calendar Date Selection** - When a user clicks on a date in the calendar interface, this endpoint can be called to display all scheduled activities for that date in the Schedule Section.

2. **Daily Schedule Overview** - Provides a comprehensive view of all appointments, consultations, and blocked slots for a specific day.

3. **Resource Planning** - Helps in understanding resource allocation and availability for a given date.

---

## Notes

- All date and time fields are returned in the database format
- Time slots are ordered by slot ID
- Appointments are ordered by time slot ID
- Consultations are ordered by ID
- User block calendar entries are ordered by slot ID
- If a related record (business, user, time slot) is not found, the field will be null
- The endpoint uses eager loading to optimize query performance

---

## Example Usage with cURL

```bash
curl -X GET "http://127.0.0.1:8000/api/user-block-calendar/schedule-details?date=2026-04-15" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

---

## Related Endpoints

- **Get Available Time Slots** - `GET /api/user-block-calendar/available-slots?date=2026-04-15`
- **User Block Calendar CRUD** - Full CRUD operations for user block calendar entries
