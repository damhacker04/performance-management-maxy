<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Maxy Academy') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="margin:0;padding:0;background:var(--neutral-50);">

@php
    $user     = auth()->user();
    $hour     = now()->hour;
    $greet    = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
    $isStaff    = $user->role === 'staff';
    $isLeader   = $user->role === 'leader';
    $isCLevel   = $user->role === 'c_level';
    $canExport  = $user->canExport(); // c_level atau is_management = true

    // Null-safe active tab detection
    $onDashboard    = request()->routeIs('dashboard');
    $onTasks        = request()->routeIs('daily-tasks.*');
    // Tab Target aktif jika di monthly-targets ATAU weekly-targets ATAU leader-targets
    $onTargets      = request()->routeIs('monthly-targets.*') || request()->routeIs('weekly-targets.*') || request()->routeIs('leader-targets.*');
    $onMyTargets    = request()->routeIs('staff-targets.*');
    $onKpi          = request()->routeIs('kpi');
    $onProfile      = request()->routeIs('profile.*');
    $onExport       = request()->routeIs('export.*');
@endphp

<div class="app-shell">

    <!-- ── App Bar ── -->
    <header class="appbar">
        <div class="left">
            <div class="av">{{ $initials }}</div>
            <div>
                <div class="greet">{{ $greet }}</div>
                <div class="uname">{{ $user->name }}</div>
            </div>
        </div>
        <div class="right">
            <button class="icon-btn" type="button" aria-label="Notifikasi">
                <svg class="lucide" viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </button>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button class="icon-btn" type="submit" aria-label="Keluar">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M16 17l5-5-5-5M21 12H9M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                </button>
            </form>
        </div>
    </header>

    <!-- ── Flash messages ── -->
    @if (session('success'))
        <div style="padding:10px 16px 0;">
            <div class="alert alert-success">{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="padding:10px 16px 0;">
            <div class="alert alert-danger">{{ session('error') }}</div>
        </div>
    @endif

    <!-- ── Page content ── -->
    {{ $slot }}

    <!-- ── Bottom Tab Bar ── -->
    <nav class="tabbar">

        {{-- Beranda --}}
        <a href="{{ route('dashboard') }}" class="tab {{ $onDashboard ? 'active' : '' }}">
            <span class="glyph">
                <svg class="lucide" viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V21H3z"/></svg>
            </span>
            Beranda
        </a>

        @if ($isStaff)
            {{-- Target (staff — read-only view dept target) --}}
            <a href="{{ route('staff-targets.index') }}" class="tab {{ $onMyTargets ? 'active' : '' }}">
                <span class="glyph">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
                Target
            </a>

            {{-- Tugas (staff) --}}
            <a href="{{ route('daily-tasks.index') }}" class="tab {{ $onTasks ? 'active' : '' }}">
                <span class="glyph">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                Tugas
            </a>
        @else
            {{-- Target (leader/c_level) — dengan segmented control Kelola Tim | Target Saya --}}
            <a href="{{ route('monthly-targets.index') }}" class="tab {{ $onTargets ? 'active' : '' }}">
                <span class="glyph">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
                Target
            </a>

            {{-- Tugas (leader) — input daily task mereka sendiri ke C-Level --}}
            @if($isLeader)
            <a href="{{ route('daily-tasks.index') }}" class="tab {{ $onTasks ? 'active' : '' }}">
                <span class="glyph">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                Tugas
            </a>
            @endif

            {{-- KPI (leader/c_level) --}}
            <a href="{{ route('kpi') }}" class="tab {{ $onKpi ? 'active' : '' }}">
                <span class="glyph">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/></svg>
                </span>
                KPI
            </a>
        @endif

        {{-- Export — hanya c_level dan is_management (Bu Ika, Fanny, dll) --}}
        @if($canExport)
        <a href="{{ route('export.index') }}" class="tab {{ $onExport ? 'active' : '' }}">
            <span class="glyph">
                <svg class="lucide" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </span>
            Export
        </a>
        @endif

        {{-- Profil --}}
        <a href="{{ route('profile.edit') }}" class="tab {{ $onProfile ? 'active' : '' }}">
            <span class="glyph">
                <svg class="lucide" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </span>
            Profil
        </a>

    </nav>

</div>
</body>
</html>
