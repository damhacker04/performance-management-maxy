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
                {{ ucfirst(str_replace('_',' ', auth()->user()->department ?? 'CEO Office')) }}
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
                                                <span class="chip chip-info" style="font-size:10px;">{{ $weeklyCount }} minggu</span>
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

        {{-- ════════════════════════════════════════
             LEADER VIEW: Accordion Aktif | Arsip
             ════════════════════════════════════════ --}}
        @php
            $aktifTargets = $targets->filter(fn($t) => $t->month == now()->month && $t->year == now()->year);
            $arsipTargets = $targets->reject(fn($t)  => $t->month == now()->month && $t->year == now()->year);

            // Group arsip by "Mmm YYYY" label
            $arsipGroups  = $arsipTargets->groupBy(fn($t) => $t->year . '-' . str_pad($t->month, 2, '0', STR_PAD_LEFT));
        @endphp

        <div style="display:flex;flex-direction:column;gap:8px;">

            {{-- ── AKTIF (bulan ini) — selalu terbuka ── --}}
            <details id="group-aktif" open
                     style="background:var(--bg-1);border-radius:14px;overflow:hidden;
                            border:1.5px solid var(--maxy-navy);
                            box-shadow:0 1px 4px rgba(0,0,0,.06);">

                <summary style="list-style:none;cursor:pointer;padding:13px 16px;
                                display:flex;align-items:center;gap:10px;
                                -webkit-tap-highlight-color:transparent;user-select:none;">
                    {{-- Dot hijau --}}
                    <div style="width:10px;height:10px;border-radius:50%;background:var(--success);flex-shrink:0;"></div>
                    <div style="flex:1;min-width:0;">
                        <span style="font-size:14px;font-weight:700;color:var(--fg-1);">Bulan Ini</span>
                        <span style="font-size:12px;color:var(--fg-4);margin-left:6px;">
                            {{ $months[now()->month] }} {{ now()->year }}
                        </span>
                    </div>
                    <span style="background:#16A57118;color:#16A571;font-size:11px;font-weight:700;
                                 padding:2px 8px;border-radius:20px;flex-shrink:0;">
                        {{ $aktifTargets->count() }} target
                    </span>
                    <svg class="aktif-chevron" viewBox="0 0 24 24"
                         style="width:16px;height:16px;stroke:var(--fg-3);fill:none;stroke-width:2;
                                flex-shrink:0;transition:transform .2s;">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </summary>

                <div style="padding:0 12px 12px;">
                    <div style="height:1px;background:var(--bd-1);margin-bottom:12px;"></div>

                    <div class="dt-card-grid" style="gap:12px;">
                    @forelse($aktifTargets as $target)
                        @php
                            $weeklyCount  = $target->weeklyTargets->count();
                            $color        = $deptColors[$target->department] ?? '#232E66';
                            $totalEntries = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
                            $doneEntries  = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());
                            $pct          = $totalEntries > 0 ? round($doneEntries / $totalEntries * 100) : 0;
                        @endphp
                        <a href="{{ route('monthly-targets.show', $target) }}"
                           style="display:block;text-decoration:none;color:inherit;">
                            <div class="m-card" style="padding:12px 14px;border:1.5px solid var(--maxy-navy);">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:5px;">
                                            <span class="chip chip-success" style="font-size:10px;">Aktif</span>
                                            <span class="chip chip-dept-{{ str_replace('_','-',$target->department) }}" style="font-size:10px;">
                                                {{ ucfirst(str_replace('_',' ',$target->department)) }}
                                            </span>
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
                                        @if($totalEntries > 0)
                                            <div style="margin-top:8px;">
                                                <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                                                    <div style="height:100%;width:{{ $pct }}%;background:{{ $color }};border-radius:4px;"></div>
                                                </div>
                                                <div style="font-size:10px;color:var(--fg-4);margin-top:3px;">
                                                {{ $totalEntries }} laporan masuk
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0;">
                                        @if($weeklyCount > 0)
                                            <span class="chip chip-info" style="font-size:10px;">{{ $weeklyCount }} minggu</span>
                                        @else
                                            <span class="chip chip-neutral" style="font-size:10px;color:var(--fg-4);">Belum ada</span>
                                        @endif
                                        <svg class="lucide sm" style="color:var(--fg-3);" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div style="text-align:center;padding:16px 0;font-size:13px;color:var(--fg-4);grid-column:1/-1;">
                            Belum ada target untuk bulan ini.
                            <a href="{{ route('monthly-targets.create') }}" style="color:var(--maxy-navy);font-weight:600;">Buat sekarang →</a>
                        </div>
                    @endforelse
                    </div>{{-- end dt-card-grid --}}
                </div>
            </details>

            {{-- ── ARSIP (bulan sebelumnya) — collapsed by default ── --}}
            @if($arsipTargets->isNotEmpty())
                <details id="group-arsip"
                         style="background:var(--bg-1);border-radius:14px;overflow:hidden;
                                border:1.5px solid var(--bd-1);
                                box-shadow:0 1px 4px rgba(0,0,0,.04);">

                    <summary style="list-style:none;cursor:pointer;padding:13px 16px;
                                    display:flex;align-items:center;gap:10px;
                                    -webkit-tap-highlight-color:transparent;user-select:none;">
                        <div style="width:10px;height:10px;border-radius:50%;background:var(--fg-4);flex-shrink:0;"></div>
                        <div style="flex:1;min-width:0;">
                            <span style="font-size:14px;font-weight:700;color:var(--fg-2);">Arsip</span>
                            <span style="font-size:12px;color:var(--fg-4);margin-left:6px;">Bulan sebelumnya</span>
                        </div>
                        <span style="background:var(--bg-3);color:var(--fg-3);font-size:11px;font-weight:700;
                                     padding:2px 8px;border-radius:20px;flex-shrink:0;">
                            {{ $arsipTargets->count() }} target
                        </span>
                        <svg class="arsip-chevron" viewBox="0 0 24 24"
                             style="width:16px;height:16px;stroke:var(--fg-3);fill:none;stroke-width:2;
                                    flex-shrink:0;transition:transform .2s;">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </summary>

                    <div style="padding:0 12px 12px;display:flex;flex-direction:column;gap:6px;">
                        <div style="height:1px;background:var(--bd-1);margin-bottom:4px;"></div>

                        @foreach($arsipGroups->sortKeysDesc() as $yearMonth => $groupTargets)
                            @php
                                [$yr, $mo] = explode('-', $yearMonth);
                                $groupLabel = $months[(int)$mo] . ' ' . $yr;
                            @endphp

                            {{-- Sub-label bulan --}}
                            <div style="font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
                                        color:var(--fg-4);padding:6px 4px 2px;">
                                {{ $groupLabel }}
                            </div>

                            @foreach($groupTargets as $target)
                                @php
                                    $weeklyCount  = $target->weeklyTargets->count();
                                    $color        = $deptColors[$target->department] ?? '#232E66';
                                    $totalEntries = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
                                    $doneEntries  = $target->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());
                                    $pct          = $totalEntries > 0 ? round($doneEntries / $totalEntries * 100) : 0;
                                @endphp
                                <a href="{{ route('monthly-targets.show', $target) }}"
                                   style="display:block;text-decoration:none;color:inherit;">
                                    <div class="m-card" style="padding:12px 14px;opacity:.85;">
                                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                                            <div style="flex:1;min-width:0;">
                                                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:5px;">
                                                    <span class="chip chip-neutral" style="font-size:10px;">{{ $groupLabel }}</span>
                                                    <span class="chip chip-dept-{{ str_replace('_','-',$target->department) }}" style="font-size:10px;">
                                                        {{ ucfirst(str_replace('_',' ',$target->department)) }}
                                                    </span>
                                                </div>
                                                <div style="font-size:13px;font-weight:600;color:var(--fg-2);
                                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $target->title }}
                                                </div>
                                                @if($totalEntries > 0)
                                                    <div style="margin-top:6px;">
                                                        <div style="height:3px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                                                            <div style="height:100%;width:{{ $pct }}%;background:{{ $pct == 100 ? '#16A571' : $color }};border-radius:4px;"></div>
                                                        </div>
                                                        <div style="font-size:10px;color:var(--fg-4);margin-top:2px;">
                                                        {{ $totalEntries }} laporan masuk
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0;">
                                                @if($weeklyCount > 0)
                                                    <span class="chip chip-neutral" style="font-size:10px;">{{ $weeklyCount }} minggu</span>
                                                @endif
                                                <svg class="lucide sm" style="color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                </details>
            @endif

        </div>

        <style>
            details[open] .aktif-chevron { transform: rotate(180deg); }
            details[open] .arsip-chevron { transform: rotate(180deg); }
        </style>

    @endif

</div>
</x-app-layout>
