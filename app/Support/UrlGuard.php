<?php

namespace App\Support;

/**
 * Guard anti-SSRF untuk URL yang akan di-fetch server-side oleh
 * LinkValidatorService & LinkExtractorService.
 *
 * Pertahanan utama: host harus dikenali via parse_url (BUKAN str_contains pada
 * seluruh string URL) dan cocok dengan allowlist domain tepercaya. Ini menutup
 * trik seperti `http://127.0.0.1/docs.google.com` atau
 * `http://169.254.169.254/?docs.google.com` yang dulu lolos pengecekan substring.
 */
class UrlGuard
{
    /**
     * Domain yang boleh di-fetch server-side. Cocok bila host === suffix
     * atau merupakan subdomain (".{suffix}").
     */
    public const ALLOWED_HOST_SUFFIXES = [
        'docs.google.com',
        'drive.google.com',
        'sheets.google.com',
        'forms.google.com',
        'accounts.google.com', // tujuan redirect saat link Google restricted
        'notion.so',
        'notion.site',
    ];

    /** Ambil host (lowercase) dari URL, atau null bila tidak valid. */
    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host ? strtolower($host) : null;
    }

    /** Apakah host URL termasuk domain Google Workspace tepercaya? */
    public static function isGoogleHost(string $url): bool
    {
        return self::hostMatches($url, [
            'docs.google.com',
            'drive.google.com',
            'sheets.google.com',
            'forms.google.com',
        ]);
    }

    /** Apakah host URL termasuk domain Notion tepercaya? */
    public static function isNotionHost(string $url): bool
    {
        return self::hostMatches($url, ['notion.so', 'notion.site']);
    }

    /** Apakah host termasuk allowlist fetch (Google/Notion)? */
    public static function isAllowedHost(string $url): bool
    {
        return self::hostMatches($url, self::ALLOWED_HOST_SUFFIXES);
    }

    /**
     * Apakah URL aman untuk di-fetch server-side?
     * - Skema harus http/https.
     * - Host harus ada di allowlist (sehingga IP literal & host internal ditolak).
     */
    public static function isSafeToFetch(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return self::isAllowedHost($url);
    }

    /** Apakah IP merupakan IP publik (bukan privat/loopback/reserved)? */
    public static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private static function hostMatches(string $url, array $suffixes): bool
    {
        $host = self::host($url);
        if (! $host) {
            return false;
        }

        // Host berupa IP literal tidak pernah cocok allowlist domain → ditolak.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach ($suffixes as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }
}
