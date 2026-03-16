# Appointment API Documentation

## Overview
The Appointment API allows users to create and manage appointments without going through the follow-up system. This is useful for direct booking scenarios where businesses or individuals want to book appointments directly.

## Base URL
```
/api/appointments
```

## Authentication
All endpoints require JWT authentication and appropriate permissions.

## Endpoints

### 1. Create Direct Appointment
**POST** `/api/appointments/direct`

Creates a new appointment along with a new business and auth persons.

**Request Body:**
```json
{
    "business": {
        "name": "ABC Corporation",
        "category": "Technology",
        "type": "Enterprise",
        "website": "https://abccorp.com",
        "phone": "+1234567890",
        "email": "contact@abccorp.com"
    },
    "auth_persons": [
        {
            "title": "Mr.",
            "firstname": "John",
            "lastname": "Doe",
            "is_primary": true,
            "designation": "CEO",
            "gender": "male",
            "dob": "1980-05-15",
            "primaryphone": "+1234567890",
            "primarymobile": "+1234567891",
            "primaryemail": "john.doe@abccorp.com"
        }
    ],
    "appointment": {
        "date": "2024-03-21",
        "time_slot_id": 5,
        "current_status": "Booked",
        "status": "Appointment Booked",
        "source": "Direct",
        "notes": "Initial consultation requested"
    }
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
            "website": "https://abccorp.com",
            "phone": "+1234567890",
            "email": "contact@abccorp.com",
            "authPersons": [...]
        },
        "appointment": {
            "id": "FRMID00000001",
            "followup_business_id": 1,
            "date": "2024-03-21",
            "time_slot_id": 5,
            "current_status": "Booked",
            "status": "Appointment Booked",
            "source": "Direct",
            "timeSlot": {...}
        }
    }
}
```

### 2. Create Appointment for Existing Business
**POST** `/api/appointments/business/{businessId}`

Creates an appointment for an existing business.

**Request Body:**
```json
{
    "appointment": {
        "date": "2024-03-21",
        "time_slot_id": 5,
        "current_status": "Booked",
        "status": "Appointment Booked"
    },
    "auth_persons": [
        {
            "title": "Ms.",
            "firstname": "Jane",
            "lastname": "Smith",
            "designation": "Manager",
            "primaryemail": "jane.smith@abccorp.com"
        }
    ]
}
```

### 3. Get Available Time Slots
**GET** `/api/appointments/available-slots?date=2024-03-21`

Returns available time slots for a specific date.

**Response:**
```json
{
    "success": true,
    "message": "Available time slots retrieved successfully",
    "data": {
        "date": "2024-03-21",
        "available_slots": [
            {
                "id": 1,
                "name": "Morning Slot",
                "start_time": "09:00:00",
                "end_time": "10:00:00",
                "duration_minutes": 60,
                "available_bookings": 2,
                "max_bookings": 3
            }
        ]
    }
}
```

### 4. Hold Time Slot
**POST** `/api/appointments/hold-slot`

Temporarily holds a time slot for 15 minutes.

**Request Body:**
```json
{
    "date": "2024-03-21",
    "time_slot_id": 5
}
```

**Response:**
```json
{
    "success": true,
    "message": "Time slot held successfully",
    "data": {
        "id": 1,
        "date": "2024-03-21",
        "time_slot_id": 5,
        "expires_at": "2024-03-16T13:15:00Z"
    }
}
```

### 5. Release Time Slot
**POST** `/api/appointments/release-slot`

Releases a previously held time slot.

**Request Body:**
```json
{
    "date": "2024-03-21",
    "time_slot_id": 5
}
```

### 6. List Direct Appointments
**GET** `/api/appointments`

Returns paginated list of direct appointments with filtering options.

**Query Parameters:**
- `status` - Filter by appointment status
- `current_status` - Filter by current status
- `date_from` - Filter appointments from date
- `date_to` - Filter appointments to date
- `per_page` - Number of results per page (default: 15)

**Response:**
```json
{
    "success": true,
    "message": "Direct appointments retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [...],
        "per_page": 15,
        "total": 50
    }
}
```

### 7. Get Single Appointment
**GET** `/api/appointments/direct/{appointmentId}`

Returns detailed information about a specific appointment.

### 8. Update Appointment
**PUT** `/api/appointments/direct/{appointmentId}`

Updates appointment details.

**Request Body:**
```json
{
    "appointment": {
        "date": "2024-03-22",
        "time_slot_id": 6,
        "current_status": "Confirmed",
        "notes": "Rescheduled to next day"
    }
}
```

### 9. Cancel Appointment
**DELETE** `/api/appointments/direct/{appointmentId}/cancel`

Cancels an appointment and updates its status.

## Validation Rules

### Business Details
- `name` - Required, max 255 characters
- `category` - Optional, max 255 characters
- `type` - Optional, max 255 characters
- `website` - Optional, valid URL
- `phone` - Optional, unique
- `email` - Optional, valid email, unique

### Auth Persons
- `firstname` - Required, max 255 characters
- `lastname` - Required, max 255 characters
- `primaryemail` - Required, valid email, unique
- `primarymobile` - Optional, unique
- All contact fields are unique across all auth persons

### Appointment Details
- `date` - Required, date, must be today or future
- `time_slot_id` - Required, must exist in time_slots table
- `status` - Optional, must be "Appointment Booked" or "Appointment Rebooked"
- `current_status` - Optional, max 100 characters

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "business.name": ["The business name field is required."],
        "auth_persons.0.primaryemail": ["The email has already been taken."]
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Business not found"
}
```

### Time Slot Not Available (400)
```json
{
    "success": false,
    "message": "Time slot is not available for the selected date"
}
```

## Business Logic

### Time Slot Availability
- System checks if time slot exists and is active
- Validates maximum concurrent bookings per slot
- Prevents double booking for same date and time
- Cleans up expired temporary bookings

### Appointment Creation Flow
1. Validates business and auth person data
2. Creates business record
3. Creates auth person records
4. Associates auth persons with business
5. Validates time slot availability
6. Creates appointment record
7. Returns complete data with relationships

### ID Generation
- Appointments use custom ID format: `FRMID00000001`
- Businesses use auto-increment integer IDs
- Auth persons use auto-increment integer IDs

## Permissions Required

- **Read**: `Appointment,read`
- **Create**: `Appointment,create`
- **Update**: `Appointment,update`
- **Delete**: `Appointment,delete`

## Usage Examples

### Complete Direct Appointment Creation
```bash
curl -X POST http://localhost:8000/api/direct-appointments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "business": {
        "name": "New Client Company",
        "category": "Healthcare",
        "type": "Premium",
        "phone": "+1234567890",
        "email": "contact@newclient.com"
    },
    "auth_persons": [
        {
            "title": "Dr.",
            "firstname": "Sarah",
            "lastname": "Wilson",
            "designation": "Medical Director",
            "primaryemail": "sarah.wilson@newclient.com",
            "primarymobile": "+1234567891"
        }
    ],
    "appointment": {
        "date": "2024-03-21",
        "time_slot_id": 3,
        "current_status": "Booked"
    }
  }'
```

### Check Available Slots
```bash
curl -X GET "http://localhost:8000/api/direct-appointments/available-slots?date=2024-03-21" \
  -H "Authorization: Bearer {token}"
```

This API provides a complete solution for direct appointment management with proper validation, error handling, and business logic enforcement.
