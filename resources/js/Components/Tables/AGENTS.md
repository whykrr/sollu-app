---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Tabel & Filter Data `@/Components/Tables/`

Saat menggunakan atau mengedit komponen di `resources/js/Components/Tables`, Anda **WAJIB** mematuhi aturan berikut:

---

## 1. Wajib Menggunakan Komponen `<Table>` Bawaan Proyek
- **Larangan Raw HTML Table:** DILARANG keras menuliskan tag `<table>` mentah secara manual di file page/view. Seluruh data tabular WAJIB ditampilkan melalui `@/Components/Tables/Table.vue`.
- **Props Wajib `<Table>`:**
  - `headers` (Array): Definisi kolom `[{ label: 'Nama', field: 'name', sortable: true, slot: 'custom_slot' }]`.
  - `data` (Array): Array data baris (biasanya `items.data`).
  - `action` (Boolean): Jika `true`, kolom aksi di sisi kanan akan diaktifkan.

---

## 2. Empty State Terpusat (Di Level Komponen `<Table>`)
- **Penanganan Otomatis:** Komponen `<Table>` sudah secara native merender tampilan *empty state* ("data tidak ditemukan") ketika `data.length === 0`.
- **Larangan Duplikasi Manual:** DILARANG membuat blok `v-if="data.length === 0"` manual atau banner kosong di masing-masing page. Percayakan sepenuhnya kepada komponen `<Table>`.

---

## 3. Slotting Kustom pada `<Table>`
```vue
<Table :headers="headers" :data="products.data" :action="true">
    <!-- Kustomisasi cell kolom tertentu berdasarkan key header.slot -->
    <template #status="{ row }">
        <span class="badge" :class="$enums.ProductStatus._meta[row.status]?.color">
            {{ $enums.ProductStatus._meta[row.status]?.label }}
        </span>
    </template>

    <!-- Kustomisasi kolom aksi -->
    <template #actions="{ row }">
        <button class="btn btn-flat btn-sm" title="Edit" @click="openEdit(row)">
            <FontAwesomeIcon :icon="faPen" />
        </button>
    </template>
</Table>
```

---

## 4. Navigasi Halaman (`<Pagination>`)
- Diletakkan pada slot `<template #footer>` di dalam `<MainPage>`:
  ```vue
  <template #footer>
      <Pagination :meta="products.meta || products" />
  </template>
  ```

---

## 5. Live Search & Debounced Filter Sync
- Seluruh filter tabel dikelola dengan watcher ter-debounce (500ms):
  ```javascript
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
  ```
