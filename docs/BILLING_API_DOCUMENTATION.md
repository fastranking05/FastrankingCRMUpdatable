# Billing & Invoices API Documentation

## Overview

The Billing module generates invoices automatically for clients whose **contract is signed**. A **billing setup** is configured once per contract — it picks the proposal's services, and each service gets its own billing type and duration. A scheduled command then generates invoices on the configured dates, carrying forward any unpaid balance from previous invoices.

**Key concepts:**

- One billing setup per contract (`contract_id` unique), only for contracts with `status = "Contract Signed"`
- `invoice_frequency` controls **how often an invoice is generated** (the cadence): `weekly`, `monthly`, `quarterly`, `yearly`
- Each **item** (service) has its own `billing_type` (`one_off` | `recurring`), `service_duration`/`duration_unit`, `start_date`, and computed `end_date`
- `amount`/`vat_amount` on an item is the **per-cycle charge** — a £1000/month recurring item bills £1000 on every invoice while within its service window; a `one_off` item bills its full amount exactly once
- A recurring item bills while `next_bill_date < end_date` (a "2 Months" service starting Nov 1 bills Nov + Dec — 2 invoices)
- Each generated invoice carries `previous_balance` = the unpaid `balance` of all prior invoices for the setup
- Overdue = `balance > 0` and today > `due_date + buffer_days` (`buffer_until`)

## Base URL

```
/api/billing
```

## Authentication & Permissions

JWT required. Uses the **Invoice** module permissions.

| Permission | Description |
|------------|-------------|
| `Invoice,read` | List/view setups and invoices, form helpers, filter options |
| `Invoice,create` | Create billing setups, send invoice emails |
| `Invoice,update` | Update setups, record payments, cancel invoices |
| `Invoice,delete` | Delete setups that have no invoices |

---

## Status Values

| Entity | Values |
|--------|--------|
| Billing setup | `Active`, `Paused`, `Completed` (auto when all items finish billing) |
| Setup item | `Pending` (start_date in future), `Active`, `Completed` |
| Invoice | `Pending` (auto-generated), `Invoice Sent`, `Partially Paid`, `Paid`, `Overdue`, `Cancelled` |

---

## Billing Setup Endpoints

### 1. Form — Signed Contracts (GET)

**Endpoint:** `GET /api/billing/setups/form/contracts`

Signed contracts that do **not** already have a billing setup — for the "create setup" form dropdown.

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Filter by business name |

#### Response

```json
[
  {
    "id": 1,
    "document_id": "cc937903-....",
    "proposal_id": "FRPR00000001",
    "followup_business_id": 15,
    "business_name": "Acme Ltd"
  }
]
```

### 2. Form — Contract Context (GET)

**Endpoint:** `GET /api/billing/setups/form/contracts/{contractId}`

Returns the contract, its business, and the **proposal's services** (amount, VAT, estimation duration/unit) to prefill the billing items.

#### Response

```json
{
  "contract": { "id": 1, "document_id": "...", "status": "Contract Signed", "proposal_id": "FRPR00000001", "followup_business_id": 15 },
  "business": { "id": 15, "name": "Acme Ltd" },
  "services": [
    {
      "service_id": 3,
      "service_name": "SEO Retainer",
      "amount": "1000.00",
      "vat_amount": "200.00",
      "estimation_duration": "12.00",
      "estimation_unit": "Months"
    }
  ]
}
```

### 3. Create Billing Setup (POST)

**Endpoint:** `POST /api/billing/setups`

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `contract_id` | integer | Yes | Contract with `status = "Contract Signed"` and no existing setup |
| `invoice_frequency` | string | Yes | `weekly`, `monthly`, `quarterly`, `yearly` — invoice cadence |
| `billing_day` | integer | No | Day-of-month (1–31) for monthly+, or day-of-week (1–7) for weekly |
| `billing_start_date` | date | Yes | First invoice date — becomes `next_invoice_date` |
| `due_days` | integer | No | Days after invoice date for `due_date` (default `7`) |
| `buffer_days` | integer | No | Grace days after `due_date` before `Overdue` (default `0`) |
| `notes` | string | No | Internal notes |
| `items` | array | Yes | Services to bill (min 1) |
| `items[].service_id` | integer | Yes | Service from `services` table |
| `items[].billing_type` | string | Yes | `one_off` (bills once) or `recurring` (bills every cycle until `end_date`) |
| `items[].amount` | number | Yes | Per-cycle charge (full charge for `one_off`) |
| `items[].vat_amount` | number | Yes | Per-cycle VAT |
| `items[].service_duration` | number | No | e.g. `12` |
| `items[].duration_unit` | string | No | `Days`, `Weeks`, `Months`, `Years` |
| `items[].start_date` | date | Yes | When this service's billing begins — becomes `next_bill_date` |

`end_date` is auto-computed as `start_date + duration` (no duration = open-ended recurring). Item `next_bill_date` starts at `start_date`.

#### Example

```json
{
  "contract_id": 1,
  "invoice_frequency": "monthly",
  "billing_day": 1,
  "billing_start_date": "2026-12-01",
  "due_days": 7,
  "buffer_days": 3,
  "items": [
    { "service_id": 3, "billing_type": "recurring", "amount": 1000, "vat_amount": 200, "service_duration": 12, "duration_unit": "Months", "start_date": "2026-12-01" },
    { "service_id": 5, "billing_type": "one_off", "amount": 5000, "vat_amount": 1000, "service_duration": 10, "duration_unit": "Days", "start_date": "2026-12-01" }
  ]
}
```

