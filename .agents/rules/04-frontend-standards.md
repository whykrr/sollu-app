# Rule 04: Standar Antarmuka Frontend & Ergonomi UI

## 1. Layout `MainPage` & Non-Scrolling Sticky Hierarchy
Semua modul operasional **WAJIB** menggunakan layout `<MainPage>` dengan 5 slot baku:
1. `<template #header>`: Judul halaman (`MainPageHeader`) dan deskripsi.
2. `<template #widgets>`: Kartu KPI / ringkasan metrik.
3. `<template #filter>`: Toolbar terpadu `ActionBar`.
4. `default slot`: Konten scrollable utama (tabel data `<Table>` / analitik).
5. `<template #footer>`: Navigasi paginasi `<Pagination>`.
> Header, widget, dan filter toolbar **WAJIB** di slot non-scrolling (`#header`, `#widgets`, `#filter`). Default slot **HANYA** untuk tabel/konten scrollable.

---

## 2. Zero Shadow Invariant di dalam `<MainPage>`
- Seluruh komponen di dalam `<MainPage>` (kartu widget, toolbar, tombol, tabel, card) **DILARANG MENGGUNAKAN SHADOW** (`shadow`, `shadow-sm`, `shadow-md`, dll).
- Pemisahan visual mengandalkan border halus (`border-slate-200`) dan solid background (`bg-white`, `bg-slate-50`).
- *Pengecualian Shadow:* HANYA untuk floating overlay di luar flow halaman normal (popover menu `z-50`, modal dialog, toast).

---

## 3. Ekstraksi Filter Toolbar & Standar `ActionBar`
- **Ekstraksi Wajib:** Toolbar filter dan aksi halaman WAJIB diekstrak ke `{Entity}Filter.vue`. Dilarang inline di `Index.vue`.
- **DILARANG Filter Modal Popup:** Seluruh kontrol penyaringan tabel harus inline di `ActionBar`.
- **Struktur Slot `ActionBar` (`@/Components/UI/ActionBar/ActionBar.vue`):**
  - Kiri (`#filters` / `#left`): Filter data (`FilterPresetDate`, `FilterSegmented`, `FilterDropdown`).
  - Kanan: Pencarian `FilterSearch` (`#search`), dropdown ekspor/impor `<ActionsDropdown label="Opsi Data" />` (`#tools`), dan tombol utama `+ Tambah` (`#create` / `#primary`) di **PALING KANAN**.
- **Standarisasi Tinggi Form & Tombol Filter (30px):** Seluruh kontrol filter WAJIB berukuran `.form.sm` / `:size="'sm'"` dengan tinggi presisi **30px** (`h-[30px]`, `text-xs leading-4`). Dilarang `sm:text-sm` pada trigger button yang merusak keselarasan tinggi.

---

## 4. Komponen `<Table>` & Aksi Baris
- **Single Action (Aksi Tunggal):** Baris tabel dengan 1 aksi utama (detail/edit) **WAJIB** gunakan event `@row-click="openDetail"` dengan `:action="false"` (default). **DILARANG** mengaktifkan `:action="true"` hanya untuk satu tombol.
- **Multiple Actions:** Gunakan `:action="true"` dan slot `<template #actions="{ row }">` HANYA jika terdapat $\ge 2$ tombol aksi independen per baris.
- **Sortable Header:** Aktifkan `sortable: true` pada header kolom, dan teruskan `:sort="params?.sort"` serta `:sort-direction="params?.direction"`.

---

## 5. 3-Tier Form Architecture & Progressive Disclosure
- **Tier 1 (Simple $\le 5$ fields):** Layout vertikal flat (Kategori, UOM, Meja).
- **Tier 2 (Progressive $6-12$ fields):** 80% Core fields selalu tampil di atas + 20% Advanced fields dibungkus `<DisclosureSection>`. Input dependen hanya muncul saat trigger aktif (misal: min stok muncul jika switch lacak stok aktif).
- **Tier 3 (Complex $> 12$ fields):** Mode Create wajib menggunakan Linear Stepper (`<FormStepper>`), Mode Edit wajib menggunakan Direct Tabs (`<FormTabs>`). Sticky tombol navigasi di-teleport ke `#popUpFooter`.

---

## 6. Form Lifecycle & Dirty Guard (`useFormDirtyGuard`)
- Form Create/Edit di dalam drawer `<PopUpPage>` / modal **WAJIB** menggunakan `useFormDirtyGuard({ form })`.
- Tombol Batal **DILARANG** panggil `popUpStore.close()` langsung. Wajib panggil `handleCancel`.
- Handler `onSuccess` **WAJIB** panggil `forceClose()` agar drawer tertutup bersih tanpa dialog konfirmasi.

---

## 7. Ergonomi Multi-Device
- Target Sentuh: Mobile $\ge 44\times 44\text{px}$ (`.touch-target`), Tablet $\ge 36\times 36\text{px}$, Desktop $\ge 28\text{px}$/30px.
- Anti-Zoom iOS Safari: Input mobile wajib berukuran font $\ge 16\text{px}$ (`.form.adaptive` / `text-base sm:text-xs`).
- Thumb Zone: Aksi submit utama di mobile wajib berada di sticky footer (`#popUpFooter`).
- Responsive Columns: Kolom tabel sekunder wajib disembunyikan di smartphone menggunakan prop `show: 'md'` atau `show: 'lg'`.
