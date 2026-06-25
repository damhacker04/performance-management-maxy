<x-app-layout>

<div class="page">

    {{-- Back + header --}}
    <div>
        <a href="{{ route('ceo.targets.index', ['month' => $filterMonth, 'year' => $filterYear]) }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--maxy-navy);text-decoration:none;margin-bottom:12px;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            Kembali ke daftar target
        </a>
        @php
            $deptKey = $leader->department;
            $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_',' ', (string) $deptKey));
        @endphp
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="av-lg" style="width:48px;height:48px;font-size:17px;background:var(--maxy-navy);">
                {{ collect(explode(' ', $leader->name))->take(2)->map(fn($w)=>strtoupper($w[0]))->implode('') }}
            </span>
            <div>
                <h1 style="font-size:20px;font-weight:800;color:var(--fg-1);margin:0;">{{ $leader->name }}</h1>
                <div style="display:flex;align-items:center;gap:8px;margin-top:3px;">
                    <span class="chip chip-dept-{{ str_replace('_','-', (string) $deptKey) }}">{{ $deptLabel }}</span>
                    <span style="font-size:12px;color:var(--fg-3);">Leader · {{ $monthLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Section A: Target dari CEO untuk leader --}}
    <div class="m-card" style="padding:0;">
        <div class="section-head">
            <span class="overline-label">Target dari Anda untuk {{ explode(' ', $leader->name)[0] }}</span>
        </div>
        <div style="padding:4px 16px 16px;display:flex;flex-direction:column;gap:10px;">
            @forelse($leaderTargets as $t)
                @php
                    $c     = $leaderEntryCounts[$t->id] ?? ['total' => 0, 'done' => 0];
                    $pct   = $c['total'] > 0 ? (int) round($c['done'] / $c['total'] * 100) : 0;
                    $pcol  = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                @endphp
                <div class="m-row as-card" style="border-left:3px solid {{ $pcol }};flex-direction:column;align-items:stretch;gap:8px;">
                    <div>
                        <span class="eyebrow">Target Bulanan</span>
                        <div style="font-size:15px;font-weight:700;color:var(--fg-1);line-height:1.3;">{{ $t->title }}</div>
                    </div>
                    @if($t->description)
                        <div>
                            <span class="eyebrow eyebrow-muted">Detail / Arahan</span>
                            <div style="font-size:13px;color:var(--fg-2);line-height:1.5;">{{ $t->description }}</div>
                        </div>
                    @endif
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                            <span style="font-size:12px;color:var(--fg-2);font-weight:600;">{{ $t->weeklyTargets->count() }} target mingguan · {{ $c['done'] }}/{{ $c['total'] }} laporan selesai</span>
                            <span style="font-size:13px;font-weight:800;color:{{ $pcol }};">{{ $pct }}%</span>
                        </div>
                        <div class="progress-bar"><i style="width:{{ $pct }}%;background:{{ $pcol }};"></i></div>
                    </div>
                </div>
            @empty
                <div class="empty-state" style="padding:20px 0;">Anda belum menetapkan target untuk leader ini pada {{ $monthLabel }}.</div>
            @endforelse
        </div>
    </div>

    {{-- Section B: Target leader untuk staff (read-only) --}}
    <div class="m-card" style="padding:0;">
        <div class="section-head" style="flex-direction:column;align-items:flex-start;gap:2px;">
            <span class="overline-label">Target untuk Staff</span>
            <span style="font-size:12px;color:var(--fg-3);font-weight:400;text-transform:none;letter-spacing:0;">
                Diberikan oleh {{ explode(' ', $leader->name)[0] }} — hanya bisa dilihat
            </span>
        </div>
        <div style="padding:4px 16px 16px;display:flex;flex-direction:column;gap:10px;">
            @forelse($byStaff as $s)
                @php
                    $pct  = $s['progress'];
                    $pcol = $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--maxy-navy)' : 'var(--danger)');
                @endphp
                <a href="{{ route('period.staff-targets', ['year' => $filterYear, 'month' => $filterMonth, 'staff' => $s['staff']->id]) }}"
                   class="m-row as-card" style="border-left:3px solid {{ $pcol }};text-decoration:none;color:inherit;">
                    <div class="row-body">
                        <div class="row-title">{{ $s['staff']->name }}</div>
                        <div class="row-meta">
                            <span>{{ $s['target_count'] }} target</span>
                            <span style="font-weight:700;color:{{ $pcol }};">· {{ $pct }}% selesai</span>
                        </div>
                        <div class="progress-bar" style="margin-top:8px;"><i style="width:{{ $pct }}%;background:{{ $pcol }};"></i></div>
                    </div>
                    <svg class="lucide sm" viewBox="0 0 24 24" style="color:var(--fg-3);flex-shrink:0;align-self:center;"><path d="M9 6l6 6-6 6"/></svg>
                </a>
            @empty
                <div class="empty-state" style="padding:20px 0;">{{ explode(' ', $leader->name)[0] }} belum memberi target ke staff pada periode ini.</div>
            @endforelse
        </div>
    </div>

</div>

</x-app-layout>
