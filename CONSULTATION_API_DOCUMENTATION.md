# Consultation API Documentation

## Overview
The Consultation module provides comprehensive functionality for managing consultation records related to appointments. It supports creating, reading, updating, and deleting consultations with proper relationship management and role-based access control.

## Base URL
```
/api/consultation
```

## Authentication & Permissions
All endpoints require JWT authentication and appropriate permissions:
- **Read:** `Consultation,read`
- **Create:** `Consultation,create`
- **Update:** `Consultation,update`
- **Delete:** `Consultation,delete`

---

## API Endpoints

### 1. List Consultations (GET)
**Endpoint:** `GET /api/consultation`

Retrieves a paginated list of consultations with optional filtering.

#### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by consultation status |
| appointment_id | string | No | Filter by appointment ID |
| assigned_user | integer | No | Filter by assigned user ID |
| created_by | integer | No | Filter by creator user ID |
| date_from | date | No | Filter by creation date (from) |
| date_to | date | No | Filter by creation date (to) |
| per_page | integer | No | Results per page (default: 15) |

#### Request Example
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://127.0.0.1:8000/api/consultation?status=Pending&per_page=10"
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultations retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "appointment_id": "FRMID00000001",
        "status": "Pending",
        "custom_status": "Awaiting Review",
        "reason": "Initial consultation required",
        "reschedule_date": "2026-04-15",
        "reschedule_slot": 5,
        "conducted_date": null,
        "assigned_user": 4,
        "created_by": 1,
        "created_at": "2026-04-01T10:00:00.000000Z",
        "updated_at": "2026-04-01T10:00:00.000000Z",
        "appointment": {
          "id": "FRMID00000001",
          "followup_business_id": 1,
          "date": "2026-04-10",
          "followupBusiness": {
            "id": 1,
            "name": "ABC Corporation"
          }
        },
        "rescheduleSlot": {
          "id": 5,
          "start_time": "14:00:00",
          "end_time": "14:30:00"
        },
        "closer": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User",
          "username": "admin"
        },
        "assignedUser": {
          "id": 4,
          "first_name": "Sandeep",
          "last_name": "Singh",
          "username": "sandeep"
        },
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User",
          "username": "admin"
        }
      }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/consultation?page=1",
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

### 2. Create Consultation (POST)
**Endpoint:** `POST /api/consultation`

Creates a new consultation record.

#### Request Payload
```json
{
  "appointment_id": "FRMID00000001",
  "status": "Pending",
  "custom_status": "Awaiting Review",
  "reason": "Initial consultation required for quality assessment",
  "reschedule_date": "2026-04-15",
  "reschedule_slot": 5,
  "assigned_user": 4,
  "conducted_date": "2026-04-20"
}
```

#### Validation Rules
```json
{
  "appointment_id": "required|exists:appointments,id",
  "status": "required|string|max:50",
  "custom_status": "nullable|string|max:50",
  "reason": "nullable|string",
  "reschedule_date": "nullable|date",
  "reschedule_slot": "nullable|exists:time_slots,id",
  "assigned_user": "nullable|exists:users,id",
  "conducted_date": "nullable|date"
}
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultation created successfully",
  "data": {
    "id": 1,
    "appointment_id": "FRMID00000001",
    "status": "Pending",
    "custom_status": "Awaiting Review",
    "reason": "Initial consultation required for quality assessment",
    "reschedule_date": "2026-04-15",
    "reschedule_slot": 5,
    "conducted_date": null,
    "assigned_user": 4,
    "created_by": 1,
    "created_at": "2026-04-01T10:00:00.000000Z",
    "updated_at": "2026-04-01T10:00:00.000000Z",
    "appointment": { ... },
    "rescheduleSlot": { ... },
    "closer": null,
    "assignedUser": { ... },
    "creator": { ... }
  }
}
```

### 3. Get Single Consultation (GET)
**Endpoint:** `GET /api/consultation/{id}`

Retrieves a specific consultation by ID with all relationships.

