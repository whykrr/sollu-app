# Rule 03: PHP Enums SSOT & Dual-Layer Otorisasi

## 1. PHP Enums sebagai Single Source of Truth (No Magic Strings)
- **Backend adalah Master:** Seluruh status, tipe, dan kategori didefinisikan di `app/Enums/*.php`. Dilarang menggunakan *magic strings* di Vue (anti-pattern: `v-if="status === 'draft'"`).
- **Distribusi ke Frontend:** Otomatis melalui Inertia Shared Props (`$enums`). Daftarkan enum baru ke `FrontendEnumProvider.php`.
- **Penggunaan di Vue:**
  - Template: `$enums.AdjustmentStatus.Draft`
  - Script: `const { enums, getOptions, getLabel, getColor } = useEnum()`
  - Dropdown Options: `:options="getOptions('AdjustmentReason')"` (Dilarang me-hardcode opsi di Vue).
- **Entitas Dinamis (Bukan Enum):** `BusinessType` bersifat 100% dinamis di DB (`business_types`). Dilarang membuat `BusinessTypeEnum`. Ambil via `BusinessType::getAllCached()` / `BusinessType::options()`.
- **Zero-Orphan Permission & Role Template:**
  - Setiap case baru pada `PermissionEnum` WAJIB memiliki method `label()`, `group()`, `groupLabel()`.
  - WAJIB dipetakan ke template peran POS di `RoleTemplateEnum` dan lolos `tests/Unit/Enums/RoleTemplateIntegrityTest.php`.
- **Larangan Keras Wildcard Permissions (`.*`):**
  - DILARANG KERAS menggunakan permission wildcard (`business.*`, `product.*`, `transaction.*`, dsb) di database, seeders, route, controller, form request, maupun test.
  - Seluruh permission harus eksplisit terdaftar di `PermissionEnum`. Pada antarmuka kustomisasi peran, seluruh permission dalam satu kategori/grup dipilih sebagai array item eksplisit.
- **Otorisasi Akun Utama (Root User):**
  - Otorisasi mutasi level entitas bisnis (profil toko, logo, konfigurasi SoD, dan penentuan metode biaya inventori) serta hak bypass Segregation of Duties (SoD) murni menggunakan verifikasi status Akun Utama: `$user->is_root_user === true`.
  - Permission `business.setting` dan `business.update` telah ditiadakan.
- **Standardisasi Namespace Permission:**
  - Kategori Produk: `product.category.{view,create,update,delete}` (bukan `category.*`).
  - Pemasok & Pembelian: `purchase.supplier.{view,create,update,delete}` dan `purchase.{view,create,update,validate,delete,receive,return}` (bukan `purchase_order.*`). Aksi cancel & void dipetakan ke `purchase.delete`.
  - Penyesuaian Inventori: `inventory.adjust.{view,create,update,validate,delete}` (bukan `inventory.adjustment.*`).
  - Katalog Jasa: Menggunakan hak akses `product.*` (karena `service.*` telah dieliminasi total).
  - Kasir & Transaksi: Mempertahankan `transaction.override_price` dan menambahkan `transaction.validation_supervision` untuk otorisasi supervisor.

---

## 2. Dual-Layer Authorization (User RBAC vs SaaS Feature Plan)

> [!NOTE]
> Untuk analisa komprehensif 6 lapisan pembatasan (*The 6-Layer Stack*: Tenant Lifecycle $\rightarrow$ BusinessType $\rightarrow$ Plan $\rightarrow$ Personalization $\rightarrow$ Outlet Guard $\rightarrow$ RBAC) dan formula evaluasi instan tanpa scan seluruh kode, aktifkan domain skill `domain-access-and-feature-gating`.

