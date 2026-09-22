# PRD — Flexible Purchase Unit & Manual Conversion

## 1. Informasi Dokumen

| Item    | Detail                          |
| ------- | ------------------------------- |
| Product | POS Multi-Merchant Multi-Outlet |
| Feature | Pembelian Stok                  |
| Status  | Draft                           |
| Versi   | 1.0                             |
| Bahasa  | Indonesia                       |

---

# 2. Ringkasan

Fitur ini memungkinkan merchant melakukan pembelian inventory dengan **satuan pembelian yang berbeda dari satuan produk yang digunakan di sistem POS**.

Sistem hanya mengenal **1 satuan utama pada produk**, yaitu satuan yang digunakan untuk:

- inventory;
- stock movement;
- penjualan;
- stock opname;
- transfer antar-outlet;
- inventory valuation;
- perhitungan COGS.

Tidak terdapat pengaturan berbagai UOM pada master produk.

Jika supplier menjual barang dengan satuan yang berbeda, user dapat memasukkan satuan tersebut secara langsung pada transaksi pembelian.

Konversi dari satuan pembelian ke satuan produk dilakukan **secara manual oleh user ketika melakukan penerimaan barang**.

### Contoh

Produk:

> Aqua 600 ml

Satuan produk:

> Botol

Supplier menjual:

> Dus

Pembelian:

> 10 Dus

Ketika barang diterima, user menentukan:

> 1 Dus = 24 Botol

Maka inventory bertambah:

> 10 × 24 = 240 Botol

---

# 3. Tujuan

## 3.1 Tujuan Utama

Menyediakan proses pembelian yang fleksibel tanpa membuat sistem UOM menjadi kompleks.

Sistem harus memungkinkan:

1. Produk hanya memiliki satu satuan.
2. User membeli menggunakan satuan yang berbeda.
3. Satuan pembelian dapat berbeda setiap transaksi.
4. Konversi dilakukan saat penerimaan barang.
5. Inventory selalu dicatat menggunakan satuan produk.
6. Harga pembelian tetap dapat disimpan berdasarkan satuan pembelian.
7. Sistem tetap dapat menghitung biaya per satuan inventory.
8. Partial receiving tetap dapat dilakukan.
9. Purchase Order dan penerimaan dapat menggunakan satuan yang sama.
10. Konversi tidak mengubah master produk.

---

# 4. Non-Goals

Fitur ini **tidak** mencakup:

- master UOM;
- multi-UOM pada produk;
- konfigurasi conversion factor pada produk;
- conversion factor permanen pada produk;
- conversion factor permanen pada supplier-product;
- otomatisasi konversi berdasarkan histori pembelian;
- otomatisasi konversi berdasarkan barcode UOM;
- penggantian satuan inventory produk;
- penggunaan beberapa satuan inventory untuk produk yang sama.

Dengan desain ini:

> **Conversion factor adalah informasi transaksi, bukan konfigurasi produk.**

---

# 5. Prinsip Desain

## 5.1 Produk hanya memiliki satu UOM

Contoh:

```text
Produk:
Indomie Goreng

UOM:
PCS
```

Sistem tidak menyimpan:

```text
PCS
PACK
BOX
CARTON
```

sebagai UOM produk.

---

## 5.2 Satuan pembelian bersifat transaksional

Jika supplier menjual:

```text
1 Karton = 40 PCS
```

maka `Karton` hanya dicatat pada transaksi pembelian.

Tidak perlu membuat:

```text
Product Unit:
Karton
```

---

## 5.3 Conversion dilakukan ketika receiving

Purchase:

```text
10 Karton
```

Receiving:

```text
1 Karton = 40 PCS
```

Inventory:

```text
+400 PCS
```

---

## 5.4 Inventory hanya menggunakan UOM produk

Seluruh stock movement harus menggunakan UOM produk.

Contoh:

```text
Purchase:
10 Karton

Receiving:
400 PCS

Inventory:
+400 PCS
```

Tidak boleh ada stock ledger:

```text
+10 Karton
```

karena `Karton` bukan UOM inventory produk.

---

# 6. Terminologi

