<html class="dark">

<head>
    <base href="{{ url('') }}">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    @routes
    @vite(['resources/css/web.css', 'resources/js/web.js'])
    @inertiaHead
</head>

<body class="bg-neutral-200 text-neutral-800" style="margin: 0px">
    @inertia
</body>

</html>
