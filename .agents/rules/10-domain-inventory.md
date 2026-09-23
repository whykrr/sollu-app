---
trigger: always_on
---

# Rule 10: Standar Domain Modul Inventori

Panduan dan aturan baku rekayasa perangkat lunak khusus **Modul Inventory** pada **Sollu App**.
Setiap AI Agent yang bekerja atau bersinggungan dengan modul inventori **WAJIB** membaca, mematuhi, dan memperbarui aturan ini agar eksekusi berjalan optimal dengan penggunaan *context window* yang minimal.

---

## 1. Ringkasan Domain & Matriks Entitas (High-Density Cheat Sheet)

Semua entitas inventori berada dalam namespace backend `App\Models\Inventory\` dan terisolasi secara multi-tenant (`business_id` dan `outlet_id`).

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                             Inventory Domain Map                                 │
├────────────────────────┼─────────────────────────────────────────────────────────┤
│ InventoryItem          │ Master item inventori (bahan baku, barang jadi, SKU)     │
│ InventoryBalance       │ Saldo stok berjalan per Outlet (current_stock, costs)    │
│ InventoryCostLayer     │ Antrean batch biaya FIFO (qty_purchased, qty_remaining)  │
│ InventoryMovement      │ Buku besar mutasi stok immutable (in, out, diff, cogs)  │
│ StockAdjustment (+Item)│ Penyesuaian stok draf/approval (waste, expired, damage) │
│ StockOpname (+Item)    │ Stock opname fisik, selisih sistem vs aktual, audit     │
│ StockTransfer (+Item)  │ Transfer stok antar-outlet (pending, in-transit, rcv)   │
│ PurchaseOrder (+Item)  │ Pengadaan PO dari Supplier (ordered, received, layers)  │
│ GoodsReceipt (+Item)   │ Penerimaan fisik PO bertahap / multi-GR di outlet       │
│ PurchaseReturn (+Item) │ Retur barang pembelian ke pemasok terikat GR item       │
│ Supplier               │ Data vendor/pemasok dan kebijakan `return_period_days`  │
│ Outlet.is_stock_frozen │ Status pembekuan transaksi stok outlet saat audit       │
└────────────────────────┴─────────────────────────────────────────────────────────┘
```

### Tabel Relasi & Field Kunci

| Model | Tabel Database | Kunci Partisi Multi-Tenant | Karakteristik Kunci |
| :--- | :--- | :--- | :--- |
| `InventoryItem` | `inventory_items` | `business_id` | SKU, Barcode, UOM/Satuan, Minimum Stock, Tipe (Bahan Baku / Produk Jadi). |
| `InventoryBalance` | `inventory_balances` | `business_id`, `outlet_id` | `current_stock`, `average_cost`, `last_cost`, `total_value`. |
| `InventoryCostLayer`| `inventory_cost_layers` | `business_id`, `outlet_id` | FIFO tracking: `purchase_price`, `qty_purchased`, `qty_remaining`, `reference_type`, `reference_id`. |
| `InventoryMovement` | `inventory_movements` | `business_id`, `outlet_id` | Immutable Ledger ($timestamps = false): `movement_type`, `qty_change`, `stock_before`, `stock_after`, `unit_cost`, `total_cost`, `balance_value_after`. |
| `StockAdjustment` | `stock_adjustments` | `business_id`, `outlet_id` | Status transition: `Draft` $\rightarrow$ `Approved` / `Rejected` / `Void`. |
| `StockOpname` | `stock_opnames` | `business_id`, `outlet_id` | Status transition: `InProgress` $\rightarrow$ `PendingApproval` $\rightarrow$ `Approved` / `Rejected`. |
| `StockTransfer` | `stock_transfers` | `business_id` (`from_outlet_id`, `to_outlet_id`) | Status transition: `Pending` $\rightarrow$ `Approved` $\rightarrow$ `InTransit` $\rightarrow$ `Completed` / `Rejected`. |
| `PurchaseOrder` | `purchase_orders` | `business_id`, `outlet_id` | Status transition: `Draft` $\rightarrow$ `Ordered` $\rightarrow$ `Received` / `Partial` / `Cancelled` / `Void`. |
| `GoodsReceipt` | `goods_receipts` | `business_id`, `outlet_id` | Relasi ke `purchase_order_id`, nomor penerimaan `receipt_number`, pencatatan tanggal penerimaan `received_at`. |
| `PurchaseReturn` | `purchase_returns` | `business_id`, `outlet_id` | Relasi ke `purchase_order_id`, nomor retur `return_number`, item terikat `goods_receipt_item_id`. |
| `Supplier` | `suppliers` | `business_id` | Master vendor pemasok dengan batasan `return_period_days` (hari). |