| Terminologi             | Definisi                                                   |
| ----------------------- | ---------------------------------------------------------- |
| Product UOM             | Satu-satunya satuan yang dimiliki produk di sistem         |
| Purchase UOM            | Satuan yang digunakan supplier dalam transaksi pembelian   |
| Purchase Quantity       | Jumlah barang yang dipesan/dibeli berdasarkan Purchase UOM |
| Conversion Factor       | Jumlah Product UOM dalam satu Purchase UOM                 |
| Received Purchase Qty   | Jumlah Purchase UOM yang diterima                          |
| Received Inventory Qty  | Jumlah Product UOM yang masuk inventory                    |
| Goods Receipt           | Dokumen penerimaan barang                                  |
| Base/Inventory Quantity | Quantity yang digunakan dalam stock ledger                 |

---

# 7. Contoh Penggunaan

## 7.1 Pembelian dengan satuan yang sama

Produk:

```text
Gula
UOM = KG
```

Pembelian:

```text
Quantity = 20 KG
```

Receiving:

```text
Received = 20 KG
Conversion = 1
```

Inventory:

```text
+20 KG
```

---

## 7.2 Pembelian dengan satuan berbeda

Produk:

```text
Air Mineral 600ml
UOM = BOTOL
```

Pembelian:

```text
10 DUS
```

Receiving:

```text
Received = 10 DUS
1 DUS = 24 BOTOL
```

Inventory:

```text
10 × 24
= 240 BOTOL
```

---

## 7.3 Konversi berbeda pada transaksi berikutnya

Pembelian pertama:

```text
10 DUS
1 DUS = 24 BOTOL

Inventory = 240 BOTOL
```

Pembelian berikutnya:

```text
5 DUS
1 DUS = 30 BOTOL

Inventory = 150 BOTOL
```

Sistem **tidak menganggap conversion factor 24 sebagai konfigurasi produk**.

Histori transaksi tetap menyimpan:

```text
Purchase #001
Conversion = 24

Purchase #002
Conversion = 30
```

---

# 8. User Flow

## 8.1 Direct Purchase

Untuk usaha kecil, user dapat melakukan pembelian langsung.

```text
Pembelian Baru
      ↓
Pilih Supplier
      ↓
Tambah Barang
      ↓
Masukkan Purchase UOM
      ↓
Masukkan Quantity
      ↓
Masukkan Harga
      ↓
Simpan Pembelian
      ↓
Penerimaan Barang
      ↓
Masukkan Conversion
      ↓
Post Receiving
      ↓
Inventory Bertambah
```

---

# 9. Purchase Form

## 9.1 Header

Field:

| Field            | Required | Keterangan                  |
| ---------------- | -------: | --------------------------- |
| Supplier         |       Ya | Supplier pembelian          |
| Outlet           |       Ya | Outlet tujuan inventory     |
| Purchase Date    |       Ya | Tanggal pembelian           |
| Reference Number |    Tidak | Nomor invoice/nota supplier |
| Notes            |    Tidak | Catatan                     |

---

## 9.2 Purchase Item

Field minimal:

| Field        | Required |
| ------------ | -------: |
| Product      |       Ya |
| Purchase UOM |       Ya |
| Quantity     |       Ya |
| Unit Price   |       Ya |
| Discount     |    Tidak |
| Tax          |    Tidak |
| Notes        |    Tidak |

Contoh:

```text
Produk      : Aqua 600ml
UOM Produk  : Botol

Satuan Beli : Dus
Qty         : 10
Harga       : Rp48.000 / Dus
```

Sistem menampilkan UOM produk sebagai informasi:

```text
Inventory UOM: Botol
```

tetapi user tidak mengubahnya dari form pembelian.

---

# 10. Purchase UOM

Purchase UOM merupakan **free-form transactional value**.

Contoh:

```text
PCS
Pack
Box
Dus
Karton
Karung
Sak
Tray
Liter
Kg
```

Namun nilai tersebut tidak menjadi master UOM produk.

Sistem dapat menyediakan pilihan UOM umum untuk mempercepat input, tetapi pilihan tersebut hanya membantu input transaksi dan **tidak mengubah konfigurasi produk**.

Jika diperlukan, user juga dapat memasukkan satuan secara manual.

---

# 11. Goods Receipt

Goods Receipt merupakan titik di mana inventory benar-benar berubah.

Contoh:

```text
Purchase:

10 Dus
Rp48.000 / Dus
```

Pada receiving:

```text
Purchase UOM:
Dus

Received:
10

Conversion:
1 Dus = 24 Botol
```

Sistem menghitung:

```text
Inventory Qty
= Received Qty × Conversion Factor

= 10 × 24

= 240 Botol
```

---

# 12. Receiving dengan Satuan yang Sama

Jika:

```text
Product UOM = PCS
Purchase UOM = PCS
```

