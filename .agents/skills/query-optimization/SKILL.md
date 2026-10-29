---
name: query-optimization
description: >-
    Comprehensive engineering standards and actionable patterns for database query optimization,
    eliminating N+1 (including hidden serialization N+1 from $appends), anti-overfetching via explicit column selection,
    lean Eloquent API Resources without parent::toArray, in-memory preloading for batch mutations and Excel imports,
    and observer lookup memoization across all modules in Sollu App.
    Activate whenever writing Eloquent queries, designing API Resources, optimizing controller response times,
    debugging slow queries/Telescope, or processing batch data.
---

# Skill: Optimasi Kueri & Rekayasa Performa Database (Query Optimization)

Panduan teknis dan standar baku rekayasa performa kueri database pada **Sollu App** (Laravel 12, PostgreSQL, Vue 3 Inertia, dan SQLite POS Sync).

---

## 1. Eliminasi Hidden N+1 pada Model Serialization (`$appends`)

### 1.1. Akar Masalah
Ketika accessor yang membaca relasi Eloquent didaftarkan ke array `$appends` model:
```php
// ANTI-PATTERN: Memicu puluhan query tersembunyi saat serialisasi
protected $appends = ['unit_name'];

protected function unitName(): Attribute
{
    return Attribute::make(
        get: fn () => $this->item?->uom?->name ?? 'Pcs',
    );
}
```
Setiap kali model diserialisasi (`Product::paginate()`, `Inertia::render()`, `$model->toArray()`, `response()->json()`), Laravel mengevaluasi accessor tersebut. Jika relasi `item.uom` tidak di-eager-load, Laravel menjalankan lazy loading kueri database untuk setiap baris model ($N+1$).

### 1.2. Standar Baku
1. **Properti `$appends` HANYA untuk Komputasi Murni / URL Storage:**
   - Boleh: URL publik file storage (`cover_image_url`), format string tanggal lokal, kalkulasi matematis kolom yang sudah ada di tabel model yang sama.
   - DILARANG KERAS: Membaca properti relasi Eloquent (`$this->relation->field`).
2. **Accessor Tetap Tersedia untuk Pemanggilan Manual:**
   - Method accessor tetap dapat dibuat pada model, tetapi **TIDAK** didaftarkan ke `$appends`. Pemanggilan dilakukan secara on-demand saat relasi terkait sudah dipastikan termuat.
3. **Format Atribut Turunan Relasi di Level API Resource / DTO:**
   - Jika frontend atau consumer API membutuhkan atribut gabungan, transformasikan di level API Resource setelah relasi di-eager load dengan kolom spesifik:
     ```php
     'unit_name' => $this->relationLoaded('item') && $this->item?->relationLoaded('uom')
         ? ($this->item->uom->name ?? 'Pcs')
         : 'Pcs',
     ```

---

## 2. Anti-Overfetching: Explicit Column Selection & Pruning Relasi

### 2.1. Standar Seleksi Kolom di Controller (`index()`)
Dilarang menjalankan query `select *` pada tabel katalog atau listing. Selalu batasi kolom sesuai konsumsi template Vue/komponen frontend:
```php
// BENAR: Kolom terbatas sesuai kebutuhan visual tabel/grid
$items = Item::currentBusiness()
    ->select([
        'id',
        'business_id',
        'category_id',
        'name',
        'code',
        'image_url',
        'is_active',
        'created_at',
    ])
    ->with([
        'category:id,name',
        'prices' => fn ($q) => $q->select('id', 'item_id', 'outlet_id', 'amount'),
    ])
    ->paginate(15);
```

### 2.2. Pruning Relasi yang Tidak Dikonsumsi
- Jika tampilan katalog hanya menampilkan foto thumbnail dari kolom `image_url`, **jangan pernah** meng-eager load relasi `images` (hasMany).
- Periksa komponen Vue (`Table.vue`, `Card.vue`): jika komponen tidak membaca `row.images`, hilangkan `with('images')`.

### 2.3. Efisiensi Endpoint `formOptions()`
Endpoint penyedia opsi form (dropdown/modal) wajib sangat ramping:
- **Hapus Data yang Tidak Digunakan:** Jangan me-load model berat yang tidak dikonsumsi form drawer.
- **Select Kolom Minimal:** Gunakan `select('id', 'name')` untuk master data dropdown.
- **Batasi Nested Relations:** Jika dropdown memerlukan data terkait (misal UOM atau harga rata-rata), batasi kolom bersarangnya:
  ```php
  'rawMaterials' => ProductItem::currentBusiness()
      ->active()
      ->select(['id', 'name', 'uom_id'])
      ->with([
          'uom:id,name',
          'inventoryItem:id,product_item_id' => fn ($q) => $q->with('balances:id,inventory_item_id,average_cost'),
      ])
      ->get();
  ```

