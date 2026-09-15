# Sollu App Architecture Documentation

Panduan arsitektur tingkat tinggi dan struktur teknis sistem **Sollu App**.

---

## 1. Tech Stack & Platform Overview

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 11.9+ | PHP 8.3, modern single-action/thin controller, typed properties |
| **Frontend Framework** | Vue 3 (Composition API `<script setup>`) | Single Root Component, Script Setup, Tailwind v4 |
| **Monolith Bridge** | Inertia.js 1.2 | `@inertiajs/vue3` ^1.2.0, `inertia-laravel` ^1.3 (Inertia v1 patterns) |
| **Database** | PostgreSQL 16+ | UUID primary keys (`HasUuids`), JSONB columns, indexed tenant scopes |
| **Caching & Queue** | Redis | Cache query data murni (pure array/primitif), async queues |
| **Styling Engine** | Tailwind CSS v4 | `@tailwindcss/postcss` ^4.1.11, custom `@utility` di `resources/css/app.css` |
| **State Management** | Pinia ^2.3.0 | `usePopUpStore`, `useModalStore`, `useToastStore`, `useAppStore` |
| **Client Routing Helper** | Ziggy ^2.3 | Named route helper untuk Vue |
| **Authorization** | Spatie Laravel Permission ^6.20 | Multi-tenant team mode (`business_id`) |

---

## 2. Subdomain & Guard Routing Architecture

Sollu App menggunakan arsitektur pemisahan domain berdasarkan fungsionalitas dan target pengguna:

```
                          ┌───────────────────────────┐
                          │     DNS / Reverse Proxy   │
                          └─────────────┬─────────────┘
                                        │
        ┌───────────────────────────────┼───────────────────────────────┐
        ▼                               ▼                               ▼
 ┌───────────────┐              ┌───────────────┐              ┌────────────────┐
 │ app.sollu.test │              │cockpit.sollu.id│             │ api.sollu.test │
 └───────┬───────┘              └───────┬───────┘              └───────┬────────┘
         │                              │                              │
   Guard: business                Guard: cockpit                 Guard: sanctum
   Middleware: web                Middleware: web                Middleware: api
   Routes: routes/app.php         Routes: routes/cockpit.php     Routes: routes/api.php
   Inertia: Pages/App/*           Inertia: Pages/Cockpit/*       Payload: Pure JSON (snake_case)
```

1. **Merchant App (`app.sollu.test`):**
   - Panel utama pemilik bisnis & karyawan outlet.
   - Menggunakan Guard `business`, middleware `web`, dan stack Inertia Vue.
   - Definisi route modular di `routes/app/*.php`.
2. **Internal Admin Cockpit (`cockpit.sollu.id`):**
   - Panel operasional internal tim Sollu untuk manajemen tenant, paket, dan billing.
   - Menggunakan Guard `cockpit`, middleware `web`.
   - Definisi route di `routes/cockpit.php` dan views di `resources/js/Pages/Cockpit/`.
3. **POS & External API (`api.sollu.test`):**
   - API untuk perangkat POS (Point of Sale), sync offline/online, dan integrasi webhook pihak ketiga.
   - Menggunakan Guard `sanctum`, middleware `api`.
   - Menghasilkan payload JSON murni berformat `snake_case`.

---

## 3. Modular Monolith & Bounded Contexts

Aplikasi dirancang menggunakan pendekatan **Modular Monolith** dengan batasan domain (*Bounded Contexts*) yang terisolasi.

### 3.1. Matriks Modul Resmi

| Modul | Controller Namespace | Service Namespace | Model Namespace | Routes File | Vue Pages Path |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Inventory** | `App\Http\Controllers\App\Inventory` | `App\Services\App\Inventory` | `App\Models\Inventory` | `routes/app/inventories.php` | `resources/js/Pages/App/Inventory` |
| **Master** | `App\Http\Controllers\App\Master` | `App\Services\App\Master` | `App\Models\Master` | `routes/app/masters.php` | `resources/js/Pages/App/Master` |
| **Transaction / Sales** | `App\Http\Controllers\App\Transaction` | `App\Services\App\Transaction` | `App\Models\Sales` | `routes/app/transactions.php` | `resources/js/Pages/App/Transaction` |
| **Promotion** | `App\Http\Controllers\App` | `App\Services\App\PromoService` | `App\Models\Promo*` | `routes/app/promotions.php` | `resources/js/Pages/App/Promotion` |
| **Customer** | `App\Http\Controllers\App` | `App\Services\App\CustomerService` | `App\Models\Master\Customer` | `routes/app/customers.php` | `resources/js/Pages/App/Customer` |
| **Employee** | `App\Http\Controllers\App` | `App\Services\App\EmployeeService` | `App\Models\User`, `Role` | `routes/app/employees.php` | `resources/js/Pages/App/Employee` |
| **Settings & Outlet** | `App\Http\Controllers\App\Settings` | `App\Services\App\Outlet` | `App\Models\Outlet*` | `routes/app/settings.php` | `resources/js/Pages/App/Settings` |
| **Reports** | `App\Http\Controllers\App\Reports` | `App\Services\App\Reports` | Read-only queries | `routes/app/reports.php` | `resources/js/Pages/App/Reports` |
| **Subscription** | `App\Http\Controllers\App\Settings` | `App\Services\App\SubscriptionService` | `App\Models\Subscription*` | `routes/app/settings.php` | `resources/js/Pages/App/Settings/Subscription` |
| **Cockpit** | `App\Http\Controllers\Cockpit` | `App\Services\Cockpit` | `App\Models\Cockpit*` | `routes/cockpit.php` | `resources/js/Pages/Cockpit` |

