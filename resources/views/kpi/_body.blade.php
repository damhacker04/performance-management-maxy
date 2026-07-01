{{--
    Partial isi halaman KPI — dipakai bersama oleh:
      • resources/views/kpi.blade.php        (/kpi, CEO/Leader)
      • resources/views/admin/kpi.blade.php  (/admin/kpi, Admin HR)

    Satu-satunya perbedaan antar halaman adalah nama route untuk self-link
    (form filter & tombol reset), dikirim lewat variabel $kpiRouteName.
    Semua fitur (termasuk tombol AI auto-detect) hidup di SATU tempat ini
    agar kedua halaman tidak pernah "drift" lagi.

    Butuh dari controller: $kpiByDept  (dan $groupedStaffs bila ada).
--}}
@php $kpiRouteName ??= 'kpi'; @endphp
@php
    $canManage = auth()->user()->isExecutive() || auth()->user()->is_management;
    $deptColors = [
        'sales'=>'#1B4FD8','marketing'=>'#7C3AED','operational'=>'#0E7490',
        'hr'=>'#065F46','finance'=>'#9A3412','product_it'=>'#1D4ED8',
        'ga'=>'#047857','creative'=>'#6D28D9','customer_support'=>'#C2410C',
    ];
    $monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];

    // ── Filter periode (frontend-only, baca dari query string) ──
    $selMonth = (int) request()->query('month', 0);
    $selYear  = (int) request()->query('year', 0);

    // Hanya tampilkan KPI L2 yang cocok dengan periode terpilih (jika ada)
    $visibleByDept = $kpiByDept->map(function ($kpis) use ($selMonth, $selYear) {
        return $kpis->filter(function ($k) use ($selMonth, $selYear) {
            if ($selMonth && (int) $k->month !== $selMonth) return false;
            if ($selYear  && (int) $k->year  !== $selYear)  return false;
            return true;
        });
    })->filter(fn ($kpis) => $kpis->isNotEmpty());

    // Helper warna status realisasi (selaras dgn accessor status_color di KpiActual)
    $statusColor = [
        'green'  => 'var(--success)',
        'yellow' => 'var(--warning)',
        'red'    => 'var(--danger)',
        'gray'   => 'var(--fg-4)',
    ];
@endphp

