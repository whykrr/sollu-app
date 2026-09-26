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
3. **SSOT Outlet Resolution (Dilarang Membaca Query Mentah):**
   - **DILARANG KERAS** membaca mentah `$request->get('outlet_id')` atau `$request->outlet` langsung ke Query Builder tanpa validasi.
   - **WAJIB** menggunakan `SelectedOutlet::resolveEffectiveOutletId($request->user(), $request->input('outlet_id') ?: $request->input('outlet'))`.
4. **Hirarki Prioritas Resolusi Outlet:**
   - **Prioritas 1 (Sidebar Master Scope):** Jika user memilih outlet spesifik di sidebar, ID tersebut mutlak dipakai (mengabaikan parameter URL liar).
   - **Prioritas 2 (Semua Outlet Scope):** Jika sidebar = "Semua Outlet", parameter URL divalidasi ke daftar accessible outlet user. Nilai di luar hak akses user/tenant otomatis di-sanitize menjadi `null`.
5. **Penerapan Scope Eloquent & Mode Semua Outlet:**
   - Model yang berelasi dengan outlet wajib menggunakan trait `HasOutlet` (`scopeSelectedOutlet($filter)`).
   - Saat dalam mode "Semua Outlet", query **WAJIB** dibatasi pada `whereIn('outlet_id', $userAccessibleOutletIds)` milik user aktif, dilarang membuka ke seluruh database.
6. **Zero Redundant DB Query & In-Memory Memoization:**
   - Dilarang query berulang ke tabel `outlets`. Gunakan in-memory memoization pada `SelectedOutlet::make($user)->get()` dan cache `SummaryUser`.
7. **Clean Navigation & State Reset:**
   - Endpoint switch outlet wajib membersihkan stale query params (`outlet`, `outlet_id`, `page`) dari referer URL sebelum redirect.
   - Komponen switch outlet di Vue (`SidebarOutlet.vue`) wajib menggunakan `:preserve-state="false"` agar seluruh filter dan pagination halaman di-reset bersih.


---

## 3. 🚨 Larangan Keras (Anti-Patterns)
- **Bypass Resolusi Outlet:** Dilarang menggunakan `$request->get('outlet_id')` atau `$request->outlet` secara langsung di controller untuk menyaring data. Wajib menggunakan `SelectedOutlet::resolveEffectiveOutletId()` untuk mencegah pembobolan tenant/outlet.
- **Mutasi Lintas Modul:** Modul A **DILARANG KERAS** melakukan `insert`, `update`, atau `delete` langsung ke tabel/Model milik Modul B. Gunakan Domain Event/Listener atau Public Service Contract.
- **Import UI Privat Lintas Modul:** File Vue Modul A dilarang mengimpor komponen privat dari folder Modul B. Pindahkan ke `@/Components/` (global) jika digunakan bersama.
- **God Service:** Dilarang membuat class Service yang menangani logika bisnis lintas domain berbeda.
- **Over-Fetching di `index()`:** Dilarang me-load relasi berat (*items, recipe, logs*) atau master data massal pada method `index()` Inertia. Data detail lengkap wajib dimuat secara *on-demand* via endpoint `show()` / PopUp drawer.

---

## 4. Pola Komunikasi Antar-Modul
1. **Domain Events & Listeners (Prioritas Utama):** Modul A men-dispatch `Event`, Modul B menangkap via `Listener` dan memodifikasi datanya sendiri secara independen.
2. **Public Service Contract:** Digunakan untuk verifikasi/kalkulasi sinkron. Mengembalikan DTO/primitif, **BUKAN** Eloquent Model kotor atau Query Builder mentah.
3. **Reports & Analytics (Read-Only):** Hanya modul analitik/laporan yang diizinkan membaca query lintas modul untuk agregasi. Modul laporan dilarang mutasi data.
