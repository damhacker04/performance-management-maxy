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
    $isCLevel = in_array(auth()->user()->role, ['c_level', 'super_admin']);
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

    {{-- ── Filter Periode (wajib) ── --}}
    @php
        $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
        $currentYear = now()->year;
    @endphp
    <form method="GET" action="{{ route('monthly-targets.index') }}"
          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        {{-- Bulan --}}
        <div class="select-wrap" style="width:140px;">
            <select name="month" class="m-select m-input" style="font-size:13px;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $filterMonth == $m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                @endfor
            </select>
        </div>
        {{-- Tahun --}}
        <div class="select-wrap" style="width:100px;">
            <select name="year" class="m-select m-input" style="font-size:13px;">
                @for($y = 2024; $y <= $currentYear + 1; $y++)
                    <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Tampilkan
        </button>
    </form>


    @if($targets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p style="font-size:14px;margin-bottom:4px;">Belum ada target untuk <strong>{{ $monthNames[$filterMonth] }} {{ $filterYear }}</strong>.</p>
                <p style="font-size:12px;color:var(--fg-4);margin-bottom:8px;">Coba pilih bulan lain di filter di atas.</p>
                <a href="{{ route('monthly-targets.create') }}" style="font-size:13px;font-weight:600;color:var(--maxy-navy);">Buat target baru →</a>
            </div>
        </div>

    @elseif($isCLevel)
        @php
            // Re-group dari paginated collection (sudah difilter periode di controller)
            $groupedByDept = $targets->getCollection()->groupBy('department');
        @endphp

        {{-- C-LEVEL VIEW: Accordion per departemen --}}
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
             LEADER VIEW: Daftar Bulan — klik langsung ke halaman staf
             ════════════════════════════════════════════════════════════════ --}}
        <div style="display:flex;flex-direction:column;gap:8px;">

        @foreach(($leaderGrouped ?? []) as $yearMonth => $monthTargets)
            @php
                [$yr, $mo] = explode('-', $yearMonth);
                $monthLabel = ['','Januari','Februari','Maret','April','Mei','Juni',
                               'Juli','Agustus','September','Oktober','November','Desember'][(int)$mo]
                              . ' ' . $yr;
                $isActive = (int)$mo == now()->month && (int)$yr == now()->year;

                $totalEntries = $monthTargets->sum(fn($t) => $t->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count()));
                $doneEntries  = $monthTargets->sum(fn($t) => $t->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count()));
                $pct          = $totalEntries > 0 ? round($doneEntries / $totalEntries * 100) : 0;
                $staffCount   = $monthTargets->pluck('assigned_to')->filter()->unique()->count();
            @endphp

            <a href="{{ route('period.staff-list', ['year' => $yr, 'month' => $mo]) }}"
               style="display:flex;align-items:center;gap:12px;
                      padding:14px 16px;border-radius:14px;
                      background:var(--bg-1);text-decoration:none;color:inherit;
                      border:1.5px solid {{ $isActive ? 'var(--warning)' : 'var(--bd-1)' }};
                      box-shadow:0 2px 8px rgba(0,0,0,.06);
                      transition:border-color .15s,box-shadow .15s;"
               onmouseenter="this.style.borderColor='var(--maxy-navy)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.10)'"
               onmouseleave="this.style.borderColor='{{ $isActive ? 'var(--warning)' : 'var(--bd-1)' }}';this.style.boxShadow='0 2px 8px rgba(0,0,0,.06)'">

                {{-- Status dot --}}
                <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0;
                            background:{{ $isActive ? 'var(--warning)' : 'var(--neutral-300)' }};
                            {{ $isActive ? 'box-shadow:0 0 0 3px #FEF3C7;' : '' }}">
                </div>

                {{-- Info --}}
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                        <span style="font-size:15px;font-weight:700;color:var(--fg-1);">
                            {{ $monthLabel }}
                        </span>
                        @if($isActive)
                            <span class="chip chip-success" style="font-size:10px;">Bulan ini</span>
                        @endif
                    </div>
                    <div style="font-size:11px;color:var(--fg-4);display:flex;gap:10px;flex-wrap:wrap;margin-bottom:{{ $totalEntries > 0 ? '6px' : '0' }};">
                        <span>👤 {{ $staffCount }} staf</span>
                        <span>📋 {{ $monthTargets->count() }} target</span>
                        @if($totalEntries > 0)
                            <span>{{ $doneEntries }}/{{ $totalEntries }} laporan selesai</span>
                        @else
                            <span>Belum ada laporan</span>
                        @endif
                    </div>
                    {{-- Progress bar --}}
                    @if($totalEntries > 0)
                        <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                            <div style="height:100%;width:{{ $pct }}%;border-radius:4px;transition:width .4s;
                                        background:{{ $pct >= 80 ? '#16A571' : 'var(--warning)' }};"></div>
                        </div>
                        <div style="font-size:10px;color:{{ $pct >= 80 ? '#16A571' : 'var(--fg-3)' }};margin-top:3px;font-weight:600;">
                            {{ $pct }}% selesai
                        </div>
                    @endif
                </div>

                {{-- Chevron --}}
                <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24">
                    <path d="M9 6l6 6-6 6"/>
                </svg>
            </a>

        @endforeach

        </div>

    @endif

    {{-- Pagination --}}
    @if($targets->hasPages())
    <div style="padding:8px 0 4px;display:flex;flex-direction:column;align-items:center;gap:10px;">
        <div style="font-size:12px;color:var(--fg-3);">
            Menampilkan {{ $targets->firstItem() }}–{{ $targets->lastItem() }} dari {{ $targets->total() }} target
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;">
            @if($targets->onFirstPage())
                <span style="padding:6px 14px;border-radius:8px;background:var(--neutral-100);color:var(--fg-4);font-size:13px;font-weight:600;cursor:default;">← Sebelumnya</span>
            @else
                <a href="{{ $targets->previousPageUrl() }}" style="padding:6px 14px;border-radius:8px;background:var(--neutral-50);border:1px solid var(--neutral-200);color:var(--maxy-navy);font-size:13px;font-weight:600;text-decoration:none;">← Sebelumnya</a>
            @endif

            @foreach($targets->getUrlRange(max(1,$targets->currentPage()-2), min($targets->lastPage(),$targets->currentPage()+2)) as $page => $url)
                @if($page == $targets->currentPage())
                    <span style="padding:6px 12px;border-radius:8px;background:var(--maxy-navy);color:#fff;font-size:13px;font-weight:700;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;background:var(--neutral-50);border:1px solid var(--neutral-200);color:var(--fg-2);font-size:13px;font-weight:600;text-decoration:none;">{{ $page }}</a>
                @endif
            @endforeach

            @if($targets->hasMorePages())
                <a href="{{ $targets->nextPageUrl() }}" style="padding:6px 14px;border-radius:8px;background:var(--neutral-50);border:1px solid var(--neutral-200);color:var(--maxy-navy);font-size:13px;font-weight:600;text-decoration:none;">Berikutnya →</a>
            @else
                <span style="padding:6px 14px;border-radius:8px;background:var(--neutral-100);color:var(--fg-4);font-size:13px;font-weight:600;cursor:default;">Berikutnya →</span>
            @endif
        </div>
    </div>
    @endif

</div>
</x-app-layout>
