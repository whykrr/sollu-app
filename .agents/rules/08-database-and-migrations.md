# Rule 08: Standar Database Skema, Migrasi Reversibel, Immutabilitas Berbasis Git Tag & Protokol Rollback

## 0. Protokol Immutabilitas Migrasi Berbasis Git Tag (Production Baseline)

### 0.1. Prinsip Dasar: Git Tag sebagai Parameter Produksi
Siklus deployment produksi Sollu App **mutlak dikendalikan oleh Git Tag rilis stabil SemVer** (format `vMAJOR.MINOR.PATCH`, contoh: `v1.2.3`). Setiap tag rilis menandai snapshot kode dan skema database yang telah dieksekusi di server produksi dan dicatat dalam tabel `migrations`.

### 0.2. Aturan Mutlak Immutabilitas Migrasi (Immutability Gate)
1. **File Migrasi Berstatus Terkunci (Locked):**
   - Seluruh file migrasi yang telah ada di dalam Git Tag rilis stabil terakhir bersifat **MUTLAK IMMUTABLE**.
   - **DILARANG KERAS** mengedit, memodifikasi method `up()` / `down()`, me-rename, atau menghapus file migrasi yang telah dirilis pada tag stabil.
2. **Alasan Teknis & Risiko Produksi:**
   - Database produksi hanya menjalankan migrasi baru yang belum tercatat di tabel `migrations`.
   - Mengubah file migrasi lama tidak akan pernah dieksekusi pada database produksi yang sedang berjalan (*missed execution*), menciptakan ketidaksinkronan fatal (*schema drift*) antara lingkungan lokal dan produksi.
   - Menghapus migrasi lama menyebabkan kegagalan audit rollback dan merusak instalasi node/tenant baru.
3. **Mandat Pembuatan File Migrasi Baru:**
   - Setiap perubahan skema terhadap tabel atau kolom yang sudah dirilis (menambah kolom, mengubah tipe data, menambah index, mengubah relasi, atau memodifikasi constraint) **WAJIB dibuatkan file migrasi baru** menggunakan perintah:
     ```bash
     php artisan make:migration add_columns_to_table_name --table=table_name
     ```
4. **Fase Unreleased (Sebelum Tag Baru Dipotong):**
   - File migrasi baru yang dibuat *setelah* tag rilis stabil terakhir (berada di fase development/unreleased) masih diizinkan untuk diamandemen atau disatukan sebelum rilis tag stabil berikutnya dipotong.
   - Begitu tag stabil baru (`git tag -a vX.Y.Z`) dibuat, seluruh migrasi di dalamnya otomatis terkunci permanen.
5. **Verifikasi Otomatis via MCP:**
   - Agen AI dan developer **WAJIB menjalankan tool MCP `audit_migration_compatibility`** dari `sollu-project` untuk mendeteksi pelanggaran file terkunci dan memeriksa backward compatibility sebelum memfinalisasi pekerjaan database.

---

## 1. Prinsip Simetri Mutlak (Absolute Rollback Symmetry)
Setiap file migrasi **WAJIB bersifat dua arah (bidirectional)**. Seluruh perubahan skema pada method `up()` harus memiliki kebalikan yang presisi dan dapat dieksekusi sempurna pada method `down()` dengan urutan terbalik (*LIFO - Last In, First Out*).

### Matriks Pemetaan Operasi DDL (up vs down)
| Operasi di `up()` | Operasi Pembalik Wajib di `down()` | Aturan Khusus |
| :--- | :--- | :--- |
| `Schema::create('table', ...)` | `Schema::dropIfExists('table')` | Drop foreign key relasi anak terlebih dahulu jika ada. |
| `Schema::dropIfExists('table')` | `Schema::create('table', ...)` | **WAJIB mendefinisikan ulang seluruh kolom, indeks, default value, dan foreign keys secara utuh.** Dilarang mengosongkan `down()`. |
| `$table->string('new_col')` / Add Column | `$table->dropColumn('new_col')` | Gunakan array `$table->dropColumn([...])` jika multi-kolom. **Dilarang menggunakan method non-existent seperti `removeColumn()`**. |
| `$table->dropColumn(['col1', 'col2'])` | `$table->string('col1')->...; $table->string('col2')->...;` | Definisikan kembali seluruh tipe data, nullability, dan default value yang sebelumnya ada pada kolom yang di-drop. |
| `$table->renameColumn('old_name', 'new_name')` | `$table->renameColumn('new_name', 'old_name')` | Lakukan pada seluruh tabel yang terpengaruh (termasuk foreign key di tabel lain). |
| `$table->foreign('col_id')->references(...)` | `$table->dropForeign(['col_id'])` | Drop constraint foreign key sebelum drop kolom penampungnya pada `down()`. |

---

## 2. Backward Compatibility & Protokol Expand-and-Contract (Phased Migrations)

Setiap perubahan skema database harus memperhitungkan **Backward Compatibility** terhadap aplikasi produksi, antrean background worker yang sedang berjalan, dan mobile POS client di lapangan:

1. **Non-Breaking Schema Changes (Additive First):**
   - Kolom baru pada tabel produksi yang sudah aktif **WAJIB bersifat `nullable()`** atau memiliki nilai `default(...)`.
   - **DILARANG KERAS** menambahkan kolom `NOT NULL` tanpa default value pada tabel yang sudah memiliki data operasional di produksi, karena akan menggagalkan `INSERT` dari versi aplikasi/klien yang belum diperbarui.
