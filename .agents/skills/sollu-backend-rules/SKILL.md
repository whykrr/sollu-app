---
name: sollu-backend-rules
description: Backend rules for the Sollu app project using Laravel 11. Use this whenever working on Laravel controllers, models, services, migrations, form requests, API routes, or CSV export/import.
---

# Sollu Backend Rules (Laravel)

## Architecture & Controllers

Follow this structure:

```text
Controller
    ↓
Action / Service
    ↓
Repository (optional)
    ↓
Model
```

**Controller Pattern:**
Use a **hybrid** approach:

1. **Resource-style (inline):** Use for simple CRUD operations without complex side-effects. Logic can be written directly in the controller.
2. **Service-injected:** For complex business logic, use a _thin controller_. Controllers should only handle request validation, authorization, and responses. Inject Service classes via constructor (e.g., `CreateOutletService`).

**Do not hardcode authorization:**

```php
// Wrong
if (\$user->role == 'admin')
```

Use Gates, Policies, Spatie Permissions, or inside Form Requests.

**Permission Validation Requirement:**
When validating permissions in a Controller, ALWAYS use the `$this->authorize('permission.name')` method provided by the `AuthorizesRequests` trait directly inside the controller methods, OR use the `authorize()` method inside Laravel's Form Requests.
**DO NOT** use `$this->middleware('permission:...')` in the controller's `__construct()` method.

## Model Standards

**Property/Method Ordering:**

1. `use` Traits (one per line)
2. `$fillable`
3. `$hidden` (if exists)
4. `$sortable` (if exists)
5. `$appends` (if exists)
6. `casts(): array`
7. Custom notification methods (if exists)
8. Relationships (BelongsTo → HasMany → BelongsToMany → HasOne)
9. `scopeFilters()`
10. Other scopes
11. Custom methods/helpers

**Trait Style:**

- Each trait must be on a separate line for consistency.

```php
use HasRoles;
use HasFactory;
use HasUuids;
```

**`casts()` Pattern:**

- Use the method style (Laravel 11 standard).
- Use column-aligned `=>` arrows for readability.

```php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
    ];
}
```

**PHPDoc Annotations:**

- Include relationship annotations using PHPDoc (e.g., `/** @property-read Collection|Outlet[] $outlets */`).

**Relationship Patterns:**

- Always specify return types: `public function business(): BelongsTo`.

## Database & Migration

- All foreign keys must use constraints.
- Use UUID or Auto Increment for primary keys (UUID is standard, via `HasUuids`).
- Always add indexes on foreign keys, code, sku, slug, and searchable columns.
- > **Verification:** Before adding new columns, check existing migrations for the same table to avoid duplicate columns or naming conflicts.

## Query Patterns

- Use model scopes (`scopeFilters`) with `->when()` to organize query conditions.
- Prioritize Eloquent, avoid `DB::select(...)` unless necessary.
- Avoid N+1 queries. Always consider eager loading (`->with()`).

## Service Layer

For complex operations, business logic must reside in a Service.

**Service File Strategy — Simple vs Complex:**

- **Single-file Service (≤500 lines, low complexity):** If the entire business logic for a domain does not exceed 500 lines and is not overly complex, use a **single service file** (e.g., `app/Services/OutletService.php`). Methods such as `create()`, `update()`, `delete()` are combined in one class.
- **Split-file Service (>500 lines or complex):** If the business logic exceeds 500 lines or involves many dependencies/complex side-effects, split into separate files per action (e.g., `app/Services/Outlet/CreateOutletService.php`, `app/Services/Outlet/UpdateOutletService.php`). Each file uses a **main method** `execute()`.

**General Rules:**

- **Naming (single-file):** Domain-oriented (e.g., `OutletService`, `OrderService`).
- **Naming (split-file):** Action-oriented (e.g., `CreateOrderService`, `UpdateOutletService`).
- **Structure:** Placed in specific subdirectories based on domain (e.g., `app/Services/Outlet/`). Single-file services may be placed directly in `app/Services/`.
- **Main method (split-file):** `execute(array $data, User $user)` (or similar parameters).
- **Integrity:** Always wrap actions in `DB::transaction()`.
- **Audit Logging:** Every mutation must create an audit log (e.g., using `OutletAuditLog` or `AuditLogService`).

## Form Request

**Naming Convention:**

- `Get{Entity}Request` for index/list.
- `Store{Entity}Request` for creation.
- `Update{Entity}Request` for updates.

**Base Class:**

- Extend `App\Http\Requests\BaseInertiaFormRequest` if authorization failure should redirect back with an Indonesian error message ("Anda tidak memiliki akses.").

**Authorization (`authorize()`):**

- Should return permission string checks (e.g., `return Auth::user()?->can('outlet.create');`).

**Rules (`rules()`):**

- **Column-Aligned:** Format rules in the array using aligned `=>` arrows for readability.
- Use the array format for complex rules or Store/Update requests: `['required', 'string', 'max:255']`.
- All form validation must go through Form Requests, not `$request->validate()` in the controller.

