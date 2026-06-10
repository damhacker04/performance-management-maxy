{{--
    Partial: Gap Analysis Card
    Ditampilkan di halaman show Weekly Target atau Monthly Target
    jika target tersebut memiliki Gap Analysis Report dari AI.

    Variabel yang dibutuhkan: $gapReport (GapAnalysisReport model)
    Variabel opsional: $isMonthly (bool) — untuk membedakan label Weekly vs Monthly
--}}

@php $isMonthly = $isMonthly ?? false; @endphp

<div style="margin-top:16px;border:1px solid #FDE68A;border-radius:12px;overflow:hidden;background:#fff;">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg, #92400E, #D97706); padding:12px 16px; display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:8px;">
            <svg style="width:16px;height:16px;color:#fff;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v3m0 4h.01"/>
            </svg>
            <span style="color:#fff;font-size:12px;font-weight:700;letter-spacing:.04em;">
                🤖 AI GAP ANALYSIS — {{ $isMonthly ? 'LAPORAN STRATEGIS (C-Level)' : 'INVESTIGASI MINGGUAN' }}
            </span>
        </div>
        <span style="background:rgba(255,255,255,.25);color:#fff;font-size:10px;padding:2px 8px;border-radius:10px;font-weight:600;">
            {{ $gapReport->tasks_analyzed }} {{ $isMonthly ? 'minggu' : 'tugas' }} dianalisis
        </span>
    </div>

    {{-- Badge Tipe Akar Masalah --}}
    <div style="padding:12px 16px 0;">
        @php
            $chipColors = [
                'internal' => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'border' => '#FCA5A5'],
                'external' => ['bg' => '#FFFBEB', 'text' => '#92400E', 'border' => '#FCD34D'],
                'mixed'    => ['bg' => '#F3F4F6', 'text' => '#374151', 'border' => '#D1D5DB'],
            ];
            $chip = $chipColors[$gapReport->root_cause_type] ?? $chipColors['mixed'];
        @endphp
        <div style="display:inline-flex;align-items:center;gap:6px;background:{{ $chip['bg'] }};color:{{ $chip['text'] }};border:1px solid {{ $chip['border'] }};padding:5px 12px;border-radius:99px;font-size:11px;font-weight:700;">
            @if($gapReport->root_cause_type === 'internal') ⚠️
            @elseif($gapReport->root_cause_type === 'external') 🔧
            @else 🔄
            @endif
            {{ $gapReport->root_cause_label }}
        </div>
    </div>

    {{-- Narasi Investigasi AI --}}
    <div style="padding:12px 16px;">
        <div style="font-size:10px;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
            🔎 {{ $isMonthly ? 'Benang Merah Kegagalan (Structural)' : 'Ringkasan Investigasi' }}
        </div>
        <p style="font-size:13px;color:#1F2937;margin:0;line-height:1.7;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px;">
            {{ $gapReport->narrative }}
        </p>
    </div>

    {{-- Rekomendasi AI --}}
    @if($gapReport->recommendation)
    <div style="padding:0 16px 14px;">
        <div style="font-size:10px;color:#1D4ED8;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
            💡 {{ $isMonthly ? 'Rekomendasi Strategis untuk Manajemen' : 'Rekomendasi untuk Leader' }}
        </div>
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px;font-size:12px;color:#1E3A8A;line-height:1.7;">
            {{ $gapReport->recommendation }}
        </div>
    </div>
    @endif

    {{-- Footer: kapan dianalisis --}}
    <div style="padding:8px 16px 12px;border-top:1px dashed #FDE68A;">
        <span style="font-size:10px;color:#D97706;">
            Dianalisis oleh AI pada {{ $gapReport->generated_at->isoFormat('D MMMM YYYY, HH:mm') }}
        </span>
    </div>
</div>
