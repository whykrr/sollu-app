---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Metrik & Widget `@/Components/Widgets/`

Saat menampilkan ringkasan performa bisnis, KPI, atau metrik analitik, gunakan komponen dari `@/Components/Widgets/`:

---

## 1. Lokasi Penempatan Widget
- Seluruh kartu ringkasan atau widget analitik **WAJIB diletakkan di dalam slot `<template #widgets>` atau `<template #header>` pada `<MainPage>`**.
- DILARANG menaruh widget di default slot agar widget tidak ter-scroll dan tetap sticky di atas tabel.

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
<WidgetProgress
    :icon="faBox"
    title="Kapasitas Gudang"
    :value="75"
    :maxValue="100"
>
    75% Terisi
</WidgetProgress>
```
