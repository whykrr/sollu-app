# Sollu App UX Copywriting & Wording Standards

Standar penulisan teks antarmuka (*UX Copywriting* & *Microcopy*) untuk seluruh modul dan komponen pada **Sollu App**.

---

## 1. Filosofi & Karakter Tone of Voice

### 🌟 Filosofi Utama
> **"Jangan membuat user belajar cara kerja aplikasi; buat aplikasi mengikuti cara kerja user."**
> *(Bahasa aplikasi harus berbicara dengan bahasa familiar pengguna/pedagang, bukan istilah teknis sistem, database, atau pengembang).*

Sollu App adalah asisten digital operasional bisnis harian yang andal, gesit, dan bersahabat. Setiap kata yang tampil di layar harus mencerminkan karakter:

1. **Jelas & Familiar (Bukan Istilah Teknis):** Gunakan kosakata yang dipahami kasir dan pemilik toko harian. Hindari istilah teknis developer atau birokrasi formal (*"Unduh"* bukan *"Download"*, *"Sampah"* bukan *"Trash/Recycle Bin"*, *"Draf"* bukan *"Pending Review"*).
2. **Santai & Hangat:** Hindari bahasa birokrasi, kaku, atau terasa seperti mesin/hukum formal. Gunakan tutur kata manusiawi yang akrab selayaknya rekan kerja yang cerdas (*"Yuk, ..."*, *"Tokomu"*, *"Bisnismu"*).
3. **Komunikatif & Membantu:** Arahkan pengguna dengan kalimat yang membimbing dan solutif saat terjadi kendala.
4. **To the Point (Lugas & Ringkas):** Langsung ke inti maksud. Sederhana — tampilkan hanya yang diperlukan tanpa kalimat pembuka yang bertele-tele.
5. **Tetap Profesional & Jelas (*Clarity First*):** Santai bukan berarti menggunakan bahasa pasar/slang berlebihan (*alay/lebay*). Istilah teknis operasional bisnis (*SKU, Stok Opname, Purchase Order, Void, Refund*) tetap dipertahankan dengan tepat.
6. **Feedback Jelas & Menenangkan:** Notifikasi dan pesan konfirmasi harus memberikan kepastian status aksi secara transparan.

---

## 2. Tabel Komparasi Gaya Penulisan (Do's & Don'ts)

| Konteks UI | ❌ Kaku / Birokratis / Buruk | ⚠️ Terlalu Santai / Slang | ✅ Santai, Komunikatif & Profesional |
| :--- | :--- | :--- | :--- |
| **Empty State (Tabel Kosong)** | "Tidak ada rekaman data ditemukan dalam basis data." | "Waduh sepi bet belum ada apa2 gan." | "Belum ada produk nih. Yuk, tambah produk pertamamu!" |
| **Empty State (Hasil Cari Kosong)** | "Pencarian data tidak menghasilkan entitas terkait." | "Gak nemu apa-apa nih bro." | "Data yang kamu cari nggak ditemukan. Coba cek ejaan atau kata kunci lain ya." |
| **Notifikasi Berhasil Simpan** | "Proses penyimpanan data entitas telah sukses dieksekusi." | "Mantap data udah masuk bos!" | "Data berhasil disimpan!" / "Perubahan berhasil disimpan." |
| **Notifikasi Hapus (Ke Sampah)** | "Data telah berhasil dipindahkan menuju direktori trash." | "Udah dibuang ya datanya." | "Data berhasil dipindah ke sampah." |
| **Modal Konfirmasi Hapus** | "Apakah Anda sungguh meyakini hendak mengeksekusi penghapusan terhadap rekaman ini?" | "Beneran mau didelete nih? Gak nyesel?" | "Yakin mau hapus produk ini? Data akan dipindah ke sampah dan tidak muncul lagi di kasir." |
| **Validasi Input Kosong** | "Kolom berikut merupakan parameter wajib yang tidak boleh kosong." | "Harap isi dong jangan dikosongin." | "Bagian ini wajib diisi ya." |
| **Validasi Format Email** | "Format masukan alamat surat elektronik tidak memenuhi standar regex." | "Emailnya ngaco tuh." | "Format email kurang tepat. Contoh: nama@bisnis.com" |
| **Validasi Angka / Stok** | "Nilai kuantitas masukan melampaui batas ketersediaan saldo inventori." | "Kebanyakan bos stok gak cukup." | "Jumlah melebihi stok yang tersedia (sisa: 12 pcs)." |
| **Akses Ditolak (RBAC)** | "Pengguna tidak memiliki wewenang otoritas untuk mengakses sumber daya ini." | "Eits lu gak boleh masuk sini." | "Kamu belum punya akses ke halaman ini. Hubungi pemilik bisnis untuk bantuan." |
| **Upsell / Feature Lock** | "Fitur ini dilarang untuk tingkatan paket langganan Anda saat ini." | "Upgrade dulu dong biar bisa pake." | "Mau kelola resep otomatis? Yuk, tingkatkan paket tokomu ke Pro!" |
| **Error Server / Jaringan** | "Terjadi kegagalan konektivitas soket pada gateway subsistem server 500." | "Server lagi ngambek nih." | "Ups, koneksi terputus. Coba muat ulang halaman ya." |
| **Deskripsi Header Halaman** | "Halaman antarmuka manajemen pengelolaan seluruh entitas inventarisasi barang." | "Tempat buat liat-liat stok toko lu." | "Pantau dan kelola stok barang di seluruh outlet tokomu." |

