# Rule: Sollu App Inventory Module Standards

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
   - Pesan error validasi SoD wajib menggunakan standar bahasa Indonesia Sollu yang ramah dan komunikatif.

---

## 5. Komunikasi Lintas Modul & Integrasi Penjualan (Decoupling)

1. **🚨 LARANGAN KERAS MUTASI LANGSUNG:**
   - Modul POS / Kasir / Transaksi / Penjualan **DILARANG KERAS** melakukan operasi Eloquent `insert`/`update`/`delete` langsung pada tabel `inventory_balances`, `inventory_cost_layers`, atau `inventory_movements`.
2. **Pemotongan Stok Penjualan:**
   - Modul Transaksi WAJIB memanggil `App\Services\App\Transaction\InventoryDeductionService` atau melempar Domain Event (`TransactionCompleted`) yang ditangkap oleh listener inventori.
3. **Komposisi Resep / Bahan Baku:**
   - Pemotongan item komposit/resep diproses secara transaksional per bahan penyusun dengan mencatat movement `InventoryMovementType::SalesOut`.

---

## 6. Standar Backend (Controllers, Services, Requests)

1. **Thin Controller:**
   - Controller di `app/Http/Controllers/App/Inventory/` hanya bertugas menerima HTTP request, memverifikasi otorisasi (`$this->authorize` / `v-can`), memanggil Domain Service, dan merender Inertia/JSON response.
2. **Database Transaction:**
   - Setiap operasi mutasi inventori di Service Layer WAJIB dibungkus dalam `DB::transaction(function () { ... })`.
3. **Form Request Standards:**
   - Request di `app/Http/Requests/App/Inventory/` wajib meng-extend `BaseInertiaFormRequest`.
   - Gunakan `Rule::enum(EnumName::class)` untuk validasi kolom enum.
   - Sertakan validasi sorting (`sort` dan `direction`) via trait `SortableModel`.
4. **Anti Over-Fetching di `index()`:**
   - Endpoint `index()` HANYA me-load data ringkasan paginasi.
   - Detail resep, antrean cost layer, dan riwayat mutasi lengkap WAJIB dimuat secara on-demand via `show()` atau async endpoint saat drawer dibuka.

---

## 7. Standar Frontend (Pages, Components & UI Ergonomics)

1. **Struktur Layout Halaman:**
   - Seluruh halaman modul inventori (`Stock`, `Movement`, `Purchase`, `Adjustment`, `StockOpname`, `Transfer`, `Supplier`, `RawMaterial`) WAJIB menggunakan `<MainPage>`.
   - Slot non-scrolling:
     - `<template #header>`: `MainPageHeader` (Judul & deskripsi).
     - `<template #widgets>`: Kartu KPI / metrik stok (`StockWidgets.vue`).
     - `<template #filter>`: Toolbar terpadu (`ActionBar`).
   - Default slot: Tabel data (`<Table>`).
   - `<template #footer>`: Paginasi (`<Pagination>`).
2. **Ekstraksi Wajib Komponen Filter:**
   - Toolbar filter WAJIB diekstrak ke `Components/{Entity}Filter.vue` (misal: `StockFilter.vue`, `PurchaseFilter.vue`, `Filter.vue`).
   - Gunakan `<ActionBar>`:
     - Sisi Kiri (`#filters`): Preset tanggal (`FilterPresetDate`), status segmented (`FilterSegmented`), filter dropdown (`FilterDropdown`).
     - Sisi Kanan: Pencarian (`FilterSearch`), dropdown berkas (`ActionsDropdown label="Opsi Data"`), dan tombol Tambah (`+ Tambah ...`) di **PALING KANAN**.
3. **Standar PopUp Drawer (`PopUpPage`):**
   - Form pembuatan/edit dan tampilan detail WAJIB menggunakan drawer `<PopUpPage>` melalui `usePopUpStore()`.
   - Body modal sudah memiliki padding bawaan; child form DILARANG menambahkan wrapper margin/padding ganda.
4. **Tabel & Aksi Baris (Single Action vs Multiple Actions):**
   - **Single Action (Buka Detail / Edit):** Gunakan `@row-click="openDetail"` pada `<Table>` dengan `:action="false"` bawaan. DILARANG membuat tombol tunggal di dalam slot `#actions`.
   - **Multiple Actions:** Aktifkan `:action="true"` HANYA jika terdapat $>1$ tombol independen (misal: Cetak PDF + Batalkan).
