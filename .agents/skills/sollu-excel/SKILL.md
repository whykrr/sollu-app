---
name: sollu-excel
description: >-
  Standards for asynchronous Excel export (AbstractExcelExportJob) and import (AbstractExcelImportJob) features in Sollu App.
  MUST trigger whenever implementing Excel export jobs, Excel import processing, Excel template downloads, Excel UTF-8 BOM encoding,
  delimiter auto-detection, or failed import handling.
---

# Aturan Ekspor & Impor Excel di Sollu App

Setiap pembuatan fitur **Ekspor Excel** atau **Impor Excel** wajib menggunakan arsitektur *asynchronous (background job)* dan standar format bawaan proyek.

## 1. Ekspor Excel Async (`AbstractExcelExportJob`)

### Aturan Utama:
1. **Dilarang** melakukan *streaming* Excel langsung dari controller untuk data besar.
2. Buat class Job di `app/Jobs/[Module]/Export[Entity]Job.php` turunan dari `App\Jobs\ImportExport\AbstractExcelExportJob`.
3. Implementasikan method wajib:
   - `getQuery()`: Mengembalikan *query builder* Eloquent (dengan filter aktif).
   - `getHeaders()`: Array nama *header* kolom Excel (bahasa Indonesia resmi, contoh: `['Nama', 'SKU', 'Satuan', 'Stok']`).
   - `mapRow($row)`: Format tiap baris. Teks di-map biasa, numerik di-cast `(float)`, boolean di-map `'Ya'` / `'Tidak'`.
   - `getModuleName()`: Nama modul untuk notifikasi header (misal: `'Inventori Stok'`).
   - `getFileName()`: Nama file berformat `'[entity]_export_' . time() . '.xlsx'`.

### Notifikasi & UTF-8 BOM:
- `AbstractExcelExportJob` otomatis menyertakan **UTF-8 BOM Header** (`\xEF\xBB\xBF`) agar Excel dapat dibuka secara rapi di Microsoft Excel.
- Data diproses secara efisien menggunakan chunking (500 baris). File tersimpan di `storage/app/public/exports/` dan notifikasi `ExcelExportCompleted` dikirimkan ke pengguna beserta link download yang berlaku selama 1 hari.

---

## 2. Impor Excel Async (`AbstractExcelImportJob`)

### Aturan Utama:
1. Buat class Job di `app/Jobs/[Module]/Import[Entity]Job.php` turunan dari `App\Jobs\ImportExport\AbstractExcelImportJob`.
2. Implementasikan method wajib:
   - `getModuleName()`: Nama modul untuk notifikasi.
   - `processRow(array $row)`: Memproses 1 baris data. **Lempar `Exception`** jika validasi atau pengolahan data gagal agar baris tersebut dicatat ke log error.

### Fitur Otomatis `AbstractExcelImportJob`:
- **Auto-Detection Delimiter:** Otomatis membedakan koma (`,`) atau titik koma (`;`).
- **BOM Skip:** Otomatis melewati BOM header saat membaca baris data.
- **Handling Baris Gagal:** Baris yang mengalami kegagalan otomatis dikumpulkan dan ditulis ke file `storage/app/public/exports/failed_import_[timestamp].xlsx` beserta kolom `Error Message`. Notifikasi `ExcelImportCompleted` akan dikirimkan menyertakan link unduhan baris gagal tersebut.

### Controller Action Impor:
```php
public function import(Request $request)
{
    $request->validate(['file' => 'required|mimes:xlsx,txt|max:10240']);
    $path = $request->file('file')->store('imports', 'local');

    ImportEntityJob::dispatch(Auth::user(), $path);

    return redirect()->back()->with('success', 'Proses impor Excel sedang berjalan di latar belakang.');
}
```

---

## 3. Unduhan Template Excel (Streamed)

Khusus untuk unduhan **Template Excel** (contoh: acuan format impor), diperbolehkan *stream* langsung dari Controller dengan menyertakan BOM:

```php
public function importTemplate()
{
    $headers = ['Nama', 'SKU', 'Barcode', 'Satuan', 'Minimum Stok'];
    $dummyData = ['Gula Pasir', 'GL-001', '8991234567890', 'Kilogram', '10'];

    return response()->stream(function () use ($headers, $dummyData) {
        $file = fopen('php://output', 'w');
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputxlsx($file, $headers);
        fputxlsx($file, $dummyData);
        fclose($file);
    }, 200, [
        'Content-Type'        => 'text/xlsx',
        'Content-Disposition' => 'attachment; filename="template_[entity].xlsx"',
    ]);
}
```
