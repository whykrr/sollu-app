<?php

$controllers = [
    'SalesReportController' => ['ExportSalesReportJob', 'sales', 'reports.sales'],
    'ProductReportController' => ['ExportProductReportJob', 'products', 'reports.products'],
    'StockAssetReportController' => ['ExportStockReportJob', 'stocks', 'reports.stocks'],
    'CashierShiftReportController' => ['ExportCashierReportJob', 'cashiers', 'reports.cashiers'],
    'PromotionReportController' => ['ExportPromotionReportJob', 'promotions', 'reports.promotions'],
    'CustomerReportController' => ['ExportCustomerReportJob', 'customers', 'reports.customers'],
];

foreach ($controllers as $className => $data) {
    $file = "app/Http/Controllers/Reports/{$className}.php";
    $content = file_get_contents($file);

    $jobName = $data[0];
    $bladeName = $data[1];
    $routePrefix = $data[2];

    // Add imports
    $imports = "use Barryvdh\DomPDF\Facade\Pdf;\nuse Illuminate\Support\Facades\Auth;\nuse App\Jobs\Reports\\{$jobName};\n";
    $content = str_replace("use Inertia\Inertia;\n", "use Inertia\Inertia;\n".$imports, $content);

    // Add methods before the last '}'
    $methods = "
    public function exportPdf(Request \$request, \$service)
    {
        \$startDateParam = \$request->get('start_date');
        \$endDateParam = \$request->get('end_date');
        \$outletId = \$request->get('outlet', '');

        \$now = Carbon::now();
        \$startDate = \$startDateParam ? Carbon::parse(\$startDateParam)->startOfDay() : \$now->copy()->startOfMonth();
        \$endDate = \$endDateParam ? Carbon::parse(\$endDateParam)->endOfDay() : \$now->copy()->endOfDay();

        \$data = \$service->getReport(\$outletId, \$startDate, \$endDate);

        \$pdf = Pdf::loadView('pdf.reports.{$bladeName}', [
            'data'     => \$data,
            'business' => Auth::user()->business,
            'outlet'   => Auth::user()->activeOutlet,
            'start_date' => \$startDate->format('d M Y'),
            'end_date' => \$endDate->format('d M Y'),
        ])->setPaper('a4', 'landscape');

        return \$pdf->download('{$bladeName}_report_' . now()->format('YmdHis') . '.pdf');
    }

    public function exportCsv(Request \$request)
    {
        \$startDateParam = \$request->get('start_date');
        \$endDateParam = \$request->get('end_date');
        \$outletId = \$request->get('outlet', '');

        \$now = Carbon::now();
        \$startDate = \$startDateParam ? Carbon::parse(\$startDateParam)->startOfDay() : \$now->copy()->startOfMonth();
        \$endDate = \$endDateParam ? Carbon::parse(\$endDateParam)->endOfDay() : \$now->copy()->endOfDay();

        {$jobName}::dispatch(Auth::user(), (array) \$outletId, \$startDate, \$endDate);

        return redirect()->back()->with('success', 'Proses ekspor CSV sedang berjalan di latar belakang. Anda akan menerima notifikasi jika sudah selesai.');
    }
}
";

    // Fix the dependency injection for exportPdf
    $serviceClass = str_replace('Controller', 'Service', $className);
    $methods = str_replace('public function exportPdf(Request $request, $service)', "public function exportPdf(Request \$request, \\App\\Services\\Reports\\{$serviceClass} \$service)", $methods);

    $content = preg_replace('/}\s*$/', $methods, $content);
    file_put_contents($file, $content);
    echo "Patched $className\n";
}
