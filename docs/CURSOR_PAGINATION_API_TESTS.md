# Cursor pagination API tests

Automated coverage for every HTTP endpoint that uses Laravel `cursorPaginate()` lives in:

`tests/Feature/Api/Pagination/CursorPaginationEndpointsTest.php`

Helpers for creating users and granting `can_read` on the correct modules:

`tests/Concerns/SetsUpUserWithModuleReadPermissions.php`

JWT and permission middleware are disabled for these tests; the user still has matching rows in `departments`, `modules`, `module_department`, and `department_user` so the setup mirrors production permission checks.

## Why SQLite (default PHPUnit) skips these tests

Several migrations use **MySQL-only** column defaults (`CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`). The default `phpunit.xml` uses `DB_CONNECTION=sqlite` and `:memory:`; migrating that schema fails, so the test class detects `DB_CONNECTION=sqlite` **before** `RefreshDatabase` runs and marks tests skipped.

## Run against MySQL (e.g. Docker Compose)

Ensure MySQL is up and the database exists (`docker-compose` exposes `3307` → MySQL `3306`). If tests fail immediately with **`SQLSTATE[HY000] [2002] ... actively refused connection`**, the host/port has no listener: start Compose (`docker compose up -d mysql` or full stack), then retry.

```powershell
cd D:\FastrankingCRMUpdatable
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3307'
$env:DB_DATABASE='fastrankingcrm'
$env:DB_USERNAME='root'
$env:DB_PASSWORD=''
php artisan test tests/Feature/Api/Pagination/CursorPaginationEndpointsTest.php
```

Use a dedicated **testing** database if you prefer not to `migrate:fresh` the development DB (`RefreshDatabase` wipes all tables).

## What is asserted

For each endpoint the test checks:

- HTTP `200`
- `success: true` (see `BaseApiController::successResponse`)
- Pagination payload shaped like Laravel’s `CursorPaginator::toArray()`: `data`, `path`, `per_page`, `next_page_url`, `prev_page_url`, `next_cursor`, `prev_cursor`

Endpoints that nest the paginator (e.g. `leads`, `appointments`) assert that structure under `data.{key}`.

- `GET /api/consultation/filter` cursor response uses the outer success `data` as the Laravel cursor object; transformed rows appear in `data.data` alongside `next_cursor`, etc.

## Breaking note (Quality list)

`/api/quality` (same handler as POST `/api/quality/quality-filter`) previously returned `data` as a **plain array** of records. It now returns a **CursorPaginator-shaped object** in `data`, with transformed rows under `data.data` (`next_cursor`, `per_page`, etc. at the same level).

## Breaking note (Consultation filter list)

`GET /api/consultation/filter` previously returned `data` as a **plain array** of transformed consultations. It now uses the same cursor envelope (`data.next_cursor`, `data.data`, etc.).

## Related fixes

- `/api/quality/my-assignments` was unreachable when registered **after** `GET /quality/{id}`; static routes were reordered in `routes/api/admin/quality/quality.php`.
- Users without roles/departments could error in consultation filters when reading `$user->roles->first()->name`; null-safe access was fixed in `ConsultationController`.
