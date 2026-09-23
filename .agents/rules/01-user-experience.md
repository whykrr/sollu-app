---
trigger: always_on
---

# Rule 01: Filosofi Inti & Prinsip Pengalaman Pengguna (User-Centric Principles)

## 🌟 Filosofi Inti
> **"Jangan membuat user belajar cara kerja aplikasi; buat aplikasi mengikuti cara kerja user."**
> *(Aplikasi wajib beradaptasi dengan alur dan kebiasaan bisnis nyata pedagang/merchant, bukan memaksa pengguna mempelajari kerumitan teknis dan arsitektur sistem).*

---

## 📋 15 Prinsip Utama Rekayasa Berpusat Pengguna (User-Centric Principles)

### 1. Mudah di-Setup (*Frictionless Setup & Onboarding*)
- **Prinsip:** Konfigurasi awal harus dapat diselesaikan dalam hitungan menit tanpa manual book yang rumit.
- **Implementasi:**
  - Onboarding wizard yang ringkas, hangat, dan membimbing.
  - Nilai konfigurasi bawaan (*default settings*) sudah siap pakai sesuai jenis usaha merchant (`BusinessType`).
  - Tidak membebani pengguna dengan formulir registrasi yang panjang dan melelahkan di awal.

### 2. Mudah Dioperasikan (*Effortless Daily Operations*)
- **Prinsip:** Pekerjaan operasional berulang (kasir POS, pencatatan transaksi, cek stok) dapat dijalankan dengan lancar tanpa hambatan mental.
- **Implementasi:**
  - Layout POS dan dashboard memprioritaskan kecepatan dan alur kerja harian.
  - Dukungan shortcut keyboard dan integrasi barcode scanner pada pencarian item.
  - Transisi halaman instan menggunakan Inertia.js SPA tanpa reload penuh browser.

### 3. Interface Tidak Ambigu (*Unambiguous Interface & Clarity*)
- **Prinsip:** Setiap elemen visual harus memiliki maksud yang gamblang dan tidak menimbulkan tanda tanya atau keraguan.
- **Implementasi:**
  - Label tombol menjelaskan aksi nyata (`"+ Tambah Produk"`, `"Simpan Perubahan"`), bukan kata umum ambigu (`"Proses"`, `"OK"`).
  - Status data (badge) memiliki warna semantik yang konsisten dan label yang jelas (`Draft` abu-abu, `Disetujui` hijau, `Dibatalkan` merah).
  - Indikator visual jelas pada field wajib (*required indicator*) dan field opsional.

### 4. Experience User Diutamakan (*User Experience First*)
- **Prinsip:** Seluruh keputusan teknis dan desain harus berorientasi pada kemudahan dan kenyamanan pengguna di berbagai perangkat.
- **Implementasi:**
  - **Ergonomi Multi-Device:** Target sentuh minimum Mobile $\ge 44\times 44\text{px}$ (`.touch-target`), Tablet $\ge 36\times 36\text{px}$ (`.touch-target-sm`), Desktop $\ge 28\times 28\text{px}$ (`.btn-sm`, `.form.sm`).
  - **Thumb Zone Mobile:** Aksi utama formulir di smartphone wajib berada di sticky footer bawah (`#popUpFooter`).
  - **Anti-Zoom iOS Safari:** Input form mobile wajib berukuran font $\ge 16\text{px}$ (`.form.adaptive` / `text-base sm:text-xs`).
  - **Performa Responsif:** Waktu respons API dan render halaman tidak boleh melebihi 5 detik.

### 5. Sederhana — Tampilkan Hanya yang Diperlukan (*Simplicity & Clutter-Free*)
- **Prinsip:** Hilangkan distraksi visual dan beban kognitif yang tidak perlu.
- **Implementasi:**
  - **Desain Flat Minimalis:** Komponen di dalam container `<MainPage>` **DILARANG MENGGUNAKAN SHADOW** (`shadow`, `shadow-sm`, dll). Gunakan garis batas tipis (`border-slate-200`) dan latar warna solid (`bg-white` / `bg-slate-50`).
  - **Non-Scrolling Sticky Header:** Header, widget analitik, dan filter toolbar tetap di atas slot non-scrolling; default slot khusus untuk tabel data scrollable.
  - Sembunyikan informasi sekunder pada tampilan mobile menggunakan *responsive column masking* (`show: 'md'` atau `show: 'lg'` pada `<Table>`).

