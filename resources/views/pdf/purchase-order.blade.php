<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $po->po_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header table {
            width: 100%;
        }

        .header img {
            max-width: 150px;
            max-height: 80px;
        }

        .title {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
            color: #555;
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
            margin-bottom: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
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

        .status-badge {
            display: inline;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
            color: #fff;
        }

        .status-draft {
            background-color: #6c757d;
        }

        .status-ordered {
            background-color: #17a2b8;
        }

        .status-received {
            background-color: #28a745;
        }

        .status-cancelled {
            background-color: #dc3545;
        }

        /* Watermark for Cancelled */
        .watermark {
            position: absolute;
            top: 30%;
            left: 20%;
            font-size: 80px;
            color: rgba(220, 53, 69, 0.2);
            transform: rotate(-45deg);
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @if ($po->status === 'cancelled')
        <div class="watermark">DIBATALKAN (VOID)</div>
    @endif
    @if ($po->status === 'received')
        <div class="watermark" style="color: rgba(40, 167, 69, 0.1);">SELESAI</div>
    @endif

    <div class="header">
        <table>
            <tr>
                <td>
                    @if ($business && $business->logo_url)
                        <?php
                        $path = storage_path('app/public/' . $business->logo);
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_exists($path) ? file_get_contents($path) : '';
                        $base64 = $data ? 'data:image/' . $type . ';base64,' . base64_encode($data) : '';
                        ?>
                        @if ($base64)
                            <img src="{{ $base64 }}" alt="Logo">
                        @else
                            <h2>{{ $business->name ?? 'Sollu App' }}</h2>
                        @endif
                    @else
                        <h2>{{ $business->name ?? 'Sollu App' }}</h2>
                    @endif
                </td>
                <td class="title">
                    PURCHASE ORDER<br>
                    <span style="font-size: 16px; font-weight: normal;">#{{ $po->po_number }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <strong>Kepada (Supplier):</strong><br>
                {{ $po->supplier?->name ?? '-' }}<br>
                {{ $po->supplier?->address ?? '' }}<br>
                {{ $po->supplier?->phone ?? '' }}
            </td>
            <td>
                <strong>Dikirim Ke (Outlet):</strong><br>
                {{ $po->outlet?->name ?? '-' }}<br>
                {{ $po->outlet?->address ?? '' }}<br>
                <br>
                <strong>Tanggal PO:</strong> {{ $po->created_at->format('d M Y') }}<br><br>
                <strong>Status:</strong>
                <span class="status-badge status-{{ $po->status }}">
                    {{ strtoupper($po->status === 'cancelled' ? 'DIBATALKAN' : $po->status) }}
                </span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th class="text-center">Jml Pesan</th>
                @if ($po->status === 'received')
                    <th class="text-center">Jml Terima</th>
                @endif
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->inventoryItem?->name ?? 'Item' }}
                        <br><small style="color: #666;">Satuan: {{ $item->uom?->name ?? '-' }}</small>
                    </td>
                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($item->qty_ordered, 2, ',', '.'), '0'), ',') }}</td>
                    @if ($po->status === 'received')
                        <td class="text-center">
                            {{ rtrim(rtrim(number_format($item->qty_received, 2, ',', '.'), '0'), ',') }}</td>
                    @endif
                    <td class="text-right">Rp {{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $po->status === 'received' ? 5 : 4 }}" class="text-right" style="font-weight: bold;">
                    TOTAL KESELURUHAN</td>
                <td class="text-right" style="font-weight: bold; font-size: 16px;">Rp
                    {{ number_format($po->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($po->notes)
        <div style="margin-top: 20px;">
            <strong>Catatan Tambahan:</strong><br>
            <p style="white-space: pre-wrap;">{{ $po->notes }}</p>
        </div>
    @endif
</body>

</html>
