<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Produk</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 11px;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }

        .table tr {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-left {
            text-align: left !important;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-muted {
            color: #777;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f4f4f4;
            border-top: 2px solid #bbb;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet'   => $outlet ?? null,
        'title'    => 'LAPORAN PRODUK',
        'subtitle' => 'Periode: ' . ($start_date ?? '-') . ' - ' . ($end_date ?? '-')
    ])

    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-right">Qty Terjual</th>
                <th class="text-right">Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalSales = 0;
                $products = $data ?? [];
            @endphp
            @forelse($products as $item)
                @php
                    $itemObj = (object) $item;
                    $totalQty += $itemObj->total_qty ?? 0;
                    $totalSales += $itemObj->total_sales ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-bold">{{ $itemObj->product_name ?? '-' }}</td>
                    <td>{{ $itemObj->category_name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($itemObj->total_qty ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($itemObj->total_sales ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Tidak ada data penjualan produk pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($products) > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-center font-bold">TOTAL</td>
                    <td class="text-right font-bold">{{ number_format($totalQty, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalSales, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
