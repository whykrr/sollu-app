<html class="dark">

<head>
    <base href="{{ url('') }}">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Website CMS</title>
    <link rel="icon" href="storage/img/icon-dark.png">
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="bg-neutral-200 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200" style="margin: 0px">
    @inertia
</body>

</html>
