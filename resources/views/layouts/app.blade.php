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
    $isStaff      = $user->role === 'staff';
    $isLeader     = $user->role === 'leader';
    $isCLevel     = $user->role === 'c_level';
    $isSuperAdmin = $user->role === 'super_admin';
    $isExecutive  = $isCLevel || $isSuperAdmin; // super_admin punya akses & menu setara C-Level
    $canExport    = $user->canExport(); // c_level atau is_management = true

    // Null-safe active tab detection
    $onDashboard    = request()->routeIs('dashboard');
    $onTasks        = request()->routeIs('daily-tasks.*');
    // Tab Target aktif jika di monthly-targets ATAU weekly-targets ATAU leader-targets ATAU period (hierarki baru)
    $onTargets      = request()->routeIs('monthly-targets.*') || request()->routeIs('weekly-targets.*') || request()->routeIs('leader-targets.*') || request()->routeIs('period.*') || request()->routeIs('ceo.targets.*') || request()->routeIs('admin.targets.*');
    // Tujuan menu "Target/Overview/KPI" beda per role:
    //   super_admin → halaman /admin/* sendiri; C-Level → halaman /ceo/*; leader → daftar target dept.
    $targetRoute    = $isSuperAdmin ? route('admin.targets.index') : ($isCLevel ? route('ceo.targets.index') : route('monthly-targets.index'));
    $overviewRoute  = $isSuperAdmin ? route('admin.overview') : route('ceo.overview');
    $kpiRoute       = $isSuperAdmin ? route('admin.kpi') : route('kpi');
    $onMyTargets    = request()->routeIs('staff-targets.*');
    $onOverview     = request()->routeIs('ceo.overview') || request()->routeIs('admin.overview');
    $onKpi          = request()->routeIs('kpi') || request()->routeIs('kpi.*') || request()->routeIs('admin.kpi');
    $onWorkload     = request()->routeIs('workload-report.*');
    $onProfile      = request()->routeIs('profile.*');
    $onExport       = request()->routeIs('export.*');
    // Admin panel (super_admin / HR)
    $onAdminUsers   = request()->routeIs('admin.users.*');
    $onAdminAssign  = request()->routeIs('admin.target-assignment.*');
    // Item yang masuk sheet "Lainnya" di tabbar mobile super_admin
    $onMore         = $onTargets || $onKpi || $onWorkload || $onExport || $onProfile;

    // Role label untuk sidebar
    $roleLabel = match($user->role) {
        'leader'      => 'Leader',
        'c_level'     => 'C-Level',
        'staff'       => 'Staff',
        'super_admin' => 'Admin HR',
        default       => ucfirst($user->role),
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
                {{-- Overview / Progress Staf (c_level & super_admin) --}}
                @if($isExecutive)
                <a href="{{ $overviewRoute }}"
                   class="dt-nav-item {{ $onOverview ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                    Overview
                </a>
                @endif

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

                {{-- KPI (leader/c_level; super_admin → /admin/kpi) --}}
                <a href="{{ $kpiRoute }}"
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

            {{-- Panel Admin (super_admin / HR) — di atas akses C-Level penuh --}}
            @if ($isSuperAdmin)
                <a href="{{ route('admin.users.index') }}"
                   class="dt-nav-item {{ $onAdminUsers ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Manajemen Pengguna
                </a>
                <a href="{{ route('admin.target-assignment.index') }}"
                   class="dt-nav-item {{ $onAdminAssign ? 'active' : '' }}">
                    <svg class="dt-nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                    Assign Target
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
                    <div class="alert alert-success" id="flash-success">{{ session('success') }}</div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('flash-success');
                    if (!el) return;
                    // Pesan "AI sedang memproses" jadi basi kalau penilaian AI ternyata
                    // sudah selesai (kartu pending tidak ada / kartu hasil sudah tampil).
                    if (/memproses/i.test(el.textContent) && !document.getElementById('ai-pending-card')) {
                        el.textContent = '✅ Laporan berhasil dikirim! Penilaian AI sudah tersedia.';
                    }
                });
                </script>
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

            @if ($isSuperAdmin)
                {{-- Overview --}}
                <a href="{{ route('admin.overview') }}" class="tab {{ $onOverview ? 'active' : '' }}">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                    </span>
                    Overview
                </a>

                {{-- Manajemen Pengguna --}}
                <a href="{{ route('admin.users.index') }}" class="tab {{ $onAdminUsers ? 'active' : '' }}">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    Pengguna
                </a>

                {{-- Assign Target --}}
                <a href="{{ route('admin.target-assignment.index') }}" class="tab {{ $onAdminAssign ? 'active' : '' }}">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                    </span>
                    Assign
                </a>

                {{-- Lainnya (buka sheet: Target, KPI, Report, Export, Profil) --}}
                <button type="button" class="tab {{ $onMore ? 'active' : '' }}" id="more-tab" aria-expanded="false" aria-controls="more-sheet">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>
                    </span>
                    Lainnya
                </button>
            @elseif ($isStaff)
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
                {{-- Overview (c_level & super_admin) --}}
                @if($isExecutive)
                <a href="{{ route('ceo.overview') }}" class="tab {{ $onOverview ? 'active' : '' }}">
                    <span class="glyph">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                    </span>
                    Overview
                </a>
                @endif

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

            {{-- Export & Profil — untuk super_admin dipindah ke sheet "Lainnya" agar tabbar tidak sesak --}}
            @unless ($isSuperAdmin)
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
            @endunless

        </nav>

        {{-- Sheet "Lainnya" — overflow tabbar mobile khusus super_admin --}}
        @if ($isSuperAdmin)
        <div id="more-sheet" class="more-sheet-backdrop" hidden>
            <div class="more-sheet" role="menu" aria-label="Menu lainnya">
                <div class="more-sheet-grip"></div>
                <a href="{{ $targetRoute }}" class="more-sheet-item {{ $onTargets ? 'active' : '' }}">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Target
                </a>
                <a href="{{ route('admin.kpi') }}" class="more-sheet-item {{ $onKpi ? 'active' : '' }}">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/></svg>
                    KPI
                </a>
                <a href="{{ route('workload-report.index') }}" class="more-sheet-item {{ $onWorkload ? 'active' : '' }}">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    Workload Report
                </a>
                @if($canExport)
                <a href="{{ route('export.index') }}" class="more-sheet-item {{ $onExport ? 'active' : '' }}">
                    <svg class="lucide" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export Laporan
                </a>
                @endif
                <a href="{{ route('profile.edit') }}" class="more-sheet-item {{ $onProfile ? 'active' : '' }}">
                    <svg class="lucide" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    Profil
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin:6px 0 0;">
                    @csrf
                    <button type="submit" class="more-sheet-item" style="width:100%;background:none;border:none;cursor:pointer;color:var(--danger);font:inherit;">
                        <svg class="lucide" viewBox="0 0 24 24"><path d="M16 17l5-5-5-5M21 12H9M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
        <script>
            (function () {
                var tab   = document.getElementById('more-tab');
                var sheet = document.getElementById('more-sheet');
                if (!tab || !sheet) return;
                function open()  { sheet.hidden = false; requestAnimationFrame(function () { sheet.classList.add('open'); }); tab.setAttribute('aria-expanded', 'true'); }
                function close() { sheet.classList.remove('open'); tab.setAttribute('aria-expanded', 'false'); setTimeout(function () { sheet.hidden = true; }, 200); }
                tab.addEventListener('click', function (e) { e.preventDefault(); sheet.hidden ? open() : close(); });
                sheet.addEventListener('click', function (e) { if (e.target === sheet) close(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !sheet.hidden) close(); });
            })();
        </script>
        @endif

    </div>{{-- end dt-main --}}

</div>{{-- end app-shell --}}

@include('partials.confirm-modal')
</body>
</html>