### 3.2. Aturan Komunikasi Antar-Modul (Decoupling)

1. **🚨 Larangan Mutasi Lintas Modul:** Modul A **DILARANG KERAS** melakukan operasi INSERT, UPDATE, atau DELETE langsung ke database/Model milik Modul B.
2. **Pola 1: Domain Event (Asinkron / Efek Samping):**
   - Modul A melempar `Event` (misal `TransactionCompleted`).
   - Modul B menangkap via `Listener` (misal `DeductStockOnTransactionListener`) dan memanggil service-nya sendiri (`StockDeductionService`).
3. **Pola 2: Public Service Contract (Panggilan Sinkron):**
   - Jika Modul A butuh kalkulasi atau validasi dari Modul B (misal evaluasi promo saat kasir checkout), Modul B menyediakan Public Service (misal `PromoEvaluationService`).
   - **Kompensasi Output:** Method WAJIB mengembalikan tipe data primitif, DTO, atau pure array terstruktur. Dilarang mengembalikan unexecuted Query Builder atau model aktif yang kotor.

---

## 4. Backend Execution Flow & Layer Responsibilities

Alur eksekusi request mengikuti pola berlapis:

```
 HTTP Request
      │
      ▼
 Form Request (extends BaseInertiaFormRequest)
   ├── Authorization check ($this->user()->can(...))
   └── Validation rules (using Rule::enum(...))
      │
      ▼
 Controller (Thin / Resource / Hybrid)
   ├── Authorize Action ($this->authorize(...))
   └── Delegates to Domain Service
      │
      ▼
 Domain Service (Single-file <=500 lines or Split-file >500 lines)
   ├── DB::transaction(...)
   ├── Business logic calculation
   └── AuditLogService recording
      │
      ▼
 Eloquent Model (HasUuids, casts(), explicit BelongsTo/HasMany return types)
      │
      ▼
 Response Output
   ├── Inertia::render (Index table with lightweight props)
   ├── JsonResource (On-demand API / Show endpoints with (float) casting)
   └── Redirect with Flash Message (App\Constants\ResourceMessage)
```

---

## 5. Performance & On-Demand Data Loading Baseline

Untuk memastikan performa aplikasi tetap cepat dan responsif:

1. **Maksimum Waktu Eksekusi:** Response query/endpoint dilarang melebihi **5 detik**.
2. **Prinsip On-Demand Index:**
   - Props pada `index()` Inertia **HANYA** memuat data esensial tabel paginasi.
   - Dilarang memuat relasi berat (nested children, detail resep, log audit) di `index()`.
3. **Detail & Form Options via Async API:**
   - Data detail entitas untuk view/edit drawer diambil secara on-demand via endpoint `show()` menggunakan Axios saat drawer dibuka.
   - Opsi master dropdown dinamis diambil secara async saat form dibuka (`AsyncSelectField`, `AsyncOutletDropdown`).
4. **N+1 Query Prevention:**
   - Gunakan Eager Loading `with()` hanya untuk relasi yang ditampilkan di kolom tabel.
   - Gunakan `withCount()` untuk menampilkan jumlah relasi tanpa memuat seluruh model.
   - Pilih kolom spesifik dengan `select(['id', 'name', ...])` untuk menghindari `SELECT *` pada tabel besar.

---

## 6. State Management & Caching Boundaries

1. **UI State Boundary (Laravel Session):**
   - State antarmuka yang terikat pada perangkat/browser pengguna (seperti *Selected Outlet*, *Active Tab*, pilihan filter) **WAJIB** disimpan menggunakan `session()`. Ini mencegah kebocoran state (*state bleed*) jika pengguna login di beberapa perangkat berbeda.
2. **Query Optimization Boundary (Redis Cache):**
   - Redis Cache hanya dipakai untuk optimasi pembacaan query berat.
   - Data yang dicache wajib berupa *Pure Array* atau tipe data primitif (bukan instance Model Eloquent).
3. **Otomatisasi Invalidasi Cache:**
   - Invalidasi cache dilakukan via **Model Observers** (contoh: `UserCacheObserver`), bukan manual di dalam controller/service.
