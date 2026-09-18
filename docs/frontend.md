# Sollu App Frontend Architecture & UI Standards

Standar pengembangan frontend **Sollu App** berbasis **Vue 3 (Composition API `<script setup>`)**, **Inertia.js 1.2**, dan **Tailwind CSS v4**.

---

## 1. 🚨 10 Anti-Hallucination Core Rules

1. **NO RAW HTML FORMS & TABLES:** Dilarang keras menuliskan tag `<input>`, `<select>`, `<textarea>`, atau `<table>` mentah. Wajib menggunakan komponen dari `@/Components/Form/` dan `@/Components/Tables/Table.vue`.
2. **PROJECT-SPECIFIC TAILWIND STYLES:** Gunakan utility class bawaan proyek di `app.css` (`btn`, `btn-main`, `btn-outline-main`, `btn-danger`, `form`, `form-group`, dll).
3. **MANDATORY MAINPAGE & NON-SCROLLING HEADER:** Seluruh halaman utama wajib dibungkus dengan komponen `<MainPage>`. Hierarki slot terstandarisasi: 1. `<template #header>` (Judul & Aksi), 2. `<template #widgets>` (KPI/Metrik analitik di antara header dan filter), 3. `<template #filter>` (Pencarian & Toolbar Filter data), 4. Default slot (Tabel data scrollable), 5. `<template #footer>` (Pagination). Header, widget, dan filter toolbar WAJIB diletakkan di slot non-scrolling agar tetap sticky di atas dan **TIDAK ikut ter-scroll** saat tabel/konten di default slot digulir.
4. **FLAT MINIMALIST UI & ZERO SHADOWS PADA CONTAINER MAINPAGE:** Seluruh komponen di dalam container `<MainPage>` (`#header`, `#widgets`, `#filter`, default slot, `#footer` seperti widget card, filter toolbar, action buttons, table) **DILARANG MENGGUNAKAN SHADOW** (`shadow`, `shadow-xs`, `shadow-sm`, `shadow-md`, dll). Gunakan subtle border (`border border-slate-200` / `border-gray-200`) dan flat background solid (`bg-white` / `bg-slate-50`) untuk mempertahankan estetika flat minimalis. Shadow hanya diizinkan untuk floating overlay elements (dropdown popover, modal dialog, toast).
5. **SPACING SCALE 2 PADA MAINPAGE & MAKSIMAL SCALE 3 PADA KOMPONEN BARU:**
   - Jarak antar-komponen di atas `<MainPage>` dan di dalam slot-nya WAJIB berskala 2 (`gap-2`, `gap-y-2`, `gap-x-2`, `space-y-2`, `space-x-2`, `m-2`, `my-2`, `mt-2`, `mb-2`).
   - Margin dan padding pada komponen baru DILARANG melebihi skala 3 (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`).
   - Jarak antar-input formulir DILARANG melebihi skala 2 (`space-y-2`, `gap-2`).
6. **MANDATORY POPUPPAGE (ZERO CHILD OUTER PADDING):** Seluruh alur kerja Create, Edit, Detail, dan Sub-page **WAJIB** menggunakan `<PopUpPage>` (side-drawer kanan) atau `usePopUpStore()`. DILARANG menggunakan *full page redirect* (`router.get()`) untuk form sub-halaman. Container body `PopUpPage.vue` sudah memiliki padding bawaan di level komponen, sehingga child form/view di dalamnya **DILARANG** menambahkan wrapper padding/margin luar lagi.
7. **MANDATORY `<Table>` COMPONENT, ROW LINK (SINGLE ACTION), SORTABLE HEADERS & CENTRALIZED EMPTY STATE:** Seluruh tampilan data tabular WAJIB menggunakan `@/Components/Tables/Table.vue`. Dilarang menulis tag `<table>` mentah. Untuk tabel dengan **single action** (misal hanya buka Detail/Edit), WAJIB gunakan event bawaan `@row-click="openDetail"` dan biarkan `:action="false"` (default). Gunakan `:action="true"` dengan slot `#actions` HANYA jika terdapat lebih dari 1 aksi per baris. Kolom yang dapat diurutkan wajib didefinisikan dengan `sortable: true` pada `headers` dan meneruskan props `:sort="params?.sort"` serta `:sort-direction="params?.direction"` ke `<Table>`. Penanganan *empty state* ("data tidak ditemukan") ditangani secara terpusat di level komponen `<Table>`, DILARANG membuat container `v-if="data.length === 0"` manual di masing-masing page.
8. **MANDATORY FILTER COMPONENT EXTRACTION:** Setiap halaman yang memiliki filter data (search bar, filter status, filter kategori, date picker, dsb.) **WAJIB diekstrak ke file komponen terpisah** (misal: `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`), bukan ditulis inline di file `Index.vue`.
9. **STANDARISASI ON-DEMAND DATA LOADING:** Data detail entitas lengkap dan data lookup form (opsi dropdown) WAJIB dimuat secara *asynchronous* (Axios) hanya saat drawer dibuka. Wajib menyertakan skeleton loader / spinner saat fetching.
10. **MANDATORY ENUM FOR CONDITIONS & FORM OPTIONS:** Dilarang keras menggunakan string literal/hardcode. Selalu gunakan `$enums.<EnumName>.<Case>` atau `useEnum()`.
11. **MANDATORY BROWSERMCP UI VERIFICATION:** Setiap pembuatan/perubahan komponen Vue WAJIB diverifikasi visual dan fungsional via `browsermcp` (navigasi URL, snapshot DOM, screenshot, dan console logs).

---

## 2. Directory Layout (`resources/js`)

```
resources/js/
├── Components/
│   ├── Form/            # TextField, DropdownField, SelectionGroupField, Switch, dll
│   ├── UI/              # MainPage.vue, MainPageHeader.vue, PopUpPage.vue, Tab.vue, Filter/*
│   ├── Tables/          # Table.vue, Pagination.vue
│   ├── Widgets/         # Widget.vue, WidgetChart.vue, WidgetProgress.vue, WidgetMini.vue
│   ├── Modals/          # Modal.vue, ModalContainer.vue
│   └── Notifications/   # Toast.vue, ToastContainer.vue
├── Pages/               # Halaman modul (App/{Module}/ & Cockpit/{Module}/)
├── Composable/          # useAuth, useEnum, usePlanFeature
├── store/               # usePopUpStore, useModalStore, useToastStore
└── Types/               # TypeScript declarations & interfaces
```

---

## 3. Standard Page Layout (`MainPage` & `MainPageHeader`)

Pola struktur utama untuk seluruh halaman modul:

```vue
<template>
    <MainPage>
        <!-- 1. Slot Header (NON-SCROLLABLE: Judul, Deskripsi & Tombol Aksi Utama) -->
        <template #header>
            <MainPageHeader title="Data Produk" description="Kelola seluruh katalog dan harga barang">
                <button class="btn btn-flat btn-sm" @click="exportCsv">
                    <FontAwesomeIcon :icon="faDownload" /> Ekspor Data
                </button>
                <button class="btn btn-highlight-main btn-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" /> Tambah Produk
                </button>
            </MainPageHeader>
        </template>

        <!-- 2. Slot Widgets (Opsional: Metrik Analitik / KPI Cards di antara Header dan Filter) -->
        <template v-if="$slots.widgets" #widgets>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <Widget ... />
            </div>
        </template>

        <!-- 3. Slot Filter (NON-SCROLLABLE: Toolbar Pencarian & Filter Data yang Diekstrak) -->
        <template #filter>
            <ProductFilter :filters="params" :categories="categories" />
        </template>

        <!-- 4. Default Slot (SCROLLABLE CONTAINER: Tabel Data dengan Single Action Row Link) -->
        <Table
            :headers="headers"
            :data="products.data"
            :sort="params?.sort ?? 'updated_at'"
            :sort-direction="params?.direction ?? 'desc'"
            @row-click="openDetail"
        >
            <template #status="{ row }">
                <span class="badge" :class="$enums.ProductStatus._meta[row.status]?.color">
                    {{ $enums.ProductStatus._meta[row.status]?.label }}
                </span>
            </template>
        </Table>

        <!-- 5. Slot Footer (Pagination Bar) -->
        <template #footer>
            <Pagination :meta="products.meta || products" />
        </template>
    </MainPage>
</template>
```

---

## 4. Side Drawer (`PopUpPage`) vs Center Dialog (`Modal`)

Sollu App membedakan dengan tegas fungsi antara drawer samping dan modal tengah:

```
    ┌──────────────────────────────────────┬───────────────────────┐
    │                                      │                       │
    │                                      │   <PopUpPage>         │
    │         Main Page Content            │   (Side Drawer Kanan) │
    │         (<MainPage>)                 │                       │
    │                                      │   - Form Create/Edit  │
    │         - Tabel Data (Scrollable)    │   - Detail Entitas    │
    │         - Non-scroll Header (Sticky) │   - Sub-page Flows    │
    │                                      │   - Zero Outer Child  │
    │                                      │     Padding           │
    │                                      │   - Sticky Footer:    │
    │                                      │     #popUpFooter      │
    │                                      │                       │
    └──────────────────────────────────────┴───────────────────────┘
                                ▲
                                │
                        <Modal> (Center Dialog)
                        - Khusus Konfirmasi Hapus
                        - Alert Peringatan Singkat
```

### 4.1. Pola Implementasi `PopUpPage` & Teleport Footer
```vue
<template>
    <PopUpPage :show="isOpen" :title="isEdit ? 'Edit Barang' : 'Tambah Barang'" @close="closeDrawer">
        <!-- Zero outer padding (modal-body sudah memiliki padding bawaan) -->
        <form class="space-y-2" @submit.prevent="submit">
            <TextField v-model="form.name" label="Nama Barang" :feedback="form.errors.name" />
            <DropdownField v-model="form.uom_id" :options="uomOptions" label="Satuan" />
            <NumberField v-model="form.min_stock" label="Minimum Stok" />

            <!-- Sticky Footer Action via Teleport -->
            <Teleport v-if="isMounted" to="#popUpFooter">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" @click="closeDrawer">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-main" :disabled="form.processing">
                        Simpan
                    </button>
                </div>
            </Teleport>
        </form>
    </PopUpPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PopUpPage from '@/Components/UI/PopUpPage.vue'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'

const isMounted = ref(false)
onMounted(() => { isMounted.value = true })

const props = defineProps({ isOpen: Boolean, isEdit: Boolean })
const emit = defineEmits(['close'])

const form = useForm({
    name: '',
    uom_id: null,
    min_stock: 0,
})

const closeDrawer = () => emit('close')
const submit = () => {
    form.post(route('inventories.items.store'), {
        onSuccess: () => closeDrawer(),
    })
}
</script>
```

---

## 5. Enum-Driven UI (Single Source of Truth)

Dilarang meng-hardcode nilai status atau tipe di template/script.

### 5.1. Penggunaan di Vue Template
```html
<!-- Status Badge dengan Label dan Warna Otomatis dari Enum Meta -->
<span class="badge" :class="$enums.AdjustmentStatus._meta[item.status]?.color || 'badge-gray'">
    {{ $enums.AdjustmentStatus._meta[item.status]?.label || item.status }}
</span>

<!-- Kondisi Visibilitas Tombol Berdasarkan Enum -->
<button v-if="item.status === $enums.AdjustmentStatus.Draft" class="btn btn-sm btn-main" @click="openEdit(item)">
    Edit Draf
</button>
```

### 5.2. Penggunaan di `<script setup>` via `useEnum()`
```javascript
import { useEnum } from '@/Composable/useEnum'

const { enums, getOptions, getLabel, getColor } = useEnum()

// Mengambil array opsi untuk dropdown [{ value: 'draft', label: 'Draf' }, ...]
const adjustmentStatusOptions = getOptions('AdjustmentStatus')

if (item.status === enums.AdjustmentStatus.Draft) {
    // Logika khusus
}
```

---

## 6. Table Filter Toolbar & URL Sync Pattern (Diekstrak ke Komponen)

Setiap filter modul diekstrak ke file komponen terpisah (`Components/{Entity}Filter.vue`) menggunakan komponen basis `@/Components/UI/Filter/` (**Inline Filter Toolbar**, dilarang menggunakan modal overlay):

```vue
<template>
    <FilterBar>
        <template #left>
            <!-- 1. Preset Tanggal & Rentang Waktu (Default: this_month) -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.start_date"
                v-model:end-date="filterForm.end_date"
                @change="updateQuery"
            />

            <!-- 2. Status Segmented Tabs / Counters -->
            <FilterSegmented
                v-model="filterForm.status"
                :options="statusSegmentOptions"
                @change="updateQuery"
            />

            <!-- 3. Inline Dropdown Filters -->
            <FilterDropdown
                v-if="categoryOptions.length > 0"
                v-model="filterForm.category_id"
                label="Kategori"
                :options="categoryOptions"
                all-option-label="Semua Kategori"
                @change="updateQuery"
            />
        </template>

        <!-- 4. Filter Actions (Ekspor & Impor) -->
        <template #actions>
            <FilterActions>
                <ExportDropdown
                    label="Ekspor"
                    :items="exportOptions"
                />
                <button
                    type="button"
                    class="btn btn-sm bg-white border border-gray-200 hover:border-gray-300 text-neutral-700 rounded-lg inline-flex items-center gap-1.5 transition"
                    @click="$emit('open-import')"
                >
                    <FontAwesomeIcon :icon="faUpload" class="text-xs text-neutral-500" />
                    <span>Impor</span>
                </button>
            </FilterActions>
        </template>

        <!-- 5. Search Bar -->
        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari data..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUpload } from '@fortawesome/free-solid-svg-icons'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterActions from '@/Components/UI/Filter/FilterActions.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ExportDropdown from '@/Components/UI/ExportDropdown.vue'

const props = defineProps({
    filters: Object,
    categories: Array,
})

defineEmits(['open-import'])

const filterForm = reactive({
    search: props.filters?.search || '',
    preset: props.filters?.preset || 'this_month',
    status: props.filters?.status || '',
    category_id: props.filters?.category_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        preset: filterForm.preset !== 'this_month' ? filterForm.preset : undefined,
        status: filterForm.status || undefined,
        category_id: filterForm.category_id || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
        page: 1, // Reset ke halaman 1 saat filter berubah
    }

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Watch search with debounce
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)
</script>
```

---

## 7. Custom CSS Classes & Utility Catalog (`app.css`)

Semua komponen dan halaman diwajibkan menggunakan kelas basis dan utility kustom yang telah terstandarisasi di `resources/css/app.css`:

### 7.1. Formulir & Input
- **`.form`:** Base styling untuk input teks, select, textarea dengan border `border-gray-200`, hover `border-gray-300`, focus ring `focus:ring-main/10 focus:border-main`, dan rounded-lg.
- **`.form.sm` (`class="form sm"`):** Ukuran input ringkas (`text-xs! !py-1.5 !px-2.5`, tinggi komputasi 30px). **Wajib digunakan untuk toolbar filter**, baris tabel padat, dan sub-form drawer.
- **`.form.lg` (`class="form lg"`):** Ukuran input besar (`text-base! !py-3 !px-5`). Digunakan untuk checkout POS dan hero search.
- **Standarisasi Keselarasan Tinggi Dropdown & Form `sm` (30px):** Seluruh elemen di baris toolbar filter (input search `FilterSearch`, tombol trigger `FilterDropdown`, `FilterPresetDate`, `ExportDropdown`, `FilterSegmented`, serta komponen form `DropdownField`, `AsyncSelectField`, `GroupDropdownIconField` dengan prop `size="sm"`) WAJIB memiliki tinggi seragam **30px** (`h-[30px]`, `text-xs leading-4`, `py-1.5 px-2.5`). Dilarang menggunakan variasi breakpoint seperti `sm:text-sm` pada trigger button yang dapat menyebabkan lonjakan tinggi tidak simetris.
- **`.form-group`:** Container input terpadu dengan addon label/ikon. Otomatis mengatur rounded dan border border-r antar child. Mendukung modifier `.form-group.sm` atau selector `:has(.form.sm)` yang otomatis menyelaraskan tinggi addon teks/ikon ke 30px (`text-xs !py-1.5 !px-2.5`):
  ```html
  <div class="form-group">
      <span class="form-group-text"><FontAwesomeIcon :icon="faSearch" /></span>
      <input type="text" class="form sm" placeholder="Cari..." />
  </div>
  ```
- **`.form-check`:** Wrapper checkbox (`input` kotak) dan radio button (`input` rounded-full). Mendukung modifier `.form-check.sm` (checkbox 14px, teks 12px) dan `.form-check.lg`.
- **`.form-floating`:** Container input dengan floating label animasi yang naik ke atas saat focus / terisi teks.
- **`.form-feedback`:** Kontainer feedback validasi yang muncul otomatis saat dipasangkan dengan `.is-valid` (hijau) atau `.is-invalid` (merah).

### 7.2. Tombol (`.btn`)
| Modifier / Kelas | Dimensi & Style | Font Size | Rekomendasi Penggunaan |
| :--- | :--- | :--- | :--- |
| **`.btn-xs`** | `px-2 py-1 gap-1` | `text-xs` (12px) | Aksi tabel padat, badge inline action, sub-item drawer. |
| **`.btn-sm`** | `px-2 py-1.5 gap-1` | `text-xs` (12px) | Standar aksi header (`MainPageHeader`), filter toolbar, dan aksi tabel umum. |
| **`.btn`** (Regular) | `px-4 py-2 gap-2` | `text-sm` (14px) | Form submit, modal confirmation, drawer footer CTA. |
| **`.btn-lg`** | `px-6 py-3` | `text-base` (16px) | CTA hero banner, checkout POS utama. |
| **`.btn-flat`** | `!border-gray-200 hover:!bg-gray-50 !bg-white` | Inherit | Tombol sekunder berlatar putih dengan border halus. |
| **`.btn-group`** | Rounded first/last child | Inherit | Grup tombol horizontal yang menempel. |

### 7.3. Badge, Pill & Active Filter Badges
- **`.badge`:** Label status berbatas (`px-2 py-1 rounded-lg`).
- **`.pill` / `.badge.pill`:** Badge kapsul (`rounded-full!`).
- **`.badge-doted`:** Badge transparan dengan ikon bullet FontAwesome.
- **`.filter-badge`:** Badge penanda kriteria filter aktif (`pl-2.5 pr-1.5 py-1.5 bg-slate-100 border border-slate-200 text-xs font-semibold rounded-lg`).
- **`.filter-badge-remove`:** Tombol silang kecil `✕` pada badge filter (`hover:bg-slate-200 rounded-full w-4 h-4 text-[9px]`).

### 7.4. Modal, Overlays & Uploaders
- **`.overlay-backdrop` & `.overlay-modal`:** Custom modal overlay z-[9999] dengan backdrop blur, animasi scale up `cubic-bezier`, `.overlay-header`, `.overlay-title`, `.overlay-close`, dan `.overlay-footer`.
- **`.uploader-dropzone`:** Area dropzone drag-and-drop file gambar (`border-2 border-dashed border-slate-300 hover:border-primary rounded-2xl p-6`).
- **`.uploader-preview-card`:** Kartu preview foto 1:1 aspect ratio dengan border halus dan hover highlight.
- **`.floating-scroll`:** Scrollbar minimalis mengambang dengan thumb terpusat dan track transparan.
- **`.hide-scrollbar`:** Menyembunyikan tampilan scrollbar horizontal/vertikal tanpa mematikan fungsi scroll.

