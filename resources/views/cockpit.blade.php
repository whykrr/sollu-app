<html class="dark">

<head>
    <base href="/">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Sollu | Cockpit</title>
    <link rel="icon" href="img/icon.png">
    <meta name="theme-color" content="#004AAD">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sollu">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
    <link rel="manifest" href="build/manifest.webmanifest">
    @routes
    @vite(['resources/css/app.css', 'resources/js/cockpit.js'])
    @inertiaHead
</head>

<body class="text-neutral-800 overflow-x-hidden" style="margin: 0px">
    @inertia
</body>

</html>
