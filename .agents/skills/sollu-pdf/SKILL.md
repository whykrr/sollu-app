---
name: sollu-pdf
description: >-
  Backend and Blade template guidelines for generating PDF documents (invoices, reports, purchase orders) using laravel-dompdf
  (Barryvdh\DomPDF\Facade\Pdf) in Sollu App. MUST trigger whenever creating or editing PDF export controllers, Blade PDF views,
  generic header partials (pdf.partials.header), logo base64 conversion, or DomPDF styling.
---

# Aturan Pembuatan PDF di Sollu App

Setiap kali Anda diminta membuat fitur ekspor dokumen ke format PDF (misal: Laporan, Invoice, PO, dsb), Anda **diwajibkan** untuk menerapkan struktur dan *layout* generik yang telah disepakati.

## 1. Penggunaan Generic Header Template

Aplikasi ini memiliki template partial header khusus PDF di `resources/views/pdf/partials/header.blade.php`. Jangan pernah membuat logika pemuatan *base64* gambar logo secara manual pada setiap file PDF.

Setiap Blade View PDF **harus** menyertakan template tersebut di awal elemen `<body>`:

```blade
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet'   => $outlet ?? null,
        'title'    => 'JUDUL DOKUMEN',
        'subtitle' => 'Subjudul Opsional'
    ])
    
    <!-- Konten spesifik dokumen -->
</body>
```

### Parameter Header:
- `$business`: *(Wajib jika logo perusahaan/header utama ingin dirender normal)* Menyediakan nama bisnis dan `logo`. Jika `null`, *fallback* otomatis ke logo / nama 'Sollu App'.
- `$outlet`: *(Opsional)* Object *outlet* yang akan di-*render* namanya, alamat, dan nomor teleponnya tepat di bawah identitas bisnis utama.
- `$title`: Judul dokumen di sebelah kanan atas (ditulis KAPITAL, e.g. `'LAPORAN PENJUALAN'`).
- `$subtitle`: *(Opsional)* Keterangan tambahan di bawah judul (e.g. `#PO-123` atau `Periode: 1 Jan 2026 - 31 Jan 2026`).

---

## 2. Controller Format (`Barryvdh\DomPDF\Facade\Pdf`)

Gunakan Facade `Barryvdh\DomPDF\Facade\Pdf` pada method controller ekspor PDF:

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(Request $request)
{
    // Fetch data...

    $pdf = Pdf::loadView('pdf.nama-file', [
        'data'     => $data,
        'business' => Auth::user()->business,
        'outlet'   => Auth::user()->activeOutlet,
    ])->setPaper('a4', 'portrait'); // Gunakan 'landscape' jika tabel memiliki lebih dari 5 kolom

    return $pdf->download('Nama_File_' . now()->format('YmdHis') . '.pdf');
}
```

---

## 3. Best Practices Styling CSS DomPDF

Library `dompdf` tidak mendukung penuh CSS 3 Flexbox/Grid modern:
- **Layout Kolom Ganda:** Gunakan HTML `<table>` dengan `border-collapse: collapse;` untuk tata letak kolom.
- **Styling:** Gunakan tag `<style>` di blok `<head>` atau gaya *inline*.
- **Pencegahan Baris Terpotong:** Gunakan `page-break-inside: avoid;` pada baris tabel (`<tr>`) atau kartu agar tidak terpotong di batas halaman.
- **Format Angka & Mata Uang:** Format tanggal dan mata uang (Rupiah) sebaiknya sudah diolah di Controller / Helper sebelum dikirim ke Blade View PDF.
