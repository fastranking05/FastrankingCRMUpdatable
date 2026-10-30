# Proposals API Documentation

## Overview

The Proposals module manages sales proposals in the `proposals` table. Each proposal is linked to a follow-up business, authorized contact, and one or more services (stored in `proposal_services`).

Proposal IDs are auto-generated in `FRPR00000001` format.

## Base URL

```
/api/proposals
```

## Authentication & Permissions

All endpoints require JWT authentication. Proposals use the **Proposals** module permissions.

| Permission | Description |
|------------|-------------|
| `Proposals,read` | List, filter options, view proposals |
| `Proposals,create` | Create proposals, use form endpoints, send proposals by email |
| `Proposals,update` | Update proposals |
| `Proposals,delete` | Delete proposals |

Departments that previously had Deals access were automatically granted the same Proposals permissions.

---

## API Endpoints

### 1. List Proposals (GET)

**Endpoint:** `GET /api/proposals`

Cursor-paginated list with optional filters and summary totals.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Search email, proposal ID, company, service, or owner |
| `business_id` | integer | No | Filter by follow-up business ID |
| `auth_person_id` | integer | No | Filter by contact ID |
| `service_id` | integer | No | Filter by service ID |
| `created_by` | integer | No | Filter by creator user ID |
| `date_filter` | string | No | Preset date range (see filter-options) |
| `date_from` | date | No | Custom range start (`Y-m-d`) |
| `date_to` | date | No | Custom range end (`Y-m-d`) |
| `date_column` | string | No | `created_at` (default), `updated_at` |
| `per_page` | integer | No | Results per page (default: `15`) |
| `cursor` | string | No | Cursor token for pagination |

#### Example

```bash
GET /api/proposals?search=tech&business_id=12
```

#### Response summary

```json
{
  "summary": {
    "proposal_count": 5,
    "total_amount": 250000,
    "total_vat": 50000,
    "total_value": 300000
  }
}
```

---

### 2. Filter Options (GET)

**Endpoint:** `GET /api/proposals/filter-options`

Returns date filter presets and available date columns.

---

### 3. Form — Businesses (GET)

**Endpoint:** `GET /api/proposals/form/businesses`

Businesses eligible for proposal creation — only businesses that have at least one consultation with `status = conducted` are returned.

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search business name or ID |

---

### 4. Form — Business Context (GET)

**Endpoint:** `GET /api/proposals/form/businesses/{businessId}`

Returns the business, all its linked contacts (auth persons), and a `suggested_email` (first contact's email) for form prefill.

#### Response

```json
{
  "business": {
    "id": 12,
    "name": "Tech Solutions Inc.",
    "category": "Technology",
    "type": "Enterprise",
    "website": "https://example.com"
  },
  "contacts": [
    {
      "id": 45,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "7987654321",
      "job_title": "Manager"
    }
  ],
  "suggested_email": "john@example.com"
}
```

---

### 5. Form — Services (GET)

**Endpoint:** `GET /api/proposals/form/services`

Active services for the proposal form.

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Filter services by name |

---

### 6. Create Proposal (POST)

**Endpoint:** `POST /api/proposals`

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `business_id` | integer | Yes | Follow-up business ID |
| `auth_person_id` | integer | Yes | Contact linked to the business |
| `email` | string | Yes | Proposal recipient email |
| `services` | array | Yes | One or more service line items (min 1) |
| `services[].service_id` | integer | Yes | Service ID from `services` table |
| `services[].amount` | number | Yes | Amount excluding VAT |
| `services[].vat_amount` | number | Yes | VAT amount (e.g. 20%) |

#### Example

```json
{
  "business_id": 12,
  "auth_person_id": 45,
  "email": "contact@example.com",
  "services": [
    { "service_id": 3, "amount": 100000, "vat_amount": 25000 },
    { "service_id": 5, "amount": 50000, "vat_amount": 12500 }
  ]
}
```

---

### 7. Get Proposal (GET)

**Endpoint:** `GET /api/proposals/{id}`

---

### 8. Update Proposal (PUT / POST)

**Endpoints:** `PUT /api/proposals/{id}` or `POST /api/proposals/{id}`

Both update the proposal identically — use `POST` when submitting `multipart/form-data`.

All fields are optional (`sometimes` validation). Relation consistency is re-validated when `business_id` or `auth_person_id` change. When `services` is sent, it replaces the full set of service line items for the proposal.

---

### 9. Send Proposal (POST)

**Endpoint:** `POST /api/proposals/{id}/send`

Emails the proposal document to the recipient. The backend does **not** generate the PDF — send the frontend-generated proposal PDF (same file as "Download PDF") as `multipart/form-data`.

#### Request Body (multipart/form-data)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `pdf` | file | Yes | Proposal PDF generated by the frontend (max 10MB) |
| `email` | string | No | Override recipient email — defaults to proposal's `email`, then contact's primary email |

The send is logged in the `emails` table with type `proposal`.

#### Response

`200 OK` — the proposal object plus `sent_to` (the recipient email used).

| Error | Reason |
|-------|--------|
| 404 | Proposal not found |
| 422 | Missing/invalid `pdf` file, or no recipient email available |
| 500 | Mail server error |

---

### 10. Delete Proposal (DELETE)

**Endpoint:** `DELETE /api/proposals/{id}`

---

## Response Shape

Each proposal includes nested `company`, `contact`, and `owner` objects, a `services` array (each item with its `service`, `amount`, `vat_amount`, and `total_value`), plus proposal-level `total_amount`, `total_vat`, and `total_value` (sums across all service items).

```json
{
  "id": "FRPR00000001",
  "business_id": 12,
  "auth_person_id": 45,
  "email": "contact@example.com",
  "services": [
    {
      "id": 1,
      "service_id": 3,
      "amount": "100000.00",
      "vat_amount": "25000.00",
      "total_value": 125000,
      "service": { "id": 3, "name": "SEO Retainer", "status": true }
    }
  ],
  "total_amount": 100000,
  "total_vat": 25000,
  "total_value": 125000
}
```

## Validation Rules

- The contact must be linked to the selected business via `followup_business_auth_person`
- `services` must contain at least one item; each `service_id` must exist in `services`
