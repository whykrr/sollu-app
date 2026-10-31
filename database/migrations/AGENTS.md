---
trigger: always_on
---

# Wajib Perhatikan: Standar Database Migrations, Indexing & Immutabilitas Berbasis Git Tag

Saat bekerja di `database/migrations`, Anda **WAJIB** menerapkan standar dari Rule 08 dan skill `sollu`:

1. **Git Tag Production Baseline & Immutabilitas Mutlak:**
   - Parameter produksi ditentukan oleh Git Tag rilis stabil (misal: `v1.2.3`).
   - File migrasi yang sudah masuk ke dalam Git Tag rilis stabil terakhir **MUTLAK TERKUNCI / IMMUTABLE**. Dilarang keras mengedit, merename, atau menghapusnya.
   - Periksa status integritas tag secara berkala menggunakan tool MCP `sollu-project`: `audit_migration_compatibility`.
2. **Mandat Pembuatan File Migrasi Baru (No In-Place Alters):**
   - Setiap perubahan skema pada tabel yang sudah dirilis di produksi (tambah kolom, ubah tipe, tambah index, drop kolom) **WAJIB dibuatkan file migrasi baru** (`php artisan make:migration <action>_to_<table_name> --table=<table_name>`).
3. **Backward Compatibility & Non-Breaking Additions:**
   - Kolom baru pada tabel aktif produksi **WAJIB bersifat `nullable()` atau memiliki nilai `default(...)`** agar tidak memutus query insert dari aplikasi atau worker produksi yang sedang berjalan.
   - Patuhi pola *Expand and Contract* untuk deprecasi kolom lama.
4. **Mandatory Live Database Verification:** Sebelum menulis migration baru atau mengubah tabel, **WAJIB** gunakan tool MCP `laravel-boost` (`database-schema`) untuk memeriksa skema nyata database (`information_schema`, kolom, dan indeks yang sudah ada).
5. **Indexing Kolom Relasi & Tenant:** Seluruh kolom Foreign Key dan isolasi tenant (contoh: `business_id`, `outlet_id`, `user_id`, `{entity}_id`) **WAJIB** memiliki index (`$table->index('outlet_id')` atau `$table->foreignUuid('outlet_id')->index()`).
6. **Indexing Kolom Filter & Sorting:** Kolom yang sering digunakan dalam klausa `where`, `orderBy`, atau pencarian (contoh: `status`, `type`, `date`, `transaction_date`, `created_at`, `code`, `sku`) **WAJIB** ditambahkan index (`$table->index(...)`).
7. **Composite Index untuk Query Berpasangan:** Untuk query yang sering memfilter kombinasi tenant dan status/tanggal bersamaan, buatkan composite index yang efisien (contoh: `$table->index(['business_id', 'status'])` atau `$table->index(['outlet_id', 'created_at'])`).
8. **Simetri Rollback Mutlak (`down()` LIFO):** Setiap operasi di `up()` harus memiliki kebalikan presisi di `down()`. Selalu uji:
   ```bash
   php artisan migrate && php artisan migrate:rollback --step=1 && php artisan migrate
   ```
9. **Driver Guarding SQLite:** PostgreSQL-specific DDL (misal `$table->fullText(...)`, GIN/GiST) wajib dibungkus `if (DB::getDriverName() !== 'sqlite')` pada `up()` dan `down()`.
