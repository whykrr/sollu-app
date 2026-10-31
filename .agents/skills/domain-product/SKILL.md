---
name: domain-product
description: >-
    Knowledge and standards for Product Domain in Sollu App. Covers Unified Item Pattern
    (Product -> ProductItem), variant option pivot (product_item_variant_group_options),
    inventory decoupling (track_inventory), commercial causality (sellable => is_show),
    SaaS 4-tier feature gating, 2-level category hierarchy, RawMaterial, Recipe BOM
    versions per variant, Modifier groups, and Master Data Sync for POS.
    Activate whenever working on products, variants, raw materials, recipes, modifiers, or catalog APIs.
---

# Domain Skill: Modul Produk Terpadu (Unified Product Domain)

Panduan arsitektur dan aturan baku rekayasa perangkat lunak untuk **Modul Produk** pada **Sollu App** (Laravel 12 & Inertia Vue 3).

---

## 1. Ringkasan Arsitektur & Unified Item Pattern

Modul Produk mengadopsi standar **Unified Item Pattern** (Shopify/Square):
- Setiap entitas produk (`products`) bertindak sebagai wadah katalog payung.
- Seluruh unit jual, SKU, barcode, harga, dan keterikatan inventaris tersimpan pada tabel `product_items`.
- **Produk Single (Non-Varian):** Memiliki tepat 1 baris default `product_items` dengan `variant_combination = null`. Barcode dan SKU utama berada di baris item ini.
- **Produk Varian:** Memiliki $N$ baris `product_items` yang dinormalisasi dengan tabel pivot `product_item_variant_group_options`.

```
┌────────────────────────────────────────────────────────────────────────┐
│                         Product Domain Map                             │
├──────────────────────────────┼─────────────────────────────────────────┤
│ Product                      │ Wadah katalog: name, category_id, type  │
│ ProductItem                  │ SKU, barcode, price, variant_combination│
│ VariantGroup                 │ Kelompok varian (e.g., Ukuran, Warna)   │
│ VariantGroupOption           │ Opsi nilai varian (e.g., S, M, L, Merah)│
│ product_item_variant_...     │ Tabel pivot relasi item ke opsi varian  │
│ RecipeVersion (+Items)       │ BOM per varian item (product_item_id)   │
│ ModifierGroup (+Options)     │ Kelompok opsi tambahan / add-ons        │
│ RawMaterial                  │ Bahan baku (item_type = raw_material)   │
│ Category                     │ Kategori berjenjang (maks 2 tingkat)    │
└──────────────────────────────┴─────────────────────────────────────────┘
```

### Tabel Relasi & Field Kunci

| Model | Tabel Database | Kunci Multi-Tenant | Karakteristik Kunci |
| :--- | :--- | :--- | :--- |
| `Product` | `products` | `business_id` | `category_id`, `type` (`basic`/`service`), `sellable`, `is_show`, `track_inventory`. |
| `ProductItem` | `product_items` | `business_id` | `product_id`, `sku`, `barcode`, `price`, `variant_combination` (`null` jika single), `is_active`. |
| `ProductItemVariantGroupOption` | `product_item_variant_group_options` | Foreign keys | Pivot: `product_item_id`, `variant_group_option_id`. |
| `RecipeVersion` | `recipe_versions` | `business_id` | `product_id`, `product_item_id` (opsional untuk BOM spesifik varian). |
| `Category` | `categories` | `business_id` | `parent_id` (maks 1 level turunan: Root $\rightarrow$ Child). |

---

## 2. Invariansi Bisnis Mutlak (Business Invariants)

1. **Kausalitas Komersial (`sellable` $\implies$ `is_show`):**
   - Jika `sellable = false`, maka `is_show` **wajib** `false`.
   - Di Vue frontend (`CreateEdit.vue` & `StepBasicInfo.vue`): Menonaktifkan switch `sellable` otomatis menonaktifkan dan men-disable switch `is_show`.
   - Di Backend: Terkunci pada `ProductService` dan model hook `Product::booted()` (event `saving`).
2. **Pemisahan Total Modul Inventori (Decoupling):**
   - Baris `inventory_items` dan `inventory_balances` **hanya dibuat jika** `track_inventory = true`.
   - Produk jasa (`type = service`), bahan non-stok, dan produk fisik tanpa pelacakan stok **tidak memiliki data** di tabel inventaris.
3. **Pembersihan Kaskade Aman (Delete Cascade Ordering):**
   - Pada `ProductService::deleteProduct`, de-aktivasi `inventoryItems` harus dijalankan **sebelum** pembersihan `productItems` untuk mencegah pelanggaran foreign key / constraint orphan data:
     ```php
     $product->inventoryItems()->update(['inventory_items.is_active' => false]);
     $product->productItems()->delete();
     $product->delete();
     ```
4. **Pembatasan Kedalaman Kategori:**
   - Kategori hanya boleh bertingkat maksimal 2 level (`parent_id` hanya boleh menunjuk ke kategori root yang `parent_id = null`). Kategori child tidak boleh menjadi parent.
