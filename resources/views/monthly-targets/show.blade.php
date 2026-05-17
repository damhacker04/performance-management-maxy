<x-app-layout>
@php
    $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;

    // Hitung total laporan tim untuk target bulanan ini
    $totalEntries = $monthlyTarget->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
    $doneEntries  = $monthlyTarget->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());

    // Tentukan minggu aktif sekarang
    $todayDay    = now()->day;
    $currentWeek = match(true) {
        $todayDay <= 7  => 1,
        $todayDay <= 14 => 2,
        $todayDay <= 21 => 3,
        $todayDay <= 28 => 4,
        default         => 5,
    };
    $isCurrentMonth = $monthlyTarget->month == now()->month && $monthlyTarget->year == now()->year;
@endphp

<div class="page">

    {{-- Back & Header --}}
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('monthly-targets.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                <span class="chip chip-neutral">{{ $monthNames[$monthlyTarget->month] }} {{ $monthlyTarget->year }}</span>
                <span class="chip chip-dept-{{ str_replace('_','-', $monthlyTarget->department) }}">
                    {{ ucfirst(str_replace('_',' ', $monthlyTarget->department)) }}
                </span>
                @if($isCurrentMonth)
                    <span class="chip chip-info" style="font-size:10px;">Bulan ini</span>
                @endif
            </div>
            <h1 style="font-size:17px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.3;">
                {{ $monthlyTarget->title }}
            </h1>
        </div>
        {{-- Tombol Edit target bulanan --}}
        <a href="{{ route('monthly-targets.edit', $monthlyTarget) }}" class="icon-btn" title="Edit target bulanan">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </a>
    </div>

    {{-- Deskripsi target bulanan --}}
    @if($monthlyTarget->description)
        <div class="m-card" style="font-size:13px;color:var(--fg-2);line-height:1.6;background:var(--bg-2);border:1px solid var(--bg-3);">
            {{ $monthlyTarget->description }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Target Mingguan</span>
                <span class="chip chip-neutral">{{ $monthlyTarget->weeklyTargets->count() }} minggu</span>
            </div>
            <div class="kc-value">{{ $monthlyTarget->weeklyTargets->count() }}<span class="kc-sub"> target</span></div>
            <div class="progress-bar"><i style="width:{{ min($monthlyTarget->weeklyTargets->count() * 20, 100) }}%"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kc-header">
                <span class="kc-title">Laporan Tim</span>
                <span class="chip {{ $totalEntries > 0 ? 'chip-info' : 'chip-neutral' }}">{{ $monthShort[$monthlyTarget->month] }}</span>
            </div>
            <div class="kc-value">{{ $doneEntries }}<span class="kc-sub"> / {{ $totalEntries }}</span></div>
            <div class="progress-bar"><i class="success" style="width:{{ $totalEntries > 0 ? round($doneEntries/$totalEntries*100) : 0 }}%"></i></div>
        </div>
    </div>

    {{-- Section: Target Mingguan --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
        <span class="overline-label">Target Mingguan</span>
        <a href="{{ route('weekly-targets.create', ['monthly_target_id' => $monthlyTarget->id]) }}"
           class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </a>
    </div>

    @if($monthlyTarget->weeklyTargets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Belum ada target mingguan</p>
                <p style="font-size:12px;color:var(--fg-3);">Klik "Tambah" untuk membuat target mingguan pertama.</p>
            </div>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($monthlyTarget->weeklyTargets as $wt)
                @php
                    [$rStart, $rEnd] = $weekRanges[$wt->week_number] ?? [1, 7];
                    $stats        = $entriesByWeek[$wt->id] ?? ['total' => 0, 'done' => 0];
                    $wtTotal      = $stats['total'];
                    $wtDone       = $stats['done'];
                    $isActiveWeek = $isCurrentMonth && $wt->week_number == $currentWeek;
                    $entryCount   = $wtTotal;
                    $isCLevel     = auth()->user()->role === 'c_level';
                    $leaderGroups = $isCLevel ? ($leaderEntriesByWeek[$wt->id] ?? collect()) : collect();
                @endphp
                <div class="m-card" style="padding:0;{{ $isActiveWeek ? 'border:1.5px solid var(--maxy-navy);' : '' }}">

                    {{-- Card header --}}
                    <div style="padding:14px 16px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            {{-- Info minggu --}}
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
                                <a href="{{ route('weekly-targets.show', $wt) }}"
                                   style="margin-top:8px;font-size:12px;color:var(--maxy-navy);font-weight:600;
                                          display:inline-flex;align-items:center;gap:5px;text-decoration:none;
                                          background:var(--bg-2);border:1px solid var(--bd-1);border-radius:8px;
                                          padding:5px 10px;">
                                    <svg class="lucide" style="width:12px;height:12px;" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Lihat {{ $entryCount }} laporan anggota tim
                                    @if($wtTotal > 0)
                                        <span class="chip {{ $wtDone === $wtTotal ? 'chip-success' : 'chip-warning' }}" style="font-size:10px;">
                                            {{ $wtDone }}/{{ $wtTotal }} selesai
                                        </span>
                                    @endif
                                    <svg class="lucide" style="width:10px;height:10px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                                </a>
                            </div>
                            {{-- Tombol aksi --}}
                            <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                                <a href="{{ route('weekly-targets.edit', $wt) }}" class="icon-btn" title="Edit">
                                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('weekly-targets.destroy', $wt) }}"
                                      onsubmit="return confirm('Hapus target mingguan ini?\n\nPerhatian: {{ $entryCount }} laporan yang terkait akan tetap tersimpan.');"
                                      style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn" title="Hapus" style="color:var(--danger);">
                                        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- LAPORAN LEADER — hanya C-Level --}}
                    @if($isCLevel)
                        <div style="border-top:1px dashed var(--bd-1);padding:10px 16px 14px;">
                            <div style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
                                        color:var(--maxy-navy);opacity:.7;margin-bottom:8px;">
                                Laporan Leader
                            </div>
                            @if($leaderGroups->isEmpty())
                                <div style="text-align:center;font-size:12px;color:var(--fg-4);padding:4px 0;">Belum ada laporan dari leader untuk minggu ini</div>
                            @else
                                @foreach($leaderGroups as $userId => $entries)
                                    @php
                                        $leader   = $entries->first()->user;
                                        $ldDone   = $entries->where('status','selesai')->count();
                                        $ldTotal  = $entries->count();
                                        $initials = collect(explode(' ', $leader->name))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
                                    @endphp
                                    <div style="margin-bottom:10px;">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                            <div style="width:26px;height:26px;border-radius:50%;background:var(--maxy-navy);
                                                        color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;
                                                        justify-content:center;flex-shrink:0;">{{ $initials }}</div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:12px;font-weight:700;color:var(--fg-1);">{{ $leader->name }}</div>
                                                <div style="font-size:10px;color:var(--fg-4);">{{ $ldDone }}/{{ $ldTotal }} selesai</div>
                                            </div>
                                            <span class="chip {{ $ldDone === $ldTotal && $ldTotal > 0 ? 'chip-success' : 'chip-warning' }}" style="font-size:10px;">
                                                {{ $ldDone === $ldTotal && $ldTotal > 0 ? 'Selesai' : 'Berjalan' }}
                                            </span>
                                        </div>
                                        <div style="padding-left:34px;display:flex;flex-direction:column;gap:4px;">
                                            @foreach($entries->sortByDesc('task_date') as $entry)
                                                @php
                                                    $sColor = match($entry->status) {
                                                        'selesai'      => 'var(--success)',
                                                        'dalam_proses' => 'var(--warning)',
                                                        'terhambat'    => 'var(--danger)',
                                                        default        => 'var(--fg-4)',
                                                    };
                                                    $sChip = match($entry->status) {
                                                        'selesai'      => 'chip-success',
                                                        'dalam_proses' => 'chip-warning',
                                                        'terhambat'    => 'chip-danger',
                                                        default        => 'chip-neutral',
                                                    };
                                                @endphp
                                                <a href="{{ route('daily-tasks.show', $entry->id) }}"
                                                   style="display:block;text-decoration:none;background:var(--bg-2);border-radius:8px;
                                                          padding:8px 10px;border-left:3px solid {{ $sColor }};">
                                                    <div style="font-size:12px;font-weight:600;color:var(--fg-1);margin-bottom:2px;">
                                                        {{ Str::limit($entry->task_description, 60) }}
                                                    </div>
                                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                                        <span class="chip {{ $sChip }}" style="font-size:10px;">{{ $entry->status_label }}</span>
                                                        <span style="font-size:10px;color:var(--fg-4);">{{ \Carbon\Carbon::parse($entry->task_date)->format('d M') }}</span>
                                                        <span style="font-size:10px;color:var(--fg-4);">· {{ $entry->duration_label }}</span>
                                                    </div>
                                                    @if($entry->notes)
                                                        <div style="font-size:11px;color:var(--fg-3);margin-top:4px;font-style:italic;line-height:1.4;">
                                                            {{ Str::limit($entry->notes, 80) }}
                                                        </div>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @endif

    {{-- Tombol hapus target bulanan --}}
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--bg-3);">
        <form method="POST" action="{{ route('monthly-targets.destroy', $monthlyTarget) }}"
              onsubmit="return confirm('Hapus target bulanan ini beserta semua target mingguan di dalamnya?');"
              style="margin:0;">
            @csrf @method('DELETE')
            <button type="submit"
                    style="width:100%;padding:10px;background:transparent;border:1.5px solid var(--danger);
                           border-radius:8px;color:var(--danger);font-size:13px;font-weight:600;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;gap:6px;">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Target Bulanan Ini
            </button>
        </form>
    </div>

</div>
</x-app-layout>