---

## 3. Lean Eloquent API Resources (Larangan `parent::toArray()`)

### 3.1. Bahaya `parent::toArray()`
Memanggil `parent::toArray($request)` di dalam `JsonResource` menyalin seluruh atribut model mentah beserta data internal Eloquent ke payload JSON. Hal ini menyebabkan:
- Ukuran payload response membengkak (data transfer boros).
- Potensi kebocoran kolom sensitif atau internal sistem.
- Tidak adanya jaminan tipe data primitif yang aman untuk frontend/mobile client.

### 3.2. Pola Penulisan Resource Baku
Selalu definisikan array bentuk DTO terstruktur dengan tipe data pasti:
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'code' => $this->code,
        'price' => (float) $this->price,
        'is_active' => (bool) $this->is_active,
        'category' => $this->relationLoaded('category') && $this->category ? [
            'id' => $this->category->id,
            'name' => $this->category->name,
        ] : null,
        'items' => $this->relationLoaded('items')
            ? ItemResource::collection($this->items)
            : [],
    ];
}
```

---

## 4. In-Memory Preloading & Bulk Mutation pada Operasi Batch & Impor

### 4.1. Anti-Pattern: Kueri di Dalam Loop
```php
// ANTI-PATTERN: Memicu ratusan kueri SELECT berulang
foreach ($rows as $row) {
    $category = Category::firstOrCreate(['name' => $row['category']]); // O(N) DB queries
    $uom = Uom::where('name', $row['uom'])->first();                   // O(N) DB queries
    $item = Item::where('code', $row['code'])->first();                 // O(N) DB queries
}
```

### 4.2. Pola Baku: Preloading & Indexed Map Lookup ($O(1)$)
Sebelum memulai loop iterasi baris (misalnya pada Excel Import Job atau pembuatan matriks varian):
1. **Preload Master Lookups ke Memory Array:**
   ```php
   $categoriesCache = Category::where('business_id', $this->businessId)->pluck('id', 'name')->all();
   $uomsCache = Uom::pluck('id', 'name')->all();
   $existingItemsByCode = Item::where('business_id', $this->businessId)->get()->keyBy('code');
   ```
2. **Lookup Cepat di PHP:**
   ```php
   foreach ($rows as $row) {
       $catName = $row['category'];
       if (!isset($categoriesCache[$catName])) {
           $cat = Category::create(['business_id' => $this->businessId, 'name' => $catName]);
           $categoriesCache[$catName] = $cat->id;
       }
       $categoryId = $categoriesCache[$catName];

       $item = $existingItemsByCode->get($row['code']);
       // Proses mutasi...
   }
   ```
3. **Bulk Update untuk Penonaktifan / Deletion:**
   Hindari `$items->each(fn ($i) => $i->update(['is_active' => false]))`.
   Gunakan bulk update set-based:
   ```php
   if (!empty($toDeactivateIds)) {
       Item::whereIn('id', $toDeactivateIds)->update(['is_active' => false]);
   }
   ```

---

## 5. Observer Signal Throttling & Lookup Memoization

Ketika sebuah model memiliki Observer yang mengirim sinyal WebSocket/Reverb atau mengantrekan background sync per-baris (misal `ProductPriceObserver`, `StockObserver`):
- Menyimpan 20-50 baris item sekaligus dalam satu request akan mengeksekusi Observer sebanyak 20-50 kali.
- Jika Observer mencari relasi induk (misal query `outlet_product` atau `Outlet::find()`), akan terjadi $N$ kueri duplikat identik.
- **Solusi Baku:** Gunakan `protected static array $cache = [];` di dalam Observer untuk me-memoize hasil query per request lifecycle.

---

## 6. Protokol Verifikasi Performa Kueri (Checklist)

Saat membuat atau mengoptimasi endpoint dan background jobs:
- [ ] **Zero Model Appends Relation:** Pastikan tidak ada model dalam query yang memiliki relasi di properti `$appends`.
- [ ] **Explicit Select Columns:** Kueri katalog `index()` dan `formOptions()` menyertakan `select(...)` kolom yang dibutuhkan.
- [ ] **Eager Loading Bounded:** Seluruh relasi yang dibaca di template/resource dimuat via `with(['relation:id,name'])`.
- [ ] **Bounded Query Count Test:** Untuk listing 15 item vs 50 item, jumlah kueri SQL yang dieksekusi harus bernilai konstan ($O(1)$).
- [ ] **Lean Resource:** Resource tidak menggunakan `parent::toArray($request)`.
- [ ] **Batch Loop Preloaded:** Tidak ada pemanggilan kueri `find()`, `where()`, atau `firstOrCreate()` statis di dalam loop iterasi data impor/matriks.
