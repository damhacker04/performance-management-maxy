{{--
    Partial: AI Score Card
    Ditampilkan di halaman show Daily Task (bawah konten utama).
    Variabel yang dibutuhkan: $entry (DailyTaskEntry dengan relasi aiEvaluation loaded)
--}}

@php
    $eval = $entry->aiEvaluation;
@endphp

@if($eval)
<div style="margin-top:16px; border:1px solid #E0E7FF; border-radius:12px; overflow:hidden; background:#fff;">

    {{-- Header Kartu AI --}}
    <div style="background:linear-gradient(135deg,#1E3A8A,#3B82F6); padding:12px 16px; display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:8px;">
            <svg style="width:16px;height:16px;color:#fff;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/><path d="M12 6v6l4 2"/>
            </svg>
            <span style="color:#fff;font-size:12px;font-weight:700;letter-spacing:.04em;">PENILAIAN AI (Groq)</span>
            @if($eval->is_overridden)
                <span style="background:rgba(255,255,255,.25);color:#fff;font-size:10px;padding:2px 8px;border-radius:10px;font-weight:600;">✏️ Dikoreksi Leader</span>
            @endif
        </div>
        <div style="color:#fff;font-size:22px;font-weight:800;">
            {{ number_format($eval->effective_score, 1) }}<span style="font-size:12px;font-weight:500;opacity:.8;">/10</span>
        </div>
    </div>

    {{-- Peringatan jika link restricted --}}
    @if($eval->link_status === 'restricted')
    <div style="background:#FEF2F2;border-bottom:1px solid #FCA5A5;padding:8px 16px;font-size:11px;color:#991B1B;display:flex;gap:6px;align-items:center;">
        ⚠️ <strong>Catatan:</strong> Link bukti kerja yang dilampirkan terkunci (Restricted). AI hanya menilai berdasarkan teks deskripsi.
    </div>
    @endif

    {{-- Skor 4 Dimensi --}}
    <div style="padding:14px 16px; display:flex; flex-direction:column; gap:10px;">
        @php
            $dimensions = [
                ['label' => 'Pencapaian Target',   'score' => $eval->score_achievement],
                ['label' => 'Efisiensi Waktu',     'score' => $eval->score_efficiency],
                ['label' => 'Kontribusi Bisnis',   'score' => $eval->score_contribution],
                ['label' => 'Problem Solving',     'score' => $eval->score_problem_solving],
            ];
        @endphp

        @foreach($dimensions as $dim)
        @php
            $pct   = ($dim['score'] / 10) * 100;
            $color = $dim['score'] >= 8 ? '#10B981' : ($dim['score'] >= 6 ? '#F59E0B' : '#EF4444');
        @endphp
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <span style="font-size:11px;color:#374151;font-weight:600;">{{ $dim['label'] }}</span>
                <span style="font-size:12px;font-weight:700;color:{{ $color }};">{{ number_format($dim['score'], 1) }}</span>
            </div>
            <div style="height:6px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $color }};border-radius:99px;transition:width .4s;"></div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Feedback AI --}}
    @if($eval->ai_feedback)
    <div style="padding:0 16px 14px;">
        <div style="background:#F8FAFF;border:1px solid #BFDBFE;border-radius:8px;padding:10px 12px;">
            <div style="font-size:10px;color:#1D4ED8;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">💡 Catatan AI</div>
            <p style="font-size:12px;color:#1E3A8A;margin:0;line-height:1.6;">{{ $eval->ai_feedback }}</p>
        </div>
    </div>
    @endif

    {{-- Log Override (jika ada) --}}
    @if($eval->is_overridden && $eval->latestOverride)
    @php $ov = $eval->latestOverride; @endphp
    <div style="padding:0 16px 14px;">
        <div style="background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px;padding:10px 12px;">
            <div style="font-size:10px;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">✏️ Koreksi oleh Leader</div>
            <div style="font-size:11px;color:#78350F;line-height:1.6;">
                <strong>{{ $ov->leader->name ?? '-' }}</strong> mengubah skor dari
                <strong>{{ number_format($ov->original_score, 1) }}</strong> menjadi
                <strong>{{ number_format($ov->new_score, 1) }}</strong>
                pada {{ $ov->overridden_at->isoFormat('D MMM YYYY, HH:mm') }}.
                <br><em>"{{ $ov->reason }}"</em>
            </div>
        </div>
    </div>
    @endif

    {{-- Tombol Override (hanya untuk Leader/C-Level/Admin) --}}
    @if(auth()->user()->isLeadership())
    <div style="padding:0 16px 14px;">
        <button type="button"
                onclick="document.getElementById('modal-override').style.display='flex'"
                style="width:100%;padding:9px;border:1px solid #D1D5DB;background:#fff;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;display:flex;align-items:center;justify-content:center;gap:6px;">
            <svg style="width:13px;height:13px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Koreksi Nilai AI
        </button>
    </div>
    @endif

