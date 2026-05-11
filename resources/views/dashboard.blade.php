<x-app-layout>
@php
    $user = auth()->user();
@endphp

<div class="page">
    <!-- Hero greeting -->
    <div class="hero-greeting">
        <img src="{{ asset('images/m-logo.png') }}" class="watermark" alt="" />
        <div class="hl-label">Hari ini · {{ now()->format('d M Y') }}</div>
        <div class="hl-display">Halo, {{ explode(' ', $user->name)[0] }}</div>
        @if ($user->role === 'staff')
            <div class="hl-sub">Catat laporan tugas harianmu dan pantau progressmu.</div>
        @elseif ($user->role === 'leader')
            <div class="hl-sub">Pantau target dan kinerja tim {{ ucfirst(str_replace('_', ' ', $user->department)) }}.</div>
        @else
            <div class="hl-sub">Selamat datang, pantau KPI seluruh organisasi.</div>
        @endif
    </div>

    @if ($user->role === 'staff')
        @php
            $todayEntries = \App\Models\DailyTaskEntry::where('user_id', $user->id)
                ->whereDate('task_date', today())
                ->with('monthlyTarget')
                ->orderByDesc('created_at')
                ->get();
            $done  = $todayEntries->where('status', 'selesai')->count();
            $total = $todayEntries->count();
            $pct   = $total > 0 ? round(($done / $total) * 100) : 0;

            $activeTargets = \App\Models\MonthlyTarget::where('department', $user->department)
                ->where('month', now()->month)
                ->where('year', now()->year)
                ->orderByDesc('created_at')
                ->get();
        @endphp

        <!-- KPI grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kc-header">
                    <span class="kc-title">Laporan hari ini</span>
                    <span class="chip {{ $pct === 100 ? 'chip-success' : 'chip-warning' }}">
                        {{ $pct === 100 ? 'Selesai' : 'Berjalan' }}
                    </span>
                </div>
                <div class="kc-value">{{ $done }}<span class="kc-sub"> / {{ $total }}</span></div>
                <div class="progress-bar"><i class="navy" style="width:{{ $pct }}%"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kc-header">
                    <span class="kc-title">Total laporan</span>
                    <span class="chip chip-info">Bulan ini</span>
                </div>
                @php $monthTotal = \App\Models\DailyTaskEntry::where('user_id', $user->id)->whereMonth('task_date', now()->month)->whereYear('task_date', now()->year)->count(); @endphp
                <div class="kc-value">{{ $monthTotal }}<span class="kc-sub"> tugas</span></div>
                <div class="progress-bar"><i style="width:{{ min($monthTotal * 5, 100) }}%"></i></div>
            </div>
        </div>

        <!-- Target bulan ini -->
        <div class="m-card" style="padding:0;">
            <div class="section-head">
                <span class="overline-label">Target bulan ini</span>
                <span style="font-size:12px;font-weight:600;color:var(--maxy-navy);">{{ now()->format('M Y') }}</span>
            </div>
            <div style="padding:0 16px 8px;">
                @forelse ($activeTargets as $target)
                    <div class="m-row">
                        <div class="row-body">
                            <div class="row-title">{{ $target->title }}</div>
                            <div class="row-meta">
                                <span class="chip chip-dept-{{ str_replace('_','-',$target->department) }}">{{ ucfirst(str_replace('_',' ', $target->department)) }}</span>
                                @if($target->description)
                                    <span>· {{ Str::limit($target->description, 40) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Leader belum membuat target untuk bulan ini.</div>
                @endforelse
            </div>
        </div>

        <!-- Today's task list -->
        <div class="m-card" style="padding:0;">
            <div class="section-head">
                <span class="overline-label">Tugas hari ini</span>
                <span style="font-size:12px;font-weight:600;color:var(--maxy-navy);">{{ $done }}/{{ $total }} selesai</span>
            </div>
            <div style="padding:0 16px 8px;">
                @forelse ($todayEntries as $entry)
                    @php
                        $statusMap  = ['selesai'=>'success','dalam_proses'=>'warning','terhambat'=>'danger'];
                        $sChip      = $statusMap[$entry->status] ?? 'neutral';
                        $sLabel     = ['selesai'=>'Selesai','dalam_proses'=>'Dalam Proses','terhambat'=>'Terhambat'][$entry->status] ?? $entry->status;
                        $durLabel   = $entry->duration_minutes >= 60 && $entry->duration_minutes % 60 === 0
                            ? ($entry->duration_minutes / 60) . 'j'
                            : $entry->duration_minutes . 'm';
                    @endphp
                    <div class="m-row">
                        @if($entry->status === 'selesai')
                            <span class="m-checkbox done" aria-hidden="true">
                                <svg style="width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 16 16"><path d="M3 8l3.5 3.5L13 5"/></svg>
                            </span>
                        @else
                            <form method="POST" action="{{ route('daily-tasks.complete', $entry->id) }}"
                                  style="display:inline;margin:0;padding:0;flex-shrink:0;"
                                  onsubmit="return confirm('Apakah tugas ini sudah benar-benar selesai? Status tidak bisa diubah lagi setelah dikonfirmasi.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="m-checkbox"
                                        style="padding:0;font:inherit;color:inherit;"
                                        title="Tandai selesai" aria-label="Tandai tugas selesai"></button>
                            </form>
                        @endif
                        <div class="row-body">
                            <div class="row-title">{{ $entry->task_description }}</div>
                            <div class="row-meta">
                                <span class="chip chip-{{ $sChip }}">{{ $sLabel }}</span>
                                @if($entry->monthlyTarget)
                                    <span>· {{ Str::limit($entry->monthlyTarget->title, 24) }}</span>
                                @endif
                                <span>· {{ $durLabel }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada tugas hari ini. Tambahkan laporan pertamamu.</div>
                @endforelse
            </div>
        </div>

        @if(session('success'))
            <div class="m-card" style="background:#E8F7EE;border:1px solid #16A571;color:#0F7A50;padding:12px 16px;font-size:13px;font-weight:600;">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('daily-tasks.create') }}" class="btn btn-primary btn-block">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah laporan harian
        </a>

    @elseif ($user->role === 'leader')
        @php
            $targets     = \App\Models\MonthlyTarget::where('user_id', $user->id)->where('month', now()->month)->where('year', now()->year)->withCount('dailyTaskEntries')->get();
            $totalStaff  = \App\Models\User::where('department', $user->department)->where('role', 'staff')->count();
            $reported    = \App\Models\DailyTaskEntry::whereHas('user', fn($q) => $q->where('department', $user->department))->whereDate('task_date', today())->distinct('user_id')->count('user_id');
        @endphp

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kc-header">
                    <span class="kc-title">Target bulan ini</span>
                    <span class="chip chip-info">{{ now()->format('M Y') }}</span>
                </div>
                <div class="kc-value">{{ $targets->count() }}<span class="kc-sub"> target</span></div>
                <div class="progress-bar"><i style="width:{{ min($targets->count() * 25, 100) }}%"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kc-header">
                    <span class="kc-title">Staff laporan</span>
                    <span class="chip {{ $reported >= $totalStaff ? 'chip-success' : 'chip-warning' }}">
                        {{ $reported >= $totalStaff ? 'Lengkap' : 'Kurang' }}
                    </span>
                </div>
                <div class="kc-value">{{ $reported }}<span class="kc-sub"> / {{ $totalStaff }}</span></div>
                <div class="progress-bar"><i class="success" style="width:{{ $totalStaff > 0 ? round($reported/$totalStaff*100) : 0 }}%"></i></div>
            </div>
        </div>

        <div class="m-card" style="padding:0;">
            <div class="section-head">
                <span class="overline-label">Target aktif</span>
                <a href="{{ route('monthly-targets.index') }}" class="more-link">Lihat semua</a>
            </div>
            <div style="padding:0 16px 8px;">
                @forelse ($targets->take(3) as $target)
                    <div class="m-row">
                        <div class="row-body">
                            <div class="row-title">{{ $target->title }}</div>
                            <div class="row-meta">
                                <span class="chip chip-dept-{{ str_replace('_','-',$target->department) }}">{{ ucfirst(str_replace('_',' ', $target->department)) }}</span>
                                <span>· {{ $target->daily_task_entries_count }} laporan</span>
                            </div>
                        </div>
                        <svg class="lucide sm" style="color:var(--fg-3);" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                    </div>
                @empty
                    <div class="empty-state">Belum ada target bulan ini.</div>
                @endforelse
            </div>
        </div>

        <a href="{{ route('monthly-targets.create') }}" class="btn btn-primary btn-block">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah target baru
        </a>

    @else
        {{-- C-Level --}}
        @php
            $depts       = ['sales'=>'Sales','marketing'=>'Marketing','product_it'=>'Product & IT','operational'=>'Operational'];
            $deptColors  = ['sales'=>'#2F6BD6','marketing'=>'#B43BB7','product_it'=>'#16A571','operational'=>'#E89B2A'];
            $totalTargets = \App\Models\MonthlyTarget::where('month', now()->month)->where('year', now()->year)->count();
            $totalEntries = \App\Models\DailyTaskEntry::whereDate('task_date', today())->count();
        @endphp

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kc-header">
                    <span class="kc-title">Target aktif</span>
                    <span class="chip chip-info">{{ now()->format('M Y') }}</span>
                </div>
                <div class="kc-value">{{ $totalTargets }}<span class="kc-sub"> target</span></div>
                <div class="progress-bar"><i style="width:{{ min($totalTargets * 10, 100) }}%"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kc-header">
                    <span class="kc-title">Laporan hari ini</span>
                    <span class="chip chip-success">All dept</span>
                </div>
                <div class="kc-value">{{ $totalEntries }}<span class="kc-sub"> laporan</span></div>
                <div class="progress-bar"><i class="success" style="width:{{ min($totalEntries * 5, 100) }}%"></i></div>
            </div>
        </div>

        <div class="m-card">
            <div class="overline-label" style="margin-bottom:16px;">Per departemen — hari ini</div>
            <div style="display:flex;flex-direction:column;gap:16px;">
                @foreach ($depts as $key => $label)
                    @php
                        $count      = \App\Models\DailyTaskEntry::whereHas('user', fn($q) => $q->where('department', $key))->whereDate('task_date', today())->count();
                        $staffCount = \App\Models\User::where('department', $key)->where('role', 'staff')->count();
                        $pct        = $staffCount > 0 ? min(round($count / $staffCount * 100), 100) : 0;
                    @endphp
                    <div class="dept-row">
                        <div class="dept-row-header">
                            <div class="dn">
                                <span class="dept-dot" style="background:{{ $deptColors[$key] }};"></span>
                                {{ $label }}
                            </div>
                            <div class="dd">
                                <span style="font-size:12px;color:var(--fg-3);">{{ $count }}/{{ $staffCount }}</span>
                                <span style="font-size:13px;font-weight:600;color:var(--fg-2);">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="progress-bar"><i style="width:{{ $pct }}%;background:{{ $deptColors[$key] }};"></i></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</x-app-layout>
