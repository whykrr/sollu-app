---
name: domain-inventory
description: >-
    Knowledge and standards for Inventory Domain in Sollu App. Covers InventoryItem, InventoryBalance,
    InventoryCostLayer (FIFO/Moving Average), InventoryMovement ledger, StockAdjustment, StockOpname,
    StockTransfer, PurchaseOrder, GoodsReceipt, PurchaseReturn, Stock Freeze Guard, Segregation of Duties (SoD),
    and Fast-Path Directory Cheatsheet. Activate whenever working on inventory, stock costing, transfers, opname,
    goods receipt, supplier returns, or inventory supervision settings.
---

# Domain Skill: Modul Inventori (Inventory Domain)

Panduan dan aturan baku rekayasa perangkat lunak khusus **Modul Inventory** pada **Sollu App**.

---

## 1. Ringkasan Domain & Matriks Entitas

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
│ StockTransfer (+Item)  │ Transfer stok hybrid (direct 1-step, in-transit 2-step) │
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
| `StockTransfer` | `stock_transfers` | `business_id` (`from_outlet_id`, `to_outlet_id`) | Hybrid mode: `direct` (1-Step) vs `two_step` (2-Step). Status: `Pending` $\rightarrow$ `Approved` $\rightarrow$ `InTransit` $\rightarrow$ `Completed` / `Rejected` / `Void`. Audit fields: `shipped_at`, `shipped_by`, `received_at`, `voided_at`, `voided_by`, `void_reason`. |
| `StockTransferItem`| `stock_transfer_items` | N/A (via transfer) | `qty`, `qty_received`, `unit_cost` (HPP pengiriman), `qty_difference` (selisih transit), `difference_reason`. |
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
| `StockTransferMode` | `DIRECT` (`direct`), `TWO_STEP` (`two_step`) | Mode mutasi antar-outlet: 1-Langkah instan vs 2-Langkah Kirim-Terima. |
| `StockTransferStatus` | `Pending`, `Approved`, `InTransit`, `Completed`, `Rejected`, `Void` | Status siklus hidup `StockTransfer`. |
| `PurchaseOrderStatus` | `Draft`, `Ordered`, `Received`, `Partial`, `Cancelled`, `Void` | Status pengadaan barang `PurchaseOrder`. |
| `FeatureEnum` (Gating) | `DOUBLE_AUTHORIZATION` (`double_authorization`) | Prasyarat paket SaaS untuk mengaktifkan validasi supervisi / SoD. |

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

### 3.3. Aturan Mutasi Stok Antar-Outlet (`StockTransferService`)
Mendukung model hybrid standar industri (Accurate, Jurnal, Odoo):

1. **Mode 1-Step (`StockTransferMode::DIRECT` / Transfer Langsung):**
   - **Tanpa Supervisi (Fleksibel):** Sekali simpan langsung mengeksekusi `recordOutgoingStock(fromOutlet, TransferOut)` dan `recordIncomingStock(toOutlet, TransferIn)` secara atomik. Status langsung `Completed`.
   - **Dengan Supervisi:** Dibuat sebagai `Pending`. Saat supervisor menyetujui (`approveTransfer`), kedua mutasi dieksekusi secara atomik dan status menjadi `Completed`.
2. **Mode 2-Step (`StockTransferMode::TWO_STEP` / Kirim & Terima):**
   - **Pengiriman (`shipTransfer`):** Memotong stok outlet asal seketika via `recordOutgoingStock(fromOutlet, TransferOut)`. Menyimpan `unit_cost` pada baris item transfer. Status menjadi `InTransit`. Menghilangkan anomali *phantom stock* di pengirim.
   - **Penerimaan (`completeTransfer`):** Outlet tujuan menerima kuantitas riil via `recordIncomingStock(toOutlet, TransferIn)` menggunakan `unit_cost` saat barang dikirim. Jika ada selisih hilang/rusak di jalan, tercatat pada `stock_transfer_items.qty_difference` dan `difference_reason`. Status menjadi `Completed`.
3. **Mesin Pembalik / Reversal Engine (`voidTransfer`):**
   - **Status `Pending` / `Approved`:** Pembatalan draf tanpa pergerakan stok. Status `Void`.
   - **Status `InTransit`:** Mengembalikan kuantitas stok ke outlet asal (`recordIncomingStock(fromOutlet, TransferIn)`). Status `Void`.
   - **Status `Completed`:** Memvalidasi saldo outlet tujuan mencukupi ($\ge$ `qty_received`), lalu mengeksekusi mutasi pembalik simetris (potong tujuan `TransferOut`, kembalikan ke asal `TransferIn`). Status `Void`.