### 6. Jelas — Gunakan Bahasa yang Familiar, Bukan Istilah Teknis (*Familiar Language*)
- **Prinsip:** Gunakan kosakata pedagang dan bahasa bisnis harian, hindari jargon developer, database, birokrasi kaku, atau hukum formal.
- **Implementasi:**
  - Gunakan istilah baku yang ramah: `"Sampah"` (bukan *Trash/Recycle Bin*), `"Pulihkan"` (bukan *Restore*), `"Draf"` (bukan *Pending Review*), `"Unduh"` (bukan *Download*).
  - Hindari pesan error teknis seperti *"SQLSTATE[23000]: Integrity constraint violation"*; gantikan dengan *"Nama produk sudah digunakan. Coba ganti dengan nama lain ya."*

### 7. Konsisten (*System-Wide Consistency*)
- **Prinsip:** Pola tombol, formulir, navigasi, dan istilah selalu seragam di seluruh modul.
- **Implementasi:**
  - **Layout Seragam:** Semua modul menggunakan wrapper `<MainPage>` dengan hierarki slot baku (`#header`, `#widgets`, `#filter`, default slot, `#footer`).
  - **Toolbar Terpadu:** Seluruh toolbar filter menggunakan `@/Components/UI/ActionBar/ActionBar.vue` dengan tombol aksi utama di paling kanan.
  - **Hierarki Tombol:** `.btn-xs` (tabel padat), `.btn-sm` (header/filter), `.btn` (submit form/modal), `.btn-lg` (POS hero).
  - **Komponen Form Terpusat:** Selalu gunakan `@/Components/Form/` (`TextField`, `DropdownField`, `NumberField`, dll). Dilarang keras menggunakan tag HTML mentah.

### 8. Smart Default (*Intelligent Pre-fills*)
- **Prinsip:** Otomatisasi pengisian hal-hal yang sudah bisa diprediksi oleh sistem agar pengguna tidak mengulang pengisian yang sama.
- **Implementasi:**
  - Outlet otomatis terpilih sesuai sesi aktif pengguna.
  - Preset filter tanggal default selalu `'this_month'` (`DatePresetEnum`).
  - Auto-generate kode SKU / barcode jika pengguna mengosongkannya saat membuat produk baru.
  - Autofocus pada input pertama saat modal/drawer formulir dibuka.

### 9. Minim Langkah (*Minimal Steps for Common Tasks*)
- **Prinsip:** Pekerjaan umum dengan frekuensi tinggi harus dapat diselesaikan dengan sedikit klik/aksi.
- **Implementasi:**
  - **Single Action Row Click:** Baris tabel dengan 1 aksi utama wajib menggunakan `@row-click` langsung untuk membuka detail/edit (tanpa tombol ikon aksi terpisah).
  - **Pencarian Live Inline:** Pencarian tabel langsung aktif dengan debounce 500ms tanpa perlu menekan tombol "Cari" atau membuka modal filter terpisah.
  - **Dropdown Opsi Data:** Aksi ekspor Excel/PDF dan impor massal terpadu dalam satu dropdown `<ActionsDropdown label="Opsi Data" />`.

