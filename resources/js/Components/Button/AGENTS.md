---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Tombol Aksi `@/Components/Button/`

Gunakan komponen tombol bersama dari `@/Components/Button/` untuk aksi terstandarisasi:

---

## 1. Komponen Tombol Tersedia

### A. `<ButtonBack>`

Tombol batal/kembali dengan riwayat browser (`router.back()`) dan fallback ke rute overview.

```vue
<ButtonBack />
```

### B. `<ButtonGroupArchive>`

Kelompok tombol aksi berbasis teks yang menangani siklus hidup Soft Delete:

- Menampilkan tombol **Hapus** (`modal.openModalSoftDelete`) jika data aktif (`deleted_at === null`).
- Menampilkan tombol **Pulihkan** (`PUT` request) jika data di tong sampah (`deleted_at !== null`).
- Menampilkan tombol **Hapus Permanen** (`modal.openModalDelete`) jika data di tong sampah.

```vue
<ButtonGroupArchive
    :data="row"
    :urlArchive="route('products.destroy', row.id)"
    :urlRestore="route('products.restore', row.id)"
    :urlDelete="route('products.force-delete', row.id)"
/>
```

### C. `<ButtonIconGroupArchive>`

Versi tombol ikon ringkas dari `ButtonGroupArchive` untuk digunakan pada kolom tabel sempit.

```vue
<ButtonIconGroupArchive
    :data="row"
    :urlArchive="route('products.destroy', row.id)"
    :urlRestore="route('products.restore', row.id)"
    :urlDelete="route('products.force-delete', row.id)"
/>
```

---

## 2. Standar Utility Class & Ukuran Button (`app.css`)

Semua elemen `<button>` atau tautan tombol styling wajib menggunakan kelas dasar `.btn` dipadukan dengan modifier ukuran dan varian warna:

### A. Hierarki Ukuran (`Button Sizes`)

| Kelas                | Padding       | Gap     | Font Size          | Penggunaan / Konteks Rekomendasi                                          |
| :------------------- | :------------ | :------ | :----------------- | :------------------------------------------------------------------------ |
| **`.btn-xs`**        | `px-2 py-1`   | `gap-1` | `text-xs` (12px)   | Aksi tabel padat/sempit, badge toggle kecil, nested drawer action.        |
| **`.btn-sm`**        | `px-2 py-1.5` | `gap-1` | `text-xs` (12px)   | Standar aksi header (`MainPageHeader`), filter toolbar, row action biasa. |
| **`.btn`** (Regular) | `px-4 py-2`   | `gap-2` | `text-sm` (14px)   | Form submit, modal confirmation footer, tombol aksi utama halaman.        |
| **`.btn-lg`**        | `px-6 py-3`   | `gap-2` | `text-base` (16px) | Hero banner, POS primary checkout button, call-to-action besar.           |

### B. Varian Gaya Tombol

- **Solid:** `.btn .btn-main`, `.btn-secondary`, `.btn-success`, `.btn-danger`, `.btn-warning`, `.btn-info`
- **Outline:** `.btn .btn-outline-main`, `.btn-outline-danger`, `.btn-outline-secondary`
- **Highlight (Soft Tint):** `.btn .btn-highlight-main`, `.btn-highlight-success`, `.btn-highlight-danger`
- **Flat (Bordered Neutral):** `.btn .btn-flat` (Border abu-abu halus, hover background tipis)