### 3.4. Aturan Penerimaan Barang, Retur, & Void Purchase (`GoodsReceiptService` & `PurchaseReturnService`)
1. **Penerimaan Barang (`GoodsReceiptService`):**
   - Mendukung penerimaan bertahap (*partial receiving*). Setiap penerimaan menghasilkan dokumen `GoodsReceipt` dan memicu `recordIncomingStock(..., InventoryMovementType::PurchaseIn)` sesuai kuantitas riil yang diterima.
   - Status PO diperbarui otomatis: `Partial` jika belum seluruh item diterima, dan `Received` jika seluruh kuantitas terpenuhi.
2. **Retur Pembelian (`PurchaseReturnService`):**
   - Retur barang WAJIB merujuk pada `goods_receipt_item_id` aktif dan tidak melebihi selisih kuantitas yang diterima dikurangi retur sebelumnya.
   - Validasi batas periode retur (`Supplier.return_period_days`): retur ditolak jika melebihi batas waktu sejak tanggal penerimaan.
   - Retur memicu `recordOutgoingStock(..., InventoryMovementType::ReturnOut)` untuk memotong saldo stok fisik dan mengurangi kuantitas layer terkait.
3. **Void Purchase Order & Void Goods Receipt:**
   - Void Goods Receipt membalikkan stok masuk dengan menerbitkan mutasi keluar pembalik.
   - Pembatalan seluruh PO (`PurchaseStatus::Voided`) mengunci transaksi dari seluruh aksi lanjutan.

### 3.5. Aturan Baku Stok Awal (Initial Stock & Grace Period Invariant)
1. **Wajib Costing Engine:**
   - Seluruh inisialisasi stok awal **WAJIB** melalui `InventoryCostingService::recordIncomingStock()` atau `InventoryCostingService::updateInitialStock()`.
   - **DILARANG KERAS** memanipulasi database secara langsung pada `inventory_balances` yang membypass pembentukan layer biaya FIFO dan ledger `InitialStock`.
2. **Invarian Grace Period:**
   - Pembaruan kuantitas atau HPP stok awal hanya diizinkan jika item belum pernah memiliki mutasi operasional lanjutan selain `InitialStock`. Jika sudah ada mutasi operasional, stok awal **Terkunci** (*readonly*) dan revisi fisik WAJIB melalui Penyesuaian Stok (*Stock Adjustment*).

---

## 4. Pembekuan Stok Outlet & Kebijakan Supervisi / SoD

### 4.1. Pembekuan Stok Outlet (Stock Freeze Guard)
Outlet dapat dibekukan (`is_stock_frozen = true`) untuk keperluan stock opname atau audit fisik.
1. Semua mutasi stok (Penyesuaian Stok, Transfer, PO Receive, Void) WAJIB memeriksa status pembekuan via `$this->stockFreezeService->assertNotFrozen($outlet)`.
2. Middleware route: `stock.not.frozen`.
3. Transfer stok memeriksa outlet relevan secara kontekstual (asal dan/atau tujuan sesuai aksi yang dieksekusi).

### 4.2. Kebijakan Supervisi & Pemisahan Tugas (Segregation of Duties / SoD)
Dievaluasi terpusat via `App\Services\App\Inventory\InventorySodService`:
1. **Hierarki Evaluasi Gating:**
   - **Tier Plan:** `$business->hasFeature(FeatureEnum::DOUBLE_AUTHORIZATION)`.
   - **Master Toggle SSOT:** `$business->settings['inventory_sod']['is_enabled']` (fallback `false` jika `null`).
   - Jika salah satu tidak aktif $\rightarrow$ **Mode Fleksibel Aktif** (alur validasi supervisi dipotong, dokumen dapat langsung diterapkan/disetujui).
2. **Otorisasi Supervisi:**
   - Pengguna yang melakukan validasi, persetujuan, penolakan, atau pembatalan dokumen wajib memiliki hak akses supervisi (`PermissionEnum::INVENTORY_..._VALIDATE`).
3. **Pemisahan Tugas (SoD Rules):**
   - **Adjustment:** Draf creator dilarang menyetujui drafnya sendiri.
   - **Opname:** Petugas hitungan fisik dilarang menyetujui opname.
   - **Transfer Approval:** Pengaju transfer dilarang menyetujui transfer.
   - **Transfer Receive:** Pengirim dilarang mengeksekusi penerimaan di outlet tujuan.
   - **PO Receive:** Pembuat PO dilarang mencatat penerimaan fisik barang.
   - **DILARANG KERAS** menulis pengecekan manual di controller. WAJIB panggil `InventorySodService`.

---

## 5. Komunikasi Lintas Modul & Integrasi Penjualan (Decoupling)

