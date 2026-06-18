<x-app-layout>
@php
    $months = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $deptColors = [
        'sales'          => '#2F6BD6',
        'marketing'      => '#B43BB7',
        'product_it'     => '#16A571',
        'operational'    => '#E89B2A',
        'hr'             => '#6D28D9',
        'finance'        => '#0D9488',
        'ga'             => '#B45309',
        'creative'       => '#DB2777',
        'customer_support'=> '#1D4ED8',
        'ceo_office'     => '#232E66',
    ];
    $deptLabels = [
        'sales'           => 'Sales',
        'marketing'       => 'Marketing',
        'product_it'      => 'Product & IT',
        'operational'     => 'Operational',
        'hr'              => 'HR',
        'finance'         => 'Finance',
        'ga'              => 'GA',
        'creative'        => 'Creative',
        'customer_support'=> 'Customer Support',
        'ceo_office'      => 'CEO Office',
    ];
    $isCLevel = auth()->user()->role === 'c_level';
    $groupedByDept = $isCLevel ? $targets->groupBy('department') : null;
@endphp

<div class="page">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Target</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
                @if(auth()->user()->department)
                    {{ ucfirst(str_replace('_',' ', auth()->user()->department)) }}
                @else
                    {{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Semua Departemen' }}
                @endif
            </p>
        </div>
        <a href="{{ route('monthly-targets.create') }}" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </a>
    </div>

    {{-- Segmented control: hanya untuk leader --}}
    @if(auth()->user()->role === 'leader')
    <div style="display:flex;background:var(--bg-2);border-radius:10px;padding:3px;gap:2px;">
        <a href="{{ route('monthly-targets.index') }}"
           style="flex:1;text-align:center;padding:7px 8px;border-radius:8px;font-size:13px;font-weight:600;
                  background:white;color:var(--maxy-navy);text-decoration:none;
                  box-shadow:0 1px 3px rgba(0,0,0,.12);">
            Kelola Tim
        </a>
        <a href="{{ route('leader-targets.index') }}"
           style="flex:1;text-align:center;padding:7px 8px;border-radius:8px;font-size:13px;font-weight:600;
                  color:var(--fg-3);text-decoration:none;transition:all .15s;">
            Target Saya
        </a>
    </div>
    @endif

    @if($targets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p style="font-size:14px;margin-bottom:8px;">Belum ada target bulanan.</p>
                <a href="{{ route('monthly-targets.create') }}" style="font-size:13px;font-weight:600;color:var(--maxy-navy);">Buat target pertama →</a>
            </div>
        </div>

    @elseif($isCLevel)

        {{-- ════════════════════════════════════════
             C-LEVEL VIEW: Accordion per departemen
             ════════════════════════════════════════ --}}
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($groupedByDept as $dept => $deptTargets)
                @php
                    $color      = $deptColors[$dept] ?? '#232E66';
                    $label      = $deptLabels[$dept] ?? ucfirst(str_replace('_',' ', $dept));
                    $count      = $deptTargets->count();
                    $hasCurrentMonth = $deptTargets->contains(fn($t) => $t->month == now()->month && $t->year == now()->year);
                    $deptId     = 'dept-' . str_replace('_','-',$dept);
                @endphp

                <details id="{{ $deptId }}" open style="background:var(--bg-1);border-radius:14px;overflow:hidden;
                            border:1.5px solid {{ $hasCurrentMonth ? $color : 'var(--bd-1)' }};
                            box-shadow:0 1px 4px rgba(0,0,0,.06);">

                    {{-- Accordion Header --}}
                    <summary style="list-style:none;cursor:pointer;padding:14px 16px;display:flex;align-items:center;gap:10px;
                                    -webkit-tap-highlight-color:transparent;user-select:none;">

                        {{-- Colour dot --}}
                        <div style="width:10px;height:10px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></div>

                        {{-- Dept name --}}
                        <div style="flex:1;min-width:0;">
                            <span style="font-size:14px;font-weight:700;color:var(--fg-1);">{{ $label }}</span>
                            @if($hasCurrentMonth)
                                <span class="chip chip-info" style="font-size:10px;margin-left:6px;">Bulan ini</span>
                            @endif
                        </div>

                        {{-- Badge count --}}
                        <span style="background:{{ $color }}18;color:{{ $color }};font-size:11px;font-weight:700;
                                     padding:2px 8px;border-radius:20px;flex-shrink:0;">
                            {{ $count }} target
                        </span>

                        {{-- Chevron icon (rotates via CSS) --}}
                        <svg class="dept-chevron" viewBox="0 0 24 24"
                             style="width:16px;height:16px;stroke:var(--fg-3);fill:none;stroke-width:2;
                                    flex-shrink:0;transition:transform .2s;">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </summary>

                    {{-- Accordion Body --}}
                    <div style="padding:0 12px 12px;display:flex;flex-direction:column;gap:8px;">

                        {{-- Divider --}}
                        <div style="height:1px;background:var(--bd-1);margin-bottom:4px;"></div>

                        @foreach($deptTargets as $target)
                            @php
                                $weeklyCount    = $target->weeklyTargets->count();
                                $isCurrentMonth = $target->month == now()->month && $target->year == now()->year;
                                $totalEntries   = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
                                $doneEntries    = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());
                                $pct            = $totalEntries > 0 ? round($doneEntries / $totalEntries * 100) : 0;
                            @endphp
                            <a href="{{ route('monthly-targets.show', $target) }}"
                               style="display:block;text-decoration:none;color:inherit;">
                                <div class="m-card" style="padding:12px 14px;
                                            {{ $isCurrentMonth ? 'border:1.5px solid '.$color.';' : '' }}">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                                        <div style="flex:1;min-width:0;">
                                            <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:5px;">
                                                <span class="chip chip-neutral" style="font-size:11px;">
                                                    {{ $months[$target->month] }} {{ $target->year }}
                                                </span>
                                                @if($isCurrentMonth)
                                                    <span class="chip chip-success" style="font-size:10px;">Aktif</span>
                                                @endif
                                            </div>
                                            <div style="font-size:14px;font-weight:600;color:var(--fg-1);
                                                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                {{ $target->title }}
                                            </div>
                                            @if($target->description)
                                                <p style="font-size:12px;color:var(--fg-3);margin:3px 0 0;
                                                          display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;">
                                                    {{ $target->description }}
                                                </p>
                                            @endif

                                            {{-- Progress bar mini --}}
                                            @if($totalEntries > 0)
                                                <div style="margin-top:8px;">
                                                    <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                                                        <div style="height:100%;width:{{ $pct }}%;background:{{ $color }};border-radius:4px;transition:width .4s;"></div>
                                                    </div>
                                                    <div style="font-size:10px;color:var(--fg-4);margin-top:3px;">
                                                        {{ $totalEntries }} laporan masuk
                                                    </div>
                                                </div>
                                            @endif

                                            <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">
                                                oleh {{ $target->user->name }}
                                            </div>
                                        </div>
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0;">
                                            @if($weeklyCount > 0)
                                                <span class="chip chip-info" style="font-size:10px;">{{ $weeklyCount }} target mingguan</span>
                                            @else
                                                <span class="chip chip-neutral" style="font-size:10px;color:var(--fg-4);">Belum ada</span>
                                            @endif
                                            <svg class="lucide sm" style="color:var(--fg-3);" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>

        <style>
            details[open] .dept-chevron { transform: rotate(180deg); }
            details summary::-webkit-details-marker { display:none; }
        </style>

    @else

        {{-- ════════════════════════════════════════════════════════════════
             LEADER VIEW: Accordion Bulan → Dropdown Dept → Target Cards
             ════════════════════════════════════════════════════════════════ --}}
        <div style="display:flex;flex-direction:column;gap:10px;">

        @foreach($leaderGrouped as $yearMonth => $deptGroups)
            @php
                [$yr, $mo]   = explode('-', $yearMonth);
                $monthLabel  = ['','Januari','Februari','Maret','April','Mei','Juni',
                                'Juli','Agustus','September','Oktober','November','Desember'][(int)$mo]
                               . ' ' . $yr;
                $isActive    = (int)$mo == now()->month && (int)$yr == now()->year;
                $totalTargets= $deptGroups->flatten()->count();

                // Total laporan bulan ini
                $totalEntries = $deptGroups->flatten()->sum(
                    fn($t) => $t->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count())
                );
                $doneEntries  = $deptGroups->flatten()->sum(
                    fn($t) => $t->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count())
                );
                $pct = $totalEntries > 0 ? round($doneEntries / $totalEntries * 100) : 0;
                $monthId = 'month-' . $yearMonth;
            @endphp

            {{-- ── Level 1: Accordion per Bulan ── --}}
            <details id="{{ $monthId }}" {{ $isActive ? 'open' : '' }}
                     style="background:var(--bg-1);border-radius:14px;overflow:hidden;
                            border:1.5px solid {{ $isActive ? 'var(--warning)' : 'var(--bd-1)' }};
                            box-shadow:0 2px 8px rgba(0,0,0,.07);">

                <summary style="list-style:none;cursor:pointer;padding:14px 16px;
                                display:flex;align-items:center;gap:10px;
                                -webkit-tap-highlight-color:transparent;user-select:none;">

                    {{-- Status dot --}}
                    <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0;
                                background:{{ $isActive ? 'var(--warning)' : 'var(--neutral-300)' }};
                                {{ $isActive ? 'box-shadow:0 0 0 3px #FEF3C7;' : '' }}">
                    </div>

                    {{-- Bulan label --}}
                    <div style="flex:1;min-width:0;">
                        <span style="font-size:15px;font-weight:700;color:var(--fg-1);">
                            {{ $monthLabel }}
                        </span>
                        @if($isActive)
                            <span class="chip chip-success" style="font-size:10px;margin-left:6px;">Bulan ini</span>
                        @endif
                    </div>

                    {{-- Badge jumlah target --}}
                    <span style="background:{{ $isActive ? '#FEF3C7' : 'var(--neutral-100)' }};
                                 color:{{ $isActive ? '#92400E' : 'var(--fg-3)' }};
                                 font-size:11px;font-weight:700;
                                 padding:3px 10px;border-radius:20px;flex-shrink:0;">
                        {{ $totalTargets }} target
                    </span>

                    {{-- Chevron --}}
                    <svg class="month-chevron-{{ $monthId }}" viewBox="0 0 24 24"
                         style="width:16px;height:16px;stroke:var(--fg-3);fill:none;
                                stroke-width:2;flex-shrink:0;transition:transform .2s;">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </summary>

                {{-- ── Body: Progress bar + Dept Dropdown rows ── --}}
                <div style="padding:0 12px 12px;display:flex;flex-direction:column;gap:6px;">

                    {{-- Divider --}}
                    <div style="height:1px;background:var(--bd-1);margin-bottom:2px;"></div>

                    {{-- Progress bar bulan (jika ada laporan) --}}
                    @if($totalEntries > 0)
                        <div style="padding:6px 4px 2px;">
                            <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--fg-4);margin-bottom:4px;">
                                <span>{{ $doneEntries }}/{{ $totalEntries }} laporan selesai</span>
                                <span style="font-weight:700;color:{{ $pct >= 80 ? '#16A571' : ($pct >= 50 ? 'var(--warning)' : 'var(--fg-3)') }};">
                                    {{ $pct }}%
                                </span>
                            </div>
                            <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pct }}%;border-radius:4px;transition:width .4s;
                                            background:{{ $pct >= 80 ? '#16A571' : 'var(--warning)' }};"></div>
                            </div>
                        </div>
                    @endif

                    {{-- ── Level 2: Dropdown Dept ── --}}
                    @foreach($deptGroups as $dept => $deptTargets)
                        @php
                            $color      = $deptColors[$dept] ?? '#232E66';
                            $label      = $deptLabels[$dept] ?? ucfirst(str_replace('_',' ', $dept));
                            $deptCount  = $deptTargets->count();
                            $firstTarget = $deptTargets->first();
                            $deptTotalE = $deptTargets->sum(fn($t) => $t->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count()));
                            $deptDoneE  = $deptTargets->sum(fn($t) => $t->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count()));
                            $deptPct    = $deptTotalE > 0 ? round($deptDoneE / $deptTotalE * 100) : 0;
                            $weeklyCount = $deptTargets->sum(fn($t) => $t->weeklyTargets->count());
                            $staffCount  = $deptTargets->pluck('assigned_to')->merge($deptTargets->pluck('user_id'))->filter()->unique()->count();
                            $deptTargetId = 'dept-' . $yearMonth . '-' . str_replace('_','-',$dept);
                        @endphp

                        {{-- Jika hanya 1 target → langsung link ke show --}}
                        @if($deptCount === 1)
                            <a href="{{ route('monthly-targets.show', $firstTarget) }}"
                               style="display:flex;align-items:center;gap:10px;
                                      padding:11px 14px;border-radius:10px;
                                      border:1.5px solid var(--bd-1);background:#fff;
                                      text-decoration:none;color:inherit;
                                      transition:border-color .15s,box-shadow .15s;"
                               onmouseenter="this.style.borderColor='{{ $color }}';this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'"
                               onmouseleave="this.style.borderColor='var(--bd-1)';this.style.boxShadow='none'">

                                {{-- Colour dot dept --}}
                                <div style="width:8px;height:8px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></div>

                                {{-- Info --}}
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <span style="font-size:13px;font-weight:700;color:{{ $color }};">{{ $label }}</span>
                                        <span style="font-size:12px;color:var(--fg-3);">·</span>
                                        <span style="font-size:12px;color:var(--fg-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;">
                                            {{ $firstTarget->title }}
                                        </span>
                                    </div>
                                    <div style="font-size:11px;color:var(--fg-4);margin-top:2px;display:flex;gap:8px;flex-wrap:wrap;">
                                        @if($weeklyCount > 0)
                                            <span>📋 {{ $weeklyCount }} target mingguan</span>
                                        @endif
                                        @if($staffCount > 0)
                                            <span>👤 {{ $staffCount }} staf</span>
                                        @endif
                                    </div>
                                    {{-- Mini progress --}}
                                    @if($deptTotalE > 0)
                                        <div style="height:3px;background:var(--bg-3);border-radius:3px;overflow:hidden;margin-top:6px;">
                                            <div style="height:100%;width:{{ $deptPct }}%;background:{{ $color }};border-radius:3px;"></div>
                                        </div>
                                        <div style="font-size:10px;color:var(--fg-4);margin-top:2px;">{{ $deptPct }}% selesai</div>
                                    @endif
                                </div>

                                <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </a>

                        @else
                            {{-- Multiple target → accordion dept dalam bulan --}}
                            <details id="{{ $deptTargetId }}"
                                     style="background:#fff;border-radius:10px;overflow:hidden;
                                            border:1.5px solid var(--bd-1);">
                                <summary style="list-style:none;cursor:pointer;padding:11px 14px;
                                                display:flex;align-items:center;gap:10px;
                                                -webkit-tap-highlight-color:transparent;user-select:none;">
                                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></div>
                                    <div style="flex:1;min-width:0;">
                                        <span style="font-size:13px;font-weight:700;color:{{ $color }};">{{ $label }}</span>
                                        <span style="font-size:12px;color:var(--fg-4);margin-left:6px;">{{ $deptCount }} target</span>
                                    </div>
                                    @if($deptTotalE > 0)
                                        <span style="font-size:11px;font-weight:700;
                                                     color:{{ $deptPct >= 80 ? '#16A571' : 'var(--fg-3)' }};">
                                            {{ $deptPct }}%
                                        </span>
                                    @endif
                                    <svg class="dept2-chevron-{{ $deptTargetId }}" viewBox="0 0 24 24"
                                         style="width:14px;height:14px;stroke:var(--fg-4);fill:none;stroke-width:2;flex-shrink:0;transition:transform .2s;">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </summary>
                                <div style="padding:4px 12px 10px;display:flex;flex-direction:column;gap:6px;border-top:1px solid var(--bd-1);">
                                    @foreach($deptTargets as $target)
                                        @php
                                            $tTotal = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
                                            $tDone  = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());
                                            $tPct   = $tTotal > 0 ? round($tDone / $tTotal * 100) : 0;
                                        @endphp
                                        <a href="{{ route('monthly-targets.show', $target) }}"
                                           style="display:flex;align-items:center;gap:10px;
                                                  padding:10px 12px;border-radius:8px;
                                                  background:var(--bg-1);text-decoration:none;color:inherit;
                                                  transition:background .15s;"
                                           onmouseenter="this.style.background='var(--bg-2)'"
                                           onmouseleave="this.style.background='var(--bg-1)'">
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:13px;font-weight:600;color:var(--fg-1);
                                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $target->title }}
                                                </div>
                                                @if($tTotal > 0)
                                                    <div style="height:3px;background:var(--bg-3);border-radius:3px;margin-top:5px;overflow:hidden;">
                                                        <div style="height:100%;width:{{ $tPct }}%;background:{{ $color }};border-radius:3px;"></div>
                                                    </div>
                                                    <div style="font-size:10px;color:var(--fg-4);margin-top:2px;">{{ $tPct }}% selesai</div>
                                                @endif
                                            </div>
                                            <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24">
                                                <path d="M9 6l6 6-6 6"/>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                    @endforeach

                </div>
            </details>

        @endforeach

        </div>

        <style>
            details[open] .month-chevron-{{ $monthId ?? '' }} { transform: rotate(180deg); }
            details[open] [class^="dept2-chevron-"] { transform: rotate(180deg); }
            details summary::-webkit-details-marker { display: none; }
        </style>

    @endif


</div>
</x-app-layout>
