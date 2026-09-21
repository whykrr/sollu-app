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

        .status-partial_received {
            background-color: #e0a800;
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

    @include('pdf.partials.header', [
        'business' => $business,
        'outlet' => null,
        'title' => 'PURCHASE ORDER',
        'subtitle' => '#' . $po->po_number
    ])

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
                <strong>Tanggal PO:</strong> {{ $po->order_date ? date('d M Y', strtotime($po->order_date)) : $po->created_at->format('d M Y') }}<br>
                @if ($po->reference_number)
                    <strong>No. Referensi:</strong> {{ $po->reference_number }}<br>
                @endif
                <br>
                <strong>Status:</strong>
                <span class="status-badge status-{{ is_string($po->status) ? $po->status : $po->status->value }}">
                    {{ strtoupper(is_string($po->status) ? \App\Enums\PurchaseOrderStatus::tryFrom($po->status)?->label() ?? $po->status : $po->status->label()) }}
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
                @if (in_array(is_string($po->status) ? $po->status : $po->status->value, ['partial_received', 'received']))
                    <th class="text-center">Jml Terima</th>
                @endif
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Pajak</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->inventoryItem?->name ?? 'Item' }}
                        <br><small style="color: #666;">Satuan Beli: {{ $item->uom?->name ?? '-' }}</small>
                    </td>
                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($item->qty_ordered, 2, ',', '.'), '0'), ',') }}</td>
                    @if (in_array(is_string($po->status) ? $po->status : $po->status->value, ['partial_received', 'received']))
                        <td class="text-center">
                            {{ rtrim(rtrim(number_format($item->qty_received, 2, ',', '.'), '0'), ',') }}</td>
                    @endif
                    <td class="text-right">Rp {{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->discount_amount ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->tax_amount ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ in_array(is_string($po->status) ? $po->status : $po->status->value, ['partial_received', 'received']) ? 7 : 6 }}" class="text-right" style="font-weight: bold;">
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
