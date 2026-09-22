# Sollu App Frontend Architecture & UI Standards

Standar pengembangan frontend **Sollu App** berbasis **Vue 3 (Composition API `<script setup>`)**, **Inertia.js 1.2**, dan **Tailwind CSS v4**.

## 0. User-Centric Design Manifesto & 15 Core Principles

### 🌟 Filosofi Utama
> **"Jangan membuat user belajar cara kerja aplikasi; buat aplikasi mengikuti cara kerja user."**
> *(Aplikasi wajib beradaptasi dengan alur dan kebiasaan bisnis nyata pedagang/merchant, bukan memaksa pengguna mempelajari kerumitan teknis sistem).*

### 📋 15 Prinsip Pengalaman Pengguna Wajib:
1. **Mudah di-Setup:** Konfigurasi awal cepat, default settings siap pakai per `BusinessType`, registrasi ringkas.
2. **Mudah Dioperasikan:** Aksi harian (kasir POS, transaksi, opname) intuitif dan cepat.
3. **Interface Tidak Ambigu:** Label tombol deskriptif (`"+ Tambah Produk"`, `"Simpan Perubahan"`), badge status semantik.
4. **Experience User Diutamakan:** Ergonomi multi-perangkat (Mobile $\ge 44\text{px}$, Tablet $\ge 36\text{px}$, Desktop $\ge 28\text{px}$), thumb zone mobile, anti-zoom iOS Safari, respons $<5\text{s}$.
5. **Sederhana:** Desain Flat Minimalis (*zero shadows* di container `<MainPage>`), non-scrolling sticky header, responsive column masking.
6. **Jelas:** Gunakan bahasa familiar pedagang/kasir (*"Sampah"*, *"Pulihkan"*, *"Draf"*), bukan istilah teknis/sistem.
7. **Konsisten:** Pola layout `<MainPage>`, hierarki tombol (`.btn-xs`, `.btn-sm`, `.btn`), drawer `<PopUpPage>`, tabel `<Table>`, form `@/Components/Form/`.
8. **Smart Default:** Otomatisasi pre-fill (outlet aktif, preset tanggal `'this_month'`, auto-generate SKU/kode, autofocus).
9. **Minim Langkah:** Single action row click `@row-click`, shortcut POS, debounced inline search, dropdown `Opsi Data`.
10. **Progressive Disclosure:** 3-Tier Form Architecture (Simple $\le 5$ fields $\rightarrow$ Progressive Disclosure $6-12$ fields via `<DisclosureSection>` $\rightarrow$ Wizard/Tabs $> 12$ fields).
11. **Mencegah Kesalahan:** Real-time validation, proteksi form belum disimpan `useFormDirtyGuard`, modal konfirmasi sebelum aksi destruktif.
12. **Mudah Diperbaiki:** Soft delete & pulihkan data dari sampah (`FilterTrashData`), tombol Batal aman.
13. **Feedback Jelas:** Toast notification instan (`ResourceMessage`), loading spinner/skeleton, pesan validasi solutif.
14. **Ikuti Cara Kerja User:** Alur sistem mengikuti alur nyata pedagang (stok fisik vs sistem, split payment, partial goods receipt).
15. **Wording Santai Namun Tetap Profesional:** Nada bicara rekan kerja cerdas ("Yuk, ...", "Tokomu"), to the point, komunikatif & profesional.

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
6. **MANDATORY POPUPPAGE (ZERO CHILD OUTER PADDING):** Seluruh alur kerja Create, Edit, Detail, dan Sub-page **WAJIB** menggunakan `<PopUpPage>` (side-drawer kanan) atau `usePopUpStore()`. DILARANG menggunakan _full page redirect_ (`router.get()`) untuk form sub-halaman. Container body `PopUpPage.vue` sudah memiliki padding bawaan di level komponen, sehingga child form/view di dalamnya **DILARANG** menambahkan wrapper padding/margin luar lagi.
7. **MANDATORY `<Table>` COMPONENT, ROW LINK (SINGLE ACTION), SORTABLE HEADERS & CENTRALIZED EMPTY STATE:** Seluruh tampilan data tabular WAJIB menggunakan `@/Components/Tables/Table.vue`. Dilarang menulis tag `<table>` mentah. Untuk tabel dengan **single action** (misal hanya buka Detail/Edit), WAJIB gunakan event bawaan `@row-click="openDetail"` dan biarkan `:action="false"` (default). Gunakan `:action="true"` dengan slot `#actions` HANYA jika terdapat lebih dari 1 aksi per baris. Kolom yang dapat diurutkan wajib didefinisikan dengan `sortable: true` pada `headers` dan meneruskan props `:sort="params?.sort"` serta `:sort-direction="params?.direction"` ke `<Table>`. Penanganan _empty state_ ("data tidak ditemukan") ditangani secara terpusat di level komponen `<Table>`, DILARANG membuat container `v-if="data.length === 0"` manual di masing-masing page.
8. **MANDATORY FILTER COMPONENT EXTRACTION:** Setiap halaman yang memiliki filter data (search bar, filter status, filter kategori, date picker, dsb.) **WAJIB diekstrak ke file komponen terpisah** (misal: `resources/js/Pages/App/{Module}/Components/{Entity}Filter.vue` atau `Filter.vue`), bukan ditulis inline di file `Index.vue`.
9. **STANDARISASI ON-DEMAND DATA LOADING:** Data detail entitas lengkap dan data lookup form (opsi dropdown) WAJIB dimuat secara _asynchronous_ (Axios) hanya saat drawer dibuka. Wajib menyertakan skeleton loader / spinner saat fetching.
10. **MANDATORY ENUM FOR CONDITIONS & FORM OPTIONS:** Dilarang keras menggunakan string literal/hardcode. Selalu gunakan `$enums.<EnumName>.<Case>` atau `useEnum()`.
11. **MANDATORY FRONTEND UI & BUILD VERIFICATION:** Setiap pembuatan/perubahan komponen Vue WAJIB diverifikasi visual dan fungsional (bebas dari error kompilasi Vite/ESLint, verifikasi alur drawer `<PopUpPage>`, form field `@/Components/Form/`, dan toolbar filter).
12. **MANDATORY MULTI-DEVICE ERGONOMICS & TOUCH TARGET STANDARDS:** Seluruh komponen UI WAJIB ergonomis dan adaptif untuk Laptop, Tablet (POS), dan Smartphone (Mobile). Target sentuh minimum: Mobile $\ge 44\text{px}$ (`.touch-target`), Tablet $\ge 36\text{px}$ (`.touch-target-sm`), Desktop $\ge 28\text{px}$. Input formulir wajib mencegah _auto-zoom_ iOS Safari (gunakan `.form.adaptive` atau `text-base sm:text-xs`). Aksi formulir utama mobile wajib berada di _Thumb Zone_ bawah (`#popUpFooter`), tabel mobile menyembunyikan kolom sekunder (`show: 'md'`), dan elemen melayang wajib menyertakan safe area insets (`safe-pb`). Lihat panduan lengkap di [docs/ui-ergonomics.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/ui-ergonomics.md).
13. **MANDATORY UX COPYWRITING & WORDING STANDARDS (SANTAI, KOMUNIKATIF, TO THE POINT, PROFESIONAL):** Seluruh teks antarmuka (empty states, placeholders, toasts, modal konfirmasi, pesan error, label form) WAJIB menggunakan nada bicara ramah, lugas, tidak kaku/birokratis, namun tetap profesional dan presisi terhadap istilah bisnis. Dilarang menggunakan bahasa kaku ala Google Translate mentah atau slang pasar berlebihan. Lihat panduan lengkap di [docs/ux-wording.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/ux-wording.md).

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
        <!-- 1. Slot Header (NON-SCROLLABLE: Judul & Deskripsi Halaman) -->
        <template #header>
            <MainPageHeader
                title="Data Produk"
                description="Kelola seluruh katalog dan harga barang"
            />
        </template>

        <!-- 2. Slot Widgets (Opsional: Metrik Analitik / KPI Cards di antara Header dan Filter) -->
        <template v-if="$slots.widgets" #widgets>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <Widget ... />
            </div>
        </template>

        <!-- 3. Slot Filter (NON-SCROLLABLE: Toolbar Terpadu ActionBar yang Diekstrak) -->
        <template #filter>
            <ProductFilter :filters="params" :categories="categories" @create="openCreate" />
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
    <PopUpPage
        :show="isOpen"
        :title="isEdit ? 'Edit Barang' : 'Tambah Barang'"
        @close="closeDrawer"
    >
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
onMounted(() => {
    isMounted.value = true
})

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

