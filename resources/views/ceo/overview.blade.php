<x-app-layout>

<div class="page" style="display:flex;flex-direction:column;gap:20px;">

    {{-- Header + filter periode --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:20px;font-weight:700;color:var(--fg-1);margin:0;">Progress Staf</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">Ringkasan kinerja seluruh staf · {{ $monthLabel }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <form method="GET" action="{{ route('ceo.overview') }}" style="display:flex;gap:8px;">
                <div class="select-wrap">
                    <select name="month" class="m-select" onchange="this.form.submit()">
                        @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                            <option value="{{ $i+1 }}" {{ $month == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="select-wrap">
                    <select name="year" class="m-select" onchange="this.form.submit()">
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <a href="{{ route('monthly-targets.create') }}" class="btn btn-primary" style="white-space:nowrap;">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Target untuk Leader
            </a>
        </div>
    </div>

    {{-- Kartu ringkasan --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;">
        @php
            $cards = [
                ['label' => 'Total Staf',      'value' => $totalStaff,           'suffix' => '',  'color' => 'var(--fg-1)'],
                ['label' => 'Rata-rata Progres','value' => $avgProgress,         'suffix' => '%', 'color' => '#1B4FD8'],
                ['label' => 'Menunggu Review',  'value' => $pendingReview,        'suffix' => '',  'color' => $pendingReview > 0 ? '#B45309' : 'var(--fg-1)'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="m-card" style="padding:16px;">
                <div style="font-size:12px;color:var(--fg-3);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">{{ $c['label'] }}</div>
                <div style="font-size:28px;font-weight:800;color:{{ $c['color'] }};margin-top:6px;">{{ $c['value'] }}{{ $c['suffix'] }}</div>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;align-items:start;">

        {{-- Progress per departemen --}}
        <div class="m-card" style="padding:18px;">
            <h2 style="font-size:14px;font-weight:700;color:var(--fg-1);margin:0 0 14px;">Progress per Departemen</h2>
            @forelse($byDept as $d)
                <a href="{{ route('period.staff-list', ['year' => $year, 'month' => $month]) }}"
                   style="display:block;text-decoration:none;color:inherit;padding:10px 0;border-bottom:1px solid var(--border,#eee);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:13px;font-weight:600;color:var(--fg-1);">{{ $d['label'] }}</span>
                        <span style="font-size:12px;color:var(--fg-3);">{{ $d['staff_count'] }} staf · {{ $d['done'] }}/{{ $d['total'] }} tugas</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:8px;background:var(--neutral-100,#f0f0f0);border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:{{ $d['progress'] }}%;background:{{ $d['progress'] >= 70 ? '#16A34A' : ($d['progress'] >= 40 ? '#1B4FD8' : '#DC2626') }};border-radius:99px;"></div>
                        </div>
                        <span style="font-size:12px;font-weight:700;color:var(--fg-2);min-width:34px;text-align:right;">{{ $d['progress'] }}%</span>
                    </div>
                </a>
            @empty
                <p style="font-size:13px;color:var(--fg-3);margin:0;">Belum ada data progress untuk periode ini.</p>
            @endforelse
        </div>

        {{-- Staf perlu perhatian --}}
        <div class="m-card" style="padding:18px;">
            <h2 style="display:flex;align-items:center;gap:7px;font-size:14px;font-weight:700;color:var(--fg-1);margin:0 0 14px;">
                <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--danger);"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg>
                Staf Perlu Perhatian
            </h2>
            @forelse($needAttention as $r)
                <a href="{{ route('period.staff-targets', ['year' => $year, 'month' => $month, 'staff' => $r['staff']->id]) }}"
                   style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;color:inherit;padding:8px 0;border-bottom:1px solid var(--border,#eee);">
                    <div style="min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $r['staff']->name }}</div>
                        <div style="font-size:12px;color:var(--fg-3);">{{ \App\Models\User::DEPARTMENTS[$r['staff']->department] ?? ucfirst(str_replace('_',' ',$r['staff']->department ?? '-')) }}</div>
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:6px;flex-shrink:0;">
                        <span style="font-size:13px;font-weight:700;color:var(--danger);">{{ $r['progress'] }}%</span>
                        <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-4);"><path d="M9 6l6 6-6 6"/></svg>
                    </span>
                </a>
            @empty
                <p style="font-size:13px;color:var(--fg-3);margin:0;">Semua staf dalam kondisi baik 🎉</p>
            @endforelse
        </div>

    </div>
</div>

</x-app-layout>