1. **LARANGAN KERAS MUTASI LANGSUNG:** Modul POS / Kasir / Penjualan dilarang mutasi langsung ke `inventory_balances`, `inventory_cost_layers`, atau `inventory_movements`.
2. **Pemotongan Stok:** Transaksi memanggil `InventoryDeductionService` atau melempar Domain Event (`TransactionCompleted`).
3. **Resep / Bahan Baku:** Diproses secara transaksional per bahan penyusun dengan mencatat movement `InventoryMovementType::SalesOut`.

---

## 6. Standar Backend & Frontend Khusus Inventori

1. **Thin Controller:** Controller di `app/Http/Controllers/App/Inventory/` hanya menangani HTTP request, otorisasi, memanggil Service, dan merender respons.
2. **Database Transaction:** Setiap mutasi di Service Layer WAJIB dibungkus `DB::transaction(function () { ... })`.
3. **Anti Over-Fetching:** `index()` HANYA paginasi ringkasan. Detail cost layer & riwayat pergerakan dimuat on-demand via `show()` / PopUp drawer.
4. **Layout Halaman:** Menggunakan `<MainPage>`, `ActionBar` diekstrak ke `{Entity}Filter.vue`, single action row click `@row-click="openDetail"` dengan `:action="false"`.

---

## 7. Peta Alur Cepat Modul Inventori (Anti-Scan Cheatsheet & Directory Map)

Gunakan tabel ini sebagai panduan langsung untuk menghindari pencarian luas (*full codebase scan*) saat mengerjakan fitur inventori.

| Sub-Fitur | Route File & Prefix | Controller | Form Request | Service Layer Utama | PopUp / View Frontend |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Penyesuaian Stok** | `routes/app/inventories.php`<br>`/inventories/stock-adjustments` | `StockAdjustmentController` | `StoreStockAdjustmentRequest` | `StockAdjustmentService` | `Adjustment/Index.vue`<br>`Adjustment/Components/AdjustmentDetailPopUp.vue` |
| **Stock Opname** | `routes/app/inventories.php`<br>`/inventories/stock-opnames` | `StockOpnameController` | `StoreStockOpnameRequest`<br>`UpdateStockOpnameRequest` | `StockOpnameService` | `StockOpname/Index.vue`<br>`StockOpname/Components/OpnameDetailPopUp.vue` |
| **Mutasi Stok (Transfer)** | `routes/app/inventories.php`<br>`/inventories/stock-transfers` | `StockTransferController` | `StoreStockTransferRequest`<br>`ProcessStockTransferRequest`<br>`VoidStockTransferRequest` | `StockTransferService` | `Transfer/Index.vue`<br>`Transfer/Components/TransferDetail.vue`<br>`Transfer/Components/TransferForm.vue`<br>`Transfer/Components/TransferReceiveForm.vue` |
| **Pengadaan & GR** | `routes/app/inventories.php`<br>`/inventories/purchases` | `StockPurchasesController` | `StorePurchaseOrderRequest`<br>`StoreGoodsReceiptRequest` | `PurchaseOrderService`<br>`GoodsReceiptService`<br>`PurchaseReturnService` | `Purchase/Index.vue`<br>`Purchase/Components/Detail.vue` |
| **Pembekuan Stok** | `routes/app/inventories.php`<br>`/inventories/outlets/{outlet}/freeze` | `OutletFreezeController` | N/A (Inline toggle) | `StockFreezeService` | `Components/Inventory/FreezeStockPopUp.vue` |
| **Pengaturan SoD** | `routes/app/settings.php`<br>`/settings/inventory` | `InventorySettingController` | `UpdateInventorySodSettingRequest` | `InventorySodService` | `Pages/App/Settings/Inventory/Index.vue` |
| **Costing & Buku Besar** | Internal Service | N/A | N/A | `InventoryCostingService` | Relasi ke `InventoryMovement`, `InventoryBalance`, `InventoryCostLayer` |

### State Transitions Cepat:
- **Adjustment:** Draf $\rightarrow$ `Approved` (mutasi terbit) $\rightarrow$ `Void` (reversal). Fleksibel: langsung simpan & terapkan.
- **Opname:** `InProgress` $\rightarrow$ `PendingApproval` $\rightarrow$ `Approved` (rekonsiliasi varian terbit). Fleksibel: langsung selesaikan opname.
- **Transfer 1-Step:** `Pending` $\rightarrow$ `Approved` $\rightarrow$ `Completed` (mutasi asal & tujuan tuntas). Fleksibel: langsung `Completed`.
- **Transfer 2-Step:** `Pending` $\rightarrow$ `Approved` $\rightarrow$ `InTransit` (asal terpotong) $\rightarrow$ `Completed` (tujuan terima + selisih).
- **Void Transfer:** Dari `InTransit` (kembalikan asal) atau `Completed` (storno simetris asal $\leftrightarrow$ tujuan) $\rightarrow$ `Void`.