### 4.2. Standar Formulir: Progressive Disclosure & Wizard Pattern (3-Tier Architecture)

Untuk mencegah pengguna mengalami _cognitive overload_ pada formulir dengan banyak isian:

#### A. Klasifikasi 3-Tier Formulir

| Tier                       | Rentang Field                  | Pola Desain                                                                                                    | Rekomendasi Modul                                                    |
| :------------------------- | :----------------------------- | :------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------- |
| **Tier 1 (Simple)**        | $\le 5$ field                  | Single flat vertical form, langsung tampil semua.                                                              | Kategori, Satuan UOM, Meja Kasir, Alasan Void.                       |
| **Tier 2 (Progressive)**   | $6 - 12$ field (Single domain) | Core fields (80%) tampak langsung + Advanced fields (20%) di `<DisclosureSection>` + Conditional triggers.     | Bahan Baku (Raw Material), Pelanggan, Karyawan, Promo.               |
| **Tier 3 (Wizard / Tabs)** | $> 12$ field / Multi-domain    | Asimetris: **Linear Stepper** (`<FormStepper>`) untuk Create, **Tabbed Navigation** (`<FormTabs>`) untuk Edit. | Produk (Varian & Resep), Purchase Order, Transfer Stok Antar-Outlet. |

#### B. Pola Progressive Disclosure (Tier 2) dengan `<DisclosureSection>`

