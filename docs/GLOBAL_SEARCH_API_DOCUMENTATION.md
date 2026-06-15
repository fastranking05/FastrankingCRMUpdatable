# Global Search API Documentation

## Overview

Unified search across CRM records: businesses, contacts, deals, appointments, users, emails, consultations, SEO audits, and comments.

Uses **Typesense** (via Laravel Scout) when a self-hosted Typesense server is available; otherwise searches the **database** automatically (`search_engine` in response).

## Base URL

```
/api/search
```

## Authentication

All endpoints require JWT:

```
Authorization: Bearer {token}
```

---

## Entity types (`types` filter)

| Value | Label |
|-------|-------|
| `business` | Business / Lead |
| `contact` | Contact |
| `deal` | Deal |
| `appointment` | Appointment |
| `user` | User |
| `email` | Email |
| `consultation` | Consultation |
| `seo_audit` | SEO Audit |
| `comment` | Comment |

---

## Setup (Self-Hosted Typesense)

1. Install and run Typesense locally or on your server ([Typesense install guide](https://typesense.org/docs/guide/install-typesense.html)).
2. Configure `.env`:

```env
GLOBAL_SEARCH_ENABLED=true
GLOBAL_SEARCH_FALLBACK_DATABASE=true
GLOBAL_SEARCH_COLLECTION=fastranking_global_search
SCOUT_DRIVER=typesense
TYPESENSE_API_KEY=your-api-key
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108
TYPESENSE_PROTOCOL=http
```

3. Run migrations and build the index:

```bash
php artisan migrate
php artisan search:reindex --fresh
```

---

## API Endpoints

### 1. Global Search (GET)

**Endpoint:** `GET /api/search`

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `q` | string | Yes | — | Search term (min 2, max 255) |
| `types` | array | No | all | Filter types: `types[]=deal&types[]=business` |
| `page` | integer | No | `1` | Page number |
| `limit` | integer | No | `20` | Per page (max `100`) |

#### Request Example

```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://127.0.0.1:8000/api/search?q=tech&types[]=deal&types[]=business&page=1&limit=20"
```

#### Response Example

```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": {
    "query": "tech",
    "total": 5,
    "page": 1,
    "limit": 20,
    "total_pages": 1,
    "search_engine": "typesense",
    "counts_by_type": {
      "business": 2,
      "deal": 3
    },
    "available_types": [
      { "key": "business", "label": "Business / Lead" },
      { "key": "deal", "label": "Deal" }
    ],
    "results": [
      {
        "entity_type": "deal",
        "entity_type_label": "Deal",
        "entity_id": "FRDID00000001",
        "title": "Tech Solutions Inc.",
        "subtitle": "Acme Corp • Proposal Sent",
        "route": "/deals/FRDID00000001",
        "metadata": {
          "deal_id": "FRDID00000001",
          "followup_business_id": 12,
          "deal_stage": "Proposal Sent"
        },
        "score": 8.42,
        "highlight": {
          "title": ["<mark>Tech</mark> Solutions Inc."]
        }
      }
    ]
  }
}
```

When Typesense is unavailable and `GLOBAL_SEARCH_FALLBACK_DATABASE=true`, `search_engine` is `"database"` and `score` / `highlight` may be empty.

#### Errors

| Status | Message |
|--------|---------|
| `422` | Invalid search parameters |
| `503` | Typesense required but unreachable (`GLOBAL_SEARCH_FALLBACK_DATABASE=false`) |
| `500` | Search failed |

---

### 2. Search Status (GET)

**Endpoint:** `GET /api/search/status`

#### Request Example

```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://127.0.0.1:8000/api/search/status"
```

#### Response Example

```json
{
  "success": true,
  "message": "Search status retrieved successfully",
  "data": {
    "enabled": true,
    "connected": true,
    "index": "fastranking_global_search",
    "index_exists": true,
    "fallback_to_database": true,
    "search_engine": "typesense",
    "entity_types": [
      { "key": "business", "label": "Business / Lead" },
      { "key": "deal", "label": "Deal" }
    ]
  }
}
```

---

### 3. Reindex (POST)

**Endpoint:** `POST /api/search/reindex`

Rebuilds the search index from the database via Laravel Scout.

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `fresh` | boolean | No | `false` | Delete and recreate index when `true` |

#### Request Example

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://127.0.0.1:8000/api/search/reindex?fresh=1"
```

#### Response Example

```json
{
  "success": true,
  "message": "Global search index rebuilt successfully",
  "data": {
    "indexed_counts": {
      "business": 150,
      "contact": 320,
      "deal": 85,
      "appointment": 200,
      "user": 25,
      "email": 90,
      "consultation": 45,
      "seo_audit": 30,
      "comment": 500,
      "total": 1445
    }
  }
}
```

#### Errors

| Status | Message |
|--------|---------|
| `422` | Invalid reindex parameters |
| `503` | Typesense not reachable |
| `500` | Reindex failed |

---

## Indexing

CRM records are synced to Typesense automatically when created, updated, or deleted (via model observers and Laravel Scout).

Manual reindex:

```bash
php artisan search:reindex
php artisan search:reindex --fresh
```

Or use `POST /api/search/reindex`.
