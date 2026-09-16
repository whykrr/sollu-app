---
trigger: always_on
---

# Wajib Perhatikan: Standar Database Migrations & Indexing

Saat bekerja di `database/migrations`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Mandatory Live Database Verification:** Sebelum menulis migration baru atau mengubah tabel, **WAJIB** gunakan tool MCP `sollu-db` untuk memeriksa skema nyata database (`information_schema`, kolom, dan indeks yang sudah ada).
2. **Indexing Kolom Relasi & Tenant:** Seluruh kolom Foreign Key dan isolasi tenant (contoh: `business_id`, `outlet_id`, `user_id`, `{entity}_id`) **WAJIB** memiliki index (`$table->index('outlet_id')` atau `$table->foreignUuid('outlet_id')->index()`).
3. **Indexing Kolom Filter & Sorting:** Kolom yang sering digunakan dalam klausa `where`, `orderBy`, atau pencarian (contoh: `status`, `type`, `date`, `transaction_date`, `created_at`, `code`, `sku`) **WAJIB** ditambahkan index (`$table->index(...)`).
4. **Composite Index untuk Query Berpasangan:** Untuk query yang sering memfilter kombinasi tenant dan status/tanggal bersamaan, buatkan composite index yang efisien (contoh: `$table->index(['business_id', 'status'])` atau `$table->index(['outlet_id', 'created_at'])`).
5. **No Destructive Migration Alters:** Dilarang mengedit atau menghapus file migration lama yang sudah pernah dieksekusi. Setiap perubahan atau penambahan index/kolom baru WAJIB dibuatkan file migration baru (`php artisan make:migration ...`).
6. **Explicit Index Naming (Jika Perlu):** Gunakan penamaan index eksplisit jika membuat composite index pada tabel dengan nama panjang agar tidak melebihi batas panjang identifier database.
