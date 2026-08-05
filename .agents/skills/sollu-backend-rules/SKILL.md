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
if ($user->role == 'admin')
```
Use Gates, Policies, Spatie Permissions, or inside Form Requests.

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
- Extend `BaseInertiaFormRequest` if authorization failure should redirect back with an Indonesian error message ("Anda tidak memiliki akses.").

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
Sistem memproses Import dan Export CSV secara asinkron (di latar belakang) menggunakan *Queued Jobs*.

**1. Ekspor Data (`AbstractCsvExportJob`)**
- **Implementasi Job**: Buat class baru yang meng-*extend* `AbstractCsvExportJob`. Anda wajib mendefinisikan metode:
  - `getQuery()`: Mengembalikan *query builder* data yang akan diekspor (belum dieksekusi / di-`get()`).
  - `getHeaders()`: Array string header kolom CSV.
  - `mapRow($row)`: Format pemetaan *row database* ke format array CSV.
  - `getModuleName()` & `getFileName()`: Untuk notifikasi dan penamaan file di storage `public`.
- **Implementasi Controller**: Panggil `JobName::dispatch(...)`, lalu kembalikan respons `redirect()->back()->with('success', 'Ekspor sedang diproses...')`. Jangan panggil `.csv` stream secara langsung di Controller untuk data besar.

**2. Impor Data (`AbstractCsvImportJob`)**
- **Implementasi Job**: Buat class baru yang meng-*extend* `AbstractCsvImportJob`. Wajib mendefinisikan:
  - `getModuleName()`: Nama modul untuk notifikasi.
  - `processRow(array $row)`: Logika validasi dan penyimpanan ke database per-baris (menggunakan array assosiatif berbasis nama header CSV). **Penting:** Lemparkan `Exception` (`throw new Exception('Alasan gagal');`) jika terjadi kesalahan / validasi gagal. *Abstract Job* akan otomatis merekap baris yang gagal ini menjadi sebuah file CSV khusus (`failed_import.csv`).
- **Implementasi Controller**: 
  - Validasi *file* menggunakan `mimes:csv,txt`, lalu simpan secara temporer ke disk lokal (`$file->store('imports', 'local')`).
  - *Dispatch* job (`JobName::dispatch(Auth::user(), $path, ...)`) lalu berikan respons sukses `redirect()->back()->with('success', 'Proses impor berjalan di latar belakang...')`.

**3. Download Template Impor**
- Di-*generate* langsung di Controller menggunakan `response()->stream(...)`.
- Wajib menyertakan karakter *BOM* (`fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));`) agar file CSV tidak rusak saat dibuka menggunakan MS Excel. Selalu sertakan 1 baris *dummy data* sebagai referensi format pengisian yang benar.

**4. Integrasi Notifikasi UI**
- Pekerjaan latar belakang memicu kelas notifikasi `CsvExportCompleted` / `CsvImportCompleted` saat usai. Hasil unduhan (atau list baris gagal saat impor) akan langsung ditangani secara terpusat oleh antarmuka sistem (Notifikasi Header) menggunakan tautan unduhan dari `Storage` *public*.

## Logging & Seeder
- **Logging:** Log only Errors, Integration failures, Payment failures, and Critical events. Do not spam logs.
- **Seeder:** Must be idempotent. Use `updateOrCreate()` or `firstOrCreate()`. Avoid `create()` for master data.
