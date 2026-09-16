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
          <!-- 1. Widgets Slot (Opsional untuk Widget Metrik/KPI Teratas) -->
          <template v-if="$slots.widgets" #widgets>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                  <Widget ... />
              </div>
          </template>

          <!-- 2. Header Slot (NON-SCROLLABLE: Judul, Aksi, Filter Bar, Kartu Ringkasan) -->
          <template #header>
              <MainPageHeader title="Judul Halaman" description="Deskripsi singkat">
                  <!-- Tombol aksi di slot default -->
                  <button class="btn btn-highlight-main" @click="openCreate">
                      <FontAwesomeIcon :icon="faPlus" /> Tambah Baru
                  </button>
              </MainPageHeader>
              <!-- Komponen Filter yang telah diekstrak -->
              <EntityFilter :filters="filters" />
          </template>

          <!-- 3. Default Slot (SCROLLABLE CONTAINER: Tabel Data / Konten Utama) -->
          <Table :headers="headers" :data="items.data" :action="true">
              <template #actions="{ row }">
                  <button class="btn btn-flat btn-sm" @click="openEdit(row)">Edit</button>
              </template>
          </Table>

          <!-- 4. Footer Slot (Pagination Bar) -->
          <template #footer>
              <Pagination :meta="items.meta || items" />
          </template>
      </MainPage>
  </template>
  ```
- **Aturan Spacing (Skala 2):** Jarak antar-elemen di atas dan di dalam `MainPage` WAJIB berskala 2 (`gap-2`, `space-y-2`, `m-2`).
- **Elemen Non-Scrolling:** Seluruh filter, search bar, widget, dan kartu ringkasan WAJIB berada di slot `#header` agar tidak hilang atau terdorong saat tabel di-scroll.

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

## 4. Ekosistem Filter (`@/Components/UI/Filter/`)
- **`FilterSearch.vue`:** Input pencarian teks live (`v-model="filterForm.search"`).
- **`FilterModal.vue`:** Modal overlay kriteria filter (`<FilterModal :show="isOpen" @apply="apply" @reset="reset">`).
- **`FilterBadge.vue`:** Menampilkan kriteria filter yang aktif (`<FilterBadge @remove="resetStatus">Status: {{ status }}</FilterBadge>`).
- **`FilterStatus.vue`:** Selektor status instan.
- **`FilterTrashData.vue`:** Toggle filter data terhapus.

---

## 5. Tab & Feature Locking
- **`Tab.vue`:** Navigasi tab dengan array `pages: [{ label, icon, page, props, badge }]`.
- **`FeatureLock.vue`:** Proteksi kartu/fitur berbayar dengan overlay gembok (`<FeatureLock :feature="$enums.FeatureEnum.FEATURE_NAME">`).
- **`ExportDropdown.vue`:** Dropdown aksi ekspor data (`<ExportDropdown :items="exportOptions" />`).
