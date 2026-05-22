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
<body style="margin:0;padding:0;background:var(--maxy-navy);min-height:100dvh;">

    {{-- ─────────────────────────────────────────────
         MOBILE: layout lama (scroll, top branding + card bawah)
         Disembunyikan di desktop via CSS
    ───────────────────────────────────────────── --}}
    <div class="login-screen login-mobile" style="max-width:var(--app-max);margin:0 auto;">
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

    {{-- ─────────────────────────────────────────────
         DESKTOP: centered two-panel layout
         Disembunyikan di mobile via CSS
    ───────────────────────────────────────────── --}}
    <div class="login-desktop">

        {{-- Panel Kiri: Branding --}}
        <div class="login-desktop-brand">
            <div style="display:flex;flex-direction:column;align-items:flex-start;gap:20px;max-width:380px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <img src="{{ asset('images/m-logo.png') }}" alt="Maxy"
                         style="width:52px;height:52px;border-radius:14px;object-fit:contain;" />
                    <span style="font-size:18px;font-weight:800;color:#fff;letter-spacing:-.3px;">Maxy Academy</span>
                </div>
                <h1 style="font-size:32px;font-weight:800;color:#fff;line-height:1.2;margin:0;">
                    Performance<br>Management
                </h1>
                <p style="font-size:15px;color:rgba(255,255,255,0.65);line-height:1.6;margin:0;">
                    Pantau target dan kinerja tim Anda dalam satu platform yang simpel dan efisien.
                </p>
                {{-- Decorative dots --}}
                <div style="display:flex;gap:6px;margin-top:8px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--maxy-amber);"></div>
                    <div style="width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.3);"></div>
                    <div style="width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.15);"></div>
                </div>
            </div>
        </div>

        {{-- Panel Kanan: Form --}}
        <div class="login-desktop-form">
            <div class="login-desktop-card">
                <div style="margin-bottom:28px;">
                    <h2 style="font-size:22px;font-weight:800;color:var(--fg-1);margin:0 0 6px;">Masuk ke akun Anda</h2>
                    <p style="font-size:13px;color:var(--fg-3);margin:0;">Gunakan email dan kata sandi yang terdaftar.</p>
                </div>
                {{ $slot }}
            </div>
        </div>

    </div>

    <style>
        /* ── Mobile: tampilkan login-mobile, sembunyikan login-desktop ── */
        .login-desktop { display: none; }

        /* ── Desktop (≥ 1024px): tampilkan login-desktop, sembunyikan login-mobile ── */
        @media (min-width: 1024px) {
            .login-mobile { display: none !important; }
            .login-desktop {
                display: flex;
                min-height: 100dvh;
                align-items: center;
                justify-content: center;
            }
            .login-desktop-brand {
                flex: 1;
                max-width: 520px;
                padding: 60px 56px;
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }
            .login-desktop-form {
                flex: 1;
                max-width: 520px;
                padding: 60px 56px;
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }
            .login-desktop-card {
                width: 100%;
                max-width: 400px;
                background: #fff;
                border-radius: 20px;
                padding: 36px 32px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.18),
                            0 4px 16px rgba(0,0,0,0.10);
            }
            /* Sembunyikan footer di dalam form desktop karena sudah di desktop card */
            .login-desktop-card p[style*="text-align:center"] {
                color: var(--fg-4) !important;
                margin-top: 16px !important;
            }
        }
    </style>

</body>
</html>
