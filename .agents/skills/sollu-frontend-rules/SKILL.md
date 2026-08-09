---
name: sollu-frontend-rules
description: Frontend rules for the Sollu app project using Vue 3, Inertia.js, and Tailwind CSS. Use this whenever working on Vue components, page architecture, UI consistency, modals, filters, or form fields.
---

# Sollu Frontend Rules (Vue 3 / Inertia)

## 1. 🚨 Anti-Hallucination Core Rules
1. **NO RAW HTML FORMS:** ALWAYS use `@/Components/Form/` components (`TextField`, `TextareaField`, `DropdownField`, `NumberField`, `Switch`, `CheckboxField`, `RadioField`, `AsyncSelectField`, `AsyncOutletDropdown`).
2. **NO HARDCODED PAGE LAYOUTS:** ALWAYS use `<Container>` (`#header`, default slot, `#footer`).
3. **PRECISE PROPS:** Form components use `v-model`, `label`, `placeholder`, and `feedback` (for validation errors). Do NOT manually bind `is-invalid` to form components.
4. **NO TAILWIND CLUTTER:** Extract repeated class groups (5+ classes) to `@utility` in `resources/css/app.css`.
5. **GOLDEN REFERENCE:** Study `resources/js/Pages/Inventory/Adjustment/Index.vue` as the architectural standard.

## 2. Component Structure (Composition API `<script setup>`)
- **Ordering:** `<template>` first, then `<script setup>`.
- **Import Order:** 1. Vue core (`ref`, `computed`) → 2. Inertia (`router`, `useForm`) → 3. Third-party (`lodash`, `FontAwesomeIcon`) → 4. Global components (`@/Components/`) → 5. Stores/Composables → 6. Local components (`./Components/`).
- **Script Setup Order:** `defineOptions` → `defineProps`/`defineEmits` → Stores/Composables → Reactive state (`ref`, `reactive`) → `computed` → Methods → Watchers → Lifecycle hooks.
- **Props Style:** `defineProps({ user: Object })` (template only) or `const props = defineProps({ user: Object })` (accessed in script). Do NOT destructure props.

## 3. Architecture & Folder Organization
- Place page components in `Pages/{Module}/Components/` and tab components in `Pages/{Module}/Tabs/`. Keep files under 500 lines.
- **Standard Index:** `<Container>` wrapper → `#header` slot (`ContainerHeader`, Actions, `Filter`) → Default slot (`Table` with `:headers`, `:data`, `:sort`, `:sortDirection`, `:action="true"`, `#actions`) → `#footer` slot (`Pagination` with `per_page: 20`).

## 4. PopUpPage & Detail Loading Pattern
- **PopUpPage (Side Panel):** For edit/create forms & entity details (`:show="show"`).
- **Modal / ModalDelete (Centered):** For confirmation warnings only.
- **Axios Detail Fetching:** Fetch details using `axios.get()` instead of Inertia partial reloads. Map response to `ref`, then set `showDetail.value = true`.
- **Form Lifecycle (`useForm`):** Watch `props.item` (`{ immediate: true }`), call `form.reset()`, populate fields. Submissions (`form.post()`, `form.put()`) must use `{ preserveState: true, preserveScroll: true }`. On `onSuccess`, `form.reset()` and emit `close`.

## 5. UI Components & Formatting Rules
- **Styling:** Theme colors (`main`, `secondary`, `success`, `danger`, `warning`, `info`). Spacing scale 4 (`gap-4`, `p-4`). **No padding/margin inside `PopUpPage` body.**
- **Buttons:** `btn` (mandatory), variants (`btn-main`, `btn-outline-main`, `btn-highlight-main`, `btn-sm`), icons via `<FontAwesomeIcon :icon="..." />`.
- **Form Inputs:** `DropdownField` must have `placeholder` (generates empty option). `NumberField` for numbers.
- **Quantity Display (`HasQuantityFormatter`):** ALWAYS display quantities using backend trait fields (`item.qty_formatted`, `item.qty_received_formatted`). NEVER format raw quantities manually on frontend.
- **Widgets:** `Widget.vue`, `WidgetProgress.vue`, `WidgetChart.vue`.
- **Partial Loading:** ALWAYS show skeleton loaders, spinners, or `"Memuat..."` text (`animate-pulse bg-gray-200 rounded`). Never leave blank UI during data fetch.

## 6. Table Filter Pattern
- **Layout:** `flex items-center gap-2`, `<FilterSearch>`, Filter button (`faSliders`) to open `<FilterModal>`, active filters via `<FilterBadge>`.
- **Workflow:** Init `filterForm` from `props.filters`, manage `tempFilters` inside modal, 500ms debounced watcher on `filterForm.search` calling `updateQuery()`.
- **`updateQuery`:** Merge `route().params` and filters, strip empty strings (`''` → `undefined`), reset page to 1, fire `router.get(location.pathname, query, { preserveState: true, preserveScroll: true })`.

## 7. State Management & Auth Composables
- **Auth (`useAuth`):** Import from `@/Composable/useAuth`. Use `user`, `business`, `outlets`, `roles`, `permissions`, `isOwner`, `can`, `canAny`, `canAll`, `hasRole`, `hasAnyRole`. Do NOT access `usePage().props.auth` directly.
- **Pinia Stores (`resources/js/store/`):**
  - Notification/Delete Modal: `useModalStore` from `@/store/notification` (`openModalDelete`, `openModalSoftDelete`).
  - Sidebar: `useAppStore` from `@/store/app` (controls sidebar visibility and active states).

