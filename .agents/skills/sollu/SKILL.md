---
name: sollu
description: >-
  Comprehensive standards and guidelines for Sollu App (Laravel 11.9+, PHP 8.3, Vue 3, Inertia.js 1.2, Tailwind CSS v4).
  Covers Core Architecture, Modular Monolith, Backend (Controllers, Services, Models, FormRequests, Constants),
  Frontend (Vue 3, PopUpPage, Form Fields, Table Filters), PHP Enums as Single Source of Truth, RBAC (Spatie Permissions),
  SaaS Feature Plan Gating, Code Quality (Pint, ESLint, DoD), Unit Testing (100% Mocking), Web Integration Testing (browsermcp),
  Async Excel (Export/Import), Blade DomPDF, and API Documentation. MUST trigger whenever working on any Sollu App features.
---

# Sollu App Comprehensive Engineering Guidelines & Standards

Pedoman dan standar baku rekayasa perangkat lunak untuk seluruh modul dan komponen pada **Sollu App**. Mengintegrasikan seluruh standar arsitektur sistem, backend, frontend, validasi enum, manajemen hak akses, pembatasan paket SaaS, kualitas kode, pengujian otomatis, integrasi file ekspor/impor, dan dokumentasi API ke dalam satu sumber referensi terpadu.

---

