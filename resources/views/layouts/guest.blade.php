<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Maxy Academy') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="margin:0;padding:0;background:var(--maxy-navy);">
    <div class="login-screen" style="max-width:var(--app-max);margin:0 auto;">
        <!-- Top: logo + headline -->
        <div class="login-top">
            <img src="{{ asset('images/m-logo.png') }}" alt="Maxy" class="login-logo" />
            <div class="login-title">Performance Management</div>
            <div class="login-sub">Masuk untuk mencatat laporan harian dan memantau KPI tim Anda.</div>
        </div>

        <!-- Bottom: white card -->
        <div class="login-card">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
