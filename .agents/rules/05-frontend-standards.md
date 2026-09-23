---
trigger: always_on
---

# Rule 05: Standar Antarmuka Frontend & Ergonomi UI

## 1. Layout `MainPage` & Non-Scrolling Sticky Hierarchy

Seluruh halaman modul operasional **WAJIB** menggunakan layout terpadu `<MainPage>`.

### Hierarki 5 Slot Terstandarisasi:
1. `<template #header>`: Judul halaman (`MainPageHeader`) dan keterangan/deskripsi. Tombol aksi utama dipindahkan ke `ActionBar` agar header tetap bersih dan fokus.
2. `<template #widgets>`: Kartu KPI, ringkasan metrik, atau widget analitik (terletak tepat di bawah header).
3. `<template #filter>` (atau `#filters`): Bar toolbar terpadu (`ActionBar`) yang membungkus filter data, pencarian live, dropdown `Opsi Data`, dan tombol tambah data di paling kanan.
4. `default slot`: Konten scrollable utama (tabel data `<Table>` atau visual analitik).
5. `<template #footer>`: Navigasi paginasi `<Pagination>` atau bilah aksi bawah.

> [!IMPORTANT]
> Seluruh header, widget, dan filter toolbar **WAJIB** berada di slot non-scrolling (`#header`, `#widgets`, `#filter`), **BUKAN** di default slot. Default slot **HANYA** untuk tabel data atau konten yang dapat di-scroll.

---

## 2. Prinsip Desain Flat Minimalis & Larangan Shadow di dalam `<MainPage>`

- Seluruh komponen yang diletakkan di dalam container `<MainPage>` (kartu widget, toolbar filter, tombol aksi, tabel, card container, dsb.) **DILARANG MENGGUNAKAN KELAS SHADOW** (`shadow`, `shadow-xs`, `shadow-sm`, `shadow-md`, `shadow-lg`, dsb.).
- Sollu App mempertahankan tampilan **Flat Minimalis**: pemisahan dan penegasan visual antar elemen wajib mengandalkan garis batas halus (`border border-slate-200` / `border-gray-200` atau `border-neutral-200`) serta latar warna solid/subtle (`bg-white` / `bg-slate-50`), bukan drop-shadow.
- **Pengecualian Efek Shadow:** HANYA diizinkan untuk elemen melayang di luar alur halaman normal (*floating overlays*), seperti popover dropdown menu terbuka (`z-50`), dialog modal konfirmasi (`.overlay-modal`), dan toast notifications (`Toast.vue`).

---

## 3. Batasan Spacing & Skala Tailwind