### 10. Progressive Disclosure (*3-Tier Form Architecture*)
- **Prinsip:** Tampilkan input esensial terlebih dahulu; fitur lanjutan dan kompleks hanya muncul ketika pengguna membutuhkannya.
- **Implementasi:**
  - **Tier 1 (Simple Form $\le 5$ fields):** Tampilan vertikal flat sederhana (Kategori, Satuan UOM, Meja).
  - **Tier 2 (Progressive Form $6-12$ fields):** Core fields 80% selalu tampak di atas + Advanced fields 20% dibungkus dalam `<DisclosureSection>` + trigger kondisional (misal: input *Minimum Stok* hanya muncul jika switch *Lacak Stok* aktif).
  - **Tier 3 (Wizard / Tabbed Form $> 12$ fields):** Mode Create menggunakan Linear Stepper (`<FormStepper>`), Mode Edit menggunakan Direct Tabbed Navigation (`<FormTabs>`).

### 11. Mencegah Kesalahan (*Proactive Error Prevention*)
- **Prinsip:** Lindungi pengguna dari kesalahan yang tidak disengaja sebelum terjadi.
- **Implementasi:**
  - **Proteksi Form Dirty (`useFormDirtyGuard`):** Jika form sudah diubah dan pengguna menutup drawer/klik Batal, otomatis munculkan konfirmasi *"Perubahan Belum Disimpan"*.
  - **Konfirmasi Aksi Berisiko:** Aksi destruktif (Hapus data, Void transaksi, Pembatalan PO) wajib melalui dialog modal konfirmasi dengan penjelasan konsekuensi yang gamblang.
  - **Disable Saat Processing:** Tombol submit otomatis dinonaktifkan (`:disabled="form.processing"`) saat request sedang berlangsung untuk mencegah submit ganda.

### 12. Mudah Diperbaiki (*Graceful Recovery & Soft Deletes*)
- **Prinsip:** Sediakan jalan keluar jika pengguna melakukan kesalahan atau berubah pikiran.
- **Implementasi:**
  - Seluruh penghapusan data utama menerapkan **Soft Deletes** (`deleted_at`), sehingga data masuk ke tab/filter Sampah (`FilterTrashData`) dan dapat dipulihkan kapan saja via tombol **Pulihkan**.
  - Tombol Batal pada drawer/modal selalu tersedia dan aman digunakan.

### 13. Feedback Jelas (*Explicit & Instant Feedback*)
- **Prinsip:** Sistem harus selalu menginformasikan status keberhasilan, peringatan, atau kegagalan dari setiap aksi pengguna secara instan.
- **Implementasi:**
  - **Toast Notifications:** Tampilkan toast sukses (`ResourceMessage::CREATE_SUCCESS`, `UPDATE_SUCCESS`, `DELETE_SUCCESS`, `RESTORE_SUCCESS`) segera setelah mutasi berhasil.
  - **Loading States:** Tampilkan skeleton loader atau spinner saat mengambil data asinkron on-demand.
  - **Pesan Validasi Solutif:** Pesan error di bawah input harus memberitahukan cara memperbaiki data (contoh: *"Format email kurang tepat. Contoh: nama@bisnis.com"*).

### 14. Ikuti Cara Kerja User (*Adaptive Business Workflows*)
- **Prinsip:** Sistem harus mencerminkan kenyataan operasional bisnis merchant, bukan teori perangkat lunak kaku.
- **Implementasi:**
  - Stok opname membandingkan stok fisik di lapangan vs stok sistem tanpa mengunci operasional toko secara kaku.
  - Mendukung penerimaan barang bertahap (*Partial Goods Receipt*) sesuai pengiriman riil supplier.
  - Pembayaran kasir mendukung split bill dan multi-metode bayar sesuai kebiasaan pelanggan.

### 15. Wording Santai Namun Tetap Profesional (*Warm, Concise, Professional Copywriting*)
- **Prinsip:** Gaya komunikasi hangat selayaknya asisten rekan kerja cerdas, to the point, komunikatif, solutif, tanpa bahasa birokrasi kaku atau slang pasar berlebihan.
- **Implementasi:**
  - Gunakan sapaan akrab: *"Tokomu"*, *"Bisnismu"*, *"Yuk, tambah produk pertamamu!"*.
  - Langsung ke inti pesan tanpa kalimat pembuka bertele-tele.
  - Ikuti panduan baku di `02-ux-wording.md`.