#### Request Example
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://127.0.0.1:8000/api/consultation/1"
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultation retrieved successfully",
  "data": {
    "id": 1,
    "appointment_id": "FRMID00000001",
    "status": "Pending",
    "custom_status": "Awaiting Review",
    "reason": "Initial consultation required for quality assessment",
    "reschedule_date": "2026-04-15",
    "reschedule_slot": 5,
    "conducted_date": null,
    "assigned_user": 4,
    "created_by": 1,
    "created_at": "2026-04-01T10:00:00.000000Z",
    "updated_at": "2026-04-01T10:00:00.000000Z",
    "appointment": { ... },
    "rescheduleSlot": { ... },
    "closer": { ... },
    "assignedUser": { ... },
    "creator": { ... }
  }
}
```

### 4. Update Consultation (PUT)
**Endpoint:** `PUT /api/consultation/{id}`

Updates an existing consultation record.

#### Request Payload
```json
{
  "status": "In Progress",
  "custom_status": "Under Review",
  "reason": "Consultation in progress, further assessment needed",
  "reschedule_date": "2026-04-16",
  "reschedule_slot": 6,
  "assigned_user": 4,
  "conducted_date": "2026-04-20"
}
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultation updated successfully",
  "data": {
    "id": 1,
    "appointment_id": "FRMID00000001",
    "status": "In Progress",
    "custom_status": "Under Review",
    "reason": "Consultation in progress, further assessment needed",
    "reschedule_date": "2026-04-16",
    "reschedule_slot": 6,
    "conducted_date": "2026-04-20",
    "assigned_user": 4,
    "created_by": 1,
    "updated_at": "2026-04-01T12:00:00.000000Z",
    "appointment": { ... },
    "rescheduleSlot": { ... },
    "closer": { ... },
    "assignedUser": { ... },
    "creator": { ... }
  }
}
```

### 5. Delete Consultation (DELETE)
**Endpoint:** `DELETE /api/consultation/{id}`

Deletes a consultation record.

#### Request Example
```bash
curl -X DELETE \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://127.0.0.1:8000/api/consultation/1"
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultation deleted successfully",
  "data": null
}
```

### 6. Get Consultations by Appointment (GET)
**Endpoint:** `GET /api/consultation/appointment/{appointmentId}`

Retrieves all consultations for a specific appointment.

#### Request Example
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://127.0.0.1:8000/api/consultation/appointment/FRMID00000001"
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultations for appointment retrieved successfully",
  "data": [
    {
      "id": 1,
      "appointment_id": "FRMID00000001",
      "status": "Pending",
      "custom_status": "Awaiting Review",
      "reason": "Initial consultation required",
      "reschedule_date": "2026-04-15",
      "reschedule_slot": 5,
      "conducted_date": null,
      "assigned_user": 4,
      "created_by": 1,
      "created_at": "2026-04-01T10:00:00.000000Z",
      "updated_at": "2026-04-01T10:00:00.000000Z",
      "appointment": { ... },
      "rescheduleSlot": { ... },
      "closer": { ... },
      "assignedUser": { ... },
      "creator": { ... }
    },
    {
      "id": 2,
      "appointment_id": "FRMID00000001",
      "status": "Completed",
      "custom_status": "Closed",
      "reason": "Consultation completed successfully",
      "conducted_date": "2026-04-20",
      "assigned_user": 4,
      "created_by": 1,
      "created_at": "2026-04-01T11:00:00.000000Z",
      "updated_at": "2026-04-01T11:30:00.000000Z",
      "appointment": { ... },
      "rescheduleSlot": null,
      "closer": { ... },
      "assignedUser": { ... },
      "creator": { ... }
    }
  ]
}
```

### 7. Close Consultation (POST)
**Endpoint:** `POST /api/consultation/{id}/close`

Marks a consultation as completed/closed.

#### Request Payload
```json
{
  "conducted_date": "2026-04-20",
  "reason": "Consultation completed successfully, all requirements met"
}
```

