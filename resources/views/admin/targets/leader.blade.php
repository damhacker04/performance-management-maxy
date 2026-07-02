<x-app-layout>

@php
    $deptKey    = $leader->department;
    $deptLabel  = \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_',' ', (string) $deptKey));
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;
    $monthShort = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                   7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];

    // Ambil entry counts per weekly target (satu query)
    $allWeeklyIds = $leaderTargets->pluck('weeklyTargets')->flatten()->pluck('id');
    $wtEntries = \App\Models\DailyTaskEntry::where('user_id', $leader->id)
        ->whereIn('weekly_target_id', $allWeeklyIds)
        ->get(['weekly_target_id', 'status'])
        ->groupBy('weekly_target_id')
        ->map(fn($rows) => ['total' => $rows->count(), 'done' => $rows->where('status', 'selesai')->count()]);

    // Data banner progress keseluruhan
    $totalEntries   = $leaderEntryCounts->sum('total');
    $doneEntries    = $leaderEntryCounts->sum('done');
    $overallPct     = $totalEntries > 0 ? (int) round($doneEntries / $totalEntries * 100) : 0;
    $overallCol     = $overallPct >= 80 ? '#16A571' : ($overallPct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
    $leaderInitials = collect(explode(' ', $leader->name))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
@endphp

<div class="page">

    {{-- ── HEADER ──────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:8px;">
        <x-back-button :fallback="route('admin.targets.index', ['month' => $filterMonth, 'year' => $filterYear])" style="margin-left:-8px;" />
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px;">
                <span class="chip chip-dept-{{ str_replace('_','-',(string)$deptKey) }}">{{ $deptLabel }}</span>
                <span class="chip chip-neutral" style="font-size:11px;">{{ $monthLabel }}</span>
            </div>
            <h1 style="font-size:18px;font-weight:800;color:var(--fg-1);margin:0;line-height:1.25;">
                {{ $leader->name }}
            </h1>
        </div>
        <a href="{{ route('monthly-targets.create') }}?back={{ urlencode(url()->current()) }}"
           class="btn btn-primary btn-sm" style="flex-shrink:0;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tetapkan Target
        </a>
    </div>

    {{-- ── PROGRESS BANNER ─────────────────────────────────────── --}}
    <div class="m-card" style="margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="av-lg" style="background:var(--maxy-navy);color:#fff;">{{ $leaderInitials }}</div>
            <div>
                <div style="font-size:16px;font-weight:700;">{{ $leader->name }}</div>
                <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">
                    Leader · {{ $leaderTargets->count() }} Target Bulanan
                </div>
            </div>
        </div>
        @if($totalEntries > 0)
            <div style="text-align:right;width:120px;">
                <div style="font-size:20px;font-weight:800;color:{{ $overallCol }};">{{ $overallPct }}%</div>
                <div style="font-size:11px;color:var(--fg-3);margin-bottom:6px;">
                    {{ $doneEntries }} dari {{ $totalEntries }} laporan selesai
                </div>
                <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:{{ $overallPct }}%;background:{{ $overallCol }};border-radius:4px;"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- SECTION A — Target dari Anda untuk Leader                   --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div style="margin-top:20px;">
        <span class="overline-label" style="display:block;margin-bottom:10px;">
            Target dari Anda untuk {{ explode(' ', $leader->name)[0] }}
        </span>

        <div style="display:flex;flex-direction:column;gap:10px;">
            @forelse($leaderTargets as $t)
                @php
                    $c    = $leaderEntryCounts[$t->id] ?? ['total' => 0, 'done' => 0];
                    $pct  = $c['total'] > 0 ? (int) round($c['done'] / $c['total'] * 100) : 0;
                    $pcol = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                @endphp
                <div class="m-card" style="padding:14px 16px;">

                    {{-- Monthly target header --}}
                    <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;">
                        <div style="flex:1;min-width:0;">
                            <span style="font-size:11px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.5px;">
                                Target Bulanan
                            </span>
                            <div style="font-size:15px;font-weight:700;color:var(--fg-1);line-height:1.3;margin-top:2px;">
                                {{ $t->title }}
                            </div>
                            @if($t->description)
                                <div style="font-size:12px;color:var(--fg-3);margin-top:4px;line-height:1.4;">
                                    {{ $t->description }}
                                </div>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                            <a href="{{ route('monthly-targets.edit', $t) }}?back={{ urlencode(url()->current()) }}"
                               class="icon-btn" title="Edit">
                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <a href="{{ route('weekly-targets.create', [
                                    'monthly_target_id' => $t->id,
                                    'context'           => 'leader',
                                    'assigned_to'       => $leader->id,
                                    'back'              => urlencode(url()->current()),
                                ]) }}"
                               class="btn btn-primary btn-sm">
                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                                Tambah
                            </a>
                        </div>
                    </div>

                    {{-- Progress bar bulanan --}}
                    <div style="margin-bottom:{{ $t->weeklyTargets->isNotEmpty() ? 12 : 0 }}px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                            <span style="font-size:12px;color:var(--fg-2);font-weight:600;">
                                {{ $t->weeklyTargets->count() }} target mingguan
                                &nbsp;·&nbsp; {{ $c['done'] }}/{{ $c['total'] }} laporan selesai
                            </span>
                            <span style="font-size:13px;font-weight:800;color:{{ $pcol }};">{{ $pct }}%</span>
                        </div>
                        <div class="progress-bar"><i style="width:{{ $pct }}%;background:{{ $pcol }};"></i></div>
                    </div>

                    {{-- Weekly target breakdown --}}
                    @if($t->weeklyTargets->isNotEmpty())
                        <div style="border-top:1px solid var(--bd-1);padding-top:10px;display:flex;flex-direction:column;gap:8px;">
                            @foreach($t->weeklyTargets->sortBy('week_number') as $wt)
                                @php
                                    [$rStart, $rEnd] = $weekRanges[$wt->week_number] ?? [1, 7];
                                    $wStats = $wtEntries[$wt->id] ?? ['total' => 0, 'done' => 0];
                                    $wPct   = $wStats['total'] > 0 ? (int) round($wStats['done'] / $wStats['total'] * 100) : 0;
                                    $wCol   = $wPct >= 70 ? 'var(--success)' : ($wPct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                                @endphp
                                <a href="{{ route('period.weekly-show', [
                                        'year'          => $filterYear,
                                        'month'         => $filterMonth,
                                        'staff'         => $leader->id,
                                        'monthlyTarget' => $t->id,
                                        'weeklyTarget'  => $wt->id,
                                    ]) }}?back={{ urlencode(url()->current()) }}"
                                   style="display:flex;gap:10px;padding:10px 12px;border:1.5px solid var(--bd-1);
                                          border-radius:10px;text-decoration:none;color:inherit;align-items:flex-start;">
                                    <div style="width:4px;border-radius:99px;background:{{ $wCol }};
                                                align-self:stretch;flex-shrink:0;min-height:40px;"></div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:4px;">
                                            <span class="chip chip-neutral" style="font-size:11px;font-weight:700;">
                                                Minggu {{ $wt->week_number }}
                                            </span>
                                            <span style="font-size:11px;color:var(--fg-3);">
                                                {{ $rStart }}–{{ $rEnd }} {{ $monthShort[$filterMonth] }}
                                            </span>
                                            @if($wt->target_type === 'quantitative')
                                                <span class="chip chip-info" style="font-size:11px;">{{ $wt->target_label }}</span>
                                            @endif
                                        </div>
                                        <div style="font-size:13px;font-weight:700;color:var(--maxy-navy);
                                                    margin-bottom:6px;line-height:1.3;">
                                            {{ $wt->title }}
                                        </div>
                                        @if($wStats['total'] > 0)
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <div style="flex:1;height:3px;background:var(--bg-3);border-radius:3px;overflow:hidden;">
                                                    <div style="height:100%;width:{{ $wPct }}%;background:{{ $wCol }};border-radius:3px;"></div>
                                                </div>
                                                <span style="font-size:11px;color:var(--fg-3);white-space:nowrap;">
                                                    {{ $wStats['done'] }}/{{ $wStats['total'] }} laporan
                                                </span>
                                            </div>
                                        @else
                                            <span style="font-size:11px;color:var(--fg-4);">Belum ada laporan</span>
                                        @endif
                                    </div>
                                    <svg class="lucide sm" viewBox="0 0 24 24"
                                         style="color:var(--fg-4);flex-shrink:0;margin-top:2px;">
                                        <path d="M9 6l6 6-6 6"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="m-card" style="text-align:center;padding:32px;">
                    <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <div style="font-size:13px;color:var(--fg-3);margin-bottom:12px;">
                        Anda belum menetapkan target untuk {{ explode(' ', $leader->name)[0] }} pada {{ $monthLabel }}.
                    </div>
                    <a href="{{ route('monthly-targets.create') }}?back={{ urlencode(url()->current()) }}" class="btn btn-primary btn-sm">
                        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Tetapkan Target
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- SECTION B — Target dari Leader untuk Staff (hanya lihat)    --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div style="margin-top:24px;">
        <span class="overline-label" style="display:block;margin-bottom:2px;">
            Target dari {{ explode(' ', $leader->name)[0] }} untuk Staff
        </span>
        <p style="font-size:12px;color:var(--fg-3);margin:0 0 10px;">Hanya bisa dilihat</p>

        <div style="display:flex;flex-direction:column;gap:8px;">
            @forelse($byStaff as $s)
                @php
                    $pct   = $s['progress'];
                    $pcol  = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                    $sInit = collect(explode(' ', $s['staff']->name))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
                @endphp
                <a href="{{ route('period.staff-targets', ['year' => $filterYear, 'month' => $filterMonth, 'staff' => $s['staff']->id]) }}?back={{ urlencode(url()->current()) }}"
                   class="m-card"
                   style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;padding:12px 16px;">
                    <span class="av-lg" style="width:38px;height:38px;font-size:13px;
                                               background:var(--bg-3);color:var(--fg-2);flex-shrink:0;">
                        {{ $sInit }}
                    </span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:14px;font-weight:700;color:var(--fg-1);margin-bottom:1px;">
                            {{ $s['staff']->name }}
                        </div>
                        <div style="font-size:12px;color:var(--fg-3);margin-bottom:5px;">
                            {{ $s['target_count'] }} target bulanan
                        </div>
                        <div style="height:3px;background:var(--bg-3);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $pcol }};border-radius:3px;"></div>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:16px;font-weight:800;color:{{ $pcol }};">{{ $pct }}%</div>
                    </div>
                    <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-4);flex-shrink:0;">
                        <path d="M9 6l6 6-6 6"/>
                    </svg>
                </a>
            @empty
                <div class="m-card" style="padding:24px;text-align:center;">
                    <div style="font-size:13px;color:var(--fg-3);">
                        {{ explode(' ', $leader->name)[0] }} belum memberikan target ke staff pada {{ $monthLabel }}.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

</div>

</x-app-layout>
