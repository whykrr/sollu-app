---
trigger: always_on
---

# Rule: Sollu App Core (Enums & Feature Plan)

## A. PHP Enums as Single Source of Truth

**1. Prinsip Utama (No Magic Strings)**
Backend (`app/Enums/*.php`) adalah master. DILARANG menggunakan string literal/hardcode di Frontend (Vue) untuk memvalidasi status/tipe (misal: `v-if="status === 'draft'"`).

**2. Distribusi (Inertia)**
Enum didistribusikan otomatis via Inertia Shared Props (`$enums`). Daftarkan enum baru ke array `$frontendEnums` di `app/Support/Enums/FrontendEnumProvider.php`.

**3. Standar Frontend**

- **Template:** Gunakan `$enums.NamaEnum.Kasus`. Contoh: `v-if="item.status === $enums.AdjustmentStatus.Draft"`
- **Script Setup:** Gunakan composable: `const { enums, getOptions, getLabel, getColor } = useEnum()`.
- **Form/Dropdown:** Gunakan `getOptions('NamaEnum')` untuk properti `:options` komponen `DropdownField` / `SelectionGroupField`. Jangan hardcode array opsi!

**4. Dynamic Entities (Bukan Enum)**
Tipe Bisnis (`BusinessType`) bersifat 100% dinamis di database (`business_types` table). DILARANG KERAS mencari/membuat `BusinessTypeEnum`. Ambil opsi dropdown/grup via `BusinessType::getAllCached()`, `BusinessType::options()`, atau `BusinessType::grouped()`.

---

## B. Validasi Feature Plan (SaaS Entitlement)

**1. Dual-Layer Auth**

- **User RBAC:** Hak akses jabatan (`PermissionEnum`, `v-can`). UI: Sembunyikan elemen. Operasional ekspor/impor data diatur murni via RBAC (`permission: product.export`, dsb.), DILARANG di-gate oleh `plan.feature`.
- **Tenant Feature:** Kuota/paket bisnis (`FeatureEnum`, `v-feature`). UI: Tampilkan elemen terkunci (upsell). DILARANG pakai fungsi permission (RBAC) untuk cek fitur paket.

**2. Standar Frontend**

- **Directive:** Gunakan `v-feature="$enums.FeatureEnum.NAME"` atau `v-feature.all=[...]`
- **Tampilan Terkunci:** WAJIB bungkus dengan komponen `<FeatureLock :feature="...">` atau gunakan `<FeatureLockOverlay>`. Hindari `v-feature.lock` manual agar tidak layout thrashing.
- **Script Setup:** Gunakan `const { hasFeature, requireFeature } = usePlanFeature()` bersama `useEnum()`.

**3. Standar Backend**

- Definisikan key konstan di `app/Enums/FeatureEnum.php`, simpan metadata fitur (nama, deskripsi, modul, grup) di tabel database `features` (`FeatureSeeder.php`), dan petakan fitur ke paket melalui tabel pivot database `plan_features` (`SubscriptionPlanSeeder.php` atau Cockpit UI). DILARANG meng-hardcode relasi fitur di `PlanEnum.php`.
- Proteksi route dengan `middleware('plan.feature:' . FeatureEnum::NAME->value)`.
- Dukungan Custom Plan (`is_custom: true`, `is_public: false`) berjalan secara native: resolusi fitur tenant membaca langsung relasi database `plan->systemFeatures` tanpa perlu mendaftarkan kode paket baru ke PHP Enum.

---

## C. Code Formatting & Style Standards

**1. PHP Standards (Laravel Pint)**
- **Formatter:** Gunakan `composer run format` (`vendor/bin/pint`) sebagai standar tunggal berbasis `pint.json`.
- **Method Chaining:** Pemanggilan berantai lebih dari 1 method (misal pada Eloquent Query Builder, Collection, atau fluent interface) **WAJIB dipecah multiline (satu method per baris)** untuk mencegah kode horizontal yang terlalu panjang:
  ```php
  // BENAR
  $users = User::query()
      ->where('business_id', $businessId)
      ->where('is_active', true)
      ->orderBy('name')
      ->get();

  // SALAH (terlalu panjang secara horizontal)
  $users = User::query()->where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get();
  ```
- **Ruleset:** Diatur otomatis oleh `pint.json` dengan `method_chaining_indentation: true`.

**2. Frontend Standards (Prettier & ESLint)**
- **Formatter:** Prettier (`.prettierrc`) adalah penentu tata letak visual: `tabWidth: 4`, `useTabs: false`, `singleQuote: true`, `semi: false` (tanpa semicolon), `printWidth: 100`.
- **Linter:** ESLint (`eslint.config.js`) fokus pada deteksi logic error, unused variables, dan Vue template rules.
- **Commands:** Jalankan `npm run format` untuk auto-format dan `npm run lint` untuk cek kode.
- **VS Code:** Selalu gunakan setting workspace yang telah dikonfigurasi (`editor.formatOnSave: true`, formatters per bahasa).

---

## D. Frontend Page Creation & Spacing Standards

**1. Layout `MainPage` & Non-Scrolling Header**
- Seluruh halaman modul wajib menggunakan `<MainPage>`.
- Seluruh kartu (*cards*), widget KPI (*widgets*), bar pencarian & filter (*filters*), serta tombol aksi WAJIB berada di slot `<template #header>` (atau `<MainPageHeader>`), BUKAN di default slot. Default slot HANYA untuk tabel data / konten scrollable.

**2. Batasan Spacing & Skala Tailwind**
- Jarak antar-komponen di atas `<MainPage>` dan di dalam slot-nya WAJIB berskala 2 (`gap-2`, `gap-y-2`, `gap-x-2`, `space-y-2`, `space-x-2`, `m-2`, `my-2`, `mt-2`, `mb-2`).
- Margin dan padding pada komponen baru DILARANG melebihi skala 3 (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`).
- Jarak antar-input formulir DILARANG melebihi skala 2 (`space-y-2`, `gap-2`).

**3. Isolasi Spacing `PopUpPage`**
- Body `.modal-body` di `PopUpPage.vue` sudah memiliki padding bawaan. Child form/view yang dirender di dalam PopUpPage DILARANG menambahkan wrapper padding atau margin luar lagi.

**4. Ekstraksi Wajib Komponen Filter**
- Seluruh filter halaman WAJIB diekstrak ke komponen terpisah di `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`. DILARANG menulis filter inline di `Index.vue`.

**5. Wajib Menggunakan Komponen `<Table>` & Empty State Terpusat**
- Seluruh data tabel WAJIB ditampilkan melalui `@/Components/Tables/Table.vue`. DILARANG menggunakan tag `<table>` mentah.
- Penanganan *empty state* ("data tidak ditemukan") dikelola terpusat di level komponen `<Table>`. DILARANG menduplikasi blok `v-if="data.length === 0"` manual di masing-masing page.

**6. Pemanfaatan Maksimal Komponen Bawaan Proyek**
- AI Agent WAJIB membaca dan mematuhi panduan `AGENTS.md` di folder `resources/js/Components/` dan mengutamakan komponen bawaan (`TextField`, `DropdownField`, `NumberField`, `SelectionGroupField`, `Switch`, `Table`, `Pagination`, `Widget`, `Modal`, dll) sebelum membuat markup baru.



