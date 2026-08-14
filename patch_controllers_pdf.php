<?php

$controllers = [
    'SalesReportController' => 'ExportSalesReportPdfJob',
    'ProductReportController' => 'ExportProductReportPdfJob',
    'StockAssetReportController' => 'ExportStockReportPdfJob',
    'CashierShiftReportController' => 'ExportCashierReportPdfJob',
    'PromotionReportController' => 'ExportPromotionReportPdfJob',
    'CustomerReportController' => 'ExportCustomerReportPdfJob',
];

foreach ($controllers as $className => $jobName) {
    $file = "app/Http/Controllers/Reports/{$className}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);

        // Remove Barryvdh\DomPDF\Facade\Pdf import if exists
        $content = preg_replace('/use Barryvdh\\\\DomPDF\\\\Facade\\\\Pdf;\n/', '', $content);

        // Replace exportPdf method body
        $pattern = '/public function exportPdf\(Request \$request.*?\}\s*public function exportCsv/s';
        $replacement = "public function exportPdf(Request \$request)
    {
        \$startDateParam = \$request->get('start_date');
        \$endDateParam = \$request->get('end_date');
        \$outletId = \$request->get('outlet') ?? '';

        \$now = \\Carbon\\Carbon::now();
        \$startDate = \$startDateParam ? \\Carbon\\Carbon::parse(\$startDateParam)->startOfDay() : \$now->copy()->startOfMonth();
        \$endDate = \$endDateParam ? \\Carbon\\Carbon::parse(\$endDateParam)->endOfDay() : \$now->copy()->endOfDay();

        \\App\\Jobs\\Reports\\Pdf\\{$jobName}::dispatch(\\Illuminate\\Support\\Facades\\Auth::user(), (array) \$outletId, \$startDate, \$endDate);

        return redirect()->back()->with('success', 'Proses ekspor PDF sedang berjalan di latar belakang. Anda akan menerima notifikasi jika sudah selesai.');
    }

    public function exportCsv";

        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($file, $content);
        echo "Patched $className\n";
    }
}
