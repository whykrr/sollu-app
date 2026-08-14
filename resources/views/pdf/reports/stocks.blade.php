<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok & Aset</title>
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

        .text-danger {
            color: #c53030;
        }

        .text-success {
            color: #276749;
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
        'title'    => 'LAPORAN STOK & ASET',
        'subtitle' => 'Periode: ' . ($start_date ?? '-') . ' - ' . ($end_date ?? '-')
    ])

    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Nama Item</th>
                <th class="text-right">Stok Awal</th>
                <th class="text-right">Masuk</th>
                <th class="text-right">Keluar</th>
                <th class="text-right">Stok Akhir</th>
            </tr>
        </thead>
        <tbody>
            @php
                $stocks = $data ?? [];
            @endphp
            @forelse($stocks as $item)
                @php
                    $itemObj = (object) $item;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-bold">{{ $itemObj->item_name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($itemObj->starting_stock ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right text-success">+{{ number_format($itemObj->stock_in ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right text-danger">-{{ number_format($itemObj->stock_out ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($itemObj->closing_stock ?? 0, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Tidak ada pergerakan stok pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
