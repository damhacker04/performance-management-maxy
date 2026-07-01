<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Putus sesi user yang sudah login namun akunnya dinonaktifkan (is_active=false)
 * setelah login. Tanpa ini, user yang dinonaktifkan HR masih bisa beraksi
 * selama sesinya belum berakhir.
 */
class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->is_active && ! $request->routeIs('logout')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Silakan hubungi HR.',
            ]);
        }

        return $next($request);
    }
}
