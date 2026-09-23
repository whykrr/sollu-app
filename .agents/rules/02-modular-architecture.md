# Rule 02: Arsitektur Modular Monolith & Isolasi Tenant

## 1. Bounded Contexts & Prinsip Modularitas
Aplikasi dibagi ke dalam modul-modul mandiri (*Bounded Contexts*): Inventory, Master, Sales, Customer, Employee, Notification, Billing, Audit, dll.
- Backend: `App\Http\Controllers\App\{Module}\`, `App\Services\App\{Module}\`, `App\Models\{Module}\`.
- Frontend: `resources/js/Pages/App/{Module}/` (folder `Components/` di dalamnya bersifat **PRIVAT** untuk modul tersebut).
- Routing: Route modul **WAJIB** berada di `routes/app/{module}.php`. Dilarang menumpuk route di `routes/app.php`.

---

## 2. Isolasi Data Multi-Tenant & Multi-Outlet
1. **Scoping Data:** Seluruh data operasional bisnis WAJIB terisolasi berdasarkan `business_id` (dan `outlet_id` jika berlaku).
2. **Kueri Terisolasi:** Dilarang query data tanpa membatasi scope `business_id` tenant aktif.
3. **Session Context:** Identitas merchant dan outlet aktif diambil dari context user terotentikasi, bukan dipercaya mentah-mentah dari input payload client.

---

## 3. 🚨 Larangan Keras (Anti-Patterns)
- **Mutasi Lintas Modul:** Modul A **DILARANG KERAS** melakukan `insert`, `update`, atau `delete` langsung ke tabel/Model milik Modul B. Gunakan Domain Event/Listener atau Public Service Contract.
- **Import UI Privat Lintas Modul:** File Vue Modul A dilarang mengimpor komponen privat dari folder Modul B. Pindahkan ke `@/Components/` (global) jika digunakan bersama.
- **God Service:** Dilarang membuat class Service yang menangani logika bisnis lintas domain berbeda.
- **Over-Fetching di `index()`:** Dilarang me-load relasi berat (*items, recipe, logs*) atau master data massal pada method `index()` Inertia. Data detail lengkap wajib dimuat secara *on-demand* via endpoint `show()` / PopUp drawer.

---

## 4. Pola Komunikasi Antar-Modul
1. **Domain Events & Listeners (Prioritas Utama):** Modul A men-dispatch `Event`, Modul B menangkap via `Listener` dan memodifikasi datanya sendiri secara independen.
2. **Public Service Contract:** Digunakan untuk verifikasi/kalkulasi sinkron. Mengembalikan DTO/primitif, **BUKAN** Eloquent Model kotor atau Query Builder mentah.
3. **Reports & Analytics (Read-Only):** Hanya modul analitik/laporan yang diizinkan membaca query lintas modul untuk agregasi. Modul laporan dilarang mutasi data.
