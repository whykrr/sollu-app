---
trigger: always_on
---

# Rule: Sollu App UX Wording & Tone of Voice

## 1. Prinsip Utama (Santai, Komunikatif, To the Point, Profesional)

Seluruh teks antarmuka (*UI copy*, placeholder, helper text, toast message, validation feedback, error banner, empty state, modal konfirmasi) pada **Sollu App** WAJIB mengikuti standar bahasa berikut:

1. **Santai & Hangat:** DILARANG menggunakan bahasa formal birokratis/kaku (hindari: *"Dimohon untuk...", "Pengguna wajib melaksanakan...", "Sistem mengeksekusi proses..."*). Gunakan sapaan akrab selayaknya asisten rekan kerja cerdas (*"Yuk, ...", "Tokomu", "Bisnismu"*).
2. **Komunikatif & Solutif:** Beritahu pengguna apa yang sedang terjadi dan apa yang bisa mereka lakukan selanjutnya.
3. **To the Point (Lugas & Ringkas):** Langsung ke inti pesan tanpa kalimat pembuka yang bertele-tele.
4. **Tetap Profesional & Jelas (*Clarity First*):** Jangan menggunakan kata gaul/slang berlebihan yang menurunkan kredibilitas (*hindari: "bgt", "gak jelas", "gokil", "mantap gan"*). Istilah bisnis baku (*SKU, Stok Opname, Resep, HPP, Void, Refund, Outlet*) tetap digunakan secara presisi.

---

## 2. Aturan Baku per Komponen UI

### A. Empty State (Tabel & Daftar Kosong)
- **Wajib:** Mengandung pesan ramah yang menjelaskan mengapa kosong + Call to Action (CTA) untuk memulai.
- **Contoh:**
  - *Tabel Produk Kosong:* `"Belum ada produk nih. Yuk, tambah produk pertamamu!"` + Tombol `"+ Tambah Produk"`
  - *Pencarian Kosong:* `"Data yang kamu cari nggak ditemukan. Coba cek ejaan atau kata kunci lain ya."`

### B. Notifikasi Flash & Toast (`useToastStore`, Controller Flash)
- **Berhasil:** Singkat dan positif (`"Data berhasil disimpan!"`, `"Perubahan berhasil disimpan."`, `"Data berhasil dipindah ke sampah."`, `"Data berhasil dikembalikan."`).
- **Gagal/Peringatan:** Jelas dan membimbing (`"Ups! Ada data yang belum lengkap. Coba periksa kembali ya."`, `"Koneksi terputus. Coba muat ulang halaman ya."`).

### C. Placeholder & Helper Text
- **Placeholder:** Berikan contoh nyata data yang akan diisi, bukan hanya mengulang label (contoh Label: *"Nama Bahan"*, Placeholder: *"Misal: Susu UHT Full Cream"*).
- **Helper Text:** Berikan petunjuk yang mempermudah pemahaman pengguna.

### D. Modal Konfirmasi Hapus & Aksi Destruktif
- **Judul:** Lugas (contoh: `"Hapus Produk Ini?"`).
- **Deskripsi:** Jelaskan konsekuensinya secara transparan (contoh: `"Produk ini akan dipindahkan ke sampah dan tidak tampil di kasir. Kamu masih bisa mengembalikannya nanti."`).
- **Tombol:** `"Batal"` (sekunder) dan `"Ya, Hapus"` (primer destruktif).

### E. Upsell & Feature Lock
- Jangan bersikap membatasi atau menghukum. Berikan motivasi manfaat fitur (contoh: `"Mau kelola resep otomatis? Yuk, tingkatkan paket tokomu ke Pro!"`).

---

## 3. Referensi Lengkap

Lihat panduan lengkap dan tabel komparasi Do's & Don'ts di [docs/ux-wording.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/ux-wording.md).
