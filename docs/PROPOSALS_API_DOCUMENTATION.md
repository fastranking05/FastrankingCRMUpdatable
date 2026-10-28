# Proposals API Documentation

## Overview

The Proposals module manages sales proposals in the `proposals` table. Each proposal is linked to a follow-up business, authorized contact, and service.

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
| `Proposals,create` | Create proposals and use form endpoints |
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

Businesses available when creating a proposal.

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search business name or ID |

---

### 4. Form — Business Context (GET)

**Endpoint:** `GET /api/proposals/form/businesses/{businessId}`

Returns the business, its linked contacts, and a suggested email for form prefill.

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
| `service_id` | integer | Yes | Service ID from `services` table |
| `amount` | number | Yes | Amount excluding VAT |
| `vat_amount` | number | Yes | VAT amount |

#### Example

```json
{
  "business_id": 12,
  "auth_person_id": 45,
  "email": "contact@example.com",
  "service_id": 3,
  "amount": 100000,
  "vat_amount": 25000
}
```

---

### 7. Get Proposal (GET)

**Endpoint:** `GET /api/proposals/{id}`

---

### 8. Update Proposal (PUT)

**Endpoint:** `PUT /api/proposals/{id}`

All fields are optional (`sometimes` validation). Relation consistency is re-validated when `business_id` or `auth_person_id` change.

---

### 9. Delete Proposal (DELETE)

**Endpoint:** `DELETE /api/proposals/{id}`

---

## Response Shape

Each proposal includes nested `company`, `contact`, `service`, and `owner` objects, plus computed `total_value` (`amount + vat_amount`).

## Validation Rules

- The contact must be linked to the selected business via `followup_business_auth_person`
