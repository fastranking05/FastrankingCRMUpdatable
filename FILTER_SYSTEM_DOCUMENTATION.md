# Flexible Filter System Documentation

## Overview
A comprehensive, reusable filter system that provides flexible date range filtering, user filtering, status filtering, and search functionality across multiple modules (Appointments, Followup, Quality Control).

## Features
- **Date Range Filters**: Today, Yesterday, This Week, Last Week, This Month, Last Month, This Year, Last Year, Custom Range
- **Column Selection**: Filter by different date columns (appointment_date, created_at, updated_at)
- **User/Agent Filtering**: Filter by created_by or assigned_user
- **Status Filtering**: Filter by status with multiple values support
- **Search**: Text search across multiple columns
- **Reusable**: Same service can be used across any module

## API Endpoints

### Appointments Module
- `GET /api/appointments/filter-options` - Get filter configuration
- `POST /api/appointments/appointment-filter` - List appointments with filters

### Quality Control Module  
- `POST /api/quality/quality-filter` - List quality records with filters
- `POST /api/quality/filter-options` - Get filter configuration

### Followup Module
- `GET /api/followup/filter-options` - Get filter configuration
- `POST /api/followup/followup-filter` - List followup records with filters

## 🔒 Secure POST Filter APIs - Complete Reference

### 📋 Appointments Module

#### 1. Get Filter Options
```http
GET /api/appointments/filter-options
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:** (Empty)
```json
{}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "date_filters": {
      "today": "Today",
      "yesterday": "Yesterday",
      "this_week": "This Week",
      "last_week": "Last Week",
      "this_month": "This Month",
      "last_month": "Last Month",
      "this_year": "This Year",
      "last_year": "Last Year",
      "custom": "Custom Range"
    },
    "date_columns": {
      "date": "Appointment Date",
      "created_at": "Created Date",
      "updated_at": "Updated Date"
    },
    "status_options": [
      "Appointment Booked",
      "Appointment Rebooked",
      "Appointment Confirmed",
      "Appointment Completed",
      "Appointment Cancelled",
      "Ready",
      "In Progress",
      "Completed",
      "Cancelled"
    ],
    "current_status_options": [
      "Booked",
      "Confirmed",
      "Completed",
      "Cancelled",
      "Rebooked",
      "Ready",
      "In Progress"
    ]
  }
}
```

#### 2. Filter Appointments
```http
POST /api/appointments/appointment-filter
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Payload Examples:**

**Basic Date Filter:**
```json
{
  "date_filter": "this_month",
  "date_column": "date",
  "per_page": 15
}
```

**User Filter:**
```json
{
  "created_by": 1,
  "per_page": 15
}
```

**Multiple Users Filter:**
```json
{
  "created_by": [1, 2, 3],
  "per_page": 15
}
```

**Status Filter:**
```json
{
  "status": "Appointment Booked",
  "per_page": 15
}
```

**Multiple Statuses Filter:**
```json
{
  "status": ["Appointment Booked", "Appointment Confirmed"],
  "per_page": 15
}
```

**Current Status Filter:**
```json
{
  "current_status": "Booked",
  "per_page": 15
}
```

**Search Filter:**
```json
{
  "search": "ABC Corporation",
  "per_page": 15
}
```

**Custom Date Range:**
```json
{
  "date_filter": "custom",
  "date_column": "date",
  "custom_start_date": "2026-03-01",
  "custom_end_date": "2026-03-15",
  "per_page": 15
}
```

**Combined Filters:**
```json
{
  "date_filter": "this_month",
  "date_column": "date",
  "created_by": [1, 2],
  "status": "Appointment Booked",
  "search": "ABC",
  "per_page": 10
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "FRMID00000001",
        "followup_business_id": 1,
        "source": "Direct",
        "status": "Appointment Booked",
        "date": "2026-03-15",
        "time_slot_id": 1,
        "current_status": "Booked",
        "created_by": 1,
        "created_at": "2026-03-15T10:30:00.000000Z",
        "updated_at": "2026-03-15T10:30:00.000000Z",
        "followupBusiness": {
          "id": 1,
          "name": "ABC Corporation",
          "category": "Technology Services",
          "type": "Enterprise Client",
          "phone": "+1234567890",
          "email": "contact@abc.com"
        },
        "creator": {
          "id": 1,
          "first_name": "John",
          "last_name": "Doe"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/appointments?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/appointments?page=1",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/appointments?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "next_page_url": null,
    "path": "http://localhost:8000/api/appointments",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  },
  "message": "Direct appointments retrieved successfully"
}
```

