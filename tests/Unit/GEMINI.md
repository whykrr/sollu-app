---
trigger: always_on
---

# Wajib Perhatikan: Standar Unit Testing Service Layer

Saat bekerja di `tests/Unit`, Anda **WAJIB** menerapkan standar dari skill `sollu` dan `laravel-expert`:

1. **Service Layer Testing Only:** Unit testing difokuskan khusus untuk Service Layer (`tests/Unit/Services/...`).
2. **100% Pure Isolation & In-Memory:** Dilarang keras menyentuh database fisik. Wajib gunakan `RefreshDatabase` dengan `sqlite:memory` dan Mockery untuk dependensi eksternal.
3. **Struktur & Penamaan:** Lokasi file test WAJIB menduplikasi struktur namespace Service asli (misal: `App\Services\App\Inventory\StockAdjustmentService` -> `tests/Unit/Services/App/Inventory/StockAdjustmentServiceTest.php`).
4. **100% Code Coverage:** Verifikasi coverage suite unit test dengan `vendor/bin/phpunit`.
