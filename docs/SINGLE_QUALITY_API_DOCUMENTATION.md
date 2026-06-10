# Single Quality API Documentation

## Overview
This API retrieves a single quality record by ID with complete relationship data including appointment details, business information, authorized persons, and **question–answer mappings**.

The response includes:
- **`answers`** — only submitted answers, each with its related question
- **`question_answers`** — all active quality questions merged with this record’s answers (use this for single-view / audit forms)
- **`business_comments`** — all comments linked to the appointment’s business (`followup_business_id`), newest first

## API Endpoint

### **GET** `/api/quality/quality/{id}`

Retrieves a single quality record by its ID.

## Authentication Requirements

- **JWT Token:** `Authorization: Bearer {token}`
- **Permission:** `Quality Control,read`

## Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Quality record ID |

## Request Headers

```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

## Request Example

### cURL
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://127.0.0.1:8000/api/quality/quality/1
```

### JavaScript
```javascript
const response = await fetch('http://127.0.0.1:8000/api/quality/quality/1', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer YOUR_JWT_TOKEN',
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
console.log(data);
```

## Response Structure

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Quality record retrieved successfully",
  "data": {
    "id": 1,
    "appointment_id": "FRMID00000001",
    "auditstatus": "unqualified",
    "status": "QA-Pending",
    "score": null,
    "assigned_user": 4,
    "meeting_link": null,
    "created_at": "2026-03-24T16:39:49.000000Z",
    "updated_at": "2026-03-24T16:39:49.000000Z",
    "appointment": {
      "id": "FRMID00000001",
      "followup_business_id": 1,
      "source": "Direct",
      "status": "Appointment Booked",
      "date": "2026-03-28",
      "time_slot_id": 5,
      "current_status": "Booked",
      "created_by": 1,
      "created_at": "2026-03-24T16:39:49.000000Z",
      "updated_at": "2026-03-24T16:39:49.000000Z",
      "followupBusiness": {
        "id": 1,
        "name": "ABC Corporation",
        "category": "Technology Services",
        "type": "Enterprise Client",
        "website": "https://abccorp.com",
        "phone": "+2541220036",
        "email": "contact@abccorp.com",
        "created_by": 1,
        "created_at": "2026-03-24T16:39:49.000000Z",
        "updated_at": "2026-03-24T16:39:49.000000Z",
        "authPersons": [
          {
            "id": 1,
            "title": "Mr.",
            "firstname": "John",
            "middlename": "Michael",
            "lastname": "Doe",
            "job_title": "Chief Executive Officer",
            "gender": "Male",
            "dob": "1980-01-15",
            "primaryemail": "john.doe@abccorp.com",
            "primarymobile": "+1234567890",
            "is_primary": true,
            "created_by": 1,
            "created_at": "2026-03-24T16:39:49.000000Z",
            "updated_at": "2026-03-24T16:39:49.000000Z"
          },
          {
            "id": 2,
            "title": "Ms.",
            "firstname": "Jane",
            "middlename": null,
            "lastname": "Smith",
            "job_title": "Technical Director",
            "gender": "Female",
            "dob": "1985-05-20",
            "primaryemail": "jane.smith@abccorp.com",
            "primarymobile": "+1234567891",
            "is_primary": false,
            "created_by": 1,
            "created_at": "2026-03-24T16:39:49.000000Z",
            "updated_at": "2026-03-24T16:39:49.000000Z"
          }
        ]
      },
      "timeSlot": {
        "id": 5,
        "start_time": "09:00:00",
        "end_time": "10:00:00",
        "is_available": true,
        "created_at": "2026-03-24T16:39:49.000000Z",
        "updated_at": "2026-03-24T16:39:49.000000Z"
      }
    },
    "assignedUser": {
      "id": 4,
      "first_name": "Sandeep",
      "last_name": "Singh",
      "email": "sandeep@example.com",
      "created_at": "2026-03-24T16:39:49.000000Z",
      "updated_at": "2026-03-24T16:39:49.000000Z"
    },
    "answers": [
      {
        "id": 1,
        "quality_id": 1,
        "question_id": 1,
        "answer": "yes",
        "answers": "yes",
        "question": {
          "id": 1,
          "question": "This is updated question",
          "is_active": true
        },
        "created_at": "2026-03-27T14:02:27.000000Z",
        "updated_at": "2026-03-27T14:02:27.000000Z"
      },
      {
        "id": 2,
        "quality_id": 1,
        "question_id": 3,
        "answer": "yes",
        "answers": "yes",
        "question": {
          "id": 3,
          "question": "How would you rate our customer service quality?",
          "is_active": true
        },
        "created_at": "2026-03-27T14:02:27.000000Z",
        "updated_at": "2026-03-27T14:02:27.000000Z"
      }
    ],
    "question_answers": [
      {
        "quality_id": 1,
        "question_id": 1,
        "question": "This is updated question",
        "is_active": true,
        "answer_id": 1,
        "answer": "yes",
        "answers": "yes",
        "is_answered": true,
        "created_at": "2026-03-27T14:02:27.000000Z",
        "updated_at": "2026-03-27T14:02:27.000000Z"
      },
      {
        "quality_id": 1,
        "question_id": 2,
        "question": "Was the appointment conducted on time?",
        "is_active": true,
        "answer_id": null,
        "answer": null,
        "answers": null,
        "is_answered": false,
        "created_at": null,
        "updated_at": null
      },
      {
        "quality_id": 1,
        "question_id": 3,
        "question": "How would you rate our customer service quality?",
        "is_active": true,
        "answer_id": 2,
        "answer": "yes",
        "answers": "yes",
        "is_answered": true,
        "created_at": "2026-03-27T14:02:27.000000Z",
        "updated_at": "2026-03-27T14:02:27.000000Z"
      }
    ]
  }
}
```

### Business comments (`business_comments`)

All comments for the related business are included at the root and under `appointment.followup_business.comments`:

```json
"business_comments": [
  {
    "id": 1,
    "followup_business_id": 1,
    "comment": "QA approved after review",
    "old_status": "QA-Pending",
    "new_status": "QA-Approved",
    "created_by": 1,
    "creator": {
      "id": 1,
      "first_name": "Suraj",
      "last_name": "Kumar"
    },
    "created_at": "2026-06-09T10:57:30.000000Z",
    "updated_at": "2026-06-09T10:57:30.000000Z"
  }
]
```

### Answer field values

Allowed values for `answer` / `answers`:
- `yes`
- `no`
- `partially done`
- `not applicable`

### Error Responses

#### 404 Not Found
```json
{
  "success": false,
  "error": "Quality record not found",
  "message": "Quality record not found"
}
```

#### 401 Unauthorized
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Token not provided or invalid"
}
```