```vue
<template>
    <form class="space-y-2" @submit.prevent="submit">
        <!-- 1. Core Fields (80% Kasus Harian - Selalu Tampak) -->
        <TextField
            v-model="form.name"
            label="Nama Bahan Baku"
            :feedback="form.errors.name"
            required
        />
        <DropdownField v-model="form.uom_id" :options="uomOptions" label="Satuan" required />

        <!-- 2. Conditional Trigger (Hanya muncul jika diaktifkan) -->
        <label
            class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl cursor-pointer"
        >
            <span class="text-xs font-semibold text-slate-700">Lacak Stok Otomatis</span>
            <input
                v-model="form.track_inventory"
                type="checkbox"
                class="rounded h-4 w-4 text-main"
            />
        </label>
        <NumberField
            v-if="form.track_inventory"
            v-model="form.min_stock"
            label="Minimum Stok Alert"
        />

        <!-- 3. Advanced / Secondary Fields (Progressive Disclosure) -->
        <DisclosureSection
            title="Pengaturan Lanjutan"
            description="SKU manual, barcode, dan catatan internal"
            :badge="advancedOptionsCount"
            :error="Boolean(form.errors.sku || form.errors.barcode)"
        >
            <TextField v-model="form.sku" label="SKU / Kode Barang" :feedback="form.errors.sku" />
            <TextField
                v-model="form.barcode"
                label="Barcode Scanner"
                :feedback="form.errors.barcode"
            />
            <TextareaField v-model="form.notes" label="Catatan Internal" rows="2" />
        </DisclosureSection>

        <!-- Sticky Footer Teleport -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button type="button" class="btn btn-flat" @click="close">Batal</button>
                <button type="submit" class="btn btn-main" :disabled="form.processing">
                    Simpan
                </button>
            </div>
        </Teleport>
    </form>
</template>
```

