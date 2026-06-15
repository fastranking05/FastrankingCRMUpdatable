# Follow-Up API Documentation

## Base URL
`/api/followup-businesses` for business endpoints
`/api/followup-auth-persons` for auth person endpoints
`/api/followup` for follow-up details and comments endpoints

---

## 1. Create Follow-Up Business
**POST** `/followup-businesses`

Creates a new follow-up business record.

**Request Body:**
```json
{
  "name": "ABC Corporation",
  "category": "Technology",
  "type": "Software",
  "website": "https://abccorp.com",
  "phone": "+1234567890",
  "email": "contact@abccorp.com",
  "auth_person_ids": [1, 2, 3]
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `category`: nullable, string, max 255 characters
- `type`: nullable, string, max 255 characters
- `website`: nullable, url, max 255 characters
- `phone`: nullable, string, unique
- `email`: nullable, email, max 255 characters
- `auth_person_ids`: nullable, array of existing auth person IDs

**Response:**
```json
{
  "success": true,
  "message": "Follow-up business created successfully",
  "data": {
    "id": 1,
    "name": "ABC Corporation",
    "category": "Technology",
    "type": "Software",
    "website": "https://abccorp.com",
    "phone": "+1234567890",
    "email": "contact@abccorp.com",
    "created_by": 1,
    "created_at": "2026-04-27T10:00:00.000000Z",
    "updated_at": "2026-04-27T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    },
    "authPersons": [...]
  }
}
```

---

## 2. Create Follow-Up Authorized Person
**POST** `/followup-auth-persons`

Creates a new follow-up authorized person record.

**Request Body:**
```json
{
  "title": "Mr.",
  "firstname": "John",
  "middlename": "Michael",
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
  "primarymobile": "+1234567890",
  "altmobile": "+0987654321",
  "primaryemail": "john.doe@abccorp.com",
  "altemail": "john.doe.personal@gmail.com",
  "business_ids": [1, 2, 3]
}
```

**Validation Rules:**
- `title`: nullable, string, max 50 characters
- `firstname`: required, string, max 255 characters
- `middlename`: nullable, string, max 255 characters
- `lastname`: required, string, max 255 characters
- `is_primary`: nullable, boolean
- `job_title`: nullable, string, max 255 characters
- `seniority_level`: nullable, string, max 100 characters
- `extension`: nullable, string, max 50 characters
- `linkedin_profile`: nullable, url, max 255 characters
- `facebook_profile`: nullable, url, max 255 characters
- `preferred_contact_method`: nullable, string, max 100 characters
- `preferred_contact_time`: nullable, string (varchar), max 255 characters
- `gender`: nullable, must be one of: male, female, other
- `dob`: nullable, date
- `primaryphone`: nullable, string, unique
- `altphone`: nullable, string, unique
- `primarymobile`: nullable, string, unique
- `altmobile`: nullable, string, unique
- `primaryemail`: required, email, unique
- `altemail`: nullable, email, unique
- `business_ids`: nullable, array of existing business IDs

**Response:**
```json
{
  "success": true,
  "message": "Follow-up authorized person created successfully",
  "data": {
    "id": 1,
    "title": "Mr.",
    "firstname": "John",
    "middlename": "Michael",
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
    "primarymobile": "+1234567890",
    "altmobile": "+0987654321",
    "primaryemail": "john.doe@abccorp.com",
    "altemail": "john.doe.personal@gmail.com",
    "created_by": 1,
    "created_at": "2026-04-27T10:00:00.000000Z",
    "updated_at": "2026-04-27T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    },
    "businesses": [...]
  }
}
```

---

## 3. Add Follow-Up Details and Comments
**POST** `/followup`

Adds follow-up details and comments to an existing business.

**Request Body:**
```json
{
  "followup_business_id": 1,
  "followup_details": [
    {
      "source": "Website",
      "status": "Contacted",
      "date": "2026-04-28",
      "time": "10:30"
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

**Validation Rules:**
- `followup_business_id`: required, exists in followup_businesses table
- `followup_details`: nullable, array
- `followup_details.*.source`: nullable, string, max 255 characters
- `followup_details.*.status`: nullable, string, max 255 characters
- `followup_details.*.date`: nullable, date
- `followup_details.*.time`: nullable, date_format:H:i
- `comments`: nullable, array
- `comments.*.comment`: required, string
- `comments.*.old_status`: nullable, string, max 255 characters
- `comments.*.new_status`: nullable, string, max 255 characters

**Response:**
```json
{
  "success": true,
  "message": "Follow-up details and comments created successfully",
  "data": {
    "id": 1,
    "name": "ABC Corporation",
    "followupDetails": [...],
    "comments": [...]
  }
}
```

---

## 4. Get Follow-Up Record (Single View)
**GET** `/followup/{id}`

Retrieves a complete follow-up record for a business.

**Mandatory business profile (always at root of `data`):** business fields, `creator`, `auth_persons`, `business_service` (`null` if not set), `lead_qualification` (`null` if not set). `followup_details` and `comments` are returned in addition.

Includes:
- Full business details (including `priority`)
- Authorized persons (full profile)
- Business service profile (`business_service` with `primary_service` and `interested_services_list`)
- Lead qualification profile (`lead_qualification` — temperature + BANT)
- Follow-up details (with creators)
- Comments (with creators)

**Response:**
```json
{
  "success": true,
  "message": "Follow-up record retrieved successfully",
  "data": {
    "id": 1,
    "name": "ABC Corporation",
    "trading_name": "ABC Trading",
    "priority": "high",
    "source_name": "Website",
    "sub_source": "Google Ads",
    "creator": { "id": 1, "first_name": "Admin", "last_name": "User" },
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
    "followup_details": [...],
    "comments": [...]
  }
}
```

---

## 5. Update Follow-Up Details and Comments
**PUT** `/followup/{id}`

Updates follow-up details and comments for an existing business.

**Request Body:**
```json
{
  "followup_details": [
    {
      "source": "Phone",
      "status": "Follow-up Scheduled",
      "date": "2026-04-28",
      "time": "14:00"
    }
  ],
  "comments": [
    {
      "comment": "Follow-up call scheduled",
      "old_status": "Contacted",
      "new_status": "Follow-up Scheduled"
    }
  ]
}
```

**Validation Rules:**
- `followup_details`: nullable, array
- `followup_details.*.source`: nullable, string, max 255 characters
- `followup_details.*.status`: nullable, string, max 255 characters
- `followup_details.*.date`: nullable, date
- `followup_details.*.time`: nullable, date_format:H:i
- `comments`: nullable, array
- `comments.*.comment`: sometimes required, string
- `comments.*.old_status`: nullable, string, max 255 characters
- `comments.*.new_status`: nullable, string, max 255 characters

**Response:**
```json
{
  "success": true,
  "message": "Follow-up details and comments updated successfully",
  "data": {
    "id": 1,
    "name": "ABC Corporation",
    "auth_persons": [...],
    "business_service": {...},
    "lead_qualification": {...},
    "followup_details": [...],
    "comments": [...]
  }
}
```

---

## Permissions

### Follow-Up Business
- **Read:** `Follow-Up,read`
- **Create:** `Follow-Up,create`
- **Update:** `Follow-Up,update`
- **Delete:** `Follow-Up,delete`

### Follow-Up Authorized Person
- **Read:** `Follow-Up,read`
- **Create:** `Follow-Up,create`
- **Update:** `Follow-Up,update`
- **Delete:** `Follow-Up,delete`

### Follow-Up Details and Comments
- **Create:** `Follow-Up,create`
- **Update:** `Follow-Up,update`
