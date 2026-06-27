<x-guest-layout>
    <div style="margin-bottom:20px;">
        <h2 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0 0 6px;">Buat Password Akun</h2>
        <p style="font-size:13px;color:var(--fg-2);margin:0;line-height:1.5;">
            Kamu masuk lewat Google dan belum punya password. Buat password sekarang supaya
            ke depannya bisa login dengan Google <strong>atau</strong> email &amp; password.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:16px;background-color:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;font-size:14px;border:1px solid #f87171;">
            <ul style="margin:0;padding-left:16px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.setup.store') }}" style="display:flex;flex-direction:column;gap:16px;">
        @csrf

        <!-- Password -->
        <div class="field">
            <label for="password">Password Baru</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                   class="m-input" placeholder="••••••••">
        </div>

        <!-- Confirm Password -->
        <div class="field">
            <label for="password_confirmation">Konfirmasi Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="m-input" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
            Simpan Password
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;text-align:center;">
        @csrf
        <button type="submit" style="background:none;border:none;color:var(--fg-3);font-size:13px;cursor:pointer;text-decoration:underline;">
            Keluar
        </button>
    </form>
</x-guest-layout>
