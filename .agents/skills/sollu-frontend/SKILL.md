---
name: sollu-frontend
description: >-
  Frontend UI development standards for Sollu App (Vue 3 Composition API, Inertia.js 1.2, Tailwind CSS v4).
  MUST trigger whenever creating or modifying Vue components, Inertia page layouts (MainPage), custom form fields (@/Components/Form/),
  form field spacing (max scale-2), PopUpPage side drawers (usePopUpStore), center modal dialogs (useModalStore),
  Teleport footers (#popUpFooter), or table filter patterns.
---

# Sollu Frontend Rules (Vue 3 / Inertia / Tailwind v4)

Standard pengembangan antarmuka (UI) Sollu App berbasis Vue 3 (Composition API `<script setup>`), Inertia.js 1.2, dan Tailwind CSS v4.

## 1. 🚨 Anti-Hallucination Core Rules

1. **NO RAW HTML FORMS:** Selalu gunakan komponen `@/Components/Form/` (`TextField`, `TextareaField`, `DropdownField`, `NumberField`, `Switch`, `CheckboxField`, `RadioField`, `SelectionGroupField`, `AsyncSelectField`, `AsyncOutletDropdown`).
2. **NO HARDCODED PAGE LAYOUTS:** Selalu gunakan `<MainPage>` (`#header`, default slot, `#footer`).
3. **PRECISE PROPS:** Komponen form menggunakan `v-model`, `label`, `placeholder`, dan `feedback` (pesan error validasi). Dilarang mengikat `is-invalid` secara manual.
4. **NO TAILWIND CLUTTER:** Ekstrak kelompok class berulang (5+ class) ke `@utility` di `resources/css/app.css`.
5. **MANDATORY POPUPPAGE FOR SUB-PAGES & FORMS:** Seluruh alur kerja *Create*, *Edit*, *Detail*, dan *Sub-page* WAJIB menggunakan `<PopUpPage>` (side-panel drawer) atau `usePopUpStore()`. DILARANG menggunakan *full page redirect* (`router.get()`) untuk formulir sub-halaman.
6. **FORM SPACING LIMIT (MAX SCALE 2):** Jarak antar-input formulir (vertikal maupun horizontal) DILARANG melebihi scale 2 Tailwind (`space-y-2`, `space-x-2`, `gap-2`, `gap-y-2`, `gap-x-2`).
7. **ASYNC FETCH FOR SECONDARY & COMPLEX DETAILS:** Data detail kompleks (isi PopUpPage) dan data sekunder (opsi dropdown relasi) WAJIB diambil secara *async* via API internal (`axios`/`fetch`). Dilarang memuat relasi berat di props `index()` Inertia.
8. **MANDATORY BROWSERMCP UI VERIFICATION:** Setiap pembuatan atau perubahan komponen Vue/halaman Inertia WAJIB diverifikasi secara integrasi visual dan fungsional menggunakan `browsermcp` (navigasi URL, screenshot, snapshot DOM, dan inspeksi console logs via skill `sollu-integration-testing`). DILARANG menyatakan tugas frontend selesai tanpa verifikasi `browsermcp`.

## 2. Component Structure (`<script setup>`)

- **Ordering:** `<template>` terlebih dahulu, kemudian `<script setup>`.
- **Import Order:** 1. Vue core (`ref`, `computed`) → 2. Inertia (`router`, `useForm`) → 3. Third-party (`lodash`, `FontAwesomeIcon`) → 4. Global components (`@/Components/`) → 5. Stores/Composables → 6. Local components (`./Components/`).
- **Script Setup Order:** `defineOptions` → `defineProps`/`defineEmits` → Stores/Composables → Reactive state (`ref`, `reactive`) → `computed` → Methods → Watchers → Lifecycle hooks.

## 3. PopUpPage vs Modal (Distingsi Ketat)

- **`<PopUpPage>` / `usePopUpStore()` (Side Drawer Kanan):** WAJIB untuk formulir input, tampilan detail, sub-halaman, dan alur langkah berikutnya.
  - **Teleport Footer Pattern:** Komponen di dalam `PopUpPage` dapat menggunakan `<Teleport v-if="isMounted" to="#popUpFooter">` untuk mengirim tombol aksi langsung ke footer sticky `PopUpPage`.
- **`<Modal>` / `useModalStore()` (Center Dialog):** STRICTLY khusus untuk konfirmasi singkat (Hapus, Archive, Alert Peringatan).

## 4. UI Components & Formatting Standards

- **Quantity Display (`HasQuantityFormatter`):** Selalu tampilkan kuantitas dari properti trait backend (`item.qty_formatted`, `item.qty_received_formatted`). Dilarang memformat angka kuantitas secara manual di frontend.
- **Partial Loading & Skeleton:** Selalu sertakan skeleton loader / spinner / teks `"Memuat..."` (`animate-pulse bg-gray-200 rounded`) saat menunggu fetch data async. Jangan biarkan UI kosong tanpa indikator loading.

## 5. Table Filter Pattern

- **Layout:** `flex items-center gap-2`, `<FilterSearch>`, tombol Filter (`faSliders`) untuk membuka `<FilterModal>`, dan badge filter aktif via `<FilterBadge>`.
- **Workflow & Debouncing:** Inisialisasi `filterForm` dari `props.filters`, watcher 500ms debounce pada `filterForm.search` yang memanggil `updateQuery()`.
- **`updateQuery`:** Merge `route().params` dengan filter aktif, konversi string kosong `''` menjadi `undefined`, reset `page: 1`, lalu panggil `router.get(location.pathname, query, { preserveState: true, preserveScroll: true })`.

## 6. SelectionGroupField (`@/Components/Form/SelectionGroupField.vue`)

Gunakan `SelectionGroupField` untuk grup tombol opsi pilihan (mendukung seleksi tunggal dan ganda):

```vue
<!-- Single Select (Radio Button Style) -->
<SelectionGroupField
    v-model="form.gender"
    label="Jenis Kelamin"
    :options="[{ value: 'male', label: 'Laki-laki' }, { value: 'female', label: 'Perempuan' }]"
/>

<!-- Multi Select (Checkbox Button Style with Select All) -->
<SelectionGroupField
    v-model="form.outlets"
    label="Pilih Outlet"
    :options="outlets"
    multiple
    show-select-all
/>
```

