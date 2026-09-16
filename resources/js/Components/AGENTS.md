---
trigger: always_on
---

# Master Catalog Komponen UI Bawaan Proyek (`resources/js/Components`)

Panduan dan pemetaan seluruh komponen UI & formulir bawaan (_built-in template components_) pada **Sollu App**. AI Agent **WAJIB** memprioritaskan dan menggunakan komponen-komponen ini secara maksimal sebelum membuat elemen baru.

---

## 1. Aturan Baku Penggunaan Komponen

1. **Dilarang Raw HTML Form:** Wajib gunakan komponen `@/Components/Form/`. Dilarang menulis tag `<input>`, `<select>`, atau `<textarea>` mentah.
2. **Spacing Skala 2 pada MainPage:** Jarak antar-komponen di atas `<MainPage>` dan di dalam slotnya wajib berskala 2 (`gap-2`, `space-y-2`, `m-2`).
3. **Maksimal Skala 3 untuk Margin/Padding Komponen Baru:** Margin dan padding internal komponen baru DILARANG melebihi skala 3 (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`).
4. **Isolasi Padding PopUpPage:** Body `PopUpPage.vue` sudah memiliki padding bawaan. Child form di dalamnya **TIDAK BOLEH** menambahkan wrapper padding/margin luar.
5. **Non-Scrolling Header:** Kartu, widget, filter bar, dan tombol aksi wajib diletakkan di `<template #header>` `<MainPage>`. Default slot hanya untuk konten scrollable (tabel data).
6. **Wajib Komponen `<Table>`:** Seluruh data tabel wajib memakai `@/Components/Tables/Table.vue`. Empty state sudah ditangani di level komponen Table.
7. **Ekstraksi Komponen Filter:** Kontrol filter data wajib diekstrak ke komponen tersendiri (misal: `Components/EntityFilter.vue`).

---

## 2. Matriks Katalog Komponen

### A. Layout & Halaman (`@/Components/UI/`)

| Nama Komponen            | Path Import                                   | Kegunaan Utama & Deskripsi                                                                                                               | Contoh Penggunaan Singkat                                                                           |
| :----------------------- | :-------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------- |
| **`MainPage`**           | `@/Components/UI/MainPage.vue`                | Wrapper layout utama halaman. Menyediakan slot `#widgets`, `#header` (sticky/non-scroll), default slot (scrollable body), dan `#footer`. | `<MainPage><template #header>...</template><Table ... /></MainPage>`                                |
| **`MainPageHeader`**     | `@/Components/UI/MainPage/MainPageHeader.vue` | Header terstandarisasi untuk halaman utama dengan prop `title`, `description`, dan slot tombol aksi.                                     | `<MainPageHeader title="Data Produk"><button class="btn btn-main">Tambah</button></MainPageHeader>` |
| **`PopUpPage`**          | `@/Components/UI/PopUpPage.vue`               | Side-drawer panel kanan untuk formulir Create, Edit, Detail, dan alur Sub-page.                                                          | `<PopUpPage :show="isOpen" title="Edit Data" @close="closeDrawer">...</PopUpPage>`                  |
| **`PopUpContainer`**     | `@/Components/UI/PopUpContainer.vue`          | Host global rendering PopUpPage dinamis via `usePopUpStore()`.                                                                           | Digunakan di root layout `AppLayout.vue`.                                                           |
| **`Tab`**                | `@/Components/UI/Tab.vue`                     | Komponen navigasi tab horizontal atau vertikal (`vertical: boolean`) berbasis array `pages`.                                             | `<Tab :pages="tabList" />`                                                                          |
| **`Card`**               | `@/Components/UI/Card/Card.vue`               | Kartu berbatas (_bordered card_) dengan header, slot body, dan slot `#footer`.                                                           | `<Card title="Ringkasan"><p>Isi</p></Card>`                                                         |
| **`CardFade`**           | `@/Components/UI/Card/CardFade.vue`           | Kartu visual dengan efek background blur/fade untuk konten hero/promosi.                                                                 | `<CardFade :image="bannerUrl" title="Info Promo" />`                                                |
| **`ExportDropdown`**     | `@/Components/UI/ExportDropdown.vue`          | Tombol dropdown ekspor (PDF, Excel, CSV) dengan opsi aksi terkonfigurasi.                                                                | `<ExportDropdown :items="exportOptions" />`                                                         |
| **`FeatureLock`**        | `@/Components/UI/FeatureLock.vue`             | Wrapper pembatas fitur paket SaaS dengan overlay gembok dan trigger upgrade modal.                                                       | `<FeatureLock :feature="$enums.FeatureEnum.RECIPE_MANAGEMENT">...</FeatureLock>`                    |
| **`FeatureLockOverlay`** | `@/Components/UI/FeatureLockOverlay.vue`      | Overlay visual gembok terkunci untuk container kartu/fitur berbayar.                                                                     | `<FeatureLockOverlay :feature="featureKey" />`                                                      |

### B. Filter Bar & Filter Modal (`@/Components/UI/Filter/`)

| Nama Komponen         | Path Import                                  | Kegunaan Utama & Deskripsi                                                         | Contoh Penggunaan Singkat                                                        |
| :-------------------- | :------------------------------------------- | :--------------------------------------------------------------------------------- | :------------------------------------------------------------------------------- |
| **`FilterSearch`**    | `@/Components/UI/Filter/FilterSearch.vue`    | Input teks pencarian live tabel dengan ikon kaca pembesar dan `v-model`.           | `<FilterSearch v-model="filterForm.search" />`                                   |
| **`FilterModal`**     | `@/Components/UI/Filter/FilterModal.vue`     | Modal dialog overlay untuk formulir kriteria filter multi-parameter.               | `<FilterModal :show="showModal" @apply="apply" @reset="reset">...</FilterModal>` |
| **`FilterBadge`**     | `@/Components/UI/Filter/FilterBadge.vue`     | Badge pil penanda kriteria filter yang sedang aktif dengan tombol hapus `✕`.       | `<FilterBadge @remove="removeFilter">Status: Aktif</FilterBadge>`                |
| **`FilterStatus`**    | `@/Components/UI/Filter/FilterStatus.vue`    | Dropdown/selektor filter status cepat.                                             | `<FilterStatus v-model="filterForm.status" />`                                   |
| **`FilterTrashData`** | `@/Components/UI/Filter/FilterTrashData.vue` | Toggle tombol untuk menyaring data aktif vs data terhapus/sampah (_soft deletes_). | `<FilterTrashData v-model="filterForm.trashed" />`                               |

### C. Formulir & Input (`@/Components/Form/`)

| Nama Komponen                | Path Import                                    | Kegunaan Utama & Deskripsi                                                                             | Contoh Penggunaan Singkat                                                                      |
| :--------------------------- | :--------------------------------------------- | :----------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------- |
| **`TextField`**              | `@/Components/Form/TextField.vue`              | Input teks umum (`text`, `email`, `tel`, `url`) dengan floating label dan feedback validasi.           | `<TextField v-model="form.name" label="Nama" :feedback="form.errors.name" />`                  |
| **`TextareaField`**          | `@/Components/Form/TextareaField.vue`          | Input teks multiline dengan auto-expand dan feedback error.                                            | `<TextareaField v-model="form.notes" label="Catatan" rows="3" />`                              |
| **`NumberField`**            | `@/Components/Form/NumberField.vue`            | Input numerik terformat mata uang (Rp) / angka desimal dengan parsing otomatis.                        | `<NumberField v-model="form.price" label="Harga Jual" prefix="Rp" />`                          |
| **`PasswordField`**          | `@/Components/Form/PasswordField.vue`          | Input kata sandi dengan toggle intip (_eye icon_).                                                     | `<PasswordField v-model="form.password" label="Kata Sandi" />`                                 |
| **`PinField`**               | `@/Components/Form/PinField.vue`               | Input PIN numerik terproteksi untuk otorisasi supervisor / kasir.                                      | `<PinField v-model="form.pin" label="PIN Otorisasi" />`                                        |
| **`DropdownField`**          | `@/Components/Form/DropdownField.vue`          | Dropdown pilihan statis atau enum options (`getOptions('Enum')`) dengan pencarian.                     | `<DropdownField v-model="form.status" :options="getOptions('StatusEnum')" label="Status" />`   |
| **`AsyncSelectField`**       | `@/Components/Form/AsyncSelectField.vue`       | Dropdown select dengan pencarian asynchronous ke endpoint API backend.                                 | `<AsyncSelectField v-model="form.item_id" endpoint="/api/items/search" label="Pilih Bahan" />` |
| **`AsyncOutletDropdown`**    | `@/Components/Form/AsyncOutletDropdown.vue`    | Dropdown async terisolasi khusus pemilihan outlet tenant.                                              | `<AsyncOutletDropdown v-model="form.outlet_id" label="Outlet" />`                              |
| **`Switch`**                 | `@/Components/Form/Switch.vue`                 | Toggle switch boolean aktif / non-aktif gaya iOS.                                                      | `<Switch v-model="form.is_active" label="Tampilkan di Menu" />`                                |
| **`CheckboxField`**          | `@/Components/Form/CheckboxField.vue`          | Checkbox kustom tunggal dengan label.                                                                  | `<CheckboxField v-model="form.agree" label="Saya setuju" />`                                   |
| **`RadioField`**             | `@/Components/Form/RadioField.vue`             | Radio button kustom tunggal.                                                                           | `<RadioField v-model="form.gender" value="male" label="Laki-laki" />`                          |
| **`SelectionGroupField`**    | `@/Components/Form/SelectionGroupField.vue`    | Segmented group button (Single-select radio style atau Multi-select checkbox style dengan Select All). | `<SelectionGroupField v-model="form.roles" :options="roles" multiple show-select-all />`       |
| **`QuillEditor`**            | `@/Components/Form/QuillEditor.vue`            | Rich Text Editor berbasis Quill untuk konten deskripsi HTML panjang.                                   | `<QuillEditor v-model="form.content" label="Deskripsi Lengkap" />`                             |
| **`GroupTextIconField`**     | `@/Components/Form/GroupTextIconField.vue`     | Input teks dengan addon ikon FontAwesome di awal/akhir input.                                          | `<GroupTextIconField v-model="search" :icon="faSearch" placeholder="Cari..." />`               |
| **`GroupDropdownIconField`** | `@/Components/Form/GroupDropdownIconField.vue` | Dropdown select dengan addon ikon FontAwesome.                                                         | `<GroupDropdownIconField v-model="outlet" :icon="faStore" :options="outletOptions" />`         |

### D. Tabel Data & Navigasi (`@/Components/Tables/`)

| Nama Komponen        | Path Import                              | Kegunaan Utama & Deskripsi                                                                                                                                             | Contoh Penggunaan Singkat                                                                                                                                                  |
| :------------------- | :--------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **`Table`**          | `@/Components/Tables/Table.vue`          | Komponen tabel resmi dengan sortable header (`sortable: true`), props `:sort` & `:sort-direction`, cell slots dinamis, action slot, dan penanganan empty state bawaan. | `<Table :headers="headers" :data="items.data" :sort="params?.sort" :sort-direction="params?.direction" :action="true"><template #actions="{ row }">...</template></Table>` |
| **`Pagination`**     | `@/Components/Tables/Pagination.vue`     | Komponen navigasi pagination Inertia dengan rentang record info dan pemilih halaman. Diletakkan di `<template #footer>` `<MainPage>`.                                  | `<Pagination :meta="items.meta                                                                                                                                             |     | items" />` |
| **`DraggableTable`** | `@/Components/Tables/DraggableTable.vue` | Tabel dengan kemampuan drag-and-drop baris untuk menyusun urutan data (_sort order_).                                                                                  | `<DraggableTable :headers="headers" v-model="items" @order-changed="saveOrder" />`                                                                                         |

### E. Kartu KPI & Widget Analitik (`@/Components/Widgets/` & `@/Components/Cards/`)

| Nama Komponen         | Path Import                               | Kegunaan Utama & Deskripsi                                                                     | Contoh Penggunaan Singkat                                                                           |
| :-------------------- | :---------------------------------------- | :--------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------- |
| **`Widget`**          | `@/Components/Widgets/Widget.vue`         | Kartu metrik KPI dengan ikon, judul, nilai utama, dan indikator tren naik/turun (`traction="up | down"`).                                                                                            | `<Widget :icon="faReceipt" title="Total Penjualan" traction="up" :tractionPercentage="12">Rp 4.500.000</Widget>` |
| **`WidgetChart`**     | `@/Components/Widgets/WidgetChart.vue`    | Kartu metrik dengan mini grafik sparkline Chart.js.                                            | `<WidgetChart id="sales" :labels="dates" :data="amounts" title="Tren Omset" highlight="Rp 12jt" />` |
| **`WidgetProgress`**  | `@/Components/Widgets/WidgetProgress.vue` | Kartu metrik dengan visualisasi bilah progress (_progress bar_).                               | `<WidgetProgress :value="80" :maxValue="100" title="Target Bulanan">80%</WidgetProgress>`           |
| **`CardTransparent`** | `@/Components/Cards/CardTransparent.vue`  | Container kartu berlatar transparan dengan header slot dan action buttons.                     | `<CardTransparent title="Riwayat"><p>Konten</p></CardTransparent>`                                  |

### F. Tombol Aksi Bersama (`@/Components/Button/`)

| Nama Komponen                | Path Import                                      | Kegunaan Utama & Deskripsi                                                                                             | Contoh Penggunaan Singkat                                                             |
| :--------------------------- | :----------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------ |
| **`ButtonBack`**             | `@/Components/Button/ButtonBack.vue`             | Tombol navigasi kembali/batal dengan fallback otomatis ke `router.back()` atau dashboard.                              | `<ButtonBack />`                                                                      |
| **`ButtonGroupArchive`**     | `@/Components/Button/ButtonGroupArchive.vue`     | Kelompok tombol aksi teks untuk Soft Delete, Restore, dan Permanent Delete yang terintegrasi dengan `useModalStore()`. | `<ButtonGroupArchive :data="row" :urlArchive="route('items.destroy', row.id)" ... />` |
| **`ButtonIconGroupArchive`** | `@/Components/Button/ButtonIconGroupArchive.vue` | Kelompok tombol aksi versi ikon saja untuk tabel padat.                                                                | `<ButtonIconGroupArchive :data="row" :urlArchive="..." />`                            |

### G. Notifikasi & Modal Konfirmasi (`@/Components/Notifications/` & `@/Components/Modals/`)

| Nama Komponen            | Path Import                                     | Kegunaan Utama & Deskripsi                                                                                | Contoh Penggunaan Singkat                                                                      |
| :----------------------- | :---------------------------------------------- | :-------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------- |
| **`Modal`**              | `@/Components/Notifications/Modal.vue`          | Dialog konfirmasi tengah untuk tindakan kritis (Hapus, Arsip, Alert). Dikendalikan via `useModalStore()`. | `modalStore.openModalDelete(url)`                                                              |
| **`ModalContainer`**     | `@/Components/Notifications/ModalContainer.vue` | Container global rendering modal di root layout.                                                          | Diletakkan di `AppLayout.vue`.                                                                 |
| **`Toast`**              | `@/Components/Notifications/Toast.vue`          | Floating alert toast (sukses, error, info, warning). Dikendalikan via `useToastStore()`.                  | `toastStore.addToast({ type: 'success', message: 'Tersimpan' })`                               |
| **`FeatureLockedModal`** | `@/Components/Modals/FeatureLockedModal.vue`    | Modal dialog upsell yang muncul saat pengguna mengakses fitur di luar tier paketnya.                      | Ditrigger otomatis oleh redirect backend `feature_locked`.                                     |
| **`ImportCsvModal`**     | `@/Components/Modals/ImportCsvModal.vue`        | Modal dialog pengunggahan file Excel/CSV untuk proses impor asinkron.                                     | `<ImportCsvModal :show="isOpen" :uploadUrl="route('items.import')" @close="isOpen = false" />` |
