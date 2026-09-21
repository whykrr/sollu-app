<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Retur Pembelian {{ $return->return_number }}</title>
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
    @if ($return->status === \App\Enums\PurchaseReturnStatus::Voided || $return->status === 'voided')
        <div class="watermark">DIBATALKAN (VOID)</div>
    @endif

    @include('pdf.partials.header', [
        'business' => $business,
        'outlet' => null,
        'title' => 'NOTA RETUR PEMBELIAN',
        'subtitle' => '#' . $return->return_number
    ])

    <table class="info-table">
        <tr>
            <td>
                <strong>Kepada Supplier:</strong><br>
                {{ $return->supplier?->name ?? '-' }}<br>
                {{ $return->supplier?->address ?? '' }}<br>
                {{ $return->supplier?->phone ?? '' }}
                @if ($return->purchaseOrder)
                    <br><br>
                    <strong>Referensi PO:</strong> {{ $return->purchaseOrder->po_number }}
                @endif
            </td>
            <td>
                <strong>Asal Outlet:</strong><br>
                {{ $return->outlet?->name ?? '-' }}<br>
                {{ $return->outlet?->address ?? '' }}
                <br><br>
                <strong>Tanggal Retur:</strong> {{ $return->return_date ? date('d M Y', strtotime($return->return_date)) : $return->created_at->format('d M Y') }}<br>
                <strong>Dibuat Oleh:</strong> {{ $return->creator?->name ?? '-' }}<br><br>
                <strong>Status:</strong>
                <span class="status-badge status-{{ is_string($return->status) ? $return->status : $return->status->value }}">
                    {{ strtoupper(is_string($return->status) ? \App\Enums\PurchaseReturnStatus::tryFrom($return->status)?->label() ?? $return->status : $return->status->label()) }}
                </span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th class="text-center">Jml Retur</th>
                <th class="text-right">Nilai Satuan</th>
                <th class="text-right">Subtotal Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($return->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->inventoryItem?->name ?? 'Item' }}
                        <br><small style="color: #666;">Satuan: {{ $item->uom?->name ?? ($item->inventoryItem?->uom?->name ?? '-') }}</small>
                    </td>
                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($item->return_purchase_qty, 2, ',', '.'), '0'), ',') }}
                    </td>
                    <td class="text-right">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="font-weight: bold;">TOTAL NILAI RETUR</td>
                <td class="text-right" style="font-weight: bold; font-size: 16px;">Rp {{ number_format($return->total_return_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($return->reason)
        <div style="margin-top: 20px;">
            <strong>Alasan Retur:</strong><br>
            <p style="white-space: pre-wrap;">{{ $return->reason }}</p>
        </div>
    @endif
</body>

</html>
