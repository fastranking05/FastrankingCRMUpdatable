# Email API Documentation

## Base URL
`/api/emails` for all email endpoints

---

## 1. List Emails
**GET** `/emails`

Retrieves a paginated list of emails with optional filtering.

**Query Parameters:**
- `followup_business_id` (optional): Filter by follow-up business ID
- `type` (optional): Filter by email type
- `created_by` (optional): Filter by creator user ID
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "Emails retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "followup_business_id": 1,
        "to": ["contact@example.com", "info@example.com"],
        "cc": ["manager@example.com"],
        "bcc": null,
        "type": "Follow-up",
        "created_by": 1,
        "created_at": "2026-04-28T10:30:00.000000Z",
        "updated_at": "2026-04-28T10:30:00.000000Z",
        "followupBusiness": {
          "id": 1,
          "name": "ABC Corporation"
        },
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        }
      }
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 1,
    "last_page_url": "...",
    "next_page_url": null,
    "path": "...",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

---

## 2. Get All Emails (Role-Based Access)
**GET** `/emails/all-emails`

Retrieves emails based on user role hierarchy with role-based access control.

**Hierarchy Logic:**
- **Admin:** Can see all emails created by any user
- **Manager + Lead Generation dept + has team:** Can see team members' emails + own
- **Executive + Lead Generation dept:** Can see only own emails
- **Default:** Only own emails for safety

**Query Parameters:**
- `type` (optional): Filter by email type
- `followup_business_id` (optional): Filter by follow-up business ID
- `date_from` (optional): Filter emails created from this date (YYYY-MM-DD)
- `date_to` (optional): Filter emails created up to this date (YYYY-MM-DD)
- `search` (optional): Search by business name
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "All emails retrieved successfully",
  "data": {
    "emails": {
      "current_page": 1,
      "data": [...],
      "total": 25
    },
    "user_role": {
      "id": 1,
      "name": "Admin User",
      "user_type": "admin",
      "roles": ["Admin"],
      "departments": ["Management"],
      "teams": []
    },
    "access_level": "admin"
  }
}
```

---

## 3. Get My Emails
**GET** `/emails/my-emails`

Retrieves emails created only by the authenticated user.

**Query Parameters:**
- `type` (optional): Filter by email type
- `followup_business_id` (optional): Filter by follow-up business ID
- `date_from` (optional): Filter emails created from this date (YYYY-MM-DD)
- `date_to` (optional): Filter emails created up to this date (YYYY-MM-DD)
- `search` (optional): Search by business name
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "My emails retrieved successfully",
  "data": {
    "emails": {
      "current_page": 1,
      "data": [...],
      "total": 5
    },
    "created_by": 1,
    "user_name": "Admin User"
  }
}
```

---

## 4. Create Email
**POST** `/emails`

Creates a new email record.

**Request Body:**
```json
{
  "followup_business_id": 1,
  "to": ["contact@example.com", "info@example.com"],
  "cc": ["manager@example.com"],
  "bcc": ["ceo@example.com"],
  "type": "Follow-up"
}
```

**Validation Rules:**
- `followup_business_id`: required, exists in followup_businesses table
- `to`: required, array, minimum 1 item
- `to.*`: required, email format
- `cc`: nullable, array
- `cc.*`: nullable, email format
- `bcc`: nullable, array
- `bcc.*`: nullable, email format
- `type`: required, string, max 255 characters

