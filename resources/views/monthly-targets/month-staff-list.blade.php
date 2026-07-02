<x-app-layout>
@php
    $avatarColors = ['#1B4FD8','#6D28D9','#0E7490','#065F46','#9A3412','#1D4ED8','#7C3AED','#047857'];
    $deptLabels = \App\Models\User::DEPARTMENTS ?? [];
@endphp

<style>
.staff-card {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 16px; border-radius: 14px;
    background: #fff; text-decoration: none; color: inherit;
    border: 1.5px solid var(--neutral-200);
    box-shadow: var(--shadow-sm);
    transition: border-color .15s, box-shadow .15s, transform .15s;
}
.staff-card:hover {
    border-color: var(--maxy-navy);
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
}
.staff-avatar {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff; flex-shrink: 0;
    letter-spacing: .01em;
}
.progress-wrap { height: 4px; background: var(--neutral-100); border-radius: 99px; overflow: hidden; margin-top: 8px; }
.progress-fill  { height: 100%; border-radius: 99px; transition: width .4s ease; }
</style>

<div class="page">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:center;gap:8px;">
        <x-back-button :fallback="route('monthly-targets.index')" style="margin-left:-8px;" />
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px;">
                <span class="chip chip-neutral" style="font-size:11px;">{{ $monthLabel }}</span>
                @if($isCurrentMonth)
                    <span class="chip chip-success" style="font-size:11px;">Bulan ini</span>
                @endif
            </div>
            <h1 style="font-size:18px;font-weight:800;color:var(--fg-1);margin:0;">
                Tim & Staf
            </h1>
        </div>
    </div>

    @php
        // Leader hanya melihat staff-nya sendiri (role = 'staff').
        // C-Level/super_admin bisa melihat semua (termasuk leader).
        $filteredByStaff = $user->isExecutive()
            ? $byStaff
            : $byStaff->filter(fn($data) => ($data['staff']?->role ?? '') === 'staff');
    @endphp

    {{-- ── Empty state ── --}}
    @if($filteredByStaff->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-3);" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Belum Ada Staf</p>
                <p style="font-size:12px;color:var(--fg-3);margin:0;">
                    Belum ada target yang di-assign ke staf di {{ $monthLabel }}.
                </p>
            </div>
        </div>

    @else
        {{-- Info summary --}}
        <div style="font-size:12px;color:var(--fg-3);padding:0 2px;">
            {{ $filteredByStaff->count() }} staf dengan target di {{ $monthLabel }}
        </div>

        {{-- ── Daftar Staf ── --}}
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($filteredByStaff as $staffId => $data)
                @php
                    $s        = $data['staff'];
                    $progress = $data['progress'];
                    $total    = $data['totalEntries'];
                    $done     = $data['doneEntries'];
                    $name     = $s?->name ?? 'Staf';
                    $dept     = $s?->department ?? '';
                    $initials = collect(explode(' ', $name))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
                    $colorIdx = abs(crc32($name) % count($avatarColors));
                    $bgColor  = $avatarColors[$colorIdx];
                    $barColor = $progress >= 80 ? 'var(--success)' : ($progress >= 40 ? 'var(--warning)' : 'var(--maxy-navy)');
                @endphp

                <a href="{{ route('period.staff-targets', ['year' => $year, 'month' => $month, 'staff' => $staffId]) }}"
                   class="staff-card">

                    {{-- Avatar --}}
                    <div class="staff-avatar" style="background:{{ $bgColor }};">
                        {{ $initials }}
                    </div>

                    {{-- Info --}}
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px;">
                            <span style="font-size:15px;font-weight:700;color:var(--fg-1);">{{ $name }}</span>
                            @if($dept)
                                <span class="chip chip-dept-{{ str_replace('_','-', $dept) }}" style="font-size:11px;">
                                    {{ $deptLabels[$dept] ?? ucfirst(str_replace('_',' ',$dept)) }}
                                </span>
                            @endif
                        </div>

                        <div style="font-size:11px;color:var(--fg-3);display:flex;gap:8px;flex-wrap:wrap;">
                            <span>{{ $data['targetCount'] }} target bulanan</span>
                            @if($total > 0)
                                <span>{{ $done }}/{{ $total }} laporan selesai</span>
                            @else
                                <span>Belum ada laporan</span>
                            @endif
                        </div>

                        {{-- Progress --}}
                        @if($total > 0)
                            <div class="progress-wrap">
                                <div class="progress-fill" style="width:{{ $progress }}%;background:{{ $barColor }};"></div>
                            </div>
                            <div style="font-size:11px;font-weight:600;color:{{ $progress >= 80 ? 'var(--success)' : 'var(--fg-3)' }};margin-top:3px;text-align:right;">
                                {{ $progress }}%
                            </div>
                        @endif
                    </div>

                    {{-- Chevron --}}
                    <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24">
                        <path d="M9 6l6 6-6 6"/>
                    </svg>
                </a>
            @endforeach
        </div>
    @endif

</div>
</x-app-layout>
