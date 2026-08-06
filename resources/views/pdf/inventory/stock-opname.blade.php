<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stock Opname {{ $data->opname_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .table-data { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-data th, .table-data td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .table-data th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .header-info { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .header-info td { padding: 4px; vertical-align: top; }
        .label { font-weight: bold; width: 120px; }
        .summary-box { float: right; width: 300px; margin-top: 20px; border: 1px solid #ddd; padding: 10px; }
        .summary-box table { width: 100%; }
        .summary-box td { padding: 3px 0; }
        .text-red { color: #dc3545; }
        .text-green { color: #28a745; }
        .text-gray { color: #6c757d; }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet' => $outlet ?? null,
        'title' => 'DOKUMEN STOCK OPNAME',
        'subtitle' => 'No: ' . $data->opname_number
    ])

    <table class="header-info">
        <tr>
            <td class="label">Tanggal Dibuat</td>
            <td>: {{ $data->created_at->format('d M Y H:i') }}</td>
            <td class="label">Status</td>
            <td>: 
                @if($data->status == 'in_progress') Sedang Berjalan
                @elseif($data->status == 'pending_approval') Menunggu Persetujuan
                @elseif($data->status == 'approved') Disetujui
                @elseif($data->status == 'rejected') Ditolak
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Dibuat Oleh</td>
            <td>: {{ $data->creator->name ?? '-' }}</td>
            <td class="label">Disetujui/Ditolak</td>
            <td>: {{ $data->approver->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Catatan</td>
            <td colspan="3">: {{ $data->notes ?: '-' }}</td>
        </tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Item</th>
                <th>SKU</th>
                <th>Satuan</th>
                <th class="text-right">Stok Sistem</th>
                <th class="text-right">Stok Fisik</th>
                <th class="text-right">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @php
                $cocok = 0;
                $berselisih = 0;
                $totalSurplus = 0;
                $totalShortage = 0;
            @endphp
            @foreach($data->items as $index => $item)
                @php
                    $selisih = $item->difference_qty;
                    if ($selisih == 0) $cocok++;
                    else $berselisih++;

                    if ($selisih > 0) $totalSurplus += $selisih;
                    elseif ($selisih < 0) $totalShortage += abs($selisih);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->inventoryItem->name }}</td>
                    <td>{{ $item->inventoryItem->sku ?: '-' }}</td>
                    <td>{{ $item->inventoryItem->uom->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->system_qty, 2) }}</td>
                    <td class="text-right">{{ number_format($item->actual_qty, 2) }}</td>
                    <td class="text-right">
                        @if($selisih > 0)
                            <span class="text-green">+{{ number_format($selisih, 2) }}</span>
                        @elseif($selisih < 0)
                            <span class="text-red">{{ number_format($selisih, 2) }}</span>
                        @else
                            <span class="text-gray">{{ number_format($selisih, 2) }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <table>
            <tr>
                <td><strong>Total Item</strong></td>
                <td class="text-right">{{ $data->items->count() }}</td>
            </tr>
            <tr>
                <td><strong>Item Cocok</strong></td>
                <td class="text-right">{{ $cocok }}</td>
            </tr>
            <tr>
                <td><strong>Item Berselisih</strong></td>
                <td class="text-right">{{ $berselisih }}</td>
            </tr>
            <tr>
                <td><strong>Total Surplus</strong></td>
                <td class="text-right text-green">+{{ number_format($totalSurplus, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Shortage</strong></td>
                <td class="text-right text-red">-{{ number_format($totalShortage, 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
