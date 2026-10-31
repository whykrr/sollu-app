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
- **Keamanan Deserialisasi Boolean (Anti-Trap Native `(bool)`):**
  - DILARANG KERAS menggunakan cast native `(bool)` pada nilai pengaturan yang bersumber dari database dinamis, cache string, atau payload JSON mentah (`$setting->value`). Di PHP, string non-kosong apapun (termasuk `"false"` dan `"0"`) dievaluasi menjadi `true` saat di-cast dengan `(bool)`.
  - WAJIB gunakan `filter_var($value, FILTER_VALIDATE_BOOLEAN)` untuk memastikan konversi boolean yang aman dan akurat:
    ```php
    // BENAR
    $isEnabled = filter_var($setting->value ?? false, FILTER_VALIDATE_BOOLEAN);

    // SALAH (BUG: (bool) "false" menghasilkan true)
    $isEnabled = (bool) ($setting->value ?? false);
    ```
- **Keamanan Penggabungan Koleksi Berindeks (Anti-Trap Eloquent `Collection::merge()`):**
  - DILARANG KERAS memanggil `$collectionA->keyBy('key')->merge($collectionB->keyBy('key'))` ketika integritas string key / UUID diperlukan untuk idempotensi atau lookup dictionary.
  - Di Laravel, `Collection::merge()` memanggil `array_merge()` di balik layar, yang secara otomatis me-reset array numerik atau string angka menjadi indeks integer berurut `0, 1, 2...`, sehingga dictionary lookup menjadi `null` dan memicu duplikasi atau SQL `UniqueConstraintViolationException`.
  - WAJIB gunakan perulangan eksplisit `$map->put((string) $key, $item)` atau `$collectionA->replace($collectionB)` untuk menjamin key asosiatif tidak ter-reset.

---

## 2. Standar Laravel 12
- **UUIDv7 Default:** Semua pembentukan Primary Key (via `HasUuids`) dan fungsi `Str::uuid()` di Laravel 12 harus menghasilkan UUIDv7. Hal ini meningkatkan efisiensi indexing.
- **Larangan SVG (Keamanan):** Sistem *secara mutlak* menolak *upload* gambar berekstensi `.svg` karena rentan terhadap serangan XSS. Laravel 12 secara *default* sudah memblokirnya pada *rule* `'image'`. Pastikan tidak ada celah form request yang menggunakan *rule* manual yang melegalkan SVG.
- **Model Type Casts:** Wajib menggunakan method `casts(): array` bawaan Laravel, bukan properti `$casts`:
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
- **Larangan `parent::toArray()` pada JsonResource:** Eloquent Resource WAJIB mendefinisikan array bentuk DTO terstruktur eksplisit dengan tipe data pasti (strict types). DILARANG KERAS menggunakan `parent::toArray($request)` karena me-dump seluruh atribut mentah model, internal relational state, dan relasi bersarang yang memboroskan bandwidth, RAM server, serta berisiko membocorkan data.
- **Response Message Constants:** Gunakan `App\Constants\ResourceMessage` (`CREATE_SUCCESS`, `UPDATE_SUCCESS`, `DELETE_SUCCESS`, `RESTORE_SUCCESS`).
- **Thin Controller:** Controller di `app/Http/Controllers/App/` hanya menerima HTTP request, otorisasi, memanggil Domain Service, dan render/return response. Logika bisnis kompleks dilarang di controller.

---

## 4. Standar Versioning API Endpoint & AI Consultation Gate
- **Format URI:** Seluruh rute API publik wajib menggunakan URI Path Versioning: `/v{MAJOR}/{MODULE}/{RESOURCE}` (misal: `/v1/pos/transactions`).
- **Header Respons Wajib:** Setiap response API wajib menyertakan header `X-API-Version: v{MAJOR}` (misal: `X-API-Version: v1`).
- **Aturan Mutlak Hermetic Versioning:**
  - Saat merilis versi baru (misal `v2`), **SELURUH** Controller dan Form Request untuk modul terkait **WAJIB** naik tingkat ke namespace versi baru (`App\Http\Controllers\API\V2\...` dan `App\Http\Requests\API\V2\...`), meskipun kodenya 100% identik.
  - Rute versi baru secara mutlak dilarang mengimpor atau mereferensikan controller/request dari versi sebelumnya (`V1`).
  - Gunakan pendekatan **Hermetic Snapshot (Class Mandiri)** yang mendelegasikan ke Domain Service (Rule Thin Controller).