#### 403 Forbidden
```json
{
  "success": false,
  "error": "Forbidden",
  "message": "You do not have permission to perform this action"
}
```

## Data Fields Description

### Quality Record Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Quality record ID |
| appointment_id | string | Foreign key to appointments table |
| auditstatus | string | Audit status ("unqualified" or "qualified") |
| status | string | Quality record status |
| score | decimal | Quality score (nullable) |
| assigned_user | integer | ID of assigned user |
| meeting_link | string | Meeting URL (nullable) |
| created_at | datetime | Record creation timestamp |
| updated_at | datetime | Record update timestamp |

### Appointment Fields
| Field | Type | Description |
|-------|------|-------------|
| id | string | Appointment ID |
| followup_business_id | integer | Foreign key to followup_businesses |
| source | string | Appointment source (Direct, Follow-up) |
| status | string | Appointment status |
| date | date | Appointment date |
| time_slot_id | integer | Time slot ID |
| current_status | string | Current appointment status |
| followupBusiness | object | Business information |
| timeSlot | object | Time slot information |

### Business Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Business ID |
| name | string | Business name |
| category | string | Business category |
| type | string | Business type |
| website | string | Business website |
| phone | string | Business phone |
| email | string | Business email |
| authPersons | array | Array of authorized persons |

### Authorized Person Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Person ID |
| title | string | Title (Mr., Ms., Dr., etc.) |
| firstname | string | First name |
| middlename | string | Middle name (nullable) |
| lastname | string | Last name |
| job_title | string | Job title/position |
| seniority_level | string | Seniority level |
| extension | string | Phone extension |
| linkedin_profile | string | LinkedIn profile URL |
| facebook_profile | string | Facebook profile URL |
| preferred_contact_method | string | Preferred contact method |
| preferred_contact_time | string | Preferred contact time (varchar) |
| gender | string | Gender |
| dob | date | Date of birth |
| primaryemail | string | Primary email |
| primarymobile | string | Primary mobile |
| is_primary | boolean | Whether this is the primary contact |

