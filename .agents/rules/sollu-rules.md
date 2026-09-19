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

**5. Sinkronisasi Template Peran & Hak Akses (Zero-Orphan Permission Policy)**
Setiap kali ada penambahan kasus baru pada `App\Enums\PermissionEnum`:
- WAJIB menetapkan method `label()`, `group()`, dan `groupLabel()`.
- WAJIB memetakan kasus permission baru tersebut ke dalam template peran POS yang relevan pada `App\Enums\RoleTemplateEnum` (misal: permission operasional F&B dipetakan ke `CASHIER_FNB`, `MANAGER_FNB`, dsb.).
- WAJIB menjalankan test otomatis `tests/Unit/Enums/RoleTemplateIntegrityTest.php` untuk memastikan seluruh relasi hak akses dan template peran tetap sinkron dan valid.

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
- Dukungan Custom Plan (penugasan `business_id`, `is_public: false`) berjalan secara native: resolusi fitur tenant membaca langsung relasi database `plan->systemFeatures` tanpa perlu mendaftarkan kode paket baru ke PHP Enum.

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
- **Hierarki Slot Terstandarisasi:**
    1. `<template #header>`: Judul halaman (`MainPageHeader`) dan keterangan/deskripsi. Tombol aksi utama formulir/tambah data dipindahkan ke `ActionBar` agar header tetap bersih dan fokus.
    2. `<template #widgets>`: Kartu KPI, ringkasan metrik, atau widget analitik (terletak di antara header title dan filter toolbar).
    3. `<template #filter>` (atau `#filters`): Bar toolbar terpadu (`ActionBar`) yang membungkus filter data, pencarian live, dropdown `Opsi Data`, dan tombol tambah data di paling kanan.
    4. `default slot`: Konten scrollable utama (tabel data `<Table>` atau visual analitik).
    5. `<template #footer>`: Navigasi paginasi `<Pagination>` atau bilah aksi bawah.
- Seluruh header, widget, dan filter toolbar WAJIB berada di slot non-scrolling (`#header`, `#widgets`, `#filter`), BUKAN di default slot. Default slot HANYA untuk tabel data / konten scrollable.

**2. Batasan Spacing & Skala Tailwind**

