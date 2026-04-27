# Quality Score Field Documentation

## Overview
Added a `score` field to the `qualities` table to store percentage values as decimal numbers (e.g., 85.50, 100.00, 59.75) without the % symbol.

## Database Schema

### Migration Details
- **File**: `2026_03_27_123655_add_score_to_qualities_table.php`
- **Column Type**: `DECIMAL(5,2)`
- **Nullable**: Yes
- **Position**: After `auditstatus` column
- **Default**: NULL

### Column Specification
```sql
ALTER TABLE qualities ADD COLUMN score DECIMAL(5,2) NULL AFTER auditstatus;
```

**Range**: 0.00 to 999.99 (though validation restricts to 0.00-100.00)

## Model Updates

### Quality Model Changes
```php
protected $fillable = [
    'appointment_id',
    'auditstatus', 
    'status',
    'assigned_user',
    'meeting_link',
    'score', // Added
];

protected $casts = [
    'score' => 'decimal:2', // Added
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

## API Updates

### Quality Controller Changes

#### Update Method Validation
```php
$validator = Validator::make($request->all(), [
    'auditstatus' => 'sometimes|in:qualified,unqualified',
    'status' => 'sometimes|string',
    'meeting_link' => 'nullable|url',
    'score' => 'nullable|numeric|min:0|max:100', // Added
]);
```

#### Update Data Handling
```php
$updateData = [];
// ... other fields
if ($request->has('score')) {
    $updateData['score'] = $request->score; // Added
}
```

#### Index Method - Score Filtering
```php
// Filter by score range
if ($request->has('score_min')) {
    $query->where('score', '>=', $request->input('score_min'));
}
if ($request->has('score_max')) {
    $query->where('score', '<=', $request->input('score_max'));
}
if ($request->has('score')) {
    $query->where('score', $request->input('score'));
}
```

## API Usage Examples

### Update Quality with Score
**Endpoint:** `PUT /api/quality/{id}`

**Request Body:**
```json
{
  "auditstatus": "qualified",
  "status": "Completed",
  "score": 85.50
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "appointment_id": 123,
    "auditstatus": "qualified",
    "status": "Completed",
    "assigned_user": 1,
    "meeting_link": null,
    "score": 85.50,
    "created_at": "2026-03-27T12:00:00.000000Z",
    "updated_at": "2026-03-27T12:30:00.000000Z"
  },
  "message": "Quality record updated successfully"
}
```

### Filter by Score
**Endpoint:** `GET /api/quality`

**Query Parameters:**
```bash
# Filter by exact score
GET /api/quality?score=85.50

# Filter by score range
GET /api/quality?score_min=80&score_max=90

# Filter by minimum score only
GET /api/quality?score_min=75

# Filter by maximum score only
GET /api/quality?score_max=95
```

## cURL Examples

### Update Score
```bash
curl -X PUT \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "score": 92.75
     }' \
     http://localhost:8000/api/quality/1