maka conversion factor:

```text
1
```

Contoh:

```text
Purchase:
100 PCS

Receiving:
100 PCS

Conversion:
1

Inventory:
+100 PCS
```

User tidak harus mengisi conversion secara eksplisit jika Purchase UOM sama dengan Product UOM.

---

# 13. Partial Receiving

Sistem harus mendukung partial receiving.

Purchase:

```text
100 Dus
```

Receiving pertama:

```text
40 Dus

Conversion:
1 Dus = 24 PCS

Inventory:
+960 PCS
```

Receiving kedua:

```text
60 Dus

Conversion:
1 Dus = 24 PCS

Inventory:
+1.440 PCS
```

Total:

```text
100 Dus
2.400 PCS
```

---

# 14. Conversion Berbeda pada Partial Receiving

Sistem harus mengizinkan conversion berbeda antar receiving apabila kondisi fisik/packaging memang berbeda.

Contoh:

Purchase:

```text
100 Dus
```

Receiving #1:

```text
40 Dus
1 Dus = 24 PCS

Inventory:
+960 PCS
```

Receiving #2:

```text
60 Dus
1 Dus = 20 PCS

Inventory:
+1.200 PCS
```

Total inventory:

```text
2.160 PCS
```

Sistem tidak boleh memaksakan conversion factor dari receiving sebelumnya.

Namun perubahan tersebut harus tercatat dalam audit trail.

---

# 15. Purchase Price

Harga pembelian mengacu pada Purchase UOM.

Contoh:

```text
Purchase UOM = Dus
Quantity = 10
Unit Price = Rp48.000

Total = Rp480.000
```

Setelah receiving:

```text
10 Dus × 24
= 240 Botol
```

Maka biaya ekuivalen:

```text
Rp480.000 / 240
= Rp2.000 / Botol
```

Nilai ini dapat digunakan oleh inventory valuation sesuai metode valuation yang digunakan merchant.

---

# 16. Purchase Price dan Conversion

Sistem harus menyimpan data asli transaksi.

Contoh:

```text
Purchase Qty:
10

Purchase UOM:
Dus

Unit Price:
48.000

Received Qty:
10

Conversion Factor:
24

Inventory Qty:
240

Product UOM:
Botol
```

Jangan hanya menyimpan:

```text
Inventory Qty = 240
```

karena informasi transaksi supplier akan hilang.

---

# 17. Purchase tanpa Receiving

Jika merchant menggunakan workflow PO:

```text
Purchase Order
```

tidak boleh menambah inventory.

Contoh:

```text
PO:
100 Dus
```

Inventory:

```text
Tidak berubah
```

Setelah receiving:

```text
Received:
100 Dus

Conversion:
24

Inventory:
+2.400 Botol
```

---

# 18. Purchase Order

Purchase Order harus menyimpan:

```text
Product
Purchase UOM
Ordered Quantity
Unit Price
Discount
Tax
```

Contoh:

```text
Product:
Aqua 600ml

Product UOM:
Botol

Purchase UOM:
Dus

Ordered:
100

Price:
48.000
```

PO tidak perlu mengetahui jumlah inventory final karena conversion baru ditetapkan saat receiving.

---

# 19. Receiving dari PO

Pada receiving, sistem menampilkan:

```text
Product:
Aqua 600ml

Product UOM:
Botol

Purchase UOM:
Dus

Ordered:
100 Dus

Previously Received:
40 Dus

Outstanding:
60 Dus
```

User mengisi:

```text
Receive:
60 Dus

Conversion:
1 Dus = 24 Botol
```

Sistem:

```text
Inventory:
+1.440 Botol
```

---

# 20. Over Receiving

Sistem harus memiliki konfigurasi apakah over receiving diperbolehkan.

Contoh:

```text
PO:
100 Dus
```

Supplier mengirim:

```text
105 Dus
```

Configuration:

```text
Allow Over Receiving:
ON
```

Maka sistem dapat menerima:

```text
105 Dus
```

Jika:

```text
Allow Over Receiving:
OFF
```

maka receiving di atas outstanding quantity harus ditolak atau memerlukan approval.

---

# 21. Conversion Tidak Mengubah Master Product

Setelah transaksi:

```text
Product:
Aqua 600ml
UOM:
Botol
```

tetap sama.

Sistem tidak menambahkan:

```text
Dus
```

ke product.

Conversion hanya menjadi bagian dari:

```text
Purchase/Receipt Transaction
```

---

