---
name: domain-audit-log
description: >-
    Knowledge and standards for Audit Log & Activity Tracking Domain in Sollu App. Covers ActivityLoggerInterface,
    RecordActivityLogJob, AuditModuleEnum, table partitioning, event matrices, and partition pruning scheduler.
    Activate whenever adding audit trails, logging user actions, managing activity logs, or working on compliance/audit data.
---

# Domain Skill: Modul Audit Log & Jejak Aktivitas

Panduan dan aturan baku rekayasa perangkat lunak khusus **Modul Audit Log (Jejak Aktivitas)** pada **Sollu App**.

---

## 1. Prinsip Utama & Bounded Context

1. **Modul Mandiri (*Standalone Bounded Context*):**
   - Seluruh logika pencatatan log audit terisolasi di dalam namespace `App\Services\App\Audit\`, `App\Models\Audit\`, `App\Contracts\Audit\`, dan `App\Jobs\Audit\`.
   - Modul lain **DILARANG KERAS** melakukan query `insert`/`update`/`delete` langsung ke tabel `audit.activity_logs`.
2. **Komunikasi Tunggal via Interface Contract:**
   - Semua modul wajib menyuntikkan (*inject*) `App\Contracts\Audit\ActivityLoggerInterface` untuk mencatat peristiwa.
3. **Pencatatan Asinkron (*Zero POS Latency*):**
   - Secara default, pemanggilan `ActivityLoggerInterface::log(...)` men-dispatch background job `RecordActivityLogJob` ke antrean (Queue) agar transaksi pengguna tidak terhambat.
4. **Isolasi Multi-Tenant & Multi-Outlet:**
   - Setiap baris log wajib memiliki `business_id` (dan `outlet_id` jika terjadi di lingkup cabang). Service secara otomatis mendeteksi tenant dan outlet aktif dari sesi pengguna.

---

## 2. Standar Skema Database & Table Partitioning

1. **Skema PostgreSQL `audit`:**
   - Tabel master adalah `audit.activity_logs` yang dipartisi secara bulanan (*Range Partition on `created_at`*).
2. **Kolom Wajib:**
   - `id`: UUID
   - `business_id`: UUID (relasi ke `businesses`)
   - `outlet_id`: UUID nullable (relasi ke `outlets`)
   - `causer_type` & `causer_id`: Polimorfik pelaku (User/Employee)
   - `subject_type` & `subject_id`: Polimorfik entitas yang terdampak (Order, Item, Promo, dll)
   - `module`: String enum (`AuditModuleEnum`)
   - `action`: String enum / identifier (`AuditActionEnum` atau format `domain.action`)
   - `description`: Kalimat human-readable yang jelas dalam Bahasa Indonesia santai & profesional
   - `properties`: JSON payload berformat standar `['old' => [...], 'new' => [...], 'metadata' => [...]]`
   - `ip_address` & `user_agent`: Otomatis diambil dari request HTTP
3. **Compound Indexes:**
   - `(business_id, created_at DESC)`
   - `(business_id, module, created_at DESC)`
   - `(business_id, outlet_id, created_at DESC)`
   - `(business_id, causer_id, created_at DESC)`

---

## 3. Matriks Standard Event Modul & Format Aksi

| Modul (`module`) | Aksi Baku (`action`) | Contoh Deskripsi (`description`) |
| :--- | :--- | :--- |
| **`pos_and_transactions`** | `shift.opened`, `shift.closed` | *"Buka shift kasir dengan modal awal Rp 200.000"* |
| | `cash_drawer.in`, `cash_drawer.out` | *"Setor kas masuk ke laci kasir sebesar Rp 150.000"* |
| | `transaction.voided`, `refunded` | *"Void struk pesanan #TRX-202609-0012"* |
| | `transaction.discounted` | *"Pemberian diskon khusus kasir 20%"* |
| | `invoice.issued`, `payment_recorded` | *"Menerbitkan invoice tempo #INV-9921"* |
| **`inventory_and_supply`** | `adjustment.created`, `approved`, `voided` | *"Menyetujui penyesuaian stok bahan baku"* |
| | `opname.created`, `approved`, `rejected` | *"Finalisasi stock opname bulanan Gudang Utama"* |
| | `transfer.created`, `approved`, `shipped`, `received` | *"Kirim transfer stok 50 item ke Cabang Tebet"* |
| | `po.created`, `ordered`, `cancelled`, `voided` | *"Penerbitan PO #PO-8812 ke Supplier Jaya"* |
| | `goods_receipt.received`, `voided` | *"Penerimaan fisik barang PO #PO-8812"* |
| | `purchase_return.created`, `voided` | *"Retur 5 barang rusak ke supplier"* |
| | `stock.frozen`, `stock.unfrozen` | *"Membekukan transaksi stok Outlet Pusat untuk audit"* |
| **`products_and_menu`** | `product.created`, `updated`, `deleted` | *"Mengubah harga jual produk Kopi Susu Aren"* |
| | `category.created`, `updated`, `deleted` | *"Menambahkan kategori baru: Minuman Dingin"* |
| **`promotions_and_crm`** | `promo.created`, `published`, `unpublished` | *"Mempublikasikan promo Diskon Akhir Pekan 15%"* |
| | `customer.created`, `updated`, `deleted` | *"Pendaftaran member pelanggan baru"* |
| **`employees_and_roles`** | `employee.created`, `updated`, `deactivated` | *"Menambahkan staf baru: Siti Aminah (Kasir)"* |
| | `role.created`, `updated`, `deleted` | *"Mengubah izin hak akses peran Supervisor"* |
| **`settings_and_business`**| `business.updated` | *"Memperbarui informasi profil usaha"* |
| | `tax.updated`, `receipt.updated` | *"Mengubah pengaturan tarif pajak resto menjadi 11%"* |
| | `device.registered`, `unpaired` | *"Menghubungkan tablet POS baru: Kasir Depan"* |
| | `costing_method.switched`, `sod.updated` | *"Mengubah metode kalkulasi HPP persediaan ke FIFO"* |
| **`auth_and_security`** | `auth.login`, `auth.logout`, `auth.failed` | *"Pengguna berhasil masuk ke sistem"* |
| | `password.changed`, `pin.changed` | *"Memperbarui kata sandi akun"* |

---

## 4. Contoh Penggunaan di Service Layer

```php
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;

class ProductService
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger,
    ) {}

    public function updatePrice(Product $product, float $newPrice): void
    {
        $oldPrice = $product->price;
        $product->price = $newPrice;
        $product->save();

        $this->auditLogger->log(
            module: AuditModuleEnum::PRODUCTS_AND_MENU->value,
            action: 'product.price_updated',
            description: "Mengubah harga produk '{$product->name}' dari Rp " . number_format($oldPrice) . " menjadi Rp " . number_format($newPrice),
            subject: $product,
            properties: [
                'old' => ['price' => $oldPrice],
                'new' => ['price' => $newPrice],
            ]
        );
    }
}
```

---

## 5. Kebijakan Retensi 1 Tahun (365 Hari) via Scheduler

1. **Auto-Pruning Command:** `App\Console\Commands\ManageAuditPartitionsCommand` (`php artisan audit:manage-partitions --prune-days=365`).
2. **Scheduler:** Dijalankan setiap hari pukul 02:00 WIB di `routes/console.php` (membuat partisi baru dan drop partisi $> 365$ hari).
