<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur {{ $transaction->invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 20px 25px 30px 25px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
        }
        body {
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .company-info {
            font-size: 10px;
            color: #4b5563;
            line-height: 1.3;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }
        .doc-meta {
            font-size: 10px;
            color: #374151;
            text-align: right;
            line-height: 1.4;
        }
        .divider {
            border-top: 2px solid #e2e8f0;
            margin: 10px 0 15px 0;
        }
        .party-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .party-table td {
            width: 50%;
            vertical-align: top;
            padding: 8px 10px;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        .party-title {
            font-size: 10px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .party-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .party-detail {
            font-size: 10px;
            color: #475569;
            line-height: 1.3;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .items-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            font-size: 10px;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .summary-wrapper td {
            vertical-align: top;
        }
        .payment-info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .payment-info-title {
            font-weight: bold;
            color: #334155;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 4px 6px;
            font-size: 10px;
        }
        .summary-label {
            color: #475569;
            text-align: right;
        }
        .summary-value {
            font-weight: 600;
            text-align: right;
            color: #0f172a;
            width: 110px;
        }
        .grand-total-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            padding: 6px 6px;
        }
        .balance-due-row td {
            font-size: 11px;
            font-weight: bold;
            color: #b91c1c;
            padding: 4px 6px;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .history-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 9px;
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        .history-table td {
            font-size: 9px;
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .signature-title {
            font-size: 10px;
            color: #475569;
            font-weight: 600;
            margin-bottom: 50px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
        }
        .signature-sub {
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>
    {{-- Header Section --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $transaction->outlet?->business?->name ?? 'Sollu Merchant' }}</div>
                <div class="company-info">
                    <strong>Outlet:</strong> {{ $transaction->outlet?->name ?? 'Main Outlet' }}<br>
                    @if($transaction->outlet?->address)
                        {{ $transaction->outlet->address }}<br>
                    @endif
                    @if($transaction->outlet?->phone)
                        <strong>Telp:</strong> {{ $transaction->outlet->phone }}
                    @endif
                    @if($transaction->outlet?->email)
                        | <strong>Email:</strong> {{ $transaction->outlet->email }}
                    @endif
                </div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">
                    <h1>FAKTUR PENJUALAN</h1>
                </div>
                <div class="doc-meta">
                    <strong>No. Faktur:</strong> {{ $transaction->invoice->invoice_number }}<br>
                    <strong>No. Referensi:</strong> {{ $transaction->transaction_number }}<br>
                    <strong>Tanggal Faktur:</strong> {{ $transaction->invoice->invoice_date?->format('d/m/Y') ?? $transaction->transaction_date->format('d/m/Y') }}<br>
                    <strong>Termin:</strong> {{ $transaction->invoice->payment_term?->label() ?? 'Tunai' }} ({{ strtoupper($transaction->invoice->payment_term_code ?? 'COD') }})<br>
                    @if($transaction->invoice->due_date)
                        <strong>Jatuh Tempo:</strong> {{ $transaction->invoice->due_date->format('d/m/Y') }}<br>
                    @endif
                    <strong>Saluran:</strong> {{ $transaction->channel?->label() ?? 'Wholesale' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- Party Section (Bill To / Ship To) --}}
    <table class="party-table">
        <tr>
            <td>
                <div class="party-title">Ditagihkan Kepada (Bill To):</div>
                <div class="party-name">{{ $transaction->customer?->name ?? 'Pelanggan Langsung / Umum' }}</div>
                <div class="party-detail">
                    @if($transaction->customer?->phone)
                        <strong>Kontak:</strong> {{ $transaction->customer->phone }}<br>
                    @endif
                    @if($transaction->customer?->email)
                        <strong>Email:</strong> {{ $transaction->customer->email }}<br>
                    @endif
                    @if($transaction->customer?->address)
                        <strong>Alamat:</strong> {{ $transaction->customer->address }}
                    @endif
                </div>
            </td>
            <td style="border-left: 8px solid #ffffff;">
                <div class="party-title">Status Faktur & Dokumen:</div>
                <div class="party-name">{{ $transaction->status->label() }}</div>
                <div class="party-detail">
                    <strong>Status Pembayaran:</strong> {{ $transaction->payment_status->label() }}<br>
                    <strong>Dibuat Oleh:</strong> {{ $transaction->creator?->name ?? 'Staff Sales' }}<br>
                    @if($transaction->notes)
                        <strong>Catatan:</strong> {{ $transaction->notes }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Line Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 80px;">SKU</th>
                <th>Deskripsi Produk / Jasa</th>
                <th style="width: 45px;" class="text-center">Satuan</th>
                <th style="width: 45px;" class="text-center">Qty</th>
                <th style="width: 75px;" class="text-right">Harga (Rp)</th>
                <th style="width: 65px;" class="text-right">Diskon (Rp)</th>
                <th style="width: 85px;" class="text-right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->sku ?? '-' }}</td>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->notes)
                        <br><span style="font-size: 8px; color: #64748b;"><em>{{ $item->notes }}</em></span>
                    @endif
                </td>
                <td class="text-center">{{ $item->uom_name ?? 'Pcs' }}</td>
                <td class="text-center">{{ number_format($item->qty, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Summary & Bank Info --}}
    <table class="summary-wrapper">
        <tr>
            <td style="width: 55%; padding-right: 15px;">
                @if($transaction->invoice->terms_and_conditions)
                    <div class="payment-info-box">
                        <div class="payment-info-title">Syarat & Ketentuan:</div>
                        <div style="font-size: 9px; color: #475569;">
                            {{ $transaction->invoice->terms_and_conditions }}
                        </div>
                    </div>
                @endif

                @if($transaction->payments && $transaction->payments->isNotEmpty())
                    <div style="margin-top: 5px;">
                        <strong style="font-size: 10px; color: #334155;">Riwayat Pembayaran Diterima:</strong>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th>Referensi</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>{{ $payment->paymentMethod?->name ?? 'Transfer' }}</td>
                                    <td>{{ $payment->payment_reference ?? '-' }}</td>
                                    <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </td>
            <td style="width: 45%;">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Subtotal Produk:</td>
                        <td class="summary-value">Rp {{ number_format($summary->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($summary->totalDiscount > 0)
                    <tr>
                        <td class="summary-label">Total Diskon:</td>
                        <td class="summary-value" style="color: #b91c1c;">- Rp {{ number_format($summary->totalDiscount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($summary->shippingFee > 0)
                    <tr>
                        <td class="summary-label">Biaya Pengiriman:</td>
                        <td class="summary-value">Rp {{ number_format($summary->shippingFee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($summary->taxAmount > 0)
                    <tr>
                        <td class="summary-label">PPN / Pajak:</td>
                        <td class="summary-value">Rp {{ number_format($summary->taxAmount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td class="summary-label" style="font-weight: 800; color: #0f172a;">GRAND TOTAL:</td>
                        <td class="summary-value" style="font-size: 12px; font-weight: 800;">Rp {{ number_format($summary->grandTotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Total Dibayar:</td>
                        <td class="summary-value" style="color: #15803d;">Rp {{ number_format($summary->totalPaid, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="balance-due-row">
                        <td class="summary-label" style="font-weight: bold; color: #b91c1c;">SISA TAGIHAN:</td>
                        <td class="summary-value">Rp {{ number_format($summary->balanceDue, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Signatures Section --}}
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-title">Dibuat Oleh,</div>
                <div class="signature-line">{{ $transaction->creator?->name ?? 'Sales Staff' }}</div>
                <div class="signature-sub">Bagian Penjualan</div>
            </td>
            <td>
                <div class="signature-title">Disetujui Oleh,</div>
                <div class="signature-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                <div class="signature-sub">Finance / Manager</div>
            </td>
            <td>
                <div class="signature-title">Diterima Oleh,</div>
                <div class="signature-line">{{ $transaction->customer?->name ?? '( ................................ )' }}</div>
                <div class="signature-sub">Tanda Tangan & Cap Pelanggan</div>
            </td>
        </tr>
    </table>
</body>
</html>