### 📋 Quality Control Module

#### 1. Get Filter Options
```http
POST /api/quality/filter-options
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:** (Empty)
```json
{}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "date_filters": {
      "today": "Today",
      "yesterday": "Yesterday",
      "this_week": "This Week",
      "last_week": "Last Week",
      "this_month": "This Month",
      "last_month": "Last Month",
      "this_year": "This Year",
      "last_year": "Last Year",
      "custom": "Custom Range"
    },
    "date_columns": {
      "created_at": "Created Date",
      "updated_at": "Updated Date"
    },
    "status_options": [
      "QA-Pending",
      "QA-In Progress",
      "QA-Completed",
      "QA-Approved"
    ]
  }
}
```

#### 2. Filter Quality Records
```http
POST /api/quality/quality-filter
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Payload Examples:**

**Basic Date Filter:**
```json
{
  "date_filter": "this_month",
  "date_column": "created_at",
  "per_page": 15
}
```

**Assigned User Filter:**
```json
{
  "assigned_user": 4,
  "per_page": 15
}
```

**Status Filter:**
```json
{
  "status": "QA-Pending",
  "per_page": 15
}
```

**Audit Status Filter:**
```json
{
  "auditstatus": "Pending",
  "per_page": 15
}
```

**Search Filter:**
```json
{
  "search": "FRMID00000001",
  "per_page": 15
}
```

**Combined Filters:**
```json
{
  "date_filter": "this_month",
  "assigned_user": [4, 5],
  "status": "QA-Pending",
  "auditstatus": "Pending",
  "search": "FRMID00000001",
  "per_page": 10
}
```

### 📋 Followup Module

#### 1. Get Filter Options
```http
GET /api/followup/filter-options
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:** (Empty)
```json
{}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "date_filters": {
      "today": "Today",
      "yesterday": "Yesterday",
      "this_week": "This Week",
      "last_week": "Last Week",
      "this_month": "This Month",
      "last_month": "Last Month",
      "this_year": "This Year",
      "last_year": "Last Year",
      "custom": "Custom Range"
    },
    "date_columns": {
      "created_at": "Created Date",
      "updated_at": "Updated Date"
    },
    "category_options": [
      "Technology Services",
      "Healthcare",
      "Finance",
      "Education",
      "Retail",
      "Manufacturing",
      "Other"
    ],
    "type_options": [
      "Enterprise Client",
      "SME",
      "Startup",
      "Individual",
      "Government",
      "Non-Profit"
    ],
    "status_options": [
      "New",
      "Contacted",
      "Interested",
      "Not Interested",
      "Follow-up Scheduled",
      "Appointment Booked",
      "Converted",
      "Lost"
    ]
  }
}
```

#### 2. Filter Followup Records
```http
POST /api/followup/followup-filter
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Payload Examples:**

**Basic Date Filter:**
```json
{
  "date_filter": "this_month",
  "date_column": "created_at",
  "per_page": 15
}
```

**User Filter:**
```json
{
  "created_by": 1,
  "per_page": 15
}
```

**Category Filter:**
```json
{
  "category": "Technology Services",
  "per_page": 15
}
```

**Status Filter:**
```json
{
  "status": "New",
  "per_page": 15
}
```

**Search Filter:**
```json
{
  "search": "ABC Corporation",
  "per_page": 15
}
```

**Combined Filters:**
```json
{
  "date_filter": "this_month",
  "created_by": [1, 2],
  "category": "Technology Services",
  "status": "New",
  "search": "ABC",
  "per_page": 10
}
```

## 🔧 Universal Filter Parameters

### Date Filter Options:
- `today` - Today's records
- `yesterday` - Yesterday's records
- `this_week` - This week's records
- `last_week` - Last week's records
- `this_month` - This month's records
- `last_month` - Last month's records
- `this_year` - This year's records
- `last_year` - Last year's records
- `custom` - Custom date range (requires `custom_start_date` and `custom_end_date`)

### Common Parameters:
- `date_filter` - Date range filter option
- `date_column` - Column to filter dates on (`date`, `created_at`, `updated_at`)
- `custom_start_date` - Start date for custom range (YYYY-MM-DD)
- `custom_end_date` - End date for custom range (YYYY-MM-DD)
- `created_by` - User ID or array of user IDs
- `assigned_user` - Assigned user ID or array (Quality module)
- `status` - Status string or array of statuses
- `search` - Text search query
- `per_page` - Number of items per page (default: 15)
- `page` - Page number for pagination

