# Deals API Documentation

## Overview

The Deals module manages the sales pipeline using the `deals` table. Each deal is linked to a follow-up business, optional authorized contact, and owner (`created_by`).

When creating or updating a deal, optional `comments` are saved to the shared `comments` table using `followup_business_id` — the same pattern used by Consultation and Appointment APIs.

### Deal stages (UI pipeline tabs)

| Stage |
|-------|
| New Deal Created |
| Proposal Sent |
| Negotation |
| Contact Sent |
| Closed Won |
| Closed Lost |
| On Hold |

New deals default to **New Deal Created** when `deal_stage` is omitted.

## Base URL

```
/api/deals
```

## Authentication & Permissions

All endpoints require JWT authentication.

| Permission | Description |
|------------|-------------|
| `Deals,read` | List by deal_stage filter, filter options, view |
| `Deals,create` | Create deals with comments |
| `Deals,update` | Update deals and append comments |
| `Deals,delete` | Delete deals |

Register the module:

```bash
php artisan db:seed --class=DealsModuleSeeder
```

---

## API Endpoints

### 1. List Deals (GET)

**Endpoint:** `GET /api/deals`

Cursor-paginated list filtered by `deal_stage`. Har UI tab ke liye sirf `deal_stage` query param bhejo — alag summary endpoint ki zaroorat nahi.

#### Deal stage filter (required for tabs)

UI tabs ke liye in **7 exact values** mein se ek bhejo:

| `deal_stage` value |
|--------------------|
| `New Deal Created` |
| `Proposal Sent` |
| `Negotation` |
| `Contact Sent` |
| `Closed Won` |
| `Closed Lost` |
| `On Hold` |

Galat value par `422` validation error aayega.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `deal_stage` | string | **Yes (for tab view)** | One of the seven values above |
| `search` | string | No | Search deal name, service, source, company name, or owner |
| `created_by` | integer | No | Filter by deal owner user ID |
| `followup_business_id` | integer | No | Filter by business ID |
| `priority` | string | No | Filter by priority |
| `date_filter` | string | No | Preset date range (see filter-options) |
| `date_from` | date | No | Custom range start (`Y-m-d`) |
| `date_to` | date | No | Custom range end (`Y-m-d`) |
| `date_column` | string | No | `created_at` (default), `updated_at`, `estimated_closed_date` |
| `per_page` | integer | No | Results per page (default: `15`) |
| `cursor` | string | No | Cursor token for pagination |

#### Tab examples

```bash
# Proposal Sent tab
GET /api/deals?deal_stage=Proposal Sent

# Closed Won tab with search
GET /api/deals?deal_stage=Closed Won&search=Tech

# On Hold tab
GET /api/deals?deal_stage=On Hold
```

#### Request Example

```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://127.0.0.1:8000/api/deals?deal_stage=Proposal%20Sent&search=Tech"
```

#### Response Example

```json
{
  "success": true,
  "message": "Deals retrieved successfully",
  "data": {
    "data": [
      {
        "id": "FRDID00000001",
        "name": "Tech Solutions Inc.",
        "selected_service": "Custom AI Framework",
        "type": "Referral",
        "source": "Referral",
        "deal_stage": "Proposal Sent",
        "lost_reason": null,
        "probability": null,
        "estimated_closed_date": "2026-06-15T00:00:00.000000Z",
        "amount_exc_vat": "100000.00",
        "vat": "25000.00",
        "value": 125000,
        "next_activity": null,
        "priority": null,
        "followup_business_id": 5720,
        "auth_person_id": 101,
        "created_by": 4,
        "created_at": "2026-03-01T10:00:00.000000Z",
        "updated_at": "2026-03-01T10:00:00.000000Z",
        "company": {
          "id": 5720,
          "name": "Tech Solutions Inc.",
          "category": "Technology",
          "type": "Enterprise",
          "website": null,
          "phone": null,
          "email": null
        },
        "contact": {
          "id": 101,
          "title": null,
          "firstname": "David",
          "middlename": null,
          "lastname": "Chen",
          "name": "David Chen",
          "email": "david.chen@techsolutions.com",
          "phone": "+1 555-0101",
          "job_title": null
        },
        "owner": {
          "id": 4,
          "first_name": "Sandeep",
          "last_name": "Singh",
          "email": null,
          "username": "sandeep"
        }
      }
    ],
    "path": "http://127.0.0.1:8000/api/deals",
    "per_page": 15,
    "next_cursor": null,
    "prev_cursor": null,
    "next_page_url": null,
    "prev_page_url": null,
    "summary": {
      "deal_stage": "Proposal Sent",
      "deal_count": 2,
      "total_value": 173000
    }
  }
}
```