<style>
/* ── Dept Section ─────────────────────────────── */
.dept-section { margin-bottom: 24px; }
.dept-label   { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.dept-badge   {
    display:inline-flex; align-items:center; gap:6px;
    font-size:11px; font-weight:700; letter-spacing:.06em;
    text-transform:uppercase; color:var(--fg-3);
}
.dept-dot { width:8px; height:8px; border-radius:50%; }

/* ── KPI L2 (accordion) ───────────────────────── */
.kpi-l2 {
    background:#fff; border:1.5px solid var(--neutral-200);
    border-radius:var(--r-lg); box-shadow:var(--shadow-sm);
    margin-bottom:10px; overflow:hidden;
    transition:border-color .2s, box-shadow .2s;
}
.kpi-l2:hover { border-color:var(--maxy-navy); box-shadow:var(--shadow-md); }

.kpi-l2-head {
    width:100%; display:flex; align-items:flex-start; gap:14px;
    padding:14px 16px; background:none; border:none; cursor:pointer;
    text-align:left; font:inherit;
}
.kpi-icon {
    width:40px; height:40px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.kpi-info { flex:1; min-width:0; }
.kpi-name { font-size:14px; font-weight:700; color:var(--fg-1); margin-bottom:3px; }
.kpi-meta { font-size:12px; color:var(--fg-3); }
.kpi-target { font-weight:700; color:var(--maxy-navy); }

.kpi-l2-right { display:flex; align-items:center; gap:10px; flex-shrink:0; align-self:center; }
.staff-count {
    font-size:11px; font-weight:700; color:var(--fg-3);
    background:var(--neutral-100); padding:3px 9px; border-radius:20px; white-space:nowrap;
}
.chev { color:var(--fg-4); transition:transform .2s; }
.kpi-l2-head[aria-expanded="true"] .chev { transform:rotate(180deg); }

/* ── Mini bars (alokasi + realisasi) ──────────── */
.kpi-bars     { display:flex; flex-direction:column; gap:6px; margin-top:11px; }
.kpi-bar-row  { display:flex; align-items:center; gap:8px; }
.kpi-bar-lbl  { font-size:11px; color:var(--fg-3); min-width:64px; }
.kpi-bar-track{ flex:1; height:6px; background:var(--neutral-100); border-radius:99px; overflow:hidden; }
.kpi-bar-fill { height:100%; border-radius:99px; }
.kpi-bar-val  { font-size:11px; font-weight:700; min-width:118px; text-align:right; color:var(--fg-2); }

/* ── L3 staff rows (expanded body) ────────────── */
.kpi-l2-body { padding:2px 16px 14px; border-top:1px solid var(--neutral-100); }
.l3-empty    { font-size:12px; color:var(--fg-3); padding:12px 0; font-style:italic; }
.l3-row      { display:flex; align-items:center; gap:11px; padding:11px 0; border-bottom:1px solid var(--neutral-100); }
.l3-row:last-of-type { border-bottom:none; }
.l3-av {
    width:32px; height:32px; border-radius:9px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:700; color:#fff;
}
.l3-info  { flex:1; min-width:0; }
.l3-name  { font-size:13px; font-weight:700; color:var(--fg-1); display:flex; align-items:center; gap:6px; }
.l3-figs  { font-size:11px; color:var(--fg-3); margin-top:2px; }
.l3-prog  { width:120px; flex-shrink:0; }
.l3-track { height:5px; background:var(--neutral-100); border-radius:99px; overflow:hidden; }
.l3-pct   { font-size:11px; font-weight:700; text-align:right; margin-top:3px; }
.status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* ── AI Analyze Button ───────────────────────── */
.btn-ai {
    display:inline-flex; align-items:center; gap:5px;
    font-size:11px; font-weight:600; padding:4px 10px;
    border-radius:20px; border:1.5px solid var(--maxy-navy);
    color:var(--maxy-navy); background:#fff; cursor:pointer;
    transition:all .2s; white-space:nowrap; flex-shrink:0;
}
.btn-ai:hover { background:var(--maxy-navy); color:#fff; }
.btn-ai:disabled { opacity:.5; cursor:not-allowed; }
.btn-ai.loading { opacity:.7; pointer-events:none; }
.btn-ai .spin {
    width:12px; height:12px; border:2px solid currentColor;
    border-top-color:transparent; border-radius:50%;
    animation:spin .7s linear infinite; display:none;
}
.btn-ai.loading .spin { display:block; }
.btn-ai.loading .btn-ai-icon { display:none; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── AI Reasoning Tooltip ────────────────────── */
.ai-note {
    font-size:10px; color:var(--fg-4); font-style:italic;
    margin-top:3px;
    white-space:normal;
    word-break:break-word;
    line-height:1.5;
}

.l2-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:14px; flex-wrap:wrap; }

.badge-inactive {
    font-size:11px; font-weight:600; padding:2px 7px;
    border-radius:20px; background:var(--neutral-100); color:var(--fg-3); margin-left:6px;
}
.kpi-card {
    background:#fff; border:1.5px solid var(--neutral-200); border-radius:var(--r-lg);
    padding:14px 16px; display:flex; align-items:center; gap:14px;
}
.kpi-card + .kpi-card { margin-top:8px; }
</style>

<div class="page">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:20px;font-weight:800;color:var(--maxy-navy);margin:0;letter-spacing:-.02em;">KPI Organisasi</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:4px 0 0;">
                Target dept (L2) dipecah ke target per staff (L3) — ditetapkan C-Level &amp; Admin HR
            </p>
        </div>
        @if($canManage)
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('kpi.actuals.index') }}" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    Input Realisasi
                </a>
                <a href="{{ route('kpi.create') }}" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah KPI Dept
                </a>
            </div>
        @endif
    </div>

    {{-- ── Filter periode ── --}}
    <form method="GET" action="{{ route($kpiRouteName) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span style="font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.05em;">Periode</span>
        <div class="select-wrap">
            <select name="month" class="m-select" onchange="this.form.submit()" style="height:38px;">
                <option value="0">Semua Bulan</option>
                @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                    <option value="{{ $i+1 }}" {{ $selMonth == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                @endforeach
            </select>
        </div>
        <div class="select-wrap">
            <select name="year" class="m-select" onchange="this.form.submit()" style="height:38px;">
                <option value="0">Semua Tahun</option>
                @foreach(range(now()->year - 1, now()->year + 1) as $y)
                    <option value="{{ $y }}" {{ $selYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @if($selMonth || $selYear)
            <a href="{{ route($kpiRouteName) }}" style="font-size:12px;color:var(--maxy-navy);font-weight:600;text-decoration:none;">Reset</a>
        @endif
    </form>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:0;">{{ session('success') }}</div>
    @endif

    {{-- ── KPI by Dept ── --}}
    @forelse($visibleByDept as $deptKey => $kpis)
        @php
            $deptLabel    = \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_',' ',$deptKey));
            $color        = $deptColors[$deptKey] ?? '#64748b';
            $activeKpis   = $kpis->where('is_active', true);
            $inactiveKpis = $kpis->where('is_active', false);
        @endphp

        <div class="dept-section">
            <div class="dept-label">
                <div class="dept-badge">
                    <span class="dept-dot" style="background:{{ $color }};"></span>
                    {{ $deptLabel }}
                    <span style="color:var(--fg-3);font-weight:500;">· {{ $activeKpis->count() }} KPI aktif</span>
                </div>
            </div>

            {{-- ── KPI L2 Aktif (accordion) ── --}}
            @foreach($activeKpis as $kpi)
                @php
                    // L3 staff KPIs (anak yang aktif)
                    $children   = $kpi->children->where('is_active', true);
                    $deptTarget = (float) $kpi->target_value;
                    $allocated  = (float) $children->sum('target_value');
                    $unalloc    = $deptTarget - $allocated;
                    $allocPct   = $deptTarget > 0 ? min(100, round($allocated / $deptTarget * 100)) : 0;
                    $allocColor = $allocated > $deptTarget ? 'var(--danger)' : 'var(--maxy-navy)';

                    // Realisasi tim: jumlah actual tiap anak (match periode anak, fallback terbaru)
                    $totalActual  = 0.0;
                    $hasAnyActual = false;
                    foreach ($children as $ch) {
                        $act = $ch->actuals->first(fn($a) => (int)$a->month === (int)$ch->month && (int)$a->year === (int)$ch->year)
                            ?? $ch->actuals->sortByDesc(fn($a) => $a->year * 100 + $a->month)->first();
                        if ($act) { $totalActual += (float) $act->actual_value; $hasAnyActual = true; }
                    }
                    $realPct   = $allocated > 0 ? round($totalActual / $allocated * 100) : 0;
                    $realColor = !$hasAnyActual ? 'var(--fg-4)'
                                : ($realPct >= 80 ? 'var(--success)' : ($realPct >= 60 ? 'var(--warning)' : 'var(--danger)'));
                @endphp

                <div class="kpi-l2">
                    <button type="button" class="kpi-l2-head" aria-expanded="false"
                            onclick="const b=this.nextElementSibling;const h=b.classList.toggle('hidden');this.setAttribute('aria-expanded',String(!h));">
                        <div class="kpi-icon" style="background:{{ $color }}1A;">
                            <svg class="lucide sm" style="color:{{ $color }};" viewBox="0 0 24 24">
                                <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                            </svg>
                        </div>

                        <div class="kpi-info">
                            <div class="kpi-name">{{ $kpi->kpi_name }}</div>
                            <div class="kpi-meta">
                                Target Dept: <span class="kpi-target">{{ number_format($deptTarget, 0, ',', '.') }} {{ $kpi->unit }}</span>
                                &nbsp;·&nbsp; {{ $monthNames[$kpi->month] }} {{ $kpi->year }}
                                @if($kpi->setter) &nbsp;·&nbsp; Oleh: {{ $kpi->setter->name }} @endif
                            </div>

                            {{-- Mini bars --}}
                            <div class="kpi-bars">
                                {{-- Alokasi --}}
                                <div class="kpi-bar-row">
                                    <span class="kpi-bar-lbl">Alokasi</span>
                                    <span class="kpi-bar-track">
                                        <span class="kpi-bar-fill" style="width:{{ $allocPct }}%;background:{{ $allocColor }};"></span>
                                    </span>
                                    <span class="kpi-bar-val">
                                        {{ number_format($allocated, 0, ',', '.') }}/{{ number_format($deptTarget, 0, ',', '.') }}
                                        @if($unalloc > 0)
                                            <span style="color:var(--fg-4);font-weight:500;">· sisa {{ number_format($unalloc, 0, ',', '.') }}</span>
                                        @elseif($unalloc < 0)
                                            <span style="color:var(--danger);font-weight:500;">· lebih {{ number_format(abs($unalloc), 0, ',', '.') }}</span>
                                        @else
                                            <span style="color:var(--success);font-weight:500;">· pas</span>
                                        @endif
                                    </span>
                                </div>
                                {{-- Realisasi --}}
                                <div class="kpi-bar-row">
                                    <span class="kpi-bar-lbl">Realisasi</span>
                                    <span class="kpi-bar-track">
                                        <span class="kpi-bar-fill" style="width:{{ min(100, $realPct) }}%;background:{{ $realColor }};"></span>
                                    </span>
                                    <span class="kpi-bar-val">
                                        @if($hasAnyActual)
                                            {{ $realPct }}% <span style="color:var(--fg-4);font-weight:500;">· {{ number_format($totalActual, 0, ',', '.') }} {{ $kpi->unit }}</span>
                                        @else
                                            <span style="color:var(--fg-4);font-weight:500;">belum ada data</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="kpi-l2-right">
                            <span class="staff-count">{{ $children->count() }} staff</span>
                            <svg class="lucide sm chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                    </button>

                    {{-- ── Body: breakdown L3 staff ── --}}
                    <div class="kpi-l2-body hidden">
                        @if($kpi->notes)
                            <div style="font-size:11px;color:var(--fg-3);font-style:italic;padding:10px 0 4px;">{{ $kpi->notes }}</div>
                        @endif

                        @forelse($children as $ch)
                            @php
                                $act = $ch->actuals->first(fn($a) => (int)$a->month === (int)$ch->month && (int)$a->year === (int)$ch->year)
                                    ?? $ch->actuals->sortByDesc(fn($a) => $a->year * 100 + $a->month)->first();
                                $chTarget = (float) $ch->target_value;
                                $chName   = $ch->staff->name ?? 'Staf';
                                $chInit   = collect(explode(' ', $chName))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
                                $sc       = $act?->status_color ?? 'gray';
                                $dotCol   = $statusColor[$sc] ?? 'var(--fg-4)';
                                $chPct    = $act?->gap_percent;
                            @endphp
                            <div class="l3-row" id="l3-row-{{ $ch->id }}">
                                <span class="l3-av" style="background:{{ $color }};">{{ $chInit }}</span>
                                <div class="l3-info">
                                    <div class="l3-name">
                                        <span class="status-dot" id="dot-{{ $ch->id }}" style="background:{{ $dotCol }};"></span>
                                        {{ $chName }}
                                    </div>
                                    <div class="l3-figs" id="figs-{{ $ch->id }}">
                                        Target {{ number_format($chTarget, 0, ',', '.') }} {{ $ch->unit }}
                                        @if($act)
                                            &nbsp;·&nbsp; Aktual <span id="actual-val-{{ $ch->id }}">{{ number_format($act->actual_value, 0, ',', '.') }}</span>
                                            @if($act->source === 'auto_detected')
                                                &nbsp;<span style="font-size:10px;background:#EEF2FF;color:#4F46E5;padding:1px 5px;border-radius:4px;">✨ AI</span>
                                            @endif
                                        @else
                                            &nbsp;·&nbsp; <span id="actual-val-{{ $ch->id }}" style="font-style:italic;">belum ada realisasi</span>
                                        @endif
                                    </div>
                                    @if($act?->source === 'auto_detected' && $act->notes)
                                        <div class="ai-note" title="{{ $act->notes }}">💬 {{ $act->notes }}</div>
                                    @else
                                        <div class="ai-note" id="ai-note-{{ $ch->id }}"></div>
                                    @endif
                                </div>
                                <div class="l3-prog">
                                    <div class="l3-track">
                                        <div id="bar-fill-{{ $ch->id }}" style="height:100%;border-radius:99px;width:{{ $chPct !== null ? min(100, max(0, $chPct)) : 0 }}%;background:{{ $dotCol }};transition:width .6s ease;"></div>
                                    </div>
                                    <div class="l3-pct" id="bar-pct-{{ $ch->id }}" style="color:{{ $dotCol }};">
                                        {{ $chPct !== null ? $chPct.'%' : '—' }}
                                    </div>
                                </div>
                                @if($canManage)
                                <button type="button" class="btn-ai"
                                    id="ai-btn-{{ $ch->id }}"
                                    onclick="analyzeKpi({{ $ch->id }}, {{ $ch->user_id }}, {{ $kpi->month }}, {{ $kpi->year }})"
                                    title="Analisis laporan harian dengan AI">
                                    <span class="btn-ai-icon">✨</span>
                                    <span class="spin"></span>
                                    AI
                                </button>
                                @endif
                            </div>
                        @empty
                            <div class="l3-empty">Belum ada KPI staff untuk target dept ini.</div>
                        @endforelse

                        {{-- ── Actions ── --}}
                        @if($canManage)
                            <div class="l2-foot">
                                <a href="{{ route('kpi.staff.create', ['parent_id' => $kpi->id]) }}" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
                                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                                    Tambah KPI Staff
                                </a>
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('kpi.edit', $kpi) }}" class="btn btn-outline btn-sm" title="Edit KPI Dept">
                                        <svg class="lucide sm" viewBox="0 0 24 24">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('kpi.destroy', $kpi) }}"
                                          data-confirm="Nonaktifkan KPI ini?" data-confirm-variant="danger" data-confirm-ok="Ya, Nonaktifkan" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline btn-sm" title="Nonaktifkan"
                                                style="color:var(--danger);border-color:var(--danger);">
                                            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- ── KPI Tidak Aktif (collapsed) ── --}}
            @if($inactiveKpis->count() > 0)
                <details style="margin-top:8px;">
                    <summary style="font-size:12px;color:var(--fg-3);cursor:pointer;padding:4px 0;list-style:none;display:flex;align-items:center;gap:6px;">
                        <svg class="lucide" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        {{ $inactiveKpis->count() }} KPI tidak aktif
                    </summary>
                    <div style="margin-top:8px;opacity:.6;">
                        @foreach($inactiveKpis as $kpi)
                            <div class="kpi-card" style="border-style:dashed;">
                                <div class="kpi-icon" style="background:var(--neutral-100);">
                                    <svg class="lucide sm" style="color:var(--fg-3);" viewBox="0 0 24 24">
                                        <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                                    </svg>
                                </div>
                                <div class="kpi-info">
                                    <div class="kpi-name" style="color:var(--fg-3);">
                                        {{ $kpi->kpi_name }}
                                        <span class="badge-inactive">Nonaktif</span>
                                    </div>
                                    <div class="kpi-meta">
                                        Target: {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}
                                        &nbsp;·&nbsp; {{ $monthNames[$kpi->month] }} {{ $kpi->year }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    @empty
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-3);" viewBox="0 0 24 24">
                    <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                </svg>
                @if($kpiByDept->isEmpty())
                    <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Belum Ada KPI</p>
                    <p style="font-size:12px;color:var(--fg-3);margin:0 0 16px;">KPI departemen belum ditetapkan.</p>
                    @if($canManage)
                        <a href="{{ route('kpi.create') }}" class="btn btn-primary">+ Tambah KPI Pertama</a>
                    @endif
                @else
                    <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Tidak Ada KPI untuk Periode Ini</p>
                    <p style="font-size:12px;color:var(--fg-3);margin:0 0 16px;">Coba ubah filter bulan/tahun di atas.</p>
                    <a href="{{ route($kpiRouteName) }}" class="btn btn-outline btn-sm">Reset Filter</a>
                @endif
            </div>
        </div>
    @endforelse