```
┌────────────────────────────────────────────────────────────────────────┐
│                      Dual-Layer Authorization                          │
├─────────────────────┬───────────────────┬──────────────────────────────┤
│ Dimensi             │ User RBAC         │ Tenant SaaS Feature Plan     │
├─────────────────────┼───────────────────┼──────────────────────────────┤
│ Entitas             │ User / Employee   │ Business / Tenant            │
│ Source of Truth     │ PermissionEnum    │ FeatureEnum                  │
│ Logika Tampilan     │ Sembunyikan (Hide)│ Masking Upsell / Hide Matriks│
│ Directive Frontend  │ v-can             │ v-feature                    │
│ Middleware Backend  │ permission:...    │ plan.feature:...             │
└─────────────────────┴───────────────────┴──────────────────────────────┘
```

1. **User RBAC (Hak Akses Jabatan):**
   - Mengatur kewenangan per user (`PermissionEnum`, `v-can`).
   - Jika tidak ada hak akses: **Sembunyikan elemen visual** secara aman dari VDOM (`display: none !important` + `aria-hidden="true"`).
   - Operasional umum (ekspor/impor) diatur murni via RBAC (`permission: product.export`), bukan via plan feature.
2. **Tenant Feature (Fitur Paket Berlangganan):**
   - Mengatur kapabilitas bisnis sesuai paket SaaS yang aktif (`FeatureEnum`, `v-feature`).
   - **Dilarang Komponen Wrapper:** Komponen `<FeatureLock>` dan `<FeatureLockOverlay>` telah dieliminasi total. Seluruh pembatasan visual wajib menggunakan directive `v-feature`.
   - **Prinsip Gating Dualitas:**
     - **Navigasi & Sidebar:** WAJIB menggunakan modifier `v-feature.hide="$enums.FeatureEnum.NAME"` (langsung sembunyikan jika tidak aktif, tanpa masking/gembok).
     - **Content Page:**
       - *HIDE:* Fitur di luar jenis usaha ATAU merchant sudah menyimpan personalisasi (`is_personalized = true` dan fitur mati).
       - *MASKING:* Merchant belum personalisasi, fitur valid untuk jenis usaha, tetapi paket belum mencakup. Overlay gembok muncul otomatis.
   - **Capture-Phase Trapping:** Pada status `masked`, event interaksi (`click`, `keydown`) di-trap pada level container (`capture: true`) dan otomatis membuka `FeatureLockedModal` di belakang layar. Dilarang menulis pengecekan manual dan modal trigger di script komponen.
   - **Pengecekan di Skrip:** Jika membutuhkan sanitasi data form sebelum submit, gunakan `useAuth().hasFeature($enums.FeatureEnum.NAME)`.
   - Backend route protection: `middleware('plan.feature:' . FeatureEnum::NAME->value)`.
   - **Multi-Fitur pada Route Middleware (OR Logic):** Mendukung beberapa fitur dipisah koma (`plan.feature:product_catalog,service_catalog`). Akses diizinkan jika bisnis memiliki salah satu fitur tersebut.
   - Dilarang keras mencampuradukkan pengecekan permission untuk membatasi fitur paket.
3. **Tenant & Subscription Caching Standard (Zero-Redundant Query):**
   - **Gunakan `Business::findCached($id)`:** Dilarang melakukan *lazy-loading* `$user->business` berulang di middleware/request filter. Gunakan `$user->relationLoaded('business') ? $user->business : ($user->business_id ? Business::findCached($user->business_id) : null)`.
   - **Multi-Tier Feature Gating:**
     - **Tier 1 (Fast-Path):** Backend middleware `plan.feature` membaca `SummaryUser::make($user)->cached()['features']` yang telah mencakup overlap personalisasi fitur bisnis (`settings['active_features']`) dan fitur paket langganan aktif.
     - **Tier 2 (Fallback):** Model-level cache `Business::findCached()` $\rightarrow$ `Business::activePlanFeatures()` (`business:{$id}:active_plan_features`).
   - **Cache Invalidation:** Seluruh mutasi `Business`, `Subscription`, `SubscriptionPlan`, `Feature`, dan `BusinessType` wajib terdaftar di `UserCacheObserver` yang otomatis memanggil `Business::clearCache($businessId)` dan `SummaryUser::cacheDelete($userId)`.

---

## 3. Standar Penempatan Otorisasi & Caching (Zero-Redundancy)