#### C. Pola Asimetris Wizard & Tabbed (Tier 3)

```vue
<template>
    <div>
        <!-- Create Mode: Linear Stepper -->
        <FormStepper
            v-if="!isEdit"
            :steps="steps"
            v-model:current-step-index="currentStepIndex"
            :errors="form.errors"
        />

        <!-- Edit Mode: Direct Tabbed Navigation -->
        <FormTabs v-else :tabs="steps" v-model="activeTabId" :errors="form.errors" />

        <!-- Active Step / Tab Content -->
        <div class="mt-2">
            <component :is="activeComponent" :form="form" />
        </div>

        <!-- Sticky Teleport Footer Navigation -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-between w-full">
                <template v-if="!isEdit">
                    <button
                        type="button"
                        class="btn btn-flat"
                        :disabled="isFirstStep"
                        @click="prevStep"
                    >
                        Kembali
                    </button>
                    <button v-if="!isLastStep" type="button" class="btn btn-main" @click="nextStep">
                        Lanjut
                    </button>
                    <button
                        v-else
                        type="button"
                        class="btn btn-main"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Simpan Data
                    </button>
                </template>
                <template v-else>
                    <button type="button" class="btn btn-flat" @click="close">Batal</button>
                    <button
                        type="button"
                        class="btn btn-main"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Simpan Perubahan
                    </button>
                </template>
            </div>
        </Teleport>
    </div>
</template>
```

---

### 4.3. Standarisasi Komponen Dropdown Formulir

Untuk memastikan UX pengisian data tetap cepat dan efisien, pemilihan komponen dropdown wajib mengikuti aturan berikut:

| Kriteria Data Dropdown                           | Komponen Standar Wajib                      | Karakteristik & Contoh Penggunaan                                                                                                                                                                       |
| :----------------------------------------------- | :------------------------------------------ | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Enum / Status Ringkas ($\le 5$ opsi)**         | `<DropdownField>` / `<SelectionGroupField>` | Pilihan statis tanpa pencarian (contoh: Status Pesanan, Tipe Diskon, Segmentasi Arsip).                                                                                                                 |
| **Master Data Lokal / Banyak Data ($> 5$ opsi)** | `<SearchableDropdownField>`                 | **Standar Wajib** untuk data yang dimuat dari master props (Satuan UOM, Pemasok/Supplier, Kategori, Outlet, Akun Akuntansi). Dilengkapi input pencarian cepat, navigasi keyboard, dan floating popover. |
| **Data Dinamis Masif / Server-Side Search**      | `<AsyncSelectField>`                        | Opsi ribuan baris berbasis pencarian AJAX/server-side (contoh: pencarian katalog produk, SKU inventori, pencarian pelanggan dinamis).                                                                   |

```vue
<!-- Contoh SearchableDropdownField pada Baris Item / Drawer -->
<SearchableDropdownField
    v-model="item.uom_id"
    label="Satuan Beli"
    size="sm"
    placeholder="Pilih Satuan..."
    search-placeholder="Cari satuan..."
    :options="uomOptions"
    :searchable="true"
    :error="form.errors[`items.${index}.uom_id`]"
    required
/>
```

---

### 4.4. Standarisasi Form Lifecycle & Dirty State Confirmation (`useFormDirtyGuard`)

Setiap formulir Create / Edit di dalam Drawer `<PopUpPage>` atau dialog modal **WAJIB** menerapkan proteksi *dirty state* melalui composable `@/Composable/useFormDirtyGuard`.

