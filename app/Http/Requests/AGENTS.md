---
trigger: always_on
---

# Wajib Perhatikan: Standar Form Request

Saat bekerja di `app/Http/Requests`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Base Class:** Semua Form Request wajib menginduk ke `App\Http\Requests\BaseInertiaFormRequest`.
2. **Otorisasi Permission:** Method `authorize()` wajib mengembalikan cek permission spesifik (contoh: `return $user->can('permission.name');`).
3. **Format Rules:** Rule validasi disajikan sebagai array dengan panah `=>` sejajar.
4. **Validasi Query Table (`Get...Request`):** Pada Form Request untuk mengambil list/tabel (`Get{Entity}Request`), wajib sertakan validasi untuk query parameters tabel: `'sort' => ['nullable', 'string']`, `'direction' => ['nullable', 'in:asc,desc']`, `'search' => ['nullable', 'string']`, dan `'perpage' => ['nullable', 'integer']`.
