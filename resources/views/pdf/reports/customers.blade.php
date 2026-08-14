<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pelanggan</title>
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
            margin-top: 15px;
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
        'title'    => 'LAPORAN PELANGGAN',
        'subtitle' => 'Periode: ' . ($start_date ?? '-') . ' - ' . ($end_date ?? '-')
    ])

    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Nama Pelanggan</th>
                <th>Kontak</th>
                <th class="text-right">Total Kunjungan</th>
                <th class="text-right">Total Belanja</th>
                <th class="text-center">Kunjungan Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalVisits = 0;
                $totalSpent = 0;
                $customers = $data ?? [];
            @endphp
            @forelse($customers as $item)
                @php
                    $customer = (object) $item;
                    $totalVisits += $customer->total_visits ?? 0;
                    $totalSpent += $customer->total_spent ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-bold">{{ $customer->name ?? '-' }}</td>
                    <td>
                        {{ $customer->phone ?: '-' }}
                        @if(!empty($customer->email))
                            <br><small class="text-muted">{{ $customer->email }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($customer->total_visits ?? 0, 0, ',', '.') }}x</td>
                    <td class="text-right font-bold text-success">Rp {{ number_format($customer->total_spent ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center">
                        {{ !empty($customer->last_visit) ? \Carbon\Carbon::parse($customer->last_visit)->translatedFormat('d M Y H:i') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Tidak ada data pelanggan yang bertransaksi pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($customers) > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-center font-bold">TOTAL</td>
                    <td class="text-right font-bold">{{ number_format($totalVisits, 0, ',', '.') }}x</td>
                    <td class="text-right font-bold text-success">Rp {{ number_format($totalSpent, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