- Jarak antar-komponen di atas `<MainPage>` dan di dalam slot-nya WAJIB berskala 2 (`gap-2`, `gap-y-2`, `gap-x-2`, `space-y-2`, `space-x-2`, `m-2`, `my-2`, `mt-2`, `mb-2`).
- Margin dan padding pada komponen baru DILARANG melebihi skala 3 (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`).
- Jarak antar-input formulir DILARANG melebihi skala 2 (`space-y-2`, `gap-2`).

**3. Isolasi Spacing `PopUpPage`**

- Body `.modal-body` di `PopUpPage.vue` sudah memiliki padding bawaan. Child form/view yang dirender di dalam PopUpPage DILARANG menambahkan wrapper padding atau margin luar lagi.

**4. Ekstraksi Wajib Komponen Filter & Standarisasi `ActionBar`**

- Seluruh toolbar filter dan aksi halaman WAJIB diekstrak ke komponen terpisah di `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`. DILARANG menulis toolbar inline di `Index.vue`.
- **Standarisasi `ActionBar`:** Seluruh komponen toolbar WAJIB menggunakan tata letak terpadu berbasis `@/Components/UI/ActionBar/ActionBar.vue`:
    - **Sisi Kiri (`#filters` / `#left`):** Kontrol penyaringan data (preset tanggal `FilterPresetDate`, status segmented `FilterSegmented`, dropdown entitas `FilterDropdown`) dalam scroll track responsif.
    - **Sisi Kanan:** Input pencarian `FilterSearch` (`#search`), dropdown berkas `Opsi Data` (`#tools`), dan tombol utama Tambah Data `+ Tambah` (`#create` / `#primary`) di posisi **PALING KANAN**.
- **DILARANG MENGGUNAKAN POPUP MODAL UNTUK FILTER TABEL:** Dilarang membuat modal dialog popup (`FilterModal.vue`) untuk menyaring data tabel. Seluruh kontrol penyaringan harus tampil terpadu secara inline di dalam `ActionBar`.
- **Standarisasi Dropdown Ekspor / Impor (`Opsi Data`):** Aksi ekspor spreadsheet/PDF dan impor data massal WAJIB dibungkus ke dalam dropdown berwording **`Opsi Data`** menggunakan `<ActionsDropdown label="Opsi Data" :items="actionItems" />` pada slot `#tools` di `ActionBar`. DILARANG membuat tombol ekspor/impor terpisah secara horizontal yang memakan ruang toolbar.
- **Standarisasi Date Presets Backend & Frontend:** Preset rentang tanggal terpusat pada enum `App\Enums\DatePresetEnum` (`today`, `yesterday`, `last_7_days`, `last_30_days`, `this_month`, `last_month`, `this_year`, `custom`) dengan nilai bawaan (_default_) adalah `this_month`.

**5. Wajib Menggunakan Komponen `<Table>`, Row Link (Single Action), Sortable Header, & Empty State Terpusat**

- Seluruh data tabel WAJIB ditampilkan melalui `@/Components/Tables/Table.vue`. DILARANG menggunakan tag `<table>` mentah.
- **Standar Aksi Baris (Single Action vs Multiple Actions):**
    - **Single Action (Aksi Tunggal):** Jika baris tabel hanya memiliki 1 aksi utama (seperti membuka Drawer Detail atau Form Edit), **WAJIB** gunakan event bawaan `@row-click="openDetail"` atau `@row-click="openEdit"`, dan biarkan properti `:action` bernilai `false` (default). **DILARANG** mengaktifkan `:action="true"` dengan slot `#actions` yang hanya berisi satu tombol tunggal.
    - **Multiple Actions (Banyak Aksi):** Gunakan `:action="true"` dan definisikan slot `<template #actions="{ row }">` HANYA jika terdapat lebih dari 1 tombol aksi independen pada setiap baris (misal: Download PDF + Hapus, atau Print Struk + Void).
    - **Read-Only:** Gunakan `:action="false"` tanpa listener `@row-click` jika tabel murni menampilkan data tanpa interaksi klik baris.
- **Sortable Header Standard:**
    - Aktifkan `sortable: true` pada kolom header yang dapat disortir (`headers: [{ label: 'Nama', field: 'name', sortable: true }]`).
    - Teruskan properti sort aktif ke komponen: `<Table :headers="headers" :data="items.data" :sort="params?.sort" :sort-direction="params?.direction" @row-click="openDetail">`.
    - Komponen `<Table>` secara otomatis menangani toggle sorting (asc/desc), visual ikon (`faSort`, `faSortUp`, `faSortDown`), dan request navigasi Inertia (`router.get`) dengan mempertahankan query filter dan scroll.
- **Backend Sortable Integration:**
    - Model Eloquent WAJIB menggunakan trait `App\Trait\SortableModel` dan mendeklarasikan whitelist kolom yang dapat diurutkan pada properti `protected array $sortable = [...]`.
    - Form Request (`Get{Entity}Request`) WAJIB memvalidasi parameter `sort` (`nullable|string`) dan `direction` (`nullable|in:asc,desc`).
    - Controller `index()` WAJIB menerapkan method `->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))` pada query builder dan mengirimkan `params` ke Inertia props.
- **Empty State Terpusat:** Penanganan _empty state_ ("data tidak ditemukan") dikelola terpusat di level komponen `<Table>`. DILARANG menduplikasi blok `v-if="data.length === 0"` manual di masing-masing page.

**6. Pemanfaatan Maksimal Komponen Bawaan Proyek**

- AI Agent WAJIB membaca dan mematuhi panduan `AGENTS.md` di folder `resources/js/Components/` dan mengutamakan komponen bawaan (`TextField`, `DropdownField`, `NumberField`, `SelectionGroupField`, `Switch`, `Table`, `Pagination`, `Widget`, `Modal`, dll) sebelum membuat markup baru.

**7. Standar Ukuran Tombol (`.btn`, `.btn-xs`, `.btn-sm`, `.btn-lg`)**

- Gunakan hierarki ukuran tombol bawaan proyek di `app.css` secara konsisten:
    - **`.btn-xs` (`px-2 py-1 gap-1 text-xs`):** Khusus aksi baris tabel yang sangat padat, inline badge/tag toggle, atau sub-item aksi di dalam drawer/nested card.
    - **`.btn-sm` (`px-2 py-1.5 gap-1 text-xs`):** Tombol aksi standar pada `MainPageHeader`, toolbar filter, dan aksi baris tabel umum.
    - **`.btn` / Regular (`px-4 py-2 gap-2 text-sm`):** Tombol utama form submit, modal confirmation, dan CTA standar.
    - **`.btn-lg` (`px-6 py-3 text-base`):** Tombol aksi hero / landing banner / checkout POS utama.

**8. Standar Form & Utility Classes Kustom (`app.css`)**

- Gunakan kelas custom form yang telah didefinisikan di `resources/css/app.css`:
    - **`.form`:** Base styling untuk input teks, select, textarea dengan ring focus brand dan rounded border.
    - **`.form.sm` (`class="form sm"`):** Ukuran input ringkas (`text-xs! !py-1.5 !px-2.5`, tinggi 30px). **Wajib digunakan untuk seluruh kontrol filter tabel**, compact form di dalam drawer, dan tabel nested.
    - **`.form.lg` (`class="form lg"`):** Ukuran input besar (`text-base! !py-3 !px-5`) untuk search hero dan checkout POS.
    - **Standarisasi Keselarasan Tinggi Dropdown & Form `sm` (30px):** Seluruh elemen pada toolbar filter (input pencarian `FilterSearch`, tombol filter `FilterDropdown`, `FilterPresetDate`, `ExportDropdown`, `FilterSegmented`, serta komponen form `DropdownField`, `AsyncSelectField`, `GroupDropdownIconField` dengan prop `:size="'sm'"`) WAJIB memiliki tinggi seragam **30px** (`h-[30px]`, `text-xs leading-4`). DILARANG menggunakan varian teks responsive `sm:text-sm` pada tombol trigger yang membuat tinggi tombol tidak selaras dengan input `form sm`.
    - **`.form-group` & `.form-group-text`:** Container terpadu untuk input ber-addon/ikon. Mendukung modifier `.form-group.sm` atau selector `:has(.form.sm)` yang otomatis menyelaraskan ukuran font dan padding addon ke `text-xs !py-1.5 !px-2.5` (tinggi 30px).
    - **`.form-check` (`.sm` / `.lg`):** Wrapper checkbox/radio button terstandarisasi.
    - **`.filter-badge` & `.filter-badge-remove`:** Badge kriteria filter aktif dengan tombol hapus tag `✕`.

**9. Prinsip Desain Flat Minimalis & Larangan Shadow di dalam `<MainPage>`**

- Seluruh komponen yang diletakkan di dalam container `<MainPage>` (kartu widget, toolbar filter, tombol aksi, tabel, card container, dsb.) **DILARANG MENGGUNAKAN KELAS SHADOW** (`shadow`, `shadow-xs`, `shadow-sm`, `shadow-md`, `shadow-lg`, dsb.).
- Sollu App mempertahankan tampilan **Flat Minimalis**: pemisahan dan penegasan visual antar elemen wajib mengandalkan garis batas halus (`border border-slate-200` / `border-gray-200` atau `border-neutral-200`) serta latar warna solid/subtle (`bg-white` / `bg-slate-50`), bukan drop-shadow.
- Pengecualian efek shadow HANYA diizinkan untuk elemen melayang di luar alur halaman normal (_floating overlays_), seperti popover dropdown menu terbuka (`z-50`), dialog modal konfirmasi (`.overlay-modal`), dan toast notifications (`Toast.vue`).

**10. Standar Ergonomi UI Multi-Perangkat (Laptop, Tablet, Smartphone)**

- **Target Sentuh Minimum (Touch Targets):**
    - Smartphone / Mobile (`< sm`): Minimal **44×44px** (`.touch-target`) untuk aksi utama, tombol navigasi, dan tap targets sentuh.
    - Tablet / POS Terminal (`sm` - `md`): Minimal **36×36px** (`.touch-target-sm`) dengan padding hit-area yang cukup untuk jari staf/kasir.
    - Laptop / Desktop (`>= lg`): Minimal **28×28px** atau 30px tinggi standar (`.form.sm`, `.btn-sm`) untuk kerapatan data tinggi.
- **Pencegahan Forced Auto-Zoom iOS Safari:**
    - Seluruh input formulir pada tampilan mobile (`< sm`) WAJIB menggunakan komputasi font `16px` (`text-base sm:text-xs` atau `.form.adaptive`) agar Safari tidak melakukan zoom in otomatis saat mengetik.
- **Zona Jangkauan Jempol (Thumb Zone):**
    - Aksi formulir utama (Simpan, Bayar, Konfirmasi) di mobile WAJIB berada di sticky footer bawah (`#popUpFooter`), bukan di bagian atas.
- **Safe Area Insets & Dynamic Viewport (`100dvh`):**
    - Drawer dan modal wajib menggunakan `100dvh` dan padding safe-area (`.safe-pb`, `.safe-pt`) agar tidak terpotong oleh browser bar dan home gesture indicator.
- **Tabel Responsif & Kolom Prioritas:**
    - Kolom sekunder wajib dikonfigurasi dengan prop responsif `show: 'md'` atau `show: 'lg'` pada array `headers` komponen `<Table>` sehingga di smartphone tidak terjadi overflow horizontal yang merusak layout.
- _Lihat panduan lengkap di file `docs/ui-ergonomics.md`._

**11. Standar Desain Formulir: Progressive Disclosure & Wizard Pattern (3-Tier Form Architecture)**

- **Klasifikasi 3-Tier Formulir:**
    - **Tier 1 - Simple / Quick Form ($\le 5$ fields):** Tampilan flat vertikal tanpa collapsible/stepper. Khusus master data ringkas (Kategori, Satuan UOM, Meja Kasir, Alasan Void).
    - **Tier 2 - Progressive Disclosure Form ($6-12$ fields, single domain):**
        - **Prinsip 80/20 Pareto Core Fields:** 80% input harian utama (Nama, Harga, Kategori) diletakkan di bagian atas (selalu tampak).
        - **Smart Collapsible (`<DisclosureSection>`):** 20% input lanjutan/opsional (SKU kustom, Barcode manual, Alert stok minimum, Tag, Catatan) WAJIB dibungkus dalam `<DisclosureSection>`. Otomatis terbuka jika ada error validasi di dalamnya.
        - **Contextual / Trigger Reveal:** Input dependen (misal: *Minimum Stok*) dilarang dirender jika trigger utamanya (*Lacak Stok*) belum diaktifkan.
    - **Tier 3 - Complex Wizard & Tabbed Form ($> 12$ fields atau multi-domain):**
        - **Asimetri Create vs Edit:** Mode Create WAJIB menggunakan **Linear Stepper** (`<FormStepper>`) memandu langkah demi langkah (Step 1 $\rightarrow$ Step 2 $\rightarrow$ Step 3). Mode Edit WAJIB menggunakan **Direct Tabbed Navigation** (`<FormTabs>`) agar user dapat langsung menuju tab yang ingin diedit tanpa mengulang stepper.
        - **Error Badging & Step Validation:** Stepper/Tabs wajib menerima properti `:errors="form.errors"` untuk memunculkan indikator titik merah (*error dot*) pada langkah/tab yang bermasalah.
        - **Sticky Teleport Footer:** Tombol navigasi (Batal, Kembali, Lanjut, Simpan) WAJIB di-teleport ke `#popUpFooter` di *Thumb Zone* bawah.

---

## E. Standar Penggunaan MCP (Model Context Protocol)

**1. Standarisasi MCP Git (`git-mcp-server`)**

- **Wajib Prioritaskan MCP Git:** AI Agent WAJIB memprioritaskan pemanggilan MCP Git (`status`, `add`, `commit`, `branch_list`, `branch_create`, `checkout`, `stash_save`, `stash_pop`, dll.) dibandingkan raw shell command `git` via terminal runner. Hal ini memastikan isolasi eksekusi, penanganan branch/stashing aman, dan state tracking terstruktur.
- **Standar Pesan Commit (Conventional Commits):** Format pesan commit WAJIB mengikuti konvensi baku:
    - `feat(module): deskripsi fitur baru`
    - `fix(module): perbaikan bug spesifik`
    - `refactor(module): perubahan struktur kode tanpa merubah behavior/output`
    - `style(module): penyesuaian styling, indentasi, atau format linter (Pint/ESLint/Prettier)`
    - `test(module): penambahan atau pembaruan unit/feature test`
    - `chore: pembaruan dependensi, konfigurasi build/vite, atau tooling`
- **Atomic Commits & Verification First:** Commit HANYA dilakukan setelah kode melewati verifikasi (linter `pint`/`eslint` bersih, test lolos, atau visual UI terkonfirmasi). DILARANG melakukan commit kode setengah jadi atau dalam kondisi error.
- **Safety Working Tree:** Selalu verifikasi `status` sebelum checkout/merge. Amankan perubahan uncommitted menggunakan `stash_save` ketimbang membuang perubahan secara destruktif.

**2. Optimalisasi MCP Laravel Boost (`laravel-boost`)**

- **Inspeksi Skema Database:** Gunakan `database-schema` untuk memeriksa struktur kolom, tipe data, foreign key, dan indeks database riil secara instan sebelum merancang migration atau query Eloquent.
- **Pemecahan Masalah Runtime Cepat:** Gunakan `last-error` dan `read-log-entries` saat terjadi error pada API/backend untuk menganalisis jejak stacktrace Laravel tanpa membuang context membaca file `storage/logs/laravel.log` mentah.
- **Pencarian Dokumentasi & Status Aplikasi:** Gunakan `search-docs` untuk mencari panduan/API resmi ekosistem Laravel & Inertia, serta `application-info` untuk status lingkungan runtime.

**3. Optimalisasi MCP Database Direct Inspection (`sollu-db`)**

- **Inspeksi PostgreSQL Core (`sollu-db`):** Gunakan MCP Postgres untuk memeriksa data riil, validasi multi-tenancy (`business_id`, `outlet_id`), foreign key integrity, dan verifikasi hasil migrasi/seeder.
    - **Aturan Ketat Read-Only:** Pemanggilan query MCP ke database core HANYA diizinkan untuk query pembacaan (`SELECT`). DILARANG KERAS menjalankan `INSERT`, `UPDATE`, `DELETE`, `DROP`, atau `ALTER` langsung via MCP DB—seluruh mutasi skema WAJIB melalui migration Laravel, dan mutasi data WAJIB melalui Service/Model/Seeder.

**4. Optimalisasi MCP Filesystem (`filesystem`)**

- Gunakan tool MCP Filesystem sebagai pelengkap operasi file terstruktur (seperti listing direktori dengan ukuran atau inspeksi tree) di dalam ruang kerja proyek `/Users/whykrr/Documents/Projects/Laravel/sollu-app`.

---

## F. Standar Wording & UX Copywriting (Tone of Voice)

**1. Prinsip Utama (Santai, Komunikatif, To the Point, Profesional)**
- **Santai & Hangat:** DILARANG menggunakan bahasa formal birokratis/kaku (seperti *"Dimohon untuk...", "Pengguna wajib melaksanakan...", "Sistem mengeksekusi proses..."*). Gunakan sapaan akrab selayaknya rekan kerja cerdas (*"Yuk, ...", "Tokomu", "Bisnismu"*).
- **Komunikatif & Solutif:** Selalu arahkan pengguna dengan penjelasan yang membimbing dan solutif.
- **To the Point (Lugas & Ringkas):** Langsung pada inti pesan tanpa kalimat pembuka yang bertele-tele.
- **Tetap Profesional & Jelas (*Clarity First*):** Hindari slang/bahasa gaul pasar berlebihan yang menurunkan kredibilitas. Istilah operasional bisnis baku (*SKU, Stok Opname, Resep, HPP, Void, Refund*) tetap digunakan secara presisi.

**2. Standar Kontekstual UI**
- **Empty State:** Wajib memuat pesan ramah dan Call-to-Action (CTA) jelas (Contoh: *"Belum ada produk nih. Yuk, tambah produk pertamamu!"*).
- **Placeholder:** Gunakan sebagai contoh pengisian nyata (Contoh: *"Misal: Susu UHT Full Cream"*), bukan sekadar mengulang teks label.
- **Notifikasi & Toast:** Singkat, hangat, dan melegakan (Contoh: *"Data berhasil disimpan!"*, *"Data berhasil dipindah ke sampah."*).
- **Konfirmasi Hapus:** Jelaskan konsekuensi tindakan secara transparan tanpa menakut-nakuti (Contoh: *"Yakin mau hapus produk ini? Data akan dipindah ke sampah dan tidak tampil di kasir."*).
- **Feature Lock / Upsell:** Fokus pada manfaat fitur secara positif (Contoh: *"Mau kelola resep otomatis? Yuk, tingkatkan paket tokomu ke Pro!"*).
- _Lihat panduan lengkap di file `docs/ux-wording.md`._