- **Standar Deprecation RFC 8594:** Endpoint versi lama yang di-deprecate wajib menyertakan header `Deprecation: true`, `Sunset`, `Link`, dan `X-API-Deprecation-Warning`.
- **AI Consultation Gate (Wajib Konfirmasi User Sebelum Naik Versi):**
  - AI dilarang keras menaikkan versi API atau memaksakan *breaking change* secara sepihak tanpa persetujuan pengguna.
  - Jika terindikasi *breaking change* (penambahan field `required` baru, perubahan tipe data, hapus/rename field, atau ubah format response envelope), AI **WAJIB berhenti** dan menanyakan persetujuan user dengan memaparkan opsi: (A) Naik versi dengan Hermetic Versioning, atau (B) Redesain agar tetap backward-compatible di versi aktif.

---

## 5. Pemberantasan N+1 Query & Standar Kueri Teroptimasi (Zero N+1 Invariant)
- **Larangan Relasi di `$appends` Model (Hidden N+1 Serialization Invariant):**
  DILARANG KERAS menyematkan accessor yang mengakses relasi Eloquent (terutama relasi bersarang) ke dalam properti `$appends` pada Model. Hal ini memicu *hidden N+1 queries* setiap kali model diserialisasi (Inertia pagination, `toArray()`, `json_encode()`). Accessor boleh tetap ada pada model, tetapi pemanggilannya wajib eksplisit di API Resource / DTO atau setelah relasi terkait dipastikan di-eager-load.
- **Larangan Kueri dalam Loop PHP:** DILARANG KERAS memanggil kueri database (Eloquent/DB Builder seperti `where()->exists()`, `find()`, `first()`) di dalam loop PHP (`foreach`, `filter()`, `map()`, `each()`).
- **In-Memory Preloading & Bulk Mutation pada Operasi Matriks / Impor:**
  Pada operasi mutasi multi-item, kalkulasi bersarang, atau job impor batch (Excel/CSV), DILARANG menjalankan query `where()->first()`, `firstOrCreate()`, atau `->each()` di dalam loop iterasi data. Wajib melakukan preloading data yang ada ke dalam memory collection berindeks (`keyBy()`, `pluck()`) dan menjalankan mutasi bulk (`whereIn()->update()`).
- **Memoization pada Observer Per-Record:**
  Observer yang mentrigger sinyal/nudge per record wajib mem-memoize query lookup induk/outlet pada static array lokal per siklus request untuk mencegah query berulang saat mutasi data dalam jumlah besar.
- **Pendelegasian ke SQL Set-Based:** Seluruh pengecekan keterkaitan relasi atau riwayat mutasi WAJIB didelegasikan ke level SQL database menggunakan:
  - `whereDoesntHave('relation', fn($q) => ...)` atau `whereHas(...)`
  - `withExists(['relation as alias' => fn($q) => ...])`
  - Scoped Eager Loading: `with(['relation' => fn($q) => $q->where(...)])`
- **Kompleksitas Query O(1):** Endpoint daftar/pencarian item harus terbukti mengeksekusi jumlah kueri konstan terlepas dari apakah data yang diproses berjumlah 10, 40, atau 100 baris.
- **Verifikasi Kinerja Kueri:** Untuk kueri data terfilter atau kalkulatif, sertakan automated test dengan `DB::enableQueryLog()` untuk memastikan query count tetap bounded (O(1)).
- **Standar POS Offline-First Sync & Anti-DLQ Invariant (Force-Accept):**
  - Transaksi penjualan offline yang dikirimkan oleh kasir POS (melalui endpoint batch maupun single) **DILARANG KERAS DIGAGALKAN** (HTTP 422/500 Dead-Letter-Queue) hanya karena stok cloud habis atau bernilai 0 akibat keterlambatan sinkronisasi.
  - Penjualan fisik telah terjadi di gerai nyata; menolak transaksi offline merusak antrean kasir, menghilangkan uang/transaksi fisik, dan menimbulkan inkonsistensi kasir lokal vs cloud.
  - Backend WAJIB melakukan **Force-Accept (HTTP 200/201)**:
    - Potong saldo inventori (diizinkan saldo minus sementara).
    - Catat variansi / selisih stok ke log audit dan kirimkan notifikasi audit inventori ke supervisor secara asinkron.

---

## 6. Code Formatter (Laravel Pint)
- Jalankan `vendor/bin/pint --dirty` sebelum melakukan commit untuk memastikan format kode PHP bersih dan seragam.
