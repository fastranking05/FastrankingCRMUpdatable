# AI Chat API Documentation

## Overview

Global CRM chatbot powered by **Ollama** (local, free). Logged-in users can ask questions in natural language about any CRM data they are allowed to access. Responses are in **English** by default (`AI_CHAT_LANGUAGE=en`).

- **One endpoint** for all modules — no per-module or per-controller integration
- Answers are scoped to the user's **role hierarchy** (admin / manager / executive)
- Data is filtered by **module read permissions** (`can_read` on each module)
- The LLM only formats answers; Laravel fetches and filters all data first

## Base URL

```
/api/chat
```

## Authentication

All endpoints require JWT:

```
Authorization: Bearer {token}
```

Obtain a token via `POST /api/login`.

---

## How It Works

```
User message → GlobalChatDataService → Scoped CRM data → Ollama → Natural language answer
```

On every chat request, the backend:

1. Identifies the logged-in user from JWT
2. Resolves hierarchy scope (`admin`, `manager`, or `executive`)
3. Loads all modules the user has `can_read` permission for
4. Fetches **summaries** (record counts per entity type)
5. Fetches either:
   - **Search results** — when the message contains a meaningful keyword/name, or
   - **Recent records** — when the message is generic (e.g. summary, counts)
6. Sends filtered data to Ollama and returns the generated answer

The AI never queries the database directly and cannot see data outside the user's scope.

---

## Access Levels (Hierarchy)

| Level | Who | Data scope |
|-------|-----|------------|
| `admin` | Admin / SuperAdmin role | All records |
| `manager` | Manager with team membership | Own records + team members' records |
| `executive` | Default for other users | Own records only |

Scoping uses `created_by` for most entities. SEO audits and quality audits use `assigned_user`.

---

## Supported Entities & Modules

| Entity type | Label | Required module (`can_read`) | Scope column |
|-------------|-------|------------------------------|--------------|
| `business` | Business / Lead | Leads | `created_by` |
| `contact` | Contact | Follow-Up | `created_by` |
| `deal` | Deal | Deals | `created_by` |
| `appointment` | Appointment | Appointment | `created_by` |
| `email` | Email | Email | `created_by` |
| `consultation` | Consultation | Consultation | `created_by` |
| `seo_audit` | SEO Audit | SEO | `assigned_user` |
| `comment` | Comment | Leads | `created_by` |
| `user` | User | Administration | `id` |

**Extra summaries** (counts only, no search index):

| Summary | Required module |
|---------|-----------------|
| Follow-Ups (with details) | Follow-Up |
| Quality Audits | Quality Control |

---

## Environment Variables

Add to `.env`:

```env
AI_CHAT_ENABLED=true
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=qwen2.5:7b
OLLAMA_TIMEOUT=120
```

| Variable | Default | Description |
|----------|---------|-------------|
| `AI_CHAT_ENABLED` | `false` | Enable or disable chat endpoints |
| `OLLAMA_HOST` | `http://127.0.0.1:11434` | Ollama API base URL |
| `OLLAMA_MODEL` | `qwen2.5:7b` | Model name (`ollama pull <model>`) |
| `OLLAMA_TIMEOUT` | `120` | HTTP timeout in seconds for Ollama requests |
| `AI_CHAT_LANGUAGE` | `en` | Reply language: `en` (English), `hi` (Hindi), or `hinglish` |

**Prerequisites:**

1. Install Ollama: https://ollama.com/download
2. Pull a model: `ollama pull qwen2.5:7b`
3. Ensure Ollama is running (`curl http://127.0.0.1:11434/api/tags`)

---

## API Endpoints

### 1. Chat (POST)

Send a message and receive an AI-generated answer based on CRM data the user can access.

**Endpoint:** `POST /api/chat`

#### Request Body

| Field | Type | Required | Constraints | Description |
|-------|------|----------|-------------|-------------|
| `message` | string | Yes | min `2`, max `2000` | User question in natural language |

#### Request Example

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message":"Mere CRM ka summary batao"}' \
  "http://127.0.0.1:8000/api/chat"
