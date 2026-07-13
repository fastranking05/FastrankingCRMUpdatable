# Proposals API Documentation

## Overview

The Proposals module manages sales proposals in the `proposals` table. Each proposal is linked to a deal, follow-up business, authorized contact, and service.

Proposal IDs are auto-generated in `FRPR00000001` format.

## Base URL

```
/api/proposals
```

## Authentication & Permissions

All endpoints require JWT authentication. Proposals use the **Deals** module permissions — no separate Proposals module is required.

| Permission | Description |
|------------|-------------|
| `Deals,read` | List, filter options, view proposals |
| `Deals,create` | Create proposals and use form endpoints |
| `Deals,update` | Update proposals |
| `Deals,delete` | Delete proposals |

If a user/department already has Deals access, they can use all proposal endpoints with the same permission level.

---

## API Endpoints

### 1. List Proposals (GET)

**Endpoint:** `GET /api/proposals`

Cursor-paginated list with optional filters and summary totals.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Search email, proposal ID, company, deal, service, or owner |
| `business_id` | integer | No | Filter by follow-up business ID |
| `auth_person_id` | integer | No | Filter by contact ID |
| `deal_id` | string | No | Filter by deal ID |
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

### 3. Form — Deals (GET)

**Endpoint:** `GET /api/proposals/form/deals`

Deals available when creating a proposal.

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search deal name, ID, or company |
| `business_id` | integer | Filter deals by business |

---

### 4. Form — Deal Context (GET)

**Endpoint:** `GET /api/proposals/form/deals/{dealId}`

Returns deal, business, contact, and suggested email for form prefill.

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
| `business_id` | integer | Yes | Must match the selected deal's business |
| `auth_person_id` | integer | Yes | Must be linked to the business and match deal contact when set |
| `deal_id` | string | Yes | Existing deal ID |
| `email` | string | Yes | Proposal recipient email |
| `service_id` | integer | Yes | Service ID from `services` table |
| `amount` | number | Yes | Amount excluding VAT |
| `vat_amount` | number | Yes | VAT amount |

#### Example

```json
{
  "business_id": 12,
  "auth_person_id": 45,
  "deal_id": "FRDID00000001",
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

All fields are optional (`sometimes` validation). Relation consistency is re-validated when `business_id`, `auth_person_id`, or `deal_id` change.

---

### 9. Delete Proposal (DELETE)

**Endpoint:** `DELETE /api/proposals/{id}`

---

## Response Shape

Each proposal includes nested `company`, `contact`, `deal`, `service`, and `owner` objects, plus computed `total_value` (`amount + vat_amount`).

## Validation Rules

- `business_id`, `auth_person_id`, and `deal_id` must be consistent with each other
- The contact must be linked to the selected business via `followup_business_auth_person`
- If the deal has an `auth_person_id`, the proposal contact must match
