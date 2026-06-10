@php
    use App\Models\KpiWeightSetting;
    $defaultWeights = [
        'achievement'    => 40,
        'efficiency'     => 25,
        'contribution'   => 20,
        'problem_solving'=> 15,
    ];
@endphp

<x-app-layout>
<div class="page">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div style="flex:1;">
            <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">⚖️ Pengaturan Bobot KPI</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:3px 0 0;">Atur proporsi 4 dimensi penilaian AI. Total bobot harus 100%.</p>
        </div>
        <a href="{{ route('admin.ai.override-logs') }}"
           style="font-size:11px;padding:7px 12px;border:1px solid var(--bg-3);border-radius:8px;text-decoration:none;color:var(--fg-2);background:#fff;display:flex;align-items:center;gap:5px;">
            <svg style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Log Override
        </a>
    </div>

    @if(session('success'))
    <div style="background:#ECFDF5;border:1px solid #6EE7B7;border-radius:10px;padding:12px 14px;font-size:12px;color:#065F46;display:flex;align-items:center;gap:8px;">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:10px;padding:12px 14px;font-size:12px;color:#991B1B;">
        <strong>Ada kesalahan:</strong>
        <ul style="margin:6px 0 0 16px;padding:0;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Kartu Bobot Aktif --}}
    @if($current)
    <div class="m-card">
        <div class="overline-label" style="margin-bottom:12px;">🟢 Bobot Aktif Saat Ini</div>
        @php
            $dims = [
                ['label' => 'Pencapaian Target',   'w' => $current->weight_achievement,    'color' => '#3B82F6'],
                ['label' => 'Efisiensi Waktu',     'w' => $current->weight_efficiency,     'color' => '#10B981'],
                ['label' => 'Kontribusi Bisnis',   'w' => $current->weight_contribution,   'color' => '#F59E0B'],
                ['label' => 'Problem Solving',     'w' => $current->weight_problem_solving,'color' => '#8B5CF6'],
            ];
        @endphp
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($dims as $d)
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-size:12px;color:var(--fg-2);font-weight:600;">{{ $d['label'] }}</span>
                    <span style="font-size:13px;font-weight:700;color:{{ $d['color'] }};">{{ $d['w'] }}%</span>
                </div>
                <div style="height:8px;background:var(--bg-3);border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:{{ $d['w'] }}%;background:{{ $d['color'] }};border-radius:99px;"></div>
                </div>
            </div>
            @endforeach
        </div>
        <div style="margin-top:12px;font-size:11px;color:var(--fg-4);">
            Berlaku sejak {{ \Carbon\Carbon::parse($current->effective_from)->isoFormat('D MMMM YYYY') }}
            · Diset oleh {{ $current->setter?->name ?? 'System' }}
        </div>
    </div>
    @endif

    {{-- Form Bobot Baru --}}
    <div class="m-card">
        <div class="overline-label" style="margin-bottom:16px;">✏️ Set Bobot Baru</div>
        <form method="POST" action="{{ route('admin.kpi-settings.store') }}"
              style="display:flex;flex-direction:column;gap:14px;"
              x-data="kpiForm()" @submit.prevent="submitIfValid()">
            @csrf

            {{-- Tanggal Berlaku --}}
            <div class="field">
                <label for="effective_from">Berlaku Mulai Tanggal <span style="color:var(--danger);">*</span></label>
                <input type="date" id="effective_from" name="effective_from"
                       value="{{ old('effective_from', today()->toDateString()) }}"
                       class="m-input" required>
                <small style="font-size:11px;color:var(--fg-4);">AI akan menggunakan bobot ini untuk semua evaluasi setelah tanggal ini.</small>
            </div>

            {{-- 4 Slider Bobot --}}
            @php
                $sliders = [
                    ['key' => 'achievement',    'label' => 'Pencapaian Target',  'icon' => '🎯', 'color' => '#3B82F6', 'desc' => 'Seberapa jauh hasil kerja mencapai target yang ditetapkan.'],
                    ['key' => 'efficiency',     'label' => 'Efisiensi Waktu',    'icon' => '⚡', 'color' => '#10B981', 'desc' => 'Apakah durasi pengerjaan proporsional dengan kompleksitas tugas.'],
                    ['key' => 'contribution',   'label' => 'Kontribusi Bisnis',  'icon' => '📈', 'color' => '#F59E0B', 'desc' => 'Seberapa besar dampak pekerjaan terhadap tujuan perusahaan.'],
                    ['key' => 'problem_solving','label' => 'Problem Solving',    'icon' => '🔧', 'color' => '#8B5CF6', 'desc' => 'Kemampuan mengatasi hambatan dan mencari solusi kreatif.'],
                ];
            @endphp

            @foreach($sliders as $s)
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <label style="font-size:12px;font-weight:700;color:var(--fg-1);">
                        {{ $s['icon'] }} {{ $s['label'] }}
                    </label>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <span x-text="weights.{{ $s['key'] }}"
                              style="font-size:15px;font-weight:800;color:{{ $s['color'] }};min-width:32px;text-align:right;"></span>
                        <span style="font-size:12px;color:var(--fg-3);">%</span>
                    </div>
                </div>
                <input type="range"
                       name="weight_{{ $s['key'] }}"
                       id="weight_{{ $s['key'] }}"
                       min="5" max="60" step="1"
                       x-model.number="weights.{{ $s['key'] }}"
                       @input="recalc('{{ $s['key'] }}')"
                       value="{{ old('weight_' . $s['key'], $current?->{'weight_' . $s['key']} ?? $defaultWeights[$s['key']]) }}"
                       style="width:100%;accent-color:{{ $s['color'] }};cursor:pointer;">
                <p style="font-size:11px;color:var(--fg-4);margin:3px 0 0;">{{ $s['desc'] }}</p>
            </div>
            @endforeach

            {{-- Total Bar --}}
            <div style="border:1px solid var(--bg-3);border-radius:10px;padding:12px 14px;background:var(--bg-2);">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:12px;font-weight:700;color:var(--fg-1);">Total Bobot</span>
                    <span x-text="total + '%'"
                          :style="total === 100 ? 'color:#10B981;font-weight:800;font-size:15px;' : 'color:#EF4444;font-weight:800;font-size:15px;'"></span>
                </div>
                <div style="height:8px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                    <div :style="`height:100%;width:${Math.min(total,100)}%;border-radius:99px;transition:width .3s;background:${total===100?'#10B981':'#EF4444'}`"></div>
                </div>
                <p x-show="total !== 100" style="font-size:11px;color:#EF4444;margin:6px 0 0;">
                    ⚠️ Total harus tepat 100%. Selisih: <span x-text="total - 100"></span>%
                </p>
                <p x-show="total === 100" style="font-size:11px;color:#10B981;margin:6px 0 0;">
                    ✅ Total sudah tepat 100%
                </p>
            </div>

            <button type="submit"
                    :disabled="total !== 100"
                    :style="total === 100
                        ? 'background:#1E3A8A;color:#fff;padding:12px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;width:100%;'
                        : 'background:#E5E7EB;color:#9CA3AF;padding:12px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:not-allowed;width:100%;'">
                Simpan & Aktifkan Bobot Baru
            </button>
        </form>
    </div>

    {{-- Histori Perubahan --}}
    @if($history->count() > 1)
    <div class="m-card">
        <div class="overline-label" style="margin-bottom:12px;">📋 Histori Perubahan Bobot</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($history as $h)
            @if(!$h->is_active)
            <div style="border:1px solid var(--bg-3);border-radius:8px;padding:10px 12px;background:var(--bg-2);opacity:.7;">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--fg-3);">
                    <span>Diset oleh <strong>{{ $h->setter?->name ?? 'System' }}</strong></span>
                    <span>{{ \Carbon\Carbon::parse($h->effective_from)->isoFormat('D MMM YYYY') }}</span>
                </div>
                <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">
                    Pencapaian {{ $h->weight_achievement }}% · Efisiensi {{ $h->weight_efficiency }}%
                    · Kontribusi {{ $h->weight_contribution }}% · Problem Solving {{ $h->weight_problem_solving }}%
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
function kpiForm() {
    return {
        weights: {
            achievement:    {{ old('weight_achievement', $current?->weight_achievement ?? $defaultWeights['achievement']) }},
            efficiency:     {{ old('weight_efficiency',  $current?->weight_efficiency  ?? $defaultWeights['efficiency']) }},
            contribution:   {{ old('weight_contribution',$current?->weight_contribution?? $defaultWeights['contribution']) }},
            problem_solving:{{ old('weight_problem_solving',$current?->weight_problem_solving ?? $defaultWeights['problem_solving']) }},
        },
        get total() {
            return this.weights.achievement + this.weights.efficiency
                 + this.weights.contribution + this.weights.problem_solving;
        },
        recalc(changed) {
            // Auto-adjust lainnya agar total tetap 100 (opsional — bisa dimatikan)
        },
        submitIfValid() {
            if (this.total !== 100) return;
            this.$el.closest('form').submit();
        }
    }
}
</script>
</x-app-layout>
