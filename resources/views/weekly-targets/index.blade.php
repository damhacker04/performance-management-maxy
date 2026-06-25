<x-app-layout>
@php
    $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;

    // Group weekly targets by month-year
    $grouped = $weeklyTargets->groupBy(fn($wt) => $wt->year . '-' . str_pad($wt->month, 2, '0', STR_PAD_LEFT));
@endphp

<div class="page">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Target Mingguan</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">
                {{ $weeklyTargets->count() }} target tersimpan
            </p>
        </div>
        <a href="{{ route('weekly-targets.create') }}" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Baru
        </a>
    </div>

    @if(session('success'))
        <div class="m-card" style="background:#E8F7EE;border:1px solid #16A571;color:#0F7A50;padding:12px 16px;font-size:13px;font-weight:600;">
            {{ session('success') }}
        </div>
    @endif

    @if($weeklyTargets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-3);" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p style="font-size:14px;margin-bottom:8px;">Belum ada target mingguan.</p>
                <a href="{{ route('weekly-targets.create') }}" style="font-size:13px;font-weight:600;color:var(--maxy-navy);">Buat target pertama →</a>
            </div>
        </div>
    @else
        @foreach($grouped as $monthKey => $items)
            @php
                [$year, $monthNum] = explode('-', $monthKey);
                $monthNum = (int) $monthNum;
            @endphp
            <div style="margin-top:8px;">
                <div style="font-size:11px;font-weight:700;color:var(--fg-3);letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;padding:0 4px;">
                    {{ $months[$monthNum] }} {{ $year }}
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach($items as $wt)
                        @php
                            [$rStart, $rEnd] = $weekRanges[$wt->week_number] ?? [1, 7];
                            $entryCount = $wt->dailyTaskEntries()->count();
                            // Gunakan kolom category eksplisit (bukan hanya cek NULL monthly_target_id)
                            $isOther = $wt->category === 'other';
                        @endphp
                        <div class="m-card" style="padding:14px 16px;{{ $isOther ? 'border-left:3px solid #B45309;' : '' }}">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                                <a href="{{ route('weekly-targets.show', $wt) }}"
                                   style="flex:1;min-width:0;text-decoration:none;color:inherit;display:block;">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                                        <span class="chip chip-neutral">Minggu {{ $wt->week_number }}</span>
                                        <span style="font-size:11px;color:var(--fg-3);">{{ $rStart }}–{{ $rEnd }} {{ $monthShort[$wt->month] }}</span>
                                        @if($isOther)
                                            <span class="chip" style="background:#FEF3C7;color:#B45309;font-size:11px;">🗂️ Other</span>
                                        @else
                                            <span class="chip" style="background:#E8F5E9;color:#2E7D32;font-size:11px;">Planned</span>
                                            @if($wt->monthlyTarget)
                                                <span class="chip chip-info" style="font-size:11px;">{{ Str::limit($wt->monthlyTarget->title, 22) }}</span>
                                            @endif
                                        @endif
                                        @if($wt->target_type === 'quantitative')
                                            <span class="chip chip-info">{{ $wt->target_label }}</span>
                                        @else
                                            <span class="chip chip-neutral">Kualitatif</span>
                                        @endif
                                    </div>
                                    <div style="font-size:14px;font-weight:600;color:var(--fg-1);">
                                        {{ $wt->title }}
                                    </div>
                                    @if($wt->description)
                                        <p style="font-size:12px;color:var(--fg-3);margin:4px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            {{ $wt->description }}
                                        </p>
                                    @endif
                                    <div style="margin-top:8px;display:flex;align-items:center;gap:6px;font-size:11px;color:var(--maxy-navy);font-weight:600;">
                                        <svg class="lucide" style="width:12px;height:12px;" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        {{ $entryCount }} laporan staff
                                        <span style="color:var(--fg-3);font-weight:400;">· tap untuk detail →</span>
                                    </div>
                                </a>
                                <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                                    <a href="{{ route('weekly-targets.edit', $wt) }}" class="icon-btn" title="Edit">
                                        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('weekly-targets.destroy', $wt) }}"
                                          onsubmit="return confirm('Hapus target mingguan ini?\n\nPerhatian: {{ $entryCount }} laporan staff yang terkait akan tetap tersimpan (weekly_target menjadi tidak terhubung), namun tidak akan terhapus.');"
                                          style="margin:0;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-btn" title="Hapus" style="color:var(--danger);">
                                            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
</x-app-layout>
