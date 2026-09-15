---
trigger: always_on
---

# Wajib Perhatikan: Standar Eloquent Model

Saat bekerja di `app/Models`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Mandatory Live Database Verification:** Sebelum/saat mengedit model, **WAJIB** gunakan tool MCP `sollu-db` (`information_schema`) atau `laravel-boost` (`DatabaseSchema`) untuk memeriksa skema nyata database. Jangan pernah mengira-ngira nama kolom atau tipe data.
2. **Casts Method (Laravel 11):** Gunakan method `casts(): array` (bukan `$casts` array property).
3. **Member Ordering:** Urutan member: Trait -> Properties -> `casts()` -> Relations -> Scopes -> Helpers.
4. **UUID:** Gunakan trait `HasUuids` jika tabel menggunakan UUID primary key.
5. **Optimal Query Scopes & Index Awareness:** Saat membuat scope query (`scopeFilters()`, dll.), pastikan kolom yang difilter/disortir telah terindeks di database dan gunakan seleksi kolom spesifik jika diperlukan.
6. **Model Verification with Tinker:** Manfaatkan tool MCP `laravel-boost` (`Tinker`) untuk memverifikasi fungsionalitas relasi, mutator, accessor, atau kalkulasi scope secara instan.

