<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tambahkan header keamanan standar pada setiap response.
 *
 * Catatan: Content-Security-Policy sengaja TIDAK dipasang di sini agar tidak
 * memblokir inline script/style yang masih dipakai frontend existing. CSP bisa
 * ditambahkan terpisah setelah audit frontend (di luar scope backend saat ini).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        if (! $headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }
        if (! $headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }
        if (! $headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        if (! $headers->has('X-XSS-Protection')) {
            $headers->set('X-XSS-Protection', '0');
        }

        return $response;
    }
}
