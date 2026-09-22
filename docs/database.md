# Sollu App Database & Data Architecture

Dokumentasi skema database, pemodelan data Eloquent, dan standar isolasi multi-tenant pada **Sollu App**.

---

## 1. Multi-Tenant Data Isolation

Sollu App menerapkan strategi **Logical Multi-Tenancy (Row-Level Isolation)** di atas database PostgreSQL.

```
┌─────────────────────────────────────────────────────────────┐
│                    Business (Tenant Core)                   │
│                    `id` (UUID Primary Key)                  │
└──────────────────────────────┬──────────────────────────────┘
                               │ 1:N
        ┌──────────────────────┴──────────────────────┐
        ▼                                             ▼
 ┌───────────────┐                             ┌──────────────┐
 │    Outlets    │                             │    Users     │
 │ `business_id` │                             │`business_id` │
 └───────┬───────┘                             └──────────────┘
         │ 1:N
         ├───────────────────────────────┐
         ▼                               ▼
 ┌──────────────────────┐        ┌──────────────────────┐
 │   Inventory Balances │        │     Transactions     │
 │ `outlet_id` + `item` │        │ `business_id`+`outlet`│
 └──────────────────────┘        └──────────────────────┘
```

### 1.1. Prinsip Isolasi Data
1. **Tenant Key (`business_id`):** Setiap tabel operasional dan data master (produk, inventori, transaksi, pelanggan) wajib memiliki kolom `business_id` (UUID).
2. **Sub-Tenant Key (`outlet_id`):** Data inventori fisik (stok berjalan, pergerakan, shift kasir, transaksi) memiliki kolom `outlet_id` (UUID).
3. **Foreign Key Integrity:** Seluruh relasi di database diamankan dengan Foreign Key constraint ke tabel induk (`businesses` atau `outlets`) dengan aturan `onDelete('cascade')` atau `onDelete('restrict')`.
4. **Primary Key Standard:** Menggunakan string UUID v4 via trait `Illuminate\Database\Eloquent\Concerns\HasUuids`.

### 1.2. Kebijakan Isolasi Data Universal Lintas Layer (Universal Multi-Tenancy Policy)
Isolasi data bukan hanya berlaku di layer pelaporan, melainkan wajib diterapkan secara ketat di **SEMUA layer aplikasi**:
1. **Layer Model (`App\Models`):** Seluruh model entitas tenant WAJIB mengimplementasikan trait `App\Trait\HasBusiness` (`scopeCurrentBusiness(?string $businessId = null)`) dan/atau `App\Trait\HasOutlet` (`scopeSelectedOutlet()`, `scopeForOutlet($outletIds)`).
2. **Layer Service & Repository:** Seluruh pemanggilan Eloquent Query Builder, agregasi analitik, atau DB query WAJIB diawali dengan `scopeCurrentBusiness()` atau klausa `where('business_id', $businessId)` serta pembatasan outlet yang diizinkan (`whereIn('outlet_id', $accessibleOutletIds)`). DILARANG KERAS mengeksekusi `DB::table(...)` tanpa filter tenant bisnis.
3. **Layer Controller & Form Request:** Form Request wajib memvalidasi kepemilikan tenant atas data yang diinput/diakses (misal: validasi `exists` dengan scope `business_id`), serta mengotorisasi hak akses peran (`PermissionEnum`).
4. **Layer Background Jobs (Queue):** Seluruh Job (Export CSV/Excel, Export PDF, Data Sync, Notifikasi) wajib membawa konteks tenant (`User $user` atau `business_id`) dan menyertakan filter `scopeCurrentBusiness($businessId)` pada query-nya.
5. **Layer Analytics, Overview & Pelaporan:** Seluruh kalkulasi metrik, tren penjualan, valuasi stok, dan laporan berkala wajib memisahkan data agregasi KPI dari data tabular detail serta terisolasi 100% per tenant dan per outlet yang diizinkan.

---

## 2. Model Standards & Conventions (Laravel 11 & PHP 8.3)

Semua model Eloquent mengikuti struktur standar urutan member (*Member Ordering*):