---

## 2. Enums sebagai Single Source of Truth (No Magic Strings)

DILARANG KERAS menggunakan string literal untuk memvalidasi tipe atau status inventori.

### Matriks Enum Resmi Modul Inventori

| Enum PHP (`app/Enums/`) | Kasus / Nilai yang Tersedia | Penggunaan Utama |
| :--- | :--- | :--- |
| `InventoryCostingMethod` | `FIFO` (`fifo`), `AVERAGE` (`average`) | Metode valuasi persediaan tenant (`$business->getCostingMethod()`). |
| `InventoryMovementType` | `PurchaseIn`, `AdjustmentIn`, `AdjustmentOut`, `OpnameSurplus`, `OpnameDeficit`, `TransferIn`, `TransferOut`, `SalesOut`, `Waste`, `ReturnIn`, `ReturnOut`, `InitialStock` | Jenis mutasi pada buku besar `InventoryMovement`. |
| `AdjustmentReason` | `Damage`, `Lost`, `Expired`, `Correction`, `Waste`, `Other` | Alasan penyesuaian stok pada `StockAdjustment`. |
| `AdjustmentStatus` | `Draft`, `Approved`, `Rejected`, `Void` | Status siklus hidup `StockAdjustment`. |
| `StockOpnameStatus` | `InProgress`, `PendingApproval`, `Approved`, `Rejected` | Status siklus hidup `StockOpname`. |
| `StockTransferStatus` | `Pending`, `Approved`, `InTransit`, `Completed`, `Rejected` | Status pengiriman `StockTransfer`. |
| `PurchaseOrderStatus` | `Draft`, `Ordered`, `Received`, `Partial`, `Cancelled`, `Void` | Status pengadaan barang `PurchaseOrder`. |

### Distribusi ke Frontend
- Frontend Vue mengakses enum melalui:
  - Script Setup: `const { enums, getOptions, getLabel, getColor } = useEnum()`
  - Template: `$enums.InventoryMovementType.PurchaseIn`, `$enums.AdjustmentStatus.Draft`
  - Dropdown Options: `:options="getOptions('AdjustmentReason')"`

---

## 3. Invarian Kritis Costing Engine & COGS (Valuasi Stok)

Semua operasi penambahan, pengurangan, atau penyesuaian stok **WAJIB** melalui `App\Services\App\Inventory\InventoryCostingService`.

### 3.1. Aturan Stok Masuk (`recordIncomingStock`)
1. **Pembaruan Saldo (`InventoryBalance`):**
   - Hitung Moving Average baru: `new_avg = ((current_stock * current_avg) + (incoming_qty * incoming_cost)) / (current_stock + incoming_qty)`.
   - Update `current_stock += incoming_qty`, `average_cost = new_avg`, `last_cost = incoming_cost`, `total_value = current_stock * new_avg`.
2. **Pembentukan Layer FIFO (`InventoryCostLayer`):**
   - Buat layer baru dengan `qty_purchased = incoming_qty`, `qty_remaining = incoming_qty`, `purchase_price = incoming_cost`.
3. **Pencatatan Ledger (`InventoryMovement`):**
   - Catat baris dengan `qty_change = +incoming_qty`, `unit_cost = incoming_cost`, `total_cost = incoming_qty * incoming_cost`, serta relasi polimorfik `reference`.

### 3.2. Aturan Stok Keluar (`recordOutgoingStock`)
1. **Alokasi HPP / COGS:**
   - **Mode FIFO:** Panggil `consumeFifoLayers()`. Kurangi `qty_remaining` dari layer aktif tertua (`orderBy('created_at', 'asc')`). Total COGS adalah akumulasi biaya dari layer yang terkonsumsi.
   - **Mode Moving Average:** Gunakan `average_cost` saat ini dikalikan `qty`. Tetap kurangi layer FIFO di latar belakang agar antrean FIFO tetap sinkron jika tenant beralih metode.
