# Quality Questions API Documentation

## Overview
Complete CRUD API for managing Quality Questions with active/inactive status functionality.

## Base URL
```
/api/quality-questions
```

## Authentication & Permissions
All endpoints require JWT authentication and appropriate permissions:

- **Read**: `Quality Control,read`
- **Create**: `Quality Control,create`
- **Update**: `Quality Control,update`
- **Delete**: `Quality Control,delete`

---

## API Endpoints

### 1. View All Questions (GET)
**Endpoint:** `GET /api/quality-questions`

**Query Parameters:**
- `is_active` (optional) - Filter by active status (0 or 1)
- `search` (optional) - Search in question text
- `per_page` (optional) - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "question": "How would you rate our customer service?",
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-03-27T10:00:00.000000Z",
        "updated_at": "2026-03-27T10:00:00.000000Z",
        "creator": {
          "id": 1,
          "first_name": "John",
          "last_name": "Doe"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/quality-questions?page=1",
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  },
  "message": "Quality questions retrieved successfully"
}
```

**Examples:**
```bash
# Get all questions
GET /api/quality-questions

# Get only active questions
GET /api/quality-questions?is_active=1

# Search questions
GET /api/quality-questions?search=customer

# Paginated results
GET /api/quality-questions?per_page=5
```

### 2. View Single Question (GET)
**Endpoint:** `GET /api/quality-questions/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "question": "How would you rate our customer service?",
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-03-27T10:00:00.000000Z",
    "updated_at": "2026-03-27T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  },
  "message": "Quality question retrieved successfully"
}
```

### 3. Add Question (POST)
**Endpoint:** `POST /api/quality-questions`

**Request Body:**
```json
{
  "question": "How would you rate our customer service?",
  "is_active": true
}
```

**Validation Rules:**
- `question`: required, string, max 1000 characters
- `is_active`: optional, boolean (default: true)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "question": "How would you rate our customer service?",
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-03-27T10:00:00.000000Z",
    "updated_at": "2026-03-27T10:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  },
  "message": "Quality question created successfully"
}
```

### 4. Update Question (PUT)
**Endpoint:** `PUT /api/quality-questions/{id}`

**Request Body:**
```json
{
  "question": "How would you rate our customer service quality?",
  "is_active": false
}
```

**Validation Rules:**
- `question`: required, string, max 1000 characters
- `is_active`: optional, boolean

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "question": "How would you rate our customer service quality?",
    "is_active": false,
    "created_by": 1,
    "created_at": "2026-03-27T10:00:00.000000Z",
    "updated_at": "2026-03-27T11:00:00.000000Z",
    "creator": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe"
    }
  },
  "message": "Quality question updated successfully"
}
```

### 5. Delete Question (DELETE)
**Endpoint:** `DELETE /api/quality-questions/{id}`

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Quality question deleted successfully"
}
```

### 6. Get Active Questions Only (GET)
**Endpoint:** `GET /api/quality-questions/active`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "question": "How would you rate our customer service?",
      "is_active": true,
      "created_by": 1,
      "created_at": "2026-03-27T10:00:00.000000Z",
      "updated_at": "2026-03-27T10:00:00.000000Z",
      "creator": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe"
      }
    }
  ],
  "message": "Active quality questions retrieved successfully"
}
```

### 7. Toggle Question Status (POST)
**Endpoint:** `POST /api/quality-questions/{id}/toggle-status`

**Response:**
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
    "question": ["The question field is required."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "error": "Quality question not found"
}
```

### Permission Denied (403)
```json
{
  "success": false,
  "error": "You do not have permission to perform this action"
}
```

---

## Usage Examples

### cURL Commands

#### View All Questions
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/quality-questions
```

#### View Active Questions Only
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/quality-questions/active
```

#### Add New Question
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "question": "How would you rate our product quality?",
       "is_active": true
     }' \
     http://localhost:8000/api/quality-questions
```

#### Update Question
```bash
curl -X PUT \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "question": "How would you rate our product quality and service?",
       "is_active": false
     }' \
     http://localhost:8000/api/quality-questions/1
```

#### Toggle Question Status
```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/quality-questions/1/toggle-status
```

#### Delete Question
```bash
curl -X DELETE \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/quality-questions/1
```

### JavaScript/Axios Examples

#### Get Questions
```javascript
// Get all questions
const response = await axios.get('/api/quality-questions', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

// Get active questions only
const activeQuestions = await axios.get('/api/quality-questions/active', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

// Search questions
const searchResults = await axios.get('/api/quality-questions?search=customer', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

#### Create Question
```javascript
const newQuestion = await axios.post('/api/quality-questions', {
  question: 'How would you rate our support team?',
  is_active: true
}, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

#### Update Question
```javascript
const updatedQuestion = await axios.put('/api/quality-questions/1', {
  question: 'How would you rate our support team performance?',
  is_active: false
}, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

#### Toggle Status
```javascript
const toggledStatus = await axios.post('/api/quality-questions/1/toggle-status', {}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

#### Delete Question
```javascript
await axios.delete('/api/quality-questions/1', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

---

## React Component Example

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const QualityQuestionsManager = () => {
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Load questions
  const loadQuestions = async (filters = {}) => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      Object.keys(filters).forEach(key => {
        if (filters[key] !== undefined) {
          params.append(key, filters[key]);
        }
      });
      
      const response = await axios.get(`/api/quality-questions?${params}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      setQuestions(response.data.data);
    } catch (error) {
      setError('Failed to load questions');
    } finally {
      setLoading(false);
    }
  };

  // Create question
  const createQuestion = async (questionData) => {
    try {
      const response = await axios.post('/api/quality-questions', questionData, {
        headers: { 
          Authorization: `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      return response.data.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to create question');
    }
  };

  // Update question
  const updateQuestion = async (id, questionData) => {
    try {
      const response = await axios.put(`/api/quality-questions/${id}`, questionData, {
        headers: { 
          Authorization: `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      return response.data.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to update question');
    }
  };

  // Delete question
  const deleteQuestion = async (id) => {
    try {
      await axios.delete(`/api/quality-questions/${id}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      return true;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to delete question');
    }
  };

  // Toggle status
  const toggleStatus = async (id) => {
    try {
      const response = await axios.post(`/api/quality-questions/${id}/toggle-status`, {}, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      return response.data.data.is_active;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to toggle status');
    }
  };

  useEffect(() => {
    loadQuestions();
  }, []);

  return (
    <div>
      {/* Your UI components here */}
    </div>
  );
};

export default QualityQuestionsManager;
```

---

## Database Schema

### quality_questions Table
- `id` - Primary key
- `question` - Question text (string, max 1000)
- `is_active` - Boolean flag (default: 1)
- `created_by` - Foreign key to users table
- `created_at` - Timestamp
- `updated_at` - Timestamp

---

## Features Summary

✅ **Complete CRUD Operations**
- Create, Read, Update, Delete questions
- Single question view
- Paginated list view

✅ **Active/Inactive Status**
- Toggle question status
- Filter by active status
- Get active questions only

✅ **Search & Filtering**
- Search in question text
- Filter by active status
- Pagination support

✅ **Permission-Based Access**
- JWT authentication required
- Role-based permissions
- Secure endpoint access

✅ **Validation & Error Handling**
- Input validation
- Proper error responses
- User-friendly messages

✅ **API Documentation**
- Complete endpoint reference
- Usage examples
- Error handling guide
