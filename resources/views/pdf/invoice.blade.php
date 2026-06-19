<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.4;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .header td {
            vertical-align: top;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .status.open { background: #fff3cd; color: #856404; }
        .status.paid { background: #d4edda; color: #155724; }
        .status.void { background: #f8d7da; color: #721c24; }
        
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .info-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: #f4f4f4;
            border-bottom: 2px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header">
            <tr>
                <td>
                    <div class="title">Invoice #{{ $invoice->invoice_number }}</div>
                    <div>Tanggal: {{ $invoice->created_at->format('d M Y H:i') }}</div>
                    <div>Jatuh Tempo: {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}</div>
                </td>
                <td class="text-right">
                    @if($invoice->status == 'open')
                        <span class="status open">Belum Dibayar</span>
                    @elseif($invoice->status == 'paid')
                        <span class="status paid">Terbayar</span>
                    @elseif($invoice->status == 'void')
                        <span class="status void">Dibatalkan</span>
                    @else
                        <span class="status">{{ $invoice->status }}</span>
                    @endif
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>
                    <div class="info-title">Ditagihkan Oleh:</div>
                    <strong>PT. SOLUSI DARI ANAK BANGSA</strong><br>
                    NPWP 1000 0000 0546 70
                </td>
                <td>
                    <div class="info-title">Ditagihkan Kepada:</div>
                    <strong>{{ $invoice->business ? $invoice->business->name : '-' }}</strong><br>
                    {{ $invoice->business ? $invoice->business->owner_name : '-' }}<br>
                    {{ $invoice->business ? $invoice->business->address : '-' }}
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        {{ $item->description }}
                        @if($item->item_type == 'outlet_addition' && isset($item->metadata['remaining_days']))
                            <div style="font-size: 11px; color: #777;">Prorated {{ $item->metadata['remaining_days'] }} hari tersisa</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total</td>
                    <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        @if($payment)
        <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px;">
            <div class="info-title">Detail Pembayaran Terakhir</div>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 150px;"><strong>Order ID:</strong></td>
                    <td>{{ $payment->order_id }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td style="text-transform: capitalize;">{{ $payment->status }}</td>
                </tr>
                <tr>
                    <td><strong>Metode:</strong></td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                </tr>
            </table>
        </div>
        @endif
    </div>
</body>
</html>
