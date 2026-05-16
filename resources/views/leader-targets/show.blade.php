<x-app-layout>
@php
    $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    $statusMap = [
        'belum_mulai'  => 'neutral',
        'dalam_proses' => 'warning',
        'terhambat'    => 'danger',
        'selesai'      => 'success',
    ];

    $pct = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
@endphp

<div class="page">

    <!-- Back & Header -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('leader-targets.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            {{-- Breadcrumb konteks --}}
            <div style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
                        color:var(--maxy-navy);opacity:.6;margin-bottom:5px;">
                Target dari C-Level
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                <span class="chip chip-neutral">{{ $monthNames[$monthlyTarget->month] }} {{ $monthlyTarget->year }}</span>
                <span class="chip chip-dept-{{ str_replace('_','-', $monthlyTarget->department) }}">
                    {{ ucfirst(str_replace('_',' ', $monthlyTarget->department)) }}
                </span>
            </div>
            <h1 style="font-size:17px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.3;">
                {{ $monthlyTarget->title }}
            </h1>
        </div>
    </div>

    <!-- Deskripsi target bulanan -->
    @if($monthlyTarget->description)
        <div class="m-card" style="font-size:13px;color:var(--fg-2);line-height:1.6;background:var(--bg-2);border:1px solid var(--bg-3);">
            {{ $monthlyTarget->description }}
        </div>
    @endif

    <!-- KPI: progress laporan saya bulan ini -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Laporanku</span>
                <span class="chip chip-info">{{ $monthShort[$monthlyTarget->month] }} {{ $monthlyTarget->year }}</span>
            </div>
            <div class="kc-value">{{ $totalTasks }}<span class="kc-sub"> laporan</span></div>
            <div class="progress-bar"><i class="navy" style="width:{{ min($totalTasks * 10, 100) }}%"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Selesai</span>
                <span class="chip {{ $pct >= 80 ? 'chip-success' : ($pct >= 40 ? 'chip-warning' : 'chip-neutral') }}">
                    {{ $pct }}%
                </span>
            </div>
            <div class="kc-value">{{ $doneTasks }}<span class="kc-sub"> / {{ $totalTasks }}</span></div>
            <div class="progress-bar"><i class="success" style="width:{{ $pct }}%"></i></div>
        </div>
    </div>

    <!-- Breakdown per weekly target -->
    {{-- Section label TARGET MINGGUAN --}}
    <div style="display:flex;align-items:center;gap:8px;">
        <span class="overline-label" style="display:flex;align-items:center;gap:5px;">
            <svg style="width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24">
                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Target Mingguan
        </span>
        @if($monthlyTarget->weeklyTargets->isNotEmpty())
            <span class="chip chip-neutral" style="font-size:10px;">{{ $monthlyTarget->weeklyTargets->count() }} minggu</span>
        @endif
    </div>

    @if($monthlyTarget->weeklyTargets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p style="font-size:14px;color:var(--fg-2);margin-bottom:4px;">Belum ada target mingguan</p>
                <p style="font-size:12px;color:var(--fg-3);">C-Level akan menambahkan target mingguan untuk bulan ini.</p>
            </div>
        </div>
    @else
        @foreach($monthlyTarget->weeklyTargets as $wt)
            @php
                [$rStart, $rEnd] = \App\Models\WeeklyTarget::WEEK_RANGES[$wt->week_number] ?? [1, 7];
                $myTasks = $dailyTasksByWeek->get($wt->id, collect());
                $myDone  = $myTasks->where('status', 'selesai')->count();
                $myTotal = $myTasks->count();

                $today = now();
                $currentWeek = match(true) {
                    $today->day <= 7  => 1,
                    $today->day <= 14 => 2,
                    $today->day <= 21 => 3,
                    $today->day <= 28 => 4,
                    default           => 5,
                };
                $isActiveWeek = $wt->month == $today->month
                    && $wt->year == $today->year
                    && $wt->week_number == $currentWeek;
            @endphp

            <div class="m-card" style="padding:0;{{ $isActiveWeek ? 'border:1.5px solid var(--maxy-navy);' : '' }}">

                <!-- Header weekly target -->
                <div style="padding:14px 16px 12px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                                <span class="chip chip-neutral" style="font-weight:700;font-size:11px;">
                                    Minggu {{ $wt->week_number }}
                                </span>
                                <span style="font-size:11px;color:var(--fg-4);">
                                    {{ $rStart }}–{{ $rEnd }} {{ $monthShort[$wt->month] }} {{ $wt->year }}
                                </span>
                                @if($isActiveWeek)
                                    <span class="chip chip-success" style="font-size:10px;">Minggu ini</span>
                                @endif
                                @if($wt->target_type === 'quantitative')
                                    <span class="chip chip-info" style="font-size:10px;">{{ $wt->target_label }}</span>
                                @else
                                    <span class="chip chip-neutral" style="font-size:10px;">Kualitatif</span>
                                @endif
                            </div>
                            <div style="font-size:14px;font-weight:600;color:var(--fg-1);line-height:1.4;">
                                {{ $wt->title }}
                            </div>
                            @if($wt->description)
                                <p style="font-size:12px;color:var(--fg-3);margin:4px 0 0;line-height:1.5;">
                                    {{ Str::limit($wt->description, 100) }}
                                </p>
                            @endif
                        </div>

                        <!-- Badge progres -->
                        @if($myTotal > 0)
                            <span class="chip {{ $myDone === $myTotal ? 'chip-success' : 'chip-warning' }}"
                                  style="flex-shrink:0;font-size:11px;white-space:nowrap;">
                                {{ $myDone }}/{{ $myTotal }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Daftar laporan saya untuk minggu ini -->
                @if($myTasks->isNotEmpty())
                    <div style="border-top:1px solid var(--bg-3);padding:4px 16px 8px;">
                        @foreach($myTasks as $entry)
                            @php $sChip = $statusMap[$entry->status] ?? 'neutral'; @endphp
                            <a href="{{ route('daily-tasks.show', $entry->id) }}"
                               class="m-row"
                               style="text-decoration:none;color:inherit;padding:8px 0;">
                                <span class="m-checkbox {{ $entry->status === 'selesai' ? 'done' : '' }}" aria-hidden="true">
                                    @if($entry->status === 'selesai')
                                        <svg style="width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 16 16"><path d="M3 8l3.5 3.5L13 5"/></svg>
                                    @endif
                                </span>
                                <div class="row-body">
                                    <div class="row-title">{{ $entry->task_description }}</div>
                                    <div class="row-meta">
                                        <span class="chip chip-{{ $sChip }}">{{ $entry->status_label }}</span>
                                        <span>· {{ \Carbon\Carbon::parse($entry->task_date)->format('d M') }}</span>
                                        <span>· {{ $entry->duration_label }}</span>
                                    </div>
                                    @if($entry->notes && $entry->status === 'terhambat')
                                        <div style="font-size:11px;color:var(--fg-3);margin-top:4px;font-style:italic;border-left:2px solid var(--danger);padding-left:8px;">
                                            {{ Str::limit($entry->notes, 80) }}
                                        </div>
                                    @endif
                                </div>
                                <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- CTA: Tambah laporan untuk minggu ini -->
                <div style="padding:{{ $myTasks->isNotEmpty() ? '4px' : '10px' }} 12px 12px;">
                    <a href="{{ route('daily-tasks.create', ['weekly_target_id' => $wt->id]) }}"
                       class="btn btn-primary btn-block"
                       style="font-size:13px;padding:9px 14px;">
                        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah Laporan — Minggu {{ $wt->week_number }}
                    </a>
                </div>
            </div>
        @endforeach
    @endif

</div>
</x-app-layout>
