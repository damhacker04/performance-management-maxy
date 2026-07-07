<?php

namespace App\Http\Controllers\Concerns;

trait RedirectsToSafeBack
{
    /**
     * Kembalikan URL `back` hanya jika aman (path relatif ATAU host sama dengan
     * host aplikasi). Selain itu pakai route fallback. Mencegah open redirect
     * lewat parameter `back` yang dikontrol user.
     */
    protected function safeBack(?string $back, string $fallbackRoute): string
    {
        return $this->safeBackOrNull($back) ?? route($fallbackRoute);
    }

    /**
     * Sama dengan safeBack() tapi kembalikan null (bukan route fallback) bila
     * `back` tidak ada / tidak aman — berguna saat fallback-nya URL dinamis.
     */
    protected function safeBackOrNull(?string $back): ?string
    {
        $back = $back !== null && $back !== '' ? urldecode($back) : null;

        if ($back === null) {
            return null;
        }

        $host = parse_url($back, PHP_URL_HOST);

        if ($host === null) {
            if (str_starts_with($back, '/') && ! str_starts_with($back, '//')) {
                return $back;
            }

            return null;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($appHost !== null && strcasecmp($host, $appHost) === 0) {
            return $back;
        }

        return null;
    }
}
