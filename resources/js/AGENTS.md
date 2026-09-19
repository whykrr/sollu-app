---
trigger: always_on
---

# Wajib Perhatikan: Standar Frontend Vue 3 / Inertia / Tailwind v4 (Sollu App)

Saat membuat atau memodifikasi antarmuka di `resources/js`, Anda **WAJIB** mematuhi aturan baku berikut:

## 1. Standar Spacing & Margin/Padding Antarmuka

- **Spacing Skala 2 pada Layout & Halaman (`MainPage`):** Jarak antar-elemen/komponen di atas wrapper `<MainPage>` dan di dalam slot-nya WAJIB menggunakan skala 2 Tailwind (`gap-2`, `gap-y-2`, `gap-x-2`, `space-y-2`, `space-x-2`, `m-2`, `my-2`, `mt-2`, `mb-2`).
- **Batas Spacing Komponen Baru (Maksimal Skala 3):** Margin dan padding pada komponen baru atau child komponen DILARANG melebihi skala 3 (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`). Hindari padding/margin berlebih (`p-4`, `p-5`, `p-6` pada elemen interior).
- **Form Input Spacing (Skala 2):** Jarak antar-input formulir DILARANG melebihi skala 2 (`space-y-2`, `gap-2`).
- **Prinsip Flat Minimalis & Bebas Shadow pada Container `<MainPage>`:** Seluruh komponen di dalam `<MainPage>` (`#header`, `#widgets`, `#filter`, default slot, `#footer` seperti widget card, filter toolbar, action buttons, table) **DILARANG MENGGUNAKAN KELAS SHADOW** (`shadow`, `shadow-xs`, `shadow-sm`, `shadow-md`, dll). Gunakan garis batas halus (`border border-slate-200` / `border-gray-200`) dan warna latar solid/subtle (`bg-white` / `bg-slate-50`) untuk menjaga tampilan flat minimalis. Shadow hanya diizinkan untuk floating overlay elements (dropdown popover terbuka, modal dialog, toast).

## 2. Struktur Halaman Utama (`<MainPage>`) & Keseragaman Header

- **Wrapper Utama:** Semua halaman modul wajib menggunakan `<MainPage>`.
- **Tampilan Flat Minimalis (Tanpa Shadow):** Semua komponen di dalam slot `MainPage` harus flat tanpa drop-shadow.
- **Keseragaman Header (`<MainPageHeader>`):** Wajib menggunakan `<MainPageHeader :title="..." :description="...">` murni untuk judul dan deskripsi. Tombol aksi formulir dipindahkan ke `ActionBar` agar header bersih.
- **Hierarki Slot Non-Scrolling:**
    1. `<template #header>`: Judul halaman (`<MainPageHeader>`).
    2. `<template #widgets>`: Kartu KPI/ringkasan metrik (di antara header dan filter).
    3. `<template #filter>`: Toolbar terpadu `ActionBar` (filter, live search, dropdown `Opsi Data`, dan tombol `+ Tambah Data` di paling kanan).
    4. Default slot: Konten scrollable utama (`<Table>`).
    5. `<template #footer>`: Pagination bar.
       Seluruh elemen header, widget, dan filter WAJIB diletakkan di slot non-scrolling masing-masing agar **TIDAK ikut ter-scroll** saat tabel di default slot digulir.
- **Scrollable Content (Default Slot):** Komponen `<Table>` dan daftar data diletakkan di default slot `<MainPage>`.

## 3. Ekstraksi Wajib Komponen Filter

- Setiap halaman yang memiliki filter data (search bar, dropdown status, date range picker, dsb.) **WAJIB diekstrak ke file komponen terpisah** (misal: `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`). Dilarang menuliskan kontrol filter panjang secara inline di file `Index.vue`.

## 4. Standarisasi Tampilan Data Tabular (`<Table>`), Row Link & Sortable Header

- **Wajib Komponen `<Table>`:** Seluruh data tabular WAJIB menggunakan `@/Components/Tables/Table.vue`. Dilarang menulis tag `<table>` mentah.
- **Standar Single Action vs Multi Actions:**
    - **Single Action (Aksi Tunggal):** Jika tabel hanya memerlukan 1 aksi baris (membuka detail/edit), WAJIB gunakan event bawaan `@row-click="openDetail"` dan biarkan `:action="false"` (default). Dilarang mengaktifkan `:action="true"` untuk satu tombol saja.
    - **Multiple Actions (Banyak Aksi):** Gunakan `:action="true"` dengan `<template #actions="{ row }">` hanya jika terdapat lebih dari 1 aksi per baris.
- **Sortable Header Standard:** Setiap kolom yang dapat disortir WAJIB didefinisikan dengan `sortable: true` pada array `headers` dan meneruskan props `:sort="params?.sort"` serta `:sort-direction="params?.direction"` ke `<Table>`. Komponen akan menangani interaksi klik sort dan URL sync Inertia secara otomatis.
- **Empty State Terpusat:** Penanganan _empty state_ ("data tidak ditemukan") ditangani secara terpusat di level komponen `<Table>`. DILARANG membuat container `v-if="data.length === 0"` manual di masing-masing page.

## 5. Side Drawer (`<PopUpPage>` / `usePopUpStore()`) vs Modal Konfirmasi

- **`<PopUpPage>` / `usePopUpStore()` (Drawer Samping):** WAJIB untuk formulir _Create_, _Edit_, _Detail_, dan _Sub-page_. Dilarang menggunakan _full page redirect_ (`router.get()`) untuk sub-halaman.
- **Isolasi Padding PopUpPage:** Slot default / body `PopUpPage.vue` sudah memiliki padding bawaan di tingkat komponen. Child view yang dirender di dalam PopUpPage **TIDAK BOLEH** menambahkan wrapper padding/margin luar lagi (cukup `<form class="space-y-2">` atau `<div>`).
- **Sticky Footer Teleport:** Kirim tombol aksi di PopUpPage ke footer sticky drawer menggunakan `<Teleport v-if="isMounted" to="#popUpFooter">`.
- **`<Modal>` / `useModalStore()` (Center Dialog):** STRICTLY khusus untuk konfirmasi singkat (Hapus Data, Archive, Alert Peringatan).

## 6. Komponen Form Resmi (`@/Components/Form/`)

- Dilarang keras menggunakan tag raw HTML `<input>`, `<select>`, atau `<textarea>`. Wajib gunakan komponen dari `@/Components/Form/` (`TextField`, `DropdownField`, `NumberField`, `SelectionGroupField`, `Switch`, dll).
- Selalu bind `v-model` dan teruskan pesan error validasi ke `:feedback="form.errors.field"`.

## 7. PHP Enums & SaaS Feature Plan (No Magic Strings)

- **Kondisi Status & Tipe:** Gunakan `$enums.<EnumName>.<Case>` di template atau `useEnum()` di script setup.
- **Opsi Dropdown:** Gunakan `:options="getOptions('EnumName')"` via `useEnum()`.
- **Feature Plan Gating:** Gunakan directive `v-feature="$enums.FeatureEnum.NAME"` atau komponen `<FeatureLock :feature="$enums.FeatureEnum.NAME">`.
- **RBAC Permission:** Gunakan `v-can="'permission.name'"` atau `useAuth().can('permission.name')`.

## 8. Standar Ukuran Tombol (`.btn-xs`, `.btn-sm`, `.btn`, `.btn-lg`)

- Gunakan modifier ukuran tombol bawaan proyek:
    - `.btn-xs` (`px-2 py-1 gap-1 text-xs`): Tabel padat, badge inline action, sub-item drawer.
    - `.btn-sm` (`px-2 py-1.5 gap-1 text-xs`): Header actions (`MainPageHeader`), filter bar, aksi tabel umum.
    - `.btn` (Regular, `px-4 py-2 gap-2 text-sm`): Form submit, drawer footer, dialog modal utama.
    - `.btn-lg` (`px-6 py-3 text-base`): Hero CTA, POS primary checkout.

## 9. Linter, Build & Clean Code

- Hapus semua _dead code_ (unused imports, unused state/props, commented-out code).
- Jalankan `npm run fix:eslint` dan pastikan `npm run build` berhasil tanpa error.
