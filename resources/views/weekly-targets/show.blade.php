<x-app-layout>
@php
    $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    [$rStart, $rEnd] = \App\Models\WeeklyTarget::WEEK_RANGES[$weeklyTarget->week_number] ?? [1, 7];

    $statusMap = [
        'belum_mulai'  => 'neutral',
        'dalam_proses' => 'warning',
        'terhambat'    => 'danger',
        'selesai'      => 'success',
    ];

    $completionPct = $summary['total'] > 0
        ? round(($summary['selesai'] / $summary['total']) * 100)
        : 0;
@endphp

<div class="page">
    <!-- Back & Header -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('weekly-targets.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                <span class="chip chip-neutral">Minggu {{ $weeklyTarget->week_number }}</span>
                <span style="font-size:11px;color:var(--fg-4);">{{ $rStart }}–{{ $rEnd }} {{ $months[$weeklyTarget->month] }} {{ $weeklyTarget->year }}</span>
            </div>
            <h1 style="font-size:17px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.3;">{{ $weeklyTarget->title }}</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">
                @if($weeklyTarget->monthlyTarget)
                    ↳ {{ $weeklyTarget->monthlyTarget->title }}
                @else
                    <span style="color:#B45309;font-weight:600;">🗂️ Other</span> — tidak terikat target bulanan
                @endif
            </p>
        </div>
        <a href="{{ route('weekly-targets.edit', $weeklyTarget) }}" class="icon-btn" title="Edit">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </a>
    </div>

    <!-- Target info card -->
    <div class="m-card">
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:8px;">
            @if($weeklyTarget->target_type === 'quantitative')
                <span class="chip chip-info">Target: {{ $weeklyTarget->target_label }}</span>
            @else
                <span class="chip chip-neutral">Kualitatif</span>
            @endif
        </div>
        @if($weeklyTarget->description)
            <p style="font-size:13px;color:var(--fg-2);line-height:1.5;margin:0;">
                {{ $weeklyTarget->description }}
            </p>
        @endif
    </div>

    <!-- KPI summary -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Total laporan</span>
                <span class="chip chip-info">{{ $byStaff->count() }} staff</span>
            </div>
            <div class="kc-value">{{ $summary['total'] }}<span class="kc-sub"> task</span></div>
            <div class="progress-bar"><i class="navy" style="width:{{ min($summary['total']*10, 100) }}%"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Selesai</span>
                <span class="chip {{ $completionPct >= 80 ? 'chip-success' : ($completionPct >= 50 ? 'chip-warning' : 'chip-danger') }}">
                    {{ $completionPct }}%
                </span>
            </div>
            <div class="kc-value">{{ $summary['selesai'] }}<span class="kc-sub"> / {{ $summary['total'] }}</span></div>
            <div class="progress-bar"><i class="success" style="width:{{ $completionPct }}%"></i></div>
        </div>
    </div>

    @if($summary['terhambat'] > 0)
        <div class="m-card" style="background:#FDECEE;border:1px solid var(--danger);padding:12px 16px;display:flex;gap:10px;align-items:flex-start;">
            <svg class="lucide sm" style="color:var(--danger);flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <div style="font-size:13px;color:var(--fg-1);">
                <strong>{{ $summary['terhambat'] }} task terhambat</strong> — perlu perhatian khusus
            </div>
        </div>
    @endif

    <!-- Daily tasks per staff -->
    @if($dailyTasks->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p style="font-size:14px;color:var(--fg-2);margin-bottom:4px;">Belum ada laporan staff</p>
                <p style="font-size:12px;color:var(--fg-3);">Laporan staff akan muncul di sini saat mereka submit untuk target ini.</p>
            </div>
        </div>
    @else
        @foreach($byStaff as $userId => $entries)
            @php
                $staff = $entries->first()->user;
                $staffDone = $entries->where('status', 'selesai')->count();
                $initials = collect(explode(' ', $staff->name))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
            @endphp
            <div class="m-card" style="padding:0;">
                <div class="section-head" style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--maxy-navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                        {{ $initials }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:700;color:var(--fg-1);">{{ $staff->name }}</div>
                        <div style="font-size:11px;color:var(--fg-3);">
                            {{ $staffDone }}/{{ $entries->count() }} selesai
                        </div>
                    </div>
                </div>
                <div style="padding:0 16px 8px;">
                    @foreach($entries as $entry)
                        @php $sChip = $statusMap[$entry->status] ?? 'neutral'; @endphp
                        <div class="m-row">
                            <span class="m-checkbox {{ $entry->status === 'selesai' ? 'done' : '' }}" aria-hidden="true">
                                @if($entry->status === 'selesai')
                                    <svg style="width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 16 16"><path d="M3 8l3.5 3.5L13 5"/></svg>
                                @endif
                            </span>
                            <div class="row-body">
                                <div class="row-title">
                                    {{ $entry->task_description }}
                                    @if($entry->is_overdue)
                                        <span class="chip chip-danger" style="margin-left:6px;font-size:10px;">⏰ Terlambat</span>
                                    @endif
                                </div>
                                <div class="row-meta">
                                    <span class="chip chip-{{ $sChip }}">{{ $entry->status_label }}</span>
                                    <span>· {{ \Carbon\Carbon::parse($entry->task_date)->format('d M') }}</span>
                                    <span>· {{ $entry->duration_label }}</span>
                                </div>
                                @if($entry->notes)
                                    <div style="font-size:11px;color:var(--fg-3);margin-top:4px;font-style:italic;background:var(--bg-2);padding:6px 8px;border-radius:6px;border-left:2px solid var(--fg-4);">
                                        "{{ $entry->notes }}"
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
</x-app-layout>
