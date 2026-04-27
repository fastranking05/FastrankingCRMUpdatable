# Follow-Up API Documentation

## Base URL
`/api/followup-businesses` for business endpoints
`/api/followup-auth-persons` for auth person endpoints

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
  "designation": "CEO",
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
- `designation`: nullable, string, max 255 characters
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
    "designation": "CEO",
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
