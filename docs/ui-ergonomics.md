# Standar UI Ergonomis Multi-Perangkat (Laptop, Tablet, Smartphone)

Dokumen ini adalah pedoman dan standar teknis resmi untuk desain antarmuka pengguna (UI) **Sollu App** lintas perangkat: **Laptop/Desktop**, **Tablet (POS Terminal/iPad)**, dan **Smartphone/Mobile**.

---

## 1. Filosofi & Prinsip Desain Ergonomis

Aplikasi **Sollu App** digunakan oleh berbagai profil pengguna dalam berbagai kondisi kerja:
1. **Admin / Pemilik Bisnis di Laptop:** Fokus pada efisiensi kerja cepat, navigasi keyboard/mouse, kerapatan data (*high data density*), dan analisis mendalam.
2. **Kasir / Staf di Tablet / POS Terminal:** Fokus pada kecepatan sentuhan jari (*finger tap*), target sentuh besar, pencegahan salah klik (*miss-tap*), dan ketahanan terhadap orientasi layar (*landscape* / *portrait*).
3. **Pemilik / Manajer di Smartphone:** Fokus pada kemudahan satu tangan (*one-hand / Thumb Zone*), keterbacaan cepat, formulir yang tidak memicu *auto-zoom* browser, dan alur drawer/bottom-sheet yang mulus.

---

## 2. Matrix Breakpoint & Device Form Factors

| Breakpoint Tailwind | Resolusi Layar | Target Perangkat | Layout Mode | Sidebar Behavior | Drawer Mode |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`< sm` (`< 640px`)** | 360px - 480px | Smartphone (iPhone, Android) | Single Column (Vertical Stack) | Off-canvas Drawer (Overlay) | Full Screen / Bottom Sheet (`100dvh`) |
| **`sm` - `md` (`640px - 767px`)** | 640px - 768px | Tablet Portrait, Mini Tablet | Compact Fluid | Off-canvas / Slim Rail | Large Side Drawer (85% width) |
| **`md` - `lg` (`768px - 1023px`)** | 768px - 1024px | Tablet Landscape, Small Laptop | Hybrid Grid | Slim Icon Rail (Collapsible) | Right Side Drawer (`max-w-xl`) |
| **`lg` - `xl` (`1024px - 1279px`)** | 1024px - 1280px | Standard Laptop (13"-14") | Full Desktop Multi-column | Expanded Default (`w-64`) | Right Side Drawer (`max-w-2xl`) |
| **`>= 2xl` (`>= 1536px`)** | 1536px - 4K | Desktop Monitor / Wide Screen | Ultra Wide Balanced | Expanded (`w-72`) | Right Side Drawer (`max-w-3xl`) |

---

## 3. Standar Target Sentuh & Ergonomi (Touch Target Rules)

### 3.1. Ukuran Minimum Target Sentuh (Fitts's Law)

- **Laptop & Desktop (Mouse / Trackpad Pointer):**
  - Ukuran kontrol minimum: `28px × 28px` atau `30px` tinggi standar (`.form.sm`, `.btn-sm`).
- **Tablet (Jari Sentuh / Stylus):**
  - Ukuran kontrol minimum: `36px × 36px` (`.touch-target-sm`) dengan *hit-area* yang nyaman untuk jari staf kasir.
- **Smartphone / Mobile (Jempol / Jari):**
  - Ukuran kontrol minimum: `44px × 44px` (`.touch-target` / `.touch-target-lg`) untuk tombol aksi utama atau area tap sentuh.

### 3.2. Zona Jangkauan Jempol (Thumb Zone Ergonomics)

```
       MOBILE THUMB ZONE                    TABLET ERGONOMIC ZONE
  ┌─────────────────────────┐            ┌─────────────────────────────┐
  │  [  Hard to Reach  ]    │ ◄── Top    │ [Hard]     [Medium]   [Hard]│
  │                         │     Bar    │                             │
  │  [  Comfortable    ]    │            │ [Comfortable] [Comfortable] │
  │                         │            │  (Left Thumb) (Right Thumb) │
  │  [  NATURAL THUMB  ]    │ ◄── Bottom │                             │
  │  [      ZONE       ]    │     Sticky │ [   NATURAL REACH ZONE    ] │
  └─────────────────────────┘            └─────────────────────────────┘
```

