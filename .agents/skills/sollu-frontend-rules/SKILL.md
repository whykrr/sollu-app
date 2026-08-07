---
name: sollu-frontend-rules
description: Frontend rules for the Sollu app project using Vue 3, Inertia.js, and Tailwind CSS. Use this whenever working on Vue components, page architecture, UI consistency, modals, filters, or form fields.
---
# Sollu Frontend Rules (Vue 3 / Inertia)

## 🚨 STRICT ANTI-HALLUCINATION CONSTRAINTS (CRITICAL) 🚨
1. **NO RAW HTML FORMS**: NEVER write raw HTML `<input>`, `<select>`, or `<textarea>`. You MUST always use existing components inside `@/Components/Form/`.
2. **NO HARDCODED PAGE LAYOUTS**: NEVER build page layouts from scratch with raw divs and Tailwind grids. You MUST always use the `<Container>` component (with `#header`, default, and `#footer` slots). 
3. **PRECISE COMPONENT PROPS**: Do not guess props. Custom form components use `v-model`, `label`, and `feedback` (for validation errors). Do NOT manually bind Tailwind classes like `is-invalid` to the form components.
4. **NO INLINE TAILWIND CLUTTER**: If a group of styling classes is used repeatedly or creates clutter (too long), you MUST create a new reusable utility or component class in `resources/css/app.css`. (See Custom Styling Section).
5. **GOLDEN REFERENCE**: Always study the UI in `resources/js/Pages/Inventory` (e.g., `resources/js/Pages/Inventory/Adjustment/Index.vue`) as the absolute standard for how pages and forms should be structured.

---

## 1. Custom Styling & Reusable CSS (`app.css`)
- **Avoid Excessively Long Inline Classes**: Do not bloat Vue templates with repetitive inline Tailwind classes.
- **Extract to `app.css`**: Create reusable custom classes in `resources/css/app.css` utilizing Tailwind v4 syntax.
- **Example Usage**:
  ```css
  /* In resources/css/app.css */
  @utility my-custom-card {
      @apply bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow;
  }
  ```
- **Rule of Thumb**: If you use the same combination of more than 5 classes multiple times, extract it to `app.css`.

---

## 2. Component Structure (Vue 3 Composition API)
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
1. Vue core (`ref`, `computed`, etc.)
2. Inertia / Router (`router`, `useForm`, `usePage`)
3. Third-party libraries (`lodash`, `FontAwesomeIcon`)
4. Global Components (`@/Components/...`)
5. Composables and Stores
6. Local/Page Components (`./Components/...`)

**Script Setup Internal Ordering:**
1. `defineOptions`
2. `defineProps` & `defineEmits`
3. Store / Composable initialization
4. Reactive state (`ref`, `reactive`)
5. Computed properties
6. Methods / Functions
7. Watchers
8. Lifecycle hooks

**Props Declaration Style:**
- **Pattern A:** `defineProps({ user: Object })` (if props are NOT accessed in `<script>`)
- **Pattern B:** `const props = defineProps({ user: Object })` (if props ARE accessed in `<script>`)
- **Do not use destructuring** for props to maintain Vue 3 reactivity.

---

## 3. Page Architecture & Golden Pattern
- **Reference**: `resources/js/Pages/Inventory/Adjustment/Index.vue` is the golden standard.
- One component, one responsibility. Keep components under 500 lines.
- **Page Folders:** Place sub-components inside `Pages/{Module}/Components/` (e.g., `Filter.vue`, `Form.vue`, `Detail.vue`).
- **Tabs:** Place tab components inside `Pages/{Module}/Tabs/` (e.g., `GeneralTab.vue`, `SettingsTab.vue`).

**Standard Index Pattern:**
All index pages must use `<Container>` (`@/Components/UI/Container.vue`):
- `<template #header>`: Contains `<ContainerHeader>`, Action Buttons, and the `<Filter>` component.
- **Default Slot**: Contains `<Table>` component with props `:headers`, `:data`, `:sort`, `:sortDirection`, and `:action="true"`.
- `<template #footer>`: Contains `<Pagination>`.

---

## 4. PopUpPage Pattern (Forms & Detail Views)
Forms and detail pages should slide in as side panels using `PopUpPage.vue` to maintain user context.

