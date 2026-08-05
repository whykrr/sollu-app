<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Berita Acara Penyesuaian Stok - {{ $adjustment->adjustment_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.4;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            vertical-align: top;
            width: 50%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }

        .items-table th {
            background-color: #f8f9fa;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .signature-table {
            width: 100%;
            margin-top: 50px;
            text-align: center;
        }

        .signature-table td {
            width: 50%;
            padding: 10px;
        }

        .signature-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #333;
            margin-top: 60px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    @include('pdf.partials.header')

    <table class="info-table">
        <tr>
            <td>
                <strong>Nomor Referensi:</strong> {{ $adjustment->adjustment_number }}<br>
                <strong>Tanggal Dibuat:</strong> {{ $adjustment->created_at->format('d M Y H:i') }}<br>
                <strong>Status:</strong> <span style="text-transform: capitalize;">{{ $adjustment->status?->value ?? $adjustment->status }}</span>
            </td>
            <td>
                <strong>Outlet:</strong> {{ $outlet ? $outlet->name : '-' }}<br>
                <strong>Alasan:</strong> <span
                    style="text-transform: capitalize;">{{ str_replace('_', ' ', $adjustment->reason?->value ?? $adjustment->reason) }}</span><br>
                @if ($adjustment->notes)
                    <strong>Catatan:</strong> {{ $adjustment->notes }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th>Nama Barang</th>
                <th class="text-center">Tipe</th>
                <th class="text-right">Stok Sebelum</th>
                <th class="text-right">Perubahan</th>
                <th class="text-right">Stok Sesudah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($adjustment->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->inventoryItem->name ?? '-' }}<br>
                        <small style="color: #666;">Satuan: {{ $item->inventoryItem->uom->name ?? '-' }}</small>
                    </td>
                    <td class="text-center" style="text-transform: capitalize;">
                        {{ $item->movement_type }}
                    </td>
                    <td class="text-right">
                        {{ rtrim(rtrim(number_format($item->stock_before, 4, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right">
                        {{ $item->qty_change > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($item->qty_change, 4, ',', '.'), '0'), ',') }}
                    </td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($item->stock_after, 4, ',', '.'), '0'), ',') }}
                    </td>
                    <td>{{ $item->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <div class="signature-line"></div><br>
                {{ $adjustment->creator->name ?? '(................................)' }}
            </td>
            <td>
                @if (in_array($adjustment->status?->value ?? $adjustment->status, ['approved', 'rejected', 'voided']))
                    Disetujui/Diproses Oleh,<br>
                    <div class="signature-line"></div><br>
                    {{ $adjustment->approver->name ?? '(................................)' }}
                @else
                    Mengetahui,<br>
                    <div class="signature-line"></div><br>
                    (................................)
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
