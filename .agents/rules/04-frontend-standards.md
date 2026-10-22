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

### 7.1. Solid Mobile Viewport Architecture (Standalone & Auth Pages)
Khusus untuk halaman penuh mandiri tanpa layout `<MainPage>` (seperti Login, Register, Forgot Password, Select Business, Onboarding Wizard):
1. **Viewport Height Anchoring (`h-svh`):** Container utama **WAJIB** menggunakan `h-screen h-svh max-h-svh w-full overflow-hidden` untuk mengantisipasi dynamic URL bar dan toolbar navigasi bawah pada iOS Safari dan Google Chrome.
2. **Struktur 3-Bagian (Flex Column Pinned-Docked):**
   - **Top Pinned Header (`shrink-0`):** Berisi Logo, Stepper progress bar, atau navigasi kembali.
   - **Middle Content (`flex-1 min-h-0 flex flex-col justify-center`):** Menampung judul dan input form. **DILARANG menggunakan `my-auto`** pada container ini karena menyerap flex space dan mendorong footer keluar layar. Jika konten berpotensi meluap di layar sangat kecil, tambahkan `overflow-y-auto no-scrollbar`.
   - **Docked Bottom Action (`shrink-0`):** Tombol aksi utama (submit) dan link sekunder selalu terpasang di bawah, dilengkapi `safe-pb` (`env(safe-area-inset-bottom)`) dan target sentuh $\ge 44\text{px}$.
3. **Overscroll & Anti-Zoom Guard:** `resources/views/app.blade.php` wajib menyertakan `viewport-fit=cover` dan class `overscroll-y-none` pada `<body>`. Input floating form pada mobile wajib `text-base` ($\ge 16\text{px}$) untuk mencegah auto-zoom.

---

## 8. Standarisasi Modal Dialog & Konfirmasi (`useModalStore`) vs Drawer (`usePopUpStore`)
Aplikasi Sollu memisahkan dengan tegas antara **Drawer / Side-Panel** (alur kerja data/formulir) dan **Center Modal Dialog** (dialog konfirmasi/alert):

1. **Drawer / Side-Panel (`usePopUpStore` dari `@/store/popup`):**
   - **WAJIB** digunakan untuk formulir entitas Create/Edit (1-3 Tier), detail entitas read-only, dan form transaksi/pembayaran.
   - Meluncur dari kanan layar via `<PopUpPage>` dengan layout sticky `#popUpFooter` dan proteksi `useFormDirtyGuard`.

2. **Modal Dialog Konfirmasi & Alert (`useModalStore` dari `@/store/notification`):**
   - **WAJIB** menggunakan `useModalStore` yang dirender terpusat di level layout oleh `ModalContainer.vue`.
   - **DILARANG** meng-instansiasi tag `<Modal :show="...">` langsung di dalam template halaman/komponen untuk dialog konfirmasi atau alert sederhana (mencegah nested overlay dan boilerplate ganda).
   - **Konfirmasi Aksi Destruktif (Hapus Data / Hapus Draf):**
     ```javascript
     modalStore.confirm({
         title: 'Konfirmasi Hapus Draf',
         message: 'Apakah Anda yakin ingin menghapus draf faktur ini? Tindakan ini tidak dapat dikembalikan.',
         type: 'danger',
         confirmText: 'Ya, Hapus',
         cancelText: 'Batal',
         onConfirm: () => { router.delete(...) },
     })
     ```
   - **Konfirmasi Aksi Operasional Kritis (Terbitkan Faktur, Batalkan Faktur):**
     ```javascript
     modalStore.confirm({
         title: 'Konfirmasi Terbitkan Faktur',
         message: 'Apakah Anda yakin ingin menerbitkan faktur resmi untuk transaksi ini? Stok inventori akan langsung dipotong.',
         type: 'warning',
         confirmText: 'Ya, Terbitkan',
         cancelText: 'Batal',
         onConfirm: () => { router.post(...) },
     })
     ```
   - **Pemberitahuan / Peringatan Validasi (Alert):**
     ```javascript
     modalStore.alert({
         title: 'Metode Pembayaran Diperlukan',
         message: 'Silakan pilih metode pembayaran (Kas/Bank) untuk transaksi tunai.',
         type: 'warning',
         confirmText: 'Mengerti',
     })
     ```
   - **Dialog Konfirmasi Reviu / Konten Kustom (`component`):**
     Jika dialog membutuhkan tampilan tabel/ringkasan data sebelum konfirmasi (misal reviu faktur sebelum terbit), ekstrak isi dialog ke komponen mandiri (tanpa tag `<Modal>` pembungkus) dan buka melalui:
     ```javascript
     modalStore.open({
         title: 'Konfirmasi Penerbitan Faktur',
         size: 'max-w-lg',
         type: 'info',
         component: markRaw(CustomReviewComponent),
         props: { ... },
         confirmText: 'Ya, Terbitkan Faktur',
         cancelText: 'Periksa Kembali',
         confirmClass: 'btn-main',
         showCancel: true,
         showFooter: true,
         onConfirm: () => { ... },
     })
     ```
   - **Invarian Penamaan & Semantik:**
     - Properti tombol: Wajib gunakan `confirmText` dan `cancelText` (DILARANG `confirmButtonText`).
     - Tipe semantik: `'danger'` (merah), `'warning'` (amber), `'info'`/`'confirm'` (biru), `'success'` (hijau) (DILARANG `type: 'error'`).

