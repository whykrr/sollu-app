---
name: sollu-backend-rules
description: Backend rules for the Sollu app project using Laravel 11. Use this whenever working on Laravel controllers, models, services, migrations, form requests, API routes, or CSV export/import.
---

# Sollu Backend Rules (Laravel 11)

## 1. Architecture & Controllers

- **Flow:** Controller → Action/Service → Repository (optional) → Model.
- **Pattern:** Hybrid approach:
    - _Resource-style (inline):_ Simple CRUD directly in controller.
    - _Service-injected:_ Complex business logic via constructor-injected Services (thin controller handling validation, auth, response).
- **Authorization:** ALWAYS use `$this->authorize('permission.name')` in controller methods or `authorize()` in Form Requests. **NEVER** use `$this->middleware('permission:...')` in `__construct()`. No hardcoded role checks (`$user->role == 'admin'`).

## 2. Model Standards

- **Member Ordering:**
    1. `use` Traits (one per line, e.g. `use HasRoles;`)
    2. `$fillable`, `$hidden`, `$sortable`, `$appends`
    3. `casts(): array` (Laravel 11 method style, aligned `=>` arrows)
    4. Custom notification methods
    5. Relationships (Order: BelongsTo → HasMany → BelongsToMany → HasOne; typed return `: BelongsTo`)
    6. `scopeFilters()` & other scopes
    7. Custom methods/helpers
- **PHPDoc:** Add relationship annotations (e.g. `/** @property-read Collection|Outlet[] $outlets */`).

## 3. Database, Migrations & Queries

- **Database:** Standard UUID via `HasUuids` (or Auto Increment). Mandatory foreign key constraints and indexes on FK, code, sku, slug, and searchable columns. Check existing migrations before adding columns to prevent duplicates.
- **Queries:** Organize filter conditions inside `scopeFilters()` using `->when()`. Prioritize Eloquent over raw SQL (`DB::select`). Always eager load (`with()`) to prevent N+1 queries.

## 4. Service Layer

- **Single-File Service (≤500 lines, low complexity):** Combined domain service (e.g. `app/Services/OutletService.php`) with `create()`, `update()`, `delete()`.
- **Split-File Service (>500 lines or complex):** Single-action class per file (e.g. `app/Services/Outlet/CreateOutletService.php`) with main method `execute(array $data, User $user)`.
- **Rules:** Wrap mutations in `DB::transaction()`. Record audit logs for every mutation (e.g. `AuditLogService` or `OutletAuditLog`).

## 5. Form Requests

- **Naming:** `Get{Entity}Request`, `Store{Entity}Request`, `Update{Entity}Request`.
- **Base Class:** Extend `App\Http\Requests\BaseInertiaFormRequest` (auto-redirects with Indonesian error message: `"Anda tidak memiliki akses."`).
- **Rules:** Return permission string checks in `authorize()`. Format `rules()` with column-aligned `=>` arrows in array format `['required', 'string', 'max:255']`. All validation must go through Form Requests.

## 6. API & Routes

- **Routes:** Group via `prefix()`, `name()`, `group()`. Use dot-notation (`entity.action`, e.g. `settings.outlets.index`).
- **Endpoints:** `DELETE /{model}` → `delete` (soft), `PUT /{model}/restore` → `restore` (`->withTrashed()`), `DELETE /{model}/destroy` → `destroy` (force).

## 7. CSV Async Import/Export

- **Export (`AbstractCsvExportJob`):** Define `getQuery()`, `getHeaders()`, `mapRow($row)`, `getModuleName()`, `getFileName()`. Controller dispatches job and returns `redirect()->back()->with('success', 'Export is being processed...')`. Never stream large CSVs directly from controller.
- **Import (`AbstractCsvImportJob`):** Define `getModuleName()`, `processRow(array $row)` (throw `Exception` on error to log to `failed_import.csv`). Controller validates `mimes:csv,txt`, stores temporarily in `imports` local disk, dispatches job, and returns `redirect()->back()->with('success', 'Import process is running in the background...')`.
- **Template Download:** Streamed from controller (`response()->stream(...)`). Must include BOM (`fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));`) and 1 dummy row reference.
- **Notifications:** Background jobs fire `CsvExportCompleted` / `CsvImportCompleted` with header notification download link via public `Storage`.

## 8. Performance, Query Optimization & Logging

- **Query Performance Limit** (Max 5s):
    - Query/Response execution MUST NOT exceed 5 seconds.
    - Mandatory DB indexes on WHERE, ORDER BY, GROUP BY, and foreign keys.
    - Run EXPLAIN / Telescope in dev to prevent full table scans.
    - Strictly prevent N+1 queries via eager loading.
- **Datatables & Pagination**: Do not eager load heavy relations in paginated `index()`; use `withCount()` instead. Load details via Axios in `show($id)`.
    - **Eager Loading vs. Single-Query Joins**: Use `with()` to resolve N+1 problems in standard queries. For DataTables/pagination requiring _sorting_ or _filtering_ on related columns, use `join()` or `leftJoin()` to enable database-level sorting and minimize memory usage.
- **Large Datasets**: Use `chunk()`, `lazy()`, or `cursor()` for memory efficiency.
- **Offload Heavy Processes**: Offload complex calculations, exports, imports, and notifications to Queue Jobs.
- **Logging**: Log Errors, Integration, Payment, and Critical events only. Log warnings for slow queries (>2s).
- **Seeders**: Idempotent (`updateOrCreate`/`firstOrCreate`). Register permissions in `PermissionEnum.php` & `RolePermissionSeeder.php`, then seed via artisan.

## 9. API JSON Response Standards

- **Format:** `snake_case` keys. Rely strictly on HTTP status codes (200, 201, 400, 404, 422, 500). No custom success envelopes (`"success": true`).
- **Data & Meta:** Use `JsonResource`. Wrap collections in `"data"` and pagination in `"meta"`.
- **Errors:** Standard FormRequest structure (`"message"`, `"errors"` with status 422).
- **Traits:** Use `BaseController` response trait (`successResponse`, `errorResponse`, `noContent`).
