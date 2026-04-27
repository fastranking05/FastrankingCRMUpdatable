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
    "followupBusiness": {...},
    "followupBusiness.authPersons": [...],
    "followupBusiness.creator": {...},
    "followupBusiness.comments": [...],
    "timeSlot": {...},
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

**Response:**
```json
{
  "success": true,
  "message": "Appointment retrieved successfully",
  "data": {...}
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
  "message": "Appointment created successfully",
  "data": {...}
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