---

## 9. Modal Kredensial, Clipboard Fallback, & In-Modal Async Flow

### A. Pengamanan Kredensial Sekali Tampil (One-Time Credential Display)
1. **Proteksi Backdrop (`closeOnBackdrop: false`):** Saat modal menampilkan kredensial yang hanya muncul sekali (misal: kata sandi baru hasil reset, kode OTP, atau token API), properti `closeOnBackdrop` **WAJIB bernilai `false`** agar dialog tidak tertutup secara tidak sengaja oleh klik di luar modal sebelum kredensial disalin.
2. **Kepatuhan Zero-Shadow & Aksesibilitas:** Kotak preview kredensial menggunakan background solid halus (`bg-slate-50`), border lembut (`border-slate-200`), font monospace jelas dengan seleksi instan (`select-all font-mono font-bold`), dan tombol salin berukuran standar touch-target (`touch-target-sm`).

### B. Standardisasi Penyalinan Papan Klip (Clipboard Fallback)
1. **Dukungan Lingkungan Non-Secure Context (HTTP):** Pemanggilan `navigator.clipboard.writeText()` hanya berfungsi di *secure context* (HTTPS/localhost). Untuk lingkungan testing staging/lokal (`http://app.sollu.test`), fungsi salin **WAJIB memiliki fallback** ke elemen sementara `<textarea>` dengan `document.execCommand('copy')`.
2. **Feedback Visual Instan:** Tombol salin wajib memberikan umpan balik langsung (misal: berubah menjadi tanda centang hijau *"Kata Sandi Tersalin!"* selama 2 detik) disertai trigger notifikasi `toastStore.success()`.

### C. Alur Asinkron In-Modal 2-Tahap (Two-State In-Modal Flow)
1. **Hindari Chaining `modalStore.confirm` dengan `modalStore.open`:** `modalStore.confirm` menutup modal secara otomatis tepat saat `onConfirm()` dipanggil. Jika `onConfirm` menjalankan operasi asynchronous (seperti API reset password), modal akan tertutup lebih awal dan menimbulkan kedip UI sebelum dialog hasil muncul.
2. **Gunakan Komponen Modal Terpadu:** Untuk alur konfirmasi yang menghasilkan data asinkron, gunakan komponen modal terdedikasi (misal `<PasswordResetModal>`) dengan transisi status internal (Tahap 1: Konfirmasi $\rightarrow$ Indikator Loading $\rightarrow$ Tahap 2: Hasil & Tombol Salin).

---

## 10. Standar Efek Fokus Form, Ring Inset, & Anti-Flash Transition

### 10.1. Kewajiban `ring-inset` pada Form Controls & Interactive Selectors
Untuk mencegah pemotongan efek visual (*clipping*) oleh container scrollable (`overflow-y-auto`, `overflow-hidden`, modal dialog, atau drawer `<PopUpPage>`):
1. **Kontrol Formulir Global:** Seluruh input form (`@utility form`, `.form-group`, `.form-check input`, `.is-valid`/`.is-invalid`, `.btn-flat`) **WAJIB** menggunakan `focus:ring-inset` dipadukan dengan `focus:ring-2 focus:ring-main/20 focus:border-main`.
2. **Kartu Selektor Interaktif:** Komponen kartu pilihan di dalam modal/drawer (seperti kartu paket, kartu promo, opsi metode inventori, picker produk) **WAJIB** menggunakan `ring-inset` (`ring-1 ring-inset` atau `ring-2 ring-inset`) saat status aktif/terpilih.

### 10.2. Invarian Anti-Flash Transition & Outline Guard
1. **DILARANG `transition-all` pada Form Controls:** Elemen formulir dilarang keras menggunakan `transition-all` karena browser menginterpolasi `box-shadow: none` melalui `currentColor` (warna teks hitam), yang memicu kilatan (flash) border hitam tebal sebelum warna ring sebenarnya muncul.
2. **Wajib `transition-colors duration-200 ease-in-out`:** Kontrol formulir hanya boleh menganimasikan warna (`border-color`, `background-color`, `color`). Focus ring (`ring-2 ring-inset`) harus muncul seketika secara tegas (*instant crisp feedback*).
3. **Wajib `outline-none` pada Base State:** Deklarasikan `outline-none` pada state dasar (unfocused) dan `focus:outline-none` pada focus state untuk menonaktifkan native outline browser secara permanen.