2. **Pola 3-Fase *Expand and Contract*:**
   - **Fase 1 (Expand):** Tambahkan tabel/kolom baru tanpa menghapus struktur lama. Kode aplikasi mulai menulis ke struktur baru dan lama (*dual-write*) atau membaca fallback.
   - **Fase 2 (Migrate & Normalisasi):** Salin data historis via migrasi data DML idempoten. Aplikasi sepenuhnya beralih membaca dari struktur baru.
   - **Fase 3 (Contract):** Setelah seluruh klien mobile dan instance produksi stabil (minimal 1 siklus rilis tag berikutnya), buat file migrasi baru terpisah untuk menghapus kolom/tabel lama yang deprecated.
3. **Reverse ETL pada `down()`:**
   - Jika `up()` memindahkan data dari tabel A ke tabel B lalu menghapus kolom pada tabel A, method `down()` **WAJIB menyalin kembali data dari tabel B ke tabel A** sebelum tabel B atau kolom barunya di-drop.
   - Hindari penghancuran data (*data loss*) permanen saat proses rollback.
4. **Database Transaction:**
   - Gunakan transaksi eksplisit `DB::transaction()` pada migrasi data jika DDL mendukung atau pisahkan DDL skema murni dengan DML migrasi data agar rollback database konsisten.

---

## 3. Kompatibilitas Multi-Database (PostgreSQL & SQLite Testing)
Aplikasi menggunakan **PostgreSQL** di production/development dan **in-memory SQLite** untuk testing unit/feature.

- **PostgreSQL-Specific DDL:** Indeks full-text (`$table->fullText(...)`), GIN/GiST index, atau UUID extensions wajib di-guard agar tidak gagal pada driver SQLite:
  ```php
  if (DB::getDriverName() !== 'sqlite') {
      Schema::table('product_items', function (Blueprint $table) {
          $table->fullText('name');
      });
  }
  ```
- **Symmetric Guarding pada `down()`:** Jika penambahan indeks di-guard berdasarkan driver, proses drop index pada `down()` juga wajib di-guard dengan kondisi yang sama.

---

## 4. Protokol Verifikasi Rollback Wajib (Rollback Verification Protocol)
Setiap kali membuat atau memperbarui file migrasi database, developer/agen **WAJIB menguji siklus rollback secara langsung**:

```bash
# 1. Jalankan migrasi baru
php artisan migrate

# 2. Uji rollback langkah terakhir
php artisan migrate:rollback --step=1

# 3. Jalankan kembali migrasi untuk memastikan idempotensi
php artisan migrate
```

### Kriteria Kelulusan (*Acceptance Criteria*):
- Tool MCP `audit_migration_compatibility` menghasilkan status `pass`.
- Perintah `migrate:rollback` berhasil tanpa error sintaks, unknown method, atau constraint violation.
- Menjalankan `migrate` ulang setelah rollback berjalan mulus tanpa error tabel/kolom duplikat.
- Seluruh test suite (`php artisan test`) lolos 100%.

---

## 5. Larangan Pemanggilan Seeder di Dalam Migrasi (Test State Isolation)
Migrasi database (baik DDL maupun DML) **DILARANG memanggil Production Seeder** (seperti `(new FeatureSeeder)->run()` atau `(new RolePermissionSeeder)->run()`).
- **Alasan:** Migrasi dijalankan otomatis pada setiap siklus testing yang menggunakan trait `RefreshDatabase`. Memanggil seeder di dalam migrasi akan mengotori tabel dan menyebabkan *unique constraint violation* atau kegagalan asersi count pada tes unit/integrasi yang menggunakan Model Factory dari kondisi database kosong.
- **Pengecualian:** Migrasi data hanya boleh menjalankan query DML spesifik (`DB::table(...)->where(...)->update(...)`) yang bersifat idempoten dan simetris pada `down()`.

---

## 6. Standar Arsitektur Metode Pembayaran (Payment Method Standards)

- **Two-Tier Status:** Status aktif metode pembayaran menggunakan pendekatan dua tingkat:
  1. Status Global HQ: `payment_methods.is_active` (boolean, default true) untuk arsip global oleh HQ (misal jika metode sudah memiliki transaksi sehingga tidak boleh di-hard delete).
  2. Status Ketersediaan Cabang: `outlet_payment_method.is_enabled` (boolean, default true) untuk mengontrol ketersediaan per cabang.
- **Proteksi Sistem (`is_system`):** Metode inti (seperti Tunai / Cash) wajib berstatus `is_system = true`, kebal terhadap penghapusan (`delete()`) dan perubahan tipe dasar (`type`).
- **Eliminasi Urutan Manual:** Tidak menggunakan drag-and-drop / manual `sort_order`. Query wajib menggunakan `scopeOrderedByIndustryStandard()`:
  $$\text{CASH (1)} \rightarrow \text{QRIS (2)} \rightarrow \text{EDC (3)} \rightarrow \text{EWALLET (4)} \rightarrow \text{BANK\_TRANSFER (5)} \rightarrow \text{CUSTOM (6)}$$
  diikuti oleh `is_system DESC, name ASC`.
- **Atribut Operasional Kasir:** Model `PaymentMethod` wajib mendukung:
  - `reference_policy`: Enum `PaymentReferencePolicy` (`optional`, `mandatory`, `hidden`).
  - `open_cash_drawer`: Boolean pemicu buka laci kasir (default true untuk Tunai).
  - `instructions`: Teks catatan/instruksi pembayaran untuk kasir.
  - `account_number` & `account_name`: Detail rekening bank/merchant ID.
  - `qr_image_path`: Path file gambar QRIS statis yang diunggah ke storage publik.
