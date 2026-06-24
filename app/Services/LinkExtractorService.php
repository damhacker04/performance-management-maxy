<?php

namespace App\Services;

use App\Support\UrlGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LinkExtractorService — Mengunduh dan mengekstrak konten teks
 * dari link Google Docs/Sheets yang berstatus Publik.
 *
 * Digunakan oleh EvaluateDailyTaskJob (background) sebelum
 * mengirim data ke GeminiService untuk dinilai.
 */
class LinkExtractorService
{
    // Batas karakter yang dikirim ke Gemini (agar tidak terlalu mahal/lama)
    private int $maxChars = 8000;

    /**
     * Ekstrak teks dari URL publik.
     * Mengembalikan null jika gagal atau URL bukan Google Workspace.
     */
    public function extract(string $url): ?string
    {
        if (! $url) {
            return null;
        }

        // Anti-SSRF: hanya host Google/Notion yang di-allowlist boleh di-fetch.
        if (! UrlGuard::isSafeToFetch($url)) {
            Log::warning('LinkExtractorService: URL di luar allowlist, dilewati', ['url' => $url]);

            return null;
        }

        if ($this->isGoogleDocsUrl($url)) {
            return $this->extractFromGoogleDocs($url);
        }

        if ($this->isGoogleSheetsUrl($url)) {
            return $this->extractFromGoogleSheets($url);
        }

        // Untuk URL lain (Notion, dll), coba ekstrak HTML biasa
        return $this->extractFromGenericUrl($url);
    }

    // ── Google Docs ──────────────────────────────────────────────────────────

    private function extractFromGoogleDocs(string $url): ?string
    {
        // Ekstrak ID dokumen dari URL
        preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches);
        $docId = $matches[1] ?? null;

        if (! $docId) {
            return null;
        }

        // Export sebagai plain text (tidak perlu login jika publik)
        $exportUrl = "https://docs.google.com/document/d/{$docId}/export?format=txt";

        try {
            $response = Http::timeout(15)->get($exportUrl);
            if (! $response->successful()) {
                return null;
            }

            $text = $response->body();

            return $this->truncate($text);

        } catch (\Exception $e) {
            Log::warning('LinkExtractorService: gagal ekstrak Google Docs', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ── Google Sheets ────────────────────────────────────────────────────────

    private function extractFromGoogleSheets(string $url): ?string
    {
        preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches);
        $sheetId = $matches[1] ?? null;

        if (! $sheetId) {
            return null;
        }

        // Export sebagai CSV (mudah dibaca AI)
        $exportUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";

        try {
            $response = Http::timeout(15)->get($exportUrl);
            if (! $response->successful()) {
                return null;
            }

            // Konversi CSV ke teks yang lebih mudah dibaca AI
            $csv = $response->body();
            $text = $this->csvToReadableText($csv);

            return $this->truncate($text);

        } catch (\Exception $e) {
            Log::warning('LinkExtractorService: gagal ekstrak Google Sheets', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ── Generic URL (Notion, dll) ────────────────────────────────────────────

    private function extractFromGenericUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            // Strip HTML tags, ambil teks bersih
            $text = strip_tags($response->body());
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            return $this->truncate($text);

        } catch (\Exception $e) {
            Log::warning('LinkExtractorService: gagal ekstrak URL generik', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function isGoogleDocsUrl(string $url): bool
    {
        return str_contains($url, 'docs.google.com/document');
    }

    private function isGoogleSheetsUrl(string $url): bool
    {
        return str_contains($url, 'docs.google.com/spreadsheets')
            || str_contains($url, 'sheets.google.com');
    }

    private function csvToReadableText(string $csv): string
    {
        $lines = explode("\n", $csv);
        $readable = [];
        foreach (array_slice($lines, 0, 100) as $line) { // Max 100 baris
            $cols = str_getcsv($line);
            $readable[] = implode(' | ', array_filter($cols));
        }

        return implode("\n", array_filter($readable));
    }

    private function truncate(string $text): string
    {
        if (strlen($text) <= $this->maxChars) {
            return $text;
        }

        return substr($text, 0, $this->maxChars).'...[dipotong karena terlalu panjang]';
    }
}
