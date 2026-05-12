<x-app-layout>
@php
    $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $now = now();

    // Hitung progres mingguan — berapa minggu sudah punya laporan
    $grouped = $targets->groupBy(fn($t) => $t->year . '-' . str_pad($t->month, 2, '0', STR_PAD_LEFT))
                       ->sortKeysDesc();
@endphp

<div class="page">

    <!-- Header -->
    <div>
        <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Target Tim</h1>
        <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
            {{ ucfirst(str_replace('_', ' ', auth()->user()->department ?? '-')) }} · Hanya bisa dilihat
        </p>
    </div>

    @if($targets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Belum ada target</p>
                <p style="font-size:13px;color:var(--fg-3);">Leader belum membuat target untuk departemenmu.</p>
            </div>
        </div>
    @else
        @foreach($grouped as $key => $monthTargets)
            @php
                $first         = $monthTargets->first();
                $isCurrentMonth = $first->month == $now->month && $first->year == $now->year;
            @endphp

            <!-- Label bulan -->
            <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                <span class="overline-label">{{ $monthNames[$first->month] }} {{ $first->year }}</span>
                @if($isCurrentMonth)
                    <span class="chip chip-info" style="font-size:10px;">Bulan ini</span>
                @endif
            </div>

            @foreach($monthTargets as $target)
                @php
                    $counts   = $myCounts->get($target->id, ['total' => 0, 'done' => 0]);
                    $myTotal  = $counts['total'];
                    $myDone   = $counts['done'];
                    $weekCount = $target->weeklyTargets->count();
                @endphp

                <a href="{{ route('staff-targets.show', $target) }}"
                   style="display:block;text-decoration:none;color:inherit;">
                    <div class="m-card" style="padding:14px 16px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                            <!-- Kiri: info target -->
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:15px;font-weight:700;color:var(--fg-1);line-height:1.4;margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $target->title }}
                                </div>

                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span class="chip chip-neutral">
                                        {{ $weekCount }} minggu
                                    </span>
                                    @if($myTotal > 0)
                                        <span class="chip {{ $myDone === $myTotal ? 'chip-success' : 'chip-warning' }}">
                                            {{ $myDone }}/{{ $myTotal }} selesai
                                        </span>
                                    @else
                                        <span class="chip chip-neutral" style="color:var(--fg-4);">Belum ada laporan</span>
                                    @endif
                                </div>

                                @if($target->description)
                                    <p style="font-size:12px;color:var(--fg-3);margin:8px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;">
                                        {{ $target->description }}
                                    </p>
                                @endif
                            </div>

                            <!-- Kanan: chevron -->
                            <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;margin-top:3px;" viewBox="0 0 24 24">
                                <path d="M9 6l6 6-6 6"/>
                            </svg>
                        </div>

                        <!-- Preview chip minggu -->
                        @if($target->weeklyTargets->isNotEmpty())
                            <div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--bd-1);display:flex;flex-wrap:wrap;gap:4px;">
                                @foreach($target->weeklyTargets as $wt)
                                    <span class="chip chip-info" style="font-size:10px;padding:2px 8px;">
                                        M{{ $wt->week_number }} · {{ Str::limit($wt->title, 22) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        @endforeach
    @endif

</div>
</x-app-layout>