</div>

{{-- Modal Override --}}
@if(auth()->user()->isLeadership())
<div id="modal-override"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:420px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);">

        {{-- Modal Header --}}
        <div style="background:#1E3A8A;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-size:14px;font-weight:700;">✏️ Koreksi Nilai AI</span>
            <button type="button" onclick="document.getElementById('modal-override').style.display='none'"
                    style="background:rgba(255,255,255,.2);border:none;color:#fff;width:26px;height:26px;border-radius:50%;cursor:pointer;font-size:14px;">✕</button>
        </div>

        {{-- Form Override --}}
        <form method="POST" action="{{ route('ai.evaluations.override.store', $eval) }}" style="padding:16px;display:flex;flex-direction:column;gap:14px;">
            @csrf
            <div style="background:#FEF9EC;border:1px solid #FCD34D;border-radius:8px;padding:10px 12px;font-size:11px;color:#92400E;">
                ⚠️ Koreksi ini akan dicatat dalam log audit manajemen. Pastikan alasannya jelas dan objektif.
            </div>

            <div>
                <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
                    Skor Baru <span style="color:#EF4444;">*</span>
                    <span style="font-weight:400;color:#6B7280;">(Skor AI saat ini: {{ number_format($eval->effective_score, 1) }})</span>
                </label>
                <input type="number"
                       name="new_score"
                       min="0" max="10" step="0.1"
                       value="{{ number_format($eval->effective_score, 1) }}"
                       class="m-input"
                       style="font-size:16px;font-weight:700;text-align:center;"
                       required>
            </div>

            <div>
                <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
                    Alasan Koreksi <span style="color:#EF4444;">*</span>
                </label>
                <textarea name="reason"
                          class="m-textarea"
                          placeholder="Jelaskan mengapa nilai AI perlu dikoreksi (min. 10 karakter)…"
                          minlength="10"
                          required
                          style="min-height:80px;font-size:13px;"></textarea>
                <small style="font-size:11px;color:#9CA3AF;">Minimal 10 karakter. Alasan ini akan terlihat oleh Super Admin.</small>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button"
                        onclick="document.getElementById('modal-override').style.display='none'"
                        style="flex:1;padding:10px;border:1px solid #D1D5DB;background:#fff;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;color:#374151;">
                    Batal
                </button>
                <button type="submit"
                        style="flex:1;padding:10px;background:#1E3A8A;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">
                    Simpan Koreksi
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@elseif(isset($evalPending) && $evalPending)
{{-- AI sedang memproses - dengan auto-refresh otomatis --}}
<div id="ai-pending-card" style="margin-top:16px;border:1px solid #E0E7FF;border-radius:12px;padding:14px 16px;background:#F8FAFF;display:flex;gap:10px;align-items:center;">
    <svg style="width:16px;height:16px;flex-shrink:0;color:#3B82F6;animation:ai-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
    <div>
        <div style="font-size:12px;font-weight:700;color:#1E3A8A;">🤖 AI sedang memproses penilaian…</div>
        <div style="font-size:11px;color:#3B82F6;margin-top:2px;">Hasil akan muncul otomatis dalam beberapa detik. Tidak perlu refresh manual.</div>
    </div>
</div>
<style>@keyframes ai-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }</style>
<script>
(function() {
    // Auto-refresh setiap 4 detik selama AI masih memproses
    var interval = setInterval(function() {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(res) { return res.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc    = parser.parseFromString(html, 'text/html');
                // Cek apakah di halaman baru sudah TIDAK ada spinner lagi
                var stillPending = doc.getElementById('ai-pending-card');
                if (!stillPending) {
                    // Skor sudah ada → reload halaman untuk tampilkan kartu skor
                    clearInterval(interval);
                    window.location.reload();
                }
            })
            .catch(function() { /* abaikan error jaringan sementara */ });
    }, 4000); // Cek setiap 4 detik

    // Hentikan polling setelah 5 menit (agar tidak loop selamanya)
    setTimeout(function() { clearInterval(interval); }, 300000);
})();
</script>
@endif
