<html class="dark">

<head>
    <base href="<?php echo e(url('')); ?>">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Website CMS</title>
    <link rel="icon" href="storage/img/icon-dark.png">
    <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>

<body class="bg-neutral-200 text-neutral-800 overflow-x-hidden" style="margin: 0px">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
</body>

</html>
<?php /**PATH /Users/whykrr/Documents/Projects/Laravel/sollu-dashboard/resources/views/app.blade.php ENDPATH**/ ?>