#### A. Aturan Baku Form Dirty:
1. **Dilarang Direct Store Close:** Dilarang keras memanggil `popUpStore.close()` secara langsung pada tombol Batal (`@click="popUpStore.close()"` adalah anti-pattern).
2. **Wajib `handleCancel`:** Tombol Batal **WAJIB** memanggil `handleCancel` dari `useFormDirtyGuard({ form })`.
3. **Wajib `forceClose` pada `onSuccess`:** Saat form berhasil disubmit, panggil `forceClose()` di dalam opsi `onSuccess` agar drawer menutup secara bersih tanpa memicu modal konfirmasi.
4. **UX Copywriting Terstandarisasi:** Jika form memiliki perubahan (`isDirty === true`), penutupan via tombol Batal, tombol silang `✕` Header, klik backdrop, maupun tombol `Escape` otomatis memunculkan dialog konfirmasi:
   - **Judul:** `"Perubahan Belum Disimpan"`
   - **Pesan:** `"Kamu memiliki perubahan data yang belum disimpan. Yakin mau membatalkan dan keluar dari formulir ini?"`
   - **Tombol Konfirmasi:** `"Ya, Buang Perubahan"` (merah / `btn-danger`)
   - **Tombol Batal:** `"Lanjut Mengisi"` (sekunder)

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'

const form = useForm({
    name: '',
    outlet_id: '',
})

// Pasang dirty guard cukup 1 baris:
const { handleCancel, forceClose, isDirty } = useFormDirtyGuard({ form })

const submit = () => {
    form.post(route('inventory.items.store'), {
        preserveScroll: true,
        onSuccess: () => forceClose(),
    })
}
</script>

<template>
    <!-- ... form body ... -->
    <Teleport v-if="isMounted" to="#popUpFooter">
        <button type="button" class="btn btn-flat" :disabled="form.processing" @click="handleCancel">
            Batal
        </button>
        <button type="button" class="btn btn-main" :disabled="form.processing" @click="submit">
            Simpan
        </button>
    </Teleport>
</template>
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
<button
    v-if="item.status === $enums.AdjustmentStatus.Draft"
    class="btn btn-sm btn-main"
    @click="openEdit(item)"
>
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

### 6. Table Action & Filter Toolbar (`ActionBar`) & URL Sync Pattern

Setiap kontrol filter dan aksi modul diekstrak ke file komponen terpisah (`Components/{Entity}Filter.vue`) menggunakan komponen basis `@/Components/UI/ActionBar/ActionBar.vue` (**Inline Action Toolbar**, dilarang menggunakan modal popup filter):

```vue
<template>
    <ActionBar>
        <template #filters>
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

        <!-- 4. Search Bar -->
        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari data..."
                @clear="updateQuery"
            />
        </template>

        <!-- 5. Data Tools (Dropdown Opsi Data: Ekspor & Impor) -->
        <template #tools>
            <ActionsDropdown label="Opsi Data" :items="actionItems" />
        </template>

        <!-- 6. Primary Action (Tombol Tambah Data di Paling Kanan) -->
        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                <span>Tambah Data</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faDownload, faUpload } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'

const props = defineProps({
    filters: Object,
    categories: Array,
})

const emit = defineEmits(['open-import', 'create'])

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

| Modifier / Kelas     | Dimensi & Style                                | Font Size          | Rekomendasi Penggunaan                                                       |
| :------------------- | :--------------------------------------------- | :----------------- | :--------------------------------------------------------------------------- |
| **`.btn-xs`**        | `px-2 py-1 gap-1`                              | `text-xs` (12px)   | Aksi tabel padat, badge inline action, sub-item drawer.                      |
| **`.btn-sm`**        | `px-2 py-1.5 gap-1`                            | `text-xs` (12px)   | Standar aksi header (`MainPageHeader`), filter toolbar, dan aksi tabel umum. |
| **`.btn`** (Regular) | `px-4 py-2 gap-2`                              | `text-sm` (14px)   | Form submit, modal confirmation, drawer footer CTA.                          |
| **`.btn-lg`**        | `px-6 py-3`                                    | `text-base` (16px) | CTA hero banner, checkout POS utama.                                         |
| **`.btn-flat`**      | `!border-gray-200 hover:!bg-gray-50 !bg-white` | Inherit            | Tombol sekunder berlatar putih dengan border halus.                          |
| **`.btn-group`**     | Rounded first/last child                       | Inherit            | Grup tombol horizontal yang menempel.                                        |

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
