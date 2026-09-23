---
name: domain-inventory
description: >-
    Knowledge and standards for Inventory Domain in Sollu App. Covers InventoryItem, InventoryBalance,
    InventoryCostLayer (FIFO/Moving Average), InventoryMovement ledger, StockAdjustment, StockOpname,
    StockTransfer, PurchaseOrder, GoodsReceipt, PurchaseReturn, Stock Freeze Guard, and Segregation of Duties (SoD).
    Activate whenever working on inventory, stock costing, transfers, opname, goods receipt, or supplier returns.
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
1. Semua mutasi stok (Penyesuaian Stok, Transfer, PO Receive, Void) WAJIB memeriksa status pembekuan via `$this->stockFreezeService->assertNotFrozen($outlet)`.
2. Middleware route: `stock.not.frozen`.
3. Transfer stok WAJIB memvalidasi kedua outlet: `$fromOutlet` dan `$toOutlet` tidak beku.

### 4.2. Kebijakan Pemisahan Tugas (Segregation of Duties / SoD)
Dievaluasi terpusat via `App\Services\App\Inventory\InventorySodService` berdasarkan `business->settings['inventory_sod']`:
- **Stock Adjustment:** Draf creator dilarang menyetujui drafnya sendiri.
- **Stock Opname:** Petugas hitungan fisik dilarang menyetujui opname.
- **Transfer Approval:** Pengaju transfer dilarang menyetujui transfer.
- **Transfer Receive:** Pengirim dilarang mengeksekusi penerimaan di outlet tujuan.
- **PO Receive:** Pembuat PO dilarang mencatat penerimaan fisik barang.
- **DILARANG KERAS** menulis pengecekan hardcode manual di controller. WAJIB panggil `InventorySodService`.

---

## 5. Komunikasi Lintas Modul & Integrasi Penjualan (Decoupling)

1. **LARANGAN KERAS MUTASI LANGSUNG:** Modul POS / Kasir / Penjualan dilarang mutasi langsung ke `inventory_balances`, `inventory_cost_layers`, atau `inventory_movements`.
2. **Pemotongan Stok:** Transaksi memanggil `InventoryDeductionService` atau melempar Domain Event (`TransactionCompleted`).
3. **Resep / Bahan Baku:** Diproses secara transaksional per bahan penyusun dengan mencatat movement `InventoryMovementType::SalesOut`.

---

## 6. Standar Backend & Frontend Khusus Inventori

1. **Thin Controller:** Controller di `app/Http/Controllers/App/Inventory/` hanya HTTP request, otorisasi, panggil Service, dan render respons.
2. **Database Transaction:** Setiap mutasi di Service Layer WAJIB dibungkus `DB::transaction(function () { ... })`.
3. **Anti Over-Fetching:** `index()` HANYA paginasi ringkasan. Detail cost layer & riwayat pergerakan dimuat on-demand via `show()` / PopUp drawer.
4. **Layout Halaman:** Menggunakan `<MainPage>`, `ActionBar` diekstrak ke `{Entity}Filter.vue`, single action row click `@row-click="openDetail"` dengan `:action="false"`.