1. **Bottom Sticky Actions:** Tombol aksi formulir utama (Simpan, Bayar, Konfirmasi) di mobile WAJIB diletakkan di bagian bawah *sticky footer* (`#popUpFooter`), bukan tersembunyi di atas layar.
2. **Safe Area Insets:** Seluruh elemen melayang/sticky di bagian bawah WAJIB menyertakan padding safe area: `pb-[max(0.75rem,env(safe-area-inset-bottom,0px))]` (`.safe-pb`) untuk menghindari terpotong oleh bilah gestur iOS/Android.

---

## 4. Standar Formulir & Input Pencegah Auto-Zoom iOS

### 4.1. Aturan Font 16px di Mobile (`< sm`)
Browser mobile (terutama iOS Safari) secara otomatis melakukan *zoom in* paksa jika pengguna menekan input dengan ukuran font `< 16px`. Untuk mencegah layout rusak saat mengetik:

- Gunakan modifier `.form.adaptive` atau utility `text-base sm:text-xs` pada seluruh kontrol input formulir.
- Tinggi input formulir di mobile: `min-h-[42px]` (`text-base`), dan otomatis menjadi `h-[30px]` (`text-xs`) pada viewport laptop/desktop (`sm+`).

### 4.2. Selection Group & Radio/Checkbox Pills
- Pada mobile & tablet, opsi pilihan grup (`SelectionGroupField`) menggunakan gap `gap-1.5` dan padding vertikal `py-1.5 px-2.5` agar mudah disentuh tanpa salah memilih opsi sebelah.

---

## 5. Standar Komponen Inti Lintas Perangkat

### 5.1. `MainPageHeader`
- **Desktop (`sm+`):** Judul di kiri, tombol aksi sejajar di kanan (`flex-row justify-between`).
- **Mobile (`< sm`):** Judul di baris atas, tombol aksi wrap di baris bawah dengan scrolling horizontal halus jika terdapat banyak tombol.

### 5.2. `FilterBar`
- **Desktop:** Layout 1 baris (`flex-row`), filter track di kiri (`flex-1`), search dan actions di kanan (`md:w-64`).
- **Mobile:** Layout 2 baris teratur:
  - Baris 1: Search bar penuh (`order-1 w-full`) dan tombol aksi (Ekspor/Impor).
  - Baris 2: Filter track horizontal (`order-2`) dengan drag-to-scroll dan fade masks kiri-kanan.

### 5.3. `Table`
- **Desktop (`md+`):** Menampilkan seluruh kolom data lengkap dengan sortable headers.
- **Mobile (`< md`):** Kolom sekunder disembunyikan menggunakan konfigurasi `show: 'md'` atau `show: 'lg'` pada array `headers`, sehingga di smartphone hanya menampilkan kolom esensial (Nomor/Nama, Status, Aksi).

### 5.4. `PopUpPage`
- **Desktop & Tablet Landscape:** Panel drawer kanan dengan lebar proporsional (`sm:max-w-lg`, `sm:max-w-xl`, `lg:max-w-2xl`, `lg:max-w-4xl`).
- **Smartphone:** Tampil penuh `w-full h-[100dvh] rounded-none` dengan momentum scrolling vertikal, safe-area insets di header dan footer, serta tombol submit di zona jempol.

---

## 6. Checklist Verifikasi UI Ergonomis

Sebelum menyelesaikan fitur atau komponen baru, AI Agent WAJIB memverifikasi:
- [ ] Apakah target sentuh tombol dan link di mobile minimal `36px - 44px`?
- [ ] Apakah input formulir bebas dari *auto-zoom* saat disentuh di perangkat seluler?
- [ ] Apakah tabel data tidak mengalami *overflow* horizontal berlebihan di smartphone?
- [ ] Apakah footer aksi drawer (`#popUpFooter`) terlihat jelas dan berada di atas *home bar* gesture?
- [ ] Apakah filter bar dapat digeser (*swipe/drag*) dengan lancar di layar sentuh?
