<?php

$services = [
    'SalesReportService.php' => function ($content) {
        // Only paginate daily_sales
        return str_replace("return [\n            'daily_sales' => \$dailySales->get(),\n            'payment_methods' => \$paymentMethods->get(),\n        ];", "return [\n            'daily_sales' => \$dailySales->paginate(15),\n            'payment_methods' => \$paymentMethods->get(),\n        ];", $content);
    },
    'ProductReportService.php' => function ($content) {
        return str_replace('->get();', '->paginate(15);', $content);
    },
    'StockAssetReportService.php' => function ($content) {
        return str_replace('->get();', '->paginate(15);', $content);
    },
    'CashierShiftReportService.php' => function ($content) {
        return str_replace('->get();', '->paginate(15);', $content);
    },
    'PromotionReportService.php' => function ($content) {
        return str_replace('->get();', '->paginate(15);', $content);
    },
    'CustomerReportService.php' => function ($content) {
        return str_replace('->get();', '->paginate(15);', $content);
    },
];

foreach ($services as $file => $callback) {
    $path = "app/Services/Reports/{$file}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $newContent = $callback($content);
        file_put_contents($path, $newContent);
        echo "Patched $file\n";
    }
}
