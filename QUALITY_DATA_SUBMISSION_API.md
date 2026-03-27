# Quality Data Submission API

## Overview
Single API endpoint for submitting quality data with answers using existing tables (qualities, quality_answers, quality_questions).

## Base URL
```
/api/quality-data-submission
```

## Authentication & Permissions
All endpoints require JWT authentication and `Administration,create` permission.

---

## API Endpoints

### 1. Submit Quality Data with Answers (POST)
**Endpoint:** `POST /api/quality-data-submission`

### Request Payload

#### Complete Submission with Comments
```json
{
  "auditstatus": "qualified",
  "status": "QA-Approved",
  "meetinglink": "https://meet.example.com/quality-assessment-123",
  "score": 85.50,
  "appointment_id": "FRMID00000001",
  "answers": [
    {
      "quality_id": 1,
      "question_id": 1,
      "answer": "yes"
    },
    {
      "quality_id": 1,
      "question_id": 3,
      "answer": "yes"
    },
    {
      "quality_id": 1,
      "question_id": 4,
      "answer": "partially done"
    }
  ],
  "comments": [
    {
      "followup_business_id": 1,
      "comment": "dfjghdkghkdfghk",
      "old_status": "QA-Pending",
      "new_status": "QA-Approved"
    }
  ]
}
```

#### Minimal Submission
```json
{
  "auditstatus": "qualified",
  "status": "In Progress",
  "appointment_id": "FRMID00000001",
  "answers": [
    {
      "quality_id": 1,
      "question_id": 1,
      "answer": "yes"
    }
  ]
}
```

#### Without Meeting Link or Comments
```json
{
  "auditstatus": "unqualified",
  "status": "Needs Improvement",
  "score": 65.25,
  "appointment_id": "FRMID00000001",
  "answers": [
    {
      "quality_id": 1,
      "question_id": 1,
      "answer": "Service needs improvement in response time"
    },
    {
      "quality_id": 1,
      "question_id": 3,
      "answer": "Staff training required for better customer handling"
    }
  ]
}
```

### Validation Rules
```json
{
  "auditstatus": "required|in:qualified,unqualified",
  "status": "required|string",
  "meetinglink": "nullable|string",
  "score": "nullable|numeric|min:0|max:100",
  "appointment_id": "required|exists:appointments,id",
  "answers": "required|array|min:1",
  "answers.*.quality_id": "required|exists:qualities,id",
  "answers.*.question_id": "required|exists:quality_questions,id",
  "answers.*.answer": "required|in:yes,no,partially done,not applicable",
  "comments": "nullable|array",
  "comments.*.followup_business_id": "required_with:comments|exists:followup_businesses,id",
  "comments.*.comment": "required_with:comments|string",
  "comments.*.old_status": "required_with:comments|string",
  "comments.*.new_status": "required_with:comments|string"
}
```

### Success Response (201)
```json
{
  "success": true,
  "data": {
    "quality": {
      "id": 1,
      "appointment_id": "FRMID00000001",
      "auditstatus": "qualified",
      "status": "QA-Approved",
      "assigned_user": 1,
      "meeting_link": "https://meet.example.com/quality-assessment-123",
      "score": 85.50,
      "created_at": "2026-03-27T12:00:00.000000Z",
      "updated_at": "2026-03-27T12:00:00.000000Z",
      "assignedUser": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe"
      },
      "answers": [
        {
          "id": 1,
          "quality_id": 1,
          "question_id": 1,
          "answers": "yes",
          "created_by": 1,
          "created_at": "2026-03-27T12:00:00.000000Z",
          "updated_at": "2026-03-27T12:00:00.000000Z",
          "question": {
            "id": 1,
            "question": "This is updated question",
            "is_active": true
          }
        },
        {
          "id": 2,
          "quality_id": 1,
          "question_id": 3,
          "answers": "yes",
          "created_by": 1,
          "created_at": "2026-03-27T12:00:00.000000Z",
          "updated_at": "2026-03-27T12:00:00.000000Z",
          "question": {
            "id": 3,
            "question": "How would you rate our customer service quality?",
            "is_active": true
          }
        }
      ]
    },
    "comments": [
      {
        "id": 1,
        "followup_business_id": 1,
        "comment": "dfjghdkghkdfghk",
        "old_status": "QA-Pending",
        "new_status": "QA-Approved",
        "created_by": 1,
        "created_at": "2026-03-27T12:00:00.000000Z",
        "updated_at": "2026-03-27T12:00:00.000000Z"
      }
    ]
  },
  "message": "Quality data submitted successfully"
}
```

