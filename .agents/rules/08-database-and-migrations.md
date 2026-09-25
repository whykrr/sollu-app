# Rule 08: Standar Database Skema, Migrasi Reversibel & Protokol Rollback

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

## 2. Protokol Normalisasi & Migrasi Data (Data ETL Reversibility)
Migrasi yang melibatkan transformasi atau normalisasi data antar tabel (DML) harus dirancang agar **tidak menghilangkan data saat di-rollback**.

1. **Pola *Expand and Contract* (Phased Migrations):**
   - **Fase 1 (Expand):** Buat tabel atau kolom baru tanpa langsung menghapus kolom lama.
   - **Fase 2 (Migrate & Normalisasi):** Salin dan transformasi data dari struktur lama ke struktur baru. Kode aplikasi mulai membaca dan menulis ke struktur baru.
   - **Fase 3 (Contract):** Setelah fitur stabil dan teruji di production, buat migrasi terpisah untuk menghapus kolom/tabel lama yang sudah deprecated.
2. **Reverse ETL pada `down()`:**
   - Jika `up()` memindahkan data dari tabel A ke tabel B lalu menghapus kolom pada tabel A, method `down()` **WAJIB menyalin kembali data dari tabel B ke tabel A** sebelum tabel B atau kolom barunya di-drop.
   - Hindari penghancuran data (*data loss*) permanen saat proses rollback.
3. **Database Transaction:**
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
- Perintah `migrate:rollback` berhasil tanpa error sintaks, unknown method, atau constraint violation.
- Menjalankan `migrate` ulang setelah rollback berjalan mulus tanpa error tabel/kolom duplikat.
- Seluruh test suite (`php artisan test`) lolos 100%.