---

## 3. Penerapan Berdasarkan Komponen UI

### 3.1. Judul & Deskripsi Header (`MainPageHeader`)
- **Prinsip:** Judul harus singkat (1-3 kata), deskripsi berupa 1 kalimat aktif yang menjelaskan fungsi utama halaman.
- **Contoh:**
  - Title: `"Katalog Produk"` — Description: `"Kelola harga, varian, dan kategori produk tokomu."`
  - Title: `"Stok Opname"` — Description: `"Cocokkan catatan stok sistem dengan fisik barang di outlet."`
  - Title: `"Pesanan Penjualan"` — Description: `"Pantau seluruh riwayat transaksi dan status pembayaran."`

### 3.2. State Kosong (*Empty State*)
- **Prinsip:** Berikan penjelasan yang ramah disertai Call-to-Action (CTA) yang jelas agar pengguna tahu langkah berikutnya.
- **Pola Wording:**
  1. *Headline:* Menyatakan kondisi saat ini secara lugas (Contoh: `"Belum Ada Bahan Baku"`).
  2. *Body:* Menjelaskan apa fungsinya atau kenapa kosong (Contoh: `"Catat bahan baku untuk memantau sisa stok dan hitung HPP otomatis."`).
  3. *Tombol CTA:* Ajakan aktif (Contoh: `"Tambah Bahan Baku"`).

### 3.3. Formulir & Input Fields
- **Label:** Singkat dan langsung (Contoh: `"Nama Barang"`, `"Harga Jual"`, `"Kategori"`). Hindari awalan bertele-tele seperti `"Silakan Masukkan Nama Barang"`.
- **Placeholder:** Gunakan sebagai contoh isian nyata atau petunjuk ringkas, bukan mengulang kata label.
  - *Label:* `"SKU / Kode Barang"` $\rightarrow$ *Placeholder:* `"Misal: KOP-001"`
  - *Label:* `"Harga Pokok (HPP)"` $\rightarrow$ *Placeholder:* `"0"`
  - *Label:* `"Cari Produk"` $\rightarrow$ *Placeholder:* `"Ketik nama produk atau scan barcode..."`
- **Helper Text / Keterangan di Bawah Input:** Berikan panduan santai yang membantu.
  - Contoh: `"Harga sebelum pajak dan biaya layanan."`
  - Contoh: `"Barcode bisa langsung diisi otomatis menggunakan scanner."`

### 3.4. Tombol Aksi (*Action Buttons*)
- **Prinsip:** Gunakan kata kerja aktif spesifik. Maksimal 2-3 kata.
- **Contoh Baku:**
  - Simpan form baru: `"Simpan"` / `"Simpan Data"`
  - Simpan perubahan: `"Simpan Perubahan"`
  - Batal/Tutup: `"Batal"`
  - Tambah entitas: `"Produk Baru"`, `"Pesanan Baru\"`
  - Ekspor/Impor: `"Ekspor Excel"`, `"Unduh PDF"`, `"Impor Data"`
  - Filter: `"Terapkan"`, `"Reset Filter"`

