<x-guest-layout>
    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom:4px;">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:16px;background-color:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;font-size:14px;border:1px solid #f87171;">
            <ul style="margin:0;padding-left:16px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:flex;flex-direction:column;gap:16px;">
        <p style="color:var(--fg-2);font-size:14px;margin-bottom:8px;text-align:center;">Silakan masuk menggunakan email terdaftar.</p>

        <!-- Form Login Manual -->
        <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" style="display:block;font-size:13px;font-weight:600;color:var(--fg-1);margin-bottom:6px;">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;"
                       placeholder="nama@email.com">
            </div>

            <!-- Password -->
            <div>
                <label for="password" style="display:block;font-size:13px;font-weight:600;color:var(--fg-1);margin-bottom:6px;">Password</label>
                <input id="password" type="password" name="password" required
                       style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;"
                       placeholder="••••••••">
            </div>

            <!-- Remember Me & Lupa Password -->
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <label for="remember_me" style="display:inline-flex;align-items:center;cursor:pointer;">
                    <input id="remember_me" type="checkbox" name="remember" style="border-radius:4px;border-color:#d1d5db;color:#1D4ED8;">
                    <span style="margin-left:8px;font-size:13px;color:var(--fg-2);">Ingat saya</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size:13px;color:#1D4ED8;text-decoration:none;font-weight:500;">
                        Lupa Password?
                    </a>
                @endif
            </div>

            <button type="submit" class="btn" style="background:#1D4ED8;color:#fff;width:100%;padding:12px;font-size:14px;border-radius:8px;margin-top:8px;">
                MASUK
            </button>
        </form>

        <!-- Divider -->
        <div style="display:flex;align-items:center;gap:12px;margin:8px 0;">
            <div style="flex:1;height:1px;background:#e2e8f0;"></div>
            <span style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">ATAU</span>
            <div style="flex:1;height:1px;background:#e2e8f0;"></div>
        </div>
        
        <a href="{{ route('google.login') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:12px;background-color:#ffffff;color:#374151;border:1px solid #d1d5db;border-radius:8px;padding:12px 16px;font-weight:500;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.05);transition:background-color 0.2s;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Masuk dengan Google
        </a>


        <p style="text-align:center;font-size:12px;color:var(--fg-3);margin-top:24px;">
            Maxy Academy · v1.0
        </p>
    </div>
</x-guest-layout>
