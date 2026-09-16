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

---

## 2. Model Standards & Conventions (Laravel 11 & PHP 8.3)

Semua model Eloquent mengikuti struktur standar urutan member (*Member Ordering*):

```php
namespace App\Models\Inventory;

use App\Enums\AdjustmentStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Traits\HasQuantityFormatter;
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
    use HasFactory, HasUuids, SoftDeletes, HasQuantityFormatter;

    // 2. Table & Fillable Properties
    protected $table = 'stock_adjustments';

    protected $fillable = [
        'business_id',
        'outlet_id',
        'adjustment_number',
        'status',
        'notes',
        'adjusted_at',
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
- `InventoryBalance`: Saldo stok berjalan per item per outlet fisik.
- `InventoryMovement`: Buku besar log mutasi stok (*stock card/ledger*) yang mencatat setiap penambahan/pengurangan stok.
- `InventoryCostLayer`: Pencatatan lapisan biaya FIFO/Average untuk perhitungan HPP akurat.
- `StockAdjustment` & `StockAdjustmentItem`: Penyesuaian stok manual (rusak, hilang, koreksi).
- `StockOpname` & `StockOpnameItem`: Sensus audit fisik stok berkala.
- `StockTransfer` & `StockTransferItem`: Mutasi perpindahan stok antar-outlet.
- `PurchaseOrder` & `PurchaseOrderItem`: Pesanan pembelian ke vendor pemasok.
- `Supplier`: Master pemasok/vendor bahan baku.

### 3.4. Sales & POS Domain (`App\Models\Sales`)
- `Shift`: Sesi kerja kasir per mesin POS dengan saldo awal dan saldo akhir kas.
- `ShiftCashLog`: Mutasi uang kas masuk/keluar selama kasir bertugas.
- `Transaction`: Transaksi penjualan utama (order POS).
- `TransactionItem`: Rincian produk yang dipesan dalam satu transaksi.
- `TransactionItemModifier`: Rincian modifier/topping yang dipilih pada item transaksi.
- `TransactionPayment`: Catatan rincian pembayaran (split payment, tunai, kartu, QRIS).
- `TransactionPromo`: Diskon dan promo yang diaplikasikan pada transaksi.
- `TransactionInvoice`: Nomor faktur/struk resmi yang diterbitkan.

### 3.5. Subscription & Cockpit Domain (`App\Models`)
- `SubscriptionPlan`: Master paket langganan SaaS (Micro, Basic, Pro, serta Custom Plan via atribut `is_public` dan `is_custom`).
- `Feature`: Master daftar fitur sistem modular (tabel `features` dengan `module`, `group`, `group_label`, `sort_order`, `is_active`).
- `plan_features`: Tabel pivot relasi many-to-many antara `subscription_plans` dan `features`.
- `Subscription`: Status langganan aktif suatu bisnis, tanggal kedaluwarsa, dan kuota outlet.
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
