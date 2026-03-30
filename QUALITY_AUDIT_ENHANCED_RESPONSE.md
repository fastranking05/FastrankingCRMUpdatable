# Quality Audit API Enhanced Response Documentation

## Overview
The Quality Audit APIs have been enhanced to include business data and authorized persons data while removing unwanted fields from the response.

## Enhanced Response Structure

### New Response Format
```json
{
  "success": true,
  "message": "Audit pending quality data retrieved successfully",
  "data": [
    {
      "id": 1,
      "appointment_id": "FRMID00000001",
      "auditstatus": "unqualified",
      "status": "QA-Pending",
      "score": 65.25,
      "assigned_user": {
        "id": 4,
        "first_name": "Sandeep",
        "last_name": "Singh",
        "email": "sandeep@example.com"
      },
      "meeting_link": "https://meet.example.com/quality-audit-123",
      "created_at": "2026-03-24T16:39:49.000000Z",
      "updated_at": "2026-03-24T16:39:49.000000Z",
      "answers": [
        {
          "id": 1,
          "quality_id": 1,
          "question_id": 1,
          "answers": "yes",
          "created_at": "2026-03-27T14:02:27.000000Z",
          "updated_at": "2026-03-27T14:02:27.000000Z",
          "question": {
            "id": 1,
            "question": "This is updated question"
          }
        }
      ],
      "business": {
        "id": 1,
        "name": "Tech Solutions Inc.",
        "category": "Technology",
        "type": "Corporation",
        "website": "https://techsolutions.com",
        "phone": "+1-555-0123",
        "email": "contact@techsolutions.com",
        "auth_persons": [
          {
            "id": 1,
            "title": "Mr.",
            "firstname": "John",
            "middlename": "Michael",
            "lastname": "Doe",
            "designation": "CEO",
            "primaryemail": "john.doe@techsolutions.com",
            "primarymobile": "+1-555-0123-4567",
            "is_primary": true
          },
          {
            "id": 2,
            "title": "Ms.",
            "firstname": "Sarah",
            "middlename": "Ann",
            "lastname": "Smith",
            "designation": "CTO",
            "primaryemail": "sarah.smith@techsolutions.com",
            "primarymobile": "+1-555-0123-4568",
            "is_primary": false
          }
        ]
      },
      "appointment_date": "2024-03-28T00:00:00.000000Z",
      "appointment_source": "Direct"
    }
  ]
}
```

## Changes Made

### 1. Added Business Data
- **Business Information:** id, name, category, type, website, phone, email
- **Authorized Persons:** Complete contact information for all authorized persons

### 2. Removed Unwanted Fields
- **Removed from appointment:** time_slot_id, current_status, created_by, status
- **Removed from business:** created_by, created_at, updated_at
- **Removed from auth persons:** created_by, created_at, updated_at, hidden phone/email fields

### 3. Enhanced Data Structure
- **Nested business object:** Contains business details and auth persons
- **Simplified appointment info:** Only date and source
- **Cleaner response:** More organized and relevant data

## Field Descriptions

### Quality Audit Fields
- **id:** Quality record ID
- **appointment_id:** Foreign key to appointments table
- **auditstatus:** "unqualified" or "qualified"
- **status:** Quality record status
- **score:** Quality score (nullable)
- **assigned_user:** User assigned to the quality audit
- **meeting_link:** Meeting URL (nullable)
- **created_at/updated_at:** Timestamps
- **answers:** Quality answers with question details

### Business Fields
- **id:** Business ID
- **name:** Business name
- **category:** Business category
- **type:** Business type
- **website:** Business website
- **phone:** Business phone
- **email:** Business email
- **auth_persons:** Array of authorized persons

### Auth Person Fields
- **id:** Person ID
- **title:** Title (Mr., Ms., etc.)
- **firstname/middlename/lastname:** Person's name
- **designation:** Job title/position
- **primaryemail:** Primary email address
- **primarymobile:** Primary mobile number
- **is_primary:** Whether this is the primary contact

### Appointment Fields
- **appointment_date:** Appointment date
- **appointment_source:** Source of appointment (Direct, Follow-up, etc.)

## API Endpoints

All three endpoints now return the enhanced response format:

1. **GET** `/api/quality-audit/audit-pending` - Unqualified audits
2. **GET** `/api/quality-audit/audit-completed` - Qualified audits
3. **GET** `/api/quality-audit/all` - All audits

## Benefits

### ✅ **Enhanced Data**
- Complete business information
- Authorized persons contact details
- Better context for quality audits

### ✅ **Cleaner Response**
- Removed unnecessary fields
- Better organized data structure
- More relevant information

### ✅ **Improved Usability**
- Easier to consume in frontend
- Better performance with selective fields
- Clearer data relationships

### ✅ **Security**
- Only includes necessary contact information
- Removes sensitive internal fields
- Maintains data privacy

## Testing

### Test Command
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://127.0.0.1:8000/api/quality-audit/audit-pending
```

### Expected Response
The API will now return the enhanced response format with:
- Quality audit information
- Assigned user details
- Quality answers and questions
- Business information and authorized persons
- Clean appointment details

## Performance Considerations

### Optimized Queries
- Selective field loading reduces data transfer
- Nested relationships loaded efficiently
- Proper indexing on foreign keys

### Memory Usage
- Only necessary fields loaded
- Minimal data processing
- Efficient mapping functions

## Backward Compatibility

The enhanced response maintains:
- Same success/error structure
- Same authentication requirements
- Same role-based access control
- Same endpoint URLs

Only the data structure within the `data` array has been enhanced.

---

## Summary

✅ **Business Data Added:** Complete business information with authorized persons  
✅ **Unwanted Fields Removed:** Cleaner, more relevant response  
✅ **Better Organization:** Nested structure for better readability  
✅ **Performance Optimized:** Selective field loading  
✅ **Security Maintained:** Only necessary contact information  
✅ **Backward Compatible:** Same API structure and authentication  

The enhanced Quality Audit APIs now provide comprehensive business context while maintaining clean, efficient responses! 🎯
