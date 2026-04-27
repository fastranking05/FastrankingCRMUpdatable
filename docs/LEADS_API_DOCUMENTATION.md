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

## 4. Get Lead Details
**GET** `/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Lead retrieved successfully",
  "data": {...}
}
```

---

## 5. Update Lead
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

## 6. Delete Lead
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
