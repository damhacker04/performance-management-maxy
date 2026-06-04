<x-app-layout>
@php
    $user = auth()->user();
@endphp

<div class="page">
    <style>
        .rotate-180 { transform: rotate(180deg); }
    </style>
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
                ->with(['monthlyTarget', 'weeklyTarget'])
                ->orderByDesc('created_at')
                ->get();
            $done  = $todayEntries->where('status', 'selesai')->count();
            $total = $todayEntries->count();
            $pct   = $total > 0 ? round(($done / $total) * 100) : 0;

            // Laporan yang perlu direvisi (diminta leader)
            $revisionEntries = \App\Models\DailyTaskEntry::where('user_id', $user->id)
                ->where('verification_status', 'revision')
                ->orderByDesc('reviewed_at')
                ->get();

            // Laporan yang sudah direvisi staff, menunggu persetujuan leader
            $pendingAfterRevision = \App\Models\DailyTaskEntry::where('user_id', $user->id)
                ->where('verification_status', 'pending')
                ->whereNotNull('reviewed_at')
                ->orderByDesc('updated_at')
                ->get();

            // Laporan yang baru saja disetujui (berdasarkan Notifikasi Lonceng)
            $recentApprovedNotifs = \App\Models\AppNotification::where('user_id', $user->id)
                ->where('type', 'report_approved')
                ->where(function($q) {
                    $q->whereNull('read_at') // Tampilkan selamanya sampai dibaca
                      ->orWhere('created_at', '>=', now()->subDays(2)); // Kalau sudah dibaca, tahan selama 2 hari di dashboard
                })
                ->orderByDesc('created_at')
                ->get();

            // Tugas yang belum selesai lebih dari 14 hari
            $overdueUnfinished = \App\Models\DailyTaskEntry::with('weeklyTarget')
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['selesai'])
                ->whereDate('task_date', '<=', today()->subDays(14))
                ->orderByDesc('task_date')
                ->get();

            // Tentukan minggu aktif berdasarkan tanggal hari ini
            $today = now()->day;
            $currentWeek = match(true) {
                $today <= 7  => 1,
                $today <= 14 => 2,
                $today <= 21 => 3,
                $today <= 28 => 4,
                default      => 5,
            };

            // Include "Other" weekly targets (monthly_target_id null) yang dibuat oleh user dept ini
            $activeWeeklyTargets = \App\Models\WeeklyTarget::with('monthlyTarget')
                ->where(function ($q) use ($user) {
                    $q->whereHas('monthlyTarget', fn($mq) => $mq->where('department', $user->department))
                      ->orWhere(function ($oq) use ($user) {
                          $oq->whereNull('monthly_target_id')
                             ->whereHas('user', fn($uq) => $uq->where('department', $user->department));
                      });
                })
                ->where(function ($q) use ($user) {
                    $q->whereNull('assigned_to')
                      ->orWhere('assigned_to', $user->id);
                })
                ->where('month', now()->month)
                ->where('year', now()->year)
                ->where('week_number', $currentWeek)
                ->get();
        @endphp

        {{-- ===================== NOTIFIKASI ===================== --}}
        
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
            {{-- Banner oranye: Tugas BELUM SELESAI >2 minggu --}}
            @if($overdueUnfinished->isNotEmpty())
                <div style="background:#FFF3E8;border:1.5px solid #F97316;border-radius:12px;padding:14px 16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" onclick="document.getElementById('overdue-accordion-body').classList.toggle('hidden')">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:15px;">⏰</span>
                            <span style="font-size:12px;font-weight:700;color:#C2410C;">Tugas Belum Selesai (&gt;2 Minggu)</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="chip" style="background:#F97316;color:#fff;font-size:10px;border:none;">{{ $overdueUnfinished->count() }} tugas</span>
                            <svg class="lucide sm" viewBox="0 0 24 24" style="color:#C2410C;"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                    </div>
                    <div id="overdue-accordion-body" class="hidden" style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
                        @foreach($overdueUnfinished as $od)
                            <a href="{{ route('daily-tasks.show', $od->id) }}"
                               style="display:flex;align-items:center;justify-content:space-between;
                                      background:#fff;border:1px solid #FED7AA;border-radius:8px;
                                      padding:8px 10px;text-decoration:none;color:inherit;">
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:var(--fg-1);margin-bottom:2px;">
                                        {{ Str::limit($od->task_description, 38) }}
                                    </div>
                                    <div style="font-size:11px;color:#C2410C;">
                                        {{ \Carbon\Carbon::parse($od->task_date)->isoFormat('D MMM') }}
                                        · {{ $od->status_label }}
                                        · {{ \Carbon\Carbon::parse($od->task_date)->diffForHumans() }}
                                    </div>
                                </div>
                                <svg class="lucide sm" viewBox="0 0 24 24" style="color:#F97316;"><path d="M9 18l6-6-6-6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Banner hijau: Laporan yang BARU DISETUJUI --}}
            @if($recentApprovedNotifs->isNotEmpty())
                @php
                    $unreadCount = $recentApprovedNotifs->filter(fn($n) => !$n->isRead())->count();
                    $hasUnread = $unreadCount > 0;
                    $mainBg = $hasUnread ? '#E8F7F4' : '#F8FAFC';
                    $mainBorder = $hasUnread ? '#16A571' : '#E2E8F0';
                    $mainText = $hasUnread ? '#0F7A50' : '#64748B';
                    $chipBg = $hasUnread ? '#16A571' : '#94A3B8';
                @endphp
                <div style="background:{{ $mainBg }};border:1.5px solid {{ $mainBorder }};border-radius:12px;padding:14px 16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" onclick="document.getElementById('approved-accordion-body').classList.toggle('hidden'); document.getElementById('approved-chevron').classList.toggle('rotate-180');">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:15px;">{{ $hasUnread ? '✅' : '✔️' }}</span>
                            <span style="font-size:12px;font-weight:700;color:{{ $mainText }};">Laporan Baru Disetujui</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="chip" style="background:{{ $chipBg }};color:#fff;font-size:10px;border:none;">{{ $unreadCount > 0 ? $unreadCount.' baru' : $recentApprovedNotifs->count().' laporan' }}</span>
                            <svg id="approved-chevron" class="lucide sm" viewBox="0 0 24 24" style="color:{{ $mainText }};transition: transform 0.3s;"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                    </div>
                    
                    <div id="approved-accordion-body" class="hidden" style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
                        @foreach($recentApprovedNotifs as $appr)
                            @php
                                $isRead = $appr->isRead();
                                $itemBg = $isRead ? '#F8FAFC' : '#fff';
                                $itemBorder = $isRead ? '#E2E8F0' : '#D1FAE5';
                                $itemTitle = $isRead ? '#64748B' : 'var(--fg-1)';
                                $itemSub = $isRead ? '#94A3B8' : '#0D6A44';
                            @endphp
                            <a href="{{ route('notifications.read', $appr->id) }}"
                               style="display:flex;align-items:center;justify-content:space-between;
                                      background:{{ $itemBg }};border:1px solid {{ $itemBorder }};border-radius:8px;
                                      padding:8px 10px;text-decoration:none;color:inherit;">
                                <div>
                                    <div style="font-size:13px;font-weight:{{ $isRead ? '500' : '600' }};color:{{ $itemTitle }};margin-bottom:2px;">
                                        {{ $appr->getMeta('task_desc') }}
                                    </div>
                                    <div style="font-size:11px;color:{{ $itemSub }};">
                                        {{ \Carbon\Carbon::parse($appr->created_at)->isoFormat('D MMM') }} · Disetujui oleh {{ explode(' ', $appr->getMeta('leader_name'))[0] ?? 'Leader' }}
                                    </div>
                                </div>
                                <svg class="lucide sm" viewBox="0 0 24 24" style="color:{{ $itemSub }};"><path d="M9 18l6-6-6-6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Card merah: laporan yang HARUS direvisi --}}
        @if($revisionEntries->isNotEmpty())
            <div style="background:#FFF8E8;border:1.5px solid #FBB041;border-radius:12px;padding:14px 16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:15px;">↩</span>
                        <span style="font-size:12px;font-weight:700;color:#B45309;">Laporan Perlu Direvisi</span>
                    </div>
                    <span class="chip chip-warning" style="font-size:10px;">{{ $revisionEntries->count() }} laporan</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @foreach($revisionEntries as $rev)
                        <a href="{{ route('daily-tasks.show', $rev->id) }}"
                           style="display:flex;align-items:center;justify-content:space-between;
                                  background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;
                                  padding:8px 10px;text-decoration:none;color:inherit;">
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--fg-1);margin-bottom:2px;">
                                    {{ Str::limit($rev->task_description, 38) }}
                                </div>
                                <div style="font-size:11px;color:#8B5A00;">
                                    {{ \Carbon\Carbon::parse($rev->task_date)->isoFormat('D MMM') }}
                                    @if($rev->rejection_note)
                                        · "{{ Str::limit($rev->rejection_note, 30) }}"
                                    @endif
                                </div>
                            </div>
                            <svg class="lucide sm" style="color:#B45309;flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Card hijau: revisi sudah dikirim, menunggu persetujuan --}}
        @if($pendingAfterRevision->isNotEmpty())
            <div style="background:#E8F7F4;border:1.5px solid #16A571;border-radius:12px;padding:14px 16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:15px;">📨</span>
                        <span style="font-size:12px;font-weight:700;color:#0F7A50;">Revisi Menunggu Persetujuan</span>
                    </div>
                    <span class="chip chip-success" style="font-size:10px;">{{ $pendingAfterRevision->count() }} laporan</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @foreach($pendingAfterRevision as $rev)
                        <a href="{{ route('daily-tasks.show', $rev->id) }}"
                           style="display:flex;align-items:center;justify-content:space-between;
                                  background:#F0FDF9;border:1px solid #A7F3D0;border-radius:8px;
                                  padding:8px 10px;text-decoration:none;color:inherit;">
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--fg-1);margin-bottom:2px;">
                                    {{ Str::limit($rev->task_description, 38) }}
                                </div>
                                <div style="font-size:11px;color:#0D6A44;">
                                    Dikirim {{ $rev->updated_at->diffForHumans() }}
                                    · {{ \Carbon\Carbon::parse($rev->task_date)->isoFormat('D MMM') }}
                                </div>
                            </div>
                            <svg class="lucide sm" style="color:#0F7A50;flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        </div>
        {{-- ============================================================ --}}

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

        <!-- Target Minggu Ini -->
        <div class="m-card" style="padding:0;">
            <div class="section-head">
                <span class="overline-label">Target minggu ini</span>
                <a href="{{ route('staff-targets.index') }}"
                   style="font-size:12px;font-weight:600;color:var(--maxy-navy);text-decoration:none;">
                    Minggu {{ $currentWeek }} · {{ now()->format('M Y') }}
                </a>
            </div>
            <div style="padding:0 16px 8px;">
                @forelse ($activeWeeklyTargets as $wt)
                    <a href="{{ $wt->monthlyTarget ? route('staff-targets.show', $wt->monthlyTarget->id) : route('staff-targets.index') }}"
                       class="m-row"
                       style="text-decoration:none;color:inherit;cursor:pointer;">
                        <div class="row-body">
                            <div class="row-title">{{ $wt->title }}</div>
                            <div class="row-meta">
                                @if($wt->target_type === 'quantitative')
                                    <span class="chip chip-info">{{ $wt->target_label }}</span>
                                @else
                                    <span class="chip chip-neutral">Kualitatif</span>
                                @endif
                                @if($wt->monthlyTarget)
                                    <span>· {{ Str::limit($wt->monthlyTarget->title, 28) }}</span>
                                @endif
                            </div>
                        </div>
                        <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                @empty
                    <div class="empty-state">Belum ada target untuk minggu ke-{{ $currentWeek }}.</div>
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
                        $statusMap = [
                            'belum_mulai' => 'neutral',
                            'dalam_proses' => 'warning',
                            'terhambat' => 'danger',
                            'selesai' => 'success',
                        ];
                        $sChip   = $statusMap[$entry->status] ?? 'neutral';
                        $sLabel  = $entry->status_label;
                        $priorityChip = [
                            'critical' => 'danger',
                            'high'     => 'warning',
                            'medium'   => 'info',
                            'low'      => 'neutral',
                        ][$entry->priority] ?? 'neutral';
                    @endphp
                    <div class="m-row">
                        @if($entry->status === 'selesai' || $entry->verification_status === 'approved')
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
                        <a href="{{ route('daily-tasks.show', $entry->id) }}"
                           class="row-body"
                           style="text-decoration:none;color:inherit;cursor:pointer;">
                            <div class="row-title" style="display:flex;flex-direction:column;gap:10px;">
                                @if($entry->weeklyTarget && $entry->weeklyTarget->monthlyTarget)
                                    {{-- Target Bulanan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Target Bulanan</div>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            {{ Str::limit($entry->weeklyTarget->monthlyTarget->title, 80) }}
                                        </div>
                                    </div>
                                    {{-- Target Mingguan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Target Mingguan</div>
                                        <div style="font-size:13px; font-weight:600; color:var(--fg-2); line-height:1.3;">
                                            {{ Str::limit($entry->weeklyTarget->title, 80) }}
                                        </div>
                                    </div>
                                @elseif($entry->monthlyTarget)
                                    {{-- Target Bulanan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Target Bulanan</div>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            {{ Str::limit($entry->monthlyTarget->title, 80) }}
                                        </div>
                                    </div>
                                @else
                                    {{-- Tugas Tambahan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Tipe Tugas</div>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            Tugas Tambahan
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- Laporan --}}
                                <div>
                                    <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Laporan Dikirim</div>
                                    <div style="font-size:12px; font-weight:400; color:var(--fg-2); line-height:1.4;">
                                        {{ $entry->task_description }}
                                        @if($entry->is_overdue)
                                            <span class="chip chip-danger" style="margin-left:6px;font-size:10px;">⏰ Terlambat</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row-meta">
                                <span class="chip chip-{{ $sChip }}">{{ $sLabel }}</span>
                                @if($entry->priority !== 'medium')
                                    <span class="chip chip-{{ $priorityChip }}">{{ $entry->priority_label }}</span>
                                @endif
                                <span class="chip chip-{{ $entry->verification_chip }}" style="font-size:10px;">
                                    @if($entry->verification_status === 'approved') ✅
                                    @elseif($entry->verification_status === 'revision') ↩
                                    @elseif($entry->verification_status === 'rejected') ❌
                                    @else ⏳
                                    @endif
                                </span>
                                <span>· {{ $entry->duration_label }}</span>
                            </div>
                        </a>
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
            Tambah Task
        </a>

    @elseif ($user->role === 'leader')
        @php
            $targets     = \App\Models\MonthlyTarget::where('department', $user->department)->where('month', now()->month)->where('year', now()->year)->withCount('dailyTaskEntries')->get();
            $totalStaff  = \App\Models\User::where('department', $user->department)->where('role', 'staff')->count();
            $reported    = \App\Models\DailyTaskEntry::whereHas('user', fn($q) => $q->where('department', $user->department))->whereDate('task_date', today())->distinct('user_id')->count('user_id');

            // Laporan tim hari ini — semua entry dari staff dept ini, hari ini
            $teamEntriesToday = \App\Models\DailyTaskEntry::with(['user','weeklyTarget','monthlyTarget'])
                ->whereHas('user', fn($q) => $q->where('department', $user->department))
                ->whereDate('task_date', today())
                ->orderByDesc('created_at')
                ->get();

            // Notifikasi hari ini yang belum dibaca (untuk card dashboard)
            $todayNotifications = \App\Models\AppNotification::where('user_id', $user->id)
                ->todayUnread()
                ->orderByDesc('created_at')
                ->get();

            $baseQuery = \App\Models\DailyTaskEntry::with(['user','weeklyTarget','monthlyTarget'])
                ->whereHas('user', fn($q) => $q->where('department', $user->department)->where('role', 'staff'))
                ->whereIn('verification_status', ['pending', 'revision'])
                ->orderByDesc('task_date')
                ->orderByDesc('updated_at');

            // Laporan yang terhubung ke target (mingguan atau bulanan)
            $pendingWithTarget = (clone $baseQuery)
                ->where(fn($q) => $q->whereNotNull('weekly_target_id')->orWhereNotNull('monthly_target_id'))
                ->get();

            // Laporan tugas tambahan / mendadak (tidak terhubung target apapun)
            $pendingAdHoc = (clone $baseQuery)
                ->whereNull('weekly_target_id')
                ->whereNull('monthly_target_id')
                ->get();

            $totalPending = $pendingWithTarget->count() + $pendingAdHoc->count();
        @endphp

        {{-- Stat cards: pada desktop jadi 3 kolom (dt-stat-row), mobile tetap 2 kolom (kpi-grid) --}}
        <div class="dt-stat-row kpi-grid">
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
                    <span class="kc-title">Tim lapor hari ini</span>
                    <span class="chip {{ $reported >= $totalStaff ? 'chip-success' : 'chip-warning' }}">
                        {{ $reported >= $totalStaff ? 'Lengkap' : 'Kurang' }}
                    </span>
                </div>
                <div class="kc-value">{{ $reported }}<span class="kc-sub"> / {{ $totalStaff }}</span></div>
                <div class="progress-bar"><i class="success" style="width:{{ $totalStaff > 0 ? round($reported/$totalStaff*100) : 0 }}%"></i></div>
            </div>
            {{-- Kolom ke-3 hanya muncul di desktop (dt-stat-row) --}}
            <div class="kpi-card" style="display:none;" id="dt-stat-pending">
                <div class="kc-header">
                    <span class="kc-title">Menunggu Review</span>
                    <span class="chip chip-warning">Hari ini</span>
                </div>
                <div class="kc-value" id="dt-pending-count">—</div>
                <div class="progress-bar"><i class="navy" style="width:0%" id="dt-pending-bar"></i></div>
            </div>
        </div>
        <script>
            // Isi stat card ke-3 setelah DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                const pendingTotal = {{ $totalPending ?? 0 }};
                const card = document.getElementById('dt-stat-pending');
                const count = document.getElementById('dt-pending-count');
                const bar = document.getElementById('dt-pending-bar');
                if (card) card.style.display = '';
                if (count) count.innerHTML = pendingTotal + '<span class="kc-sub"> laporan</span>';
                if (bar) bar.style.width = Math.min(pendingTotal * 20, 100) + '%';
            });
        </script>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- NOTIFICATION CARD HARIAN — muncul di atas Menunggu Review     --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if($todayNotifications->isNotEmpty())
        <div id="notif-card-daily"
             style="background:var(--surface-1,#fff);
                    border:1.5px solid #E2E8F0;
                    border-radius:16px;
                    overflow:hidden;
                    box-shadow:0 2px 12px rgba(0,0,0,.06);">

            {{-- Header --}}
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:12px 16px 10px;
                        border-bottom:1px solid #F1F5F9;">
                <div style="display:flex;align-items:center;gap:7px;">
                    <span style="font-size:17px;">🔔</span>
                    <span style="font-size:12px;font-weight:700;color:var(--fg-1,#1E293B);letter-spacing:.04em;text-transform:uppercase;">Notifikasi Hari Ini</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="background:#EF4444;color:#fff;font-size:10px;font-weight:700;
                                 border-radius:99px;padding:2px 8px;">{{ $todayNotifications->count() }} baru</span>
                    <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                        @csrf
                        <button type="submit"
                                style="font-size:11px;font-weight:600;color:#64748B;
                                       background:none;border:none;cursor:pointer;padding:2px 4px;
                                       text-decoration:underline;">
                            Tandai semua dibaca
                        </button>
                    </form>
                </div>
            </div>

            {{-- Daftar notifikasi --}}
            <div style="display:flex;flex-direction:column;">
                @foreach($todayNotifications as $notif)
                @php
                    $notifIcon = match($notif->type) {
                        'revision_submitted' => '📨',
                        'auto_rejected'      => '❌',
                        'not_submitted'      => '⚠️',
                        default              => '🔔',
                    };
                    $notifBg = match($notif->type) {
                        'revision_submitted' => '#F0FDF9',
                        'auto_rejected'      => '#FFF5F5',
                        'not_submitted'      => '#FFFBEB',
                        default              => '#F8FAFC',
                    };
                    $notifBorder = match($notif->type) {
                        'revision_submitted' => '#BBF7D0',
                        'auto_rejected'      => '#FECACA',
                        'not_submitted'      => '#FDE68A',
                        default              => '#E2E8F0',
                    };
                    $notifAccent = match($notif->type) {
                        'revision_submitted' => '#0F7A50',
                        'auto_rejected'      => '#DC2626',
                        'not_submitted'      => '#B45309',
                        default              => '#475569',
                    };
                    $hasDiff = $notif->type === 'revision_submitted'
                               && ($notif->getMeta('leader_note') || $notif->getMeta('staff_new_notes'));
                @endphp

                <div style="border-bottom:1px solid #F1F5F9;padding:10px 14px;
                            background:{{ $notifBg }};" id="notif-item-{{ $notif->id }}">

                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        {{-- Ikon --}}
                        <div style="font-size:18px;flex-shrink:0;margin-top:1px;">{{ $notifIcon }}</div>

                        {{-- Konten --}}
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:700;color:var(--fg-1,#1E293B);margin-bottom:2px;">
                                {{ $notif->title }}
                            </div>
                            <div style="font-size:12px;color:#64748B;line-height:1.5;">
                                {{ $notif->body }}
                            </div>

                            {{-- DIFF catatan revisi --}}
                            @if($hasDiff)
                            <div style="margin-top:8px;border-radius:8px;overflow:hidden;
                                        border:1px solid {{ $notifBorder }};font-size:11px;">
                                @if($notif->getMeta('leader_note'))
                                <div style="padding:6px 10px;background:#FEF2F2;">
                                    <span style="font-weight:700;color:#DC2626;">Catatan leader:</span><br>
                                    <span style="color:#7F1D1D;">{{ $notif->getMeta('leader_note') }}</span>
                                </div>
                                @endif
                                @if($notif->getMeta('staff_new_notes'))
                                <div style="padding:6px 10px;background:#F0FDF4;border-top:1px solid {{ $notifBorder }};">
                                    <span style="font-weight:700;color:#16A34A;">Jawaban staff:</span><br>
                                    <span style="color:#14532D;">{{ $notif->getMeta('staff_new_notes') }}</span>
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- Meta bawah: waktu + tombol lihat --}}
                            <div style="display:flex;align-items:center;gap:8px;margin-top:7px;">
                                <span style="font-size:10px;color:#94A3B8;">{{ $notif->created_at->diffForHumans() }}</span>
                                @if($notif->related_id)
                                <a href="{{ route('notifications.read', $notif->id) }}"
                                   style="font-size:11px;font-weight:700;color:{{ $notifAccent }};
                                          text-decoration:none;
                                          background:rgba(0,0,0,.04);border-radius:6px;
                                          padding:3px 8px;">
                                    Lihat laporan →
                                </a>
                                @endif
                            </div>
                        </div>

                        {{-- Tombol dismiss (×) --}}
                        <a href="{{ route('notifications.read', $notif->id) }}?dismiss=1"
                           style="flex-shrink:0;color:#CBD5E1;font-size:16px;
                                  text-decoration:none;line-height:1;
                                  padding:2px 4px;border-radius:4px;
                                  display:flex;align-items:center;justify-content:center;"
                           title="Tutup">&times;</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        {{-- ═══════════════════════════════════════════════════════════════ --}}

        {{-- Section: Menunggu Review — dibagi 2: dengan target & tugas tambahan --}}

        {{-- ── Review Section: 2 kolom berdampingan di desktop ── --}}
        <div class="dt-col2">

            {{-- Card 1: Task Target --}}
            <div class="m-card" style="padding:0;">
                <div class="section-head" style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="overline-label">📋 Menunggu Review — Task Target</span>
                        @if($pendingWithTarget->count() > 0)
                            <span class="chip chip-warning" style="font-size:11px;">{{ $pendingWithTarget->count() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('daily-tasks.index', ['tab' => 'review']) }}" style="font-size:11px;font-weight:600;color:var(--maxy-navy);text-decoration:none;">Lihat Semua &rarr;</a>
                </div>
                <div style="padding:0 16px 8px;">
                    @forelse($pendingWithTarget as $entry)
                        @php
                            $isRevised = $entry->verification_status === 'revision'
                                && $entry->reviewed_at
                                && $entry->updated_at->gt($entry->reviewed_at);
                            $targetLabel = $entry->weeklyTarget?->title ?? $entry->monthlyTarget?->title;
                        @endphp
                        <a href="{{ route('daily-tasks.show', $entry->id) }}"
                           class="m-row" style="text-decoration:none;color:inherit;cursor:pointer;">
                            <div class="row-body">
                                <div class="row-title">
                                    {{ Str::limit($entry->task_description, 38) }}
                                    @if($isRevised)
                                        <span class="chip chip-warning" style="font-size:10px;margin-left:4px;">↩ Direvisi</span>
                                    @endif
                                </div>
                                <div class="row-meta">
                                    <span style="color:var(--fg-2);font-weight:600;">{{ explode(' ', $entry->user->name)[0] }}</span>
                                    <span>· {{ \Carbon\Carbon::parse($entry->task_date)->isoFormat('D MMM') }}</span>
                                    @if($targetLabel)
                                        <span>· {{ Str::limit($targetLabel, 22) }}</span>
                                    @endif
                                </div>
                            </div>
                            <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @empty
                        <div class="empty-state" style="padding:16px 0;">✅ Tidak ada laporan dari target yang menunggu review</div>
                    @endforelse
                </div>
            </div>

            {{-- Card 2: Task Other --}}
            <div class="m-card" style="padding:0;">
                <div class="section-head" style="background:linear-gradient(90deg,#FFF7ED 0%,transparent 100%);display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="overline-label" style="color:#B45309;">📌 Menunggu Review — Task Other</span>
                        @if($pendingAdHoc->count() > 0)
                            <span class="chip" style="font-size:11px;background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;">{{ $pendingAdHoc->count() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('daily-tasks.index', ['tab' => 'review']) }}" style="font-size:11px;font-weight:600;color:#B45309;text-decoration:none;">Lihat Semua &rarr;</a>
                </div>
                <div style="padding:0 16px 8px;">
                    @forelse($pendingAdHoc as $entry)
                        @php
                            $isRevised = $entry->verification_status === 'revision'
                                && $entry->reviewed_at
                                && $entry->updated_at->gt($entry->reviewed_at);
                        @endphp
                        <a href="{{ route('daily-tasks.show', $entry->id) }}"
                           class="m-row" style="text-decoration:none;color:inherit;cursor:pointer;">
                            <div class="row-body">
                                <div class="row-title">
                                    {{ Str::limit($entry->task_description, 38) }}
                                    @if($isRevised)
                                        <span class="chip chip-warning" style="font-size:10px;margin-left:4px;">↩ Direvisi</span>
                                    @endif
                                </div>
                                <div class="row-meta">
                                    <span style="color:var(--fg-2);font-weight:600;">{{ explode(' ', $entry->user->name)[0] }}</span>
                                    <span>· {{ \Carbon\Carbon::parse($entry->task_date)->isoFormat('D MMM') }}</span>
                                    <span style="color:#B45309;font-weight:600;">· Tugas Tambahan</span>
                                </div>
                            </div>
                            <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @empty
                        <div class="empty-state" style="padding:16px 0;">✅ Tidak ada tugas tambahan yang menunggu review</div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end dt-col2 --}}

        <div class="m-card" style="padding:0;">
            <div class="section-head">
                <span class="overline-label">Target aktif</span>
                <a href="{{ route('monthly-targets.index') }}" class="more-link">Lihat semua</a>
            </div>
            <div style="padding:0 16px 8px;">
                @forelse ($targets->take(3) as $target)
                    <a href="{{ route('monthly-targets.show', $target->id) }}"
                       class="m-row"
                       style="text-decoration:none;color:inherit;cursor:pointer;">
                        <div class="row-body">
                            <div class="row-title">{{ $target->title }}</div>
                            <div class="row-meta">
                                <span class="chip chip-dept-{{ str_replace('_','-',$target->department) }}">{{ ucfirst(str_replace('_',' ', $target->department)) }}</span>
                                <span>· {{ $target->daily_task_entries_count }} laporan</span>
                            </div>
                        </div>
                        <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                @empty
                    <div class="empty-state">Belum ada target bulan ini.</div>
                @endforelse
            </div>
        </div>

        <!-- Laporan tim hari ini -->
        <div class="m-card" style="padding:0;">
            <div class="section-head">
                <span class="overline-label">Laporan tim hari ini</span>
                <span style="font-size:12px;font-weight:600;color:var(--maxy-navy);">{{ $teamEntriesToday->count() }} laporan</span>
            </div>
            <div style="padding:0 16px 8px;">
                @forelse ($teamEntriesToday as $entry)
                    @php
                        $statusMap = [
                            'belum_mulai' => 'neutral',
                            'dalam_proses' => 'warning',
                            'terhambat' => 'danger',
                            'selesai' => 'success',
                        ];
                        $sChip = $statusMap[$entry->status] ?? 'neutral';
                    @endphp
                    <div class="m-row">
                        <div class="row-body">
                            <div class="row-title">
                                {{ Str::limit($entry->task_description, 44) }}
                                @if($entry->is_overdue)
                                    <span class="chip chip-danger" style="margin-left:6px;font-size:10px;">⏰ Terlambat</span>
                                @endif
                            </div>
                            <div class="row-meta">
                                <span class="chip chip-{{ $sChip }}">{{ $entry->status_label }}</span>
                                <span>· <strong style="color:var(--fg-2);">{{ explode(' ', $entry->user->name)[0] }}</strong></span>
                                @if($entry->weeklyTarget)
                                    <span>· <span style="color:var(--maxy-navy);font-weight:600;">M{{ $entry->weeklyTarget->week_number }}</span> {{ Str::limit($entry->weeklyTarget->title, 20) }}</span>
                                @endif
                                <span>· {{ $entry->duration_label }}</span>
                            </div>
                            @if($entry->notes)
                                <div style="font-size:11px;color:var(--fg-3);margin-top:4px;font-style:italic;">
                                    "{{ Str::limit($entry->notes, 80) }}"
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada laporan dari tim hari ini.</div>
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
            $depts = \App\Models\User::DEPARTMENTS;
            $deptColors = [
                'sales' => '#2F6BD6', 'marketing' => '#B43BB7', 'product_it' => '#16A571',
                'operational' => '#E89B2A', 'hr' => '#DC2626', 'finance' => '#059669',
                'ga' => '#D97706', 'creative' => '#7C3AED', 'customer_support' => '#2563EB',
                'ceo_office' => '#1D4ED8' // Fallback
            ];
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
                    <span class="chip chip-success">Semua dept</span>
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
                        $color      = $deptColors[$key] ?? '#1D4ED8';
                    @endphp
                    <div class="dept-row">
                        <div class="dept-row-header">
                            <div class="dn">
                                <span class="dept-dot" style="background:{{ $color }};"></span>
                                {{ $label }}
                            </div>
                            <div class="dd">
                                <span style="font-size:12px;color:var(--fg-3);">{{ $count }}/{{ $staffCount }}</span>
                                <span style="font-size:13px;font-weight:600;color:var(--fg-2);">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="progress-bar"><i style="width:{{ $pct }}%;background:{{ $color }};"></i></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</x-app-layout>
