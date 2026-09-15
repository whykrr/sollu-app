# Sollu App API & Integration Standards

Standar pengembangan RESTful API, integrasi data asinkron, format respon JSON, dan pemeliharaan kontrak dokumentasi API pada **Sollu App**.

---

## 1. API Architecture & Subdomain

1. **Subdomain:** `api.sollu.test` (atau subdomain `api.*` di lingkungan produksi).
2. **Routing:** Dikelola di `routes/api.php` dan `routes/api/*.php`.
3. **Autentikasi:** Laravel Sanctum Token (`auth:sanctum`) untuk perangkat POS dan integrasi eksternal.
4. **Middleware:** Group `api` dengan pembatasan *Rate Limiting* (`throttle:api`).

---

## 2. JSON Response Standards

### 2.1. Aturan Format & Key Naming
- Seluruh key atribut JSON **WAJIB** berformat `snake_case`.
- Menggunakan standar HTTP Status Code resmi (200 OK, 201 Created, 400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 422 Unprocessable Entity, 500 Server Error).
- **DILARANG** menggunakan custom wrapper envelope seperti `{"success": true, "status": 200, ...}`.

### 2.2. Eloquent API Resources (`JsonResource`)
Seluruh output data model wajib melalui `JsonResource`:

```php
namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'sku'           => $this->sku,
            'name'          => $this->name,
            'uom'           => [
                'id'     => $this->uom?->id,
                'name'   => $this->uom?->name,
                'symbol' => $this->uom?->symbol,
            ],
            // 🚨 WAJIB: Casting tipe numerik/desimal murni
            'current_stock' => (float) $this->current_stock,
            'minimum_stock' => (float) $this->min_stock,
            'avg_cost'      => (float) $this->avg_cost,
            'is_active'     => (bool) $this->is_active,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
```

> [!IMPORTANT]
> **Numeric & Decimal Casting:** Seluruh nilai numerik (harga, total transaksi, stok, persentase diskon, berat) **WAJIB** di-cast ke tipe angka `(float)` atau `(double)`. Dilarang mengembalikan string numerik seperti `"15000.00"`.

---

## 3. Controller Response Messages & Constants

Untuk konsistensi pesan respon dan menghindari *magic string*, seluruh pesan di Controller dan Form Request **WAJIB** merujuk ke kelas Constant:

| Kelas Constant | Namespace | Contoh Nilai |
| :--- | :--- | :--- |
| `ResourceMessage` | `App\Constants\ResourceMessage` | `CREATE_SUCCESS`, `UPDATE_SUCCESS`, `DELETE_SUCCESS`, `RESTORE_SUCCESS`, `PURGE_SUCCESS` |
| `AuthorizationMessage` | `App\Constants\AuthorizationMessage` | `CANT_ACCESS_PAGE`, `CANT_ACCESS_DATA` |
| `ErrorMessage` | `App\Constants\ErrorMessage` | `DATABASE_ERROR`, `DATA_NOT_FOUND`, `PAGE_NOT_FOUND`, `TOO_MANY_REQUESTS`, `SERVER_ERROR` |
| `FlashDataVariable` | `App\Constants\FlashDataVariable` | `SUCCESS->value`, `WARNING->value`, `FAILED->value` |

```php
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;

public function store(StoreItemRequest $request)
{
    $this->itemService->create($request->validated());

    return redirect()->back()->with(
        FlashDataVariable::SUCCESS->value,
        ResourceMessage::CREATE_SUCCESS
    );
}
```

---

## 4. On-Demand Data Loading Endpoints

Untuk mendukung standar performa tinggi (maksimal 5 detik response time):

1. **Detail Entitas (`show`):**
   - Halaman `index()` tidak me-load data relasi mendalam.
   - Endpoint `show($id)` menyediakan data detail lengkap berformat JSON untuk dimuat secara async saat drawer `<PopUpPage>` dibuka.
2. **Master Lookup Options (`formOptions`):**
   - Opsi dropdown formulir yang besar (daftar produk, kategori, pelanggan) disediakan melalui endpoint tersendiri (misal: `/api/v1/inventory/items/search` atau route form options) untuk komponen `AsyncSelectField`.

---

## 5. Asynchronous File Processing (Excel & PDF)

### 5.1. Ekspor Excel Asinkron (`AbstractExcelExportJob`)
- Dilarang mengekspor dataset besar secara sinkron/langsung di controller.
- Turunan dari `App\Jobs\ImportExport\AbstractExcelExportJob`.
- Fitur bawaan:
  - Header UTF-8 BOM (`\xEF\xBB\xBF`) agar karakter non-ASCII dan format terbaca sempurna di Excel.
  - Chunking 500 baris per query untuk efisiensi memori.
  - Tersimpan di `storage/app/public/exports/` dan mengirim notifikasi saat selesai (`ExcelExportCompleted`).

```php
namespace App\Jobs\Inventory;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Inventory\InventoryItem;

class ExportInventoryItemJob extends AbstractExcelExportJob
{
    public function getQuery()
    {
        return InventoryItem::query()->where('business_id', $this->user->business_id);
    }

    public function getHeaders(): array
    {
        return ['SKU', 'Nama Barang', 'Satuan', 'Stok Saat Ini', 'Status'];
    }

    public function mapRow($row): array
    {
        return [
            $row->sku,
            $row->name,
            $row->uom?->name ?? '-',
            (float) $row->current_stock,
            $row->is_active ? 'Aktif' : 'Non-Aktif',
        ];
    }

    public function getModuleName(): string
    {
        return 'Inventori Barang';
    }

    public function getFileName(): string
    {
        return 'inventory_items_export_' . time() . '.xlsx';
    }
}
```

### 5.2. Impor Excel Asinkron (`AbstractExcelImportJob`)
- Turunan dari `App\Jobs\ImportExport\AbstractExcelImportJob`.
- Menyediakan auto-detection delimiter (koma `,` atau titik-koma `;`), penanganan UTF-8 BOM, dan isolasi baris error ke file `failed_import_[timestamp].xlsx`.

### 5.3. PDF Document Generation (`laravel-dompdf`)
- Setiap template PDF (`resources/views/pdf/*.blade.php`) wajib menyertakan generic header:
  ```blade
  @include('pdf.partials.header', [
      'business' => $business ?? null,
      'outlet'   => $outlet ?? null,
      'title'    => 'LAPORAN MUTASI STOK',
      'subtitle' => 'Periode: 01 Jan 2026 - 31 Jan 2026'
  ])
  ```
- Wajib menggunakan `page-break-inside: avoid;` pada elemen tabel/kartu agar tidak terpotong saat transisi halaman PDF.

---

## 6. API Documentation Maintenance (OpenAPI & Postman)

Dokumentasi API adalah kontrak mutlak antara Backend dan Client/POS. Setiap perubahan pada controller, form request, atau JsonResource **WAJIB** memperbarui file berikut di folder `docs/`:

1. **OpenAPI / Swagger:** `docs/openapi.yaml`
   - Perbarui definisi skema, path endpoint, header, request body, query parameters, dan validation rules.
   - Cantumkan contoh response (*example responses*) lengkap dengan HTTP status.
2. **Postman Collection:** `docs/postman_collection.json`
   - Sinkronkan folder modul, header `Authorization: Bearer {{token}}`, variabel environment, dan dummy request body.
