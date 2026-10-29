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