#### Errors

| Code | Reason |
|------|--------|
| 422 | Contract not `Contract Signed`, setup already exists, or field validation failed |
| 403 | Missing `Invoice,create` permission |

### 4. List Billing Setups (GET)

**Endpoint:** `GET /api/billing/setups`

| Parameter | Type | Description |
|-----------|------|-------------|
| `contract_id` / `proposal_id` / `followup_business_id` / `status` / `invoice_frequency` / `created_by` | — | Exact-match filters |
| `per_page`, `cursor` | — | Pagination |

### 5. Get Billing Setup (GET)

**Endpoint:** `GET /api/billing/setups/{id}`

Returns the setup with `items` (each with computed `end_date`, `next_bill_date`, `status`), `contract`, `company`, `owner`.

### 6. Update Billing Setup (PUT / POST)

**Endpoints:** `PUT /api/billing/setups/{id}` or `POST /api/billing/setups/{id}`

All fields optional (`sometimes`). Updating `items` replaces the full set — use to pause (`status: "Paused"`), resume, correct dates, or rework the service schedule.

### 7. Delete Billing Setup (DELETE)

**Endpoint:** `DELETE /api/billing/setups/{id}`

If invoices already exist, the setup is marked `Completed` instead of deleted (keeps the audit trail).

---

## Invoice Endpoints

### 8. List Invoices (GET)

**Endpoint:** `GET /api/billing/invoices`

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Invoice ID or business name |
| `billing_setup_id` / `contract_id` / `proposal_id` / `followup_business_id` / `status` / `created_by` | — | Exact-match filters |
| `date_filter`, `date_from`, `date_to`, `date_column` | — | Date filters (`date_column` default `invoice_date`) |
| `per_page`, `cursor` | — | Pagination |

Response includes a `summary` block: `invoice_count`, `total_amount`, `amount_paid`, `balance` for the filtered set.

### 9. Filter Options (GET)

**Endpoint:** `GET /api/billing/invoices/filter-options`

Returns the `statuses` list for filter dropdowns.

### 10. Get Invoice (GET)

**Endpoint:** `GET /api/billing/invoices/{id}`

Full invoice with `items` (lines billed this cycle), `payments` (recorded transactions), `contract`, `company`, `owner`.

### 11. Record Payment (POST)

**Endpoint:** `POST /api/billing/invoices/{id}/payments`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | number | Yes | Payment amount (> 0) |
| `payment_date` | date | No | Defaults to today |
| `method` | string | No | e.g. `Bank Transfer`, `Card`, `Cash` |
| `reference` | string | No | Transaction reference |
| `notes` | string | No | |

Automatically recalculates `amount_paid`, `balance`, and `status` (`Paid` when balance reaches 0, otherwise `Partially Paid`). Cannot be used on `Cancelled` invoices.

### 12. Cancel Invoice (POST)

**Endpoint:** `POST /api/billing/invoices/{id}/cancel`

Only for invoices with **no recorded payments** — sets `status = "Cancelled"` and `balance = 0` so it no longer contributes to `previous_balance`.

### 13. Send Invoice Email (POST)

**Endpoint:** `POST /api/billing/invoices/{id}/send` — `multipart/form-data`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `pdf` | file | Yes | Frontend-generated invoice PDF (max 10MB) |
| `email` | string | No | Recipient — defaults to first business contact's email |
| `auth_person_id` | integer | No | Specific contact for greeting + default recipient |

Marks `sent_at`; `Pending`/`Overdue` invoices become `Invoice Sent`. Logged in `emails` (`type: invoice`).

---

## Automatic Generation

The `invoices:generate` artisan command runs **daily at 06:00** via the scheduler:

```bash
php artisan invoices:generate          # run for today
php artisan invoices:generate --date=2026-12-01   # simulate as-of a date
```

For each active setup where `next_invoice_date <= today`:

1. Bills every item whose `next_bill_date` has arrived and is inside its service window
2. Creates invoice `FRIN00000001`-style with items, `previous_balance` (unpaid balance of prior invoices), `due_date` (`+due_days`), `buffer_until` (`+buffer_days`)
3. Advances each item (`one_off` → `Completed`; `recurring` → next cycle or `Completed` past `end_date`)
4. Advances `next_invoice_date` by the frequency; marks setup `Completed` when all items finish
5. Catches up missed cycles (up to 24 per run)
6. Marks invoices `Overdue` when `buffer_until` has passed with `balance > 0`

> **Requirement:** the scheduler must be running — `php artisan schedule:work` (or system cron `* * * * * php artisan schedule:run`).

## Notes

- `amount` is a **per-cycle** charge — the proposal's service amount is used as-is on every invoice cycle the service is active for
- No proration: an item bills its full per-cycle charge whenever `next_bill_date` arrives within its window
- Dates on items are independent — different services can start and end on different dates while sharing one invoice cadence
- Invoices are never deleted — use **cancel**; payments, overdue state, and previous balances form the transaction history