### 3.5. Dialog Konfirmasi (*Confirmation Modals*)
- **Prinsip:** Jangan membuat pengguna panik, tetapi sampaikan konsekuensi tindakan dengan sangat jelas.
- **Pola Baku:**
  - *Judul:* `"Hapus [Nama Entitas]?"` atau `"Batalkan Transaksi?"`
  - *Isi Pesan:* Jelaskan apa yang terjadi pada data tersebut dengan bahasa santai dan transparan.
  - *Aksi Sekunder:* `"Batal"` (selalu di kiri/belakang).
  - *Aksi Primer:* `"Ya, Hapus"` / `"Ya, Batalkan"` (warna merah untuk aksi destruktif).

### 3.6. Pesan Flash & Toast Notifications
- **Berhasil (Success):** Menggunakan kalimat positif yang melegakan.
  - `"Data berhasil disimpan!"`
  - `"Perubahan berhasil disimpan."`
  - `"Data dipindah ke sampah!"`
  - `"Data berhasil dikembalikan."`
- **Peringatan (Warning):** Mengingatkan dengan sopan.
  - `"Stok beberapa produk sudah menipis. Segera lakukan restock ya!"`
- **Gagal / Error (Danger):** Informatif dan solutif.
  - `"Gagal menyimpan data. Pastikan semua kolom terisi dengan benar."`

---

## 4. Kosakata Baku & Konsistensi Istilah

Untuk menjaga konsistensi di seluruh modul, gunakan padanan kata baku berikut:

| Istilah Terpilih | Hindari Penggunaan | Catatan |
| :--- | :--- | :--- |
| **Unduh** | Download, Sedot | Gunakan untuk file PDF/Excel |
| **Unggah** | Upload | Gunakan untuk form file/foto |
| **Simpan** | Save, Submit, Kirimkan | Standar aksi formulir |
| **Sampah** | Trash, Tong Sampah, Recycle Bin | Tempat penampungan soft delete |
| **Hapus** | Delete, Buang | Aksi memindahkan ke sampah / hapus permanen |
| **Pulihkan** | Restore, Balikin | Mengembalikan data dari sampah |
| **Draf** | Draft, Konsep | Status dokumen sebelum disetujui |
| **Disetujui** | Approved, Acc | Status final transaksi |
| **Batal** | Cancel, Void | Membatalkan aksi / membatalkan transaksi |
| **Kelola** | Manajemen, Maintain | Untuk deskripsi fitur |
| **Tokomu / Bisnismu** | Perusahaan Anda, Badan Usaha Anda | Sapaan yang lebih akrab dan personal |
| **Yuk / Coba** | Silahkan, Harap, Dimohon | Kata ajakan yang hangat dan tidak kaku |
| **Sollu Indonesia / Sollu** | Sollu App | Nama aplikasi resmi sesuai `APP_NAME` di `.env` / `config('app.name')`. Dilarang menggunakan "Sollu App". |

---

## 5. Standar Penamaan Aplikasi & Branding

- **Nama Aplikasi Resmi:** **Sollu Indonesia** (atau cukup **Sollu** dalam konteks ringkas/slogan).
- **🚨 LARANGAN PENGGUNAAN "Sollu App":** Dilarang keras menggunakan kata **"Sollu App"** pada email, notifikasi, halaman antarmuka (UI), invoice/dokumen PDF, maupun dokumen teknis/marketing.
- **Dynamic Variable:** Pada template email/notifikasi/Blade, selalu utamakan penggunaan `config('app.name')` agar selalu sinkron dengan environment.

---

## 6. Checklist Verifikasi Copywriting (DoD Wording)

Sebelum menyelesaikan pembuatan atau refactor tampilan UI, pastikan:

- [ ] Tidak ada pesan yang terasa birokratis atau seperti terjemahan mesin kaku (*Google Translate* mentah).
- [ ] Placeholder memberikan contoh input yang relevan, bukan sekadar mengulang teks label.
- [ ] State kosong (*empty state*) memiliki pesan ramah dan tombol aksi jelas.
- [ ] Modal konfirmasi menjelaskan dampak aksi dengan bahasa yang mudah dimengerti.
- [ ] Pesan validasi dan error memberikan solusi perbaikan bagi pengguna.
- [ ] Istilah operasional bisnis tetap akurat dan konsisten.
- [ ] Penamaan aplikasi menggunakan **Sollu Indonesia** atau `config('app.name')` (tidak ada "Sollu App").