## 🧪 Testing with cURL

```bash
# Test Appointments Filter Options
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/appointments/filter-options

# Test Appointments Filter
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{"date_filter":"this_month","per_page":10}' \
     http://localhost:8000/api/appointments/appointment-filter

# Test Quality Filter
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{"date_filter":"this_month","status":"QA-Pending"}' \
     http://localhost:8000/api/quality/quality-filter

# Test Followup Filter Options
curl -X GET \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/followup/filter-options

# Test Followup Filter
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{"date_filter":"this_month","category":"Technology Services"}' \
     http://localhost:8000/api/followup/followup-filter
```

## Filter Parameters

### Date Filters
```json
{
  "date_filter": "this_month", // today, yesterday, this_week, last_week, this_month, last_month, this_year, last_year, custom
  "date_column": "date", // date, created_at, updated_at
  "custom_start_date": "2026-03-01", // Required for custom range
  "custom_end_date": "2026-03-31"   // Required for custom range
}
```

### User/Agent Filters
```json
{
  "created_by": 1,        // Single user ID
  "created_by": [1, 2, 3] // Multiple user IDs
}
```

### Status Filters
```json
{
  "status": "Booked",           // Single status
  "status": ["Booked", "Confirmed"], // Multiple statuses
  "current_status": "Confirmed" // Additional status field
}
```

### Search Filters
```json
{
  "search": "ABC Corporation" // Text search
}
```

### Pagination
```json
{
  "per_page": 15 // Number of items per page
}
```

## Example API Calls

### 1. Get Filter Options
```bash
POST /api/appointments/filter-options
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "date_filters": {
      "today": "Today",
      "this_month": "This Month",
      "custom": "Custom Range"
    },
    "date_columns": {
      "date": "Appointment Date",
      "created_at": "Created Date"
    },
    "status_options": [
      "Appointment Booked",
      "Appointment Completed"
    ]
  }
}
```

### 2. Filter Appointments by Date Range
```bash
POST /api/appointments
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
  "date_filter": "this_month",
  "date_column": "date"
}
```

### 3. Filter by Multiple Users
```bash
POST /api/appointments
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
  "created_by": [1, 2]
}
```

### 4. Filter by Status and Date
```bash
POST /api/appointments
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
  "date_filter": "this_week",
  "status": "Booked"
}
```

### 5. Custom Date Range
```bash
POST /api/appointments
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
  "date_filter": "custom",
  "custom_start_date": "2026-03-01",
  "custom_end_date": "2026-03-15"
}
```

### 6. Search with Date Filter
```bash
POST /api/appointments
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
  "search": "ABC",
  "date_filter": "this_month"
}
```

## Frontend Integration Guide

