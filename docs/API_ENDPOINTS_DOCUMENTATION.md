# API Endpoints Documentation

## Authentication Required
All endpoints require JWT authentication except where specified.

## Quality Module Routes
**Base URL:** `/api/quality`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/quality` | Get all quality records | Quality Control,read |
| GET | `/api/quality/filter-options` | Get filter options | Quality Control,read |
| GET | `/api/quality/{id}` | Get specific quality record | Quality Control,read |
| GET | `/api/quality/my-assignments` | Get my quality assignments | Quality Control,read |
| GET | `/api/quality/workload-stats` | Get workload statistics | Quality Control,read |
| PUT | `/api/quality/{id}` | Update quality record | Quality Control,update |
| POST | `/api/quality/{qualityId}/submit-answers` | Submit quality answers | Quality Control,update |
| POST | `/api/quality/{id}/reassign` | Reassign quality record | Quality Control,update |

## Quality Questions Routes
**Base URL:** `/api/quality-questions`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/quality-questions` | Get all quality questions | Quality Questions,read |
| POST | `/api/quality-questions` | Create quality question | Quality Questions,create |
| PUT | `/api/quality-questions/{id}` | Update quality question | Quality Questions,update |
| DELETE | `/api/quality-questions/{id}` | Delete quality question | Quality Questions,delete |

## Quality Data Submission Routes
**Base URL:** `/api/quality`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| POST | `/api/quality/submit` | Submit quality data | Quality Control,create |

## Quality Audit Routes
**Base URL:** `/api/quality`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/quality/audit` | Get quality audit records | Quality,read |
| GET | `/api/quality/audit/{id}` | Get specific audit record | Quality,read |

## Consultation Module Routes
**Base URL:** `/api/consultation`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/consultation` | Get all consultations | Consultation,read |
| GET | `/api/consultation/{id}` | Get specific consultation | Consultation,read |
| GET | `/api/consultation/appointment/{appointmentId}` | Get consultation by appointment | Consultation,read |
| POST | `/api/consultation` | Create consultation | Consultation,create |
| PUT | `/api/consultation/{id}` | Update consultation | Consultation,update |
| POST | `/api/consultation/{id}/close` | Close consultation | Consultation,update |
| DELETE | `/api/consultation/{id}` | Delete consultation | Consultation,delete |

## Deals Module Routes
**Base URL:** `/api/deals`

Sales pipeline CRUD on the `deals` table with business comments. Full documentation: [DEALS_API_DOCUMENTATION.md](./DEALS_API_DOCUMENTATION.md)

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/deals` | List deals by `deal_stage` filter | Deals,read |
| GET | `/api/deals/filter-options` | Get deal stage filter options | Deals,read |
| GET | `/api/deals/{id}` | Get deal by ID | Deals,read |
| GET | `/api/deals/form/businesses` | Eligible businesses for create form | Deals,create |
| GET | `/api/deals/form/businesses/{followupBusinessId}/auth-persons` | Contacts for selected business | Deals,create |
| POST | `/api/deals` | Create deal with comments | Deals,create |
| PUT | `/api/deals/{id}` | Update deal and append comments | Deals,update |
| DELETE | `/api/deals/{id}` | Delete deal | Deals,delete |

## User Assignment Routes
**Base URL:** `/api/user-assignment`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/user-assignment/stats` | Get assignment statistics | User Assignment,read |
| GET | `/api/user-assignment/next-user` | Get next assigned user | User Assignment,read |
| POST | `/api/user-assignment/reassign/{userId}` | Reassign consultations | User Assignment,update |

## Filter System Routes
**Base URL:** `/api/filters`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/filters` | Get all filters | Filters,read |
| GET | `/api/filters/{id}` | Get specific filter | Filters,read |

## Admin Module Routes

### Departments
**Base URL:** `/api/departments`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/departments` | Get all departments | Departments,read |
| POST | `/api/departments` | Create department | Departments,create |
| PUT | `/api/departments/{id}` | Update department | Departments,update |
| DELETE | `/api/departments/{id}` | Delete department | Departments,delete |

