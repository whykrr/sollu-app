---
trigger: always_on
---

# Wajib Perhatikan: Standar Controller Backend

Saat bekerja di `app/Http/Controllers`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Thin Controller Pattern:** CRUD sederhana dapat ditulis inline, logika kompleks wajib di-offload ke Service Class via Dependency Injection.
2. **Authorization:** Gunakan `$this->authorize('permission.name')` atau `Gate::authorize()`. Dilarang menggunakan middleware di `__construct()`.
3. **Response Constants:** Dilarang menggunakan string pesan respon manual/hardcoded. Gunakan `App\Constants\ResourceMessage::*` dan `App\Constants\FlashDataVariable::*`.
4. **Dokumentasi API:** Jika menambahkan/memodifikasi endpoint atau struktur JSON response, wajib perbarui file dokumentasi di `docs/`.
5. **Formatting:** Wajib jalankan `vendor/bin/pint` sebelum menyelesaikan tugas.
6. **Error Investigation:** Jika Controller mengalami error 500 saat diuji, segera periksa penyebabnya dengan tool MCP `laravel-boost` (`LastError`).
