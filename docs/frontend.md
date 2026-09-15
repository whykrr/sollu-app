# Sollu App Frontend Architecture & UI Standards

Standar pengembangan frontend **Sollu App** berbasis **Vue 3 (Composition API `<script setup>`)**, **Inertia.js 1.2**, dan **Tailwind CSS v4**.

---

## 1. 🚨 10 Anti-Hallucination Core Rules

1. **NO RAW HTML FORMS:** Dilarang keras menuliskan tag `<input>`, `<select>`, atau `<textarea>` mentah. Wajib menggunakan komponen dari `@/Components/Form/`.
2. **PROJECT-SPECIFIC TAILWIND STYLES:** Gunakan utility class bawaan proyek di `app.css` (`btn`, `btn-main`, `btn-outline-main`, `btn-danger`, `form`, `form-group`, dll).
3. **NO HARDCODED PAGE LAYOUTS:** Seluruh halaman utama wajib dibungkus dengan komponen `<MainPage>` (`#header`, default slot, `#footer`).
4. **PRECISE COMPONENT PROPS:** Komponen form standar menerima `v-model`, `label`, `placeholder`, dan `feedback` (pesan error validasi). Dilarang mengikat class `is-invalid` manual.
5. **NO TAILWIND CLUTTER:** Ekstrak kelompok class berulang (5+ class) menjadi `@utility` di `resources/css/app.css`.
6. **MANDATORY POPUPPAGE FOR SUB-PAGES & FORMS:** Seluruh alur kerja Create, Edit, Detail, dan Sub-page **WAJIB** menggunakan `<PopUpPage>` (side-drawer kanan) atau `usePopUpStore()`. DILARANG menggunakan *full page redirect* (`router.get()`) untuk form sub-halaman.
7. **FORM SPACING LIMIT (MAX SCALE 2):** Jarak antar-input formulir DILARANG melebihi scale 2 Tailwind (`space-y-2`, `space-x-2`, `gap-2`, `gap-y-2`, `gap-x-2`).
8. **STANDARISASI ON-DEMAND DATA LOADING:** Data detail entitas lengkap dan data lookup form (opsi dropdown) WAJIB dimuat secara *asynchronous* (Axios) hanya saat drawer dibuka. Wajib menyertakan skeleton loader / spinner saat fetching.
9. **MANDATORY ENUM FOR CONDITIONS & FORM OPTIONS:** Dilarang keras menggunakan string literal/hardcode. Selalu gunakan `$enums.<EnumName>.<Case>` atau `useEnum()`.
10. **MANDATORY BROWSERMCP UI VERIFICATION:** Setiap pembuatan/perubahan komponen Vue WAJIB diverifikasi visual dan fungsional via `browsermcp` (navigasi URL, snapshot DOM, screenshot, dan console logs).

---

## 2. Directory Layout (`resources/js`)

```
resources/js/
├── Components/          # Global reusable UI & Form components
│   ├── Form/            # TextField, DropdownField, NumberField, SelectionGroupField, etc.
│   ├── Modal/           # Modal.vue, ConfirmModal.vue
│   ├── PopUpPage/       # PopUpPage.vue, PopUpPageHeader.vue
│   ├── Table/           # Table.vue, Pagination.vue, FilterSearch.vue, FilterBadge.vue
│   └── FeatureLock/     # FeatureLock.vue, FeatureLockOverlay.vue, FeatureLockedModal.vue
├── Composable/          # Vue composables (useAuth, useEnum, usePlanFeature, useDebounce)
├── Pages/               # Inertia page views
│   ├── App/             # Merchant application modules
│   │   ├── Inventory/   # Index.vue, Components/ (PopUp form, detail, filter privat)
│   │   ├── Master/      # Index.vue, Components/
│   │   └── ...          # Modul lainnya
│   └── Cockpit/         # Admin panel pages
├── store/               # Pinia state stores (usePopUpStore, useModalStore, useToastStore)
├── Layout/              # Master layouts (MainPage.vue, AuthLayout.vue)
└── app.js               # Inertia client bootstrap & plugin registrations
```

---

## 3. Form Components System (`@/Components/Form/`)

Seluruh input form wajib menggunakan komponen resmi berikut:

| Komponen Form | Deskripsi & Contoh Penggunaan |
| :--- | :--- |
| **`TextField`** | Input teks standar (`type="text"`, `"email"`, `"password"`). `<TextField v-model="form.name" label="Nama Barang" :feedback="form.errors.name" />` |
| **`NumberField`** | Input numerik dengan auto formatting mata uang / desimal. `<NumberField v-model="form.price" label="Harga Jual" prefix="Rp" />` |
| **`TextareaField`** | Input area teks multiline. `<TextareaField v-model="form.notes" label="Catatan" rows="3" />` |
| **`DropdownField`** | Dropdown pilihan statis/enum. `<DropdownField v-model="form.status" :options="getOptions('AdjustmentStatus')" label="Status" />` |
| **`AsyncSelectField`** | Dropdown pencarian async untuk dataset besar (produk, bahan, pelanggan). |
| **`AsyncOutletDropdown`** | Dropdown khusus untuk pemilihan outlet tenant secara async. |
| **`Switch`** | Toggle switch boolean aktif/non-aktif. `<Switch v-model="form.is_active" label="Aktifkan Menu" />` |
| **`CheckboxField`** | Pilihan kotak centang tunggal. |
| **`RadioField`** | Pilihan tombol radio tunggal. |
| **`SelectionGroupField`** | Grup tombol pilihan (pola pill/segmented radio atau checkbox multi-select dengan *Select All*). |

### 3.1. Contoh SelectionGroupField
```vue
<!-- Single Select (Segmented Radio) -->
<SelectionGroupField
    v-model="form.type"
    label="Tipe Penyesuaian"
    :options="[
        { value: 'addition', label: 'Penambahan (+)' },
        { value: 'reduction', label: 'Pengurangan (-)' }
    ]"
/>

<!-- Multi Select (Checkbox Buttons dengan Select All) -->
<SelectionGroupField
    v-model="form.outlet_ids"
    label="Pilih Outlet Berlaku"
    :options="outletOptions"
    multiple
    show-select-all
/>
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
    │         - Tabel Data                 │   - Detail Entitas    │
    │         - Filter Bar                 │   - Sub-page Flows    │
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
        <form class="space-y-2" @submit.prevent="submit">
            <TextField v-model="form.name" label="Nama Barang" :feedback="form.errors.name" />
            <DropdownField v-model="form.uom_id" :options="uomOptions" label="Satuan" />
            <NumberField v-model="form.min_stock" label="Minimum Stok" />

            <!-- Sticky Footer Action via Teleport -->
            <Teleport v-if="isMounted" to="#popUpFooter">
                <div class="flex items-center justify-end gap-2 p-3 bg-white border-t border-gray-100">
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
import PopUpPage from '@/Components/PopUpPage/PopUpPage.vue'
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

## 6. Table Filter & URL Sync Pattern

Setiap halaman modul dengan tabel menerapkan pola filter responsif dan ter-debounce:

```vue
<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import FilterSearch from '@/Components/Table/FilterSearch.vue'
import FilterBadge from '@/Components/Table/FilterBadge.vue'

const props = defineProps({
    items: Object,
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

watch(() => filterForm.search, () => updateQuery())
</script>
```
