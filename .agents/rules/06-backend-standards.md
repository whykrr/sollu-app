---
trigger: always_on
---

# Rule 06: Standar Kode Backend & Rekayasa Laravel

## 1. Standar Bahasa PHP 8.3

- **Blok Kontrol:** Selalu gunakan kurung kurawal `{}` untuk seluruh struktur kontrol (`if`, `foreach`, `while`), bahkan untuk statement satu baris.
- **Constructor Property Promotion:** Gunakan fitur promosi properti PHP 8:
  ```php
  public function __construct(
      protected readonly InventoryCostingService $costingService,
      protected readonly NotificationDispatcherService $dispatcher,
  ) {}
  ```
  Dilarang membiarkan method `__construct()` tanpa parameter kecuali dideklarasikan `private`.
- **Strict Return Types & Type Hints:** Wajib menyertakan tipe data eksplisit pada seluruh parameter method dan return type:
  ```php
  public function isAccessible(User $user, ?string $path = null): bool
  ```
- **Dokumentasi PHPDoc:** Utamakan PHPDoc blocks untuk array kompleks atau spesifikasi DTO dengan *array shape definitions*:
  ```php
  /**
   * @param array{business_id: string, outlet_id: string, items: array<int, array{item_id: string, qty: float}>} $payload
   */
  ```

---

## 2. Standar Arsitektur Laravel 11

- **Eloquent Model Casts:** Deklarasi type casting pada Model **WAJIB** menggunakan method `casts(): array` bawaan Laravel 11, bukan properti `$casts`:
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
- **Eager Loading Limitation:** Batasi eager loading relasi secara native tanpa package eksternal:
  ```php
  $query->latest()->limit(10);
  ```
- **Modifikasi Kolom Migration:** Saat memodifikasi kolom tabel yang sudah ada, migration wajib menyertakan seluruh atribut kolom sebelumnya agar tidak terhapus.
- **Konfigurasi Tanpa Kernel:**
  - Middleware didaftarkan secara deklaratif di `bootstrap/app.php` melalui `Application::configure()->withMiddleware()`.
  - Exception handling dikonfigurasi di `bootstrap/app.php` via `->withExceptions()`.
  - Scheduled tasks dikonfigurasi di `routes/console.php`. Command pada `app/Console/Commands/` terdaftar otomatis.

---

## 3. Multiline Method Chaining

Pemanggilan berantai lebih dari 1 method (pada Eloquent Query Builder, Collection, atau Fluent Interface) **WAJIB dipecah ke baris baru (satu method per baris)** untuk menjaga keterbacaan kode secara vertikal:

```php
// ✅ BENAR (Multiline Chaining)
$orders = Order::query()
    ->where('business_id', $businessId)
    ->where('status', OrderStatus::Completed)
    ->with(['items.product', 'customer'])
    ->orderByDesc('created_at')
    ->paginate(20);

// ❌ SALAH (Horizontal Code Bloat)
$orders = Order::query()->where('business_id', $businessId)->where('status', OrderStatus::Completed)->with(['items.product', 'customer'])->orderByDesc('created_at')->paginate(20);
```

Aturan ini dikunci secara otomatis oleh ruleset `pint.json` (`method_chaining_indentation: true`).

---

## 4. Standar APIs & Eloquent Resources

- **Format Penamaan:** Seluruh payload JSON API menggunakan `snake_case`.
- **Pure HTTP Status Codes:** Gunakan kode status HTTP murni dan konsisten (`200 OK`, `201 Created`, `204 No Content`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `422 Unprocessable Entity`, `500 Server Error`).
- **Strict Numeric Casting:** Angka desimal, harga, dan kuantitas **WAJIB** di-cast ke `(float)` atau `(int)` dalam API Resource untuk mencegah nilai numerik terkirim sebagai string ke client frontend:
  ```php
  'price' => (float) $this->price,
  'current_stock' => (float) $this->current_stock,
  ```
- **Response Constants:** Gunakan `App\Constants\ResourceMessage` untuk seluruh pesan toast dan respons mutasi data (`ResourceMessage::CREATE_SUCCESS`, `UPDATE_SUCCESS`, `DELETE_SUCCESS`, `RESTORE_SUCCESS`).
- **Pencegahan Over-Fetching:** Dilarang me-load relasi berat pada controller `index()`. Data detail lengkap wajib dimuat secara *on-demand* via controller `show()`.

---

## 5. Code Formatting & Linters

- **PHP Formatter (Laravel Pint):**
  - Standar tunggal berbasis `pint.json`.
  - Jalankan `vendor/bin/pint --dirty` sebelum melakukan commit.
  - Jangan gunakan `pint --test`; langsung jalankan `vendor/bin/pint` untuk memperbaiki format secara otomatis.
- **Frontend Formatter & Linter:**
  - **Prettier:** `tabWidth: 4`, `useTabs: false`, `singleQuote: true`, `semi: false` (tanpa semicolon), `printWidth: 100`.
  - **ESLint:** Fokus pada validasi vue script setup, unused variables, dan syntax bugs (`npm run lint`).
