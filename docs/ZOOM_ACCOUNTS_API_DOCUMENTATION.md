# Zoom Accounts API Documentation

## Overview

Zoom Accounts manage Zoom OAuth credentials and webhook configuration stored in the `zoom_accounts` table. These records are used to connect the CRM with Zoom services (meetings, webhooks, etc.).

All operations require **Administration** module permissions via department-based access control.

## Base URL

`/api/zoom-accounts`

## Authentication

All endpoints require JWT:

```http
Authorization: Bearer {token}
```

## Permission Model

| Endpoint | Required permission |
|----------|---------------------|
| `GET /api/zoom-accounts` | `Administration,read` |
| `GET /api/zoom-accounts/{id}` | `Administration,read` |
| `POST /api/zoom-accounts` | `Administration,create` |
| `PUT /api/zoom-accounts/{id}` | `Administration,update` |
| `DELETE /api/zoom-accounts/{id}` | `Administration,delete` |

Users must belong to an active department that has the matching permission on the **Administration** module in `module_department`.

---

## Database: `zoom_accounts`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `username` | string | Zoom User ID or Zoom login email (must exist as a licensed user in Zoom) |
| `account_name` | string | Display name for the account |
| `account_id` | string | Zoom account ID (Server-to-Server OAuth) |
| `client_id` | string | Zoom OAuth client ID |
| `client_secret` | text | Zoom OAuth client secret |
| `secret_token` | text | Zoom webhook secret token |
| `email` | string | Zoom user email — must match assigned sales user CRM email and exist in Zoom |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Record last update time |

---

## 1. List Zoom Accounts

**GET** `/api/zoom-accounts`

**Required permission:** `Administration,read`

Results are ordered by `account_name` ascending.

**Response (200):**

```json
{
  "success": true,
  "message": "Zoom accounts retrieved successfully",
  "data": [
    {
      "id": 1,
      "username": "zoom_user",
      "account_name": "Main Zoom Account",
      "account_id": "abc123xyz",
      "client_id": "client_id_here",
      "email": "zoom@example.com",
      "created_at": "2026-06-22T10:00:00.000000Z",
      "updated_at": "2026-06-22T10:00:00.000000Z"
    }
  ]
}
```

> **Note:** `client_secret` and `secret_token` are hidden from all API responses for security.

---

## 2. Create Zoom Account

**POST** `/api/zoom-accounts`

**Required permission:** `Administration,create`

**Request body:**

```json
{
  "username": "zoom_user",
  "account_name": "Main Zoom Account",
  "account_id": "abc123xyz",
  "client_id": "client_id_here",
  "client_secret": "client_secret_here",
  "secret_token": "secret_token_here",
  "email": "zoom@example.com"
}
```

**Validation rules:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `username` | string | Yes | Zoom User ID or Zoom login email for meeting host (must exist in Zoom) |
| `account_name` | string | Yes | Display name (max 255) |
| `account_id` | string | Yes | Zoom account ID for S2S OAuth (max 255) |
| `client_id` | string | Yes | OAuth client ID (max 255) |
| `client_secret` | string | Yes | OAuth client secret |
| `secret_token` | string | Yes | Webhook secret token |
| `email` | string | Yes | Zoom user email (must match assigned sales user CRM email and exist in Zoom) |

**Response (201):**

```json
{
  "success": true,
  "message": "Zoom account created successfully",
  "data": {
    "id": 1,
    "username": "zoom_user",
    "account_name": "Main Zoom Account",
    "account_id": "abc123xyz",
    "client_id": "client_id_here",
    "email": "zoom@example.com",
    "created_at": "2026-06-22T10:00:00.000000Z",
    "updated_at": "2026-06-22T10:00:00.000000Z"
  }
}
```

---

## 3. Get Zoom Account

**GET** `/api/zoom-accounts/{id}`

**Required permission:** `Administration,read`

**Response (200):**

```json
{
  "success": true,
  "message": "Zoom account retrieved successfully",
  "data": {
    "id": 1,
    "username": "zoom_user",
    "account_name": "Main Zoom Account",
    "account_id": "abc123xyz",
    "client_id": "client_id_here",
    "email": "zoom@example.com",
    "created_at": "2026-06-22T10:00:00.000000Z",
    "updated_at": "2026-06-22T10:00:00.000000Z"
  }
}
```

---

## 4. Update Zoom Account

**PUT** `/api/zoom-accounts/{id}`

**Required permission:** `Administration,update`

**Request body:**

```json
{
  "username": "zoom_user_updated",
  "account_name": "Updated Zoom Account",
  "account_id": "abc123xyz",
  "client_id": "new_client_id",
  "email": "updated@example.com"
}
```

