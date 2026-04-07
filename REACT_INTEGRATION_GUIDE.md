# Simple Slots API Integration Guide for React

## Overview
This guide explains how to integrate the real-time slot management API into your React application.

## API Endpoints

### 1. Get Available Slots
```
GET /api/simple-slots?date=2026-04-08
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "date": "2026-04-08",
  "slots": [
    {
      "id": 1,
      "name": "Morning Slot 1",
      "time": "9:00 AM",
      "available": 4,
      "blocked": false
    }
  ]
}
```

### 2. Block a Slot
```
POST /api/simple-slots/block
Authorization: Bearer {token}
Content-Type: application/json

{
  "time_slot_id": 1,
  "session_id": "user-session-123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Time slot held successfully",
  "booking_id": 123
}
```

### 3. Release a Slot
```
POST /api/simple-slots/release
Authorization: Bearer {token}
Content-Type: application/json

{
  "block_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Time slot released successfully"
}
```

## React Integration Steps

### 1. Setup API Service

```javascript
// src/services/api.js
import axios from 'axios';

const API_BASE_URL = 'http://127.0.0.1:8000/api';

class SimpleSlotsAPI {
  constructor() {
    this.token = localStorage.getItem('auth_token');
    this.axios = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
  }

  // Get available slots for a date
  async getAvailableSlots(date) {
    try {
      const response = await this.axios.get('/simple-slots', { params: { date } });
      return response.data;
    } catch (error) {
      console.error('Error fetching slots:', error);
      throw error;
    }
  }

  // Block a time slot
  async blockSlot(timeSlotId, sessionId) {
    try {
      const response = await this.axios.post('/simple-slots/block', {
        time_slot_id: timeSlotId,
        session_id: sessionId
      });
      return response.data;
    } catch (error) {
      console.error('Error blocking slot:', error);
      throw error;
    }
  }

  // Release a time slot
  async releaseSlot(blockId) {
    try {
      const response = await this.axios.post('/simple-slots/release', {
        block_id: blockId
      });
      return response.data;
    } catch (error) {
      console.error('Error releasing slot:', error);
      throw error;
    }
  }
}

export default new SimpleSlotsAPI();
```

### 2. React Component Example

```jsx
// src/components/SlotBooking.jsx
import React, { useState, useEffect } from 'react';
import SimpleSlotsAPI from '../services/api';

const SlotBooking = () => {
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [slots, setSlots] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [sessionId] = useState(`user-session-${Date.now()}`);

  const api = new SimpleSlotsAPI();

  // Load available slots when date changes
  useEffect(() => {
    const loadSlots = async () => {
      try {
        setLoading(true);
        const data = await api.getAvailableSlots(selectedDate);
        setSlots(data.slots || []);
      } catch (error) {
        console.error('Failed to load slots:', error);
        setSlots([]);
      } finally {
        setLoading(false);
      }
    };

    if (selectedDate) {
      loadSlots();
    }
  }, [selectedDate]);

  // Handle slot selection
  const handleSlotSelect = async (slot) => {
    if (slot.blocked) return;
    
    try {
      setLoading(true);
      const result = await api.blockSlot(slot.id, sessionId);
      
      if (result.success) {
        setSelectedSlot(slot);
        // Refresh slots to show updated availability
        const data = await api.getAvailableSlots(selectedDate);
        setSlots(data.slots || []);
      } else {
        alert(`Failed to block slot: ${result.message}`);
      }
    } catch (error) {
      console.error('Error selecting slot:', error);
      alert('Failed to select slot. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  // Handle slot release
  const handleSlotRelease = async () => {
    if (!selectedSlot) return;
    
    try {
      setLoading(true);
      const result = await api.releaseSlot(selectedSlot.bookingId);
      
      if (result.success) {
        setSelectedSlot(null);
        // Refresh slots to show updated availability
        const data = await api.getAvailableSlots(selectedDate);
        setSlots(data.slots || []);
      } else {
        alert(`Failed to release slot: ${result.message}`);
      }
    } catch (error) {
      console.error('Error releasing slot:', error);
      alert('Failed to release slot. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="slot-booking">
      <h2>Book a Time Slot</h2>
      
      {/* Date Selection */}
      <div className="date-selection">
        <label>Select Date:</label>
        <input
          type="date"
          value={selectedDate}
          onChange={(e) => setSelectedDate(e.target.value)}
          min={new Date().toISOString().split('T')[0]}
        />
      </div>

      {/* Loading State */}
      {loading && <div className="loading">Loading available slots...</div>}

      {/* Available Slots */}
      <div className="slots-grid">
        {slots.map((slot) => (
          <div
            key={slot.id}
            className={`slot ${slot.blocked ? 'blocked' : ''} ${selectedSlot?.id === slot.id ? 'selected' : ''}`}
            onClick={() => !slot.blocked && handleSlotSelect(slot)}
          >
            <div className="slot-time">{slot.time}</div>
            <div className="slot-name">{slot.name}</div>
            <div className="slot-availability">
              Available: {slot.available}
              {slot.blocked && <span className="blocked-badge">Blocked</span>}
            </div>
          </div>
        ))}
      </div>

      {/* Selected Slot Actions */}
      {selectedSlot && (
        <div className="selected-slot-actions">
          <h3>Selected Slot: {selectedSlot.time}</h3>
          <button onClick={handleSlotRelease}>
            Release Slot
          </button>
          <button onClick={() => alert('Proceed with booking!')}>
            Continue Booking
          </button>
        </div>
      )}
    </div>
  );
};

export default SlotBooking;
```