### Users
**Base URL:** `/api/users`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/users` | Get all users | Users,read |
| POST | `/api/users` | Create user | Users,create |
| PUT | `/api/users/{id}` | Update user | Users,update |
| DELETE | `/api/users/{id}` | Delete user | Users,delete |

### Roles
**Base URL:** `/api/roles`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/roles` | Get all roles | Roles,read |
| POST | `/api/roles` | Create role | Roles,create |
| PUT | `/api/roles/{id}` | Update role | Roles,update |
| DELETE | `/api/roles/{id}` | Delete role | Roles,delete |

### Teams
**Base URL:** `/api/teams`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/teams` | Get all teams | Teams,read |
| POST | `/api/teams` | Create team | Teams,create |
| PUT | `/api/teams/{id}` | Update team | Teams,update |
| DELETE | `/api/teams/{id}` | Delete team | Teams,delete |

### Business Categories
**Base URL:** `/api/business-categories`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/business-categories` | Get all business categories | Business Categories,read |
| POST | `/api/business-categories` | Create business category | Business Categories,create |
| PUT | `/api/business-categories/{id}` | Update business category | Business Categories,update |
| DELETE | `/api/business-categories/{id}` | Delete business category | Business Categories,delete |

### Business Types
**Base URL:** `/api/business-types`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/business-types` | Get all business types | Business Types,read |
| POST | `/api/business-types` | Create business type | Business Types,create |
| PUT | `/api/business-types/{id}` | Update business type | Business Types,update |
| DELETE | `/api/business-types/{id}` | Delete business type | Business Types,delete |

## Appointment Module Routes

### Appointments
**Base URL:** `/api/appointments`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/appointments` | Get all appointments | Appointments,read |
| POST | `/api/appointments` | Create appointment | Appointments,create |
| PUT | `/api/appointments/{id}` | Update appointment | Appointments,update |
| DELETE | `/api/appointments/{id}` | Delete appointment | Appointments,delete |

### Time Slots
**Base URL:** `/api/time-slots`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/time-slots` | Get all time slots | Time Slots,read |
| POST | `/api/time-slots` | Create time slot | Time Slots,create |
| PUT | `/api/time-slots/{id}` | Update time slot | Time Slots,update |
| DELETE | `/api/time-slots/{id}` | Delete time slot | Time Slots,delete |

### Time Slot Settings
**Base URL:** `/api/appointment-settings`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/appointment-settings` | Get appointment settings | Appointment Settings,read |
| PUT | `/api/appointment-settings` | Update appointment settings | Appointment Settings,update |

## Follow-Up Module Routes

### Follow-Up Records
**Base URL:** `/api/followup`

| Method | Endpoint | Description | Permissions |
|---------|-----------|-------------|--------------|
| GET | `/api/followup` | Get all follow-up records | Followup,read |
| POST | `/api/followup` | Create follow-up record | Followup,create |
| PUT | `/api/followup/{id}` | Update follow-up record | Followup,update |
| DELETE | `/api/followup/{id}` | Delete follow-up record | Followup,delete |

## Public Routes (No Authentication Required)

### Time Slots
**Base URL:** `/api/time-slots`

| Method | Endpoint | Description |
|---------|-----------|-------------|
| GET | `/api/time-slots/available` | Get available time slots by date |

### Simple Time Slots
| Method | Endpoint | Description |
|---------|-----------|-------------|
| GET | `/api/simple-slots` | Get available slots (simplified) |

### Time Slot Picker
| Method | Endpoint | Description |
|---------|-----------|-------------|
| GET | `/api/time-slots-picker` | Public time slot picker |

## Authentication Routes
**Base URL:** `/api/auth`

| Method | Endpoint | Description |
|---------|-----------|-------------|
| POST | `/api/auth/login` | User login |
| POST | `/api/auth/logout` | User logout |
| POST | `/api/auth/refresh` | Refresh JWT token |
| POST | `/api/auth/register` | User registration |

## Response Format
All API responses follow this format:

### Success Response
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

## HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error
