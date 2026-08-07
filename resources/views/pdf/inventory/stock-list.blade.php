<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Inventori</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .table-data { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-data th, .table-data td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        .table-data th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet' => $outlet ?? null,
        'title' => 'LAPORAN STOK INVENTORI',
        'subtitle' => 'Tanggal: ' . now()->format('d M Y H:i')
    ])

    <table class="table-data">
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th>Outlet</th>
                <th>Nama Barang</th>
                <th>SKU</th>
                <th>Kategori</th>
                <th class="text-right">Min. Stok</th>
                <th class="text-right">Stok Saat Ini</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stocks as $index => $stock)
                @php
                    $isHabis = $stock->current_stock <= 0;
                    $isMenipis = !$isHabis && $stock->current_stock <= $stock->minimum_stock;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $stock->outlet_name ?? '-' }}</td>
                    <td>{{ $stock->item_name }}</td>
                    <td>{{ $stock->sku ?: '-' }}</td>
                    <td>{{ $stock->category_name ?: '-' }}</td>
                    <td class="text-right">{{ number_format($stock->minimum_stock, 2) }} {{ $stock->uom }}</td>
                    <td class="text-right">{{ number_format($stock->current_stock, 2) }} {{ $stock->uom }}</td>
                    <td class="text-center">
                        @if($isHabis)
                            <span class="badge badge-danger">Habis</span>
                        @elseif($isMenipis)
                            <span class="badge badge-warning">Menipis</span>
                        @else
                            <span class="badge badge-success">Aman</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            @if(count($stocks) == 0)
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data stok.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