</div>

<script>
const _analyzeUrl  = "{{ route('kpi.actuals.analyze-ai') }}";
const _csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function analyzeKpi(kpiTargetId, staffId, month, year) {
    const btn      = document.getElementById('ai-btn-' + kpiTargetId);
    const barFill  = document.getElementById('bar-fill-' + kpiTargetId);
    const barPct   = document.getElementById('bar-pct-' + kpiTargetId);
    const aiNote   = document.getElementById('ai-note-' + kpiTargetId);
    const actualEl = document.getElementById('actual-val-' + kpiTargetId);
    const dot      = document.getElementById('dot-' + kpiTargetId);

    if (!btn) return;

    // Loading state
    btn.classList.add('loading');
    btn.disabled = true;
    if (aiNote) aiNote.textContent = '⏳ Menganalisis laporan...';

    try {
        const res = await fetch(_analyzeUrl, {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': _csrfToken,
                'Accept'      : 'application/json',
            },
            body: JSON.stringify({ kpi_target_id: kpiTargetId, month, year }),
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan.'));
            return;
        }

        // Hitung warna berdasarkan persentase
        const pct   = data.percentage;
        const color = pct >= 80 ? 'var(--success)' : pct >= 60 ? 'var(--warning)' : 'var(--danger)';

        // Update bar
        if (barFill) { barFill.style.width = Math.min(100, pct) + '%'; barFill.style.background = color; }
        if (barPct)  { barPct.textContent = pct + '%'; barPct.style.color = color; }
        if (dot)     { dot.style.background = color; }

        // Update angka aktual
        if (actualEl) {
            const val = parseFloat(data.actual_value).toLocaleString('id-ID');
            actualEl.style.fontStyle = 'normal';
            actualEl.textContent = val;
        }

        // Tampilkan reasoning
        if (aiNote) {
            aiNote.textContent = '💬 ' + (data.reasoning || '');
            aiNote.title       = data.reasoning || '';
        }

        // Ubah label tombol
        btn.innerHTML = '<span>✨</span> Analisis Ulang';
        btn.title = 'Dianalisis dari ' + data.reports_analyzed + ' laporan';

    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
        if (aiNote) aiNote.textContent = '';
    } finally {
        btn.classList.remove('loading');
        btn.disabled = false;
    }
}
</script>