5. **Zero Raw HTML Inputs:**
   - Seluruh form input WAJIB menggunakan komponen terstandarisasi di `@/Components/Form/` (`TextField`, `DropdownField`, `NumberField`, `SelectionGroupField`, `Switch`).

---

## 8. Standar Pengujian Otomatis (Automated Testing)

1. **Unit Testing Service (100% Mocking):**
   - Lokasi: `tests/Unit/Services/App/Inventory/` (`InventoryCostingServiceTest.php`, `StockAdjustmentServiceTest.php`, `StockOpnameServiceTest.php`, `StockTransferServiceTest.php`, `PurchaseOrderServiceTest.php`, `StockFreezeServiceTest.php`).
   - Unit test service fokus pada kalkulasi matematika COGS, alokasi layer FIFO, pembaruan moving average, dan transisi status.
2. **Feature & Integration Testing:**
   - Lokasi: `tests/Feature/App/Inventory/`.
   - Uji isolasi tenant (`business_id`), pencegahan mutasi saat outlet beku (`stock.not.frozen`), hak akses peran (`PermissionEnum`), dan plan feature gating (`FeatureEnum`).

---

## 9. Anti-Patterns & Larangan Keras (Do's & Don'ts)

| Anti-Pattern (SALAH) | Standar Baku (BENAR) | Rationale / Dampak |
| :--- | :--- | :--- |
| Mengubah `InventoryBalance::update(['current_stock' => ...])` langsung. | Panggil `InventoryCostingService::recordIncomingStock()` atau `recordOutgoingStock()`. | Mencegah rusaknya antrean FIFO layer dan integritas buku besar mutasi. |
| Hardcode string status seperti `if ($status === 'draft')`. | Gunakan enum `$enums.AdjustmentStatus.Draft` / `AdjustmentStatus::Draft`. | Menjamin konsistensi tipe dan mencegah bug typo. |
| Melakukan hardcode validasi `if ($user->id === $created_by)` untuk self-approval. | Panggil `InventorySodService::assertCanApprove*()` yang membaca `business->settings['inventory_sod']`. | Menjaga fleksibilitas bagi merchant mikro/kecil dan kepatuhan bagi enterprise. |
| Modul POS / Transaksi memanggil model `InventoryBalance::decrement()`. | Modul POS memanggil `InventoryDeductionService` atau memancarkan Domain Event. | Menjaga arsitektur Modular Monolith dan batas domain yang bersih. |
| Menulis toolbar filter dan search inline di dalam `Index.vue`. | Ekstrak filter ke dalam komponen `Components/{Entity}Filter.vue` berbasis `ActionBar`. | Kerapian kode, modularitas, dan reusability. |
| Menggunakan modal popup dialog (`FilterModal.vue`) untuk memfilter tabel. | Gunakan filter terpadu inline di slot `#filter` `ActionBar`. | Sesuai standar UX Sollu App (Flat Minimalis & Ergonomis). |
| Me-load relasi `costLayers` atau seluruh item detail pada `index()`. | Muat detail dan relasi berat secara on-demand via `show()` / PopUp drawer. | Mencegah memory leak dan degradasi performa render Inertia. |

---

## 10. Protokol Pemeliharaan Mandiri Agen (Self-Evolution Mandate)

> [!IMPORTANT]
> **KEWAJIBAN PEMELIHARAAN OTOMATIS OLEH AI AGENT:**
> Setiap kali seorang AI Agent melakukan salah satu dari tindakan berikut:
> 1. Menambahkan entitas, relasi, atau tabel baru pada domain inventori.
> 2. Menambah kasus baru pada enum inventori (`InventoryMovementType`, `AdjustmentReason`, dll.).
> 3. Mengubah formula atau logika pada `InventoryCostingService` / Service inventori lainnya.
> 4. Mengubah alur otorisasi, middleware, atau rute `routes/app/inventories.php`.
> 5. Merefaktor atau menambahkan komponen Vue/Inertia pada halaman inventori.
>
> **Agent WAJIB secara proaktif memperbarui dan merefaktor file aturan ini (`.agents/rules/inventory-rules.md`)** agar tabel matriks, enum, dan invariant di atas selalu akurat dan sinkron dengan codebase riil. Jadikan pembaruan aturan ini sebagai bagian dari *Definition of Done (DoD)* sebelum menyelesaikan pekerjaan.
