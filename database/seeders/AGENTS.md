---
trigger: always_on
---

# Wajib Perhatikan: Standar RBAC & Database Seeders

Saat bekerja di `database/seeders`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Permission Registration:** Tambahkan key permission baru di Enum `app/Enums/PermissionEnum.php` terlebih dahulu (menggunakan dot-notation).
2. **Assign Role:** Daftarkan permission baru ke role yang sesuai pada `database/seeders/Production/RolePermissionSeeder.php`.
3. **Execution:** Jalankan seeder via `php artisan db:seed --class="Database\Seeders\Production\RolePermissionSeeder"` untuk memperbarui izin di database.
