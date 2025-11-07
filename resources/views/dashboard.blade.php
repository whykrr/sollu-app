<html class="dark">

<head>
    <base href="{{ url('') }}">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Sollu | Dashboard</title>
    <link rel="icon" href="storage/img/icon-dark.png">
    @routes
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
    @inertiaHead
</head>

<body class="text-neutral-800 overflow-x-hidden" style="margin: 0px">
    @inertia
</body>

</html>
