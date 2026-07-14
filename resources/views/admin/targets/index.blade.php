<x-app-layout>

@php
    // Kelompokkan byLeader berdasarkan departemen leader
    $byDept = collect($byLeader)->groupBy(fn($g) => $g['leader']?->department ?? 'umum');

    $deptNames = \App\Models\User::DEPARTMENTS ?? [];
    $deptLabel = fn($key) => $deptNames[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));

    // Stats ringkasan untuk header
    $totalLeaders     = collect($byLeader)->count();
    $totalWithTargets = collect($byLeader)->filter(fn($g) => $g['target_count'] > 0)->count();
    $totalStaffSummary = $staffSummaryByLeader->flatten(1);
    $totalStaffWithTarget = $totalStaffSummary->sum('staff_with_target');
    $totalStaffAll    = $totalStaffSummary->sum('total_staff');
@endphp

<style>
.section-label {
    font-size:11px; font-weight:700; color:var(--fg-4);
    text-transform:uppercase; letter-spacing:.8px;
    display:flex; align-items:center; gap:6px;
    margin-bottom:12px;
}
.section-label::after {
    content:''; flex:1; height:1px; background:var(--bd-1);
}
.stat-badge {
    display:inline-flex; align-items:center; gap:4px;
    font-size:11px; font-weight:600; padding:3px 8px;
    border-radius:8px; white-space:nowrap;
}
.stat-badge.ok   { background:#DCFCE7; color:#15803D; }
.stat-badge.warn { background:#FEF9C3; color:#854D0E; }
.stat-badge.info { background:#EFF6FF; color:#1D4ED8; }
.stat-badge.danger { background:#FEE2E2; color:#991B1B; }
.leader-card {
    display:flex; align-items:center; gap:12px;
    padding:14px 16px; text-decoration:none; color:inherit;
    border-radius:12px; border:1.5px solid var(--bd-1);
    background:var(--bg-2); transition:border-color .15s, box-shadow .15s;
}
.leader-card:hover { border-color:var(--maxy-navy); box-shadow:0 2px 8px rgba(0,0,0,.08); }
.dept-section { margin-top:20px; }
.summary-row {
    display:flex; align-items:center; gap:12px;
    padding:12px 16px; border-radius:10px;
    background:var(--bg-2); border:1.5px solid var(--bd-1);
    margin-bottom:8px;
}
.summary-row.no-target { border-color:#FCA5A5; background:#FFF5F5; }
</style>

<div class="page">

    {{-- ── HEADER ──────────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:var(--fg-1);margin:0 0 4px;">
                Manajemen Target
            </h1>
            <p style="font-size:13px;color:var(--fg-3);margin:0;">
                Pantau semua target: C-Level → Leader → Staff
            </p>
        </div>
        <a href="{{ route('monthly-targets.create') }}?back={{ urlencode(url()->current()) }}"
           class="btn btn-primary btn-sm" style="white-space:nowrap;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tetapkan Target
        </a>
    </div>

    {{-- ── STATS SUMMARY ────────────────────────────────────────────── --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
        <span class="stat-badge info">{{ $totalLeaders }} Leader</span>
        <span class="stat-badge {{ $totalWithTargets == $totalLeaders ? 'ok' : 'warn' }}">
            {{ $totalWithTargets }}/{{ $totalLeaders }} punya target dari C-Level
        </span>
        <span class="stat-badge {{ $totalStaffWithTarget > 0 ? 'ok' : 'warn' }}">
            {{ $totalStaffWithTarget }}/{{ $totalStaffAll }} staff punya target dari leader
        </span>
    </div>

    {{-- ── FILTER BULAN ─────────────────────────────────────────────── --}}
    <div class="m-card" style="padding:14px 16px;margin-top:8px;">
        <form method="GET" action="{{ route('admin.targets.index') }}"
              style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.6px;">
                Periode
            </span>
            <div class="select-wrap" style="flex:1;min-width:120px;">
                <select name="month" class="m-select" onchange="this.form.submit()" style="height:38px;">
                    @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                        <option value="{{ $i+1 }}" {{ $filterMonth == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="select-wrap" style="min-width:90px;">
                <select name="year" class="m-select" onchange="this.form.submit()" style="height:38px;">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <span style="font-size:14px;font-weight:700;color:var(--maxy-navy);">
                {{ $monthLabel }}
            </span>
        </form>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION A — C-Level → Leader                                    --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div style="margin-top:24px;">
        <div class="section-label">
            <svg class="lucide" style="width:13px;height:13px;" viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Target C-Level → Leader
        </div>

        @forelse($byDept as $deptKey => $leaders)
            <div class="dept-section">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span class="chip chip-dept-{{ str_replace('_','-',(string)$deptKey) }}" style="font-size:12px;">
                        {{ $deptLabel($deptKey) }}
                    </span>
                    <span style="font-size:12px;color:var(--fg-3);">{{ $leaders->count() }} leader</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($leaders as $g)
                        @php
                            $pct  = $g['progress'];
                            $pcol = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                            $init = collect(explode(' ', $g['leader']->name))->take(2)->map(fn($w)=>strtoupper($w[0]))->implode('');
                        @endphp
                        <a href="{{ route('admin.targets.leader', ['leader' => $g['leader']->id, 'month' => $filterMonth, 'year' => $filterYear]) }}"
                           class="leader-card">

                            {{-- Avatar --}}
                            <span class="av-lg" style="width:42px;height:42px;font-size:15px;background:var(--maxy-navy);flex-shrink:0;">
                                {{ $init }}
                            </span>

                            {{-- Info --}}
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:15px;font-weight:700;color:var(--fg-1);
                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $g['leader']->name }}
                                </div>
                                <div style="font-size:12px;color:var(--fg-3);margin:2px 0 6px;">
                                    {{ $g['target_count'] }} target bulanan
                                    @if($g['total'] > 0)
                                        &nbsp;·&nbsp; {{ $g['done'] }}/{{ $g['total'] }} laporan selesai
                                    @else
                                        &nbsp;·&nbsp; <em>belum ada laporan</em>
                                    @endif
                                </div>
                                <div style="height:3px;background:var(--bg-3);border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $pct }}%;background:{{ $pcol }};border-radius:3px;transition:width .4s;"></div>
                                </div>
                            </div>

                            {{-- Pct --}}
                            <div style="text-align:right;flex-shrink:0;min-width:48px;">
                                <div style="font-size:18px;font-weight:800;color:{{ $pcol }};">{{ $pct }}%</div>
                            </div>

                            <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-4);flex-shrink:0;">
                                <path d="M9 6l6 6-6 6"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="m-card" style="text-align:center;padding:40px;">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p style="font-size:14px;color:var(--fg-3);margin-bottom:12px;">
                    Belum ada target untuk leader pada {{ $monthLabel }}.
                </p>
                <a href="{{ route('monthly-targets.create') }}?back={{ urlencode(url()->current()) }}" class="btn btn-primary btn-sm">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Tetapkan Target Pertama
                </a>
            </div>
        @endforelse
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION B — Leader → Staff                                      --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div style="margin-top:32px;">
        <div class="section-label">
            <svg class="lucide" style="width:13px;height:13px;" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Target Leader → Staff
        </div>

        @forelse($staffSummaryByLeader as $deptKey => $leaderGroups)
            @php $dLabel = $deptLabel($deptKey); @endphp
            <div class="dept-section">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span class="chip chip-dept-{{ str_replace('_','-',(string)$deptKey) }}" style="font-size:12px;">
                        {{ $dLabel }}
                    </span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($leaderGroups as $s)
                        @php
                            $pct  = $s['progress'];
                            $pcol = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                            $init = collect(explode(' ', $s['leader']->name))->take(2)->map(fn($w)=>strtoupper($w[0]))->implode('');
                            $coverage = $s['total_staff'] > 0
                                ? round($s['staff_with_target'] / $s['total_staff'] * 100)
                                : 0;
                        @endphp
                        <a href="{{ route('admin.targets.leader', ['leader' => $s['leader']->id, 'month' => $filterMonth, 'year' => $filterYear]) }}"
                           class="summary-row {{ !$s['has_targets'] ? 'no-target' : '' }}"
                           style="text-decoration:none;color:inherit;">

                            {{-- Avatar --}}
                            <span style="width:36px;height:36px;border-radius:50%;background:var(--bg-3);
                                         color:var(--fg-2);display:flex;align-items:center;justify-content:center;
                                         font-size:12px;font-weight:700;flex-shrink:0;">
                                {{ $init }}
                            </span>

                            {{-- Leader name & role label --}}
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:14px;font-weight:700;color:var(--fg-1);
                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $s['leader']->name }}
                                    <span style="font-size:11px;font-weight:500;color:var(--fg-4);">· Leader</span>
                                </div>
                                @if($s['has_targets'])
                                    <div style="font-size:12px;color:var(--fg-3);margin:2px 0 5px;">
                                        {{ $s['staff_with_target'] }}/{{ $s['total_staff'] }} staff punya target
                                        &nbsp;·&nbsp; {{ $s['target_count'] }} target bulanan
                                        @if($s['total_entries'] > 0)
                                            &nbsp;·&nbsp; {{ $s['done_entries'] }}/{{ $s['total_entries'] }} laporan selesai
                                        @endif
                                    </div>
                                    <div style="height:3px;background:var(--bg-3);border-radius:3px;overflow:hidden;max-width:240px;">
                                        <div style="height:100%;width:{{ $pct }}%;background:{{ $pcol }};border-radius:3px;transition:width .4s;"></div>
                                    </div>
                                @else
                                    <div style="font-size:12px;color:#DC2626;margin-top:2px;">
                                        ⚠️ Belum ada target diberikan ke staff
                                    </div>
                                @endif
                            </div>

                            {{-- Coverage badge --}}
                            @if($s['has_targets'])
                                <div style="text-align:right;flex-shrink:0;">
                                    <span class="stat-badge {{ $coverage >= 80 ? 'ok' : ($coverage >= 50 ? 'warn' : 'danger') }}">
                                        {{ $coverage }}% staff
                                    </span>
                                    @if($s['total_entries'] > 0)
                                        <div style="font-size:13px;font-weight:800;color:{{ $pcol }};margin-top:3px;">{{ $pct }}%</div>
                                    @endif
                                </div>
                            @else
                                <span class="stat-badge danger">Belum</span>
                            @endif

                            <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-4);flex-shrink:0;">
                                <path d="M9 6l6 6-6 6"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="m-card" style="text-align:center;padding:32px;">
                <p style="font-size:13px;color:var(--fg-3);">Belum ada leader yang ditemukan.</p>
            </div>
        @endforelse
    </div>

</div>

</x-app-layout>