- Jarak antar-komponen di atas `<MainPage>` dan di dalam slot-nya **WAJIB berskala 2** (`gap-2`, `gap-y-2`, `gap-x-2`, `space-y-2`, `space-x-2`, `m-2`, `my-2`, `mt-2`, `mb-2`).
- Margin dan padding pada komponen baru **DILARANG melebihi skala 3** (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`).
- Jarak antar-input formulir **DILARANG melebihi skala 2** (`space-y-2`, `gap-2`).
- **Isolasi Spacing `PopUpPage`:** Body `.modal-body` di `PopUpPage.vue` sudah memiliki padding bawaan. Child form/view yang dirender di dalam PopUpPage **DILARANG** menambahkan wrapper padding atau margin luar lagi.

---

## 4. Ekstraksi Wajib Komponen Filter & Standarisasi `ActionBar`

- **Ekstraksi Wajib:** Seluruh toolbar filter dan aksi halaman **WAJIB** diekstrak ke komponen terpisah di `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`. Dilarang menulis toolbar inline di `Index.vue`.
- **Tata Letak Baku `ActionBar` (`@/Components/UI/ActionBar/ActionBar.vue`):**
  - **Sisi Kiri (`#filters` / `#left`):** Kontrol penyaringan data (preset tanggal `FilterPresetDate`, status segmented `FilterSegmented`, dropdown entitas `FilterDropdown`) dalam scroll track responsif.
  - **Sisi Kanan:** Input pencarian `FilterSearch` (`#search`), dropdown berkas `Opsi Data` (`#tools`), dan tombol utama Tambah Data `+ Tambah` (`#create` / `#primary`) di posisi **PALING KANAN**.
- **🚨 DILARANG MENGGUNAKAN POPUP MODAL UNTUK FILTER TABEL:** Dilarang membuat modal dialog popup (`FilterModal.vue`) untuk menyaring data tabel. Seluruh kontrol penyaringan harus tampil terpadu secara inline di dalam `ActionBar`.
- **Standarisasi Dropdown Ekspor / Impor (`Opsi Data`):** Aksi ekspor spreadsheet/PDF dan impor data massal **WAJIB** dibungkus ke dalam dropdown berwording **`Opsi Data`** menggunakan `<ActionsDropdown label="Opsi Data" :items="actionItems" />` pada slot `#tools` di `ActionBar`. Dilarang membuat tombol ekspor/impor terpisah secara horizontal yang memakan ruang toolbar.
- **Standarisasi Date Presets:** Preset rentang tanggal terpusat pada enum `App\Enums\DatePresetEnum` (`today`, `yesterday`, `last_7_days`, `last_30_days`, `this_month`, `last_month`, `this_year`, `custom`) dengan nilai bawaan (*default*) adalah `this_month`.

---

## 5. Komponen `<Table>`, Row Link (Single Action), Sortable Header & Empty State

- Seluruh data tabel **WAJIB** ditampilkan melalui `@/Components/Tables/Table.vue`. Dilarang menggunakan tag `<table>` mentah.
- **Standar Aksi Baris (Single Action vs Multiple Actions):**
  - **Single Action (Aksi Tunggal):** Jika baris tabel hanya memiliki 1 aksi utama (seperti membuka Drawer Detail atau Form Edit), **WAJIB** gunakan event bawaan `@row-click="openDetail"` atau `@row-click="openEdit"`, dan biarkan properti `:action` bernilai `false` (default). **DILARANG** mengaktifkan `:action="true"` dengan slot `#actions` yang hanya berisi satu tombol tunggal.
  - **Multiple Actions (Banyak Aksi):** Gunakan `:action="true"` dan definisikan slot `<template #actions="{ row }">` HANYA jika terdapat lebih dari 1 tombol aksi independen pada setiap baris (misal: Download PDF + Hapus, atau Print Struk + Void).
  - **Read-Only:** Gunakan `:action="false"` tanpa listener `@row-click` jika tabel murni menampilkan data tanpa interaksi klik baris.
- **Sortable Header Standard:**
  - Aktifkan `sortable: true` pada kolom header yang dapat disortir (`headers: [{ label: 'Nama', field: 'name', sortable: true }]`).
  - Teruskan properti sort aktif ke komponen: `<Table :headers="headers" :data="items.data" :sort="params?.sort" :sort-direction="params?.direction" @row-click="openDetail">`.
  - Komponen `<Table>` secara otomatis menangani toggle sorting (asc/desc), visual ikon (`faSort`, `faSortUp`, `faSortDown`), dan request navigasi Inertia (`router.get`) dengan mempertahankan query filter dan scroll.
- **Backend Sortable Integration:**
  - Model Eloquent WAJIB menggunakan trait `App\Trait\SortableModel` dan mendeklarasikan whitelist kolom pada `protected array $sortable = [...]`.
  - Form Request (`Get{Entity}Request`) WAJIB memvalidasi parameter `sort` (`nullable|string`) dan `direction` (`nullable|in:asc,desc`).
  - Controller `index()` WAJIB menerapkan method `->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))`.
- **Empty State Terpusat:** Penanganan *empty state* dikelola terpusat di level komponen `<Table>`. **DILARANG** menduplikasi blok `v-if="data.length === 0"` manual di masing-masing page.

---

## 6. Pemanfaatan Komponen Bawaan Proyek

AI Agent **WAJIB** membaca dan mematuhi panduan `AGENTS.md` di folder `resources/js/Components/` dan mengutamakan komponen bawaan sebelum membuat markup baru:
- Input Form: `TextField`, `DropdownField`, `NumberField`, `SelectionGroupField`, `Switch`, `AsyncSelectField`.
- Layout & Navigasi: `Table`, `Pagination`, `Widget`, `Modal`, `PopUpPage`, `ActionBar`.

---

## 7. Standar Ukuran Tombol (`.btn`)

Gunakan hierarki ukuran tombol bawaan proyek di `app.css` secara konsisten:
- **`.btn-xs` (`px-2 py-1 gap-1 text-xs`):** Khusus aksi baris tabel yang sangat padat, inline badge/tag toggle, atau sub-item aksi di dalam drawer/nested card.
- **`.btn-sm` (`px-2 py-1.5 gap-1 text-xs`):** Tombol aksi standar pada `MainPageHeader`, toolbar filter, dan aksi baris tabel umum.
- **`.btn` / Regular (`px-4 py-2 gap-2 text-sm`):** Tombol utama form submit, modal confirmation, dan CTA standar.
- **`.btn-lg` (`px-6 py-3 text-base`):** Tombol aksi hero / landing banner / checkout POS utama.

---

## 8. Standar Form & Utility Classes Kustom (`app.css`)

- **`.form`:** Base styling untuk input teks, select, textarea dengan ring focus brand dan rounded border.
- **`.form.sm` (`class="form sm"`):** Ukuran input ringkas (`text-xs! !py-1.5 !px-2.5`, tinggi 30px). **Wajib digunakan untuk seluruh kontrol filter tabel**, compact form di dalam drawer, dan tabel nested.
- **`.form.lg` (`class="form lg"`):** Ukuran input besar (`text-base! !py-3 !px-5`) untuk search hero dan checkout POS.
- **Standarisasi Keselarasan Tinggi Dropdown & Form `sm` (30px):** Seluruh elemen pada toolbar filter (input pencarian `FilterSearch`, tombol filter `FilterDropdown`, `FilterPresetDate`, `ExportDropdown`, `FilterSegmented`, serta komponen form `DropdownField`, `AsyncSelectField`, `GroupDropdownIconField` dengan prop `:size="'sm'"`) **WAJIB** memiliki tinggi seragam **30px** (`h-[30px]`, `text-xs leading-4`). **DILARANG** menggunakan varian teks responsive `sm:text-sm` pada tombol trigger yang membuat tinggi tombol tidak selaras dengan input `form sm`.
- **`.form-group` & `.form-group-text`:** Container terpadu untuk input ber-addon/ikon. Mendukung modifier `.form-group.sm` atau selector `:has(.form.sm)` yang otomatis menyelaraskan ukuran font dan padding addon ke `text-xs !py-1.5 !px-2.5` (tinggi 30px).
- **`.form-check` (`.sm` / `.lg`):** Wrapper checkbox/radio button terstandarisasi.
- **`.filter-badge` & `.filter-badge-remove`:** Badge kriteria filter aktif dengan tombol hapus tag `✕`.

---

## 9. Standar Ergonomi UI Multi-Perangkat (Laptop, Tablet, Smartphone)

- **Target Sentuh Minimum (Touch Targets):**
  - Smartphone / Mobile (`< sm`): Minimal **44×44px** (`.touch-target`) untuk aksi utama, tombol navigasi, dan tap targets sentuh.
  - Tablet / POS Terminal (`sm` - `md`): Minimal **36×36px** (`.touch-target-sm`) dengan padding hit-area yang cukup untuk jari staf/kasir.
  - Laptop / Desktop (`>= lg`): Minimal **28×28px** atau 30px tinggi standar (`.form.sm`, `.btn-sm`) untuk kerapatan data tinggi.
- **Pencegahan Forced Auto-Zoom iOS Safari:**
  - Seluruh input formulir pada tampilan mobile (`< sm`) **WAJIB** menggunakan komputasi font `16px` (`text-base sm:text-xs` atau `.form.adaptive`) agar Safari tidak melakukan zoom in otomatis saat mengetik.
- **Zona Jangkauan Jempol (Thumb Zone):**
  - Aksi formulir utama (Simpan, Bayar, Konfirmasi) di mobile **WAJIB** berada di sticky footer bawah (`#popUpFooter`), bukan di bagian atas.
- **Safe Area Insets & Dynamic Viewport (`100dvh`):**
  - Drawer dan modal wajib menggunakan `100dvh` dan padding safe-area (`.safe-pb`, `.safe-pt`) agar tidak terpotong oleh browser bar dan home gesture indicator.
- **Tabel Responsif & Kolom Prioritas:**
  - Kolom sekunder wajib dikonfigurasi dengan prop responsif `show: 'md'` atau `show: 'lg'` pada array `headers` komponen `<Table>` sehingga di smartphone tidak terjadi overflow horizontal yang merusak layout.
- *Lihat panduan lengkap di `docs/ui-ergonomics.md`.*

---

## 10. Standar Desain Formulir: Progressive Disclosure & Wizard Pattern (3-Tier Form Architecture)

- **Klasifikasi 3-Tier Formulir:**
  - **Tier 1 - Simple / Quick Form ($\le 5$ fields):** Tampilan flat vertikal tanpa collapsible/stepper. Khusus master data ringkas (Kategori, Satuan UOM, Meja Kasir, Alasan Void).
  - **Tier 2 - Progressive Disclosure Form ($6-12$ fields, single domain):**
    - **Prinsip 80/20 Pareto Core Fields:** 80% input harian utama (Nama, Harga, Kategori) diletakkan di bagian atas (selalu tampak).
    - **Smart Collapsible (`<DisclosureSection>`):** 20% input lanjutan/opsional (SKU kustom, Barcode manual, Alert stok minimum, Tag, Catatan) **WAJIB** dibungkus dalam `<DisclosureSection>`. Otomatis terbuka jika ada error validasi di dalamnya.
    - **Contextual / Trigger Reveal:** Input dependen (misal: *Minimum Stok*) dilarang dirender jika trigger utamanya (*Lacak Stok*) belum diaktifkan.
  - **Tier 3 - Complex Wizard & Tabbed Form ($> 12$ fields atau multi-domain):**
    - **Asimetri Create vs Edit:** Mode Create **WAJIB** menggunakan **Linear Stepper** (`<FormStepper>`) memandu langkah demi langkah (Step 1 $\rightarrow$ Step 2 $\rightarrow$ Step 3). Mode Edit **WAJIB** menggunakan **Direct Tabbed Navigation** (`<FormTabs>`) agar user dapat langsung menuju tab yang ingin diedit tanpa mengulang stepper.
    - **Error Badging & Step Validation:** Stepper/Tabs wajib menerima properti `:errors="form.errors"` untuk memunculkan indikator titik merah (*error dot*) pada langkah/tab yang bermasalah.
    - **Sticky Teleport Footer:** Tombol navigasi (Batal, Kembali, Lanjut, Simpan) **WAJIB** di-teleport ke `#popUpFooter` di *Thumb Zone* bawah.

---

## 11. Standar Form Lifecycle & Penanganan Form Dirty (`useFormDirtyGuard`)

- **Wajib Menggunakan `useFormDirtyGuard`:** Seluruh formulir Create dan Edit di dalam Drawer `<PopUpPage>` atau dialog modal **WAJIB** menggunakan composable `@/Composable/useFormDirtyGuard({ form })`.
- **Dilarang Bypass Store Close pada Tombol Batal:** Tombol Batal pada formulir **DILARANG KERAS** memanggil `popUpStore.close()` secara langsung di template (`@click="popUpStore.close()"` adalah anti-pattern). Tombol Batal **WAJIB** memicu method `handleCancel` dari `useFormDirtyGuard`.
- **Wajib `forceClose()` pada `onSuccess`:** Saat form berhasil disubmit, handler `onSuccess` **WAJIB** memanggil `forceClose()` agar drawer tertutup bersih tanpa memicu modal konfirmasi.
- **Standar Dialog Konfirmasi:** Jika form memiliki perubahan data (`isDirty === true`), penutupan via tombol Batal, tombol silang `✕` Header, klik backdrop, maupun tombol keyboard `Escape` otomatis memunculkan dialog konfirmasi terstandarisasi:
  - Judul: *"Perubahan Belum Disimpan"*
  - Pesan: *"Kamu memiliki perubahan data yang belum disimpan. Yakin mau membatalkan dan keluar dari formulir ini?"*
  - Tombol Konfirmasi: *"Ya, Buang Perubahan"* (`btn-danger`)
  - Tombol Batal: *"Lanjut Mengisi"* (sekunder)
