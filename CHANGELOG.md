# Changelog

Semua perubahan penting pada proyek **Sollu App** didokumentasikan di file ini.
Format ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan versi rilis mengikuti [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

### Added
- **Purchasing V2 & Goods Receipt:**
  - Fungsionalitas **Void Purchase** untuk membatalkan seluruh pesanan pembelian beserta penguncian status (`febd151`).
  - Alur penerimaan barang parsial dan multi-GR via `GoodsReceiptService` (`febd151`, `9661b6c`).
  - Manajemen retur pembelian terintegrasi receipt item (`goods_receipt_item_id`) dan validasi batas hari retur supplier (`suppliers.return_period_days`) via `PurchaseReturnService` (`febd151`, `9661b6c`).
  - Form supplier dengan dukungan konfigurasi periode retur (`return_period_days`).
- **Inventory & Costing:**
  - Penambahan jenis mutasi inventori `InventoryMovementType::InitialStock` (`initial_stock`) beserta method metadata (`5cc3332`).
  - Dukungan isolasi saldo inventori berbasis outlet aktif pada `InventoryBalance` (`95ca046`).
  - Komponen metrik ringkasan stok `StockWidgets.vue` dan pembaruan detail stok dengan tab mutasi dan grafik (`e1eec35`).
- **Notification System:**
  - Standardisasi class notifikasi mewarisi `BaseNotification` (`ShouldQueue`) (`e99e1f0`).
  - Implementasi `NotificationDispatcherService` untuk multi-level targeting: user, merchant/business, dan outlet (`e99e1f0`).
  - Enums notifikasi: `NotificationCategoryEnum`, `NotificationTypeEnum`, `NotificationScopeEnum` (`e99e1f0`).
  - Peningkatan antarmuka popover notifikasi dengan 4 tab (Semua, Sistem, Pesanan, Stok) dan dukungan responsif mobile (`ff998da`).
- **Navigation & Breadcrumbs:**
  - Pengenalan `BreadcrumbManager` terpusat untuk penanganan navigasi aplikasi (`c86d4e8`).
  - Integrasi breadcrumb dinamis pada komponen `Breadcrumbs.vue`.
- **Shift Management:**
  - Peningkatan antarmuka (UI) manajemen shift kasir, detail shift drawer, cash log masuk/keluar, dan enum `ShiftCashLogType` serta `ShiftStatus` (`c86d4e8`).
- **Role Templates & Access Control (RBAC):**
  - Implementasi `RoleTemplateEnum` untuk template peran operasional POS (F&B Manager/Cashier, Retail Manager/Cashier) (`1aa1908`).
  - Modul Role-Based Access Control dengan sinkronisasi fitur dinamis antar guard (`e564ec2`).
- **Billing & Subscription:** 
  - Manajemen Subscription Plan di Cockpit dengan fitur sinkronisasi kuota plan (`57aa1b8`, `caa0e75`).
  - Fitur perpanjangan langganan (subscription renewal), notifikasi, dan pembuatan invoice (`9633467`).
  - Penolakan pembayaran invoice dengan notifikasi dan service layer terkait (`f1e9955`).
  - Halaman pengaturan tagihan (Billing Settings) dan peningkatan alur penanganan invoice (`16df53b`, `fc4ad11`).
- **Master Data & Promotions:**
  - Halaman manajemen Business Type dengan fungsionalitas CRUD lengkap (`c4f7b14`).
  - Route manajemen promosi, middleware feature flag, serta on-demand data loading untuk promosi dan modifier (`83b15d6`, `c24a792`).
- **Shared Enums:** `FrontendEnumProvider` untuk mendistribusikan PHP Enums ke frontend via Inertia Shared Props (`2f24688`).
- **Testing:**
  - Unit tests untuk `GoodsReceiptService`, `PurchaseReturnService`, dan `InventoryCostingService` (`9661b6c`, `ae5a8b4`).
  - Feature test untuk `ShiftFeatureTest`, `StockPurchasesControllerTest`, dan `ExceptionHandlingTest` (`c86d4e8`, `febd151`).
  - Unit tests untuk `BreadcrumbManagerTest` dan `RoleTemplateIntegrityTest` (`c86d4e8`, `1aa1908`).
  - Unit & feature tests untuk `SubscriptionController`, `RoleController`, `RememberMe`, serta coverage service layer Business & User (`fa4c17a`, `ea8c04f`).

### Changed
- **UI & Toolbar Architecture:**
  - Standardisasi seluruh toolbar halaman modul menggunakan komponen `@/Components/UI/ActionBar/ActionBar.vue` (`e3bab20`, `2033b34`).
  - Pemindahan tombol aksi utama (+ Tambah Data) ke posisi paling kanan `ActionBar` (`#create` / `#primary`).
  - Pembungkusan aksi ekspor/impor ke dropdown terpadu **`Opsi Data`** (`<ActionsDropdown>`).
  - Penghapusan modal popup filter (`FilterModal.vue`) yang digantikan oleh inline `ActionBar`.
- **Form Architecture:**
  - Penerapan pola formulir 3-tier: simple flat form, progressive disclosure (`<DisclosureSection>`), dan complex wizard/tabbed (`<FormStepper>` / `<FormTabs>`) (`d0eb67c`, `2033b34`).
- **Product Module:** Optimasi state loading saat reorder kategori produk, penyelarasan label antarmuka, dan komponen `ProductCard.vue` (`accc5ee`, `c86d4e8`).
- **UI & Table Component:** Perbaikan z-index header tabel, integrasi sorting fungsional bawaan dengan `SortableModel`, dan peningkatan `Pagination.vue` (`ede9799`, `c86d4e8`).
- **Code Standards & Guidelines:** Penerapan standar formatting PHP (Laravel Pint), UX copywriting (santai, komunikatif, to-the-point, profesional), dan panduan komponen UI (`70ca198`, `7cc8648`, `1aa1908`).
- **Developer Experience & MCP:** Standardisasi integrasi Git MCP, konfigurasi watch Vite, dan pembersihan konfigurasi MCP (`173e5a3`, `2f96a0a`, `2fcf0aa`).

---

## [v1.2.3] - 2026-08-29

### Changed
- **Routing & Client:** Simplifikasi integrasi ZiggyVue dengan menghapus konfigurasi duplikat yang tidak digunakan (`807219e`).

---

## [v1.2.2] - 2026-08-29

### Added
- **System Monitoring:** Implementasi endpoint health check `/health` beserta automated tests (`d121f60`).

---

## [v1.2.1] - 2026-08-29

### Added
- **Impersonation:** Route impersonation untuk merchant dan komponen pop-up detail bisnis (`56b2627`).
- **Routing:** Restrukturisasi dan peningkatan routing untuk domain app dan cockpit dengan namespace `App` (`34a6714`, `41f0801`).
- **Testing:** Generasi unit test untuk seluruh service layer (`a035df0`).

### Changed
- **Code Quality:** Standardisasi pembersihan deadcode dan backward compatibility routing API (`6b7ca8b`, `4c7dc17`).

### Fixed
- **Cockpit:** Perbaikan tata letak layout form login Cockpit (`b62b1f2`).

---

## [v1.1.2] - 2026-08-17

### Added
- **Inventory Opname:** Dialog modal konfirmasi pada aksi stock opname serta perbaikan interaksi di `OpnameDetailPopUp` dan `OpnameFormPopUp`.

### Changed
- **Transaction Engine:** Penggantian job transaksi asinkron menjadi pemrosesan sinkron pada `TransactionController` dengan validasi data yang diperketat.
- **Filter Component:** Pembaruan struktur komponen filter dan peningkatan penanganan filter tanggal di `Filter.vue`.

---

## [v1.1.1] - 2026-08-16

### Fixed
- **Build / Vite:** Penyesuaian orientasi layar dari portrait ke landscape pada konfigurasi Vite.

---

## [v1.1.0] - 2026-08-16

### Added
- **Testing:** Peningkatan logging tests untuk lingkungan non-produksi.

### Changed
- **Employee & Customer Module:** Refactoring komponen Customer dan Employee untuk konsistensi antarmuka pengguna.
- **UI / Filters:** Pembaruan modal filter, opsi ekspor data, dan standarisasi label tombol di seluruh halaman.

---

## [v1.0.4] - 2026-08-15

### Added
- **Infrastructure:** Penambahan user directive untuk queue-worker dan Laravel Reverb pada konfigurasi `supervisord`.

---

## [v1.0.3] - 2026-08-15

### Added
- **Docker / Storage:** Penambahan direktori ekspor dan impor privat pada konfigurasi `Dockerfile`.

---

## [v1.0.2] - 2026-08-15

### Added
- **Console Command:** Command `EnsureStorageDirectories` untuk menjamin ketersediaan direktori penyimpanan.
- **Import / Export:** Refactoring penanganan konteks user pada background job import/export.

---

## [v1.0.0] - 2026-08-15

### Added
- **Storage & Logging:** Konfigurasi filesystem Cloudflare R2 dan integrasi notification logging channel Discord.
- **Core Release:** Rilis versi stabil pertama arsitektur multi-tenant Sollu App.