5. **Persistensi & Sinkronisasi Batas Minimum Stok (`minimum_stock`):**
   - `minimum_stock` WAJIB divalidasi pada `StoreProductRequest` dan `UpdateProductRequest` (`nullable|numeric|min:0`).
   - `InventoryService::linkInventoryItem()` wajib memperbarui `minimum_stock` pada `InventoryItem`.
   - `InventoryService::syncInventoryBalances()` wajib menyinkronkan `minimum_stock` ke seluruh `inventory_balances` di setiap outlet aktif produk.
   - `ProductController::show()` dan `ProductResource` wajib memuat dan mengekspos `minimum_stock` agar ter-prefill pada form edit.
6. **Alokasi Stok Awal Multi-Outlet & Replikasi:**
   - Jika single outlet / selected outlet: tampilkan nama outlet tujuan secara eksplisit pada bagian Stok & Inventori.
   - Jika multi-outlet: tampilkan daftar per-outlet aktif produk, dilengkapi switch toggle *"Samakan semua outlet"* yang secara otomatis mereplikasi input stok awal dan HPP outlet pertama ke outlet lainnya.
   - Pada mode edit, status eligibilitas per outlet / varian dievaluasi via `ProductResource` (zero N+1) untuk mengunci field jika sudah bertransaksi.
7. **Integritas SKU & Barcode Per Merchant & Pencegahan Tabrakan Silang (*Cross-Field Collision Prevention*):**
   - SKU dan Barcode harus unik terpisah per `business_id` (mengabaikan soft-delete dan record saat ini).
   - Validasi silang: Barcode tidak boleh bernilai sama dengan SKU yang sudah ada, dan SKU tidak boleh bernilai sama dengan Barcode yang sudah ada di merchant yang sama.
   - Pengecekan in-payload: tidak boleh ada duplikasi SKU/Barcode antar varian atau antara kode produk utama dan variannya dalam satu request (`StoreProductRequest` / `UpdateProductRequest`).
   - PostgreSQL partial unique index: `(business_id, sku) WHERE deleted_at IS NULL AND sku IS NOT NULL AND sku != ''` (dan untuk barcode).
8. **Invarian Tipe Item & Model Satuan (UOM):**
   - Kolom `product_items.item_type` hanya mengizinkan nilai enum `'variant_sku'` atau `'raw_material'` (DILARANG menggunakan `'inventory'`).
   - Tabel `uoms` dan model `Uom` menggunakan kolom `code` dan `name` (DILARANG mereferensikan `short_name`).

---

## 3. SaaS 4-Tier Feature Gating

Modul Produk dilindungi oleh 4 feature flag pada `FeatureEnum`:

| Feature Flag | Tier Langganan | Deskripsi Fungsional & Proteksi |
| :--- | :--- | :--- |
| `product_catalog` | Micro+ | Akses dasar katalog produk single dan kategori. Dilindungi `plan.feature:product_catalog,service_catalog`. |
| `product_variant` | Basic+ | Matriks opsi varian, bagian Varian di form produk. Dilindungi `<FeatureLock :feature="$enums.FeatureEnum.PRODUCT_VARIANT">`. |
| `product_bom` | Pro+ | Resep bahan baku per item varian, sub-menu Bahan Baku (`/inventories/raw-materials`) dan Resep (`/master/recipes`). Dilindungi `plan.feature:product_bom`. |
| `product_modifier` | Pro+ | Opsi tambahan produk, bagian Modifier di form produk, sub-menu Modifier (`/master/modifiers`). Dilindungi `plan.feature:product_modifier`. |

---

## 4. Master Data Sync API (`MasterDataSyncService`)

Payload sinkronisasi ke POS client mengekspor:
- `products`: Data induk katalog.
- `product_items`: Seluruh varian dan default item single (`product_id`, `sku`, `barcode`, `price`, `variant_combination`).
- `product_item_variant_group_options`: Tautan pivot ID item ke ID opsi varian untuk resolusi kombinasi offline di Drift SQLite.

---

## 5. Standarisasi Ekspor & Impor Industri Berbasis Fitur & Protokol Memori

- **Template Multi-Sheet Adaptif Fitur:** Sheet 1 (*Template Impor Produk*) dinamis menyesuaikan ketersediaan fitur `PRODUCT_VARIANT` dan `MULTI_OUTLET`. Sheet 2 (*Petunjuk & Kamus Data*) berisi panduan tipe data, nilai yang valid, dan batasan operasional.
- **Protokol Memori $O(1)$ RAM:**
  - **Ekspor (`ExportProductJob`):** Seleksi kolom eksplisit (*anti-overfetching*) dan chunking 200 baris.
  - **Impor (`ImportProductJob`):** Menggunakan stream reader (`fgetcsv` / OpenSpout stream) dan preloading in-memory $O(1)$ untuk seluruh kategori, UOM, produk yang ada, SKU, dan barcode sebelum melakukan perulangan baris (Zero N+1).
- **Penolakan Fitur Non-Aktif secara Edukatif:** Jika file impor berisi varian produk padahal paket langganan tenant tidak memiliki fitur `PRODUCT_VARIANT`, baris tersebut ditolak secara elegan dan dicatat dalam laporan berkas `failed_import_*.xlsx` dengan pesan edukatif, tanpa menggagalkan baris produk valid lainnya.

