---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Metrik & Widget `@/Components/Widgets/`

Saat menampilkan ringkasan performa bisnis, KPI, atau metrik analitik, gunakan komponen dari `@/Components/Widgets/`:

---

## 1. Lokasi Penempatan Widget

- Seluruh kartu ringkasan atau widget analitik **WAJIB diletakkan di dalam slot `<template #widgets>` pada `<MainPage>`** (terletak di antara header title `#header` dan filter toolbar `#filter`).
- DILARANG menaruh widget di default slot agar widget tidak ter-scroll dan tetap sticky di atas tabel.
- **Flat Minimalist (Tanpa Shadow):** Komponen widget di dalam `MainPage` harus mempertahankan desain flat minimalis tanpa menggunakan drop-shadow (`shadow`, `shadow-sm`, dsb.), mengandalkan border halus dan background solid.

---

## 2. Komponen Widget Resmi

### A. `<Widget>` (KPI Card Standar)

Kartu metrik utama dengan ikon, judul, nilai, dan indikator tren.

```vue
<Widget
    :icon="faDollarSign"
    title="Total Pendapatan"
    traction="up"
    :tractionPercentage="15"
    descriptors="dari bulan lalu"
>
    Rp 45.200.000
</Widget>
```

### B. `<WidgetChart>` (Mini Sparkline Graph)

Kartu metrik yang dilengkapi grafik ringkas berbasis Chart.js.

```vue
<WidgetChart
    id="sales-summary"
    type="line"
    title="Tren Penjualan"
    highlight="Rp 12.500.000"
    subHighlight="8% hari ini"
    :icon="faChartLine"
    :labels="['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']"
    :data="[12, 19, 15, 22, 28, 35, 42]"
/>
```

### C. `<WidgetProgress>` (Target / Kapasitas Bilah Progress)

Kartu metrik dengan persentase dan progress bar visual.

```vue
<WidgetProgress :icon="faBox" title="Kapasitas Gudang" :value="75" :maxValue="100">
    75% Terisi
</WidgetProgress>
```

### D. `<WidgetMini>` (Horizontal Mini KPI Card)

Kartu metrik kompak berbentuk horizontal dengan ikon persegi bulat di sebelah kiri dan teks metrik di sebelah kanan. Cocok untuk grid 4-kolom (`grid grid-cols-2 lg:grid-cols-4 gap-2`).

```vue
<WidgetMini :icon="faBriefcase" title="Total Jenis Bisnis" variant="main">
    12 Tipe
</WidgetMini>

<WidgetMini :icon="faEye" title="Tampil di Registrasi" variant="success" value-class="text-success">
    10 Aktif <span class="text-xs text-neutral-400 font-normal ml-1">(2 hidden)</span>
</WidgetMini>
```
