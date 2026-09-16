# Sollu App Frontend Architecture & UI Standards

Standar pengembangan frontend **Sollu App** berbasis **Vue 3 (Composition API `<script setup>`)**, **Inertia.js 1.2**, dan **Tailwind CSS v4**.

---

## 1. 🚨 10 Anti-Hallucination Core Rules

1. **NO RAW HTML FORMS & TABLES:** Dilarang keras menuliskan tag `<input>`, `<select>`, `<textarea>`, atau `<table>` mentah. Wajib menggunakan komponen dari `@/Components/Form/` dan `@/Components/Tables/Table.vue`.
2. **PROJECT-SPECIFIC TAILWIND STYLES:** Gunakan utility class bawaan proyek di `app.css` (`btn`, `btn-main`, `btn-outline-main`, `btn-danger`, `form`, `form-group`, dll).
3. **MANDATORY MAINPAGE & NON-SCROLLING HEADER:** Seluruh halaman utama wajib dibungkus dengan komponen `<MainPage>`. Seluruh kartu (*cards*), widget KPI (*widgets*), bar pencarian & filter (*filters*), serta tombol aksi WAJIB diletakkan di slot `<template #header>` (atau `<MainPageHeader>`) agar tetap sticky di atas dan **TIDAK ikut ter-scroll** saat tabel/konten di default slot digulir.
4. **SPACING SCALE 2 PADA MAINPAGE & MAKSIMAL SCALE 3 PADA KOMPONEN BARU:**
   - Jarak antar-komponen di atas `<MainPage>` dan di dalam slot-nya WAJIB berskala 2 (`gap-2`, `gap-y-2`, `gap-x-2`, `space-y-2`, `space-x-2`, `m-2`, `my-2`, `mt-2`, `mb-2`).
   - Margin dan padding pada komponen baru DILARANG melebihi skala 3 (`p-3`, `px-3`, `py-3`, `m-3`, `mx-3`, `my-3`).
   - Jarak antar-input formulir DILARANG melebihi skala 2 (`space-y-2`, `gap-2`).
5. **MANDATORY POPUPPAGE (ZERO CHILD OUTER PADDING):** Seluruh alur kerja Create, Edit, Detail, dan Sub-page **WAJIB** menggunakan `<PopUpPage>` (side-drawer kanan) atau `usePopUpStore()`. DILARANG menggunakan *full page redirect* (`router.get()`) untuk form sub-halaman. Container body `PopUpPage.vue` sudah memiliki padding bawaan di level komponen, sehingga child form/view di dalamnya **DILARANG** menambahkan wrapper padding/margin luar lagi.
6. **MANDATORY `<Table>` COMPONENT, SORTABLE HEADERS & CENTRALIZED EMPTY STATE:** Seluruh tampilan data tabular WAJIB menggunakan `@/Components/Tables/Table.vue`. Dilarang menulis tag `<table>` mentah. Kolom yang dapat diurutkan wajib didefinisikan dengan `sortable: true` pada `headers` dan meneruskan props `:sort="params?.sort"` serta `:sort-direction="params?.direction"` ke `<Table>`. Penanganan *empty state* ("data tidak ditemukan") ditangani secara terpusat di level komponen `<Table>`, DILARANG membuat container `v-if="data.length === 0"` manual di masing-masing page.
7. **MANDATORY FILTER COMPONENT EXTRACTION:** Setiap halaman yang memiliki filter data (search bar, filter status, filter kategori, date picker, dsb.) **WAJIB diekstrak ke file komponen terpisah** (misal: `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`), bukan ditulis inline di file `Index.vue`.
8. **STANDARISASI ON-DEMAND DATA LOADING:** Data detail entitas lengkap dan data lookup form (opsi dropdown) WAJIB dimuat secara *asynchronous* (Axios) hanya saat drawer dibuka. Wajib menyertakan skeleton loader / spinner saat fetching.
9. **MANDATORY ENUM FOR CONDITIONS & FORM OPTIONS:** Dilarang keras menggunakan string literal/hardcode. Selalu gunakan `$enums.<EnumName>.<Case>` atau `useEnum()`.
10. **MANDATORY BROWSERMCP UI VERIFICATION:** Setiap pembuatan/perubahan komponen Vue WAJIB diverifikasi visual dan fungsional via `browsermcp` (navigasi URL, snapshot DOM, screenshot, dan console logs).

---

## 2. Directory Layout (`resources/js`)

