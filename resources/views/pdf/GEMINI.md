---
trigger: always_on
---

# Wajib Perhatikan: Standar Template PDF DomPDF

Saat bekerja di `resources/views/pdf`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Generic Header Partial:** Wajib menyertakan `@include('pdf.partials.header', ['business' => $business, 'outlet' => $outlet, 'title' => 'JUDUL'])` di awal elemen `<body>`. Dilarang memuat base64 logo secara manual.
2. **Layout Kolom:** DomPDF tidak mendukung Flexbox/Grid modern. Gunakan HTML `<table>` dengan `border-collapse: collapse;` untuk tata letak kolom.
3. **Pencegahan Baris Terpotong:** Gunakan `page-break-inside: avoid;` pada baris tabel (`<tr>`) atau container agar tidak terpotong di jeda halaman.
