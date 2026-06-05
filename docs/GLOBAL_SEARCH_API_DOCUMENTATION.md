# Global Search API Documentation

## Overview

Unified search across CRM records: businesses, contacts, deals, appointments, users, emails, consultations, SEO audits, and comments.

Uses **Elasticsearch** when installed locally; otherwise searches the **database** automatically (`search_engine` in response).

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
    "search_engine": "database",
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

#### Errors

| Status | Message |
|--------|---------|
| `422` | Invalid search parameters |
| `503` | Elasticsearch required but unreachable (`ELASTICSEARCH_FALLBACK_DATABASE=false`) |
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
    "index_exists": false,
    "fallback_to_database": true,
    "search_engine": "database",
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

Rebuilds the search index from the database.

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
| `503` | Elasticsearch not reachable |
| `500` | Reindex failed |