```
resources/js/
├── Components/          # Global reusable UI & Form components (Lihat AGENTS.md)
│   ├── Button/          # ButtonBack.vue, ButtonGroupArchive.vue, ButtonIconGroupArchive.vue
│   ├── Cards/           # CardTransparent.vue
│   ├── Form/            # TextField, DropdownField, NumberField, SelectionGroupField, etc.
│   ├── Modals/          # FeatureLockedModal.vue, ImportCsvModal.vue
│   ├── Notifications/   # Modal.vue, ModalContainer.vue, Toast.vue, ToastContainer.vue
│   ├── Tables/          # Table.vue, Pagination.vue, DraggableTable.vue
│   ├── UI/              # MainPage.vue, MainPageHeader.vue, PopUpPage.vue, Tab.vue, Filter/*
│   └── Widgets/         # Widget.vue, WidgetChart.vue, WidgetProgress.vue
├── Composable/          # Vue composables (useAuth, useEnum, usePlanFeature, useDropdown)
├── Pages/               # Inertia page views
│   ├── App/             # Merchant application modules
│   │   ├── Inventory/   # Index.vue, Components/ ({Entity}FormPopUp, Detail, Filter)
│   │   ├── Master/      # Index.vue, Components/
│   │   └── ...          # Modul lainnya
│   └── Cockpit/         # Admin panel pages
├── store/               # Pinia state stores (usePopUpStore, useModalStore, useToastStore)
├── Layout/              # Master layouts (AppLayout.vue, AuthLayout.vue)
└── app.js               # Inertia client bootstrap & plugin registrations
```

---

## 3. Standard Page Layout (`MainPage` & `MainPageHeader`)

Pola struktur utama untuk seluruh halaman modul:

```vue
<template>
    <MainPage>
        <!-- 1. Slot Widgets (Opsional: Metrik Analitik KPI) -->
        <template v-if="$slots.widgets" #widgets>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <Widget ... />
            </div>
        </template>

        <!-- 2. Slot Header (NON-SCROLLABLE: Judul, Aksi, Filter Bar, Kartu Ringkasan) -->
        <template #header>
            <MainPageHeader title="Data Produk" description="Kelola seluruh katalog dan harga barang">
                <button class="btn btn-flat btn-sm" @click="exportCsv">
                    <FontAwesomeIcon :icon="faDownload" /> Ekspor Data
                </button>
                <button class="btn btn-highlight-main" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" /> Tambah Produk
                </button>
            </MainPageHeader>
            <!-- Komponen filter yang diekstrak terpisah -->
            <ProductFilter :filters="params" :categories="categories" />
        </template>

        <!-- 3. Default Slot (SCROLLABLE CONTAINER: Tabel Data dengan Sortable) -->
        <Table
            :headers="headers"
            :data="products.data"
            :sort="params?.sort ?? 'updated_at'"
            :sort-direction="params?.direction ?? 'desc'"
            :action="true"
        >
            <template #status="{ row }">
                <span class="badge" :class="$enums.ProductStatus._meta[row.status]?.color">
                    {{ $enums.ProductStatus._meta[row.status]?.label }}
                </span>
            </template>
            <template #actions="{ row }">
                <button class="btn btn-flat btn-sm" @click="openEdit(row)">
                    <FontAwesomeIcon :icon="faPen" />
                </button>
            </template>
        </Table>

        <!-- 4. Slot Footer (Pagination Bar) -->
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

## 6. Table Filter & URL Sync Pattern (Diekstrak ke Komponen)

Setiap filter modul diekstrak ke file komponen terpisah (`Components/{Entity}Filter.vue`):

```vue
<template>
    <div class="flex flex-wrap items-center gap-2">
        <FilterSearch v-model="filterForm.search" />
        <div class="w-44">
            <DropdownField
                v-model="filterForm.status"
                :options="[{ value: '', label: 'Semua Status' }, ...statusOptions]"
                placeholder="Pilih Status"
            />
        </div>
        <FilterBadge v-if="filterForm.status" @remove="filterForm.status = ''">
            Status: {{ getLabel('ProductStatus', filterForm.status) }}
        </FilterBadge>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import { useEnum } from '@/Composable/useEnum'

const { getOptions, getLabel } = useEnum()
const statusOptions = getOptions('ProductStatus')

const props = defineProps({
    filters: Object,
})

const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
})

const updateQuery = debounce(() => {
    router.get(location.pathname, {
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        page: 1, // Reset ke halaman 1 saat filter berubah
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}, 500)

watch(() => [filterForm.search, filterForm.status], () => updateQuery())
</script>
```