**PopUpPage vs Modal Pattern:**
- `PopUpPage.vue` (Side Panel): For edit/create forms or large entity details. Visibility is controlled via `:show="show"`.
- `Modal.vue` / `ModalDelete.vue` (Centered): Only for confirmation warnings or quick actions.

**Detail Data Loading Pattern (Axios):**
- Always use `axios.get()` to fetch detail/show data instead of Inertia partial reloads. This prevents page freeze.
- Wait for the API to resolve, map data to a `ref`, then set `showDetail.value = true`.

**Modal Form Lifecycle Pattern (Inertia `useForm`):**
- Initialize `useForm` with default values.
- Watch the selected item prop (`props.item`) with `{ immediate: true }`.
- Inside the watcher, `form.reset()` and populate fields: `form.field = item.field`.
- Form submissions (`form.post()`, `form.put()`) must always include `preserveState: true` and `preserveScroll: true`.
- On `onSuccess`, call `form.reset()` and emit `close`.

---

## 5. Table Filter Pattern
All table filters must use the generic UI `<FilterModal>` and `<FilterBadge>` components.
1. **Layout**:
   - Wrap with `flex items-center gap-2`.
   - Use `<FilterSearch>` for main text search.
   - Use a Filter button (faSliders) to open `<FilterModal>`.
   - Display active badges using `<FilterBadge>` (import from `@/Components/UI/Filter/FilterBadge.vue`).
2. **State Management**:
   - `filterForm` (reactive): Init from `props.filters`.
   - `tempFilters` (reactive): Temporary state inside modal.
   - `showFilterModal` (ref): Modal visibility.
3. **Auto-submit Search**:
   - Add a debounced watcher (500ms) for `filterForm.search` that calls `updateQuery()`.
4. **Modal Workflow**:
   - `openModal()`: Copy `filterForm` to `tempFilters`.
   - `applyFilters()`: Copy `tempFilters` to `filterForm`, close modal, call `updateQuery()`.
5. **Routing (`updateQuery`)**:
   - Combine `route().params` and filter state.
   - Strip empty strings (`''`) to `undefined`.
   - Reset page to 1.
   - Fire: `router.get(window.location.pathname, query, { preserveState: true, preserveScroll: true })`.

---

## 6. UI & Component Usage Patterns
Aturan penggunaan styling dan komponen UI (Hemat Token & Konsisten):

### 6.1 Styling Dasar & Class
- **Warna Tema (app.css)**: `main`, `secondary`, `success`, `danger`, `warning`, `info`. 
- **Spasi**: Gunakan skala 4 (`gap-4`, `p-4`) standar, skala 3 main area, skala 2 tight/form. Dilarang pakai padding/margin di dalam `PopUpPage` body.
- **Button**:
  - `btn` (wajib).
  - Varian warna: `btn-main`, `btn-outline-main`, `btn-highlight-main` (berlaku untuk semua warna).
  - Varian ukuran: `btn-xs`, `btn-sm`, `btn-lg`.
  - Icon Button: `<button class="btn btn-main"><FontAwesomeIcon :icon="faIcon" /> Text</button>`.

### 6.2 Layout & Container
- **`@/Components/UI/Container.vue`**: Wrapper utama halaman (mendukung slot `#header`, `#footer`, `#widgets`).
- **`@/Components/UI/Card/Card.vue`**: `<Card title="..." image="...">...<template #footer>...</template></Card>`.
- **`@/Components/UI/Card/CardFade.vue`**: Sama seperti Card dengan gradasi/fade effect.