### 2. Get Active Questions (GET)
**Endpoint:** `GET /api/quality-data-submission/questions`

### Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "question": "How would you rate our customer service?",
      "is_active": true
    },
    {
      "id": 2,
      "question": "How satisfied are you with our product quality?",
      "is_active": true
    },
    {
      "id": 3,
      "question": "What improvements would you like to see?",
      "is_active": true
    }
  ],
  "message": "Active quality questions retrieved successfully"
}
```

---

## Database Tables Used

### qualities Table
**Fields Stored:**
- `auditstatus` - "qualified" or "unqualified"
- `status` - Quality record status
- `meeting_link` - Meeting URL (from meetinglink field)
- `score` - Overall score (0-100)
- `appointment_id` - Foreign key to appointments table (required)
- `assigned_user` - Current user ID

### quality_answers Table
**Fields Stored:**
- `quality_id` - Foreign key to qualities table (manually provided)
- `question_id` - Foreign key to quality_questions table
- `answers` - Answer text (yes/no/partially done/not applicable)
- `created_by` - User who submitted the answer

### quality_questions Table
**Fields Used:**
- `id` - Question ID (available: 1, 3, 4, 5)
- `question` - Question text
- `is_active` - Only active questions are used

### comments Table (Optional)
**Fields Stored:**
- `followup_business_id` - Foreign key to followup_businesses table (manually provided)
- `comment` - Comment text
- `old_status` - Previous status
- `new_status` - New status
- `created_by` - User who created the comment

---

## cURL Examples

### Submit Quality Data
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "auditstatus": "qualified",
       "status": "QA-Approved",
       "meetinglink": "https://meet.example.com/quality-assessment-123",
       "score": 85.50,
       "appointment_id": "FRMID00000001",
       "answers": [
         {
           "quality_id": 1,
           "question_id": 1,
           "answer": "yes"
         },
         {
           "quality_id": 1,
           "question_id": 3,
           "answer": "yes"
         }
       ],
       "comments": [
         {
           "followup_business_id": 1,
           "comment": "Quality assessment completed",
           "old_status": "QA-Pending",
           "new_status": "QA-Approved"
         }
       ]
     }' \
     http://localhost:8000/api/quality-data-submission
```

