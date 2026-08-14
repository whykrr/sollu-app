<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Promosi</title>
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

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-primary {
            background-color: #e2e8f0;
            color: #2d3748;
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
        'title'    => 'LAPORAN PROMOSI',
        'subtitle' => 'Periode: ' . ($start_date ?? '-') . ' - ' . ($end_date ?? '-')
    ])

    <table class="table" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th>Nama Promo</th>
                <th>Tipe Promo</th>
                <th class="text-right">Total Pemakaian</th>
                <th class="text-right">Total Diskon Diberikan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalUsage = 0;
                $totalDiscountGiven = 0;
                $promotions = $data ?? [];
            @endphp
            @forelse($promotions as $item)
                @php
                    $promo = (object) $item;
                    $totalUsage += $promo->total_usage ?? 0;
                    $totalDiscountGiven += $promo->total_discount_given ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-bold">{{ $promo->promo_name ?? '-' }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $promo->promo_type ?? '-' }}</span>
                    </td>
                    <td class="text-right">{{ number_format($promo->total_usage ?? 0, 0, ',', '.') }}x</td>
                    <td class="text-right font-bold text-danger">Rp {{ number_format($promo->total_discount_given ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Tidak ada promo yang digunakan pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($promotions) > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-center font-bold">TOTAL</td>
                    <td class="text-right font-bold">{{ number_format($totalUsage, 0, ',', '.') }}x</td>
                    <td class="text-right font-bold text-danger">Rp {{ number_format($totalDiscountGiven, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
