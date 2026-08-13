<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $transaction->transaction_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .header td {
            vertical-align: top;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .invoice-title {
            font-size: 28px;
            color: #3498db;
            text-align: right;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        .items-table td {
            border-bottom: 1px solid #dee2e6;
            padding: 10px;
        }
        .text-right {
            text-align: right;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 5px 10px;
        }
        .summary-table .bold {
            font-weight: bold;
        }
        .total-row {
            font-size: 16px;
            border-top: 2px solid #333;
            color: #2c3e50;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid { background-color: #2ecc71; }
        .status-unpaid { background-color: #e74c3c; }
        .status-void { background-color: #95a5a6; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td>
                <div class="company-name">Sollu App</div>
                <div>{{ $transaction->outlet->name ?? 'Outlet Utama' }}</div>
            </td>
            <td class="text-right">
                <div class="invoice-title">INVOICE</div>
                <div>No: <strong>{{ $transaction->transaction_number }}</strong></div>
                <div>Tanggal: {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}</div>
                @if($transaction->status === 'paid')
                    <div class="status-badge status-paid">LUNAS</div>
                @elseif($transaction->status === 'unpaid' || $transaction->status === 'partial')
                    <div class="status-badge status-unpaid">BELUM LUNAS</div>
                @elseif($transaction->status === 'void' || $transaction->status === 'cancel')
                    <div class="status-badge status-void">BATAL</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <strong>Ditagihkan kepada:</strong><br>
                @if($transaction->customer)
                    {{ $transaction->customer->name }}<br>
                    {{ $transaction->customer->email }}<br>
                    {{ $transaction->customer->phone }}
                @else
                    Pelanggan Umum
                @endif
            </td>
            <td class="text-right">
                <strong>Jatuh Tempo:</strong><br>
                {{ $transaction->due_date ? \Carbon\Carbon::parse($transaction->due_date)->format('d M Y') : '-' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->qty }}</td>
                <td class="text-right">Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                @if($transaction->notes)
                    <strong>Catatan:</strong>
                    <p>{{ $transaction->notes }}</p>
                @endif
            </td>
            <td style="width: 50%;">
                <table class="summary-table">
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($transaction->discount_amount > 0)
                    <tr>
                        <td>Diskon Tambahan</td>
                        <td class="text-right">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($transaction->shipping_fee > 0)
                    <tr>
                        <td>Biaya Pengiriman</td>
                        <td class="text-right">Rp {{ number_format($transaction->shipping_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($transaction->tax_amount > 0)
                    <tr>
                        <td>Pajak</td>
                        <td class="text-right">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($transaction->service_charge_amount > 0)
                    <tr>
                        <td>Service Charge</td>
                        <td class="text-right">Rp {{ number_format($transaction->service_charge_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row bold">
                        <td>TOTAL KESELURUHAN</td>
                        <td class="text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Sudah Dibayar</td>
                        <td class="text-right">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="bold">Sisa Tagihan</td>
                        <td class="text-right bold">Rp {{ number_format($transaction->total - $transaction->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
