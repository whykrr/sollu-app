---
trigger: always_on
---

# Wajib Perhatikan: Standar Penggunaan Komponen UI Sollu App

Saat membuat atau mengedit komponen UI di `resources/js/Components`, Anda **WAJIB** mematuhi standar dari skill `sollu`:

## 1. Aturan Penggunaan Komponen Formulir (`@/Components/Form/`)
- **DILARANG MENGGUNAKAN RAW HTML FORM:** Semua input formulir wajib menggunakan komponen dari `@/Components/Form/` (`TextField`, `TextareaField`, `DropdownField`, `NumberField`, `Switch`, `CheckboxField`, `RadioField`, `SelectionGroupField`, `AsyncSelectField`, `AsyncOutletDropdown`).
- **Binding & Props:** Gunakan `v-model` untuk binding data. Sertakan prop `label`, `placeholder`, dan `feedback` (pesan error validasi dari Inertia `form.errors.field`).
- **Form Spacing:** Spacing antar-input formulir DILARANG melebihi scale 2 (`space-y-2`, `gap-2`).

## 2. Aturan Layout Utama & Side Drawer (`@/Components/UI/`)
- **`<MainPage>`:** Wajib digunakan sebagai wrapper halaman utama/index dengan slot `#header`, default slot (konten tabel/halaman), dan slot `#footer`.
- **`<PopUpPage>` / `usePopUpStore()`:** Wajib digunakan untuk drawer panel sebelah kanan pada halaman formulir *Create*, *Edit*, *Detail*, dan *Sub-page*. Dilarang menggunakan *full page redirect* (`router.get()`) untuk formulir sub-halaman.
  - **Cara Penggunaan Global:** Import `usePopUpStore` dari `@/store/popup`. Panggil `popUpStore.open({ component: YourComponent, props: { ... }, events: { ... } })`.
  - **DILARANG BUNGKUS DENGAN `<PopUpPage>`:** Komponen Vue dinamis (`YourComponent`) **TIDAK PERLU** dibungkus `<PopUpPage>` di templatenya, cukup *div content* biasa (sebab `PopUpContainer` global sudah merendernya).
- **Sticky Footer Teleport:** Kirim tombol aksi di komponen PopUpPage ke footer sticky drawer menggunakan `<Teleport v-if="isMounted" to="#popUpFooter">`. Pastikan variabel `const isMounted = ref(false)` di-set `true` saat `onMounted`.

## 3. Aturan Modal Konfirmasi (`@/Components/Notifications/Modal.vue` / `useModalStore()`)
- **`<Modal>`:** STRICTLY hanya digunakan untuk dialog konfirmasi singkat (contoh: Konfirmasi Hapus Data, Archive, Alert Peringatan).

## 4. Verifikasi Visual & Fungsional (Mandatori)
- Setiap penambahan atau modifikasi komponen UI WAJIB diverifikasi secara visual dan fungsional menggunakan MCP Web `browsermcp` (navigasi URL, screenshot, DOM snapshot, dan inspeksi console logs via skill `sollu`).
- Jalankan `npm run fix:eslint` dan `npm run build` sebelum menyelesaikan tugas.
