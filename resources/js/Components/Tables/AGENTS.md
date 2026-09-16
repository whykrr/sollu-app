---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Tabel & Filter Data `@/Components/Tables/`

Saat menggunakan atau mengedit komponen di `resources/js/Components/Tables`, Anda **WAJIB** mematuhi aturan berikut:

---

## 1. Wajib Menggunakan Komponen `<Table>` Bawaan Proyek

- **Larangan Raw HTML Table:** DILARANG keras menuliskan tag `<table>` mentah secara manual di file page/view. Seluruh data tabular WAJIB ditampilkan melalui `@/Components/Tables/Table.vue`.
- **Struktur Props `<Table>`:**
    - `headers` (Array, required): Definisi kolom `[{ label: 'Nama', field: 'name', sortable: true, slot: 'custom_slot', show: 'md' }]`.
    - `data` (Array, required): Array data baris (biasanya `items.data`).
    - `sort` (String, optional): Key field yang sedang aktif diurutkan (biasanya `params?.sort ?? 'updated_at'`).
    - `sortDirection` (String, optional): Arah pengurutan aktif `'asc'` atau `'desc'` (biasanya `params?.direction ?? 'desc'`).
    - `action` (Boolean, optional): Jika `true`, kolom aksi di sisi kanan akan diaktifkan.

---

## 2. Fitur Sortable Table (Frontend & Backend Integration)

### A. Konfigurasi Header Kolom

Setiap kolom yang dapat diurutkan **WAJIB** menetapkan `sortable: true` dan menyertakan `field` yang cocok dengan nama kolom database / accessor model:

```javascript
const headers = [
    { label: 'Foto', field: 'image', slot: 'image', sortable: false },
    { label: 'Kode', field: 'code', slot: 'code', sortable: true },
    { label: 'Nama Produk', field: 'name', sortable: true },
    { label: 'Kategori', field: 'category_id', slot: 'category', sortable: false },
    { label: 'Dibuat Pada', field: 'created_at', slot: 'created_at', sortable: true, show: 'lg' },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
]
```

### B. Binding Props pada `<Table>`

Teruskan parameter sort aktif dari server (`params`):

```vue
<Table
    :headers="headers"
    :data="products.data"
    :sort="params?.sort ?? 'updated_at'"
    :sort-direction="params?.direction ?? 'desc'"
    :action="true"
>
    <!-- Slot kustom -->
</Table>
```

### C. Mekanisme Kerja Otomatis `Table.vue`

- Saat pengguna mengklik header dengan `sortable: true`, `Table.vue` otomatis melakukan toggle `asc` <-> `desc` dan menembakkan navigasi Inertia:
    ```javascript
    router.get(
        window.location.pathname,
        {
            ...route().params,
            page: 1,
            sort: sortKey.value,
            direction: sortOrder.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
    ```
- Indikator visual otomatis menampilkan ikon FontAwesome:
    - `faSort` (abu-abu/redup saat tidak aktif).
    - `faSortUp` / `faSortDown` (gelap/kontras saat kolom aktif diurutkan).

### D. Standar Backend (Model, Request, & Controller)

1. **Model:** Wajib menggunakan trait `App\Trait\SortableModel` dan mendeklarasikan properti `protected array $sortable = ['name', 'code', 'created_at', 'updated_at'];`.
2. **Form Request (`Get...Request`):** Wajib menyertakan rule `'sort' => ['nullable', 'string']` dan `'direction' => ['nullable', 'in:asc,desc']`.
3. **Controller (`index()`):** Wajib merantai `->sortable($request->validated('sort', 'updated_at'), $request->validated('direction', 'desc'))` dan meneruskan array `params` ke Inertia props.

---

## 3. Empty State Terpusat (Di Level Komponen `<Table>`)

- **Penanganan Otomatis:** Komponen `<Table>` sudah secara native merender tampilan _empty state_ ("data tidak ditemukan.") ketika `data.length === 0`.
- **Larangan Duplikasi Manual:** DILARANG membuat blok `v-if="data.length === 0"` manual atau banner kosong di masing-masing page. Percayakan sepenuhnya kepada komponen `<Table>`.

---

## 4. Slotting Kustom pada `<Table>`

```vue
<Table
    :headers="headers"
    :data="products.data"
    :sort="params?.sort ?? 'updated_at'"
    :sort-direction="params?.direction ?? 'desc'"
    :action="true"
>
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

## 5. Navigasi Halaman (`<Pagination>`)

- Diletakkan pada slot `<template #footer>` di dalam `<MainPage>`:
    ```vue
    <template #footer>
        <Pagination :meta="products.meta || products" />
    </template>
    ```

---

## 6. Live Search & Debounced Filter Sync

- Seluruh filter tabel dikelola dengan watcher ter-debounce (500ms):
    ```javascript
    const updateQuery = debounce(() => {
        router.get(
            location.pathname,
            {
                ...route().params,
                search: filterForm.search || undefined,
                status: filterForm.status || undefined,
                page: 1, // Reset ke halaman 1 saat filter berubah
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        )
    }, 500)
    ```