### Get Active Questions
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/quality-data-submission/questions
```

---

## JavaScript/Axios Examples

### Submit Quality Data
```javascript
const submitQualityData = async (qualityData) => {
  try {
    const response = await axios.post('/api/quality-data-submission', {
      auditstatus: 'qualified',
      status: 'QA-Approved',
      meetinglink: 'https://meet.example.com/quality-assessment-123',
      score: 85.50,
      appointment_id: 'FRMID00000001',
      answers: [
        {
          quality_id: 1,
          question_id: 1,
          answer: 'yes'
        },
        {
          quality_id: 1,
          question_id: 3,
          answer: 'yes'
        }
      ],
      comments: [
        {
          followup_business_id: 1,
          comment: 'Quality assessment completed',
          old_status: 'QA-Pending',
          new_status: 'QA-Approved'
        }
      ]
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error submitting quality data:', error.response?.data);
  }
};
```

### Get Active Questions
```javascript
const getActiveQuestions = async () => {
  try {
    const response = await axios.get('/api/quality-data-submission/questions', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    return response.data.data;
  } catch (error) {
    console.error('Error fetching questions:', error);
  }
};
```

---

## React Component Example

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const QualityDataSubmissionForm = () => {
  const [questions, setQuestions] = useState([]);
  const [formData, setFormData] = useState({
    auditstatus: 'qualified',
    status: 'Completed',
    meetinglink: '',
    score: '',
    answers: {}
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    loadQuestions();
  }, []);

  const loadQuestions = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get('/api/quality-data-submission/questions', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      setQuestions(response.data.data);
      
      // Initialize answers object
      const initialAnswers = {};
      response.data.data.forEach(q => {
        initialAnswers[q.id] = '';
      });
      setFormData(prev => ({ ...prev, answers: initialAnswers }));
    } catch (error) {
      setError('Failed to load questions');
    }
  };

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleAnswerChange = (questionId, answer) => {
    setFormData(prev => ({
      ...prev,
      answers: { ...prev.answers, [questionId]: answer }
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('token');
      
      // Convert answers object to array
      const answersArray = Object.entries(formData.answers)
        .filter(([_, answer]) => answer.trim() !== '')
        .map(([questionId, answer]) => ({
          question_id: parseInt(questionId),
          answer: answer
        }));

      const submissionData = {
        auditstatus: formData.auditstatus,
        status: formData.status,
        meetinglink: formData.meetinglink || null,
        score: formData.score ? parseFloat(formData.score) : null,
        answers: answersArray
      };

      await axios.post('/api/quality-data-submission', submissionData, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      alert('Quality data submitted successfully!');
      // Reset form
      setFormData({
        auditstatus: 'qualified',
        status: 'Completed',
        meetinglink: '',
        score: '',
        answers: {}
      });
    } catch (error) {
      setError(error.response?.data?.message || 'Submission failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="quality-submission-form">
      <h2>Submit Quality Assessment</h2>
      
      {error && <div className="alert alert-danger">{error}</div>}
      
      <form onSubmit={handleSubmit}>
        {/* Quality Fields */}
        <div className="form-group">
          <label>Audit Status</label>
          <select
            value={formData.auditstatus}
            onChange={(e) => handleInputChange('auditstatus', e.target.value)}
            className="form-control"
            required
          >
            <option value="qualified">Qualified</option>
            <option value="unqualified">Unqualified</option>
          </select>
        </div>

        <div className="form-group">
          <label>Status</label>
          <input
            type="text"
            value={formData.status}
            onChange={(e) => handleInputChange('status', e.target.value)}
            className="form-control"
            required
          />
        </div>

        <div className="form-group">
          <label>Meeting Link</label>
          <input
            type="text"
            value={formData.meetinglink}
            onChange={(e) => handleInputChange('meetinglink', e.target.value)}
            className="form-control"
            placeholder="https://meet.example.com/..."
          />
        </div>

        <div className="form-group">
          <label>Score (0-100)</label>
          <input
            type="number"
            value={formData.score}
            onChange={(e) => handleInputChange('score', e.target.value)}
            className="form-control"
            min="0"
            max="100"
            step="0.01"
            placeholder="85.50"
          />
        </div>

        {/* Questions Section */}
        <h3>Quality Questions</h3>
        {questions.map(question => (
          <div key={question.id} className="form-group">
            <label>{question.question}</label>
            <textarea
              value={formData.answers[question.id] || ''}
              onChange={(e) => handleAnswerChange(question.id, e.target.value)}
              className="form-control"
              rows="3"
              required
            />
          </div>
        ))}

        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Submitting...' : 'Submit Quality Data'}
        </button>
      </form>
    </div>
  );
};

export default QualityDataSubmissionForm;
```

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "auditstatus": ["The audit status field is required."],
    "status": ["The status field is required."],
    "answers": ["The answers field is required."]
  }
}
```

### Question Not Found (422)
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "answers.0.question_id": ["The selected question id is invalid."]
  }
}
```

---

## Features Summary

✅ **Single API Endpoint** - Submit complete quality data in one call  
✅ **Uses Existing Tables** - qualities, quality_answers, quality_questions, comments  
✅ **Transaction Safety** - All-or-nothing data creation  
✅ **Flexible Fields** - auditstatus, status, meetinglink, score, appointment_id  
✅ **Multiple Answers** - Support for any number of question answers  
✅ **Manual quality_id** - Provide specific quality_id in answers  
✅ **Manual followup_business_id** - Provide specific followup_business_id in comments  
✅ **Active Questions Only** - Only uses active quality questions (IDs: 1, 3, 4, 5)  
✅ **Proper Relationships** - Loads related data in response  
✅ **Comprehensive Validation** - All fields properly validated  
✅ **Comments Support** - Optional comments with status tracking  
✅ **Required Appointment** - Links quality assessment to specific appointment  

The Quality Data Submission API provides a complete solution for submitting quality assessments with question answers and comments using existing database tables! 🎯
