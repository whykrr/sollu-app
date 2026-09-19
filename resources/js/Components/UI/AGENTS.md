---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Layout UI `@/Components/UI/`

Saat menggunakan atau mengedit komponen di `resources/js/Components/UI`, Anda **WAJIB** mematuhi aturan berikut:

---

## 1. Komponen Layout Halaman Utama (`<MainPage>`)

- **Struktur Halaman Resmi:**
    ```vue
    <template>
        <MainPage>
            <!-- 1. Header Slot (NON-SCROLLABLE: Judul, Deskripsi & Tombol Aksi) -->
            <template #header>
                <MainPageHeader title="Judul Halaman" description="Deskripsi singkat">
                    <!-- Tombol aksi di slot default -->
                    <button class="btn btn-highlight-main btn-sm" @click="openCreate">
                        <FontAwesomeIcon :icon="faPlus" /> Tambah Baru
                    </button>
                </MainPageHeader>
            </template>

            <!-- 2. Widgets Slot (Opsional: Metrik/KPI di antara Header dan Filter) -->
            <template v-if="$slots.widgets" #widgets>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <Widget ... />
                </div>
            </template>

            <!-- 3. Filter Slot (NON-SCROLLABLE: Toolbar Filter yang Diekstrak) -->
            <template #filter>
                <EntityFilter :filters="filters" />
            </template>

            <!-- 4. Default Slot (SCROLLABLE CONTAINER: Tabel Data / Konten Utama) -->
            <Table :headers="headers" :data="items.data" :action="true">
                <template #actions="{ row }">
                    <button class="btn btn-flat btn-sm" @click="openEdit(row)">Edit</button>
                </template>
            </Table>

            <!-- 5. Footer Slot (Pagination Bar) -->
            <template #footer>
                <Pagination :meta="items.meta || items" />
            </template>
        </MainPage>
    </template>
    ```
- **Aturan Spacing (Skala 2):** Jarak antar-elemen di atas dan di dalam `MainPage` WAJIB berskala 2 (`gap-2`, `space-y-2`, `m-2`).
- **Prinsip Flat Minimalis (Bebas Shadow):** Seluruh komponen di dalam container `<MainPage>` (kartu widget `#widgets`, toolbar filter `#filter`, tabel data, card, tombol) **DILARANG MENGGUNAKAN SHADOW** (`shadow`, `shadow-xs`, `shadow-sm`, dsb.). Gunakan border halus (`border border-slate-200` / `border-gray-200`) dan background flat (`bg-white` / `bg-slate-50`) untuk menjaga estetika flat minimalis. Shadow hanya diizinkan untuk floating elements (dropdown popover, modal dialog, toast).
- **Elemen Non-Scrolling:** Seluruh filter, search bar, widget KPI, dan header title WAJIB berada di slot non-scrolling (`#header`, `#widgets`, `#filter`) agar tidak hilang atau terdorong saat tabel di-scroll.

---

## 2. Header Halaman Terstandarisasi (`<MainPageHeader>`)

- **Props:**
    - `title` (String, required): Judul halaman (e.g. `"Data Produk"`, `"Laporan Penjualan"`).
    - `description` (String, optional): Keterangan singkat di bawah judul.
- **Default Slot:** Menampung tombol aksi (Ekspor, Impor, Tambah Baru).
- **Contoh:**
    ```vue
    <MainPageHeader title="Kelola Outlet" description="Daftar seluruh cabang usaha Anda">
        <button class="btn btn-highlight-main" @click="openCreate">
            <FontAwesomeIcon :icon="faPlus" /> Tambah Outlet
        </button>
    </MainPageHeader>
    ```

---

## 3. Side Drawer Panel (`<PopUpPage>` & `usePopUpStore()`)

- **Penggunaan:** Mandatory untuk Create, Edit, Detail, dan alur Sub-page.
- **Isolasi Padding:** Container `.modal-body` di `PopUpPage.vue` sudah memiliki padding bawaan. Child form yang dirender di dalam PopUpPage **DILARANG** menambahkan wrapper padding luar lagi (jangan gunakan `p-4`, `p-5`, `p-6` di root child).
- **Sticky Footer Action via Teleport:**
    ```vue
    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center justify-end gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="closeDrawer">Batal</button>
            <button type="submit" class="btn btn-main" :disabled="form.processing">Simpan</button>
        </div>
    </Teleport>
    ```

---

## 4. Ekosistem Toolbar & Filter (`ActionBar` & `@/Components/UI/Filter/`)

- **`ActionBar.vue` (`@/Components/UI/ActionBar/ActionBar.vue`):** Wrapper toolbar terpadu halaman (`<ActionBar><template #filters>...</template><template #search>...</template><template #tools>...</template><template #create>...</template></ActionBar>`).
- **`FilterBar.vue`:** Wrapper kompatibilitas transisi yang mendelegasikan ke `ActionBar.vue`.
- **`FilterPresetDate.vue`:** Dropdown preset tanggal (_Hari Ini_, _7 Hari Terakhir_, _Bulan Ini_, _Kustom_) dan modal rentang tanggal. Default preset: `'this_month'`.
- **`FilterSegmented.vue`:** Tab status/counter segmented pill (`<FilterSegmented v-model="form.status" :options="options" />`).
- **`FilterDropdown.vue`:** Dropdown filter pill inline untuk kategori/outlet/supplier (`<FilterDropdown v-model="form.cat" label="Kategori" :options="opts" />`).
- **`FilterSearch.vue`:** Input pencarian teks live terstandarisasi (`<FilterSearch v-model="filterForm.search" placeholder="Cari..." />`).
- **`FilterBadge.vue`:** Menampilkan kriteria filter yang aktif (`<FilterBadge @remove="resetStatus">Status: {{ status }}</FilterBadge>`).
- **`FilterTrashData.vue`:** Toggle filter data terhapus.
- **🚨 Dilarang menggunakan `<FilterModal.vue>` untuk penyaringan tabel utama.** Seluruh filter dan aksi halaman wajib menggunakan susunan inline `ActionBar` di atas.

---

## 5. Tab, Dropdown Data, & Feature Locking

- **`ActionsDropdown.vue`:** Dropdown menu ringkas berikon dengan auto-collision positioning (`<ActionsDropdown label="Opsi Data" :items="actionItems" />`).
- **`ExportDropdown.vue`:** Dropdown aksi ekspor data berbasis `ActionsDropdown` (`<ExportDropdown :items="exportOptions" />`).
- **`Tab.vue`:** Navigasi tab dengan array `pages: [{ label, icon, page, props, badge }]`.
- **`FeatureLock.vue`:** Proteksi kartu/fitur berbayar dengan overlay gembok (`<FeatureLock :feature="$enums.FeatureEnum.FEATURE_NAME">`).