**Response:**
```json
{
  "success": true,
  "message": "Email created successfully",
  "data": {
    "id": 1,
    "followup_business_id": 1,
    "to": ["contact@example.com", "info@example.com"],
    "cc": ["manager@example.com"],
    "bcc": ["ceo@example.com"],
    "type": "Follow-up",
    "created_by": 1,
    "created_at": "2026-04-28T10:30:00.000000Z",
    "updated_at": "2026-04-28T10:30:00.000000Z",
    "followupBusiness": {
      "id": 1,
      "name": "ABC Corporation"
    },
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

---

## 5. Get Email Details
**GET** `/emails/{id}`

Retrieves details of a specific email.

**Response:**
```json
{
  "success": true,
  "message": "Email retrieved successfully",
  "data": {
    "id": 1,
    "followup_business_id": 1,
    "to": ["contact@example.com", "info@example.com"],
    "cc": ["manager@example.com"],
    "bcc": ["ceo@example.com"],
    "type": "Follow-up",
    "created_by": 1,
    "created_at": "2026-04-28T10:30:00.000000Z",
    "updated_at": "2026-04-28T10:30:00.000000Z",
    "followupBusiness": {
      "id": 1,
      "name": "ABC Corporation"
    },
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

---

## 6. Update Email
**PUT** `/emails/{id}`

Updates an existing email record.

**Request Body:**
```json
{
  "followup_business_id": 2,
  "to": ["newcontact@example.com"],
  "cc": ["newmanager@example.com"],
  "bcc": null,
  "type": "Updated Follow-up"
}
```

**Validation Rules:**
- `followup_business_id`: sometimes required, exists in followup_businesses table
- `to`: sometimes required, array, minimum 1 item
- `to.*`: required, email format
- `cc`: nullable, array
- `cc.*`: nullable, email format
- `bcc`: nullable, array
- `bcc.*`: nullable, email format
- `type`: sometimes required, string, max 255 characters

**Response:**
```json
{
  "success": true,
  "message": "Email updated successfully",
  "data": {
    "id": 1,
    "followup_business_id": 2,
    "to": ["newcontact@example.com"],
    "cc": ["newmanager@example.com"],
    "bcc": null,
    "type": "Updated Follow-up",
    "created_by": 1,
    "created_at": "2026-04-28T10:30:00.000000Z",
    "updated_at": "2026-04-28T10:45:00.000000Z",
    "followupBusiness": {
      "id": 2,
      "name": "New Company"
    },
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

---

## 7. Delete Email
**DELETE** `/emails/{id}`

Deletes an email record.

**Response:**
```json
{
  "success": true,
  "message": "Email deleted successfully",
  "data": null
}
```

---

## Database Schema

### Emails Table
```sql
CREATE TABLE emails (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    followup_business_id BIGINT NOT NULL,
    to JSON NOT NULL,
    cc JSON NULL,
    bcc JSON NULL,
    type VARCHAR(255) NOT NULL,
    created_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (followup_business_id) REFERENCES followup_businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## Relationships

- Email belongs to FollowupBusiness
- Email belongs to User (creator)

---

## Permissions

### Email Module
- **Read:** `Email,read`
- **Create:** `Email,create`
- **Update:** `Email,update`
- **Delete:** `Email,delete`

---

## Role-Based Access Details

### Access Levels
1. **Admin**: Full access to all emails regardless of creator
2. **Manager** (Lead Generation dept + has team): Can see emails from team members + own
3. **Executive** (Lead Generation dept): Can see only own emails
4. **Default**: Most restrictive - only own emails

### Department Requirements
- **Lead Generation**: Required for Manager and Executive access levels
- **Team Membership**: Required for Manager access level

### Role Detection
- Checks both `user_type` and assigned `roles`
- Supports variations: Admin/SuperAdmin, Manager/Team Manager, Executive/Sales Executive
- Department name variations: "Lead Generation", "lead_generation", "leadgeneration"

---

## Notes

- All email fields (`to`, `cc`, `bcc`) are stored as JSON arrays to support multiple email addresses
- Email validation is performed on each email address in the arrays
- The `created_by` field is automatically set to the authenticated user's ID
- All endpoints are protected by JWT authentication middleware
- Email records are soft-deleted through Laravel's standard delete mechanism
- Role-based access control ensures users can only see emails they're authorized to view
- Comprehensive logging for access control and debugging