# 22. Inventory Movement

Inventory movement harus menggunakan Product UOM.

Contoh:

```text
Goods Receipt
--------------------------
Product:
Aqua 600ml

Purchase Qty:
10 Dus

Conversion:
24

Inventory Qty:
240 Botol
```

Stock Ledger:

```text
Date        Type          Qty
21 Sep      Purchase      +240 Botol
```

---

# 23. Inventory Valuation

Jika merchant menggunakan inventory valuation:

```text
Purchase Cost
÷
Inventory Quantity
```

dapat menghasilkan unit cost.

Contoh:

```text
Purchase:
10 Dus × Rp48.000
= Rp480.000

Inventory:
240 Botol

Unit Cost:
Rp2.000 / Botol
```

Inventory valuation tetap menggunakan Product UOM.

---

# 24. Purchase Return

Purchase return harus mempertimbangkan dua quantity:

```text
Purchase UOM
Inventory UOM
```

Contoh:

Receiving:

```text
10 Dus
1 Dus = 24 Botol

Inventory:
240 Botol
```

Return:

```text
2 Dus
Conversion:
24

Inventory:
-48 Botol
```

Return harus menyimpan conversion yang digunakan agar histori transaksi tetap konsisten.

---

# 25. Koreksi Receiving

Jika user salah memasukkan:

```text
1 Dus = 12 Botol
```

padahal seharusnya:

```text
1 Dus = 24 Botol
```

sistem tidak boleh mengubah stock secara langsung tanpa histori.

Gunakan:

```text
Receiving Adjustment
```

atau mekanisme reversal/re-posting.

Contoh:

```text
Original:
+120 Botol

Correction:
-120 Botol

Correct Receipt:
+240 Botol
```

Semua perubahan harus tercatat pada audit trail.

---

# 26. Multi-Outlet

Purchase harus memiliki tujuan inventory yang jelas.

Contoh:

```text
Merchant
   ├── Outlet A
   ├── Outlet B
   └── Outlet C
```

Purchase:

```text
Outlet A
10 Dus
```

Receiving:

```text
Outlet A
10 Dus
× 24

Inventory Outlet A:
+240 Botol
```

Outlet B tidak terpengaruh.

---

# 27. Central Purchasing

Sistem juga dapat mendukung:

```text
Head Office
     ↓
Purchase
     ↓
Central Warehouse
     ↓
Transfer
     ├── Outlet A
     ├── Outlet B
     └── Outlet C
```

Purchase UOM tetap hanya berlaku pada proses purchasing/receiving.

Setelah masuk inventory, stock menggunakan Product UOM.

---

# 28. Data Model Konseptual

## Product

```text
products
---------
id
merchant_id
name
inventory_uom
...
```

Product hanya memiliki:

```text
inventory_uom
```

Tidak ada:

```text
purchase_uom
sales_uom
conversion_factor
```

---

## Purchase

```text
purchases
---------
id
merchant_id
outlet_id
supplier_id
purchase_date
reference_number
status
subtotal
discount
tax
total
...
```

---

## Purchase Item

```text
purchase_items
--------------
id
purchase_id
product_id

purchase_uom
purchase_qty
unit_price

discount
tax
subtotal
...
```

`purchase_uom` merupakan nilai transaksi.

---

## Goods Receipt

```text
goods_receipts
--------------
id
purchase_id
outlet_id
received_at
status
...
```

---

## Goods Receipt Item

```text
goods_receipt_items
-------------------
id
goods_receipt_id
purchase_item_id
product_id

purchase_uom
received_purchase_qty

product_uom
conversion_factor
received_inventory_qty

unit_cost
total_cost
...
```

### Formula

```text
received_inventory_qty
=
received_purchase_qty
×
conversion_factor
```

---

# 29. Data Integrity

Sistem harus menyimpan snapshot informasi transaksi.

Jika Product UOM berubah di masa depan, histori transaksi tidak boleh berubah.

Contoh:

Purchase tahun 2026:

```text
Product UOM:
PCS
```

Purchase:

```text
10 Dus
Conversion:
24
```

Jika kemudian nama/konfigurasi produk berubah, historical receipt tetap:

```text
10 Dus
× 24
= 240 PCS
```

---

# 30. Validasi

## Purchase

- Product wajib dipilih.
- Purchase UOM wajib diisi.
- Quantity harus > 0.
- Unit price tidak boleh negatif.
- Product harus tersedia pada outlet yang dipilih.
- Supplier harus valid jika supplier diwajibkan oleh merchant.