1. **Single Point of Enforcement:**
   - **Endpoint dengan FormRequest:** Otorisasi RBAC WAJIB di `FormRequest::authorize()`. Dilarang memanggil ulang `$this->authorize(...)` di Controller.
   - **Endpoint tanpa FormRequest:** Otorisasi RBAC WAJIB di baris pertama Controller (`$this->authorize(PermissionEnum::...->value)`).
2. **Runtime Memoization & Service SSOT:**
   - Seluruh cek hak akses RBAC melalui Laravel Gate (`$user->can(...)` / `$this->authorize(...)`) yang di-handle oleh `UserPermissionCacheService`.
   - Dilengkapi runtime in-memory memoization per request + Redis (`CACHE_TTL = 3600`).
3. **Granular Invalidation (`UserCacheObserver`):**
   - Model `User`: Hanya hapus permission cache jika kolom struktural otorisasi berubah (`business_id`, `is_root_user`, `deleted_at`). Mutasi profil/login (`last_login_at`, `photo`, `name`, `pin`, `password`) HANYA menghapus `SummaryUser`.
   - Model `Outlet`: Dilarang menghapus permission cache user.
   - Model `Role` & `Permission`: Tetap invalidasi penuh (`clearBusinessPermissions` / `clearRuntimeCache`).

---

## 4. Arsitektur Multi-Tenant User, Otentikasi Kasir & Reset Sandi Instan

### A. Scoped Uniqueness & Penanganan Nullable di PostgreSQL
1. **Unik Komposit Per Bisnis:** Kolom `email` dan `phone` pada tabel `users` **DILARANG** menggunakan unique global. Wajib menggunakan unique komposit: `['business_id', 'email']` dan `['business_id', 'phone']`.
2. **Karakteristik Nullable PostgreSQL:** Kolom `email` dan `password` bersifat `nullable()`. Di PostgreSQL, multiple baris bernilai `NULL` pada unique index komposit **tidak saling melanggar constraint**, memungkinkan penambahan kasir tanpa email secara tak terbatas dalam bisnis yang sama.
3. **Turnover Karyawan & Registrasi Bisnis Baru:** Karyawan lama dengan email terdaftar di bisnis lain bebas mendaftarkan bisnis barunya sendiri. `RegisterRequest` HANYA menolak jika email/phone adalah milik Akun Utama aktif (`is_root_user = true` dan `deleted_at IS NULL`).

### B. Pemisahan Kasir POS vs Staf Web Backoffice
1. **Kasir POS Murni (`has_web_access = false`):**
   - Disimpan dengan `email = null` dan `password = null`.
   - Otentikasi murni via Device Lock + PIN 6 digit di perangkat POS.
   - **Blokir Akses Web:** Kasir dengan `password = null` otomatis ditolak saat mencoba login di web dashboard (`LoginController`).
2. **Staf Web Backoffice (`has_web_access = true`):**
   - Wajib menyertakan `email`.
   - Password default dibuat otomatis via `EmployeeService::generateDefaultPassword()` dengan format **Nama Kota di Indonesia + 4 digit angka acak** (misal: `Bandung4829`, `Surabaya1024`) dan dikirimkan via email saat registrasi baru.

### C. Reset Kata Sandi Instan (Zero-Email Dispatch)
1. **Endpoint Khusus:** `POST /employees/{user}/reset-password`.
2. **Proteksi Akun Utama:** Dilarang mereset password Akun Utama (`is_root_user = true`) melalui endpoint staf (melempar HTTP 422).
3. **Tanpa Notifikasi Email:** Reset kata sandi staf langsung mengupdate hash di database, mencatat audit log (`AuditModuleEnum::EMPLOYEES`), dan mengembalikan plain password dalam respons JSON `{ success: true, password: "..." }` tanpa memicu mailer/notifikasi email.
4. **Smart Tenant Selector:** Jika 1 email terdaftar di lebih dari satu bisnis dengan kata sandi yang cocok, alur login web mengarahkan ke antarmuka pemilihan tenant (`User/SelectBusiness.vue`) sebelum mengautentikasi sesi bisnis.