### Time Slot Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Time slot ID |
| start_time | time | Start time |
| end_time | time | End time |
| is_available | boolean | Availability status |

### Quality Answer Fields (`answers` array)
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Answer record ID |
| quality_id | integer | Quality record ID |
| question_id | integer | Question ID |
| answer | string | Submitted answer (`yes`, `no`, `partially done`, `not applicable`) |
| answers | string | Same as `answer` (kept for backward compatibility) |
| question | object | Nested question: `id`, `question`, `is_active` |
| created_at | datetime | When the answer was submitted |
| updated_at | datetime | When the answer was last updated |

### Question–Answer Mapping Fields (`question_answers` array)
| Field | Type | Description |
|-------|------|-------------|
| quality_id | integer | Quality record ID |
| question_id | integer | Question ID |
| question | string | Question text |
| is_active | boolean | Whether the question is active |
| answer_id | integer \| null | Answer record ID if submitted |
| answer | string \| null | Submitted answer if present |
| answers | string \| null | Same as `answer` (backward compatibility) |
| is_answered | boolean | `true` if this question has an answer for this quality |
| created_at | datetime \| null | Answer created timestamp |
| updated_at | datetime \| null | Answer updated timestamp |

> **Frontend tip:** Use `question_answers` for the single-view screen so every active question is shown, including unanswered ones (`is_answered: false`).

### Business Comment Fields (`business_comments` array)
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Comment ID |
| followup_business_id | integer | Related business ID |
| comment | string | Comment text |
| old_status | string \| null | Previous status |
| new_status | string \| null | New status |
| created_by | integer | User ID who created the comment |
| creator | object \| null | `id`, `first_name`, `last_name` |
| created_at | datetime | Comment created timestamp |
| updated_at | datetime | Comment updated timestamp |

## Use Cases

### 1. View Quality Details
Display complete information about a specific quality audit including business context and mapped question–answers.

### 2. Quality Management
Review individual quality records for assessment and decision-making.

### 3. Reporting
Generate detailed reports for specific quality audits.

### 4. Audit Trail
Track the complete history and details of a quality assessment.

## Performance Considerations

### Database Queries
- Single database query with eager loading
- Optimized relationships to prevent N+1 queries
- Efficient data structure for minimal transfer

### Response Size
- Complete data set for comprehensive view
- Consider pagination for large datasets in list views

## Security

### Access Control
- JWT authentication required
- Role-based permission system
- Quality Control,read permission required

### Data Privacy
- Only authorized users can access quality data
- Sensitive information properly handled

## Integration Examples

### React Component
```javascript
const QualityDetail = ({ qualityId }) => {
  const [quality, setQuality] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchQuality = async () => {
      try {
        const token = localStorage.getItem('jwt_token');
        const response = await fetch(`/api/quality/quality/${qualityId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        const data = await response.json();
        setQuality(data.data);
      } catch (error) {
        console.error('Error fetching quality:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchQuality();
  }, [qualityId]);

  if (loading) return <div>Loading...</div>;
  if (!quality) return <div>Quality not found</div>;

  return (
    <div>
      <h2>Quality Audit Details</h2>
      <p>Status: {quality.status}</p>
      <p>Score: {quality.score || 'Not rated'}</p>
      <h3>Business Information</h3>
      <p>{quality.appointment?.followupBusiness?.name}</p>

      <h3>Question &amp; Answers</h3>
      <ul>
        {(quality.question_answers || []).map((item) => (
          <li key={item.question_id}>
            <strong>{item.question}</strong>
            <span> — {item.is_answered ? item.answer : 'Not answered'}</span>
          </li>
        ))}
      </ul>
    </div>
  );
};
```

### Vue Component
```javascript
<template>
  <div v-if="quality">
    <h2>Quality Audit Details</h2>
    <div class="quality-info">
      <p><strong>Status:</strong> {{ quality.status }}</p>
      <p><strong>Score:</strong> {{ quality.score || 'Not rated' }}</p>
    </div>
    <div class="business-info">
      <h3>Business Information</h3>
      <p>{{ quality.appointment.followupBusiness.name }}</p>
      <p>{{ quality.appointment.followupBusiness.email }}</p>
    </div>
    <div class="question-answers">
      <h3>Question &amp; Answers</h3>
      <ul>
        <li v-for="item in quality.question_answers" :key="item.question_id">
          <strong>{{ item.question }}</strong>
          — {{ item.is_answered ? item.answer : 'Not answered' }}
        </li>
      </ul>
    </div>
  </div>
  <div v-else-if="loading">
    Loading...
  </div>
  <div v-else>
    Quality not found
  </div>