2. **Pembaruan Saldo:**
   - `current_stock -= outgoing_qty`, `total_value = max(0, total_value - total_cogs)`.
3. **Pencatatan Ledger:**
   - Catat baris dengan `qty_change = -outgoing_qty`, `unit_cost = unit_cogs`, `total_cost = total_cogs`.

### 3.3. Aturan Transfer Stok Antar-Outlet (`StockTransferService`)
1. **Outlet Asal (Transfer Out):**
   - Eksekusi `recordOutgoingStock(..., InventoryMovementType::TransferOut)`. Dapatkan `unit_cogs` hasil kalkulasi biaya di outlet asal.
2. **Outlet Tujuan (Transfer In):**
   - Eksekusi `recordIncomingStock(..., InventoryMovementType::TransferIn)` dengan `unitCost` bernilai sama persis dengan `unit_cogs` yang keluar dari outlet asal.
3. **Konsistensi Nilai:** Dilarang me-reset atau mengabaikan HPP saat transfer barang antar-outlet.

### 3.4. Aturan Penerimaan Barang, Retur, & Void Purchase (`GoodsReceiptService` & `PurchaseReturnService`)
1. **Penerimaan Barang (`GoodsReceiptService`):**
   - Mendukung penerimaan bertahap (*partial receiving*). Setiap penerimaan menghasilkan dokumen `GoodsReceipt` dan memicu `recordIncomingStock(..., InventoryMovementType::PurchaseIn)` sesuai kuantitas riil yang diterima.
   - Status PO diperbarui otomatis: `Partial` jika belum seluruh item diterima, dan `Received` jika seluruh kuantitas terpenuhi.
2. **Retur Pembelian (`PurchaseReturnService`):**
   - Retur barang WAJIB merujuk pada `goods_receipt_item_id` aktif dan tidak melebihi selisih kuantitas yang diterima dikurangi retur sebelumnya.
   - Validasi batas periode retur (`Supplier.return_period_days`): retur ditolak jika melebihi batas waktu sejak tanggal penerimaan.
   - Retur memicu `recordOutgoingStock(..., InventoryMovementType::ReturnOut)` untuk memotong saldo stok fisik dan mengurangi kuantitas layer terkait.
3. **Void Purchase Order:**
   - Pembatalan seluruh PO (`PurchaseStatus::Voided`) mengunci transaksi dari seluruh aksi lanjutan (tidak bisa diedit, diterima, atau diretur lagi).

---

## 4. Pembekuan Stok Outlet (Stock Freeze Guard) & Pemisahan Tugas (Segregation of Duties)

### 4.1. Pembekuan Stok Outlet (Stock Freeze Guard)

Outlet dapat dibekukan (`is_stock_frozen = true`) untuk keperluan stock opname atau audit fisik.

1. **Pemeriksaan Wajib:**
   - Semua mutasi stok (Penyesuaian Stok, Transfer, PO Receive, Void) WAJIB memeriksa status pembekuan:
     ```php
     $this->stockFreezeService->assertNotFrozen($outlet);
     ```
2. **Middleware Route:**
   - Gunakan middleware `stock.not.frozen` pada grup route yang memutasi stok fisik.
3. **Transfer Antar-Outlet:**
   - Transfer stok WAJIB memvalidasi kedua outlet: `$fromOutlet` dan `$toOutlet` tidak dalam kondisi beku.

### 4.2. Kebijakan Pemisahan Tugas (Segregation of Duties / SoD)

Untuk fleksibilitas berbagai jenis merchant (usaha mikro/kecil vs korporasi multi-outlet), aturan pencegahan *self-approval* dan pemisahan tugas diatur secara dinamis melalui konfigurasi `business->settings['inventory_sod']` dan dievaluasi terpusat via `App\Services\App\Inventory\InventorySodService`.

1. **Skema JSON Konfigurasi:**
   ```json
   {
     "inventory_sod": {
       "enabled": false,
       "allow_owner_bypass": true,
       "rules": {
         "stock_adjustment": true,
         "stock_opname": true,
         "stock_transfer_approval": true,
         "stock_transfer_receive": true,
         "purchase_order_receive": false,
         "direct_purchase_allowed": true
       }
     }
   }
   ```
