---
name: sollu-frontend-rules
description: Frontend rules for the Sollu app project using Vue 3, Inertia.js, and Tailwind CSS. Use this whenever working on Vue components, page architecture, UI consistency, modals, filters, or form fields.
---
# Sollu Frontend Rules (Vue 3 / Inertia)

## Component Structure
Use **Composition API** (`<script setup>`).

**Template vs Script Ordering:**
- **Always use template-first ordering:**
```vue
<template>...</template>
<script setup>
...
</script>
```

**Import Ordering:**
```javascript
// 1. Vue core
import { ref, computed, watch, onMounted } from 'vue';
// 2. Inertia / Router
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
// 3. Third-party libraries
import { debounce } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faPencil } from '@fortawesome/free-solid-svg-icons';
// 4. Global Components (alias @/)
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
// 5. Composables
import { formatDateTimeSimple } from '@/Composable/date';
// 6. Stores (Pinia)
import { useModalStore } from '@/store/notification';
// 7. Local/Page Components (relative path)
import Filter from './Components/Filter.vue';
import Form from './Components/Form.vue';
```

**Script Setup Internal Ordering:**
```javascript
// 1. defineOptions (if any)
// 2. defineProps
// 3. defineEmits
// 4. Store / Composable initialization
// 5. Reactive state (ref, reactive)
// 6. Computed properties
// 7. Methods / Functions
// 8. Watchers
// 9. Lifecycle hooks (onMounted, etc.)
```

**Props Declaration Style:**
- **Pattern A:** `defineProps({ user: Object })` (if props are NOT accessed in `<script>`)
- **Pattern B:** `const props = defineProps({ user: Object })` (if props ARE accessed in `<script>`)
- **Do not use destructuring** for props to maintain Vue 3 reactivity.

## Page Architecture
- One component, one responsibility. Keep components under 500 lines.
- **Page Folders:** Place sub-components inside `Pages/{Module}/Components/` (e.g., `Filter.vue`, `Form.vue`, `Detail.vue`).
- **Tabs:** Place tab components inside `Pages/{Module}/Tabs/` (e.g., `GeneralTab.vue`, `SettingsTab.vue`).

## UI Consistency & Page Patterns

**Inertia Workflow (Index + Show):**
- The `index` and `show` routes often map to the same controller method.
- The `show` route fetches detail data via partial reload (`only: ['user']`).
- When closing a detail view, use `router.get` to reset the URL and clear the prop.

**Standard List/Index Pattern:**
All index pages must use `<Container>` (`@/Components/UI/Container.vue`):
- `<template #header>`: Contains `<ContainerHeader>` `<Filter>` component and action buttons (Add).
- **Default Slot**: Contains `<Table>` component with props `headers`, `data`, `sort`, `sort-direction`.
- `<template #footer>`: Contains `<Pagination>`.

**Data Fetching & Inertia Routing:**
- Form submissions (`form.post()`, `form.put()`) must always include `preserveState: true` and `preserveScroll: true`.
- Transitioning or loading base details uses `router.visit()` with **partial reloads** (`only: ['prop_name']`).
- **Detail Data Loading:** Always use Axios (`axios.get()`) to fetch detail/show data instead of Inertia partial reloads. This prevents page freeze and provides better control over loading states. Register the corresponding routes in `web.php`.
- For complex data loading in tabs, use API calls (Axios) to prevent page freeze. Register routes in `web.php`.

**Table Filter Pattern:**
Setiap filter table harus menggunakan komponen UI generic `<FilterModal>` dan `<FilterBadge>`:
1. **Layout & Komponen**: 
   - Gunakan wrapper `flex items-center gap-2`.
   - Gunakan `<FilterSearch>` untuk pencarian teks utama (`v-model="filterForm.search"`).
   - Buat tombol "Filter" dengan icon `faSliders` untuk membuka modal (`showFilterModal = true`).
   - Tampilkan badge aktif menggunakan `<FilterBadge>` (import dari `@/Components/UI/Filter/FilterBadge.vue`) untuk setiap filter yang sedang berjalan dengan event `@remove="removeFilter(key)"`.
   - Gunakan `<FilterModal>` (import dari `@/Components/UI/Filter/FilterModal.vue`) alih-alih membuat elemen *overlay* HTML hardcode. Komponen ini mengatur state dasar *backdrop*, *header*, tombol *close*, dan tombol *footer*. Anda cukup mengirim event props: `:show="showFilterModal"`, `@close="closeModal"`, `@reset="resetTempFilters"`, `@apply="applyFilters"`.
2. **State Management (Script Setup)**:
   - Definisikan `filterForm` menggunakan `reactive()` dengan inisialisasi dari `props.filters`.
   - Definisikan `tempFilters` menggunakan `reactive()` untuk menyimpan state sementara di dalam modal.
   - Definisikan `showFilterModal` menggunakan `ref(false)`.
3. **Pencarian Auto-submit**:
   - Terapkan watcher terpisah khusus untuk `filterForm.search` menggunakan `debounce` (500ms) agar langsung memanggil `updateQuery()`.
4. **Alur Kerja Modal (Apply, Reset, Cancel)**:
   - `openModal()`: Salin nilai `filterForm` ke `tempFilters`, lalu buka modal.
   - `closeModal()`: Tutup modal tanpa merubah apapun.
   - `resetTempFilters()`: Kosongkan nilai di `tempFilters` saja.
   - `applyFilters()`: Salin nilai `tempFilters` kembali ke `filterForm`, tutup modal, lalu jalankan `updateQuery()`.