</template>

<script>
export default {
  data() {
    return {
      quality: null,
      loading: true
    };
  },
  async created() {
    await this.fetchQuality();
  },
  methods: {
    async fetchQuality() {
      try {
        const token = localStorage.getItem('jwt_token');
        const response = await fetch(`/api/quality/quality/${this.$route.params.id}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        const data = await response.json();
        this.quality = data.data;
      } catch (error) {
        console.error('Error fetching quality:', error);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
```

## Testing

### Test Cases

#### 1. Valid Quality ID
```bash
curl -X GET \
     -H "Authorization: Bearer VALID_JWT_TOKEN" \
     http://127.0.0.1:8000/api/quality/quality/1
```
**Expected:** 200 OK with quality data

#### 2. Invalid Quality ID
```bash
curl -X GET \
     -H "Authorization: Bearer VALID_JWT_TOKEN" \
     http://127.0.0.1:8000/api/quality/quality/99999
```
**Expected:** 404 Not Found

#### 3. No Authentication
```bash
curl -X GET \
     http://127.0.0.1:8000/api/quality/quality/1
```
**Expected:** 401 Unauthorized

#### 4. Invalid Token
```bash
curl -X GET \
     -H "Authorization: Bearer INVALID_TOKEN" \
     http://127.0.0.1:8000/api/quality/quality/1
```
**Expected:** 401 Unauthorized

#### 5. No Permission
```bash
curl -X GET \
     -H "Authorization: Bearer USER_WITHOUT_PERMISSION_TOKEN" \
     http://127.0.0.1:8000/api/quality/quality/1
```
**Expected:** 403 Forbidden

## Troubleshooting

### Common Issues

#### 1. 404 Not Found
- Verify the quality ID exists
- Check the URL structure: `/api/quality/quality/{id}`

#### 2. 401 Unauthorized
- Ensure JWT token is valid
- Check token expiration
- Verify token format

#### 3. 403 Forbidden
- Verify user has `Quality Control,read` permission
- Check user role assignments

#### 4. 500 Server Error
- Check database connections
- Verify relationships exist
- Review error logs for details

## Related APIs

### Quality List API
- **Endpoint:** `GET /api/quality/quality/`
- **Description:** List all quality records with filtering

### Quality Audit APIs
- **Endpoint:** `GET /api/quality-audit/audit-pending`
- **Description:** Get pending quality audits

### Quality Update API
- **Endpoint:** `PUT /api/quality/quality/{id}`
- **Description:** Update quality record

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-03-30 | Initial documentation |
| 1.1 | 2026-03-30 | Fixed relationship names, added timeSlot |
| 1.2 | 2026-06-09 | Added `question_answers` mapped array; `answers` now includes nested `question` and `answer` fields |
| 1.3 | 2026-06-09 | Added `business_comments` mapped by `followup_business_id` |

---

## Summary

The Single Quality API provides comprehensive access to individual quality audit records with complete relationship data including business information, authorized persons, appointment details, and **mapped question–answers**. Use `question_answers` for single-view/audit forms and `answers` for submitted answer records only. Authentication and `Quality Control,read` permission are required.