## API & Route

- Group routes using `prefix()`, `name()`, and `group()`.
- Route names must follow dot-notation (`entity.action`, e.g., `settings.outlets.index`).
- **Standard Endpoint Naming:**
    - `DELETE /{model}` → `delete` (soft delete)
    - `PUT /{model}/restore` → `restore` (using `->withTrashed()`)
    - `DELETE /{model}/destroy` → `destroy` (force delete)

## CSV Import/Export Pattern

The system processes CSV Import and Export asynchronously using Queued Jobs.

**1. Data Export (`App\Jobs\AbstractCsvExportJob`)**

- **Job Implementation:** Create a new class extending `AbstractCsvExportJob`. You must define:
    - `getQuery()`: Returns the query builder for data to be exported (do not call `->get()`).
    - `getHeaders()`: Array of CSV column header strings.
    - `mapRow($row)`: Maps database row to CSV array format.
    - `getModuleName()` & `getFileName()`: For notifications and file naming in `public` storage.
- **Controller Implementation:** Call `JobName::dispatch(...)`, then return `redirect()->back()->with('success', 'Export is being processed...')`. Never stream CSV directly from the Controller for large datasets.

**2. Data Import (`App\Jobs\AbstractCsvImportJob`)**

- **Job Implementation:** Create a new class extending `AbstractCsvImportJob`. You must define:
    - `getModuleName()`: Module name for notifications.
    - `processRow(array $row)`: Logic for validation and database persistence per row (associative array based on CSV headers). **Important:** Throw an `Exception` (`throw new Exception('Failure reason');`) if validation or save fails. The Abstract Job will automatically record failed rows into a `failed_import.csv` file.
- **Controller Implementation:**
    - Validate files using `mimes:csv,txt`, then store temporarily in local disk (`$file->store('imports', 'local')`).
    - Dispatch the job (`JobName::dispatch(Auth::user(), $path, ...)`) and return `redirect()->back()->with('success', 'Import process is running in the background...')`.

**3. Download Import Template**

- Generated directly in the Controller using `response()->stream(...)`.
- Must include the BOM character (`fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));`) to prevent CSV corruption in MS Excel. Always provide 1 row of dummy data as a format reference.

**4. UI Notification Integration**

- Background jobs trigger `CsvExportCompleted` or `CsvImportCompleted` notifications upon completion. The download link (or list of failed rows) will be handled centrally by the header notification UI via a public `Storage` link.

### 3. Performance & Database Queries
- **Eager Loading**: Always use eager loading (`with()`) for relationships that will be accessed in collections or API responses to prevent N+1 query problems.
- **Partial Data Loading for Datatables**: Never eager load heavy hasMany/belongsToMany relations (e.g., `items`) on `index()` methods that return paginated data for Datatables. Instead, use `withCount('relation')` to show summary counts. Create a `show($id)` method to fetch detailed relationships via API/Axios ONLY when the user opens a detail view or edit modal.
- **Chunking**: For processing large datasets (e.g., exports or bulk updates), use `chunk()` or `cursor()`.

## Logging & Seeder

- **Logging:** Log only Errors, Integration failures, Payment failures, and Critical events. Do not spam logs.
- **Seeder:** Must be idempotent. Use `updateOrCreate()` or `firstOrCreate()`. Avoid `create()` for master data.

## API JSON Response Standards

**1. General Response Format**
- Always use **`snake_case`** for all JSON response keys.
- Do not use custom wrapping envelopes like `{"success": true}`. Rely strictly on HTTP Status Codes (e.g., 200, 201, 400, 404, 422, 500) to dictate the success or failure of a request.

**2. Data Transformation (Laravel API Resources)**
- Use Laravel's built-in `JsonResource` for transforming models.
- Standard data should be wrapped in the default `"data"` key.
- **Pagination:** When returning paginated data, ensure the metadata is placed within a `"meta"` object (which is the default behavior when returning a Resource Collection of a paginator).

*Example:*
```json
{
    "data": [
        {
            "id": 1,
            "full_name": "John Doe"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "total": 50
    }
}
```

**3. Error Responses**
- Return appropriate HTTP status codes (400, 401, 403, 404, 422, 500).
- Do not append arbitrary `success: false` or custom `error_code` fields.
- For validation errors, use Laravel's default FormRequest response which provides standard `"message"` and `"errors"` structure with a 422 status code.

*Example (Validation Error - 422):*
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email_address": ["The email address has already been taken."]
    }
}
```

**4. Controller Traits**
- In addition to `JsonResource`, you should implement standard response methods inside a Trait (used in `BaseController`) for simple standardized responses (e.g., `successResponse`, `errorResponse`, `noContent`).
- These trait methods should follow the same rules: no custom `success` fields, snake_case keys, and proper HTTP status codes.

*Example Trait Implementation snippet:*
```php
public function successResponse($data = [], $message = null, $code = 200)
{
    $response = [];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    if ($message) {
        $response['message'] = $message;
    }
    
    return response()->json($response, $code);
}
```
