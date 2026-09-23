---
trigger: always_on
---

# Rule 11: Standarisasi Sistem Notifikasi & Isolasi Multi-Tenant

Pedoman baku pembuatan, pengiriman, dan manajemen notifikasi di **Sollu App** (Laravel 11, Reverb WebSocket, Vue 3, Inertia.js 1.2).

---

## 1. Prinsip Utama & Arsitektur Dasar

**1. Wajib Mewarisi `BaseNotification`**
Seluruh notifikasi baru di aplikasi **WAJIB** mewarisi abstract class `App\Notifications\BaseNotification` yang mengimplementasikan `ShouldQueue`.
**DILARANG KERAS** membuat notifikasi yang mewarisi langsung `Illuminate\Notifications\Notification` tanpa struktur data baku Sollu.

**2. Tiga Saluran Pengiriman Terintegrasi (`via`)**
- **`database`**: Menyimpan record ke tabel `notifications` untuk dibaca di riwayat popover lonceng.
- **`mail`**: Mengirimkan email formal (verifikasi, tagihan invoice, **password default karyawan baru**).
- **`broadcast`**: Mengirimkan push real-time (< 100ms) via Laravel Reverb & Echo ke private channel (`App.Models.User.{id}`, `outlets.{id}`, `businesses.{id}`).

---

## 2. Isolasi Target Notifikasi (Multi-Level Scoping)

Pengiriman notifikasi **WAJIB** menggunakan `App\Services\App\Notification\NotificationDispatcherService` untuk memastikan isolasi tenant dan akurasi status baca (*read state*):

| Level Target | Method Dispatcher | Target Penerima | Contoh Kasus |
| :--- | :--- | :--- | :--- |
| **Level User** | `NotificationDispatcherService::sendToUser($user, $notification)` | User spesifik | Ekspor/Impor file selesai, verifikasi email sukses, reset password, personal welcome. |
| **Level Business** | `NotificationDispatcherService::sendToBusiness($business, $notification, $roles = ['owner', 'manager'])` | Seluruh owner/manager di merchant tersebut | Pembayaran invoice lunas/gagal, invoice langganan terbit, trial/langganan akan habis, aktivasi outlet baru. |
| **Level Outlet** | `NotificationDispatcherService::sendToOutlet($outlet, $notification, $roles = null)` | Seluruh staf/kasir/manager yang terhubung ke outlet | Pesanan online non-POS masuk (GoFood, Grab, QR Meja), alert stok menipis, transfer stok masuk, permintaan approval opname. |

> [!CAUTION]
> **Larangan Keras:** Dilarang me-loop user secara manual tanpa scoping peran (`setPermissionsTeamId($business->id)`) saat mengirim notifikasi tingkat merchant/outlet. Gunakan selalu `NotificationDispatcherService`.

---

## 3. PHP Enums Single Source of Truth (No Magic Strings)

Seluruh kategori, tipe visual, dan scope notifikasi **WAJIB** menggunakan PHP Backed Enum terdaftar:

1. **`App\Enums\NotificationCategoryEnum`**:
   - `SYSTEM = 'system'`: Notifikasi operasional sistem, registrasi, onboarding, billing SaaS, status akun, dan ekspor/impor berkas.
   - `ORDER = 'order'`: Notifikasi pesanan non-POS, pesanan online, delivery integrasi, QR meja, dan alert void.
   - `INVENTORY = 'inventory'`: Notifikasi peringatan stok menipis (*low stock alert*), approval opname, dan transfer stok.
   - `EMPLOYEE = 'employee'`: Notifikasi aktivitas karyawan dan perubahan hak akses.
2. **`App\Enums\NotificationTypeEnum`**: `INFO = 'info'`, `SUCCESS = 'success'`, `WARNING = 'warning'`, `DANGER = 'danger'`.
3. **`App\Enums\NotificationScopeEnum`**: `USER = 'user'`, `BUSINESS = 'business'`, `OUTLET = 'outlet'`.

---

## 4. Format Baku Payload Notifikasi (`toDatabase` / `toArray`)

Setiap notifikasi menghasilkan struktur array JSON konsisten:
```php
[
    'category'    => $this->category->value,     // system | order | inventory | employee
    'type'        => $this->type->value,         // info | success | warning | danger
    'scope'       => $this->scope->value,        // user | business | outlet
    'business_id' => $this->businessId,          // uuid|null
    'outlet_id'   => $this->outletId,            // uuid|null
    'title'       => $this->title,               // Judul ringkas
    'message'     => $this->message,             // Pesan santai, komunikatif, to the point
    'action_url'  => $this->actionUrl,           // route('...') | null
    'action_text' => $this->actionText,          // 'Lihat Detail' | 'Unduh File' | null
    'expires_at'  => $this->expiresAt,           // ISO string | null (khusus file download)
    'meta'        => $this->meta,                // Array payload konteks tambahan
]
```

---

## 5. Alur Karyawan Baru & Password Default

Saat menambahkan karyawan baru di `EmployeeService`:
1. Sistem men-generate password default acak 10 karakter (`Str::random(10)`).
2. Mengirimkan notifikasi via `NewEmployee($defaultPassword)`:
   - **Saluran Email (`mail`)**: Template `mail.employee.new` memuat detail login dan **Password Default Sementara**.
   - **Saluran In-App (`database` & `broadcast`)**: Menampilkan notifikasi selamat datang di dashboard dengan tombol aksi langsung menuju halaman ubah password & PIN (`route('settings.account.profile')`).

---

## 6. Standar Frontend UI (Popover 4 Tab Ergonomis)

1. **Struktur Tab**: Wajib mempertahankan 4 tab berimbang: **Semua**, **Sistem**, **Pesanan**, dan **Stok**.
2. **Layout Simetris**: Menggunakan `grid grid-cols-4 gap-1 p-1` pada kontainer 416px (`w-[26rem]`) dengan ukuran teks `text-[11px] sm:text-xs` (**DILARANG** merusak layout yang menyebabkan text-wrapping atau overflow horizontal).
3. **Live Sync Echo**: Dengarkan private channel `App.Models.User.{id}` untuk menambahkan notifikasi real-time dan meng-increment badge unread tanpa reload halaman.

---

## 7. Strategi Retensi Database & Scheduler Pruning

Untuk mencegah database overload seiring pertambahan volume notifikasi:
- **Kebijakan Pruning**:
  - Notifikasi yang sudah dibaca (`read_at IS NOT NULL`) otomatis dihapus setelah berumur **$\ge 1\text{ tahun}$ (365 hari)**.
  - Notifikasi berkas ekspor/impor kedaluwarsa (`expires_at`) otomatis dihapus setelah **30 hari**.
  - Notifikasi usang tak pernah dibaca otomatis dihapus setelah **2 tahun**.
- **Otomatisasi Cron**:
  - Command: `php artisan notifications:prune --days=365`
  - Terdaftar di `routes/console.php` berjalan harian pukul **02:30 WIB** dengan *chunked batch deletion* (1.000 baris per batch).
