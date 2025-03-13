<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Sandbox Module - {{ config('app.name', 'Laravel') }}</title>

    <meta name="description" content="{{ $description ?? '' }}">
    <meta name="keywords" content="{{ $keywords ?? '' }}">
    <meta name="author" content="{{ $author ?? '' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('modules/sandbox/css/styles.css') }}">

    {{-- script --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
        {{-- Vite CSS --}}
        {{-- {{ module_vite('build-sandbox', 'resources/assets/sass/app.scss', storage_path('vite.hot')) }} --}}
            <
            script src = "chart.js" >
    </script>
    <script src="https://kit.fontawesome.com/9cfa50ee02.js" crossorigin="anonymous"></script>

    @vite([
        'Modules/SandBox/Resources/assets/js/infinite-scroll.js',
        'Modules/SandBox/Resources/assets/css/style.css',
    ])

</head>

<body>
    <style>
        body {
            background-color: aliceblue;
        }
    </style>

    @yield('content')
</body>
