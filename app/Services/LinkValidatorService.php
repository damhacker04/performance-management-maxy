<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LinkValidatorService — Mengecek apakah sebuah link Google Workspace
 * dapat diakses secara publik (tanpa perlu login Google).
 *
 * Digunakan untuk validasi REAL-TIME di frontend (via AJAX)
 * sebelum staf menekan tombol Submit.
 */
class LinkValidatorService
{
    /**
     * Cek apakah link dapat diakses publik.
     *
     * @return array ['status' => 'public'|'restricted'|'invalid', 'message' => string]
     */
    public function check(string $url): array
    {
        // Validasi format URL dasar
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'status'  => 'invalid',
                'message' => 'Format URL tidak valid.',
            ];
        }

        // Hanya proses link Google atau Notion (link lain dianggap publik)
        if (!$this->isGoogleWorkspaceUrl($url) && !$this->isNotionUrl($url)) {
            return [
                'status'  => 'public',
                'message' => 'Link dapat diakses.',
            ];
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            // Google redirect ke accounts.google.com jika restricted
            $finalUrl = $response->effectiveUri() ?? '';

            if ($this->isRedirectedToLogin((string) $finalUrl)) {
                return [
                    'status'  => 'restricted',
                    'message' => 'Link masih terkunci (Restricted). Ubah akses file menjadi "Anyone with the link" agar AI bisa membaca isinya.',
                ];
            }

            if ($response->successful()) {
                return [
                    'status'  => 'public',
                    'message' => 'Link dapat diakses publik.',
                ];
            }

            return [
                'status'  => 'restricted',
                'message' => 'Link tidak bisa diakses (kemungkinan terkunci atau tidak ditemukan).',
            ];

        } catch (\Exception $e) {
            Log::warning('LinkValidatorService: gagal cek link', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            // Jika timeout/error network, anggap restricted agar sistem tidak buta
            return [
                'status'  => 'restricted',
                'message' => 'Tidak dapat memverifikasi link. Pastikan link dapat diakses publik.',
            ];
        }
    }

    private function isGoogleWorkspaceUrl(string $url): bool
    {
        return str_contains($url, 'docs.google.com')
            || str_contains($url, 'drive.google.com')
            || str_contains($url, 'sheets.google.com')
            || str_contains($url, 'forms.google.com');
    }

    private function isNotionUrl(string $url): bool
    {
        return str_contains($url, 'notion.so') || str_contains($url, 'notion.site');
    }

    private function isRedirectedToLogin(string $url): bool
    {
        return str_contains($url, 'accounts.google.com')
            || str_contains($url, 'login')
            || str_contains($url, 'signin');
    }
}
