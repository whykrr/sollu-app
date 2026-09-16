---
trigger: always_on
---

# Wajib Perhatikan: Standar Form Request

Saat bekerja di `app/Http/Requests`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Base Class:** Semua Form Request wajib menginduk ke `App\Http\Requests\BaseInertiaFormRequest`.
2. **Otorisasi Permission:** Method `authorize()` wajib mengembalikan cek permission spesifik (contoh: `return $user->can('permission.name');`).
3. **Format Rules:** Rule validasi disajikan sebagai array dengan panah `=>` sejajar.
