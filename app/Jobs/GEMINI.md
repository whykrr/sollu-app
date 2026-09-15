---
trigger: always_on
---

# Wajib Perhatikan: Standar Import & Export Job

Saat bekerja di `app/Jobs`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Export Job:** Wajib menginduk ke `App\Jobs\ImportExport\AbstractExcelExportJob`. Menyertakan BOM header `\xEF\xBB\xBF` secara otomatis dan menggunakan chunking (500 baris).
2. **Import Job:** Wajib menginduk ke `App\Jobs\ImportExport\AbstractExcelImportJob`. Otomatis menangani auto-delimiter, BOM skip, dan penulisan baris gagal impor ke file export terpisah.
3. **Async Processing:** Dilarang melakukan streaming data ekspor besar secara synchronous di Controller.
