<x-guest-layout>
    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom:4px;">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:16px;">
        @csrf

        <!-- Email -->
        <div class="field">
            <label for="email">Email</label>
            <input
                id="email" type="email" name="email"
                value="{{ old('email') }}"
                class="m-input {{ $errors->has('email') ? 'err' : '' }}"
                placeholder="anda@maxy.id"
                required autofocus autocomplete="username"
            />
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </div>

        <!-- Password -->
        <div class="field">
            <label for="password">Kata sandi</label>
            <input
                id="password" type="password" name="password"
                class="m-input {{ $errors->has('password') ? 'err' : '' }}"
                placeholder="Kata sandi"
                required autocomplete="current-password"
            />
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </div>

        <!-- Remember + forgot -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin:-4px 0 4px;">
            <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;color:var(--fg-2);cursor:pointer;">
                <input type="checkbox" name="remember" style="accent-color:var(--maxy-navy);width:16px;height:16px;" />
                Ingat saya
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="font-size:13px;color:var(--maxy-navy);font-weight:500;">Lupa sandi?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-block">Masuk</button>

        <p style="text-align:center;font-size:12px;color:var(--fg-3);margin-top:4px;">
            Maxy Academy · v1.0
        </p>
    </form>
</x-guest-layout>
