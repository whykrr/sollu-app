<?php

$controllers = [
    'SalesReportController',
    'ProductReportController',
    'StockAssetReportController',
    'CashierShiftReportController',
    'PromotionReportController',
    'CustomerReportController',
];

foreach ($controllers as $className) {
    $file = "app/Http/Controllers/Reports/{$className}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace(
            "\$outletId = \$request->get('outlet', '');",
            "\$outletId = \$request->get('outlet') ?? '';",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed $className\n";
    }
}
