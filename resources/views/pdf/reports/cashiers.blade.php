<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Shift & Kasir</title>
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

        .text-danger {
            color: #c53030;
        }

        .text-success {
            color: #276749;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
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
        'title'    => 'LAPORAN SHIFT & KASIR',
        'subtitle' => 'Periode: ' . ($start_date ?? '-') . ' - ' . ($end_date ?? '-')
    ])

    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Kasir</th>
                <th>Waktu Buka</th>
                <th>Waktu Tutup</th>
                <th class="text-center">Status</th>
                <th class="text-right">Kas Awal</th>
                <th class="text-right">Expected (Sistem)</th>
                <th class="text-right">Actual (Fisik)</th>
                <th class="text-right">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalStart = 0;
                $totalExpected = 0;
                $totalActual = 0;
                $totalDiff = 0;
                $shifts = $data ?? [];
            @endphp
            @forelse($shifts as $item)
                @php
                    $shift = (object) $item;
                    $totalStart += $shift->starting_cash ?? 0;
                    $totalExpected += $shift->expected_ending_cash ?? 0;
                    $totalActual += $shift->actual_ending_cash ?? 0;
                    $totalDiff += $shift->difference ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-bold">{{ $shift->cashier_name ?? '-' }}</td>
                    <td>{{ $shift->opened_at ? \Carbon\Carbon::parse($shift->opened_at)->translatedFormat('d M Y H:i') : '-' }}</td>
                    <td>{{ $shift->closed_at ? \Carbon\Carbon::parse($shift->closed_at)->translatedFormat('d M Y H:i') : 'Belum Tutup' }}</td>
                    <td class="text-center">
                        @if(($shift->status ?? '') === 'closed')
                            <span class="badge badge-success">Closed</span>
                        @else
                            <span class="badge badge-warning">Open</span>
                        @endif
                    </td>
                    <td class="text-right">Rp {{ number_format($shift->starting_cash ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($shift->expected_ending_cash ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($shift->actual_ending_cash ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-bold {{ ($shift->difference ?? 0) < 0 ? 'text-danger' : (($shift->difference ?? 0) > 0 ? 'text-success' : '') }}">
                        {{ ($shift->difference ?? 0) > 0 ? '+' : '' }}Rp {{ number_format($shift->difference ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">Tidak ada data shift kasir pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($shifts) > 0)
                <tr class="total-row">
                    <td colspan="5" class="text-center font-bold">TOTAL</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalStart, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalExpected, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($totalActual, 0, ',', '.') }}</td>
                    <td class="text-right font-bold {{ $totalDiff < 0 ? 'text-danger' : ($totalDiff > 0 ? 'text-success' : '') }}">
                        {{ $totalDiff > 0 ? '+' : '' }}Rp {{ number_format($totalDiff, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
