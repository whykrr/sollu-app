<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Stok - {{ $data->transfer_number }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px 0;
        }
        .info-table .label {
            font-weight: bold;
            width: 120px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            text-align: center;
        }
        .signature-box {
            width: 30%;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin: 40px auto 5px auto;
            width: 80%;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet' => $outlet ?? null,
        'title' => 'BUKTI TRANSFER STOK',
        'subtitle' => 'No: ' . $data->transfer_number
    ])

    <table class="info-table">
        <tr>
            <td class="label">Dari Outlet</td>
            <td>: {{ $data->fromOutlet->name ?? '-' }}</td>
            <td class="label">Tanggal Dibuat</td>
            <td>: {{ $data->created_at->format('d M Y, H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Ke Outlet</td>
            <td>: {{ $data->toOutlet->name ?? '-' }}</td>
            <td class="label">Status</td>
            <td>: 
                @if($data->status == 'pending') Menunggu
                @elseif($data->status == 'approved') Disetujui
                @elseif($data->status == 'in_transit') Dalam Perjalanan
                @elseif($data->status == 'completed') Selesai
                @elseif($data->status == 'rejected') Ditolak
                @else {{ $data->status }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Catatan</td>
            <td colspan="3">: {{ $data->notes ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Detail Item</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 45%">Nama Item</th>
                <th style="width: 15%">Satuan</th>
                <th class="text-right" style="width: 15%">Qty Dikirim</th>
                @if(in_array($data->status, ['completed', 'rejected']))
                <th class="text-right" style="width: 20%">Qty Diterima</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($data->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->inventoryItem->name ?? '-' }}</td>
                <td>{{ $item->inventoryItem->uom->name ?? '-' }}</td>
                <td class="text-right">{{ $item->qty_formatted }}</td>
                @if(in_array($data->status, ['completed', 'rejected']))
                <td class="text-right">{{ $item->qty_received_formatted }}</td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ in_array($data->status, ['completed', 'rejected']) ? 5 : 4 }}" class="text-center">Tidak ada data item</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total Item:</th>
                <th class="text-right">{{ app(\App\Models\Inventory\StockTransferItem::class)->formatQuantity($data->items->sum('qty')) }}</th>
                @if(in_array($data->status, ['completed', 'rejected']))
                <th class="text-right">{{ app(\App\Models\Inventory\StockTransferItem::class)->formatQuantity($data->items->sum('qty_received')) }}</th>
                @endif
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div>Dibuat Oleh,</div>
                <div class="signature-line"></div>
                <div>{{ $data->requester->name ?? '(.........................)' }}</div>
            </td>
            <td class="signature-box">
                <div>Disetujui Oleh,</div>
                <div class="signature-line"></div>
                <div>{{ $data->approver->name ?? '(.........................)' }}</div>
            </td>
            <td class="signature-box">
                <div>Diterima Oleh,</div>
                <div class="signature-line"></div>
                <div>{{ $data->receiver->name ?? '(.........................)' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
