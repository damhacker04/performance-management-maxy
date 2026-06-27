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
    // Tab Target aktif jika di monthly-targets ATAU weekly-targets ATAU leader-targets ATAU period (hierarki baru)
    $onTargets      = request()->routeIs('monthly-targets.*') || request()->routeIs('weekly-targets.*') || request()->routeIs('leader-targets.*') || request()->routeIs('period.*') || request()->routeIs('ceo.targets.*');
    // Tujuan menu "Target" beda per role: C-Level ke halaman target khusus, leader ke daftar target dept.
    $targetRoute    = $isCLevel ? route('ceo.targets.index') : route('monthly-targets.index');
    $onMyTargets    = request()->routeIs('staff-targets.*');
    $onKpi          = request()->routeIs('kpi') || request()->routeIs('kpi.*');
    $onWorkload     = request()->routeIs('workload-report.*');
    $onProfile      = request()->routeIs('profile.*');
    $onExport       = request()->routeIs('export.*');

    // Role label untuk sidebar
    $roleLabel = match($user->role) {
        'leader'  => 'Leader',
        'c_level' => 'C-Level',
        'staff'   => 'Staff',
        default   => ucfirst($user->role),
    };
@endphp

<div class="app-shell">

    {{-- ═══════════════════════════════════════
         SIDEBAR — Desktop only (hidden on mobile via CSS)
    ═══════════════════════════════════════ --}}
    <aside class="dt-sidebar">

        {{-- Logo + Nama App --}}
        <div class="dt-sidebar-header">
            <div class="dt-sidebar-logo">M</div>
            <span class="dt-sidebar-appname">Maxy Academy</span>
        </div>

        {{-- User Info --}}
        <div class="dt-sidebar-user">
            <div class="dt-sidebar-av">{{ $initials }}</div>
            <div class="dt-sidebar-name">{{ $user->name }}</div>
            <span class="dt-sidebar-role">{{ $roleLabel }}</span>
        </div>

        {{-- Nav Items --}}
        <nav class="dt-sidebar-nav">

            {{-- Beranda --}}
            <a href="{{ route('dashboard') }}"
               class="dt-nav-item {{ $onDashboard ? 'active' : '' }}">
                <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V21H3z"/></svg>
                Beranda
            </a>

            @if ($isStaff)
                {{-- Target (staff) --}}
                <a href="{{ route('staff-targets.index') }}"
                   class="dt-nav-item {{ $onMyTargets ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Target
                </a>

                {{-- Tugas (staff) --}}
                <a href="{{ route('daily-tasks.index') }}"
                   class="dt-nav-item {{ $onTasks ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Tugas
                </a>
            @else
                {{-- Target (leader/c_level) --}}
                <a href="{{ $targetRoute }}"
                   class="dt-nav-item {{ $onTargets ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Target
                </a>

                {{-- Tugas (leader saja) --}}
                @if($isLeader)
                <a href="{{ route('daily-tasks.index') }}"
                   class="dt-nav-item {{ $onTasks ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Tugas
                </a>
                @endif

                {{-- KPI (leader/c_level) --}}
                <a href="{{ route('kpi') }}"
                   class="dt-nav-item {{ $onKpi ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/></svg>
                    KPI
                </a>

                {{-- Workload Report (leader/c_level/management) --}}
                <a href="{{ route('workload-report.index') }}"
                   class="dt-nav-item {{ $onWorkload ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Workload Report
                </a>
            @endif

            {{-- Export — c_level & management --}}
            @if($canExport)
            <a href="{{ route('export.index') }}"
               class="dt-nav-item {{ $onExport ? 'active' : '' }}">
                <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </a>
            @endif

            {{-- Profil --}}
            <a href="{{ route('profile.edit') }}"
               class="dt-nav-item {{ $onProfile ? 'active' : '' }}">
                <svg class="dt-nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                Profil
            </a>

        </nav>

        {{-- Logout --}}
        <div class="dt-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="dt-logout-btn">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M16 17l5-5-5-5M21 12H9M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                    Keluar
                </button>
            </form>
        </div>

    </aside>

    {{-- ═══════════════════════════════════════
         MAIN WRAPPER — Topbar + Scrollable content
    ═══════════════════════════════════════ --}}
    <div class="dt-main">

        {{-- ── Topbar (Mobile: appbar | Desktop: simplified topbar) ── --}}
        <header class="appbar">
            {{-- Mobile: avatar + greeting (disembunyikan di desktop via CSS) --}}
            <div class="left">
                <div class="av">{{ $initials }}</div>
                <div>
                    <div class="greet">{{ $greet }}</div>
                    <div class="uname">{{ $user->name }}</div>
                </div>
            </div>
            <div class="right">
                @php
                    $unreadCount = \App\Models\AppNotification::where('user_id', auth()->id())
                        ->whereNull('read_at')->count();
                    $recentNotifs = \App\Models\AppNotification::where('user_id', auth()->id())
                        ->orderByDesc('created_at')->take(5)->get();
                @endphp

                {{-- Lonceng Notifikasi --}}
                <div style="position:relative;" x-data="{ open: false }" @click.away="open = false">
                    <button class="icon-btn" type="button" aria-label="Notifikasi"
                            @click="open = !open"
                            style="position:relative;">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @if($unreadCount > 0)
                            <span style="position:absolute;top:-2px;right:-2px;min-width:16px;height:16px;
                                         background:#EF4444;color:#fff;border-radius:999px;font-size:10px;
                                         font-weight:700;display:flex;align-items:center;justify-content:center;
                                         padding:0 3px;border:2px solid var(--bg-1, #fff);line-height:1;">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </button>

                    {{-- Dropdown notifikasi --}}
                    <div x-show="open" x-cloak
                         style="position:absolute;right:-8px;top:calc(100% + 8px);width:300px;
                                background:#fff;border:1px solid var(--bg-3, #e5e7eb);border-radius:12px;
                                box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9999;overflow:hidden;">
                        <div style="display:flex;align-items:center;justify-content:space-between;
                                    padding:10px 14px;border-bottom:1px solid #f3f4f6;">
                            <span style="font-size:13px;font-weight:700;color:var(--fg-1, #111);">
                                Notifikasi
                                @if($unreadCount > 0)
                                    <span style="background:#EF4444;color:#fff;font-size:10px;padding:1px 6px;border-radius:999px;margin-left:4px;">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </span>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" style="font-size:11px;color:var(--maxy-navy,#1e3a5f);background:none;border:none;cursor:pointer;padding:0;">
                                        Tandai semua dibaca
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div style="max-height:320px;overflow-y:auto;">
                            @forelse($recentNotifs as $notif)
                                <a href="{{ route('notifications.read', $notif) }}"
                                   style="display:block;padding:10px 14px;border-bottom:1px solid #f9fafb;
                                          text-decoration:none;color:inherit;
                                          background:{{ $notif->read_at ? '#fff' : '#F0F7FF' }};
                                          transition:background .15s;">
                                    <div style="display:flex;gap:8px;align-items:flex-start;">
                                        <span style="flex-shrink:0;margin-top:1px;color:var(--maxy-navy);">
                                            @if($notif->type === 'revision_requested')
                                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a4 4 0 0 1 0 8h-1"/></svg>
                                            @elseif($notif->type === 'revision_submitted')
                                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                            @elseif($notif->type === 'auto_rejected')
                                                <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--danger);"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                                            @elseif($notif->type === 'not_submitted')
                                                <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--warning);"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg>
                                            @else
                                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                            @endif
                                        </span>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:12px;font-weight:{{ $notif->read_at ? '500' : '700' }};color:var(--fg-1, #111);margin-bottom:2px;">
                                                {{ $notif->title }}
                                            </div>
                                            <div style="font-size:11px;color:var(--fg-3, #6b7280);line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                {{ Str::limit($notif->body, 60) }}
                                            </div>
                                            <div style="font-size:10px;color:var(--fg-4, #9ca3af);margin-top:3px;">
                                                {{ $notif->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                        @if(!$notif->read_at)
                                            <span style="width:7px;height:7px;border-radius:50%;background:#3B82F6;flex-shrink:0;margin-top:4px;"></span>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div style="padding:24px 14px;text-align:center;font-size:12px;color:var(--fg-3, #6b7280);">
                                    Belum ada notifikasi
                                </div>
                            @endforelse
                        </div>

                        <a href="{{ route('notifications.index') }}"
                           style="display:block;text-align:center;padding:10px;font-size:12px;
                                  color:var(--maxy-navy,#1e3a5f);font-weight:600;
                                  border-top:1px solid #f3f4f6;text-decoration:none;">
                            Lihat semua notifikasi →
                        </a>
                    </div>
                </div>

                {{-- Logout button (mobile) — di desktop logout ada di sidebar --}}
                <form method="POST" action="{{ route('logout') }}" style="margin:0;" class="dt-mobile-logout">
                    @csrf
                    <button class="icon-btn" type="submit" aria-label="Keluar">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M16 17l5-5-5-5M21 12H9M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                    </button>
                </form>
            </div>
        </header>

        {{-- ── Scrollable content wrapper ── --}}
        <div class="dt-content">

            {{-- Flash messages --}}
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

            {{-- Page content --}}
            {{ $slot }}

        </div>

        {{-- ── Bottom Tab Bar (mobile only — hidden on desktop) ── --}}
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
                {{-- Target (leader/c_level) --}}
                <a href="{{ $targetRoute }}" class="tab {{ $onTargets ? 'active' : '' }}">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                    Target
                </a>

                {{-- Tugas (leader) --}}
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

                {{-- Workload Report --}}
                <a href="{{ route('workload-report.index') }}" class="tab {{ $onWorkload ? 'active' : '' }}">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    </span>
                    Report
                </a>
            @endif


            {{-- Export --}}
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

    </div>{{-- end dt-main --}}

</div>{{-- end app-shell --}}

@include('partials.confirm-modal')
</body>
</html>