#### Response Example
```json
{
  "success": true,
  "message": "Consultation closed successfully",
  "data": {
    "id": 1,
    "appointment_id": "FRMID00000001",
    "status": "Completed",
    "custom_status": "Closed",
    "reason": "Consultation completed successfully, all requirements met",
    "reschedule_date": "2026-04-15",
    "reschedule_slot": 5,
    "conducted_date": "2026-04-20",
    "assigned_user": 4,
    "created_by": 1,
    "closer": 1,
    "created_at": "2026-04-01T10:00:00.000000Z",
    "updated_at": "2026-04-01T14:00:00.000000Z",
    "appointment": { ... },
    "rescheduleSlot": { ... },
    "closer": { ... },
    "assignedUser": { ... },
    "creator": { ... }
  }
}
```

## Data Fields Description

### Consultation Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| appointment_id | string | Foreign key to appointments table |
| status | string | Consultation status (max 50 chars) |
| custom_status | string | Custom status field (max 50 chars) |
| reason | text | Reason for consultation |
| reschedule_date | date | Date for rescheduling |
| reschedule_slot | integer | Foreign key to time_slots table |
| closer | integer | Foreign key to users table (who closed) |
| conducted_date | date | When consultation was conducted |
| assigned_user | integer | Foreign key to users table (assigned to) |
| created_by | integer | Foreign key to users table (who created) |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |

### Relationship Fields
| Relationship | Included Fields | Description |
|-------------|----------------|-------------|
| appointment | id, date, followup_business_id | Basic appointment info |
| appointment.followupBusiness | id, name | Business information |
| rescheduleSlot | id, start_time, end_time | Time slot for rescheduling |
| closer | id, first_name, last_name, username | User who closed consultation |
| assignedUser | id, first_name, last_name, username | User assigned to consultation |
| creator | id, first_name, last_name, username | User who created consultation |

## Error Responses

### 404 Not Found
```json
{
  "success": false,
  "error": "Consultation not found",
  "message": "Consultation not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "error": "Validation failed",
  "message": "Validation failed",
  "errors": {
    "appointment_id": ["The appointment id field is required."],
    "status": ["The status field is required."]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Token not provided or invalid"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "error": "Forbidden",
  "message": "You do not have permission to perform this action"
}
```

## Use Cases

### 1. Quality Assessment Workflow
- Create consultation when quality assessment is needed
- Track consultation status through the process
- Close consultation when assessment is complete

### 2. Appointment Management
- Link consultations to specific appointments
- View all consultations for an appointment
- Manage rescheduling and time slots

### 3. User Assignment
- Assign consultations to specific users
- Track consultation workload
- Monitor user performance

### 4. Reporting & Analytics
- Filter consultations by date range
- Track consultation status and outcomes
- Generate consultation reports

## Integration Examples

### React Component
```javascript
const ConsultationList = () => {
  const [consultations, setConsultations] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchConsultations = async () => {
      try {
        const token = localStorage.getItem('jwt_token');
        const response = await fetch('/api/consultation', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        const data = await response.json();
        setConsultations(data.data.data);
      } catch (error) {
        console.error('Error fetching consultations:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchConsultations();
  }, []);

  return (
    <div>
      <h2>Consultations</h2>
      {loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          {consultations.map(consultation => (
            <div key={consultation.id} className="consultation-card">
              <h3>{consultation.status}</h3>
              <p>Appointment: {consultation.appointment?.followupBusiness?.name}</p>
              <p>Assigned: {consultation.assignedUser?.first_name} {consultation.assignedUser?.last_name}</p>
              <p>Created: {new Date(consultation.created_at).toLocaleDateString()}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
```

