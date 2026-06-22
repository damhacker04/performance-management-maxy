<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsSet
{
    /**
     * Pastikan setiap user yang sudah login memiliki password.
     *
     * User yang pertama kali masuk lewat Google belum punya password di
     * tabel users. Mereka wajib membuat password dulu sebelum bisa
     * mengakses halaman lain, agar nantinya bisa login lewat Google
     * maupun email + password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Hanya berlaku untuk user yang sudah login & belum punya password.
        if ($user && empty($user->password)) {
            // Jangan blokir halaman setup password itu sendiri & logout,
            // supaya tidak terjadi redirect loop.
            if (! $request->routeIs('password.setup', 'logout')) {
                return redirect()->route('password.setup');
            }
        }

        return $next($request);
    }
}
