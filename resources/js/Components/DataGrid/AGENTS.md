---
trigger: always_on
---

# Standar Komponen DataGrid (`@/Components/DataGrid/DataGrid.vue`)

Komponen resmi untuk menampilkan data dalam bentuk grid kartu responsif (_Card Grid View_), melengkapi `@/Components/Tables/Table.vue`.

---

## 1. Spesifikasi Props

| Prop           | Tipe     | Default                                                                                  | Keterangan                                                           |
| :------------- | :------- | :--------------------------------------------------------------------------------------- | :------------------------------------------------------------------- |
| `data`         | `Array`  | `[]`                                                                                     | **Wajib.** Array dataset yang akan dirender (misal `products.data`). |
| `gridClass`    | `String` | `'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5'` | Konfigurasi kolom Tailwind responsif.                                |
| `keyField`     | `String` | `'id'`                                                                                   | Kunci unik pada setiap objek baris data.                             |
| `emptyMessage` | `String` | `'data tidak ditemukan.'`                                                                | Teks yang tampil saat data kosong.                                   |

---

## 2. Event & Interaktivitas

- `@row-click="handleAction"`: Dipicu saat salah satu kartu diklik (kompatibel 100% dengan `<Table>`).
- `@item-click="handleAction"`: Alias event kartu diklik.

---

## 3. Contoh Penggunaan

```vue
<DataGrid :data="products.data" @row-click="openEdit">
    <template #default="{ row }">
        <ProductCard :product="row" @click="openEdit(row)" />
    </template>
</DataGrid>
```
