<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 11px;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }

        .table tr {
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #444;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-left {
            text-align: left !important;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-muted {
            color: #777;
        }

        .text-danger {
            color: #c53030;
        }

        .text-success {
            color: #276749;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f4f4f4;
            border-top: 2px solid #bbb;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'business' => $business ?? null,
        'outlet'   => $outlet ?? null,
        'title'    => 'LAPORAN PENJUALAN',
        'subtitle' => 'Periode: ' . ($start_date ?? '-') . ' - ' . ($end_date ?? '-')
    ])

    <div class="section-title">Laporan Penjualan Harian</div>
    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Tanggal</th>
                <th class="text-right">Gross Omset</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Pajak</th>
                <th class="text-right">Net Omset</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalGross = 0;
                $totalDiscount = 0;
                $totalTax = 0;
                $totalNet = 0;
                $dailySales = $data['daily_sales'] ?? [];
            @endphp
            @forelse($dailySales as $item)
                @php
                    $itemObj = (object) $item;
                    $totalGross += $itemObj->gross_sales ?? 0;
                    $totalDiscount += $itemObj->total_discount ?? 0;
                    $totalTax += $itemObj->total_tax ?? 0;
                    $totalNet += $itemObj->net_sales ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($itemObj->date)->translatedFormat('d M Y') }}</td>
                    <td class="text-right">Rp {{ number_format($itemObj->gross_sales ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right text-danger">{{ ($itemObj->total_discount ?? 0) > 0 ? '-Rp ' . number_format($itemObj->total_discount, 0, ',', '.') : 'Rp 0' }}</td>
                    <td class="text-right">Rp {{ number_format($itemObj->total_tax ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($itemObj->net_sales ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Tidak ada data penjualan pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($dailySales) > 0)
                <tr class="total-row">
                    <td colspan="2" class="text-center font-bold">TOTAL</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalGross, 0, ',', '.') }}</td>
                    <td class="text-right font-bold text-danger">{{ $totalDiscount > 0 ? '-Rp ' . number_format($totalDiscount, 0, ',', '.') : 'Rp 0' }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalTax, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalNet, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Ringkasan Metode Pembayaran</div>
    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Metode Pembayaran</th>
                <th class="text-right">Jumlah Transaksi</th>
                <th class="text-right">Total Penerimaan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalTxCount = 0;
                $totalTxRevenue = 0;
                $paymentMethods = $data['payment_methods'] ?? [];
            @endphp
            @forelse($paymentMethods as $payment)
                @php
                    $payObj = (object) $payment;
                    $totalTxCount += $payObj->total_transactions ?? 0;
                    $totalTxRevenue += $payObj->total_revenue ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $payObj->payment_name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($payObj->total_transactions ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($payObj->total_revenue ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Tidak ada data pembayaran pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($paymentMethods) > 0)
                <tr class="total-row">
                    <td colspan="2" class="text-center font-bold">TOTAL</td>
                    <td class="text-right font-bold">{{ number_format($totalTxCount, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalTxRevenue, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
