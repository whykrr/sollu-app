# Rule 05: Standar Kode Backend & Rekayasa Laravel

## 1. Standar PHP 8.3 & Invarian Sintaks
- **Blok Kontrol:** Selalu gunakan kurung kurawal `{}` untuk seluruh struktur kontrol (`if`, `foreach`, `while`), bahkan untuk statement satu baris.
- **Constructor Property Promotion:** Gunakan `public function __construct(protected readonly Service $service) {}`. Dilarang method `__construct()` kosong tanpa parameter.
- **Strict Types:** Wajib deklarasi tipe parameter dan return type eksplisit di seluruh method:
  `public function isAccessible(User $user, ?string $path = null): bool`
- **Multiline Method Chaining:** Pemanggilan berantai $> 1$ method (Eloquent Query Builder / Collection) **WAJIB dipecah ke baris baru (satu method per baris)**:
  ```php
  $orders = Order::query()
      ->where('business_id', $businessId)
      ->where('status', OrderStatus::Completed)
      ->latest()
      ->paginate(20);
  ```

---

## 2. Standar Laravel 11
- **Model Type Casts:** Wajib menggunakan method `casts(): array` bawaan Laravel 11, bukan properti `$casts`:
  ```php
  protected function casts(): array
  {
      return [
          'status' => AdjustmentStatus::class,
          'total_amount' => 'decimal:2',
          'is_active' => 'boolean',
      ];
  }
  ```
- **Native Eager Loading Limit:** Batasi eager loading relasi tanpa package eksternal: `$query->latest()->limit(10);`.
- **Modifikasi Migration:** Saat ubah kolom yang sudah ada, migration wajib menyertakan seluruh atribut kolom sebelumnya.
- **Konfigurasi Tanpa Kernel:** Middleware & exception didaftarkan deklaratif di `bootstrap/app.php`.

---

## 3. APIs, Eloquent Resources & Thin Controller
- **Penamaan JSON:** Seluruh payload API menggunakan `snake_case`.
- **Strict Numeric Casting:** Angka desimal, harga, dan kuantitas WAJIB di-cast ke `(float)` atau `(int)` di API Resource agar tidak terkirim sebagai string ke frontend.
- **Response Message Constants:** Gunakan `App\Constants\ResourceMessage` (`CREATE_SUCCESS`, `UPDATE_SUCCESS`, `DELETE_SUCCESS`, `RESTORE_SUCCESS`).
- **Thin Controller:** Controller di `app/Http/Controllers/App/` hanya menerima HTTP request, otorisasi, memanggil Domain Service, dan render/return response. Logika bisnis kompleks dilarang di controller.

---

## 4. Code Formatter (Laravel Pint)
- Jalankan `vendor/bin/pint --dirty` sebelum melakukan commit untuk memastikan format kode PHP bersih dan seragam.
