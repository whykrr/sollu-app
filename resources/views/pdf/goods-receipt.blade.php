<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Bukti Penerimaan Barang {{ $receipt->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
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

        .status-completed {
            background-color: #28a745;
        }

        .status-voided {
            background-color: #dc3545;
        }

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
    @if ($receipt->status === \App\Enums\GoodsReceiptStatus::Voided || $receipt->status === 'voided')
        <div class="watermark">DIBATALKAN (VOID)</div>
    @endif

    @include('pdf.partials.header', [
        'business' => $business,
        'outlet' => null,
        'title' => 'BUKTI PENERIMAAN BARANG',
        'subtitle' => '#' . $receipt->receipt_number
    ])

    <table class="info-table">
        <tr>
            <td>
                <strong>Pemasok / Rekanan:</strong><br>
                {{ $receipt->purchaseOrder?->supplier?->name ?? '-' }}<br>
                {{ $receipt->purchaseOrder?->supplier?->address ?? '' }}<br>
                {{ $receipt->purchaseOrder?->supplier?->phone ?? '' }}
                <br><br>
                <strong>Nomor PO Terkait:</strong> {{ $receipt->purchaseOrder?->po_number ?? '-' }}<br>
                <strong>No. Surat Jalan (DO):</strong> {{ $receipt->delivery_order_number ?: '-' }}
            </td>
            <td>
                <strong>Diterima di Outlet:</strong><br>
                {{ $receipt->outlet?->name ?? '-' }}<br>
                {{ $receipt->outlet?->address ?? '' }}
                <br><br>
                <strong>Tanggal Diterima:</strong> {{ $receipt->received_at ? $receipt->received_at->format('d M Y H:i') : $receipt->created_at->format('d M Y H:i') }}<br>
                <strong>Diterima Oleh:</strong> {{ $receipt->receiver?->name ?? '-' }}<br><br>
                <strong>Status:</strong>
                <span class="status-badge status-{{ is_string($receipt->status) ? $receipt->status : $receipt->status->value }}">
                    {{ strtoupper(is_string($receipt->status) ? \App\Enums\GoodsReceiptStatus::tryFrom($receipt->status)?->label() ?? $receipt->status : $receipt->status->label()) }}
                </span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th class="text-center">Jml Diterima (Satuan Beli)</th>
                <th class="text-center">Faktor Konversi</th>
                <th class="text-center">Stok Masuk (Satuan Stok)</th>
                <th class="text-right">Biaya Satuan</th>
                <th class="text-right">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCost = 0; @endphp
            @foreach ($receipt->items as $index => $item)
                @php $totalCost += $item->total_cost; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->inventoryItem?->name ?? 'Item' }}
                        @if ($item->inventoryItem?->sku)
                            <br><small style="color: #666;">SKU: {{ $item->inventoryItem->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($item->received_purchase_qty, 2, ',', '.'), '0'), ',') }}
                        {{ $item->uom?->name ?? '' }}
                    </td>
                    <td class="text-center">
                        &times; {{ rtrim(rtrim(number_format($item->conversion_factor, 4, ',', '.'), '0'), ',') }}
                    </td>
                    <td class="text-center" style="font-weight: bold;">
                        {{ rtrim(rtrim(number_format($item->received_inventory_qty, 2, ',', '.'), '0'), ',') }}
                        {{ $item->inventoryItem?->uom?->name ?? '' }}
                    </td>
                    <td class="text-right">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right" style="font-weight: bold;">TOTAL PEROLEHAN STOK</td>
                <td class="text-right" style="font-weight: bold; font-size: 16px;">Rp {{ number_format($totalCost, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($receipt->notes)
        <div style="margin-top: 20px;">
            <strong>Catatan:</strong><br>
            <p style="white-space: pre-wrap;">{{ $receipt->notes }}</p>
        </div>
    @endif
</body>

</html>
