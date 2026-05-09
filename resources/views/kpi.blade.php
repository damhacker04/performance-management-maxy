<x-app-layout>
@php
    $user      = auth()->user();
    $isCLevel  = $user->role === 'c_level';

    $depts = [
        'sales'        => ['label' => 'Sales',        'color' => '#2F6BD6'],
        'marketing'    => ['label' => 'Marketing',    'color' => '#B43BB7'],
        'product_it'   => ['label' => 'Product & IT', 'color' => '#16A571'],
        'operational'  => ['label' => 'Operational',  'color' => '#E89B2A'],
    ];

    $now = now();

    // Hitung per departemen
    $deptData = [];
    foreach ($depts as $key => $info) {
        $staffCount    = \App\Models\User::where('department', $key)->where('role', 'staff')->count();
        $reportedToday = \App\Models\DailyTaskEntry::whereHas('user', fn($q) => $q->where('department', $key))
                            ->whereDate('task_date', today())->distinct('user_id')->count('user_id');
        $monthEntries  = \App\Models\DailyTaskEntry::whereHas('user', fn($q) => $q->where('department', $key))
                            ->whereMonth('task_date', $now->month)->whereYear('task_date', $now->year)->count();
        $monthTargets  = \App\Models\MonthlyTarget::where('department', $key)
                            ->where('month', $now->month)->where('year', $now->year)->count();
        $pct = $staffCount > 0 ? min(round($reportedToday / $staffCount * 100), 100) : 0;

        // Jika leader, hanya tampilkan dept sendiri
        if (!$isCLevel && $key !== $user->department) continue;

        $deptData[$key] = array_merge($info, [
            'staff'         => $staffCount,
            'reportedToday' => $reportedToday,
            'monthEntries'  => $monthEntries,
            'monthTargets'  => $monthTargets,
            'pct'           => $pct,
        ]);
    }

    $totalStaff    = array_sum(array_column($deptData, 'staff'));
    $totalReported = array_sum(array_column($deptData, 'reportedToday'));
    $avgPct        = count($deptData) > 0 ? round(array_sum(array_column($deptData, 'pct')) / count($deptData)) : 0;
@endphp

<div class="page">

    <!-- Header -->
    <div>
        <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">KPI Organisasi</h1>
        <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
            Minggu ke-{{ $now->weekOfYear }} · {{ $now->format('M Y') }}
        </p>
    </div>

    <!-- Summary cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Rata-rata KPI</span>
                <span class="chip chip-{{ $avgPct >= 80 ? 'success' : ($avgPct >= 50 ? 'warning' : 'danger') }}">
                    {{ $avgPct >= 80 ? 'Baik' : ($avgPct >= 50 ? 'Cukup' : 'Perlu perhatian') }}
                </span>
            </div>
            <div class="kc-value">{{ $avgPct }}<span class="kc-sub">%</span></div>
            <div class="progress-bar"><i class="navy" style="width:{{ $avgPct }}%"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Laporan hari ini</span>
                <span class="chip chip-{{ $totalReported >= $totalStaff ? 'success' : 'warning' }}">
                    {{ $totalReported }}/{{ $totalStaff }}
                </span>
            </div>
            <div class="kc-value">{{ $totalStaff > 0 ? round($totalReported/$totalStaff*100) : 0 }}<span class="kc-sub">%</span></div>
            <div class="progress-bar"><i class="success" style="width:{{ $totalStaff > 0 ? round($totalReported/$totalStaff*100) : 0 }}%"></i></div>
        </div>
    </div>

    <!-- Per departemen -->
    <div class="m-card">
        <div class="overline-label" style="margin-bottom:16px;">Per departemen</div>
        <div style="display:flex;flex-direction:column;gap:18px;">
            @foreach ($deptData as $key => $dept)
                <div class="dept-row">
                    <div class="dept-row-header">
                        <div class="dn">
                            <span class="dept-dot" style="background:{{ $dept['color'] }};"></span>
                            {{ $dept['label'] }}
                        </div>
                        <div class="dd">
                            <span style="font-size:12px;color:var(--fg-3);">
                                {{ $dept['reportedToday'] }}/{{ $dept['staff'] }} staff
                            </span>
                            <span style="font-size:14px;font-weight:700;color:{{ $dept['pct'] >= 80 ? 'var(--success)' : ($dept['pct'] >= 50 ? 'var(--warning)' : 'var(--danger)') }};">
                                {{ $dept['pct'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <i style="width:{{ $dept['pct'] }}%;background:{{ $dept['color'] }};"></i>
                    </div>
                    <div style="display:flex;gap:12px;margin-top:4px;">
                        <span style="font-size:11px;color:var(--fg-3);">
                            {{ $dept['monthTargets'] }} target aktif
                        </span>
                        <span style="font-size:11px;color:var(--fg-3);">
                            · {{ $dept['monthEntries'] }} laporan bulan ini
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Laporan terbaru per dept -->
    @foreach ($deptData as $key => $dept)
        @php
            $recentEntries = \App\Models\DailyTaskEntry::with(['user','monthlyTarget'])
                ->whereHas('user', fn($q) => $q->where('department', $key))
                ->orderByDesc('task_date')->orderByDesc('created_at')
                ->take(3)->get();
        @endphp
        @if ($recentEntries->isNotEmpty())
            <div class="m-card" style="padding:0;">
                <div class="section-head">
                    <span class="overline-label">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $dept['color'] }};margin-right:6px;vertical-align:middle;"></span>
                        {{ $dept['label'] }}
                    </span>
                    <span style="font-size:12px;color:var(--fg-3);">3 terbaru</span>
                </div>
                <div style="padding:0 16px 8px;">
                    @foreach ($recentEntries as $entry)
                        @php
                            $statusMap  = ['selesai'=>'success','dalam_proses'=>'warning','terhambat'=>'danger'];
                            $sChip      = $statusMap[$entry->status] ?? 'neutral';
                            $sLabel     = ['selesai'=>'Selesai','dalam_proses'=>'Dalam Proses','terhambat'=>'Terhambat'][$entry->status] ?? $entry->status;
                        @endphp
                        <div class="m-row">
                            <div class="row-body">
                                <div class="row-title">{{ Str::limit($entry->task_description, 40) }}</div>
                                <div class="row-meta">
                                    <span class="chip chip-{{ $sChip }}">{{ $sLabel }}</span>
                                    <span>· {{ $entry->user->name }}</span>
                                    <span>· {{ \Carbon\Carbon::parse($entry->task_date)->format('d M') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

</div>
</x-app-layout>
