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

---

## 7. Sistem Notifikasi Terstandarisasi (`BaseNotification` & Dispatcher)

Sistem notifikasi aplikasi mengikuti struktur terpadu berbasis antrean asinkron:

1. **Wajib Mewarisi `BaseNotification`:**
   - Seluruh notifikasi mewarisi `App\Notifications\BaseNotification` (`implements ShouldQueue`).
   - Notifikasi memiliki payload terstruktur: `title`, `message`, `category` (`NotificationCategoryEnum`), `type` (`NotificationTypeEnum`), `scope` (`NotificationScopeEnum`), dan optional `action_url`.
2. **Multi-Level Dispatching (`NotificationDispatcherService`):**
   - **User Level (`sendToUser`):** Mengirimkan notifikasi langsung ke user spesifik.
   - **Merchant Level (`sendToBusiness`):** Mengirimkan notifikasi ke seluruh staf dengan role tertentu (default: `owner`, `manager`) di bawah tenant tersebut.
   - **Outlet Level (`sendToOutlet`):** Mengirimkan notifikasi ke karyawan yang bertugas pada outlet tertentu.
3. **Kebijakan Retensi & Pembersihan:**
   - Notifikasi yang telah dibaca $\ge 365\text{ hari}$ otomatis dipangkas melalui scheduler `php artisan notifications:prune --days=365` setiap malam pukul 02:30 WIB.

---

## 8. Navigasi & Breadcrumbs Terpusat (`BreadcrumbManager`)

Sistem navigasi breadcrumbs dikelola terpusat oleh `App\Support\Breadcrumbs\BreadcrumbManager`:
1. **Resolusi Berdasarkan Route Name:** `BreadcrumbManager` memetakan rute aktif ke hierarki judul, ikon, dan tautan halaman.
2. **Distribusi Otomatis via Middleware:** Dikirimkan ke frontend sebagai shared prop Inertia (`breadcrumbs`) oleh `HandleAppInertiaRequests` dan `HandleCockpitInertiaRequests`.
3. **Render Frontend:** Dirender otomatis oleh `@/Components/Layout/Header/Breadcrumbs.vue`.

---

## 9. Alur Domain Khusus: Purchasing V2 (Pengadaan, Penerimaan, & Retur)

Alur pengadaan barang menerapkan siklus transaksi berlapis untuk menjamin integritas stok dan akuntansi:

```
┌─────────────────┐       ┌────────────────────┐       ┌──────────────────────┐
│  PurchaseOrder  │ ────► │    GoodsReceipt    │ ────► │  InventoryCostLayer  │
│(Draft -> Order) │       │(Partial / Multi-GR)│       │  & InventoryMovement │
└────────┬────────┘       └─────────┬──────────┘       └──────────────────────┘
         │                          │
         ▼                          ▼
┌─────────────────┐       ┌────────────────────┐
│  Void Purchase  │       │   PurchaseReturn   │
│(Cancel PO & GR) │       │(Locked by GR Item) │
└─────────────────┘       └────────────────────┘
```

1. **Purchase Order (`PurchaseOrderService`):** Pembuatan pesanan pembelian ke vendor (`Supplier`).
2. **Goods Receipt (`GoodsReceiptService`):** Penerimaan fisik barang di outlet. Mendukung penerimaan bertahap (*partial receipt*) yang menghasilkan mutasi stok masuk (`InventoryMovementType::PurchaseIn`) dan pembentukan layer biaya FIFO via `InventoryCostingService`.
3. **Purchase Return (`PurchaseReturnService`):** Pengembalian barang rusak/salah ke vendor. Wajib merujuk pada item penerimaan spesifik (`goods_receipt_item_id`) dan mematuhi batas hari retur (`Supplier.return_period_days`). Menghasilkan mutasi stok keluar (`InventoryMovementType::ReturnOut`).
4. **Void Purchase:** Pembatalan menyeluruh pesanan pembelian yang mengunci status menjadi `Voided` dan mencegah manipulasi lebih lanjut.

---

## 10. Audit & Activity Logging Subsystem

Sistem pencatatan jejak audit aktivitas (*Audit Trail*) mengadopsi arsitektur event-driven asinkron dan partisi database:

