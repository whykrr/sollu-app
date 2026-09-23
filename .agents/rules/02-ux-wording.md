---
trigger: always_on
---

# Rule 02: Standar Gaya Bahasa & UX Copywriting (Tone of Voice)

## 1. Filosofi Inti & Karakter Tone of Voice

> **"Jangan membuat user belajar cara kerja aplikasi; buat aplikasi mengikuti cara kerja user."**
> *(Gunakan bahasa yang familiar bagi pelaku usaha, bukan istilah teknis/sistem. Aplikasi harus berbicara selayaknya rekan kerja cerdas yang ramah dan solutif).*

Seluruh teks antarmuka (*UI copy*, placeholder, helper text, toast message, validation feedback, error banner, empty state, modal konfirmasi) pada **Sollu App** WAJIB mengikuti standar bahasa berikut:

1. **Jelas & Familiar (Bukan Istilah Teknis):** Hindari jargon developer, database, birokrasi, atau hukum formal (hindari: *"Dimohon untuk...", "Pengguna wajib melaksanakan...", "Sistem mengeksekusi proses...", "Query gagal"*). Gunakan istilah bisnis dan bahasa percakapan yang akrab bagi pedagang/kasir.
2. **Santai & Hangat:** Gunakan sapaan akrab selayaknya asisten rekan kerja cerdas (*"Yuk, ...", "Tokomu", "Bisnismu"*).
3. **Komunikatif & Solutif:** Beritahu pengguna apa yang sedang terjadi dan arahkan langkah solutif selanjutnya jika terjadi kesalahan.
4. **To the Point (Lugas & Ringkas):** Langsung ke inti pesan tanpa kalimat pembuka yang bertele-tele. Sederhana — tampilkan hanya yang diperlukan.
5. **Tetap Profesional & Jelas (*Clarity First*):** Jangan menggunakan kata gaul/slang pasar berlebihan yang menurunkan kredibilitas (*hindari: "bgt", "gak jelas", "gokil", "mantap gan"*). Istilah operasional bisnis baku (*SKU, Stok Opname, Resep, HPP, Void, Refund, Outlet*) tetap digunakan secara presisi.
6. **Feedback Jelas:** Selalu beri tahu dengan tegas apakah suatu aksi berhasil atau gagal secara langsung.

---

## 2. Aturan Baku per Komponen UI

### A. Empty State (Tabel & Daftar Kosong)
- **Wajib:** Mengandung pesan ramah yang menjelaskan mengapa data kosong + Call to Action (CTA) jelas untuk memulai.
- **Contoh:**
  - *Tabel Produk Kosong:* `"Belum ada produk nih. Yuk, tambah produk pertamamu!"` + Tombol `"+ Tambah Produk"`
  - *Pencarian Kosong:* `"Data yang kamu cari nggak ditemukan. Coba cek ejaan atau kata kunci lain ya."`

### B. Notifikasi Flash & Toast (`useToastStore`, Controller Flash)
- **Berhasil:** Singkat, positif, dan melegakan.
  - `"Data berhasil disimpan!"`
  - `"Perubahan berhasil disimpan."`
  - `"Data berhasil dipindah ke sampah."`
  - `"Data berhasil dikembalikan."`
- **Gagal/Peringatan:** Jelas dan membimbing.
  - `"Ups! Ada data yang belum lengkap. Coba periksa kembali ya."`
  - `"Koneksi terputus. Coba muat ulang halaman ya."`

### C. Placeholder & Helper Text
- **Placeholder:** Berikan contoh nyata data yang akan diisi, bukan hanya mengulang label.
  - *Label:* `"Nama Bahan"` $\rightarrow$ *Placeholder:* `"Misal: Susu UHT Full Cream"`
  - *Label:* `"Harga Jual"` $\rightarrow$ *Placeholder:* `"0"`
- **Helper Text:** Berikan petunjuk yang mempermudah pemahaman pengguna.

### D. Modal Konfirmasi Hapus & Aksi Destruktif
- **Judul:** Lugas dan to-the-point (contoh: `"Hapus Produk Ini?"`).
- **Deskripsi:** Jelaskan konsekuensinya secara transparan tanpa menakut-nakuti (contoh: `"Produk ini akan dipindahkan ke sampah dan tidak tampil di kasir. Kamu masih bisa mengembalikannya nanti."`).
- **Tombol:** `"Batal"` (sekunder) dan `"Ya, Hapus"` (primer destruktif `btn-danger`).

### E. Upsell & Feature Lock
- Jangan bersikap membatasi atau menghukum. Berikan motivasi manfaat fitur secara positif.
  - *Contoh:* `"Mau kelola resep otomatis? Yuk, tingkatkan paket tokomu ke Pro!"`

---

## 3. Matriks Do's & Don'ts Wording

| Konteks | ❌ Hindari (Kaku / Teknis / Slang) | ✅ Gunakan (Santai, Jelas, Profesional) |
| :--- | :--- | :--- |
| **Sapaan** | "Pengguna yang terhormat", "User" | "Kamu", "Tokomu", "Bisnismu" |
| **Ajakan** | "Silakan klik tombol di bawah untuk input" | "Yuk, tambah produk pertamamu!" |
| **Error Form** | "Field 'price' is required." | "Harga jual belum diisi nih." |
| **Hapus Data** | "Apakah Anda yakin ingin mendelete record?" | "Yakin mau hapus produk ini?" |
| **Soft Delete** | "Data dimasukkan ke Recycle Bin." | "Data berhasil dipindah ke sampah." |
| **Restore** | "Record restored successfully." | "Data berhasil dikembalikan." |
| **Unduh File** | "File downloaded." | "Laporan berhasil diunduh." |

---

## 4. Referensi Lengkap

Lihat panduan lengkap dan tabel perbandingan rinci di [docs/ux-wording.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/ux-wording.md).
