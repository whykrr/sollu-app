---
trigger: always_on
---

# Wajib Perhatikan: Standar Unit Testing (Layer 1)

Saat bekerja di `tests/Unit`, Anda **WAJIB** menerapkan standar berikut:

1. **Pure Isolated Logic:** Unit testing difokuskan khusus untuk logika terisolasi murni (kalkulasi diskon, pajak, harga, HPP Moving Average/FIFO murni, helper fungsi, integritas PHP Enums, dan DTO/Value Objects).
2. **Zero Database Dependency:** DILARANG menyentuh database (tanpa `RefreshDatabase` atau I/O database) pada `tests/Unit/`. DILARANG me-mocking rantai query Eloquent yang rumit. Jika class membutuhkan interaksi database atau Model Eloquent, tempatkan pengujiannya di `tests/Integration/` atau `tests/Feature/`.
3. **Struktur & Penamaan:** Lokasi file test mencerminkan domain atau namespace class asli (misal: `App\Support\TaxCalculator` -> `tests/Unit/Support/TaxCalculatorTest.php`).
4. **Eksekusi Ultra Cepat:** Seluruh test suite `tests/Unit/` menggunakan `PHPUnit\Framework\TestCase` dan harus dapat dieksekusi dalam hitungan milidetik tanpa *framework boot overhead*.
5. **Open Question Alignment:** Selalu konfirmasi variasi batas (*edge cases* nilai nol, pembagian nol, desimal ekstrem) kepada stakeholder sebelum menyelesaikan suite test.