```

### Filter by Score Range
```bash
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://localhost:8000/api/quality?score_min=80&score_max=95"
```

## JavaScript/Axios Examples

### Update Score
```javascript
const updateQualityScore = async (qualityId, score) => {
  try {
    const response = await axios.put(`/api/quality/${qualityId}`, {
      score: score
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error updating score:', error.response?.data);
  }
};

// Usage
updateQualityScore(1, 85.50);
```

### Filter by Score
```javascript
const getQualitiesByScore = async (scoreMin, scoreMax) => {
  try {
    const params = new URLSearchParams();
    if (scoreMin !== undefined) params.append('score_min', scoreMin);
    if (scoreMax !== undefined) params.append('score_max', scoreMax);
    
    const response = await axios.get(`/api/quality?${params}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching qualities:', error.response?.data);
  }
};

// Usage
getQualitiesByScore(80, 90); // Get qualities with score between 80-90
```

## Frontend Implementation

### Display Score as Percentage
```javascript
// Format score for display
const formatScoreAsPercentage = (score) => {
  if (score === null || score === undefined) return 'N/A';
  return `${score}%`;
};

// Example usage
const score = 85.50;
const displayValue = formatScoreAsPercentage(score); // "85.50%"
```

### React Component Example
```jsx
import React, { useState } from 'react';

const QualityScore = ({ quality, onUpdate }) => {
  const [score, setScore] = useState(quality.score || '');
  const [isEditing, setIsEditing] = useState(false);

  const handleUpdate = async () => {
    try {
      await onUpdate(quality.id, { score: parseFloat(score) });
      setIsEditing(false);
    } catch (error) {
      console.error('Update failed:', error);
    }
  };

  return (
    <div className="quality-score">
      {isEditing ? (
        <div>
          <input
            type="number"
            value={score}
            onChange={(e) => setScore(e.target.value)}
            min="0"
            max="100"
            step="0.01"
            className="score-input"
          />
          <button onClick={handleUpdate}>Save</button>
          <button onClick={() => setIsEditing(false)}>Cancel</button>
        </div>
      ) : (
        <div onClick={() => setIsEditing(true)}>
          {quality.score ? `${quality.score}%` : 'N/A'}
        </div>
      )}
    </div>
  );
};

export default QualityScore;
```

### Score Input Component
```jsx
const ScoreInput = ({ value, onChange, disabled = false }) => {
  return (
    <div className="score-input-group">
      <input
        type="number"
        value={value || ''}
        onChange={(e) => onChange(e.target.value ? parseFloat(e.target.value) : null)}
        min="0"
        max="100"
        step="0.01"
        disabled={disabled}
        className="form-control"
        placeholder="Enter score (0-100)"
      />
      <span className="input-group-text">%</span>
    </div>
  );
};
```

## Validation Rules

### Score Field Validation
```php
'score' => 'nullable|numeric|min:0|max:100'
```

**Rules:**
- **Nullable**: Score can be null/empty
- **Numeric**: Must be a number
- **Min**: Minimum value 0
- **Max**: Maximum value 100
- **Decimal**: Automatically handles decimal values (e.g., 85.50, 92.75)

### Frontend Validation
```javascript
const validateScore = (score) => {
  if (score === null || score === undefined || score === '') return true;
  
  const numScore = parseFloat(score);
  if (isNaN(numScore)) return false;
  if (numScore < 0 || numScore > 100) return false;
  
  return true;
};

// Usage
const isValid = validateScore(85.50); // true
const isValid2 = validateScore(150); // false
const isValid3 = validateScore(-10); // false
```

## Data Examples

### Database Storage vs Display
| **Display Format** | **Stored Value** | **Notes** |
|------------------|------------------|-----------|
| 100% | 100.00 | Perfect score |
| 85% | 85.00 | Good score |
| 85.5% | 85.50 | Decimal score |
| 92.75% | 92.75 | Precise decimal |
| N/A | NULL | No score assigned |

### API Response Examples
```json
{
  "data": [
    {
      "id": 1,
      "score": 100.00,
      "auditstatus": "qualified"
    },
    {
      "id": 2, 
      "score": 85.50,
      "auditstatus": "qualified"
    },
    {
      "id": 3,
      "score": null,
      "auditstatus": "pending"
    }
  ]
}
```

## Migration Instructions

### Run the Migration
```bash
php artisan migrate
```

### Rollback (if needed)
```bash
php artisan migrate:rollback --step=1
```

## Testing

### Unit Test Example
```php
public function test_quality_score_validation()
{
    // Test valid scores
    $this->assertTrue($this->validateScore(85.50));
    $this->assertTrue($this->validateScore(100));
    $this->assertTrue($this->validateScore(0));
    
    // Test invalid scores
    $this->assertFalse($this->validateScore(-10));
    $this->assertFalse($this->validateScore(150));
    $this->assertFalse($this->validateScore('invalid'));
}

private function validateScore($score)
{
    $validator = Validator::make(['score' => $score], [
        'score' => 'nullable|numeric|min:0|max:100'
    ]);
    
    return !$validator->fails();
}
```

### API Test Example
```php
public function test_update_quality_score()
{
    $quality = Quality::factory()->create();
    
    $response = $this->putJson("/api/quality/{$quality->id}", [
        'score' => 85.50
    ]);
    
    $response->assertStatus(200)
             ->assertJsonPath('data.score', 85.50);
}
```

## Summary

✅ **Database**: DECIMAL(5,2) column added to qualities table  
✅ **Model**: Updated fillable and casts  
✅ **API**: Score validation and filtering added  
✅ **Frontend**: Ready for percentage display formatting  
✅ **Validation**: 0-100 range enforced  
✅ **Storage**: Numeric values (85.50) not strings ("85.50%")  
✅ **Display**: Format as percentage in frontend  

The score field is now ready for use! 🎯
