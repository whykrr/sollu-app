<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Mutasi Stok - {{ $item->sku }}</title>
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
        .text-red { color: #dc3545; }
        .text-green { color: #28a745; }
        .text-gray { color: #6c757d; }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet' => $outlet ?? null,
        'title' => 'RIWAYAT MUTASI STOK',
        'subtitle' => 'Periode: 30 Hari Terakhir'
    ])

    <table class="header-info">
        <tr>
            <td class="label">Nama Produk</td>
            <td>: {{ $item->name }}</td>
            <td class="label">Kategori</td>
            <td>: {{ $item->product->category->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">SKU</td>
            <td>: {{ $item->sku ?: '-' }}</td>
            <td class="label">Tipe Item</td>
            <td>: {{ $item->item_type == 'raw_material' ? 'Bahan Baku' : 'Produk' }}</td>
        </tr>
        <tr>
            <td class="label">Barcode</td>
            <td>: {{ $item->barcode ?: '-' }}</td>
            <td class="label">Satuan</td>
            <td>: {{ $item->uom->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal / Waktu</th>
                <th>Jenis Mutasi</th>
                <th class="text-right">Perubahan Qty</th>
                <th class="text-right">Stok Akhir</th>
                <th>User / Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $index => $movement)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                    <td>{{ is_string($movement->movement_type) ? (App\Enums\InventoryMovementType::tryFrom($movement->movement_type)?->label() ?? $movement->movement_type) : $movement->movement_type?->label() }}</td>
                    <td class="text-right">
                        @if($movement->qty_change > 0)
                            <span class="text-green">+{{ number_format($movement->qty_change, 2) }}</span>
                        @elseif($movement->qty_change < 0)
                            <span class="text-red">{{ number_format($movement->qty_change, 2) }}</span>
                        @else
                            <span class="text-gray">{{ number_format($movement->qty_change, 2) }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($movement->stock_after, 2) }}</td>
                    <td>
                        {{ $movement->creator->name ?? '-' }}
                        @if($movement->description)
                            <br><small class="text-gray">Catatan: {{ $movement->description }}</small>
                        @endif
                    </td>
                </tr>
            @endforeach
            
            @if($movements->isEmpty())
            <tr>
                <td colspan="6" class="text-center">Tidak ada mutasi dalam 30 hari terakhir.</td>
            </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