### Vue Component
```javascript
<template>
  <div>
    <h2>Consultations</h2>
    <div v-if="loading">Loading...</div>
    <div v-else>
      <div v-for="consultation in consultations" :key="consultation.id" class="consultation-card">
        <h3>{{ consultation.status }}</h3>
        <p>Appointment: {{ consultation.appointment?.followupBusiness?.name }}</p>
        <p>Assigned: {{ consultation.assignedUser?.first_name }} {{ consultation.assignedUser?.last_name }}</p>
        <p>Created: {{ formatDate(consultation.created_at) }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      consultations: [],
      loading: true
    };
  },
  async created() {
    await this.fetchConsultations();
  },
  methods: {
    async fetchConsultations() {
      try {
        const token = localStorage.getItem('jwt_token');
        const response = await fetch('/api/consultation', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        const data = await response.json();
        this.consultations = data.data.data;
      } catch (error) {
        console.error('Error fetching consultations:', error);
      } finally {
        this.loading = false;
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    }
  }
};
</script>
```

## Testing

### Test Cases

#### 1. Create Consultation
```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"appointment_id":"FRMID00000001","status":"Pending","reason":"Initial consultation required"}' \
     http://127.0.0.1:8000/api/consultation
```

#### 2. List Consultations with Filters
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://127.0.0.1:8000/api/consultation?status=Pending&assigned_user=4"
```

#### 3. Update Consultation
```bash
curl -X PUT \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"status":"In Progress","reason":"Consultation in progress"}' \
     http://127.0.0.1:8000/api/consultation/1
```

#### 4. Close Consultation
```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"conducted_date":"2026-04-20","reason":"Consultation completed successfully"}' \
     http://127.0.0.1:8000/api/consultation/1/close
```

## Database Schema

### consultations Table
```sql
CREATE TABLE `consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` varchar(12) NOT NULL,
  `status` varchar(50) NOT NULL,
  `custom_status` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `reschedule_date` date DEFAULT NULL,
  `reschedule_slot` bigint unsigned DEFAULT NULL,
  `closer` bigint unsigned DEFAULT NULL,
  `conducted_date` date DEFAULT NULL,
  `assigned_user` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `consultations_appointment_id_index` (`appointment_id`),
  KEY `consultations_status_index` (`status`),
  KEY `consultations_assigned_user_index` (`assigned_user`),
  KEY `consultations_created_by_index` (`created_by`),
  CONSTRAINT `consultations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_reschedule_slot_foreign` FOREIGN KEY (`reschedule_slot`) REFERENCES `time_slots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consultations_closer_foreign` FOREIGN KEY (`closer`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consultations_assigned_user_foreign` FOREIGN KEY (`assigned_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consultations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
```

## Security Considerations

### Authentication
- JWT token required for all endpoints
- Token expiration handling
- Secure token storage

### Authorization
- Role-based permissions (Consultation,read/create/update/delete)
- User access control
- Department-based restrictions if needed

### Data Validation
- Input sanitization
- SQL injection prevention
- File upload security (if applicable)

### Audit Trail
- created_by and updated_at tracking
- User action logging
- Status change history

## Performance Considerations

### Database Optimization
- Proper indexing on frequently queried columns
- Efficient relationship loading
- Pagination for large datasets

### Response Caching
- Consider caching for frequently accessed data
- Cache invalidation strategies

### Rate Limiting
- API rate limiting per user
- DDoS protection
- Resource usage monitoring

## Troubleshooting

### Common Issues

#### 1. Permission Denied
- Check user role and permissions
- Verify JWT token validity
- Ensure module is active

#### 2. Validation Errors
- Check required fields
- Verify data formats
- Review validation rules

#### 3. Relationship Loading
- Ensure foreign keys exist
- Check relationship definitions
- Verify eager loading

#### 4. Database Issues
- Check connection configuration
- Verify table existence
- Review migration status

---

## Summary

The Consultation API provides comprehensive functionality for managing consultation records with:
- ✅ Full CRUD operations
- ✅ Advanced filtering and searching
- ✅ Relationship management
- ✅ Role-based access control
- ✅ Audit trail capabilities
- ✅ Performance optimization
- ✅ Security best practices

This module integrates seamlessly with the existing Quality and Appointment systems to provide a complete consultation management solution.