## Receiving

- Received quantity harus > 0.
- Received quantity tidak boleh melebihi outstanding quantity jika over receiving disabled.
- Conversion factor harus > 0.
- Product UOM harus ditampilkan sebagai informasi.
- Inventory quantity harus dihitung sistem.
- User tidak boleh mengubah hasil inventory quantity secara langsung.

---

# 31. Audit Trail

Perubahan berikut harus dapat dilacak:

- perubahan quantity;
- perubahan Purchase UOM;
- perubahan harga;
- perubahan conversion factor;
- perubahan received quantity;
- pembatalan receiving;
- reversal receiving;
- adjustment inventory akibat koreksi receiving.

Minimal informasi:

```text
Who
When
What changed
Old Value
New Value
Reason
```

---

# 32. Reporting

Sistem harus dapat menyediakan informasi:

### Purchase Report

```text
Product
Purchase UOM
Purchase Qty
Unit Price
Total
```

Contoh:

```text
Aqua 600ml
10 Dus
Rp48.000
Rp480.000
```

### Inventory Report

```text
Product
Inventory UOM
Inventory Qty
```

Contoh:

```text
Aqua 600ml
240 Botol
```

### Purchase Conversion Report

Opsional:

```text
Product
Purchase UOM
Purchase Qty
Conversion
Inventory Qty
```

Contoh:

```text
Aqua 600ml
10 Dus
24
240 Botol
```

---

# 33. UX Recommendation

Pada Purchase Item, tampilkan:

```text
Produk
Aqua 600ml

Satuan Produk
Botol

Satuan Pembelian
[ Dus ]

Jumlah
[ 10 ]

Harga / Satuan
[ 48.000 ]
```

Pada Receiving:

```text
Produk
Aqua 600ml

Satuan Produk
Botol

Satuan Pembelian
Dus

Jumlah Dipesan
10 Dus

Jumlah Diterima
[ 10 ] Dus

Konversi
[ 24 ] Botol / Dus

Jumlah Masuk Inventory
240 Botol
```

**`Jumlah Masuk Inventory` harus read-only.**

---

# 34. Prinsip UX Penting

User tidak boleh dipaksa memahami konsep UOM yang kompleks.

User cukup berpikir:

> "Saya membeli 10 dus."

Kemudian ketika barang datang:

> "1 dus berisi 24 botol."

Sistem yang menghitung:

> "Inventory bertambah 240 botol."

---

# 35. Edge Cases

## Case 1 — Supplier menjual satuan yang berbeda

```text
Purchase:
5 Karton

Conversion:
1 Karton = 48 PCS

Inventory:
240 PCS
```

---

## Case 2 — Supplier menjual berdasarkan berat

```text
Product UOM:
KG

Purchase:
5 Karung

Conversion:
1 Karung = 25 KG

Inventory:
125 KG
```

---

## Case 3 — Isi kemasan tidak konsisten

Purchase #1:

```text
10 Dus
1 Dus = 24 PCS
```

Purchase #2:

```text
10 Dus
1 Dus = 20 PCS
```

Keduanya diperbolehkan karena conversion merupakan data transaksi.

---

## Case 4 — Barang diterima kurang

Purchase:

```text
10 Dus
```

Receiving:

```text
8 Dus
```

Outstanding:

```text
2 Dus
```

Inventory hanya bertambah sesuai 8 Dus.

---

## Case 5 — Barang diterima sebagian dengan conversion berbeda

Receiving #1:

```text
5 Dus × 24
= 120 PCS
```

Receiving #2:

```text
5 Dus × 20
= 100 PCS
```

Total:

```text
220 PCS
```

---

# 36. Acceptance Criteria

### AC-01 — Single Product UOM

**Given** sebuah produk memiliki UOM `PCS`

**When** user membuka master produk

**Then** hanya terdapat satu UOM produk yaitu `PCS`.

Tidak tersedia konfigurasi:

- Purchase UOM;
- Sales UOM;
- Conversion UOM.

---

### AC-02 — Different Purchase UOM

**Given**

```text
Product UOM = PCS
```

**When**

user membuat pembelian dengan:

```text
Purchase UOM = Dus
Qty = 10
```

**Then**

purchase berhasil disimpan tanpa mengubah master produk.

---

### AC-03 — Manual Conversion

**Given**

```text
Purchase = 10 Dus
Product UOM = PCS
```

**When**

user melakukan receiving:

```text
Conversion = 24 PCS / Dus
```

**Then**

inventory bertambah:

```text
240 PCS
```

---

### AC-04 — Same UOM

**Given**

```text
Product UOM = PCS
Purchase UOM = PCS
```

**Then**

conversion factor default:

```text
1
```

---

### AC-05 — Partial Receiving

**Given**

```text
PO = 100 Dus
```

**When**

user menerima:

```text
40 Dus
```

**Then**

outstanding:

```text
60 Dus
```

dan inventory hanya bertambah sesuai 40 Dus.

---

### AC-06 — Historical Conversion

**Given**

Purchase #1:

```text
10 Dus × 24
```

**And**

Purchase #2:

```text
10 Dus × 30
```

**Then**

kedua transaksi mempertahankan conversion masing-masing.

---

### AC-07 — Inventory Ledger

**Given**

```text
10 Dus
Conversion = 24
```

**Then**

inventory ledger mencatat:

```text
+240 Product UOM
```

dan bukan:

```text
+10 Dus
```

---

### AC-08 — Product Master Isolation

**Given**

user melakukan pembelian:

```text
10 Dus
Conversion = 24
```

**Then**

master produk tidak berubah dan tidak mendapatkan UOM baru bernama `Dus`.

---

# 37. Business Rules

1. Setiap produk memiliki tepat satu Product/Inventory UOM.
2. Purchase UOM adalah data transaksi.
3. Purchase UOM tidak menjadi bagian dari master product.
4. Conversion factor adalah data receiving.
5. Conversion factor dapat berbeda antar receiving.
6. Inventory selalu menggunakan Product UOM.
7. PO tidak mengubah inventory.
8. Goods Receipt mengubah inventory.
9. Purchase price disimpan berdasarkan Purchase UOM.
10. Inventory cost dihitung berdasarkan inventory quantity yang dihasilkan oleh conversion.
11. Historical conversion tidak boleh berubah setelah transaksi diposting.
12. Koreksi receiving harus menghasilkan audit trail.
13. Partial receiving harus didukung.
14. Partial payment tidak bergantung pada conversion UOM.
15. Purchase return harus menggunakan informasi conversion dari transaksi terkait atau conversion yang secara eksplisit ditentukan pada return.
16. Perubahan master Product UOM tidak boleh mengubah histori stock movement.

---

# 38. Contoh End-to-End

## Product

```text
Product:
Indomie Goreng

Product UOM:
PCS
```

## Purchase

Supplier:

```text
PT Distributor ABC
```

User membeli:

```text
100 Karton
Harga:
Rp120.000 / Karton
```

Purchase:

```text
Purchase UOM:
Karton

Quantity:
100

Unit Price:
120.000

Total:
12.000.000
```

## Receiving

Barang pertama datang:

```text
Received:
60 Karton

Conversion:
1 Karton = 40 PCS
```

Inventory:

```text
60 × 40
= 2.400 PCS
```

Barang kedua datang:

```text
Received:
40 Karton

Conversion:
1 Karton = 40 PCS
```

Inventory:

```text
40 × 40
= 1.600 PCS
```

Total inventory:

```text
4.000 PCS
```

Total purchase:

```text
100 Karton
Rp12.000.000
```

Effective cost:

```text
Rp12.000.000 / 4.000
= Rp3.000 / PCS
```

---

# 39. Keputusan Arsitektur

Model ini sengaja memilih pendekatan:

```text
Simple Product
      +
Flexible Transaction
```

daripada:

```text
Complex Product
      +
Fixed UOM Configuration
```

Dengan demikian merchant tidak perlu melakukan konfigurasi:

```text
1 Carton = 40 PCS
1 Box = 24 PCS
1 Pack = 6 PCS
```

di master produk.

Semua informasi tersebut hanya diperlukan ketika transaksi pembelian benar-benar terjadi.

Arsitektur akhirnya:

```text
                PRODUCT
                   │
            Product UOM = PCS
                   │
                   │
                   ▼
             PURCHASE
                   │
          Purchase UOM = Dus
          Purchase Qty = 10
                   │
                   ▼
            GOODS RECEIPT
                   │
          Conversion = 24
                   │
                   ▼
           INVENTORY MOVEMENT
                   │
             +240 PCS
```

## Core Principle

> **Product hanya mengenal satu UOM. Purchase boleh menggunakan satuan apa pun. Conversion dilakukan secara manual pada saat receiving, dan hanya hasil konversinya yang masuk ke inventory.**