2. **Matriks 5 Alur Terkendali SoD:**
   - **Penyesuaian Stok (`StockAdjustment`):** Jika `rules.stock_adjustment = true`, pembuat draf dilarang menyetujui drafnya sendiri (`created_by !== approved_by`).
   - **Stock Opname (`StockOpname`):** Jika `rules.stock_opname = true`, petugas pencatat hitungan fisik dilarang menyetujui/memfinalisasi opname (`created_by !== approved_by`).
   - **Transfer Stok - Persetujuan (`StockTransfer`):** Jika `rules.stock_transfer_approval = true`, pengaju transfer dilarang menyetujui transfer (`requested_by !== approved_by`).
   - **Transfer Stok - Penerimaan (`StockTransfer`):** Jika `rules.stock_transfer_receive = true`, pengirim dilarang mengeksekusi penerimaan di outlet tujuan (`shipper !== receiver`).
   - **Penerimaan Barang PO (`GoodsReceipt`):** Jika `rules.purchase_order_receive = true`, pembuat PO dilarang mencatat penerimaan fisik barang (`po.created_by !== gr.received_by`).
3. **Aturan Evaluasi di Service Layer:**
   - **DILARANG KERAS** menulis pengecekan hardcode manual seperti `if ($user->id === $model->created_by)` di dalam service atau controller.
   - **WAJIB** panggil method asersi terpusat dari `InventorySodService` (misal: `$this->inventorySodService->assertCanApproveAdjustment($adjustment, $user)`).

---

## 5. Komunikasi Lintas Modul & Integrasi Penjualan (Decoupling)

1. **🚨 LARANGAN KERAS MUTASI LANGSUNG:**
   - Modul POS / Kasir / Transaksi / Penjualan **DILARANG KERAS** melakukan operasi Eloquent `insert`/`update`/`delete` langsung pada tabel `inventory_balances`, `inventory_cost_layers`, atau `inventory_movements`.
2. **Pemotongan Stok Penjualan:**
   - Modul Transaksi WAJIB memanggil `App\Services\App\Transaction\InventoryDeductionService` atau melempar Domain Event (`TransactionCompleted`) yang ditangkap oleh listener inventori.
3. **Komposisi Resep / Bahan Baku:**
   - Pemotongan item komposit/resep diproses secara transaksional per bahan penyusun dengan mencatat movement `InventoryMovementType::SalesOut`.

---

## 6. Standar Backend & Frontend Khusus Inventori

1. **Thin Controller:**
   - Controller di `app/Http/Controllers/App/Inventory/` hanya bertugas menerima HTTP request, otorisasi, memanggil Domain Service, dan render respons.
2. **Database Transaction:**
   - Setiap operasi mutasi inventori di Service Layer WAJIB dibungkus dalam `DB::transaction(function () { ... })`.
3. **Anti Over-Fetching di `index()`:**
   - Endpoint `index()` HANYA me-load data ringkasan paginasi. Detail antrean cost layer dan riwayat mutasi dimuat on-demand via `show()` / PopUp drawer.
4. **Layout Halaman & Filter:**
   - Seluruh halaman inventori menggunakan `<MainPage>` dan toolbar `ActionBar` yang diekstrak ke `{Entity}Filter.vue`. Single action row click `@row-click="openDetail"` dengan `:action="false"`.

---

## 7. Protokol Pemeliharaan Mandiri Agen (Self-Evolution Mandate)

> [!IMPORTANT]
> **KEWAJIBAN PEMELIHARAAN OTOMATIS OLEH AI AGENT:**
> Setiap kali seorang AI Agent:
> 1. Menambahkan entitas, relasi, atau tabel baru pada domain inventori.
> 2. Menambah kasus baru pada enum inventori (`InventoryMovementType`, `AdjustmentReason`, dll.).
> 3. Mengubah formula atau logika pada `InventoryCostingService` / Service inventori lainnya.
> 4. Mengubah alur otorisasi, middleware, atau rute `routes/app/inventories.php`.
> 5. Merefaktor atau menambahkan komponen Vue/Inertia pada halaman inventori.
>
> **Agent WAJIB secara proaktif memperbarui file aturan ini (`.agents/rules/10-domain-inventory.md`)** agar tabel matriks, enum, dan invariant di atas selalu akurat dan sinkron dengan codebase riil. Jadikan pembaruan aturan ini sebagai bagian dari *Definition of Done (DoD)* sebelum menyelesaikan pekerjaan.
