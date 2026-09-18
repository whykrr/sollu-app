# Changelog

Semua perubahan penting pada proyek **Sollu App** didokumentasikan di file ini.
Format ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan versi rilis mengikuti [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

### Added
- **Shift Management:** Peningkatan antarmuka (UI) manajemen shift kasir dan fungsionalitas operasional kasir harian (`c86d4e8`).
- **Billing & Subscription:** 
  - Manajemen Subscription Plan di Cockpit dengan fitur sinkronisasi kuota plan (`57aa1b8`, `caa0e75`).
  - Fitur perpanjangan langganan (subscription renewal), notifikasi, dan pembuatan invoice (`9633467`).
  - Penolakan pembayaran invoice dengan notifikasi dan service layer terkait (`f1e9955`).
  - Halaman pengaturan tagihan (Billing Settings) dan peningkatan alur penanganan invoice (`16df53b`, `fc4ad11`).
- **Access Control (RBAC):** Modul Role-Based Access Control dengan sinkronisasi fitur dinamis antar guard (`e564ec2`).
- **Master Data (Business Type):** Halaman manajemen Business Type dengan fungsionalitas CRUD lengkap (`c4f7b14`).
- **Promotions & Modifiers:** Route manajemen promosi, middleware feature flag, serta on-demand data loading untuk promosi dan modifier (`83b15d6`, `c24a792`).
- **Shared Enums:** `FrontendEnumProvider` untuk mendistribusikan PHP Enums ke frontend via Inertia Shared Props (`2f24688`).
- **Testing:** Unit & feature tests untuk `SubscriptionController`, `RoleController`, `RememberMe`, serta coverage service layer Business & User (`fa4c17a`, `ea8c04f`).

### Changed
- **Product Module:** Optimasi state loading saat reorder kategori produk dan penyelarasan label antarmuka (`accc5ee`).
- **UI & Table Component:** Perbaikan z-index header tabel dan integrasi sorting fungsional bawaan (`ede9799`).
- **Code Standards & Guidelines:** Penerapan standar formatting PHP (Laravel Pint) dan frontend (Prettier/ESLint) serta panduan komponen UI (`70ca198`, `7cc8648`).
- **Developer Experience:** Konfigurasi watch Vite dan penambahan konfigurasi VSCode workspace (`285de43`, `f3ad672`).
- **MCP Ecosystem:** Standardisasi integrasi Git MCP dan refactoring konfigurasi tooling development (`173e5a3`, `2f96a0a`).

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
