# Time Slot Picker API Documentation

## Overview
The Time Slot Picker API provides simple endpoints for frontend developers to retrieve available time slots based on date selection. This API is designed for easy integration with date pickers and calendar components.

## Base URL
```
/api/time-slots
```
*Note: These routes are loaded independently with no middleware or authentication requirements.*

## Authentication
No authentication required for time slot picker endpoints. These are public endpoints for frontend integration.

## Endpoints

### 1. Get Available Slots for Date
**GET** `/api/time-slots/available`

Returns available time slots for a specific date.

**Query Parameters:**
- `date` (required) - Date in Y-m-d format (e.g., "2024-03-21")
- `department_id` (optional) - Department ID for department-specific slots

**Example Request:**
```
GET /api/time-slots/available?date=2024-03-21&department_id=2
```

**Response:**
```json
{
    "success": true,
    "message": "Time slots retrieved successfully",
    "data": {
        "date": "2024-03-21",
        "department_id": 2,
        "total_slots": 8,
        "available_slots": [
            {
                "id": 1,
                "name": "Morning Slot",
                "start_time": "09:00:00",
                "end_time": "10:00:00",
                "duration_minutes": 60,
                "available_bookings": 2,
                "max_bookings": 3,
                "is_available": true,
                "display_time": "9:00 AM",
                "time_range": "9:00 AM - 10:00 AM"
            },
            {
                "id": 2,
                "name": "Late Morning",
                "start_time": "10:30:00",
                "end_time": "11:30:00",
                "duration_minutes": 60,
                "available_bookings": 1,
                "max_bookings": 3,
                "is_available": true,
                "display_time": "10:30 AM",
                "time_range": "10:30 AM - 11:30 AM"
            }
        ],
        "has_available_slots": true,
        "message": "Available time slots found"
    }
}
```

### 2. Get Slots for Date Range
**GET** `/api/time-slots/range`

Returns available time slots for a date range (perfect for calendar views).

**Query Parameters:**
- `start_date` (required) - Start date in Y-m-d format
- `end_date` (required) - End date in Y-m-d format
- `department_id` (optional) - Department ID for filtering
- `days` (optional) - Limit number of days (default: 7, max: 31)

**Example Request:**
```
GET /api/time-slots/range?start_date=2024-03-20&end_date=2024-03-26&days=7
```

**Response:**
```json
{
    "success": true,
    "message": "Date range time slots retrieved successfully",
    "data": {
        "start_date": "2024-03-20",
        "end_date": "2024-03-26",
        "department_id": null,
        "total_days": 7,
        "days_with_availability": 5,
        "slots": [
            {
                "date": "2024-03-20",
                "day_name": "Wednesday",
                "day_number": "20",
                "month": "March",
                "is_today": false,
                "is_weekend": false,
                "total_slots": 8,
                "available_slots": [
                    {
                        "id": 1,
                        "name": "Morning Slot",
                        "start_time": "09:00:00",
                        "display_time": "9:00 AM",
                        "time_range": "9:00 AM - 10:00 AM",
                        "available": true
                    }
                ],
                "has_availability": true
            }
        ],
        "summary": {
            "total_slots_available": 25,
            "best_day": {
                "date": "2024-03-22",
                "day_name": "Friday",
                "total_slots": 8,
                "available_slots": [...]
            }
        }
    }
}
```

### 3. Get Next Available Slots
**GET** `/api/time-slots/next`

Returns the next available slots for the coming days.

**Query Parameters:**
- `days` (optional) - Number of days to check (default: 7, max: 14)
- `department_id` (optional) - Department ID for filtering
- `preferred_time` (optional) - Preferred time of day: "morning", "afternoon", "evening"

**Example Request:**
```
GET /api/time-slots/next?days=7&preferred_time=morning
```

**Response:**
```json
{
    "success": true,
    "message": "Next available slots retrieved successfully",
    "data": {
        "days_checked": 7,
        "department_id": null,
        "total_dates_with_slots": 5,
        "next_available_dates": [
            {
                "date": "2024-03-21",
                "day_name": "Thursday",
                "relative_day": "Tomorrow",
                "slots": [
                    {
                        "id": 1,
                        "name": "Morning Slot",
                        "start_time": "09:00:00",
                        "display_time": "9:00 AM",
                        "time_range": "9:00 AM - 10:00 AM",
                        "available_bookings": 2
                    }
                ],
                "best_slot": {
                    "id": 1,
                    "name": "Morning Slot",
                    "start_time": "09:00:00",
                    "display_time": "9:00 AM",
                    "time_range": "9:00 AM - 10:00 AM",
                    "available_bookings": 2
                }
            }
        ],
        "first_available": {
            "date": "2024-03-21",
            "day_name": "Thursday",
            "relative_day": "Tomorrow",
            "slots": [...],
            "best_slot": {...}
        }
    }
}
```