### 6.3 Form, Input, & Grouping (`@/Components/Form/`)
- Gunakan `TextField`, `TextareaField`, `DropdownField`, `NumberField`, `Switch`, `CheckboxField`, `RadioField`, `AsyncSelectField`.
- Props Utama: `v-model`, `label`, `placeholder`, `feedback` (bukan error).
- **Validation**: Bind the validation string to the `feedback` prop. Do not try to append `is-invalid` classes manually to the component.
- **Dropdowns**: Always include a `placeholder` attribute to generate a default empty `<option>`.
- **Numbers**: Always use `NumberField` instead of `<input type="number">`. Values bind as raw numbers.
- **Quantity Display (`HasQuantityFormatter`)**: Whenever displaying stock or item quantities in the UI (tables, details), you MUST use the formatted quantity provided by the backend's `HasQuantityFormatter` trait (e.g., `item.qty_formatted`, `item.qty_received_formatted`). DO NOT use the raw `qty` field for display, and DO NOT manually format quantities in the frontend.
- Ukuran Form: Tambahkan class `sm` atau `lg`.
- **Form Group**: 
  - Icon/teks dengan input: `<div class="form-group"><label class="form-group-text">...</label><input class="form" /></div>`.
- **Form Floating**: 
  - `<div class="form-floating"><input id="x" required/><label for="x">...</label></div>`.
- **Checkbox / Radio**:
  - Standar: `<div class="form-check"><input class="form-check-input" type="checkbox"/><label>...</label></div>`.
  - Tampilan Button: `<input class="form-check-btn peer" type="radio"/><label class="btn btn-outline-main">...</label>`.

**Correct Example:**
```html
<TextField
    id="name"
    v-model="form.name"
    label="Nama Lengkap"
    :feedback="form.errors.name"
/>
```

### 6.4 Tables & Data Display (`@/Components/Tables/`)
- **`Table.vue`**: Gunakan prop `:action="true"` jika ada aksi, isi slot `#actions`. Jangan buat kolom action manual.
- **`Pagination.vue`**: Gunakan default `per_page: 20` pada request backend.

### 6.5 Widgets (Dashboard/Metrics)
Berikan warna via class (cth: `widget-main`, `widget-teal`).
- **`Widget.vue`**: Metrik standar. Slot default = nilai utama. Props: `title`, `icon`, `traction`, `tractionPercentage`, `descriptors`.
- **`WidgetProgress.vue`**: Metrik dengan progress bar. Props: `title`, `icon`, `value`, `maxValue`.
- **`WidgetChart.vue`**: Metrik chart. Tanpa slot. Props: `id`, `title`, `icon`, `type`, `highlight`, `sub-highlight`, `labels`, `data`.

### 6.6 Partial Loading & Loading States (Deferred)
- Saat mengimplementasikan pemuatan data secara parsial (Partial Loading, Lazy Loading, Infinte Scroll, Inertia Deferred props, dll), **WAJIB** selalu menggunakan UI _placeholder_ (contoh: skeleton loader, indikator _spinner_, atau state teks "Memuat...").
- Dilarang keras membiarkan area tampilan kosong/blank (atau UI tampak freeze) selama data sedang di-_fetch_.
- Gunakan komponen _skeleton_ bawaan (bila tersedia) atau manfaatkan animasi Tailwind pulse sederhana (`animate-pulse bg-gray-200 rounded`) sebagai placeholder selagi menunggu komponen/data dirender penuh.

---

## 7. State Management & Auth (Composables & Pinia)

### 7.1 Auth & Permissions (`useAuth`)
**ALWAYS** use the `useAuth` composable located at `@/Composable/useAuth` instead of directly accessing `usePage().props.auth`.
- **Import:** `import { useAuth } from '@/Composable/useAuth';`
- **Exposed Utilities:** `user`, `business`, `outlets`, `roles`, `permissions`, `isOwner`, `can`, `canAny`, `canAll`, `hasRole`, `hasAnyRole`.
- **Usage Example:**
  ```javascript
  const { user, canAny, can } = useAuth();
  
  const canApprove = computed(() => {
      if (can('business.*')) return true;
      return canAny(['inventory.transfer.approve']) && transferData.value?.requester?.id !== user.value?.id;
  });
  ```

### 7.2 Modals & Global State (Pinia)
The project uses Pinia for global state, located in `resources/js/store/`.
- **Notification/Delete Modal:** Use `useModalStore` from `@/store/notification`.
  - For standard delete (Hapus Data): `modalStore.openModalDelete(route('api.destroy', id))`
  - For soft delete (Arsipkan): `modalStore.openModalSoftDelete(route('api.archive', id))`
- **App Sidebar State:** Use `useAppStore` from `@/store/app` to control sidebar visibility and active states.