5. **Inertia Router Push (`updateQuery`)**:
   - Buat objek `query` dengan menggabungkan `route().params` dan isi filter. 
   - Pastikan parameter yang kosong (`''`) diubah menjadi `undefined` agar tidak muncul di URL.
   - Selalu reset halaman kembali ke `page: 1`.
   - Lakukan request: `router.get(window.location.pathname, query, { preserveState: true, preserveScroll: true });`.

**Modal Form Lifecycle Pattern:**
- Initialize `useForm` with null/empty default values.
- Watch the data prop (`props.user`) with `{ immediate: true }`.
- Inside the watcher, call `form.reset()` and then populate `form.field = data.field`.
- On submit success (`onSuccess` callback), call `form.reset()` and emit `close`.

**PopUpPage vs Modal Pattern:**
- `PopUpPage.vue` (Side Panel): For edit/create forms or large entity details. Visibility is controlled via `:class="{ show: show }"`.
- `Modal.vue` / `ModalDelete.vue` (Centered): Only for confirmation warnings or quick actions.

**Tab Pattern:**
- For complex details, wrap a `<Tab>` component inside a `PopUpPage`.
- Define tabs as a computed array: `[{ label: 'General', icon: faCog, page: GeneralTab, props: { outlet } }]`.

**Form Fields Pattern:**
- Use custom components (e.g., `TextField`, `DropdownField`).
- **DropdownField:** Selalu sertakan properti `placeholder` (misal: `placeholder="Pilih opsi..."`) agar menghasilkan `<option value="">` sebagai pilihan bawaan (kosong) yang memandu pengguna.
- **NumberField:** Gunakan `NumberField` untuk setiap input bertipe angka/kuantitas (jangan gunakan `<input type="number">` secara langsung). Pastikan nilai yang di-binding ke `v-model` adalah angka asli (raw number), bukan string yang sudah diformat.
- Init state with Inertia's `useForm`.
- Validation error display:
```html
<TextField
    id="name"
    v-model="form.name"
    label="Nama Lengkap"
    :class="{ 'is-invalid': form.errors.name }"
    :feedback="form.errors.name"
/>
```

## UI & Component Usage Patterns
Aturan penggunaan styling dan komponen UI (Hemat Token & Konsisten):

### 1. Styling Dasar & Class
- **Warna Tema (app.css)**: `main`, `secondary`, `success`, `danger`, `warning`, `info`. 
- **Spasi**: Gunakan skala 4 (`gap-4`, `p-4`) standar, skala 3 main area, skala 2 tight/form. Dilarang pakai padding/margin di dalam `PopUpPage` body.
- **Button**:
  - `btn` (wajib).
  - Varian warna: `btn-main`, `btn-outline-main`, `btn-highlight-main` (berlaku untuk semua warna).
  - Varian ukuran: `btn-xs`, `btn-sm`, `btn-lg`.
  - Icon Button: `<button class="btn btn-main"><FontAwesomeIcon :icon="faIcon" /> Text</button>`.

### 2. Layout & Container
- **`@/Components/UI/Container.vue`**: Wrapper utama halaman (mendukung slot `#header`, `#footer`, `#widgets`).
- **`@/Components/UI/Card/Card.vue`**: `<Card title="..." image="...">...<template #footer>...</template></Card>`.
- **`@/Components/UI/Card/CardFade.vue`**: Sama seperti Card dengan gradasi/fade effect.

### 3. Form & Input (`@/Components/Form/`)
- Gunakan `TextField`, `TextareaField`, `DropdownField`, `NumberField`, `Switch`.
- Props: `v-model`, `label`, `placeholder`, `error`, `success`. 
- Ukuran Form: Tambahkan class `sm` atau `lg`.
- **Form Group**: 
  - Icon/teks dengan input: `<div class="form-group"><label class="form-group-text">...</label><input class="form" /></div>`.
- **Form Floating**: 
  - `<div class="form-floating"><input id="x" required/><label for="x">...</label></div>`.
- **Checkbox / Radio**:
  - Standar: `<div class="form-check"><input class="form-check-input" type="checkbox"/><label>...</label></div>`.
  - Tampilan Button: `<input class="form-check-btn peer" type="radio"/><label class="btn btn-outline-main">...</label>`.

### 4. Tables & Data Display (`@/Components/Tables/`)
- **`Table.vue`**: Gunakan prop `:actions="true"` jika ada aksi, isi slot `#actions`. Jangan buat kolom action manual.
- **`Pagination.vue`**: Gunakan default `per_page: 20` pada request backend.

### 5. Widgets (Dashboard/Metrics)
Berikan warna via class (cth: `widget-main`, `widget-teal`).
- **`Widget.vue`**: Metrik standar. Slot default = nilai utama. Props: `title`, `icon`, `traction`, `tractionPercentage`, `descriptors`.
- **`WidgetProgress.vue`**: Metrik dengan progress bar. Props: `title`, `icon`, `value`, `maxValue`.
- **`WidgetChart.vue`**: Metrik chart. Tanpa slot. Props: `id`, `title`, `icon`, `type`, `highlight`, `sub-highlight`, `labels`, `data`.
