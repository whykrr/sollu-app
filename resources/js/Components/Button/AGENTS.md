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
