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
            width: 25%;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin: 40px auto 5px auto;
            width: 80%;
        }
        .void-banner {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 8px 12px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 11px;
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

    @php
        $statusVal = is_object($data->status) ? $data->status->value : $data->status;
        $modeVal = is_object($data->transfer_mode) ? $data->transfer_mode->value : $data->transfer_mode;
        $isReceivedOrCompleted = ($statusVal === 'completed') || ($statusVal === 'void' && $data->received_at);
        $hasDiscrepancy = $data->items->contains(fn($i) => ($i->qty_difference ?? 0) > 0);
    @endphp

    @if($statusVal === 'void')
    <div class="void-banner">
        <strong>PERHATIAN: DOKUMEN TRANSFER INI TELAH DIBATALKAN (VOID)</strong><br>
        @if($data->void_reason)
        <span>Alasan: {{ $data->void_reason }}</span><br>
        @endif
        <span style="font-size: 10px; color: #7f1d1d;">
            Dibatalkan pada {{ $data->voided_at ? $data->voided_at->format('d M Y, H:i') : '-' }} {{ $data->voidedBy ? 'oleh ' . $data->voidedBy->name : '' }}
        </span>
    </div>
    @endif

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
                @if($statusVal == 'pending') Menunggu
                @elseif($statusVal == 'approved') Disetujui
                @elseif($statusVal == 'in_transit') Dalam Perjalanan
                @elseif($statusVal == 'completed') Selesai
                @elseif($statusVal == 'rejected') Ditolak
                @elseif($statusVal == 'void') Dibatalkan (Void)
                @else {{ $statusVal }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Mode Transfer</td>
            <td>: {{ is_object($data->transfer_mode) ? $data->transfer_mode->label() : ($modeVal == 'two_step' ? 'Kirim & Terima (2-Langkah)' : 'Transfer Langsung (1-Langkah)') }}</td>
            <td class="label">Catatan</td>
            <td>: {{ $data->notes ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Detail Item</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: {{ $isReceivedOrCompleted ? ($hasDiscrepancy ? '35%' : '40%') : '50%' }}">Nama Item</th>
                <th style="width: 15%">Satuan</th>
                <th class="text-right" style="width: 15%">Qty Dikirim</th>
                @if($isReceivedOrCompleted)
                <th class="text-right" style="width: 15%">Qty Diterima</th>
                @if($hasDiscrepancy)
                <th class="text-right" style="width: 15%">Selisih</th>
                @endif
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($data->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $item->inventoryItem->name ?? '-' }}
                    @if($hasDiscrepancy && ($item->qty_difference ?? 0) > 0 && $item->difference_reason)
                    <div style="font-size: 10px; color: #b91c1c; font-style: italic;">
                        Alasan: {{ $item->difference_reason }}
                    </div>
                    @endif
                </td>
                <td>{{ $item->inventoryItem->uom->name ?? '-' }}</td>
                <td class="text-right">{{ $item->qty_formatted }}</td>
                @if($isReceivedOrCompleted)
                <td class="text-right">{{ $item->qty_received_formatted }}</td>
                @if($hasDiscrepancy)
                <td class="text-right" style="{{ ($item->qty_difference ?? 0) > 0 ? 'color: #b91c1c; font-weight: bold;' : '' }}">
                    {{ $item->qty_difference_formatted ?? '0' }}
                </td>
                @endif
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $isReceivedOrCompleted ? ($hasDiscrepancy ? 6 : 5) : 4 }}" class="text-center">Tidak ada data item</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total Item:</th>
                <th class="text-right">{{ app(\App\Models\Inventory\StockTransferItem::class)->formatQuantity($data->items->sum('qty')) }}</th>
                @if($isReceivedOrCompleted)
                <th class="text-right">{{ app(\App\Models\Inventory\StockTransferItem::class)->formatQuantity($data->items->sum('qty_received')) }}</th>
                @if($hasDiscrepancy)
                <th class="text-right">{{ app(\App\Models\Inventory\StockTransferItem::class)->formatQuantity($data->items->sum('qty_difference')) }}</th>
                @endif
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
            @if($modeVal === 'two_step')
            <td class="signature-box">
                <div>Dikirim Oleh,</div>
                <div class="signature-line"></div>
                <div>{{ $data->shipper->name ?? '(.........................)' }}</div>
            </td>
            @endif
            <td class="signature-box">
                <div>Diterima Oleh,</div>
                <div class="signature-line"></div>
                <div>{{ $data->receiver->name ?? '(.........................)' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