```
┌───────────────────────────┐
│ Domain Service / Handler  │
└─────────────┬─────────────┘
              │ calls $this->activityLogger->log(...)
              ▼
┌───────────────────────────┐
│   ActivityLogService      │
│(App\Contracts\Audit\...)  │
└─────────────┬─────────────┘
              │ dispatches to Redis queue
              ▼
┌───────────────────────────┐
│   RecordActivityLogJob    │
└─────────────┬─────────────┘
              │ inserts into partitioned table
              ▼
┌────────────────────────────────────────────────────────┐
│ PostgreSQL Partitioned Table: activity_logs            │
│ ├── activity_logs_p2026_08                             │
│ ├── activity_logs_p2026_09                             │
│ └── activity_logs_pYYYY_MM (Auto-created by scheduler) │
└────────────────────────────────────────────────────────┘
```

1. **Kontrak Terstandarisasi (`ActivityLoggerInterface`):**
   - Layanan bisnis menyuntikkan `App\Contracts\Audit\ActivityLoggerInterface`.
   - Menggunakan `AuditModuleEnum` untuk standarisasi nama modul (Auth, Inventory, Sales, Master, Settings, Employee, Billing, Promo, Report).
2. **Eksekusi Asinkron:** Job `RecordActivityLogJob` dieksekusi di antrean latar belakang sehingga pencatatan audit tidak menambah latensi HTTP response.
3. **Database Partitioning (PostgreSQL):**
   - Tabel induk `activity_logs` dipartisi secara bulanan (`RANGE (created_at)`).
   - Scheduler `php artisan audit:create-partitions` berjalan otomatis untuk menyiapkan partisi bulan berikutnya.
   - Scheduler `php artisan audit:prune-partitions --retention-months=12` memangkas partisi lama di luar masa retensi kepatuhan tanpa membebani VACUUM database.

---

## 11. Core Image Optimization Engine (`ImageOptimizerService`)

Untuk menjaga performa aplikasi dan konsumsi storage, pemrosesan berkas gambar dipusatkan pada `App\Services\Core\ImageOptimizerService`:

1. **Konversi Otomatis ke WebP:** Semua file gambar yang diupload (JPEG, PNG, GIF, BMP) otomatis dikonversi ke format modern WebP dengan kompresi kualitas adaptif (default $82\%$).
2. **Batasan Dimensi Maksimum (Max Bounds & Aspect Ratio):**
   - **Avatar / Foto Profil:** $400 \times 400\text{ px}$.
   - **Logo Bisnis:** $600 \times 600\text{ px}$.
   - **Katalog Produk:** $1200 \times 1200\text{ px}$.
   - **Bukti Transfer Pembayaran:** $1600 \times 1600\text{ px}$.
   - Dimensi asli yang lebih kecil dari batas tidak akan di-upscale untuk mencegah penurunan ketajaman gambar.
3. **Memory-Safe GD Processing:** Alokasi memori PHP diamankan dan resource GD dibersihkan via `imagedestroy()` pada blok `finally` untuk mencegah memory leak saat concurrency tinggi.
4. **Pembersihan Otomatis Berkas Lama:** Menggantikan gambar lama saat update (misal: ganti avatar/logo/foto produk) dengan menghapus file lama dari disk `public` secara otomatis.

---

## 12. Segregation of Duties (SoD) & Inventory Integrity

Untuk mencegah *fraud* dan ketidaksesuaian stok fisik pada operasional tenant multi-outlet:

1. **Prinsip Maker-Checker (Pemisahan Wewenang):**
   - User yang membuat/mengajukan draf Stock Adjustment, Stock Opname, atau Stock Transfer **DILARANG KERAS** menyetujui (*approve*) atau menyelesaikan (*finalize*) transaksinya sendiri.
   - Pengecekan diverifikasi pada level Service Layer (`App\Services\App\Inventory\*`) dan Authorization Policy.
2. **Decoupled Stock Deduction (`InventoryDeductionService`):**
   - Kasir POS saat checkout hanya mencatat transaksi penjualan.
   - Pemotongan saldo inventori dan pembentukan lapisan biaya HPP dijalankan melalui event listener `DeductInventoryOnSaleListener` yang memanggil `InventoryDeductionService` secara terisolasi.