**Validation rules:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `username` | string | Yes | Zoom username (max 255) |
| `account_name` | string | Yes | Display name (max 255) |
| `account_id` | string | Yes | Zoom account ID (max 255) |
| `client_id` | string | Yes | OAuth client ID (max 255) |
| `client_secret` | string | No | OAuth client secret — omit to keep existing value |
| `secret_token` | string | No | Webhook secret token — omit to keep existing value |
| `email` | string | Yes | Valid email (max 255) |

**Update secrets only when needed:**

```json
{
  "username": "zoom_user",
  "account_name": "Main Zoom Account",
  "account_id": "abc123xyz",
  "client_id": "client_id_here",
  "client_secret": "new_client_secret",
  "secret_token": "new_secret_token",
  "email": "zoom@example.com"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Zoom account updated successfully",
  "data": {
    "id": 1,
    "username": "zoom_user_updated",
    "account_name": "Updated Zoom Account",
    "account_id": "abc123xyz",
    "client_id": "new_client_id",
    "email": "updated@example.com",
    "created_at": "2026-06-22T10:00:00.000000Z",
    "updated_at": "2026-06-22T10:30:00.000000Z"
  }
}
```

---

## 5. Delete Zoom Account

**DELETE** `/api/zoom-accounts/{id}`

**Required permission:** `Administration,delete`

**Response (200):**

```json
{
  "success": true,
  "message": "Zoom account deleted successfully",
  "data": null
}
```

---

## Error Responses

### Validation Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field must be a valid email address."]
  }
}
```

### Unauthorized (401)

```json
{
  "success": false,
  "message": "Token not provided"
}
```

### Forbidden (403)

```json
{
  "success": false,
  "message": "Permission denied. You do not have read access for Administration. Contact administrator."
}
```

### Not Found (404)

Returned when `{id}` does not exist (Laravel default 404 response).

---

## cURL Examples

### Create zoom account

```bash
curl -X POST http://127.0.0.1:8000/api/zoom-accounts \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "zoom_user",
    "account_name": "Main Zoom Account",
    "account_id": "abc123xyz",
    "client_id": "client_id_here",
    "client_secret": "client_secret_here",
    "secret_token": "secret_token_here",
    "email": "zoom@example.com"
  }'
```

### List all zoom accounts

```bash
curl -X GET http://127.0.0.1:8000/api/zoom-accounts \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Get single zoom account

```bash
curl -X GET http://127.0.0.1:8000/api/zoom-accounts/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Update zoom account (without changing secrets)

```bash
curl -X PUT http://127.0.0.1:8000/api/zoom-accounts/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "zoom_user_updated",
    "account_name": "Updated Zoom Account",
    "account_id": "abc123xyz",
    "client_id": "new_client_id",
    "email": "updated@example.com"
  }'
```

### Update zoom account secrets

```bash
curl -X PUT http://127.0.0.1:8000/api/zoom-accounts/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "zoom_user",
    "account_name": "Main Zoom Account",
    "account_id": "abc123xyz",
    "client_id": "client_id_here",
    "client_secret": "new_client_secret",
    "secret_token": "new_secret_token",
    "email": "zoom@example.com"
  }'
```

### Delete zoom account

```bash
curl -X DELETE http://127.0.0.1:8000/api/zoom-accounts/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Testing Checklist

- [ ] Create account with valid data
- [ ] Create account with missing required fields (should fail with 422)
- [ ] Create account with invalid email (should fail with 422)
- [ ] List all accounts
- [ ] Get single account by ID
- [ ] Update account without changing secrets (existing secrets preserved)
- [ ] Update account with new secrets
- [ ] Delete account
- [ ] Get deleted account (should fail with 404)
- [ ] Test without JWT token (should fail with 401)
- [ ] Test without Administration permission (should fail with 403)
- [ ] Verify `client_secret` and `secret_token` are never returned in responses

---

## Database Verification

```sql
SELECT id, username, account_name, account_id, client_id, email, created_at, updated_at
FROM zoom_accounts;
```

---

## Security Notes

- `client_secret` and `secret_token` are stored in the database but **never exposed** in API JSON responses (model `$hidden` attribute).
- On update, secrets are only changed when explicitly provided in the request body.
- All endpoints require JWT authentication and Administration module permissions — there is no admin bypass for module permissions.

## Zoom Marketplace App Scopes (Server-to-Server OAuth)

For quality submission meeting creation, enable these scopes on your Zoom Marketplace app:

| Scope | Required | Purpose |
|-------|----------|---------|
| `meeting:write:admin` or `meeting:write` | Yes | Create scheduled meetings |
| `user:read:admin` or `user:read` | Recommended | Resolve host user by email from Zoom directory |

Meeting creation uses **`zoom_accounts.email` only** (not `username`) to verify and host the Zoom meeting:

1. Match assigned sales user → `zoom_accounts.email` (case-insensitive)
2. Look up Zoom user in directory by `zoom_accounts.email`
3. Create meeting with resolved user ID, then `zoom_accounts.email`, then `/users/me/meetings`