```php
namespace App\Models\Inventory;

use App\Enums\AdjustmentStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Traits\HasQuantityFormatter;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $business_id
 * @property string $outlet_id
 * @property AdjustmentStatus $status
 * @property-read Business $business
 * @property-read Outlet $outlet
 */
class StockAdjustment extends Model
{
    // 1. Traits
    use HasFactory, HasUuids, SoftDeletes, HasQuantityFormatter, SortableModel;

    // 2. Table, Fillable & Sortable Properties
    protected $table = 'stock_adjustments';

    protected $fillable = [
        'business_id',
        'outlet_id',
        'adjustment_number',
        'status',
        'notes',
        'adjusted_at',
    ];

    /**
     * Whitelist kolom yang diizinkan untuk di-sort dari tabel frontend.
     */
    protected array $sortable = [
        'adjustment_number',
        'status',
        'adjusted_at',
        'created_at',
        'updated_at',
    ];

    // 3. Laravel 11 casts() Method (Bukan properti $casts)
    protected function casts(): array
    {
        return [
            'status'      => AdjustmentStatus::class,
            'adjusted_at' => 'datetime',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
            'deleted_at'  => 'datetime',
        ];
    }

    // 4. Relationships (Explicit Return Types & Ordering: BelongsTo -> HasMany)
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'stock_adjustment_id');
    }

    // 5. Scopes (Local Scopes & Filtering)
    public function scopeForOutlet($query, string $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }
}
```

---

## 3. Core Entities & Bounded Context Schemas

### 3.1. Tenant & Core Identity (`App\Models`)
- `Business`: Entitas induk penyewa/merchant SaaS. Menyimpan tipe bisnis, paket langganan aktif, dan status bisnis.
- `BusinessType`: Klasifikasi jenis industri bisnis dinamis (tabel `business_types` dengan kolom `category`: `retail`, `fnb`, `service`, `category_label`, `sort_order`, `is_visible`, `features`). Tidak menggunakan enum.
- `Outlet`: Unit gerai/cabang fisik di bawah satu bisnis.
- `OutletSetting`: Konfigurasi operasional per gerai (pajak, service charge, printer, footer struk).
- `User`: Pengguna sistem (pemilik, manajer, kasir) yang terikat ke `business_id`.
- `Role` & `Permission`: Implementasi Spatie Permission dengan multi-tenant team scope (`business_id`).

### 3.2. Master Data Domain (`App\Models\Master`)
- `Product`: Entitas produk jual.
- `ProductCategory`: Kategori hierarki produk.
- `ProductPrice`: Konfigurasi harga jual per outlet atau tier.
- `VariantGroup` & `VariantGroupOption`: Varian produk (Ukuran, Rasa, Warna).
- `ModifierGroup` & `ModifierOption`: Tambahan topping/opsi produk kustom.
- `ProductRecipeItem` & `RecipeVersion`: Resep BOM (*Bill of Materials*) yang menghubungkan produk jual dengan `InventoryItem` mentah.
- `Customer`: Data pelanggan/member per bisnis.
- `PaymentMethod`: Metode pembayaran yang tersedia (Tunai, QRIS, EDC, Transfer).
- `Uom`: Unit of Measure (Satuan hitung: Pcs, Kg, Liter, Box).