### 2. Filter Options (GET)

**Endpoint:** `GET /api/deals/filter-options`

Returns allowed `deal_stages` (same 7 values), date filters, date columns, and priority options.

---

### 3. Create Form — Eligible Businesses (GET)

**Endpoint:** `GET /api/deals/form/businesses`  
**Permission:** `Deals,create`

Create Deal modal ke **Select Business Name** dropdown ke liye.

Sirf woh businesses aati hain jinki **latest consultation** (per appointment) ye conditions match karti ho:

| Field | Required value |
|-------|----------------|
| `status` | `conducted` |
| `custom_status` | `Conducted Offered` |

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Business name search |

#### Request Example

```bash
GET /api/deals/form/businesses
GET /api/deals/form/businesses?search=Tech
```

#### Response Example

```json
{
  "success": true,
  "message": "Eligible businesses retrieved successfully",
  "data": [
    {
      "id": 5720,
      "name": "Tech Solutions Inc.",
      "category": "Technology",
      "type": "Enterprise",
      "website": null,
      "phone": null,
      "email": null
    }
  ]
}
```

---

### 4. Create Form — Business Contacts (GET)

**Endpoint:** `GET /api/deals/form/businesses/{followupBusinessId}/auth-persons`  
**Permission:** `Deals,create`

Business select hone ke baad **Select Person Name** dropdown ke liye. Sirf us business ke linked auth persons aate hain (`followup_business_auth_person` pivot se).

Business eligible nahi hai to `404`.

#### Response Example

```json
{
  "success": true,
  "message": "Business contacts retrieved successfully",
  "data": [
    {
      "id": 101,
      "title": null,
      "firstname": "David",
      "middlename": null,
      "lastname": "Chen",
      "name": "David Chen",
      "email": "david.chen@techsolutions.com",
      "phone": "+1 555-0101",
      "job_title": null
    }
  ]
}
```

---

### 5. Create Deal (POST)

**Endpoint:** `POST /api/deals`

Creates a deal and optionally saves business comments.

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `followup_business_id` | integer | Yes | Business ID |
| `auth_person_id` | integer | No | Contact from `followup_auth_persons` |
| `name` | string | Yes | Deal name |
| `type` | string | No | Source (UI "Source" column, e.g. Referral) |
| `deal_stage` | string | No | Defaults to `New Deal Created` |
| `lost_reason` | string | No | Reason when closed lost |
| `probability` | number | No | 0–100 |
| `estimated_closed_date` | date | No | Expected close date |
| `selected_service` | string | No | Service subtitle under deal name |
| `amount_exc_vat` | number | No | Amount excluding VAT |
| `vat` | number | No | VAT amount |
| `next_activity` | string | No | Next planned activity |
| `priority` | string | No | Low, Medium, High, Urgent |
| `comments` | array | No | Business comments to save |

Each comment object:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `comment` | string | Yes | Comment text |
| `old_status` | string | No | Previous status |
| `new_status` | string | No | Defaults to deal stage |

#### Request Example

```json
{
  "followup_business_id": 5720,
  "auth_person_id": 101,
  "name": "Tech Solutions Inc.",
  "type": "Referral",
  "selected_service": "Custom AI Framework",
  "amount_exc_vat": 100000,
  "vat": 25000,
  "estimated_closed_date": "2026-06-15",
  "comments": [
    {
      "comment": "Deal created after successful consultation.",
      "new_status": "New Deal Created"
    }
  ]
}
```

#### Response

`201 Created` with the deal object (`value` = `amount_exc_vat + vat`).

---

### 6. Get Deal (GET)

**Endpoint:** `GET /api/deals/{id}`

Returns a single deal by ID (e.g. `FRDID00000001`) including business comments.

---

### 7. Update Deal (PUT)

**Endpoint:** `PUT /api/deals/{id}`

Updates deal fields. Optional `comments` array appends new business comments (same structure as create).

---

### 8. Delete Deal (DELETE)

**Endpoint:** `DELETE /api/deals/{id}`

Permanently deletes the deal record. Business comments are not deleted.

---

## Deal ID Format

Deal IDs are auto-generated strings: `FRDID` + 8-digit number (e.g. `FRDID00000001`).

## Value Calculation

UI **Value** column = `amount_exc_vat + vat` (nulls treated as 0).

## Related Documentation

- [Consultation API](./CONSULTATION_API_DOCUMENTATION.md) — comments pattern reference
- [API Endpoints Index](./API_ENDPOINTS_DOCUMENTATION.md)
