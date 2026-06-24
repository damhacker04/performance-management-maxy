<?php

namespace App\Http\Requests;

use App\Models\DailyTaskEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aturan validasi laporan harian — dipakai bersama oleh store() & update().
 *
 * update() butuh dua rule tambahan (revision_response, evidences.*.id); keduanya
 * `nullable` sehingga tidak berpengaruh saat dipakai store() (no-op).
 *
 * Otorisasi sengaja TIDAK di sini — tetap ditangani authorizeEdit() di controller
 * agar perilaku & timing-nya persis sama seperti sebelumnya.
 */
class DailyTaskEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekly_target_id' => 'nullable|exists:weekly_targets,id',
            'task_description' => 'required|string',
            'priority' => ['required', Rule::in(array_keys(DailyTaskEntry::PRIORITIES))],
            'duration_value' => 'required|integer|min:1|max:1440',
            'duration_unit' => ['required', Rule::in(['menit', 'jam'])],
            'status' => ['required', Rule::in(array_keys(DailyTaskEntry::STATUSES))],
            'notes' => 'required|string|min:5',
            'revision_response' => 'nullable|string|min:3',
            // Multi-evidence
            'evidences' => 'nullable|array|max:10',
            'evidences.*.id' => 'nullable|exists:daily_task_evidences,id',
            'evidences.*.type' => ['required', Rule::in(['link', 'file', 'image'])],
            'evidences.*.label' => 'required|string|max:100',
            'evidences.*.path_or_url' => 'nullable|array',
            'evidences.*.path_or_url.*' => 'nullable|string',
            'evidences.*.file' => 'nullable|array|max:10',
            'evidences.*.file.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    /**
     * Validasi tambahan: untuk evidence bertipe `link`, setiap nilai path_or_url
     * harus berupa URL http/https yang valid (mencegah string sembarang/SSRF/XSS
     * tersimpan di sumber). Tipe file/image punya jalur upload sendiri.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('evidences', []) as $i => $ev) {
                if (($ev['type'] ?? null) !== 'link') {
                    continue;
                }
                foreach ((array) ($ev['path_or_url'] ?? []) as $j => $url) {
                    if ($url === null || $url === '') {
                        continue;
                    }
                    if (! filter_var($url, FILTER_VALIDATE_URL)
                        || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                        $validator->errors()->add(
                            "evidences.$i.path_or_url.$j",
                            'Link bukti harus berupa URL http/https yang valid.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Catatan wajib diisi untuk semua status.',
            'notes.min' => 'Catatan minimal 5 karakter — jelaskan konteks/progress task.',
            'evidences.*.label.required' => 'Judul bukti wajib diisi.',
            'evidences.*.file.*.mimes' => 'File bukti harus berformat JPG, PNG, atau PDF.',
            'evidences.*.file.*.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}
