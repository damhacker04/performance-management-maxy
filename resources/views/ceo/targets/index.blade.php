<x-app-layout>

@php
    // Kelompokkan byLeader berdasarkan departemen leader untuk ditampilkan per-dept
    $byDept = collect($byLeader)->groupBy(fn($g) => $g['leader']?->department ?? 'umum');
@endphp

<div class="page">

    {{-- ── HEADER ──────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:var(--fg-1);margin:0;">Target untuk Leader</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:3px 0 0;">
                Target yang Anda tetapkan untuk para leader
            </p>
        </div>
        <a href="{{ route('monthly-targets.create') }}?back={{ urlencode(url()->current()) }}" class="btn btn-primary btn-sm" style="white-space:nowrap;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tetapkan Target
        </a>
    </div>

    {{-- ── FILTER BULAN ─────────────────────────────────────────── --}}
    <div class="m-card" style="padding:16px;margin-top:4px;">
        <div style="font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
            Pilih Periode
        </div>
        <form method="GET" action="{{ route('ceo.targets.index') }}"
              style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <div class="select-wrap" style="flex:1;min-width:120px;">
                <select name="month" class="m-select" onchange="this.form.submit()" style="height:40px;">
                    @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                        <option value="{{ $i+1 }}" {{ $filterMonth == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="select-wrap" style="min-width:90px;">
                <select name="year" class="m-select" onchange="this.form.submit()" style="height:40px;">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <span style="font-size:13px;font-weight:700;color:var(--maxy-navy);padding:0 4px;white-space:nowrap;">
                {{ $monthLabel }}
            </span>
        </form>
    </div>

    {{-- ── DAFTAR LEADER PER DEPARTEMEN ────────────────────────── --}}
    @forelse($byDept as $deptKey => $leaders)
        @php
            $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_',' ', (string) $deptKey));
        @endphp
        <div style="margin-top:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span class="chip chip-dept-{{ str_replace('_','-',(string)$deptKey) }}" style="font-size:12px;">
                    {{ $deptLabel }}
                </span>
                <span style="font-size:12px;color:var(--fg-3);">{{ $leaders->count() }} leader</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($leaders as $g)
                    @php
                        $pct  = $g['progress'];
                        $pcol = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                    @endphp
                    <a href="{{ route('ceo.targets.leader', ['leader' => $g['leader']->id, 'month' => $filterMonth, 'year' => $filterYear]) }}"
                       class="m-card" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:12px;padding:14px 16px;">

                        {{-- Avatar --}}
                        <span class="av-lg" style="width:42px;height:42px;font-size:15px;background:var(--maxy-navy);flex-shrink:0;">
                            {{ collect(explode(' ', $g['leader']->name))->take(2)->map(fn($w)=>strtoupper($w[0]))->implode('') }}
                        </span>

                        {{-- Info --}}
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:15px;font-weight:700;color:var(--fg-1);
                                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $g['leader']->name }}
                            </div>
                            <div style="font-size:12px;color:var(--fg-3);margin:2px 0 6px;">
                                {{ $g['target_count'] }} target · {{ $g['done'] }}/{{ $g['total'] }} laporan selesai
                            </div>
                            <div style="height:3px;background:var(--bg-3);border-radius:3px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pct }}%;background:{{ $pcol }};border-radius:3px;"></div>
                            </div>
                        </div>

                        {{-- Persentase --}}
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-size:18px;font-weight:800;color:{{ $pcol }};">{{ $pct }}%</div>
                        </div>

                        <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-4);flex-shrink:0;">
                            <path d="M9 6l6 6-6 6"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="m-card" style="margin-top:16px;text-align:center;padding:40px;">
            <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p style="font-size:14px;color:var(--fg-3);margin-bottom:12px;">
                Belum ada target untuk leader pada {{ $monthLabel }}.
            </p>
            <a href="{{ route('monthly-targets.create') }}?back={{ urlencode(url()->current()) }}" class="btn btn-primary btn-sm">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Tetapkan Target Pertama
            </a>
        </div>
    @endforelse

</div>

</x-app-layout>
