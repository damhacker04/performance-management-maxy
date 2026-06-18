<x-app-layout>
@php
    $monthShort  = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $avatarColors = ['#1B4FD8','#6D28D9','#0E7490','#065F46','#9A3412','#1D4ED8','#7C3AED','#047857'];
    $initials = collect(explode(' ', $staff->name))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
    $colorIdx = abs(crc32($staff->name) % count($avatarColors));
    $bgColor  = $avatarColors[$colorIdx];
@endphp

<style>
.mt-card {
    background: #fff;
    border: 1.5px solid var(--neutral-200);
    border-radius: 14px;
    padding: 16px 18px;
    display: flex; align-items: center; gap: 14px;
    text-decoration: none; color: inherit;
    transition: border-color .2s, box-shadow .2s;
    box-shadow: var(--shadow-sm);
}
.mt-card:hover { border-color: var(--maxy-navy); box-shadow: var(--shadow-md); }
.mt-card + .mt-card { margin-top: 10px; }

.mt-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.mt-info { flex: 1; min-width: 0; }
.mt-title { font-size: 14px; font-weight: 700; color: var(--fg-1); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mt-meta  { font-size: 12px; color: var(--fg-3); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.progress-bar-wrap {
    height: 5px; background: var(--neutral-100); border-radius: 99px;
    overflow: hidden; margin-top: 8px;
}
.progress-bar-fill {
    height: 100%; border-radius: 99px;
    transition: width .4s ease;
}
</style>

<div class="page">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="javascript:history.back()" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:2px;">
                {{-- Avatar mini --}}
                <div style="width:28px;height:28px;border-radius:8px;background:{{ $bgColor }};
                            color:#fff;font-size:10px;font-weight:700;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    {{ $initials }}
                </div>
                <span style="font-size:18px;font-weight:800;color:var(--fg-1);">{{ $staff->name }}</span>
                <span class="chip chip-dept-{{ str_replace('_','-', $staff->department ?? 'neutral') }}" style="font-size:10px;">
                    {{ \App\Models\User::DEPARTMENTS[$staff->department] ?? $staff->department }}
                </span>
            </div>
            <div style="font-size:12px;color:var(--fg-4);margin-left:36px;">
                Target bulanan yang ditugaskan
            </div>
        </div>
    </div>

    {{-- ── KPI Acuan (jika ada) ── --}}
    @php
        $kpisForDept = \App\Models\KpiTarget::whereNotNull('department')
            ->where('department', $staff->department)
            ->where('is_active', true)
            ->get();
    @endphp
    @if($kpisForDept->isNotEmpty())
        <div style="background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                    border-radius:var(--r-md);padding:12px 14px;">
            <div style="font-size:11px;font-weight:700;color:var(--info,#2563eb);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                📊 KPI Departemen — Acuan Evaluasi
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($kpisForDept as $kpi)
                    <div style="font-size:12px;color:var(--fg-2);background:#fff;border:1px solid var(--info-200,#bfdbfe);
                                border-radius:8px;padding:5px 10px;">
                        <strong>{{ $kpi->kpi_name }}</strong>:
                        {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}/bln
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Daftar Monthly Target ── --}}
    @if($monthlyTargets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Belum Ada Target Bulanan</p>
                <p style="font-size:12px;color:var(--fg-3);margin:0;">
                    Belum ada target bulanan yang ditugaskan ke {{ $staff->name }}.
                </p>
            </div>
        </div>
    @else
        @php
            // Group by tahun-bulan, terbaru di atas
            $grouped = $monthlyTargets->groupBy(fn($mt) => $mt->year . '-' . str_pad($mt->month, 2, '0', STR_PAD_LEFT))
                ->sortKeysDesc();
        @endphp

        @foreach($grouped as $yearMonth => $targets)
            @php
                [$yr, $mo] = explode('-', $yearMonth);
                $isCurrentMonth = (int)$mo == now()->month && (int)$yr == now()->year;
            @endphp

            <div>
                {{-- Label bulan --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:700;color:var(--fg-4);text-transform:uppercase;letter-spacing:.06em;">
                        {{ $monthNames[(int)$mo] }} {{ $yr }}
                    </span>
                    @if($isCurrentMonth)
                        <span class="chip chip-success" style="font-size:10px;">Bulan ini</span>
                    @endif
                </div>

                @foreach($targets as $mt)
                    @php
                        $progress  = $mt->progress_pct;
                        $barColor  = $progress >= 80 ? 'var(--success)' : ($progress >= 40 ? 'var(--warning)' : 'var(--maxy-navy)');
                        $isActive  = $isCurrentMonth;
                    @endphp

                    {{-- Link ke showStaff (Gambar 4 = weekly targets) --}}
                    <a href="{{ route('monthly-targets.staff', ['monthlyTarget' => $mt->id, 'assignee' => $staff->id]) }}"
                       class="mt-card" style="border-color: {{ $isActive ? 'var(--warning)' : 'var(--neutral-200)' }};">

                        {{-- Icon --}}
                        <div class="mt-icon" style="background: {{ $isActive ? '#FEF3C7' : 'var(--neutral-100)' }};">
                            <svg class="lucide sm" style="color: {{ $isActive ? 'var(--warning)' : 'var(--fg-4)' }};" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>

                        {{-- Info --}}
                        <div class="mt-info">
                            <div class="mt-title">{{ $mt->title }}</div>
                            <div class="mt-meta">
                                <span>
                                    <svg class="lucide" style="width:12px;height:12px;vertical-align:middle;" viewBox="0 0 24 24">
                                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                    </svg>
                                    {{ $mt->weekly_count }} target mingguan
                                </span>
                                <span>
                                    <svg class="lucide" style="width:12px;height:12px;vertical-align:middle;" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    {{ $mt->done_entries }}/{{ $mt->total_entries }} laporan
                                </span>
                                @if($mt->kpiTarget)
                                    <span style="color:var(--info,#2563eb);">
                                        KPI: {{ $mt->kpiTarget->kpi_name }}
                                    </span>
                                @endif
                            </div>

                            {{-- Progress bar --}}
                            @if($mt->total_entries > 0)
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill"
                                         style="width:{{ $progress }}%;background:{{ $barColor }};"></div>
                                </div>
                                <div style="font-size:11px;color:var(--fg-4);margin-top:3px;text-align:right;">
                                    {{ $progress }}% selesai
                                </div>
                            @else
                                <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">
                                    Belum ada laporan masuk
                                </div>
                            @endif
                        </div>

                        {{-- Chevron --}}
                        <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        @endforeach
    @endif

</div>
</x-app-layout>
