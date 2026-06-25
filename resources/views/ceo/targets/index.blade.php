<x-app-layout>

<div class="page">

    {{-- Header + filter periode --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:var(--fg-1);margin:0;">Target untuk Leader</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:3px 0 0;">Target yang Anda tetapkan untuk para leader · {{ $monthLabel }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <form method="GET" action="{{ route('ceo.targets.index') }}" style="display:flex;gap:8px;">
                <div class="select-wrap">
                    <select name="month" class="m-select" onchange="this.form.submit()" style="height:40px;">
                        @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                            <option value="{{ $i+1 }}" {{ $filterMonth == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="select-wrap">
                    <select name="year" class="m-select" onchange="this.form.submit()" style="height:40px;">
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <a href="{{ route('monthly-targets.create') }}" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Tetapkan Target
            </a>
        </div>
    </div>

    {{-- Daftar leader + target mereka --}}
    <div class="dt-card-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
        @forelse($byLeader as $g)
            @php
                $pct   = $g['progress'];
                $pcol  = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                $deptKey = $g['leader']?->department;
                $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_',' ', (string) $deptKey));
            @endphp
            <a href="{{ route('ceo.targets.leader', ['leader' => $g['leader']->id, 'month' => $filterMonth, 'year' => $filterYear]) }}"
               class="m-card" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:12px;">

                {{-- Header leader --}}
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="av-lg" style="width:42px;height:42px;font-size:15px;background:var(--maxy-navy);">
                        {{ collect(explode(' ', $g['leader']->name))->take(2)->map(fn($w)=>strtoupper($w[0]))->implode('') }}
                    </span>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:15px;font-weight:700;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $g['leader']->name }}</div>
                        <span class="chip chip-dept-{{ str_replace('_','-', (string) $deptKey) }}" style="margin-top:2px;">{{ $deptLabel }}</span>
                    </div>
                    <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-4);flex-shrink:0;"><path d="M9 6l6 6-6 6"/></svg>
                </div>

                {{-- Progres --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                        <span style="font-size:12px;color:var(--fg-2);font-weight:600;">{{ $g['target_count'] }} target · {{ $g['done'] }}/{{ $g['total'] }} laporan selesai</span>
                        <span style="font-size:13px;font-weight:800;color:{{ $pcol }};">{{ $pct }}%</span>
                    </div>
                    <div class="progress-bar"><i style="width:{{ $pct }}%;background:{{ $pcol }};"></i></div>
                </div>

                {{-- Ringkas target --}}
                <div style="border-top:1px solid var(--neutral-100);padding-top:10px;display:flex;flex-direction:column;gap:6px;">
                    @foreach($g['targets']->take(3) as $t)
                        <div style="display:flex;align-items:baseline;gap:6px;font-size:13px;color:var(--fg-1);">
                            <span style="width:5px;height:5px;border-radius:50%;background:var(--maxy-amber);flex-shrink:0;transform:translateY(-2px);"></span>
                            <span style="font-weight:600;line-height:1.35;">{{ Str::limit($t->title, 60) }}</span>
                        </div>
                    @endforeach
                    @if($g['target_count'] > 3)
                        <span style="font-size:12px;color:var(--fg-3);">+{{ $g['target_count'] - 3 }} target lainnya</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="m-card" style="grid-column:1/-1;">
                <div class="empty-state">
                    <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p style="font-size:14px;margin-bottom:8px;">Belum ada target untuk leader pada {{ $monthLabel }}.</p>
                    <a href="{{ route('monthly-targets.create') }}" class="btn btn-primary btn-sm" style="margin-top:4px;">
                        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Tetapkan Target Pertama
                    </a>
                </div>
            </div>
        @endforelse
    </div>

</div>

</x-app-layout>
