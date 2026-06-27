<?php

namespace App\Http\Controllers;

use App\Models\AiEvaluation;
use App\Models\LeaderOverride;
use App\Services\LinkValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AiEvaluationController — Menangani:
 * 1. Validasi link bukti kerja secara real-time (AJAX)
 * 2. Override/Veto nilai AI oleh Leader
 * 3. Log histori override untuk audit manajemen
 */
class AiEvaluationController extends Controller
{
    /**
     * [AJAX] Validasi status link sebelum staf submit form.
     * Dipanggil secara real-time dari frontend saat staf paste URL.
     *
     * POST /ai/validate-link
     */
    public function validateLink(Request $request)
    {
        $request->validate(['url' => 'required|url|max:2048']);

        $validator = app(LinkValidatorService::class);
        $result = $validator->check($request->url);

        return response()->json($result);
    }

    /**
     * [Leader/C-Level] Tampilkan form override nilai AI.
     * Muncul sebagai modal di halaman detail Daily Task.
     *
     * GET /ai/evaluations/{evaluation}/override
     */
    public function showOverrideForm(AiEvaluation $evaluation)
    {
        $this->authorizeOverride($evaluation);

        $evaluation->load(['dailyTaskEntry.user', 'latestOverride']);

        return view('ai.override-form', compact('evaluation'));
    }

    /**
     * [Leader/C-Level] Simpan override nilai AI.
     * Wajib mengisi alasan (untuk audit trail).
     *
     * POST /ai/evaluations/{evaluation}/override
     */
    public function storeOverride(Request $request, AiEvaluation $evaluation)
    {
        $this->authorizeOverride($evaluation);

        $request->validate([
            'new_score' => 'required|numeric|min:0|max:10',
            'reason' => 'required|string|min:10|max:500',
        ], [
            'new_score.required' => 'Skor baru wajib diisi.',
            'new_score.min' => 'Skor minimal adalah 0.',
            'new_score.max' => 'Skor maksimal adalah 10.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Alasan minimal 10 karakter agar tercatat dengan jelas.',
        ]);

        // Simpan log override (audit trail)
        LeaderOverride::create([
            'ai_evaluation_id' => $evaluation->id,
            'overridden_by' => Auth::id(),
            'original_score' => $evaluation->final_score,
            'new_score' => $request->new_score,
            'reason' => $request->reason,
            'overridden_at' => now(),
        ]);

        // Tandai evaluasi sebagai sudah di-override
        $evaluation->update(['is_overridden' => true]);

        return redirect()->back()->with('success', 'Nilai AI berhasil dikoreksi dan tercatat dalam log audit.');
    }

    /**
     * [Super Admin / HR] Halaman log semua histori override.
     * Untuk monitoring: siapa Leader yang sering menganulir AI?
     *
     * GET /admin/ai/override-logs
     */
    public function overrideLogs(Request $request)
    {
        $logs = LeaderOverride::with([
            'leader',
            'aiEvaluation.dailyTaskEntry.user',
            'aiEvaluation.dailyTaskEntry.weeklyTarget',
        ])
            ->latest('overridden_at')
            ->paginate(25);

        return view('ai.override-logs', compact('logs'));
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    private function authorizeOverride(AiEvaluation $evaluation): void
    {
        $user = Auth::user();
        if (! $user->isLeadership()) {
            abort(403, 'Hanya Leader, C-Level, atau Admin yang dapat mengubah nilai AI.');
        }

        // Leader hanya boleh mengoreksi evaluasi staf di departemennya sendiri.
        // C-Level & Super Admin lintas departemen.
        if ($user->role === 'leader') {
            $staffDept = $evaluation->dailyTaskEntry?->user?->department
                ?? $evaluation->loadMissing('dailyTaskEntry.user')->dailyTaskEntry?->user?->department;

            if ($staffDept !== $user->department) {
                abort(403, 'Anda hanya dapat mengoreksi nilai staf dari departemen Anda.');
            }
        }
    }
}