### 3.3. Inventory Domain (`App\Models\Inventory`)
- `InventoryItem`: Master bahan baku/barang inventori.
- `InventoryBalance`: Saldo stok berjalan per item per outlet fisik (mendukung kalkulasi dan scoping saldo per outlet aktif).
- `InventoryMovement`: Buku besar log mutasi stok (*stock card/ledger*) immutable yang mencatat setiap penambahan/pengurangan stok dengan tipe mutasi lengkap (`InventoryMovementType`: `PurchaseIn`, `AdjustmentIn`, `AdjustmentOut`, `OpnameSurplus`, `OpnameDeficit`, `TransferIn`, `TransferOut`, `SalesOut`, `Waste`, `ReturnIn`, `ReturnOut`, `InitialStock`).
- `InventoryCostLayer`: Pencatatan lapisan biaya FIFO/Average untuk perhitungan HPP akurat.
- `StockAdjustment` & `StockAdjustmentItem`: Penyesuaian stok manual (rusak, hilang, koreksi).
- `StockOpname` & `StockOpnameItem`: Sensus audit fisik stok berkala.
- `StockTransfer` & `StockTransferItem`: Mutasi perpindahan stok antar-outlet.
- `PurchaseOrder` & `PurchaseOrderItem`: Pesanan pembelian ke vendor pemasok dengan siklus status (`Draft`, `Ordered`, `Received`, `Partial`, `Cancelled`, `Void`).
- `GoodsReceipt` & `GoodsReceiptItem`: Penerimaan barang fisik dari PO pemasok (mendukung penerimaan parsial dan multi-GR).
- `PurchaseReturn` & `PurchaseReturnItem`: Retur barang pembelian ke pemasok yang terikat pada item penerimaan barang (`goods_receipt_item_id`) dan tervalidasi terhadap batas waktu retur supplier.
- `Supplier`: Master pemasok/vendor bahan baku (memuat kolom `return_period_days` untuk kebijakan batas waktu retur pembelian).

### 3.4. Sales & POS Domain (`App\Models\Sales`)
- `Shift`: Sesi kerja kasir per mesin POS dengan saldo awal, saldo akhir kas, dan status siklus kerja kasir (`ShiftStatus`: `Open`, `Closed`).
- `ShiftCashLog`: Mutasi uang kas masuk/keluar selama kasir bertugas (`ShiftCashLogType`: `CashIn`, `CashOut`).
- `Transaction`: Transaksi penjualan utama (order POS).
- `TransactionItem`: Rincian produk yang dipesan dalam satu transaksi.
- `TransactionItemModifier`: Rincian modifier/topping yang dipilih pada item transaksi.
- `TransactionPayment`: Catatan rincian pembayaran (split payment, tunai, kartu, QRIS).
- `TransactionPromo`: Diskon dan promo yang diaplikasikan pada transaksi.
- `TransactionInvoice`: Nomor faktur/struk resmi yang diterbitkan.

### 3.5. Subscription & Cockpit Domain (`App\Models`)
- `SubscriptionPlan`: Master paket langganan SaaS (Micro, Basic, Pro, serta Custom Plan via penugasan `business_id` dan `is_public`).
- `Feature`: Master daftar fitur sistem modular (tabel `features` dengan `module`, `group`, `group_label`, `sort_order`, `is_active`).
- `plan_features`: Tabel pivot relasi many-to-many antara `subscription_plans` dan `features`.
- `Subscription`: Status langganan aktif suatu bisnis, tanggal kedaluwarsa, dan siklus penagihan.
- `Invoice` & `InvoiceItem`: Tagihan pembayaran langganan SaaS.
- `Payment`: Pembayaran faktur langganan.
- `CockpitUser`: Pengguna tim internal admin platform Sollu.

---

## 4. Query Optimization & Indexing Guidelines

1. **Database Schema Diagnostics:**
   - Gunakan perkakas MCP `sollu-db` (`query`) atau `laravel-boost` (`database-schema`) untuk memeriksa skema langsung di PostgreSQL.
2. **Indeks Wajib:**
   - Indeks gabungan (*composite index*) pada `(business_id, deleted_at)` untuk setiap tabel tenant.
   - Indeks pada `(outlet_id, inventory_item_id)` pada tabel saldo dan mutasi.
   - Indeks pada kolom status (`status`, `type`, `created_at`).
3. **Selective Column Projection:**
   ```php
   // Hindari SELECT * pada tabel dengan banyak kolom
   $items = InventoryItem::query()
       ->where('business_id', $businessId)
       ->select(['id', 'sku', 'name', 'uom_id', 'status'])
       ->with(['uom:id,name,symbol'])
       ->paginate(15);
   ```
4. **Batch Upsert / Insert:**
   - Dilarang membuat loop `foreach` yang melakukan `Model::create()` atau `Model::save()`.
   - Gunakan `DB::table(...)->upsert(...)` atau `insert()` untuk mutasi banyak baris sekaligus.
