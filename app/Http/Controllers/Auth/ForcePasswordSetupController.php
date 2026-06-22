<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordSetupController extends Controller
{
    /**
     * Tampilkan halaman pembuatan password untuk user yang belum punya.
     */
    public function create(Request $request): View|RedirectResponse
    {
        // Kalau user ternyata sudah punya password, tidak perlu di sini.
        if (! empty($request->user()->password)) {
            return redirect()->route('dashboard');
        }

        return view('auth.setup-password');
    }

    /**
     * Simpan password baru milik user yang login lewat Google.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return redirect()->route('dashboard')
            ->with('status', 'Password berhasil dibuat. Mulai sekarang kamu bisa login dengan Google atau email & password.');
    }
}
