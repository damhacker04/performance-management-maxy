<x-app-layout>
@php
    $canManage = in_array(auth()->user()->role, ['c_level','super_admin']) || auth()->user()->is_management;
    $deptColors = [
        'sales'=>'#1B4FD8','marketing'=>'#7C3AED','operational'=>'#0E7490',
        'hr'=>'#065F46','finance'=>'#9A3412','product_it'=>'#1D4ED8',
        'ga'=>'#047857','creative'=>'#6D28D9','customer_support'=>'#C2410C',
    ];
    $monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
@endphp

<style>
/* ── Dept Section ─────────────────────────────── */
.dept-section { margin-bottom: 28px; }
.dept-label {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px;
}
.dept-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--fg-3);
}
.dept-dot {
    width: 8px; height: 8px; border-radius: 50%;
}

/* ── KPI Card ─────────────────────────────────── */
.kpi-card {
    background: #fff;
    border: 1.5px solid var(--neutral-200);
    border-radius: var(--r-lg);
    padding: 14px 16px;
    display: flex; align-items: center; gap: 14px;
    transition: border-color .2s, box-shadow .2s;
    box-shadow: var(--shadow-sm);
}
.kpi-card:hover { border-color: var(--maxy-navy); box-shadow: var(--shadow-md); }
.kpi-card + .kpi-card { margin-top: 8px; }

.kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.kpi-info { flex: 1; min-width: 0; }
.kpi-name { font-size: 14px; font-weight: 700; color: var(--fg-1); margin-bottom: 3px; }
.kpi-meta { font-size: 12px; color: var(--fg-3); }
.kpi-target { font-weight: 700; color: var(--maxy-navy); }

.kpi-actions { display: flex; gap: 6px; flex-shrink: 0; }

/* Badge inactive */
.badge-inactive {
    font-size: 10px; font-weight: 600; padding: 2px 7px;
    border-radius: 20px; background: var(--neutral-100);
    color: var(--fg-4); margin-left: 6px;
}
</style>

<div class="page">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:20px;font-weight:800;color:var(--maxy-navy);margin:0;letter-spacing:-.02em;">KPI Organisasi</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:4px 0 0;">
                Standar KPI per departemen — ditetapkan C-Level &amp; Admin HR
            </p>
        </div>
        @if($canManage)
            <a href="{{ route('kpi.create') }}" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Tambah KPI
            </a>
        @endif
    </div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:0;">{{ session('success') }}</div>
    @endif

    {{-- ── KPI by Dept ── --}}
    @forelse($kpiByDept as $deptKey => $kpis)
        @php
            $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_',' ',$deptKey));
            $color     = $deptColors[$deptKey] ?? '#64748b';
            $activeKpis   = $kpis->where('is_active', true);
            $inactiveKpis = $kpis->where('is_active', false);
        @endphp

        <div class="dept-section">
            <div class="dept-label">
                <div class="dept-badge">
                    <span class="dept-dot" style="background:{{ $color }};"></span>
                    {{ $deptLabel }}
                    <span style="color:var(--fg-4);font-weight:500;">· {{ $activeKpis->count() }} KPI aktif</span>
                </div>
            </div>

            {{-- KPI Aktif --}}
            @foreach($activeKpis as $kpi)
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:{{ $color }}1A;">
                        <svg class="lucide sm" style="color:{{ $color }};" viewBox="0 0 24 24">
                            <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                        </svg>
                    </div>

                    <div class="kpi-info">
                        <div class="kpi-name">{{ $kpi->kpi_name }}</div>
                        <div class="kpi-meta">
                            Target: <span class="kpi-target">{{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}</span>
                            &nbsp;·&nbsp;
                            Berlaku: <strong>{{ $monthNames[$kpi->month] }} {{ $kpi->year }}</strong>
                            @if($kpi->setter)
                                &nbsp;·&nbsp; Oleh: {{ $kpi->setter->name }}
                            @endif
                        </div>
                        @if($kpi->notes)
                            <div style="font-size:11px;color:var(--fg-4);margin-top:3px;font-style:italic;">{{ $kpi->notes }}</div>
                        @endif
                    </div>

                    @if($canManage)
                        <div class="kpi-actions">
                            <a href="{{ route('kpi.edit', $kpi) }}" class="btn btn-outline btn-sm" title="Edit KPI">
                                <svg class="lucide sm" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('kpi.destroy', $kpi) }}"
                                  onsubmit="return confirm('Nonaktifkan KPI ini?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" title="Nonaktifkan"
                                        style="color:var(--danger);border-color:var(--danger);">
                                    <svg class="lucide sm" viewBox="0 0 24 24">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- KPI Tidak Aktif (collapsed) --}}
            @if($inactiveKpis->count() > 0)
                <details style="margin-top:8px;">
                    <summary style="font-size:12px;color:var(--fg-4);cursor:pointer;padding:4px 0;list-style:none;display:flex;align-items:center;gap:6px;">
                        <svg class="lucide" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        {{ $inactiveKpis->count() }} KPI tidak aktif
                    </summary>
                    <div style="margin-top:8px;opacity:.6;">
                        @foreach($inactiveKpis as $kpi)
                            <div class="kpi-card" style="border-style:dashed;">
                                <div class="kpi-icon" style="background:var(--neutral-100);">
                                    <svg class="lucide sm" style="color:var(--fg-4);" viewBox="0 0 24 24">
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
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Belum Ada KPI</p>
                <p style="font-size:12px;color:var(--fg-3);margin:0 0 16px;">
                    KPI departemen belum ditetapkan.
                </p>
                @if($canManage)
                    <a href="{{ route('kpi.create') }}" class="btn btn-primary">+ Tambah KPI Pertama</a>
                @endif
            </div>
        </div>
    @endforelse

</div>
</x-app-layout>
