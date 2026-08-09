---
name: sollu-csv-rules
description: Standards for creating async CSV export and import features in Sollu App. Trigger whenever working on CSV export, CSV import, CSV templates, or AbstractCsvExportJob / AbstractCsvImportJob.
---

# Aturan Ekspor & Impor CSV di Sollu App

Setiap pembuatan fitur **Ekspor CSV** atau **Impor CSV** wajib menggunakan arsitektur *asynchronous (background job)* dan standar format bawaan proyek.

## 1. Ekspor CSV Async (`AbstractCsvExportJob`)

### Aturan Utama:
1. **Dilarang** melakukan *streaming* CSV langsung dari controller untuk data besar.
2. Buat class Job di `app/Jobs/[Module]/Export[Entity]Job.php` turunan dari `App\Jobs\ImportExport\AbstractCsvExportJob`.
3. Implementasikan method wajib:
   - `getQuery()`: Mengembalikan *query builder* Eloquent (dengan filter aktif).
   - `getHeaders()`: Array nama *header* kolom CSV (bahasa Indonesia resmi, contoh: `Nama`, `SKU`, `Barcode`, `Satuan`, `Minimum Stok`, `Stok`, `Status`).
   - `mapRow($row)`: Format tiap baris. Teks di-map biasa, numerik di-cast `(float)`, boolean di-map `'Ya'` / `'Tidak'`.
   - `getModuleName()`: Nama modul untuk notifikasi header.
   - `getFileName()`: Nama file berformat `[entity]_export_' . time() . '.csv'`.

### Contoh Struktur Job:
```php
namespace App\Jobs\Inventory;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\User;

class ExportEntityJob extends AbstractCsvExportJob
{
    protected $businessId;
    protected $filters;

    public function __construct(User $user, $businessId, array $filters = [])
    {
        parent::__construct($user);
        $this->businessId = $businessId;
        $this->filters = $filters;
    }

    protected function getQuery() {
        return Model::query()->where('business_id', $this->businessId)->filters($this->filters);
    }

    protected function getHeaders(): array {
        return ['Nama', 'SKU', 'Satuan', 'Minimum Stok', 'Stok', 'Status'];
    }

    protected function mapRow($row): array {
        return [
            $row->name,
            $row->sku,
            $row->uom_name,
            (float) $row->minimum_stock,
            (float) $row->current_stock,
            $row->status_label,
        ];
    }

    protected function getModuleName(): string { return 'Modul Name'; }
    protected function getFileName(): string { return 'entity_export_' . time() . '.csv'; }
}
```

### Controller Action:
```php
public function export(Request $request)
{
    ExportEntityJob::dispatch(
        Auth::user(),
        Auth::user()->business_id,
        $request->all()
    );

    return redirect()->back()->with('success', 'Ekspor CSV sedang diproses di latar belakang.');
}
```

## 2. Format File & Encoding
- `AbstractCsvExportJob` otomatis menyertakan **UTF-8 BOM Header** (`\xEF\xBB\xBF`) agar CSV dapat dibuka langsung secara rapi di Microsoft Excel.
- File tersimpan di `storage/app/public/exports/` dan `CsvExportCompleted` notification dikirimkan ke pengguna beserta *download URL*.

## 3. Template Unduhan (Streamed)
Khusus untuk unduhan **Template CSV** (misal untuk fitur *import*), diperbolehkan *stream* langsung dari Controller dengan menyertakan BOM:
```php
public function importTemplate()
{
    $headers = ['Nama', 'SKU', 'Barcode', 'Satuan', 'Minimum Stok'];
    $dummyData = ['Gula Pasir', 'GL-001', '8991234567890', 'Kilogram', '10'];

    return response()->stream(function () use ($headers, $dummyData) {
        $file = fopen('php://output', 'w');
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($file, $headers);
        fputcsv($file, $dummyData);
        fclose($file);
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="template.csv"',
    ]);
}
```
