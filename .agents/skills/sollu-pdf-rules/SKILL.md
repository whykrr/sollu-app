---
name: sollu-pdf-rules
description: Backend and frontend rules for generating PDFs in the Sollu app project. Use this whenever working on features involving PDF exports, invoices, purchase orders, or reports.
---

# Aturan Pembuatan PDF di Sollu App

Setiap kali Anda diminta membuat fitur ekspor dokumen ke format PDF (misal: Laporan, Invoice, PO, dsb), Anda **diwajibkan** untuk menerapkan struktur dan *layout* generik yang telah disepakati.

## 1. Penggunaan Generic Header Template
Aplikasi ini sudah memiliki template partial header khusus untuk PDF di `resources/views/pdf/partials/header.blade.php`. Jangan pernah membuat logika pemuatan *base64* gambar logo secara manual pada setiap *file* PDF.

Setiap *file* PDF (blade view) **harus** menyertakan template tersebut di awal elemen `<body>`:

```blade
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet' => $outlet ?? null,
        'title' => 'JUDUL DOKUMEN',
        'subtitle' => 'Subjudul Opsional'
    ])
    
    <!-- Konten spesifik dokumen ada di sini -->
</body>
```

### Parameter Header
- `$business`: *(Wajib jika logo perusahaan/header utama ingin dirender normal)* Menyediakan nama bisnis dan `logo`. Jika `null`, *fallback* ke teks 'Sollu App'.
- `$outlet`: *(Opsional)* Object *outlet* yang akan di-*render* namanya, alamat, dan nomor teleponnya tepat di bawah identitas bisnis utama.
- `$title`: Judul dokumen di sebelah kanan atas. Disarankan ditulis KAPITAL.
- `$subtitle`: *(Opsional)* Keterangan tambahan di bawah judul, seperti `#PO-123` atau `Periode: 1 Jan 2026 - 31 Jan 2026`.

## 2. Format *Controller* (barryvdh/laravel-dompdf)
Pastikan metode untuk mengekspor (contoh: `exportPdf`) menggunakan *facade* `Pdf`.

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(Request $request)
{
    // ... Fetch Data ...

    $pdf = Pdf::loadView('pdf.nama-file', [
        'data'     => $data,
        'business' => Auth::user()->business,
    ])->setPaper('a4', 'landscape'); // Gunakan landscape untuk laporan bertabel banyak (lebih dari 5 kolom)

    return $pdf->download('Nama_File_' . now()->format('YmdHis') . '.pdf');
}
```

## 3. Gaya Penulisan (CSS) di Template PDF
Library `dompdf` tidak mendukung penuh semua fitur CSS 3. Gunakan styling dasar:
- Hindari flexbox yang kompleks jika memungkinkan, gunakan `table` untuk pembagian tata letak (*layout*) kolom ganda.
- Gunakan `<style>` di blok `<head>` atau gaya *inline*.
- Format penulisan angka / *currency* / tanggal sebaiknya sudah dimanipulasi pada Blade/Controller.
