<?php

if (!function_exists('ai_enabled')) {
    /**
     * Cek apakah fitur AI Evaluation aktif.
     *
     * AI dianggap aktif jika GROQ_API_KEY sudah di-set di .env.
     * Di production (Railway), cukup tidak set GROQ_API_KEY → AI otomatis mati,
     * tanpa perlu mengubah kode sama sekali.
     *
     * Penggunaan:
     *   @if(ai_enabled())  ... tampilkan UI AI ...  @endif
     *   if (ai_enabled()) { EvaluateDailyTaskJob::dispatch(...); }
     */
    function ai_enabled(): bool
    {
        return !empty(config('services.groq.api_key'));
    }
}
