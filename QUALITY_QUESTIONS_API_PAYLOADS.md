# Quality Questions API - Request Payloads

## POST Requests

### 1. Create Quality Question
**Endpoint:** `POST /api/quality-questions`
**Headers:** 
- `Authorization: Bearer YOUR_JWT_TOKEN`
- `Content-Type: application/json`

#### Payload 1: Basic Create (Active by default)
```json
{
  "question": "How would you rate our customer service quality?"
}
```

#### Payload 2: Create with Active Status
```json
{
  "question": "How satisfied are you with our product quality?",
  "is_active": true
}
```

#### Payload 3: Create Inactive Question
```json
{
  "question": "What improvements would you like to see in our service?",
  "is_active": false
}
```

#### Payload 4: Create with Long Question
```json
{
  "question": "Please describe your experience with our customer support team, including response time, problem resolution, and overall satisfaction with the service provided.",
  "is_active": true
}
```

### 2. Toggle Question Status
**Endpoint:** `POST /api/quality-questions/{id}/toggle-status`
**Headers:** 
- `Authorization: Bearer YOUR_JWT_TOKEN`

#### Payload: Empty (no body needed)
```json
{}
```

---

## PUT Requests

### 1. Update Question Text Only
**Endpoint:** `PUT /api/quality-questions/{id}`
**Headers:** 
- `Authorization: Bearer YOUR_JWT_TOKEN`
- `Content-Type: application/json`

#### Payload 1: Update Question Text
```json
{
  "question": "How would you rate our customer service quality? (Updated)"
}
```

#### Payload 2: Update Status Only
```json
{
  "is_active": false
}
```

#### Payload 3: Update Both Question and Status
```json
{
  "question": "How would you rate our customer service and support quality?",
  "is_active": true
}
```

#### Payload 4: Deactivate Question
```json
{
  "question": "How would you rate our customer service quality?",
  "is_active": false
}
```

#### Payload 5: Reactivate Question
```json
{
  "question": "How would you rate our customer service quality?",
  "is_active": true
}
```

---

## Complete cURL Examples

### Create Question (POST)
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "question": "How would you rate our customer service quality?",
       "is_active": true
     }' \
     http://localhost:8000/api/quality-questions
```

### Update Question (PUT)
```bash
curl -X PUT \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "question": "How would you rate our customer service quality? (Updated)",
       "is_active": false
     }' \
     http://localhost:8000/api/quality-questions/1
```

### Toggle Status (POST)
```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{}' \
     http://localhost:8000/api/quality-questions/1/toggle-status
```

---

## JavaScript/Axios Examples

### Create Question
```javascript
const createQuestion = async (questionData) => {
  try {
    const response = await axios.post('/api/quality-questions', {
      question: "How would you rate our customer service quality?",
      is_active: true
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error creating question:', error.response?.data);
  }
};
```

### Update Question
```javascript
const updateQuestion = async (id, updateData) => {
  try {
    const response = await axios.put(`/api/quality-questions/${id}`, {
      question: "Updated question text",
      is_active: false
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error updating question:', error.response?.data);
  }
};
```

### Toggle Status
```javascript
const toggleQuestionStatus = async (id) => {
  try {
    const response = await axios.post(`/api/quality-questions/${id}/toggle-status`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error toggling status:', error.response?.data);
  }
};
```

---

## Validation Rules

### POST (Create) Validation
```json
{
  "question": "required|string|max:1000",
  "is_active": "optional|boolean"
}
```

### PUT (Update) Validation
```json
{
  "question": "required|string|max:1000",
  "is_active": "optional|boolean"
}
```

### POST (Toggle Status) Validation
```json
{
  // No validation required - empty body
}
```

---

## Expected Responses

### Create Success Response (201)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "question": "How would you rate our customer service quality?",
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-03-27T11:00:00.000000Z",
    "updated_at": "2026-03-27T11:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  },
  "message": "Quality question created successfully"
}
```

### Update Success Response (200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "question": "How would you rate our customer service quality? (Updated)",
    "is_active": false,
    "created_by": 1,
    "created_at": "2026-03-27T11:00:00.000000Z",
    "updated_at": "2026-03-27T11:30:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  },
  "message": "Quality question updated successfully"
}
```

### Toggle Status Success Response (200)
```json
{
  "success": true,
  "data": {
    "is_active": false
  },
  "message": "Question status updated successfully"
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "question": ["The question field is required."],
    "is_active": ["The is active field must be a boolean."]
  }
}
```

### Not Found Error (404)
```json
{
  "success": false,
  "error": "Quality question not found"
}
```

### Permission Error (403)
```json
{
  "success": false,
  "error": "You do not have permission to perform this action"
}
```

---

## Postman Collection Example

```json
{
  "info": {
    "name": "Quality Questions API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Create Question",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer YOUR_JWT_TOKEN"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"question\": \"How would you rate our customer service quality?\",\n  \"is_active\": true\n}"
        },
        "url": {
          "raw": "http://localhost:8000/api/quality-questions",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "quality-questions"]
        }
      }
    },
    {
      "name": "Update Question",
      "request": {
        "method": "PUT",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer YOUR_JWT_TOKEN"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"question\": \"How would you rate our customer service quality? (Updated)\",\n  \"is_active\": false\n}"
        },
        "url": {
          "raw": "http://localhost:8000/api/quality-questions/1",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "quality-questions", "1"]
        }
      }
    },
    {
      "name": "Toggle Status",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer YOUR_JWT_TOKEN"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{}"
        },
        "url": {
          "raw": "http://localhost:8000/api/quality-questions/1/toggle-status",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "quality-questions", "1", "toggle-status"]
        }
      }
    }
  ]
}
```