## Frontend Integration Examples

### **React Date Picker Integration:**
```javascript
const TimeSlotPicker = () => {
  const [selectedDate, setSelectedDate] = useState(null);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchTimeSlots = async (date) => {
    setLoading(true);
    try {
      const response = await api.get(`/time-slots/available?date=${date}`);
      setAvailableSlots(response.data.available_slots);
    } catch (error) {
      console.error('Failed to fetch time slots:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <DatePicker 
        selected={selectedDate}
        onChange={(date) => {
          setSelectedDate(date);
          fetchTimeSlots(date);
        }}
        minDate={new Date()}
      />
      
      {loading && <Spinner />}
      
      <TimeSlotList slots={availableSlots} />
    </div>
  );
};
```

### **Vue.js Calendar Integration:**
```javascript
export default {
  data() {
    return {
      selectedDate: null,
      weekSlots: [],
      loading: false
    };
  },
  methods: {
    async loadWeekSlots() {
      this.loading = true;
      const startDate = this.getStartOfWeek(new Date());
      const endDate = this.getEndOfWeek(new Date());
      
      try {
        const response = await this.$api.get('/time-slots/range', {
          params: { start_date: startDate, end_date: endDate }
        });
        this.weekSlots = response.data.slots;
      } catch (error) {
        this.$toast.error('Failed to load time slots');
      } finally {
        this.loading = false;
      }
    }
  }
};
```

### **Simple JavaScript Integration:**
```javascript
class TimeSlotPicker {
  constructor() {
    this.selectedDate = null;
    this.availableSlots = [];
  }

  async selectDate(date) {
    const formattedDate = date.toISOString().split('T')[0];
    
    try {
      const response = await fetch(`/api/time-slots/available?date=${formattedDate}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      const data = await response.json();
      
      if (data.success) {
        this.availableSlots = data.data.available_slots;
        this.displayTimeSlots();
      } else {
        alert('No available slots for this date');
      }
    } catch (error) {
      console.error('Error fetching time slots:', error);
    }
  }

  displayTimeSlots() {
    const container = document.getElementById('slots-container');
    container.innerHTML = this.availableSlots.map(slot => `
      <div class="time-slot" data-id="${slot.id}">
        <div class="time">${slot.display_time}</div>
        <div class="range">${slot.time_range}</div>
        <div class="availability">${slot.available_bookings} slots available</div>
      </div>
    `).join('');
  }
}

// Usage
const picker = new TimeSlotPicker();
document.getElementById('date-picker').addEventListener('change', (e) => {
  picker.selectDate(new Date(e.target.value));
});
```

## Error Handling

### **Common Error Responses:**

#### **Validation Error (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "date": ["The date field is required."],
        "start_date": ["The start date must be after or equal to today."]
    }
}
```

#### **No Slots Available:**
```json
{
    "success": true,
    "message": "Time slots retrieved successfully",
    "data": {
        "date": "2024-03-21",
        "total_slots": 0,
        "available_slots": [],
        "has_available_slots": false,
        "message": "No available time slots for selected date"
    }
}
```

#### **Server Error (500):**
```json
{
    "success": false,
    "message": "Failed to retrieve time slots",
    "error": "Database connection failed"
}
```

## Features

### **✅ Smart Time Formatting:**
- **12-hour format** - "9:00 AM", "2:30 PM"
- **Time ranges** - "9:00 AM - 10:00 AM"
- **Relative days** - "Today", "Tomorrow", "Monday"

### **✅ Performance Optimized:**
- **Date range limits** - Max 31 days for performance
- **Efficient queries** - Optimized database calls
- **Caching support** - Ready for cache implementation

### **✅ Frontend Friendly:**
- **Simple responses** - Easy to parse and display
- **Boolean flags** - `is_available`, `has_availability`
- **Display helpers** - Pre-formatted time strings
- **Weekend detection** - `is_weekend` flag

### **✅ Flexible Options:**
- **Department filtering** - Support for department-specific slots
- **Date ranges** - Perfect for calendar views
- **Time preferences** - Morning/afternoon/evening preferences
- **Next available** - Quick access to upcoming slots

## Usage Tips

1. **Start with `/available`** for simple date selection
2. **Use `/range`** for calendar views (week/month)
3. **Call `/next`** for "quick book" features
4. **Handle empty arrays** - Always check `has_available_slots`
5. **Use `display_time`** for UI display (already formatted)
6. **Cache responses** - Time slots don't change frequently

This API provides everything needed for seamless frontend time slot selection! 🚀
