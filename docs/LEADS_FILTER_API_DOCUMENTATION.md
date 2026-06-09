# Leads Filter API Documentation

Flexible filter APIs for the Leads module — same pattern as Appointments and Follow-Up.

**Base URL:** `/api/leads`  
**Authentication:** JWT required  
**Permission:** `Leads,read`

---

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/leads/filter-options` | Get available filter options |
| POST | `/api/leads/leads-filter` | Filter leads with POST body |

---

## 1. Get Filter Options

**GET** `/api/leads/filter-options`

Returns dropdown/preset values for the leads filter UI.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully",
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
    "scope_options": {
      "all": "All leads (role-based access)",
      "my": "My leads only"
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
    "source_name_options": [
      "Website",
      "Referral",
      "Cold Call"
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

> `source_name_options` is loaded dynamically from existing leads in the database.

---

## 2. Filter Leads

**POST** `/api/leads/leads-filter`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `scope` | string | No | `all` (default, role-based) or `my` (only logged-in user's leads) |
| `date_filter` | string | No | Preset: `today`, `yesterday`, `this_week`, `last_week`, `this_month`, `last_month`, `this_year`, `last_year`, `custom` |
| `date_column` | string | No | `created_at` (default) or `updated_at` |
| `custom_start_date` | date | No | Required with `custom` filter (YYYY-MM-DD) |
| `custom_end_date` | date | No | Required with `custom` filter (YYYY-MM-DD) |
| `created_by` | int \| array | No | Filter by user ID(s) who created the lead |
| `search` | string | No | Search in name, category, type, email, phone, source_name |
| `category` | string | No | Business category |
| `type` | string | No | Business type |
| `source_name` | string | No | Lead source |
| `status` | string | No | Latest follow-up status (from followup_details) |
| `per_page` | int | No | Results per page (default: 15) |
| `cursor` | string | No | Cursor for next/previous page |

### Role-based access (`scope: all`)

| Role | Access |
|------|--------|
| Admin | All leads |
| Manager (Lead Generation + team) | Team members' leads + own |
| Executive | Own leads only |

### Request Examples

**This month's leads:**
```json
{
  "date_filter": "this_month",
  "date_column": "created_at",
  "scope": "all",
  "per_page": 15
}
```

**My leads only:**
```json
{
  "scope": "my",
  "per_page": 15
}
```

**Filter by creator:**
```json
{
  "created_by": 5,
  "per_page": 15
}
```

**Multiple creators:**
```json
{
  "created_by": [1, 2, 5],
  "per_page": 15
}
```

**Category filter:**
```json
{
  "category": "Technology Services",
  "per_page": 15
}
```

**Source filter:**
```json
{
  "source_name": "Website",
  "per_page": 15
}
```

**Status filter:**
```json
{
  "status": "New",
  "per_page": 15
}
```

**Search:**
```json
{
  "search": "ABC Corporation",
  "per_page": 15
}
```

**Custom date range:**
```json
{
  "date_filter": "custom",
  "date_column": "created_at",
  "custom_start_date": "2026-01-01",
  "custom_end_date": "2026-03-31",
  "per_page": 15
}
```

**Combined filters:**
```json
{
  "scope": "all",
  "date_filter": "this_month",
  "category": "Healthcare",
  "source_name": "Referral",
  "search": "clinic",
  "per_page": 20
}
```

### Response

Cursor-paginated list (same shape as appointments/follow-up filter APIs):

```json
{
  "success": true,
  "message": "Leads retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "ABC Corporation",
        "category": "Technology Services",
        "type": "SME",
        "source_name": "Website",
        "phone": "+1234567890",
        "email": "contact@abc.com",
        "created_by": 5,
        "created_at": "2026-06-01T10:00:00.000000Z",
        "creator": {
          "id": 5,
          "first_name": "John",
          "last_name": "Doe"
        },
        "auth_persons": [],
        "followup_details": [],
        "comments": []
      }
    ],
    "path": "http://localhost:8000/api/leads/leads-filter",
    "per_page": 15,
    "next_cursor": "eyJpZCI6MS...",
    "prev_cursor": null,
    "next_page_url": "http://localhost:8000/api/leads/leads-filter?cursor=eyJpZCI6MS...",
    "prev_page_url": null
  }
}
```

---

## Frontend Integration

```javascript
// 1. Load filter options on page mount
const options = await api.get('/api/leads/filter-options');

// 2. Apply filters
const response = await api.post('/api/leads/leads-filter', {
  scope: 'all',
  date_filter: 'this_month',
  category: selectedCategory,
  search: searchText,
  per_page: 15,
});

const leads = response.data.data;
const nextCursor = response.data.next_cursor;

// 3. Load next page
if (nextCursor) {
  await api.post('/api/leads/leads-filter', {
    ...filters,
    cursor: nextCursor,
  });
}
```

---

## cURL Examples

**Get filter options:**
```bash
curl -X GET "http://localhost:8000/api/leads/filter-options" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Filter leads:**
```bash
curl -X POST "http://localhost:8000/api/leads/leads-filter" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "scope": "all",
    "date_filter": "this_month",
    "search": "ABC",
    "per_page": 15
  }'
```

---

## Notes

- Uses `POST` for filter requests (same as appointments and follow-up) to keep filter payloads secure and consistent.
- Unlike `GET /api/leads/all-leads`, the filter API does **not** apply the fixed 3-month window — use `date_filter` instead.
- `GET /api/leads/all-leads` and `GET /api/leads/my-leads` remain available for simple listing without the full filter UI.
- `status` filters against `followup_details.status`, not a column on the business record.

---

## Automated tests

Feature tests live in `tests/Feature/Api/Leads/LeadsFilterTest.php`.

Run:

```bash
php artisan test tests/Feature/Api/Leads/LeadsFilterTest.php
```

Coverage includes filter options, cursor pagination, category/source/search/status filters, `scope=my`, date range, and authentication.