## DAFTAR ISI
1. [Core Architecture & Tech Stack](#1-core-architecture--tech-stack)
2. [Modular Monolith Architecture & Bounded Contexts](#2-modular-monolith-architecture--bounded-contexts)
3. [Backend Standards (Laravel 11.9+ & PHP 8.3)](#3-backend-standards-laravel-119--php-83)
4. [Frontend Standards (Vue 3, Inertia 1.2 & Tailwind v4)](#4-frontend-standards-vue-3-inertia-12--tailwind-v4)
5. [PHP Enums as Single Source of Truth (Anti-Magic Strings)](#5-php-enums-as-single-source-of-truth-anti-magic-strings)
6. [Role-Based Access Control (RBAC & Spatie Permissions)](#6-role-based-access-control-rbac--spatie-permissions)
7. [SaaS Feature Plan Gating (Subscription Entitlements)](#7-saas-feature-plan-gating-subscription-entitlements)
8. [Code Quality, Linters & Definition of Done (DoD)](#8-code-quality-linters--definition-of-done-dod)
9. [Service Layer Unit Testing (100% Mocking & In-Memory SQLite)](#9-service-layer-unit-testing-100-mocking--in-memory-sqlite)
10. [Web Integration & E2E Testing (browsermcp)](#10-web-integration--e2e-testing-browsermcp)
11. [Asynchronous Excel Export & Import](#11-asynchronous-excel-export--import)
12. [PDF Document Generation (laravel-dompdf)](#12-pdf-document-generation-laravel-dompdf)
13. [API Documentation Standards](#13-api-documentation-standards)

---

## 1. Core Architecture & Tech Stack

### 1.1. Official Technology Stack
- **Backend Framework:** Laravel 11.9+ (`laravel/framework` ^11.9, PHP 8.3).
- **Frontend Engine:** Vue 3 (Composition API `<script setup>`), Inertia.js 1.2 (`@inertiajs/vue3` ^1.2.0, `inertia-laravel` ^1.3).
- **Styling & Build Tool:** Tailwind CSS v4 (`@tailwindcss/postcss` ^4.1.11, `tailwindcss` ^4.1.11), Vite 6 (`vite` ^6.3.5).
- **Core Packages & State:** Pinia (^2.3.0), Ziggy (^2.3), Spatie Laravel Permission (^6.20), FontAwesome 6 (^6.7.1).
- **Additional UI Libraries:** Swiper 11, Quill 2, Chart.js 4, vue-advanced-cropper 2, vuedraggable 4, vue-i18n 11.

### 1.2. Directory Layout & Layer Responsibilities
```
app/
├── Http/
│   ├── Controllers/     # Thin controllers handling request, authorization & response
│   └── Requests/        # Form Requests extending BaseInertiaFormRequest
├── Models/              # Eloquent models using HasUuids trait and casts() method (including BusinessType, Feature, SubscriptionPlan)
├── Services/            # Domain service logic (Single-file <= 500 lines or Split-file > 500 lines)
├── Jobs/
│   └── ImportExport/    # Async CSV/Excel jobs (AbstractExcelExportJob & AbstractExcelImportJob)
├── Enums/               # PermissionEnum, RoleEnum, FeatureEnum (keys only), PlanEnum (standard tiers), Status enums (NO BusinessTypeEnum)
resources/
├── js/
│   ├── Components/      # Shared UI & Form inputs (@/Components/Form/)
│   ├── Pages/           # Inertia page views (Pages/{Module}/Components & Tabs)
│   ├── store/           # Pinia stores (usePopUpStore, useModalStore, useToastStore, useAppStore)
│   └── Composable/      # Vue composables (useAuth, useEnum, usePlanFeature)
└── views/
    └── pdf/             # DomPDF Blade templates & pdf.partials.header
```

### 1.3. Subdomain & Guard Routing Architecture
- **`app.sollu.test`** (Guard: `business`, Middleware: `web`): Merchant Web Application & Inertia Dashboard (`routes/app.php` & `routes/app/*.php`).
- **`cockpit.sollu.id`** (Guard: `cockpit`, Middleware: `web`): Internal Admin & Operations Panel (`routes/cockpit.php`).
- **`api.sollu.test`** (Guard: `sanctum` / Public, Middleware: `api`): POS Device APIs, Webhooks, and OpenAPI Docs (`routes/api.php`).

### 1.4. Localization & Language Rules
- **UI Text & Error Messages:** MUST strictly use **Indonesian** (e.g. `"Anda tidak memiliki akses."`, `"Data berhasil disimpan."`).
- **Code Documentation & Comments:** Code comments, docstrings, variable names, and architectural rules MUST be written in **English**.

### 1.5. Security & Isolation Baseline
- **Tenant Isolation:** Enforce `business_id` or `outlet_id` checks on every query mutation.
- **ORM Enforcements:** Use Eloquent or Query Builder bindings exclusively. Never construct raw SQL strings with inline variable interpolations.
- **Environment Secrets:** Store secret keys, webhooks, and API credentials exclusively in `.env`. Never commit secrets directly in code.

### 1.6. State Management & Caching Boundary
- **UI State (Session Boundary):** Segala bentuk pilihan antarmuka yang mengikat pada pengguna di suatu perangkat (contoh: *Selected Outlet*, *Active Tab*, pilihan *Filter*) **WAJIB** disimpan menggunakan `session()` Laravel. Pendekatan ini mencegah kebocoran state (*state bleed*) antar perangkat/browser ketika pengguna login di berbagai device secara bersamaan.
- **Query Performance (Cache Boundary):** Penggunaan Redis atau global `Cache::` HANYA diizinkan untuk optimasi performa *query* database (contoh: caching `SummaryUser`). Data yang disimpan di Cache wajib berupa tipe data primitif atau *Pure Array*, **DILARANG** menyimpan *Eloquent Model* untuk menghindari masalah *serialization*.
- **Cache Invalidation:** Jika menggunakan Redis cache untuk performa, invalidasi data cache (seperti menghapus `SummaryUser` saat *Role* berubah) wajib dilakukan secara otomatis melalui *Eloquent Observers* atau *Model Events* (contoh: `UserCacheObserver`), BUKAN dengan cara manual memanggil `cacheDelete()` dari dalam controller atau layer service.

### 1.7. MCP Tooling Architecture Matrix
| MCP Server | Transport / Runtime | Primary Purpose | Key Tools |
| :--- | :--- | :--- | :--- |
| **`sollu-db`** | Stdio (`@modelcontextprotocol/server-postgres`) | Direct PostgreSQL system catalog queries, index inspection, and raw SQL validation | `query` |
| **`laravel-boost`** | Stdio (`php artisan boost:mcp` via `laravel/boost`) | Application-level schema inspection, error logs, documentation vector search, and dynamic code evaluation | `DatabaseSchema`, `DatabaseQuery`, `LastError`, `ReadLogEntries`, `SearchDocs`, `Tinker`, `ApplicationInfo` |
| **`filesystem`** | Stdio (`@modelcontextprotocol/server-filesystem`) | Multi-file inspections, directory trees, and safe file moves | `read_multiple_files`, `directory_tree`, `move_file`, `get_file_info` |
| **`browsermcp`** | Stdio (`@browsermcp/mcp`) | E2E browser automation, screenshot capture, DOM inspection, and console logs | `browser_navigate`, `browser_snapshot`, `browser_click`, `browser_type`, `browser_screenshot`, `browser_get_console_logs` |

> [!NOTE]
> For standard file editing and viewing within the codebase, Antigravity's native tools (`view_file`, `replace_file_content`, `write_to_file`, `find_by_name`, `grep_search`) remain the primary mechanism. Use `filesystem` MCP for bulk operations and directory tree overviews.

---

## 2. Modular Monolith Architecture & Bounded Contexts

### 2.1. Domain & Bounded Context Directory Matrix
Setiap fitur dalam Sollu App harus ditempatkan ke dalam salah satu Bounded Context resmi:

| Bounded Context | Backend Controller & Service | Eloquent Models | Routes | Frontend Inertia Pages |
| :--- | :--- | :--- | :--- | :--- |
| **`Inventory`** | `App\Http\Controllers\App\Inventory\`<br>`App\Services\App\Inventory\` | `App\Models\Inventory\` | `routes/app/inventories.php` | `resources/js/Pages/App/Inventory/` |
| **`Master`** | `App\Http\Controllers\App\Master\`<br>`App\Services\App\Master\` | `App\Models\Master\` | `routes/app/masters.php` | `resources/js/Pages/App/Master/` |
| **`Sales` / `Transaction`** | `App\Http\Controllers\App\Transaction\`<br>`App\Services\App\Transaction\` | `App\Models\Sales\` | `routes/app/transactions.php` | `resources/js/Pages/App/Transaction/` |
| **`Promotion`** | `App\Http\Controllers\App\PromotionController`<br>`App\Services\App\PromoService` | `App\Models\Promo*` | `routes/app/promotions.php` | `resources/js/Pages/App/Promotion/` |
| **`Customer`** | `App\Http\Controllers\App\CustomerController`<br>`App\Services\App\CustomerService` | `App\Models\Master\Customer` | `routes/app/customers.php` | `resources/js/Pages/App/Customer/` |
| **`Employee`** | `App\Http\Controllers\App\EmployeeController`<br>`App\Services\App\EmployeeService` | `App\Models\User`, `Role`, `Permission` | `routes/app/employees.php` | `resources/js/Pages/App/Employee/` |
| **`Outlet` & `Settings`** | `App\Http\Controllers\App\Settings\`<br>`App\Services\App\Outlet\` | `App\Models\Outlet*`, `OutletSetting` | `routes/app/settings.php` | `resources/js/Pages/App/Settings/` |
| **`Reports`** | `App\Http\Controllers\App\Reports\`<br>`App\Services\App\Reports\` | Read-only queries | `routes/app/reports.php` | `resources/js/Pages/App/Reports/` |
| **`Subscription`** | `App\Services\App\SubscriptionService` | `App\Models\Subscription*`, `Invoice` | `routes/app/settings.php` | `resources/js/Pages/App/Settings/Subscription/` |
| **`Cockpit`** | `App\Http\Controllers\Cockpit\`<br>`App\Services\Cockpit\` | `App\Models\Cockpit*` | `routes/cockpit.php` | `resources/js/Pages/Cockpit/` |

### 2.2. Decoupling Patterns: Protokol Komunikasi Antar-Modul

#### Pola 1: Event-Driven Side-Effects (Asynchronous / Synchronous Listeners)
Gunakan ketika Modul A selesai melakukan aksinya dan ingin memberitahu sistem tanpa peduli siapa yang merespon (misal: Transaksi Selesai → Potong Stok, Tambah Poin Member, Catat Log).

```php
// 1. Modul Sales mendefinisikan & menembakkan Event
namespace App\Events\Transaction;

use App\Models\Sales\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Transaction $transaction) {}
}

// Di TransactionService:
event(new TransactionCompleted($transaction));
```

```php
// 2. Modul Inventory menangani side effect secara mandiri di domainnya
namespace App\Listeners\Inventory;

use App\Events\Transaction\TransactionCompleted;
use App\Services\App\Inventory\StockDeductionService;

class DeductStockOnTransactionListener
{
    public function __construct(protected StockDeductionService $stockDeductionService) {}

    public function handle(TransactionCompleted $event): void
    {
        $this->stockDeductionService->deductForTransaction($event->transaction);
    }
}
```

#### Pola 2: Public Service Contract / Facade (Panggilan Sinkron)
Jika Modul A membutuhkan kalkulasi atau validasi dari Modul B sebelum menyelesaikan aksinya (misal: Menghitung diskon promo saat checkout).
- **Aturan Input/Output:** Modul B menyediakan Service publik. Parameter dan return value harus bertipe data jelas (DTO, float, boolean, atau pure array).
- **Dilarang keras:** Mengembalikan query builder yang belum dieksekusi atau model Eloquent yang dapat di-mutate sembarangan oleh Modul A.

```php
namespace App\Services\App\Promotion;

class PromoEvaluationService
{
    /**
     * Hitung diskon yang berlaku untuk item transaksi secara terisolasi.
     * Return: array terstruktur ['discount_amount' => float, 'promo_id' => ?string]
     */
    public function evaluateItemDiscount(string $inventoryItemId, float $price, float $qty, string $outletId): array
    {
        return [
            'discount_amount' => 15000.0,
            'promo_id'        => 'uuid-promo',
        ];
    }
}
```

### 2.3. Panduan Pembuatan Fitur Baru (Modular-First)
1. **Identifikasi Bounded Context:** Tentukan modul pemilik utama (*owner*) data & alur kerja.
2. **Isolasi Routing:** Tambahkan route hanya pada `routes/app/{module}.php` dengan prefix `{module}.{feature}.*`.
3. **Form Request & Controller Terisolasi:** Buat di `App\Http\Requests\{Module}\` dan `App\Http\Controllers\App\{Module}\`. Controller tetap *thin*.
4. **Service Terenkapsulasi:** Buat di `App\Services\App\{Module}\`. Jika melebihi 500 baris, gunakan *Split-File Single Action Pattern* (`Create...Service.php`, `Update...Service.php`).
5. **Komunikasi Keluar:** Gunakan Public Service (sinkron) atau Domain Event (asinkron). Dilarang mutasi langsung ke tabel modul lain.
6. **Frontend Halaman & Komponen Privat:**
   - Halaman utama di `resources/js/Pages/App/{Module}/Index.vue`.
   - Form drawer di `resources/js/Pages/App/{Module}/Components/{Feature}FormPopUp.vue`.
   - Detail drawer di `resources/js/Pages/App/{Module}/Components/{Feature}DetailPopUp.vue`.
   - Filter modal di `resources/js/Pages/App/{Module}/Components/Filter.vue`.
7. **Unit Test Service Mandiri (100% Mocking):** Buat unit test di `tests/Unit/Services/{Module}/...` tanpa koneksi database fisik.

### 2.4. Refactoring Playbook: Memutus Ketergantungan Erat (Decoupling)
- **Kasus 1: Service Modul A Menulis Langsung ke Tabel Modul B:**
  Ekstrak mutasi menjadi Domain Event (Modul A) + Listener & Service (Modul B). Hapus dependensi model modul B dari modul A.
- **Kasus 2: Service Modul A Memeriksa Logika Bisnis Modul B Manual:**
  Ekstrak logika ke Public Service di Modul B (misal `PromoEvaluationService`), inject ke Service Modul A, dan panggil method yang mengembalikan tipe primitif/DTO murni.
- **Kasus 3: Frontend Meng-import Komponen Antar Halaman Berbeda:**
  Jika komponen dipakai lintas modul, pindahkan ke `resources/js/Components/` (global) atau gunakan async search API via `@/Components/Form/AsyncSelectField.vue`.

---

## 3. Backend Standards (Laravel 11.9+ & PHP 8.3)

### 3.1. Mandatory MCP Laravel Boost & Database Diagnostics
- **MANDATORY BEFORE ANY CODING:**
  - **Documentation Verification:** Gunakan MCP `laravel-boost` (`search-docs`) untuk memverifikasi sintaks resmi, signature method, dan best practices dari package ekosistem Laravel 11.
  - **Database Schema Inspection:** Gunakan MCP `laravel-boost` (`database-schema`) dan `sollu-db` (query PostgreSQL) untuk membaca skema langsung dari database hidup. Jangan pernah mengira-ngira nama kolom atau tipe data.
  - **Error Diagnostics:** Saat terjadi error / exception, **LANGKAH PERTAMA** adalah memanggil `last-error` atau `read-log-entries` dari `laravel-boost`.
- **Verifikasi Kolom & Data Type:** Pastikan nama kolom, tipe data, nulabilitas (`nullable`), default value, dan Foreign Key pada Model/FormRequest/Service **persis sama** dengan skema nyata di database.
- **Verifikasi Relasi (FK):** Cek keberadaan Foreign Key constraint di database sebelum menuliskan method relasi Eloquent (`belongsTo`, `hasMany`, dll) atau validasi `exists:table,id`.

### 3.2. Architecture & Controllers
- **Flow:** Controller → Action/Service → Repository (opsional) → Model.
- **Controller Pattern (Hybrid):**
  - _Resource-style (inline):_ CRUD sederhana dapat langsung ditulis di controller.
  - _Service-injected:_ Logika bisnis kompleks wajib di-offload ke Service Class via Constructor Injection.
- **Authorization:** Gunakan `$this->authorize('permission.name')` atau `Gate::authorize()`. Dilarang menggunakan middleware di `__construct()`.

### 3.3. Model Standards (Laravel 11)
- **Member Ordering:**
  1. `use` Traits (satu per baris: `use HasFactory, HasUuids, SoftDeletes;`)
  2. Properti: `$fillable`, `$hidden`, `$sortable`, `$appends`
  3. Method `casts(): array` (Style Laravel 11 dengan panah `=>` rapi)
  4. Method Notifikasi Custom
  5. Relationships (Urutan: `BelongsTo` → `HasMany` → `BelongsToMany` → `HasOne`; return type explicit `: BelongsTo`)
  6. `scopeFilters()` & Scopes lainnya
  7. Custom Helpers / Methods
- **PHPDoc:** Selalu tambahkan `@property-read Collection|Outlet[] $outlets` untuk membantu Autocomplete IDE / Larstan.

### 3.4. Form Requests (`BaseInertiaFormRequest`)
- **Base Class:** Semua Form Request wajib menginduk ke `App\Http\Requests\BaseInertiaFormRequest`.
- **Naming:** `Get{Entity}Request`, `Store{Entity}Request`, `Update{Entity}Request`.
- **Authorization:** Kembalikan cek permission pada method `authorize()`.
- **Validation Rules:** Format rules dalam bentuk array dengan panah `=>` sejajar:
  ```php
  public function rules(): array
  {
      return [
          'name' => ['required', 'string', 'max:255'],
          'sku'  => ['nullable', 'string', 'max:100'],
      ];
  }
  ```

### 3.5. Service Layer Standards
- **Single-File Service (≤500 baris):** Gabungan domain service (contoh: `app/Services/OutletService.php`) memuat method `create()`, `update()`, `delete()`.
- **Split-File Service (>500 baris atau kompleks):** Single-action class per file (contoh: `app/Services/Outlet/CreateOutletService.php`) dengan method utama `execute(array $data, User $user)`.
- **Database Transactions:** Bungkus setiap mutasi multi-tabel dalam `DB::transaction(function () { ... });`.
- **Audit Log:** Catat perubahan data penting menggunakan `AuditLogService`.

### 3.6. Query Optimization & On-Demand Data Loading (Max 5s)
- **Waktu Eksekusi Query/Response:** Dilarang melebihi **5 detik**.
- **Standarisasi On-Demand Data Loading:**
  - **Inertia `index()` HANYA Memuat Data Esensial Tabel:** Dilarang keras memuat data relasi berat (children, items, recipes, logs) atau lookup master massal (semua kategori, semua item, semua opsi modifier) ke dalam props Inertia `index()`.
  - **Offload Detail ke Endpoint On-Demand (`show`):** Detail lengkap entitas (untuk drawer/PopUpPage/modal view & edit) WAJIB disediakan melalui endpoint API/controller tersendiri (misal: `show(Entity $entity)` yang mengembalikan JSON atau `JsonResource`) dan diambil secara *asynchronous* (Axios) hanya saat drawer/popup dibuka.
  - **Offload Form Lookup Options:** Opsi dropdown form yang besar atau dinamis WAJIB dimuat secara on-demand saat formulir dibuka (via endpoint khusus seperti `formOptions` atau pencarian async `AsyncSelectField`).
- **N+1 Query Prevention:**
  - Selalu gunakan Eager Loading (`with()`) untuk relasi yang ditampilkan pada kolom tabel.
  - **Dilarang Over-Eager Loading:** Dilarang me-load relasi yang TIDAK ditampilkan di kolom tabel.
  - **Hitung Jumlah dengan `withCount()`:** Jika tabel hanya menampilkan jumlah data relasi, WAJIB gunakan `withCount('relation')` dan akses `relation_count`. DILARANG memuat seluruh model relasi (`with('relation')`) hanya untuk menghitung count di frontend.
- **Selective Column Loading (`select()`):** Hindari `SELECT *` pada tabel besar/query berat. Pilih hanya kolom yang dibutuhkan (`select(['id', 'name', 'status', ...])`), terutama saat eager loading (`with(['relation:id,parent_id,name'])`).
- **Existence Checks:** Gunakan `exists()` atau `doesntExist()` saat mengecek keberadaan data (bukan `count() > 0` atau `first() !== null`).
- **Batch Processing & Mutations:** Dilarang perulangan mutasi model (`foreach (...) { Model::create(...) }`). Gunakan batch `insert()` atau `upsert()`.
- **Index Awareness:** Periksa ketersediaan indeks pada kolom pencarian, filter status, tenant ID, atau relasi menggunakan MCP `sollu-db`.
- **No Unbounded Queries:** Dilarang memanggil `get()` atau `all()` tanpa batasan (`limit` atau `paginate`). Gunakan `chunk()`, `lazy()`, atau `cursor()` untuk dataset besar.

### 3.7. API JSON Response Standards
- **Key Format:** `snake_case`.
- **Status Codes:** Mengacu pada standar HTTP (200, 201, 400, 404, 422, 500). Tidak menggunakan wrapper custom `"success": true`.
- **Data & Meta:** Gunakan `JsonResource`. Bungkus koleksi data dalam `"data"` dan data paginasi dalam `"meta"`.
- **Numeric & Decimal Casting (`(float)` / `(double)`):** Seluruh nilai desimal dan numerik (harga, stok, persentase, bobot) pada `JsonResource` atau respon API WAJIB di-cast ke tipe angka murni `(float)` atau `(double)`. Dilarang mengirim string desimal seperti `"10.50"`.
  ```php
  public function toArray(Request $request): array
  {
      return [
          'id'            => $this->id,
          'name'          => $this->name,
          'base_price'    => (float) $this->amount,
          'current_stock' => (float) $this->current_stock,
      ];
  }
  ```

### 3.8. Controller Response Messages & Constants (MANDATORY)
- **DILARANG MENGGUNAKAN HARDCODED STRING:** Dilarang keras menuliskan string pesan respon manual langsung di Controller (contoh salah: `->with('success', 'Data berhasil dibuat')`).
- **WAJIB MENGGUNAKAN CONSTANT / TRANSLATION:**
  - `App\Constants\ResourceMessage::CREATE_SUCCESS` (`'Data berhasil dibuat!'`)
  - `App\Constants\ResourceMessage::UPDATE_SUCCESS` (`'Data berhasil diperbarui!'`)
  - `App\Constants\ResourceMessage::DELETE_SUCCESS` (`'Data dipindah ke sampah!'`)
  - `App\Constants\ResourceMessage::RESTORE_SUCCESS` (`'Data berhasil di kembalikan!'`)
  - `App\Constants\ResourceMessage::PURGE_SUCCESS` (`'Data berhasil di hapus!'`)
  - `App\Constants\AuthorizationMessage::CANT_ACCESS_PAGE`
  - `App\Constants\AuthorizationMessage::CANT_ACCESS_DATA`
  - `App\Constants\ErrorMessage::DATABASE_ERROR` (`'Terjadi kesalahan database. Coba lagi nanti.'`)
  - `App\Constants\ErrorMessage::DATA_NOT_FOUND` (`'Data tidak ditemukan.'`)
  - `App\Constants\ErrorMessage::PAGE_NOT_FOUND` (`'Halaman tidak ditemukan.'`)
  - `App\Constants\ErrorMessage::TOO_MANY_REQUESTS` (`'Terlalu banyak permintaan. Coba lagi nanti.'`)
  - `App\Constants\ErrorMessage::SERVER_ERROR` (`'Terjadi kesalahan pada server. Coba lagi nanti.'`)
  - `App\Constants\FlashDataVariable::SUCCESS->value` (`'success'`)
  - `App\Constants\FlashDataVariable::WARNING->value` (`'warning'`)
  - `App\Constants\FlashDataVariable::FAILED->value` (`'failed'`)

```php
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;

public function store(StoreOutletRequest $request)
{
    $this->outletService->create($request->validated());

    return redirect()->back()->with(
        FlashDataVariable::SUCCESS->value,
        ResourceMessage::CREATE_SUCCESS
    );
}
```

### 3.9. Backend Dead Code Removal Standards
1. **Clean Unused Imports (`use`):** Hapus semua baris `use` yang tidak dipanggil. Jalankan `vendor/bin/pint`.
2. **Remove Dead Methods & Helpers:** Hapus method yatim tanpa caller beserta unit test-nya.
3. **No Commented-Out PHP Code:** Hapus seluruh blok komentar kode lama (`//`, `/* */`).
4. **Obsolete Routes & Requests:** Hapus `FormRequest` class dan deklarasi route yang tidak lagi dipakai.

---

## 4. Frontend Standards (Vue 3, Inertia 1.2 & Tailwind v4)

### 4.1. 🚨 10 Anti-Hallucination Core Rules
1. **NO RAW HTML FORMS & MANDATORY REUSABLE COMPONENTS:** Selalu gunakan komponen `@/Components/Form/` (`TextField`, `TextareaField`, `DropdownField`, `NumberField`, `Switch`, `CheckboxField`, `RadioField`, `SelectionGroupField`, `AsyncSelectField`, `AsyncOutletDropdown`). DILARANG KERAS menggunakan tag `<input>`, `<select>`, atau `<textarea>` mentah!
2. **PROJECT-SPECIFIC TAILWIND STYLES:** Gunakan utility class yang sudah didefinisikan di `app.css` (`btn`, `btn-main`, `btn-outline-main`, `btn-danger`, `form`, `form-group`).
3. **NO HARDCODED PAGE LAYOUTS:** Selalu gunakan `<MainPage>` (`#header`, default slot, `#footer`).
4. **PRECISE PROPS:** Komponen form menggunakan `v-model`, `label`, `placeholder`, dan `feedback` (pesan error validasi). Dilarang mengikat `is-invalid` secara manual.
5. **NO TAILWIND CLUTTER:** Ekstrak kelompok class berulang (5+ class) ke `@utility` di `resources/css/app.css`.
6. **MANDATORY POPUPPAGE FOR SUB-PAGES & FORMS:** Seluruh alur kerja *Create*, *Edit*, *Detail*, dan *Sub-page* WAJIB menggunakan `<PopUpPage>` (side-panel drawer) atau `usePopUpStore()`. DILARANG menggunakan *full page redirect* (`router.get()`) untuk formulir sub-halaman.
7. **FORM SPACING LIMIT (MAX SCALE 2):** Jarak antar-input formulir DILARANG melebihi scale 2 Tailwind (`space-y-2`, `space-x-2`, `gap-2`, `gap-y-2`, `gap-x-2`).
8. **STANDARISASI ON-DEMAND DATA LOADING:** Data detail entitas lengkap dan data sekunder (opsi dropdown) WAJIB diambil secara *on-demand / async* via API internal (`axios.get`) saat drawer/modal dibuka. DILARANG memuat relasi berat di props `index()`. Selalu gunakan skeleton loader atau spinner saat menunggu data async.
9. **MANDATORY ENUM FOR CONDITIONS & FORM OPTIONS (NO MAGIC STRINGS):** DILARANG meng-hardcode string literal status/tipe. WAJIB gunakan `$enums.<EnumName>.<Case>` di template atau composable `useEnum()` (`enums.<EnumName>.<Case>`, `getOptions('EnumName')`).
10. **MANDATORY BROWSERMCP UI VERIFICATION:** Setiap pembuatan/perubahan komponen Vue WAJIB diverifikasi visual dan fungsional via `browsermcp` (navigasi URL, screenshot, snapshot DOM, inspeksi console logs).

### 4.2. Component Structure (`<script setup>`)
- **Ordering:** `<template>` terlebih dahulu, kemudian `<script setup>`.
- **Import Order:** 1. Vue core (`ref`, `computed`) → 2. Inertia (`router`, `useForm`) → 3. Third-party (`lodash`, `FontAwesomeIcon`) → 4. Global components (`@/Components/`) → 5. Stores/Composables → 6. Local components (`./Components/`).
- **Script Setup Order:** `defineOptions` → `defineProps`/`defineEmits` → Stores/Composables → Reactive state (`ref`, `reactive`) → `computed` → Methods → Watchers → Lifecycle hooks.

### 4.3. PopUpPage vs Modal (Distingsi Ketat)
- **`<PopUpPage>` / `usePopUpStore()` (Side Drawer Kanan):** WAJIB untuk formulir input, tampilan detail, sub-halaman, dan alur langkah berikutnya.
  - **Teleport Footer Pattern:** Komponen di dalam `PopUpPage` menggunakan `<Teleport v-if="isMounted" to="#popUpFooter">` untuk mengirim tombol aksi ke footer sticky drawer.
- **`<Modal>` / `useModalStore()` (Center Dialog):** STRICTLY khusus untuk konfirmasi singkat (Hapus, Archive, Alert Peringatan).

### 4.4. UI Components & Formatting Standards
- **Quantity Display (`HasQuantityFormatter`):** Selalu tampilkan kuantitas dari properti trait backend (`item.qty_formatted`, `item.qty_received_formatted`). Dilarang memformat angka kuantitas secara manual di frontend.
- **Partial Loading & Skeleton:** Selalu sertakan skeleton loader / spinner / teks `"Memuat..."` (`animate-pulse bg-gray-200 rounded`) saat menunggu fetch data async.

### 4.5. Table Filter Pattern
- **Layout:** `flex items-center gap-2`, `<FilterSearch>`, tombol Filter (`faSliders`) untuk membuka `<FilterModal>`, dan badge filter aktif via `<FilterBadge>`.
- **Workflow & Debouncing:** Inisialisasi `filterForm` dari `props.filters`, watcher 500ms debounce pada `filterForm.search` yang memanggil `updateQuery()`.
- **`updateQuery`:** Merge `route().params` dengan filter aktif, konversi string kosong `''` menjadi `undefined`, reset `page: 1`, lalu panggil `router.get(location.pathname, query, { preserveState: true, preserveScroll: true })`.

### 4.6. SelectionGroupField (`@/Components/Form/SelectionGroupField.vue`)

```html
<!-- Single Select (Radio Button Style) -->
<SelectionGroupField
    v-model="form.gender"
    label="Jenis Kelamin"
    :options="[{ value: 'male', label: 'Laki-laki' }, { value: 'female', label: 'Perempuan' }]"
/>

<!-- Multi Select (Checkbox Button Style with Select All) -->
<SelectionGroupField
    v-model="form.outlets"
    label="Pilih Outlet"
    :options="outlets"
    multiple
    show-select-all
/>
```

### 4.7. Frontend Dead Code Removal Standards
1. **Clean Unused Imports:** Hapus semua `import` komponen, ikon, composable, atau helper yang tidak dipanggil. Jalankan `npm run fix:eslint`.
2. **Remove Unused Reactive State & Props/Emits:** Hapus variabel `ref`, `reactive`, `computed`, `defineProps`, atau `defineEmits` yang tidak digunakan.
3. **No Commented-Out HTML/Vue Code:** Hapus komentar kode HTML/Vue (`<!-- ... -->`, `// ...`).
4. **Obsolete Utility CSS Cleanups:** Hapus aturan `@utility` di `resources/css/app.css` yang sudah tidak dirujuk. Pastikan `npm run build` sukses.

---

## 5. PHP Enums as Single Source of Truth (Anti-Magic Strings)

### 5.1. Anti-Magic Strings Principle & Comparison Matrix
DILARANG KERAS menuliskan string literal mentah untuk validasi kondisi, status, atau fitur jika Enum-nya tersedia.

| Area | ❌ DILARANG (Salah) | ✅ WAJIB (Benar) |
| :--- | :--- | :--- |
| **Vue Template `v-if`** | `v-if="item.status === 'draft'"` | `v-if="item.status === $enums.AdjustmentStatus.Draft"` |
| **Vue Feature Gating** | `v-feature="'promo_management'"` | `v-feature="$enums.FeatureEnum.PROMO_MANAGEMENT"` |
| **Vue Upsell Lock** | `v-feature.lock="'recipe_management'"` | `v-feature.lock="$enums.FeatureEnum.RECIPE_MANAGEMENT"` |
| **Vue Badge Class** | `:class="item.status === 'approved' ? 'badge-success' : 'badge-gray'"` | `:class="$enums.AdjustmentStatus._meta[item.status]?.color"` |
| **Vue Badge Text** | `{{ item.status === 'approved' ? 'Disetujui' : item.status }}` | `{{ $enums.AdjustmentStatus._meta[item.status]?.label }}` |
| **Vue Script Setup** | `if (item.status === 'draft')` | `if (item.status === enums.AdjustmentStatus.Draft)` via `useEnum()` |
| **Dropdown Options** | `:options="[{ value: 'draft', label: 'Draf' }]"` | `:options="getOptions('AdjustmentStatus')"` via `useEnum()` |
| **Backend Condition** | `if ($item->status === 'draft')` | `if ($item->status === AdjustmentStatus::Draft)` |
| **Backend Validation** | `'status' => 'in:draft,approved'` | `'status' => [Rule::enum(AdjustmentStatus::class)]` |
| **Backend Model Cast** | *Tanpa cast atau `'string'`* | `'status' => AdjustmentStatus::class` di method `casts()` |

### 5.2. Frontend Vue 3 Usage

#### Di Dalam Template Vue:
```html
<button v-if="adjustment.status === $enums.AdjustmentStatus.Draft" class="btn btn-main" @click="openEdit(adjustment)">
    Edit Draf
</button>

<span class="badge" :class="$enums.StockOpnameStatus._meta[item.status]?.color || 'badge-gray'">
    {{ $enums.StockOpnameStatus._meta[item.status]?.label || item.status }}
</span>
```

#### Di Dalam `<script setup>`:
```javascript
import { useEnum } from '@/Composable/useEnum'
import { usePlanFeature } from '@/Composable/usePlanFeature'

const { enums, getOptions, getLabel, getColor } = useEnum()
const { hasFeature } = usePlanFeature()

if (adjustment.status === enums.AdjustmentStatus.Draft) {
    // Logic khusus draft
}

const statusOptions = getOptions('AdjustmentStatus')
```

### 5.3. Backend Laravel 11 Usage
- **Model Attribute Casting:**
  ```php
  protected function casts(): array
  {
      return [
          'status'      => AdjustmentStatus::class,
          'reason'      => AdjustmentReason::class,
          'approved_at' => 'datetime',
      ];
  }
  ```
- **Pattern Matching:**
  ```php
  $canEdit = match ($adjustment->status) {
      AdjustmentStatus::Draft, AdjustmentStatus::Rejected => true,
      AdjustmentStatus::Approved, AdjustmentStatus::Voided => false,
  };
  ```
- **FormRequest Validation:**
  ```php
  public function rules(): array
  {
      return [
          'status' => ['required', Rule::enum(AdjustmentStatus::class)],
          'reason' => ['nullable', Rule::enum(AdjustmentReason::class)],
      ];
  }
  ```

### 5.4. Alur Menambahkan Enum Baru
1. **Definisikan di `app/Enums/{Name}.php`:** Backed Enum (string/int) dengan method `label(): string`, `color(): string`, `values(): array`, dan `options(): array`.
2. **Daftarkan di `app/Support/Enums/FrontendEnumProvider.php`:** Tambahkan ke array `$frontendEnums`.
3. **Gunakan di Model, Route, dan Frontend.**

### 5.5. Batasan Enum vs Database-Driven Entities
- **Kapan Menggunakan Enum:** Gunakan PHP Backed Enum HANYA untuk status, peran statis, atau tipe diskrit yang menjadi percabangan logika kode backend secara permanen (misal: `AdjustmentStatus`, `RoleEnum`, `InvoiceStatus`, `PaymentMethodType`, dsb.).
- **Tipe Bisnis (`BusinessType`):** 100% database-driven di tabel `business_types`. **DILARANG KERAS** membuat atau mencari `BusinessTypeEnum`. Pengambilan daftar tipe bisnis pada form registrasi/pengaturan WAJIB menggunakan `BusinessType::getAllCached()`, `BusinessType::options()`, atau `BusinessType::grouped()`.
- **Fitur SaaS (`FeatureEnum` vs Database `features`):** `FeatureEnum` hanya menampung case/keys untuk type-safety (`plan.feature:` dan `$enums.FeatureEnum.*`). Seluruh metadata tampilan (nama, deskripsi, modul, grup, urutan, status aktif) dikelola di tabel `features` dan ditransformasikan otomatis ke frontend via `FrontendEnumProvider` (`$enums.FeatureEnum._meta` dan `$enums.FeatureEnum._grouped`).

---

## 6. Role-Based Access Control (RBAC & Spatie Permissions)

### 6.1. Multi-Tenant Role Isolation (Tenant-Scoped)
- Role pada Sollu App dipisahkan per Bisnis (`business_id`) menggunakan mode `teams => true` (`team_foreign_key = 'business_id'`) pada `spatie/laravel-permission`.
- 3 Default Roles otomatis per bisnis: `owner`, `manager`, `cashier`.
- Evaluasi tim otomatis via `setPermissionsTeamId($user->business_id)` di `AppServiceProvider.php` (listener `Authenticated`).
- **DILARANG** melakukan seeding Role secara global di `RolePermissionSeeder.php`. Pembuatan role dilakukan melalui `App\Services\App\Role\RoleProvisioningService`.
- Normalisasi struktur role di database: `php artisan sollu:normalize-rbac`.

### 6.2. Permission Registration Workflow
1. **Daftarkan Key Permission** di Enum `app/Enums/PermissionEnum.php` (dot-notation, misal `SETTINGS_OUTLET_INDEX = 'settings.outlets.index'`).
2. **Assign ke Default Role** (owner/manager/cashier) di `RoleProvisioningService.php`.
3. **Jalankan Seeder Permission Global:**
   ```bash
   php artisan db:seed --class="Database\Seeders\Production\RolePermissionSeeder"
   ```
4. **Jalankan Normalisasi RBAC jika diperlukan:**
   ```bash
   php artisan sollu:normalize-rbac
   ```

### 6.3. Backend Authorization Rules
- Gunakan `$this->authorize('permission.name')` atau `Gate::authorize('permission.name')` di dalam controller method.
- Dilarang menggunakan middleware di `__construct()`.
- Form Request wajib mengembalikan boolean check permission pada method `authorize()`.
- **DILARANG HARDCODE ROLE:** Dilarang mengecek `$user->role === 'admin'`. Selalu cek permission via `$user->can('permission.name')` atau `$user->hasPermissionTo(...)`.

### 6.4. Frontend Authorization (`useAuth`)
```javascript
import { useAuth } from '@/Composable/useAuth';

const { can, canAny, canAll, hasRole, isOwner } = useAuth();

if (can('settings.outlets.create')) {
    // izinkan aksi
}
```
- Dilarang mengakses `usePage().props.auth` secara manual langsung jika composable `useAuth()` tersedia.

---

## 7. SaaS Feature Plan Gating (Subscription Entitlements)

### 7.1. Perbedaan Otorisasi: RBAC vs Feature Plan
| Parameter | RBAC Permission | Feature Plan |
| :--- | :--- | :--- |
| **Entitas** | Pengguna / User (Jabatan) | Bisnis / Merchant (Paket Langganan) |
| **Backend Guard** | `$this->authorize('perm.name')` | `middleware('plan.feature:feature_name')` |
| **Inertia Prop** | `props.auth.permissions` | `props.auth.features` |
| **Vue Directive** | `v-can="'perm.name'"` | `v-feature="'feature_name'"` |
| **Vue Composable** | `useAuth()` (`can()`, `canAny()`) | `usePlanFeature()` (`hasFeature()`, `requireFeature()`) |
| **Handling UI** | Elemen dihapus dari DOM | Dihapus dari DOM atau ditahan dengan modal upgrade (`v-feature.lock`) |

### 7.2. Backend Feature Registration Workflow
1. **Daftarkan Key Fitur:** Tambahkan case di `app/Enums/FeatureEnum.php` (e.g. `case RECIPE_MANAGEMENT = 'recipe_management';`).
2. **Daftarkan Metadata Fitur di Database:** Tambahkan metadata (code, name, description, module, group, group_label, sort_order) di `database/seeders/Production/FeatureSeeder.php` dan tabel database `features`.
3. **Petakan ke Paket Langganan:** Tambahkan relasi fitur ke paket di `database/seeders/Production/SubscriptionPlanSeeder.php` atau atur langsung melalui antarmuka Cockpit Super Admin via tabel pivot `plan_features` (`SubscriptionPlan::systemFeatures(): BelongsToMany`). DILARANG meng-hardcode pemetaan paket di `PlanEnum.php`.
4. **Pasang Middleware pada Route:**
   ```php
   Route::prefix('recipes')
       ->middleware('plan.feature:' . FeatureEnum::RECIPE_MANAGEMENT->value)
       ->group(function () {
           Route::resource('recipes', RecipeController::class);
       });
   ```
5. **Response Otomatis Jika Terkunci:** AJAX/JSON mengembalikan HTTP 403 `is_feature_locked: true`. Inertia/Web me-redirect back dengan flash `feature_locked` yang memicu `FeatureLockedModal.vue`.

### 7.3. Frontend Validation Standards
- **Directive `v-feature`:**
  ```html
  <button v-feature="$enums.FeatureEnum.PROMO_MANAGEMENT" class="btn btn-main">Buat Promo</button>
  <div v-feature.all="[$enums.FeatureEnum.INVENTORY_MANAGEMENT, $enums.FeatureEnum.RECIPE_MANAGEMENT]">...</div>
  ```
- **Komponen `<FeatureLock>` & `<FeatureLockOverlay>` (Direkomendasikan):**
  ```html
  <FeatureLock :feature="$enums.FeatureEnum.RECIPE_MANAGEMENT">
      <div class="card">...</div>
  </FeatureLock>
  ```
- **Composable `usePlanFeature`:**
  ```javascript
  import { usePlanFeature } from '@/Composable/usePlanFeature';
  import { useEnum } from '@/Composable/useEnum';

  const { hasFeature, requireFeature } = usePlanFeature();
  const { enums } = useEnum();

  if (hasFeature(enums.FeatureEnum.PROMO_MANAGEMENT)) { ... }
  ```
- **Strict Prohibitions:** Dilarang mengecek string nama paket mentah (`plan.name === 'Paket Pro'`), dilarang membuat Spatie permission untuk tier paket langganan.

### 7.4. Dynamic Custom Plan & Modular SaaS Packaging
- **Dukungan Custom Plan (Enterprise/B2B):** Tabel `subscription_plans` mendukung paket kustom non-publik (`is_custom: true`, `is_public: false`). Paket kustom tidak muncul pada katalog paket publik merchant (`/settings/billing/plans`), namun dapat dibuat dan di-assign langsung oleh Super Admin melalui Cockpit.
- **Resolusi Fitur Tenant Otomatis:** Method `Business::getAvailablePlanFeatures()` dan `Business::activePlanFeatures()` membaca langsung relasi `plan->systemFeatures` dari database/cache. Seluruh otorisasi fitur (`hasFeature`, `middleware('plan.feature:...')`, `v-feature`) berjalan secara dinamis tanpa perlu mendaftarkan kode paket baru ke PHP Enum.
- **De-Gating Operasional Ekspor/Impor:** Fungsi ekspor dan impor data (produk, pelanggan, stok, transaksi) adalah hak akses operasional internal merchant yang diatur oleh **Spatie RBAC** (`PermissionEnum`), BUKAN fitur berbayar yang di-gate oleh paket SaaS (`plan.feature`). DILARANG memasang middleware `plan.feature` pada route ekspor/impor.

---

## 8. Code Quality, Linters & Definition of Done (DoD)

### 8.1. Automated Code Formatting & Linting
- **Backend (PHP):** `vendor/bin/pint` (PSR-12 / Laravel style).
- **Frontend (Vue/JS):** `npm run fix:eslint`.
- **Frontend Build Test:** `npm run build` (pastikan bebas dari syntax/bundling error).

### 8.2. Strict Development Prohibitions
1. **NO Monolithic Refactoring:** Jangan refaktor masif di luar cakupan tugas.
2. **NO Destructive Migration Alters:** Jangan hapus migrasi lama atau ubah skema produksi tanpa instruksi eksplisit.
3. **NO Soft Delete / Audit Log Removal:** Jangan hapus log audit (`AuditLogService`) atau bypass `SoftDeletes`.
4. **NO Raw SQL Injection Risks:** Jangan gunakan string concatenation SQL mentah.
5. **NO Direct Role Hardcoding:** Jangan tulis `$user->role == 'admin'`. Selalu gunakan Spatie permission checks.
6. **NO Unoptimized Queries / N+1:** Hindari lazy loading di dalam loop, jangan gunakan `count() > 0` untuk pengecekan eksistensi, dan hindari mutasi loop per model.
7. **NO Dead Code Leftovers:** Hapus seluruh commented-out code, unused imports, orphaned methods/variables, dan file/route usang.
8. **NO Magic Strings for Status/Types/Features:** Selalu gunakan PHP Backed Enum dan `$enums`.
9. **NO BusinessTypeEnum:** Dilarang membuat atau mencari `BusinessTypeEnum`. Tipe bisnis dikelola 100% dinamis di database (`business_types` table). Gunakan `BusinessType::getAllCached()` atau `BusinessType::options()`.
10. **NO Hardcoded Plan Features in Enums:** Dilarang meng-hardcode pemetaan paket di `PlanEnum.php`. Seluruh relasi paket-fitur disimpan di tabel database `plan_features` (`SubscriptionPlan::systemFeatures()`).
11. **NO SaaS Gating on Operational Export/Import:** Dilarang memasang middleware `plan.feature` pada route ekspor dan impor. Gunakan otorisasi Spatie RBAC (`PermissionEnum`).

### 8.3. Definition of Done (DoD) Checklist
- [ ] Backend logic & endpoints tested and returning accurate HTTP status codes.
- [ ] Skema database & error log diverifikasi via MCP `laravel-boost` / `sollu-db` tanpa asumsi.
- [ ] Dokumentasi framework & package diverifikasi via MCP `laravel-boost` (`search-docs`).
- [ ] Controller response messages use `App\Constants\*` (`ResourceMessage`, `FlashDataVariable`) or translation files without hardcoded strings.
- [ ] Query & Eloquent teroptimasi (Eager loading, kolom spesifik, `exists()`, batch `insert`/`upsert`, batas query).
- [ ] Frontend UI verified visually and functionally via `browsermcp` (navigasi URL, screenshot, snapshot, console log check).
- [ ] Semua dead code (commented-out code, unused imports, orphaned methods, obsolete routes) telah dihapus.
- [ ] Code formatted with `vendor/bin/pint` and `npm run fix:eslint`.
- [ ] `npm run build` executes cleanly with zero syntax or bundling errors.
- [ ] All permissions registered in `PermissionEnum.php` & `RolePermissionSeeder.php` (if applicable).
- [ ] Fitur paket SaaS terdaftar di `FeatureEnum.php`, `FeatureSeeder.php`, dan terpetakan di `plan_features` (bukan hardcoded di `PlanEnum.php`).
- [ ] Tipe bisnis menggunakan model database `BusinessType` dinamis tanpa dependensi enum.

---

## 9. Service Layer Unit Testing (100% Mocking & In-Memory SQLite)

### 9.1. Mandatory Unit Test for Service Layer
Setiap pembuatan, perbaikan bug, atau enhancement pada **Service class** WAJIB disertai pembuatan/pembaruan **Unit Test** di `tests/Unit/Services/...`.

### 9.2. Pure In-Memory Testing & Mocking
- Test Service Layer **DILARANG KERAS** menyentuh database fisik.
- **WAJIB** menggunakan `sqlite:memory` dan trait `RefreshDatabase`.
- Dependensi eksternal di-mock menggunakan **Mockery**.

### 9.3. 100% Code Coverage & Scenario Testing
- Uji seluruh skenario (*Happy Path*, *Edge Cases*, dan *Error/Exception/Failure Paths*).
- Pastikan semua percabangan (`if/else`, `switch`, `try/catch`) tereksekusi 100%.
- Gunakan `$this->expectException(...)` untuk memvalidasi exception.

### 9.4. Struktur Direktori & Mocking Contoh
- Lokasi test menduplikasi namespace asli: `App\Services\App\Inventory\StockAdjustmentService` -> `tests/Unit/Services/App/Inventory/StockAdjustmentServiceTest.php`.
- Contoh mocking dependency:
  ```php
  $this->dependencyMock = Mockery::mock(DependencyClass::class);
  $this->dependencyMock->shouldReceive('methodName')
      ->once()
      ->with('args')
      ->andReturn($expectedValue);
  ```

---

## 10. Web Integration & E2E Testing (browsermcp)

### 10.1. Prerequisites & Dev Server
- Dev URL: **http://app.sollu.test/** (atau URL domain dev lokal).
- Jika terdapat file Dusk di `tests/Browser`, jalankan `php artisan dusk`. Jika tidak, gunakan `browsermcp`.

### 10.2. Standard E2E Testing Steps with browsermcp
1. **Navigasi & Autentikasi (`browser_navigate`, `browser_type`, `browser_click`):**
   - Navigasi ke `/login`, isi form kredensial (`sollu.mart@email.com` / `password`), lalu submit login.
2. **Verifikasi Alur UI Sollu App:**
   - Halaman utama / Tabel (`<MainPage>`): Verifikasi header, pagination, dan live search `<FilterSearch>`.
   - Side Drawer Form (`<PopUpPage>` & `usePopUpStore`): Buka drawer via klik Tambah/Edit, isi formulir `@/Components/Form/`, dan submit via sticky footer `#popUpFooter`.
   - Modal Konfirmasi (`<Modal>` & `useModalStore`): Uji dialog konfirmasi hapus/arsip.
3. **Inspeksi DOM & Visual Screenshot (`browser_snapshot`, `browser_screenshot`):**
   - Audit struktur DOM/accessibility tree dan tangkap screenshot visual untuk memastikan layout bersih & toast notifikasi muncul.
4. **Audit Error Log Konsol Browser (`browser_get_console_logs`):**
   - **WAJIB** periksa console logs untuk memastikan tidak ada JavaScript exception atau response HTTP 500/422 yang unhandled.

### 10.3. MCP Web Tools Cheat Sheet
| Perkakas MCP | Kegunaan Utama |
| :--- | :--- |
| `browser_navigate` | Navigasi ke URL target |
| `browser_click` | Klik tombol, link, atau elemen UI |
| `browser_type` | Pengisian teks pada bidang form |
| `browser_select_option` | Memilih item dropdown |
| `browser_press_key` | Menekan tombol keyboard (Enter, Escape, Tab) |
| `browser_snapshot` | Mengambil peta struktur DOM & ref index |
| `browser_screenshot` | Mengambil tangkapan layar UI visual |
| `browser_get_console_logs` | Memeriksa log error / console warning browser |

---

## 11. Asynchronous Excel Export & Import

### 11.1. Ekspor Excel Async (`AbstractExcelExportJob`)
- Dilarang streaming Excel langsung dari controller untuk data besar.
- Buat class Job di `app/Jobs/[Module]/Export[Entity]Job.php` turunan `App\Jobs\ImportExport\AbstractExcelExportJob`.
- Implementasikan method wajib:
  - `getQuery()`: Mengembalikan query builder Eloquent (dengan filter aktif).
  - `getHeaders()`: Array header kolom bahasa Indonesia resmi (contoh: `['Nama', 'SKU', 'Satuan', 'Stok']`).
  - `mapRow($row)`: Format tiap baris (teks biasa, numerik `(float)`, boolean `'Ya'`/`'Tidak'`).
  - `getModuleName()`: Nama modul untuk notifikasi.
  - `getFileName()`: Nama file berformat `'[entity]_export_' . time() . '.xlsx'`.
- Otomatis menyertakan UTF-8 BOM Header (`\xEF\xBB\xBF`), chunking 500 baris, tersimpan di `storage/app/public/exports/`, dan notifikasi `ExcelExportCompleted`.

### 11.2. Impor Excel Async (`AbstractExcelImportJob`)
- Buat class Job di `app/Jobs/[Module]/Import[Entity]Job.php` turunan `App\Jobs\ImportExport\AbstractExcelImportJob`.
- Implementasikan: `getModuleName()` dan `processRow(array $row)` (lempar `Exception` jika baris gagal).
- Fitur otomatis: Auto delimiter detection (`,` atau `;`), BOM skip, pengumpulan baris gagal ke `storage/app/public/exports/failed_import_[timestamp].xlsx` beserta pesan error.
- Dispatch job dari controller:
  ```php
  public function import(Request $request)
  {
      $request->validate(['file' => 'required|mimes:xlsx,txt|max:10240']);
      $path = $request->file('file')->store('imports', 'local');
      ImportEntityJob::dispatch(Auth::user(), $path);

      return redirect()->back()->with('success', 'Proses impor Excel sedang berjalan di latar belakang.');
  }
  ```

### 11.3. Unduhan Template Excel (Streamed)
Diperbolehkan stream langsung dari Controller dengan menyertakan BOM:
```php
public function importTemplate()
{
    $headers = ['Nama', 'SKU', 'Barcode', 'Satuan', 'Minimum Stok'];
    $dummyData = ['Gula Pasir', 'GL-001', '8991234567890', 'Kilogram', '10'];

    return response()->stream(function () use ($headers, $dummyData) {
        $file = fopen('php://output', 'w');
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputxlsx($file, $headers);
        fputxlsx($file, $dummyData);
        fclose($file);
    }, 200, [
        'Content-Type'        => 'text/xlsx',
        'Content-Disposition' => 'attachment; filename="template_[entity].xlsx"',
    ]);
}
```

---

## 12. PDF Document Generation (laravel-dompdf)

### 12.1. Penggunaan Generic Header Template
Setiap Blade View PDF **wajib** menyertakan template `resources/views/pdf/partials/header.blade.php` di awal `<body>`:
```blade
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet'   => $outlet ?? null,
        'title'    => 'JUDUL DOKUMEN',
        'subtitle' => 'Subjudul Opsional'
    ])
    
    <!-- Konten spesifik dokumen -->
</body>
```
- `$business`: Menyediakan nama bisnis dan logo (fallback ke 'Sollu App' jika null).
- `$outlet`: Object outlet (nama, alamat, nomor telepon).
- `$title`: Judul dokumen huruf KAPITAL (e.g. `'LAPORAN PENJUALAN'`).
- `$subtitle`: Keterangan tambahan di bawah judul (e.g. `Periode: 1 Jan 2026 - 31 Jan 2026`).

### 12.2. Controller Format (`Barryvdh\DomPDF\Facade\Pdf`)
```php
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(Request $request)
{
    $pdf = Pdf::loadView('pdf.nama-file', [
        'data'     => $data,
        'business' => Auth::user()->business,
        'outlet'   => Auth::user()->activeOutlet,
    ])->setPaper('a4', 'portrait'); // Gunakan 'landscape' jika tabel > 5 kolom

    return $pdf->download('Nama_File_' . now()->format('YmdHis') . '.pdf');
}
```

### 12.3. DomPDF CSS Best Practices
- Tata letak multi-kolom wajib menggunakan HTML `<table>` (`border-collapse: collapse;`).
- Gunakan `<style>` di `<head>` atau gaya *inline*.
- Gunakan `page-break-inside: avoid;` pada `<tr>` atau card agar tidak terpotong di batas halaman.
- Format mata uang dan tanggal di Controller/Helper sebelum dikirim ke Blade View.

---

## 13. API Documentation Standards

### 13.1. API Documentation Maintenance
Dokumentasi API adalah kontrak antara Backend dan Frontend/Client. Setiap perubahan request (parameter, body, header) atau response (struktur JSON, tipe data, HTTP status) **WAJIB** memperbarui dokumentasi terkait di `docs/`:
1. **Deteksi Perubahan:** Perubahan pada `routes/api.php`, `FormRequest`, `Controller` JSON, atau `JsonResource`.
2. **File Dokumentasi:**
   - **Postman Collection:** `docs/postman_collection.json`.
   - **OpenAPI / Swagger:** `docs/openapi.yaml`.
3. **Detail yang Harus Diperbarui:**
   - Kolom/parameter/body baru beserta deskripsi dan tipe datanya.
   - Perbarui contoh balasan (*example response*) di Postman/Swagger.
   - Perbarui tipe data (*integer*, *string*, *boolean*, UUID).
4. **Konfirmasi:** Jika file dokumentasi tidak ditemukan di repositori, konfirmasi ke pengguna untuk lokasi file sebelum mengakhiri tugas.