```

#### Response Example

```json
{
  "success": true,
  "message": "Chat response generated successfully",
  "data": {
    "answer": "Your CRM has 12 leads, 5 deals, and 2 appointments today. This data is within your manager access level.",
    "access_level": "manager",
    "readable_modules": [
      "Leads",
      "Deals",
      "Appointment",
      "Email",
      "Follow-Up"
    ],
    "model": "qwen2.5:7b"
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `answer` | string | Natural language response from Ollama |
| `access_level` | string | User hierarchy level: `admin`, `manager`, or `executive` |
| `readable_modules` | array | Module names the user has `can_read` on |
| `model` | string | Ollama model used for this response |

#### Errors

| Status | Message | Cause |
|--------|---------|-------|
| `401` | Unauthorized | Missing or invalid JWT |
| `422` | Validation failed | `message` missing, too short, or too long |
| `503` | AI chat is disabled… | `AI_CHAT_ENABLED=false` |
| `503` | Ollama is not running… | Ollama app not started |
| `500` | Chat failed | Unexpected server error |

---

### 2. Chat Status (GET)

Check whether AI chat is enabled and Ollama is reachable.

**Endpoint:** `GET /api/chat/status`

#### Request Example

```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://127.0.0.1:8000/api/chat/status"
```

#### Response Example

```json
{
  "success": true,
  "message": "Chat status retrieved successfully",
  "data": {
    "enabled": true,
    "ollama_reachable": true,
    "model": "qwen2.5:7b",
    "host": "http://127.0.0.1:11434"
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `enabled` | boolean | Value of `AI_CHAT_ENABLED` |
| `ollama_reachable` | boolean | Whether Ollama HTTP API responds |
| `model` | string | Configured Ollama model |
| `host` | string | Configured Ollama host URL |

---

## Example Questions

| Question | What the backend uses |
|----------|----------------------|
| `"Mere CRM ka summary batao"` | Summaries + recent records |
| `"Kitne leads hain?"` | Summaries |
| `"Tech company dhundho"` | Scoped search across allowed entities |
| `"Acme ke deals aur appointments?"` | Scoped search |
| `"Mujhe kya access hai?"` | Access info + readable modules |
| `"Aaj ke appointments kitne hain?"` | Summaries + recent/search |
| `"Show my deals in Proposal Sent stage"` | Scoped search + summaries |

The assistant always replies in English (or the language set in `AI_CHAT_LANGUAGE`), regardless of the question language.

---

## Internal Data Flow (Reference)

Data passed to Ollama (not returned in the API response) looks like:

```json
{
  "access": {
    "user": { "id": 1, "name": "Rahul Sharma", "user_type": "admin" },
    "scope": {
      "access_level": "manager",
      "allowed_user_ids": [1, 5, 12],
      "role_names": ["Manager"],
      "team_names": ["Sales Team A"],
      "department_names": ["Lead Generation"]
    },
    "readable_modules": ["Leads", "Deals", "Appointment"],
    "module_permissions": [{ "module": "Leads", "can_read": true }]
  },
  "summaries": {
    "Business / Lead": 12,
    "Deal": 5,
    "Appointment": 8
  },
  "search": {
    "query": "tech",
    "results": [
      {
        "entity_type": "deal",
        "entity_type_label": "Deal",
        "entity_id": "FRDID00000001",
        "title": "Tech Solutions",
        "subtitle": "Acme Corp • Proposal Sent",
        "metadata": {}
      }
    ]
  }
}
```

When no search keyword is detected, `recent_records` is included instead of `search`.

---

## Backend File Structure

```
config/ai.php

app/Services/AI/
  OllamaClient.php
  UserDataScopeService.php
  GlobalChatDataService.php
  ScopedChatSearchService.php
  CrmChatService.php
  Data/UserDataScope.php

app/Http/Controllers/Api/Chat/
  ChatController.php

routes/api/chat.php
```

---

## Security Notes

- JWT is required on every request
- Module `can_read` permission is checked per entity type
- Role hierarchy filters all queries before data reaches Ollama
- The LLM does not receive database credentials or raw SQL
- If a user lacks permission for a module, the assistant reports that — it does not leak data from other modules

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| `503` Ollama is not running | Start the Ollama app or run `ollama serve` |
| `503` AI chat is disabled | Set `AI_CHAT_ENABLED=true` in `.env` |
| Slow responses (30s+) | Use a smaller model, e.g. `llama3.2:3b` |
| `ERR_CONNECTION_REFUSED` on `/api/chat` | Laravel server crashed — restart `php artisan serve` (see note below) |
| Empty or wrong answers | Confirm the user has `can_read` on the relevant module |
| Manager sees only own data | Verify user has teams assigned and Manager + Lead Generation role/dept |

### `ERR_CONNECTION_REFUSED` after chat request

If `/api/chat/status` works but `/api/chat` fails with **connection refused**, the Laravel dev server likely **crashed** because Ollama took longer than PHP’s 30s default timeout.

**Fix:**

1. Restart Laravel: `php artisan serve`
2. Ensure Ollama is running (menu bar app or `ollama serve`)
3. First chat response may take **30–90 seconds** — wait, do not retry rapidly
4. For faster replies, use a smaller model: `OLLAMA_MODEL=llama3.2:3b`

---

## Related APIs

| API | Purpose |
|-----|---------|
| `POST /api/login` | Obtain JWT token |
| `GET /api/profile` | Logged-in user profile |
| `GET /api/search` | Manual global search (non-AI) |

See also: [Global Search API Documentation](./GLOBAL_SEARCH_API_DOCUMENTATION.md)
