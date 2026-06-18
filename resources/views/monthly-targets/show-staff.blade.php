<x-app-layout>
@php
    $monthName = \Carbon\Carbon::create(null, $monthlyTarget->month)->isoFormat('MMMM');
    $monthShort = [
        1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
        7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'
    ];
    $currentMonth = date('n');
    $currentYear  = date('Y');
    $isCurrentMonth = ($monthlyTarget->month == $currentMonth && $monthlyTarget->year == $currentYear);
    $currentWeek    = $isCurrentMonth ? \Carbon\Carbon::now()->weekOfMonth : null;

    $progressColor = $pProgress >= 80 ? 'var(--success)' : ($pProgress >= 40 ? 'var(--warning)' : 'var(--maxy-navy)');
@endphp

<div class="page">

    {{-- ── HEADER ──────────────────────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:8px;">
        {{-- Back URL di-set oleh controller (period.staff-targets untuk flow baru,
             monthly-targets.show untuk target umum via legacy showStaff) --}}
        <a href="{{ $backUrl ?? ($personKey === 'umum' || !$personKey
                ? route('monthly-targets.show', $monthlyTarget->id)
                : route('period.staff-targets', ['year' => now()->year, 'month' => now()->month, 'staff' => $personKey])) }}"
           class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px;">
                <span class="chip chip-neutral" style="font-size:10px;">{{ $monthName }} {{ $monthlyTarget->year }}</span>
                @if($isCurrentMonth)
                    <span class="chip chip-success" style="font-size:10px;">Bulan ini</span>
                @endif
            </div>
            <h1 style="font-size:17px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.3;">{{ $pName }}</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">{{ $monthlyTarget->title }}</p>
        </div>

        @if(in_array(auth()->user()->role, ['leader', 'c_level', 'super_admin']))
        <a href="{{ route('weekly-targets.create', ['monthly_target_id' => $monthlyTarget->id, 'context' => 'team', 'assigned_to' => $personKey === 'umum' ? '' : $personKey]) }}"
           class="btn btn-primary btn-sm" style="flex-shrink:0;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </a>
        @endif
    </div>

    {{-- ── BANNER PROGRESS ─────────────────────────────────────────────────────── --}}
    <div class="m-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px;display:flex;align-items:center;gap:14px;">
            {{-- Avatar --}}
            <div style="width:48px;height:48px;border-radius:12px;
                        background:{{ $bgColor }};color:#fff;
                        font-size:16px;font-weight:800;letter-spacing:0.01em;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;
                        box-shadow:0 2px 8px rgba(0,0,0,0.12);">
                {{ $initials }}
            </div>

            {{-- Info --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:16px;font-weight:700;color:var(--fg-1);margin-bottom:2px;">{{ $pName }}</div>
                <div style="font-size:12px;color:var(--fg-3);">{{ $personTargets->count() }} Target Mingguan</div>
            </div>

            {{-- Persentase --}}
            @if($pTotalEntry > 0)
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:24px;font-weight:800;color:{{ $progressColor }};line-height:1;">{{ $pProgress }}%</div>
                <div style="font-size:11px;color:var(--fg-4);margin-top:2px;">selesai</div>
            </div>
            @endif
        </div>

        {{-- Progress bar full width --}}
        @if($pTotalEntry > 0)
        <div style="padding:0 16px 14px;">
            <div style="height:5px;background:var(--neutral-100);border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $pProgress }}%;background:{{ $progressColor }};border-radius:99px;transition:width .5s ease;"></div>
            </div>
            <div style="font-size:11px;color:var(--fg-4);margin-top:6px;">
                {{ $pDoneEntry }} dari {{ $pTotalEntry }} laporan selesai
            </div>
        </div>
        @endif
    </div>

    {{-- ── DAFTAR TARGET MINGGUAN ──────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:10px;">
        @forelse($personTargets as $wt)
            @php
                [$rStart, $rEnd] = $weekRanges[$wt->week_number] ?? [1, 7];
                $stats       = $entriesByWeek[$wt->id] ?? ['total' => 0, 'done' => 0, 'pending_review' => 0];
                $wtTotal     = $stats['total'];
                $wtDone      = $stats['done'];
                $wtPending   = $stats['pending_review'];
                $isActiveWeek = $isCurrentMonth && $wt->week_number == $currentWeek;
                $hasOverdue2w = $wt->dailyTaskEntries()
                    ->whereNotIn('status', ['selesai'])
                    ->whereDate('task_date', '<=', today()->subDays(14))
                    ->exists();
            @endphp

            <div class="m-card" style="padding:0;overflow:hidden;border:1.5px solid {{ $isActiveWeek ? 'var(--maxy-navy)' : 'var(--neutral-200)' }};">
                {{-- Baris atas: badges + tombol aksi --}}
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 16px 0;">
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                        <span class="chip chip-neutral" style="font-size:10px;font-weight:700;">Minggu {{ $wt->week_number }}</span>
                        <span style="font-size:10px;color:var(--fg-4);">{{ $rStart }}–{{ $rEnd }} {{ $monthShort[$monthlyTarget->month] }}</span>
                        @if($isActiveWeek)
                            <span class="chip chip-success" style="font-size:10px;">Minggu ini</span>
                        @endif
                        @if($hasOverdue2w)
                            <span class="chip" style="font-size:10px;background:#FEF3C7;color:#92400E;border:none;">⏰ &gt;2 minggu</span>
                        @endif
                        @if($wt->target_type === 'quantitative')
                            <span class="chip chip-info" style="font-size:10px;">{{ $wt->target_label }}</span>
                        @else
                            <span class="chip chip-neutral" style="font-size:10px;">Kualitatif</span>
                        @endif
                    </div>

                    {{-- Tombol Edit & Hapus --}}
                    @if(in_array(auth()->user()->role, ['leader', 'c_level', 'super_admin']))
                    <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                        <a href="{{ route('weekly-targets.edit', $wt) }}" class="icon-btn" title="Edit" style="width:32px;height:32px;">
                            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('weekly-targets.destroy', $wt) }}"
                              onsubmit="return confirm('Hapus target mingguan ini?\n\nPerhatian: {{ $wtTotal }} laporan terkait akan tetap tersimpan.');"
                              style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="icon-btn" title="Hapus" style="color:var(--danger);width:32px;height:32px;">
                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Konten utama --}}
                <div style="padding:10px 16px 14px;">
                    {{-- Garis aksen kiri untuk minggu aktif --}}
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        @if($isActiveWeek)
                        <div style="width:3px;border-radius:99px;background:var(--maxy-navy);flex-shrink:0;align-self:stretch;min-height:20px;"></div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:14px;font-weight:600;color:var(--fg-1);line-height:1.4;margin-bottom:4px;">
                                {{ $wt->title }}
                            </div>
                            @if($wt->description)
                                <p style="font-size:12px;color:var(--fg-3);margin:0 0 10px;line-height:1.5;">
                                    {{ Str::limit($wt->description, 120) }}
                                </p>
                            @endif

                            {{-- Footer: link laporan + pending badge --}}
                            <div style="display:flex;align-items:center;gap:8px;padding-top:4px;border-top:1px solid var(--neutral-100);margin-top:6px;">
                                <a href="{{ route('weekly-targets.show', $wt) }}"
                                   style="font-size:12px;color:var(--maxy-navy);font-weight:600;display:inline-flex;align-items:center;gap:5px;text-decoration:none;flex:1;">
                                    <svg class="lucide" style="width:13px;height:13px;" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Lihat {{ $wtTotal }} laporan
                                </a>
                                @if($wtPending > 0)
                                    <span style="background:var(--danger-100);color:var(--danger);font-size:10px;font-weight:700;padding:3px 8px;border-radius:99px;">
                                        {{ $wtPending }} pending
                                    </span>
                                @endif
                                @if($wtTotal > 0)
                                    <span style="font-size:11px;color:var(--fg-4);">{{ $wtDone }}/{{ $wtTotal }} selesai</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="m-card" style="border:1.5px dashed var(--neutral-200);">
                <div class="empty-state">
                    <div style="font-size:28px;margin-bottom:10px;">🎯</div>
                    <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Belum ada target mingguan</p>
                    <p style="font-size:12px;color:var(--fg-3);margin:0;">Klik "Tambah" untuk membuat target mingguan untuk {{ explode(' ', $pName)[0] }}.</p>
                </div>
            </div>
        @endforelse
    </div>

</div>
</x-app-layout>
