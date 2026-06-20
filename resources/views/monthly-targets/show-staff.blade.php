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
@endphp

<div class="page">
@php
    // URL halaman ini (L4) — dipakai sebagai ?back= untuk Tambah/Edit agar
    // setelah save bisa redirect kembali ke sini
    $currentUrl = isset($backUrl)
        ? url()->current()  // sudah dalam period context
        : url()->current();
@endphp

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
        </div>

        @if(in_array(auth()->user()->role, ['leader', 'c_level', 'super_admin']))
        <a href="{{ route('weekly-targets.create', [
                'monthly_target_id' => $monthlyTarget->id,
                'context'           => 'team',
                'assigned_to'       => $personKey === 'umum' ? '' : $personKey,
                'back'              => urlencode(url()->current()),
            ]) }}"
           class="btn btn-primary btn-sm" style="flex-shrink:0;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </a>
        @endif
    </div>

    {{-- Banner Progress --}}
    <div class="m-card" style="margin-top:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="av-lg" style="background:var(--bg-3);color:var(--fg-2);">{{ $initials }}</div>
            <div>
                <div style="font-size:16px;font-weight:700;">{{ $pName }}</div>
                <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">{{ $personTargets->count() }} Target Mingguan</div>
            </div>
        </div>
        @if($pTotalEntry > 0)
        <div style="text-align:right; width:120px;">
            <div style="font-size:20px;font-weight:800;color:var(--maxy-navy);">{{ $pProgress }}%</div>
            <div style="font-size:11px;color:var(--fg-3);margin-bottom:6px;">{{ $pDoneEntry }} dari {{ $pTotalEntry }} laporan selesai</div>
            <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:{{ $pProgress }}%;background:{{ $pProgress >= 80 ? '#16A571' : ($pProgress >= 40 ? '#F59E0B' : 'var(--maxy-navy)') }};border-radius:4px;"></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Daftar Target --}}
    <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
        @forelse($personTargets as $wt)
            @php
                [$rStart, $rEnd] = $weekRanges[$wt->week_number] ?? [1, 7];
                $stats        = $entriesByWeek[$wt->id] ?? ['total' => 0, 'done' => 0, 'pending_review' => 0];
                $wtTotal      = $stats['total'];
                $wtDone       = $stats['done'];
                $wtPending    = $stats['pending_review'];
                $isActiveWeek   = $isCurrentMonth && $wt->week_number == $currentWeek;
                // Cek apakah ada laporan overdue >2 minggu di target ini
                $hasOverdue2w   = $wt->dailyTaskEntries()
                    ->whereNotIn('status', ['selesai'])
                    ->whereDate('task_date', '<=', today()->subDays(14))
                    ->exists();
            @endphp
            <div class="m-card" style="padding:14px 16px; border:1.5px solid var(--bd-1); display:flex; gap:12px;">
                {{-- Garis minggu aktif --}}
                <div style="width:4px;border-radius:99px;flex-shrink:0;align-self:stretch;
                            background:{{ $isActiveWeek ? 'var(--maxy-navy)' : 'var(--bg-3)' }};"></div>

                {{-- Konten target --}}
                <div style="flex:1;min-width:0;">
                    {{-- Badges --}}
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:6px;">
                        <span class="chip chip-neutral" style="font-size:10px;font-weight:700;">Minggu {{ $wt->week_number }}</span>
                        <span style="font-size:10px;color:var(--fg-4);">{{ $rStart }}–{{ $rEnd }} {{ $monthShort[$monthlyTarget->month] }}</span>
                        @if($isActiveWeek)
                            <span class="chip chip-success" style="font-size:10px;">Minggu ini</span>
                        @endif
                        @if($hasOverdue2w)
                            <span class="chip" style="font-size:10px;background:#F97316;color:#fff;border:none;">⏰ >2 minggu</span>
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
                        <a href="{{ route('weekly-targets.edit', $wt) }}?back={{ urlencode(url()->current()) }}" class="icon-btn" title="Edit" style="width:32px;height:32px;">
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
                    @if($wt->description)
                        <p style="font-size:12px;color:var(--fg-3);margin:0 0 8px;line-height:1.4;">
                            {{ Str::limit($wt->description, 120) }}
                        </p>
                    @endif

                    {{-- Link laporan --}}
                    <a href="{{ route('weekly-targets.show', $wt) }}"
                       style="font-size:12px;color:var(--maxy-navy);font-weight:600;display:inline-flex;align-items:center;gap:4px;text-decoration:none;">
                        <svg class="lucide" style="width:12px;height:12px;" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Lihat {{ $wtTotal }} laporan
                        @if($wtPending > 0)
                            <span style="background:var(--danger);color:#fff;font-size:10px;padding:1px 6px;border-radius:99px;">{{ $wtPending }} pending</span>
                        @endif
                    </a>
                </div>

                            {{-- Footer: link laporan + pending badge --}}
                            <div style="display:flex;align-items:center;gap:8px;padding-top:4px;border-top:1px solid var(--neutral-100);margin-top:6px;">
                                <a href="{{ isset($year, $month, $person)
                                        ? route('period.weekly-show', [
                                            'year'          => $year,
                                            'month'         => $month,
                                            'staff'         => $person->id,
                                            'monthlyTarget' => $monthlyTarget->id,
                                            'weeklyTarget'  => $wt->id,
                                          ])
                                        : route('weekly-targets.show', $wt) }}"
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
            <div style="padding:32px;text-align:center;color:var(--fg-4);font-size:13px;background:var(--bg-1);border-radius:12px;border:1.5px dashed var(--bd-1);">
                <div style="font-size:24px;margin-bottom:8px;">🎯</div>
                Belum ada target mingguan untuk {{ explode(' ', $pName)[0] }}.
            </div>
        @endforelse

    </div>
</div>
</x-app-layout>