### 3. Styling (CSS)

```css
/* src/styles/SlotBooking.css */
.slot-booking {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.date-selection {
  margin-bottom: 20px;
}

.date-selection label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.date-selection input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.loading {
  text-align: center;
  padding: 20px;
  color: #666;
}

.slots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.slot {
  border: 2px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.slot:hover:not(.blocked) {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.slot.selected {
  border-color: #28a745;
  background-color: #28a745;
  color: white;
}

.slot.blocked {
  background-color: #f8d7da;
  border-color: #dc3545;
  cursor: not-allowed;
  opacity: 0.7;
}

.slot-time {
  font-size: 1.2em;
  font-weight: bold;
  color: #333;
  margin-bottom: 5px;
}

.slot-name {
  font-size: 0.9em;
  color: #666;
  margin-bottom: 10px;
}

.slot-availability {
  font-size: 0.8em;
  color: #28a745;
  font-weight: bold;
}

.blocked-badge {
  background-color: #dc3545;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.7em;
  margin-left: 10px;
}

.selected-slot-actions {
  background-color: #e9ecef;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  margin-top: 20px;
}

.selected-slot-actions h3 {
  margin-top: 0;
  color: #333;
}

.selected-slot-actions button {
  margin-right: 10px;
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.selected-slot-actions button:first-child {
  background-color: #dc3545;
  color: white;
}

.selected-slot-actions button:last-child {
  background-color: #28a745;
  color: white;
}
```

### 4. Advanced Features

#### Real-time Updates with WebSocket
```javascript
// Enhanced API service with WebSocket support
class SimpleSlotsAPI {
  constructor() {
    this.token = localStorage.getItem('auth_token');
    this.axios = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    
    // WebSocket for real-time updates
    this.setupWebSocket();
  }

  setupWebSocket() {
    const ws = new WebSocket('ws://127.0.0.1:6001');
    
    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      
      if (data.type === 'slot_update') {
        // Refresh slots when they change
        this.emitSlotUpdate(data.slotId, data.date);
      }
    };
  }

  emitSlotUpdate(slotId, date) {
    // Custom event or state management
    window.dispatchEvent(new CustomEvent('slotUpdate', {
      detail: { slotId, date }
    }));
  }
}
```

#### Error Handling
```javascript
// Enhanced error handling
const handleApiError = (error) => {
  if (error.response) {
    switch (error.response.status) {
      case 401:
        // Token expired, redirect to login
        window.location.href = '/login';
        break;
      case 422:
        // Validation error
        alert(error.response.data.message);
        break;
      case 500:
        // Server error
        alert('Server error. Please try again later.');
        break;
      default:
        alert('An error occurred. Please try again.');
    }
  } else {
    alert('Network error. Please check your connection.');
  }
};
```

## Integration Checklist

### ✅ Required Steps:
1. **Authentication**: Implement JWT token management
2. **API Service**: Create SimpleSlotsAPI class
3. **React Component**: Build SlotBooking component
4. **State Management**: Handle loading, selection, and errors
5. **Real-time Updates**: Implement WebSocket for live updates
6. **Error Handling**: Comprehensive error management
7. **Styling**: Professional UI with feedback states

### 🔧 Key Features:
- **Dynamic Availability**: Based on active Sales users
- **Real-time Blocking**: Prevents double-booking
- **Auto-expiry**: 15-minute slot holding
- **Visual Feedback**: Blocked/selected states
- **Responsive Design**: Grid layout for all screen sizes
- **Error Recovery**: Graceful handling of network issues

## Testing Your Integration

1. **Test API Endpoints**: Verify all endpoints work
2. **Test Blocking Flow**: Select → Block → Try second selection
3. **Test Release Flow**: Block → Release → Verify availability
4. **Test Real-time**: Multiple users selecting same slot
5. **Test Edge Cases**: Network errors, token expiry

This guide provides everything needed to successfully integrate the simple-slots API into your React application with real-time slot management functionality!