### React Component Example

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const AppointmentFilters = () => {
  const [appointments, setAppointments] = useState([]);
  const [filterOptions, setFilterOptions] = useState({});
  const [filters, setFilters] = useState({
    date_filter: '',
    date_column: 'date',
    created_by: '',
    status: '',
    search: '',
    per_page: 15
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Load filter options on component mount
  useEffect(() => {
    const loadFilterOptions = async () => {
      try {
        const response = await axios.get('/api/appointments/filter-options');
        setFilterOptions(response.data.data);
      } catch (error) {
        console.error('Error loading filter options:', error);
        setError('Failed to load filter options');
      }
    };
    loadFilterOptions();
  }, []);

  // Load appointments when filters change
  useEffect(() => {
    const loadAppointments = async () => {
      setLoading(true);
      setError('');
      try {
        const response = await axios.post('/api/appointments/appointment-filter', filters);
        setAppointments(response.data.data);
      } catch (error) {
        console.error('Error loading appointments:', error);
        setError('Failed to load appointments');
      } finally {
        setLoading(false);
      }
    };
    loadAppointments();
  }, [filters]);

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const resetFilters = () => {
    setFilters({
      date_filter: '',
      date_column: 'date',
      created_by: '',
      status: '',
      search: '',
      per_page: 15
    });
  };

  return (
    <div className="appointment-filters">
      <h2>Appointment Filters</h2>
      
      {error && <div className="error">{error}</div>}
      {loading && <div className="loading">Loading...</div>}

      <div className="filter-controls">
        {/* Date Filter */}
        <div className="filter-group">
          <label>Date Range:</label>
          <select 
            value={filters.date_filter} 
            onChange={(e) => handleFilterChange('date_filter', e.target.value)}
          >
            <option value="">Select Date Filter</option>
            {Object.entries(filterOptions.date_filters || {}).map(([key, label]) => (
              <option key={key} value={key}>{label}</option>
            ))}
          </select>
        </div>

        {/* Date Column */}
        <div className="filter-group">
          <label>Date Column:</label>
          <select 
            value={filters.date_column} 
            onChange={(e) => handleFilterChange('date_column', e.target.value)}
          >
            {Object.entries(filterOptions.date_columns || {}).map(([key, label]) => (
              <option key={key} value={key}>{label}</option>
            ))}
          </select>
        </div>

        {/* Custom Date Range */}
        {filters.date_filter === 'custom' && (
          <div className="filter-group">
            <label>Custom Date Range:</label>
            <input 
              type="date" 
              placeholder="Start Date"
              value={filters.custom_start_date || ''}
              onChange={(e) => handleFilterChange('custom_start_date', e.target.value)}
            />
            <input 
              type="date" 
              placeholder="End Date"
              value={filters.custom_end_date || ''}
              onChange={(e) => handleFilterChange('custom_end_date', e.target.value)}
            />
          </div>
        )}

        {/* Status Filter */}
        <div className="filter-group">
          <label>Status:</label>
          <select 
            value={filters.status} 
            onChange={(e) => handleFilterChange('status', e.target.value)}
          >
            <option value="">All Statuses</option>
            {filterOptions.status_options?.map(status => (
              <option key={status} value={status}>{status}</option>
            ))}
          </select>
        </div>

        {/* Search */}
        <div className="filter-group">
          <label>Search:</label>
          <input 
            type="text" 
            placeholder="Search..."
            value={filters.search}
            onChange={(e) => handleFilterChange('search', e.target.value)}
          />
        </div>

        {/* Reset Button */}
        <button onClick={resetFilters} className="reset-btn">
          Reset Filters
        </button>
      </div>

      {/* Results */}
      <div className="results">
        <h3>Results ({appointments.total || 0})</h3>
        {appointments.data?.map(appointment => (
          <div key={appointment.id} className="appointment-item">
            <div className="appointment-header">
              <h4>{appointment.followupBusiness?.name}</h4>
              <span className="date">{appointment.date}</span>
            </div>
            <div className="appointment-details">
              <p>Status: {appointment.status}</p>
              <p>Created by: {appointment.creator?.first_name} {appointment.creator?.last_name}</p>
            </div>
          </div>
        ))}
      </div>

      {/* Pagination */}
      {appointments.links && (
        <div className="pagination">
          {appointments.links.map((link, index) => (
            <button
              key={index}
              dangerouslySetInnerHTML={{ __html: link.label }}
              onClick={() => {
                if (link.url) {
                  // Handle pagination
                  const url = new URL(link.url);
                  const page = url.searchParams.get('page');
                  handleFilterChange('page', page);
                }
              }}
              disabled={!link.url}
              className={link.active ? 'active' : ''}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default AppointmentFilters;
```

### Advanced Filter Component with Multi-Select

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const AdvancedFilters = ({ onFilterChange, filterOptions, moduleName = 'appointments' }) => {
  const [filters, setFilters] = useState({
    date_filter: '',
    date_column: 'date',
    custom_start_date: '',
    custom_end_date: '',
    created_by: [],
    status: [],
    search: '',
    per_page: 15
  });

  const [users, setUsers] = useState([]);

  // Load users for filter dropdown
  useEffect(() => {
    const loadUsers = async () => {
      try {
        const response = await axios.get('/api/users');
        setUsers(response.data.data);
      } catch (error) {
        console.error('Error loading users:', error);
      }
    };
    loadUsers();
  }, []);

  const handleMultiSelectChange = (key, value) => {
    const currentValues = filters[key] || [];
    if (currentValues.includes(value)) {
      // Remove value
      setFilters(prev => ({
        ...prev,
        [key]: currentValues.filter(v => v !== value)
      }));
    } else {
      // Add value
      setFilters(prev => ({
        ...prev,
        [key]: [...currentValues, value]
      }));
    }
  };

  const applyFilters = () => {
    onFilterChange(filters);
  };

  const resetFilters = () => {
    setFilters({
      date_filter: '',
      date_column: 'date',
      custom_start_date: '',
      custom_end_date: '',
      created_by: [],
      status: [],
      search: '',
      per_page: 15
    });
  };

  return (
    <div className="advanced-filters">
      <h3>Advanced Filters</h3>
      
      {/* Date Range Section */}
      <div className="filter-section">
        <h4>Date Range</h4>
        <select 
          value={filters.date_filter} 
          onChange={(e) => setFilters(prev => ({ ...prev, date_filter: e.target.value }))}
        >
          <option value="">Select Range</option>
          {Object.entries(filterOptions.date_filters || {}).map(([key, label]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </select>
        
        <select 
          value={filters.date_column} 
          onChange={(e) => setFilters(prev => ({ ...prev, date_column: e.target.value }))}
        >
          {Object.entries(filterOptions.date_columns || {}).map(([key, label]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </select>
        
        {/* Custom Date Inputs */}
        {filters.date_filter === 'custom' && (
          <div className="custom-date-range">
            <input 
              type="date" 
              placeholder="Start Date"
              value={filters.custom_start_date}
              onChange={(e) => setFilters(prev => ({ ...prev, custom_start_date: e.target.value }))}
            />
            <input 
              type="date" 
              placeholder="End Date"
              value={filters.custom_end_date}
              onChange={(e) => setFilters(prev => ({ ...prev, custom_end_date: e.target.value }))}
            />
          </div>
        )}
      </div>

      {/* Multi-Select for Users */}
      <div className="filter-section">
        <h4>Created By</h4>
        <div className="multi-select">
          {users.map(user => (
            <label key={user.id} className="checkbox-label">
              <input
                type="checkbox"
                checked={filters.created_by.includes(user.id)}
                onChange={() => handleMultiSelectChange('created_by', user.id)}
              />
              {user.first_name} {user.last_name}
            </label>
          ))}
        </div>
      </div>

      {/* Multi-Select for Status */}
      <div className="filter-section">
        <h4>Status</h4>
        <div className="multi-select">
          {filterOptions.status_options?.map(status => (
            <label key={status} className="checkbox-label">
              <input
                type="checkbox"
                checked={filters.status.includes(status)}
                onChange={() => handleMultiSelectChange('status', status)}
              />
              {status}
            </label>
          ))}
        </div>
      </div>

      {/* Search */}
      <div className="filter-section">
        <h4>Search</h4>
        <input 
          type="text" 
          placeholder="Search..."
          value={filters.search}
          onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
        />
      </div>

      {/* Action Buttons */}
      <div className="filter-actions">
        <button onClick={applyFilters} className="apply-btn">
          Apply Filters
        </button>
        <button onClick={resetFilters} className="reset-btn">
          Reset
        </button>
      </div>
    </div>
  );
};

export default AdvancedFilters;
```

### Custom Hook for Filter Management

```jsx
import { useState, useEffect } from 'react';
import axios from 'axios';

const useFilters = (moduleName) => {
  const [data, setData] = useState([]);
  const [filterOptions, setFilterOptions] = useState({});
  const [filters, setFilters] = useState({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  });

  // Load filter options
  useEffect(() => {
    const loadFilterOptions = async () => {
      try {
        // Use GET for appointments and followup, POST for quality
        const method = (moduleName === 'appointments' || moduleName === 'followup') ? 'get' : 'post';
        const endpoint = (moduleName === 'appointments' || moduleName === 'followup') 
          ? `/api/${moduleName}/filter-options`
          : `/api/${moduleName}/filter-options`;
        
        const response = method === 'get' 
          ? await axios.get(endpoint)
          : await axios.post(endpoint);
        
        setFilterOptions(response.data.data);
      } catch (error) {
        console.error('Error loading filter options:', error);
        setError('Failed to load filter options');
      }
    };
    loadFilterOptions();
  }, [moduleName]);

  // Load data when filters change
  useEffect(() => {
    const loadData = async () => {
      setLoading(true);
      setError('');
      try {
        const filterEndpoint = moduleName === 'appointments' 
          ? '/api/appointments/appointment-filter'
          : moduleName === 'quality'
          ? '/api/quality/quality-filter'
          : '/api/followup/followup-filter';
        
        const response = await axios.post(filterEndpoint, filters);
        setData(response.data.data.data || []);
        setPagination(response.data.data);
      } catch (error) {
        console.error('Error loading data:', error);
        setError('Failed to load data');
      } finally {
        setLoading(false);
      }
    };
    
    if (Object.keys(filterOptions).length > 0) {
      loadData();
    }
  }, [filters, moduleName, filterOptions]);

  const updateFilter = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const updateFilters = (newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters }));
  };

  const resetFilters = () => {
    setFilters({});
  };

  return {
    data,
    filterOptions,
    filters,
    loading,
    error,
    pagination,
    updateFilter,
    updateFilters,
    resetFilters
  };
};

export default useFilters;
```

### CSS Styles

```css
/* Filter Component Styles */
.appointment-filters {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.filter-controls {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
  padding: 20px;
  background: #f5f5f5;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  margin-bottom: 5px;
  font-weight: 600;
  color: #333;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.custom-date-range {
  display: flex;
  gap: 10px;
}

.custom-date-range input {
  flex: 1;
}

.reset-btn {
  padding: 10px 20px;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.reset-btn:hover {
  background: #5a6268;
}

.results {
  margin-top: 20px;
}

.appointment-item {
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-bottom: 10px;
  background: white;
}

.appointment-header {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 10px;
}

.appointment-header h4 {
  margin: 0;
  color: #333;
}

.appointment-header .date {
  color: #666;
  font-size: 14px;
}

.appointment-details p {
  margin: 5px 0;
  color: #666;
  font-size: 14px;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-top: 20px;
}

.pagination button {
  padding: 8px 12px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 4px;
}

.pagination button.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Advanced Filters Styles */
.advanced-filters {
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 20px;
}

.filter-section {
  margin-bottom: 20px;
}

.filter-section h4 {
  margin-bottom: 10px;
  color: #333;
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
}

.multi-select {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  padding: 5px;
  border-radius: 4px;
  cursor: pointer;
}

.checkbox-label:hover {
  background: #e9ecef;
}

.checkbox-label input {
  margin-right: 8px;
}

.filter-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.apply-btn {
  padding: 10px 20px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.apply-btn:hover {
  background: #0056b3;
}

.loading {
  text-align: center;
  padding: 20px;
  color: #666;
}

.error {
  background: #f8d7da;
  color: #721c24;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}
```

## Testing Steps

### 1. Test Filter Options Endpoint
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/appointments/filter-options
```

### 2. Test Basic Filtering
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{"date_filter":"this_month"}' \
     http://localhost:8000/api/appointments
```

### 3. Test Combined Filters
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{"date_filter":"this_month","created_by":1,"status":"Booked"}' \
     http://localhost:8000/api/appointments
```

### 4. Test Search
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{"search":"ABC"}' \
     http://localhost:8000/api/appointments
```

## Module-Specific Notes

### Appointments Module
- Default date column: `date` (appointment date)
- Additional columns: `created_at`, `updated_at`
- Search columns: `id`, `followupBusiness.name`, `followupBusiness.email`, `followupBusiness.phone`
- Additional filters: `current_status`, `statuses` (array)

### Quality Control Module
- Default date column: `created_at`
- Additional columns: `updated_at`
- Search columns: `appointment_id`, `appointment.business.name`
- Additional filters: `auditstatus`

### Followup Module
- Default date column: `created_at`
- Additional columns: `updated_at`
- Search columns: `name`, `category`, `type`, `email`, `phone`
- Additional filters: `category`, `type`

## Benefits
1. **Consistent**: Same filter behavior across all modules
2. **Flexible**: Multiple filter types and combinations
3. **Reusable**: Single service for all modules
4. **Maintainable**: Easy to add new filter types
5. **User-Friendly**: Intuitive date range options
6. **Performant**: Efficient database queries

## Troubleshooting

### Common Issues
1. **404 Errors**: Check if routes are properly registered
2. **Authentication Errors**: Ensure JWT token is valid and has proper permissions
3. **Empty Results**: Verify filter parameters are correct
4. **Slow Performance**: Consider adding database indexes on filtered columns

### Debug Tips
- Use browser dev tools to inspect API requests
- Check Laravel logs for errors: `php artisan log:tail`
- Verify route list: `php artisan route:list --name=filter`
- Test with Postman first before implementing in frontend

This filter system provides a powerful, consistent way to filter data across your entire application while maintaining clean, reusable code.
