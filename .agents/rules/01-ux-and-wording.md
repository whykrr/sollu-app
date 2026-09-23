# Rule 01: Filosofi UX, Prinsip Pengguna & Standar Wording

## 1. Filosofi Inti & Tone of Voice
> **"Jangan membuat user belajar cara kerja aplikasi; buat aplikasi mengikuti cara kerja user."**
> *(Aplikasi wajib beradaptasi dengan alur bisnis nyata pedagang/kasir. Bahasa ramah, santai selayaknya rekan kerja cerdas, to the point, solutif, tanpa jargon teknis/birokrasi kaku).*

---

## 2. 15 Prinsip Rekayasa Pengalaman Pengguna & Invarian Teknis

| No | Prinsip | Invarian Teknis Wajib |
| :--- | :--- | :--- |
| 1 | **Mudah di-Setup** | Onboarding ringkas; default settings otomatis per `BusinessType`; form awal minimal. |
| 2 | **Mudah Dioperasikan** | Navigasi SPA Inertia instan; shortcut keyboard kasir; pencarian barcode scanner. |
| 3 | **Interface Tidak Ambigu** | Label aksi eksplisit (`"+ Tambah Produk"`, `"Simpan Perubahan"`); warna badge semantik dari Enum. |
| 4 | **Ergonomi Multi-Device** | Target sentuh: Mobile $\ge 44\text{px}$ (`.touch-target`), Tablet $\ge 36\text{px}$, Desktop $\ge 28\text{px}$/30px. Input mobile font $\ge 16\text{px}$ (`.form.adaptive`) cegah zoom iOS. Thumb zone di footer (`#popUpFooter`). |
| 5 | **Sederhana (Zero Shadow)** | Komponen di `<MainPage>` **DILARANG MENGGUNAKAN SHADOW** (`shadow-*`). Gunakan border tipis (`border-slate-200`) dan bg solid. Non-scrolling sticky header; responsive column masking di mobile (`show: 'md'`). |
| 6 | **Bahasa Familiar** | Kosakata bisnis ramah (*"Sampah"*, *"Pulihkan"*, *"Draf"*, *"Unduh"*); ganti pesan error teknis database dengan pesan solutif. |
| 7 | **Sistem Konsisten** | Layout baku `<MainPage>` 5-slot; toolbar filter terpadu `ActionBar`; form `@/Components/Form/`. |
| 8 | **Smart Default** | Outlet aktif otomatis terpilih; preset tanggal default `'this_month'`; auto-generate SKU jika kosong. |
| 9 | **Minim Langkah** | Single action row click `@row-click` langsung buka detail/edit (`:action="false"`); live search inline debounced 500ms; ekspor/impor disatukan dalam dropdown `<ActionsDropdown label="Opsi Data" />`. |
| 10 | **Progressive Disclosure** | 3-Tier Form Architecture: Tier 1 Simple ($\le 5$ fields), Tier 2 Progressive ($6-12$ fields, 80/20 via `<DisclosureSection>`), Tier 3 Complex ($> 12$ fields, Create: Stepper vs Edit: Tabs). |
| 11 | **Mencegah Kesalahan** | Proteksi dirty form via `useFormDirtyGuard`; konfirmasi dialog untuk aksi destruktif/hapus; disable button saat processing (`:disabled="form.processing"`). |
| 12 | **Mudah Diperbaiki** | Seluruh data utama menerapkan Soft Deletes (`deleted_at`); tombol **Pulihkan** selalu tersedia di tab Sampah (`FilterTrashData`). |
| 13 | **Feedback Instan** | Toast notifikasi (`ResourceMessage::CREATE_SUCCESS`, dll); skeleton loader saat async fetch; pesan validasi solutif di bawah input. |
| 14 | **Alur Kerja Adaptif** | Dukung stock opname tanpa hard-lock kaku; penerimaan bertahap (Partial GR); split bill kasir. |
| 15 | **Wording Profesional** | Sapaan akrab (*"Tokomu"*, *"Bisnismu"*, *"Yuk, tambah produk pertamamu!"*); hindari slang berlebihan (*"bgt"*, *"gak jelas"*). |

---

## 3. Matriks Standar UX Copywriting (Do's & Don'ts)

| Konteks | ❌ Dilarang (Kaku / Teknis / Slang) | ✅ Gunakan (Santai, Jelas, Profesional) |
| :--- | :--- | :--- |
| **Sapaan** | "Pengguna yang terhormat", "User" | "Kamu", "Tokomu", "Bisnismu" |
| **CTA Empty State** | "Silakan klik tombol di bawah untuk input" | "Yuk, tambah produk pertamamu!" |
| **Pesan Error Form** | "Field 'price' is required." | "Harga jual belum diisi nih." |
| **Konfirmasi Hapus** | "Apakah Anda yakin ingin mendelete record?" | "Hapus Produk Ini? Data akan dipindah ke sampah." |
| **Soft Delete** | "Data dimasukkan ke Recycle Bin." | "Data berhasil dipindah ke sampah." |
| **Restore Data** | "Record restored successfully." | "Data berhasil dikembalikan." |
| **Unduh File** | "File downloaded." | "Laporan berhasil diunduh." |

> Kamus lengkap dan pedoman wording per komponen dapat dilihat di `docs/ux-wording.md`.
