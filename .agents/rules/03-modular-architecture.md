---
trigger: always_on
---

# Rule 03: Arsitektur Modular Monolith & Isolasi Sistem

## 1. Bounded Contexts & Prinsip Modularitas

Aplikasi dibagi ke dalam modul-modul mandiri (*Bounded Contexts*) seperti Inventory, Master, Sales, Customer, Employee, Notification, Billing, dll. Setiap modul harus memiliki batasan yang tegas, dapat diuji secara independen, dan direfaktor tanpa merusak modul lainnya.

---

## 2. Isolasi Data Multi-Tenant & Multi-Outlet

1. **Scoping Data:** Seluruh data operasional bisnis wajib terisolasi berdasarkan `business_id` (dan `outlet_id` jika berlaku).
2. **Kueri Terisolasi:** Dilarang mengambil atau memanipulasi data tanpa membatasi scope `business_id` tenant aktif.
3. **Session Context:** Identitas merchant dan outlet aktif diambil dari context user terotentikasi, bukan dipercaya mentah-mentah dari input payload client.

---

## 3. 🚨 Larangan Keras (Anti-Patterns)

- **Mutasi Lintas Modul:** Modul A **DILARANG KERAS** melakukan operasi `insert`, `update`, atau `delete` langsung ke tabel/Model milik Modul B. Gunakan *Domain Event* atau panggil *Public Service*.
- **Import UI Lintas Modul:** File Vue pada Modul A dilarang mengimpor komponen privat (`/Components/`) dari dalam folder Modul B. Pindahkan komponen tersebut ke `@/Components/` (global) jika digunakan bersama.
- **God Service:** **DILARANG** membuat class Service yang menangani logika bisnis lintas domain berbeda.
- **Routing Berantakan:** Route **WAJIB** dikelompokkan dan didaftarkan pada `routes/app/{module}.php`. Dilarang menaruh route fitur langsung di `routes/app.php` tanpa modularisasi.
- **Over-Fetching di Index:** **DILARANG** me-load relasi berat (*children, items, recipe, logs*) atau master lookup massal pada method `index()` Inertia. Data detail lengkap dan opsi formulir **WAJIB** dimuat secara *on-demand* via endpoint `show()` atau async API saat Drawer/PopUp dibuka.

---

## 4. Komunikasi Antar Modul

1. **Events & Listeners (Rekomendasi Utama):**
   - Modul A me-trigger `Event`, Modul B menangkapnya melalui `Listener` dan memodifikasi datanya sendiri secara independen (cocok untuk *side effects*, logging, mutasi stok akibat penjualan, dan notifikasi).
2. **Public Service Contract:**
   - Digunakan untuk kebutuhan kalkulasi atau verifikasi sinkron. Modul tujuan **WAJIB** mengembalikan data primitif atau DTO (*Data Transfer Object*), **BUKAN** Query Builder mentah atau Model dalam kondisi kotor (*dirty state*).
3. **Reports & Analytics (Read-Only):**
   - Hanya modul analitik/laporan yang diizinkan membaca data (query) lintas modul untuk agregasi. Modul laporan **DILARANG KERAS** melakukan mutasi data.

---

## 5. Struktur Anatomi File Terstandarisasi

- **Backend:**
  - `Controllers`, `Services`, `Events`, `Listeners`, `Models`, dan `Requests` diletakkan dalam namespace terstruktur: `App\...\App\{Module}`.
  - Public Service yang diekspos ke modul lain harus memiliki antarmuka yang jelas dan terdokumentasi tipe datanya.
- **Frontend:**
  - `resources/js/Pages/App/{Module}/Components/` bersifat **PRIVAT** untuk modul tersebut saja (contoh: `{Entity}FormPopUp.vue`, `{Entity}DetailPopUp.vue`, `{Entity}Filter.vue`).
  - Seluruh toolbar filter dan aksi halaman **WAJIB** diekstrak ke dalam komponen privat modul ini.
- **Testing:**
  - Unit test Service **WAJIB** 100% Mocking (`tests/Unit/Services/{Module}`).
