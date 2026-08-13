<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $transaction->invoice?->invoice_number ?? $transaction->transaction_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f1f5f9;
            border-bottom: 2px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #475569;
        }
        .items-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 10px;
            vertical-align: top;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .text-right {
            text-align: right;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 4px 8px;
        }
        .summary-table .bold {
            font-weight: bold;
        }
        .total-row {
            font-size: 15px;
            border-top: 2px solid #334155;
            border-bottom: 2px solid #334155;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #10b981; color: #ffffff; }
        .badge-warning { background-color: #f59e0b; color: #ffffff; }
        .badge-danger { background-color: #ef4444; color: #ffffff; }
        .badge-secondary { background-color: #64748b; color: #ffffff; }
        .promo-tag {
            font-size: 11px;
            color: #2563eb;
            font-style: italic;
        }
    </style>
</head>
<body>

    @include('pdf.partials.header', [
        'business' => $business ?? $transaction->outlet?->business ?? null,
        'outlet'   => $outlet ?? $transaction->outlet ?? null,
        'title'    => 'INVOICE PENJUALAN',
        'subtitle' => '# ' . ($transaction->invoice?->invoice_number ?? $transaction->transaction_number)
    ])

    <table class="info-table">
        <tr>
            <td style="padding-right: 10px;">
                <div class="info-box">
                    <strong style="color: #475569; text-transform: uppercase; font-size: 11px; display: block; margin-bottom: 4px;">DITAGIHKAN KEPADA:</strong>
                    @if($transaction->customer)
                        <div style="font-weight: bold; font-size: 14px; color: #0f172a;">{{ $transaction->customer->name }}</div>
                        @if($transaction->customer->email)
                            <div>Email: {{ $transaction->customer->email }}</div>
                        @endif
                        @if($transaction->customer->phone)
                            <div>Telp/HP: {{ $transaction->customer->phone }}</div>
                        @endif
                        @if($transaction->customer->address)
                            <div>Alamat: {{ $transaction->customer->address }}</div>
                        @endif
                    @else
                        <div style="font-weight: bold; font-size: 14px; color: #0f172a;">Pelanggan Umum</div>
                    @endif
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="info-box">
                    <strong style="color: #475569; text-transform: uppercase; font-size: 11px; display: block; margin-bottom: 4px;">RINCIAN DOKUMEN:</strong>
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <tr>
                            <td style="padding: 2px 0;">No. Transaksi</td>
                            <td style="padding: 2px 0; font-weight: bold;" class="text-right">{{ $transaction->transaction_number }}</td>
                        </tr>
                        @if($transaction->invoice)
                        <tr>
                            <td style="padding: 2px 0;">No. Invoice</td>
                            <td style="padding: 2px 0; font-weight: bold;" class="text-right">{{ $transaction->invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Term Pembayaran</td>
                            <td style="padding: 2px 0;" class="text-right">
                                {{ $transaction->invoice->payment_term === 'credit' ? 'Kredit / Term' : 'Tunai / Cash' }}
                            </td>
                        </tr>
                        @if($transaction->invoice->payment_term === 'credit' && $transaction->invoice->due_date)
                        <tr>
                            <td style="padding: 2px 0;">Jatuh Tempo</td>
                            <td style="padding: 2px 0; color: #dc2626; font-weight: bold;" class="text-right">
                                {{ \Carbon\Carbon::parse($transaction->invoice->due_date)->format('d M Y') }}
                            </td>
                        </tr>
                        @endif
                        @endif
                        <tr>
                            <td style="padding: 2px 0;">Status Pembayaran</td>
                            <td style="padding: 2px 0;" class="text-right">
                                @if($transaction->status === 'paid')
                                    <span class="badge badge-success">LUNAS</span>
                                @elseif($transaction->status === 'unpaid')
                                    <span class="badge badge-danger">BELUM LUNAS</span>
                                @elseif($transaction->status === 'partial')
                                    <span class="badge badge-warning">SEBAGIAN</span>
                                @elseif($transaction->status === 'draft')
                                    <span class="badge badge-warning">DRAF</span>
                                @else
                                    <span class="badge badge-secondary">{{ strtoupper($transaction->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Produk / Varian</th>
                <th style="width: 15%; text-align: right;">Harga</th>
                <th style="width: 10%; text-align: right;">Qty</th>
                <th style="width: 10%; text-align: right;">Diskon</th>
                <th style="width: 15%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div style="font-weight: bold; color: #0f172a;">{{ $item->product_name }}</div>
                    @if($item->promo_name)
                        <div class="promo-tag">Promo: {{ $item->promo_name }}</div>
                    @endif
                </td>
                <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->qty }}</td>
                <td class="text-right">
                    @if($item->discount_amount > 0)
                        - Rp {{ number_format($item->discount_amount, 0, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right" style="font-weight: bold;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 10px;">
        <tr style="page-break-inside: avoid;">
            <td style="width: 50%; vertical-align: top;">
                @if($transaction->promos && $transaction->promos->count() > 0)
                    <div style="margin-bottom: 10px; padding: 8px; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; font-size: 11px;">
                        <strong style="color: #1e40af;">Promo Dokumen Terpakai:</strong>
                        @foreach($transaction->promos as $p)
                            <div style="color: #1e3a8a;">• {{ $p->promo_name }} (-Rp {{ number_format($p->discount_amount, 0, ',', '.') }})</div>
                        @endforeach
                    </div>
                @endif
                @if($transaction->notes)
                    <div style="font-size: 12px; color: #475569;">
                        <strong>Catatan:</strong>
                        <p style="margin: 4px 0 0 0; color: #334155;">{{ $transaction->notes }}</p>
                    </div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table class="summary-table">
                    <tr>
                        <td>Subtotal Produk</td>
                        <td class="text-right">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($transaction->discount_amount > 0)
                    <tr>
                        <td>Diskon Dokumen</td>
                        <td class="text-right" style="color: #dc2626;">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
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
                        <td style="padding: 8px 0;">TOTAL KESELURUHAN</td>
                        <td class="text-right" style="padding: 8px 0;">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
