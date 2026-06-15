<x-app-layout>
@php
    $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;
    $deptLabels = \App\Models\User::DEPARTMENTS;

    $totalEntries        = $monthlyTarget->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
    $doneEntries         = $monthlyTarget->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());
    $pendingReviewTotal  = $monthlyTarget->weeklyTargets->sum(fn($wt) =>
        $wt->dailyTaskEntries->where('status','selesai')->where('verification_status','pending')->count()
    );
    $totalWeekly         = $monthlyTarget->weeklyTargets->count();
    $totalStaff          = $byPersonSorted->keys()->reject(fn($k) => $k === 'umum')->count();

    $todayDay    = now()->day;
    $currentWeek = match(true) {
        $todayDay <= 7  => 1,
        $todayDay <= 14 => 2,
        $todayDay <= 21 => 3,
        $todayDay <= 28 => 4,
        default         => 5,
    };
    $isCurrentMonth = $monthlyTarget->month == now()->month && $monthlyTarget->year == now()->year;
    $isCLevel       = in_array(auth()->user()->role, ['c_level', 'super_admin']);

    // Warna avatar berdasarkan nama (konsisten antar reload)
    $avatarColors = ['#1B4FD8','#6D28D9','#0E7490','#065F46','#9A3412','#1D4ED8','#7C3AED','#047857'];
@endphp

{{-- ── STYLES ────────────────────────────────────────────────────────────── --}}
<style>
.person-accordion { border:1.5px solid var(--bg-3); border-radius:14px; overflow:hidden; background:#fff; }
.person-accordion + .person-accordion { margin-top:10px; }
.person-header {
    cursor:pointer; user-select:none;
    transition:all .15s;
}
.person-header:hover { border-color:var(--maxy-navy) !important; }
.person-body { overflow:hidden; transition:max-height .3s ease; padding:0 12px 12px; display:flex; flex-direction:column; gap:8px; }
.person-body.collapsed { max-height:0 !important; padding-top:0; padding-bottom:0; }
.wt-row {
    display:flex; align-items:flex-start; gap:10px;
    padding:12px 14px; border:1.5px solid var(--bd-1); border-radius:10px; background:#fff;
    transition:all .15s;
}
.wt-row:hover { border-color:var(--maxy-navy); }
.wt-row.week-hidden { display:none; }
.person-accordion.all-weeks-hidden { display:none; }

.week-filter-btn {
    padding:5px 12px; border-radius:99px; font-size:12px; font-weight:600;
    border:1.5px solid var(--bg-3); background:var(--bg-1); color:var(--fg-2);
    cursor:pointer; transition:all .15s; white-space:nowrap;
}
.week-filter-btn.active {
    background:var(--maxy-navy); color:#fff; border-color:var(--maxy-navy);
}
.dept-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 12px; border-radius:99px; font-size:12px; font-weight:600;
    border:1.5px solid var(--bg-3); background:var(--bg-1); color:var(--fg-2);
    text-decoration:none; white-space:nowrap; transition:all .15s;
}
.dept-pill.active { background:var(--maxy-navy); color:#fff; border-color:var(--maxy-navy); }
.dept-pill:hover:not(.active) { background:var(--bg-2); }
</style>

<div class="page">

{{-- ── HEADER ──────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:8px;">
    <a href="{{ route('monthly-targets.index') }}" class="icon-btn" style="margin-left:-8px;">
        <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
    </a>
    <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
            <span class="chip chip-neutral">{{ $monthNames[$monthlyTarget->month] }} {{ $monthlyTarget->year }}</span>
            <span class="chip chip-dept-{{ str_replace('_','-', $monthlyTarget->department) }}">
                {{ ucfirst(str_replace('_',' ', $monthlyTarget->department)) }}
            </span>
            @if($isCurrentMonth)
                <span class="chip chip-info" style="font-size:10px;">Bulan ini</span>
            @endif
        </div>
        <h1 style="font-size:17px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.3;">
            {{ $monthlyTarget->title }}
        </h1>
    </div>
    <a href="{{ route('monthly-targets.edit', $monthlyTarget) }}" class="icon-btn" title="Edit target bulanan">
        <svg class="lucide" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    </a>
</div>

{{-- ── DESKRIPSI ────────────────────────────────────────────────────────────── --}}
@if($monthlyTarget->description)
    <div class="m-card" style="font-size:13px;color:var(--fg-2);line-height:1.6;background:var(--bg-2);border:1px solid var(--bg-3);">
        {{ $monthlyTarget->description }}
    </div>
@endif

{{-- ── STATS ────────────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
    @php
        $stats = [
            ['label' => 'Target Mingguan', 'value' => $totalWeekly,  'sub' => 'target',  'color' => 'var(--maxy-navy)'],
            ['label' => 'Staf Terlibat',   'value' => $totalStaff,   'sub' => 'orang',   'color' => '#6D28D9'],
            ['label' => 'Laporan Masuk',   'value' => $totalEntries, 'sub' => 'laporan', 'color' => '#0E7490'],
            ['label' => 'Pending Review',  'value' => $pendingReviewTotal, 'sub' => 'menunggu', 'color' => $pendingReviewTotal > 0 ? 'var(--danger)' : 'var(--fg-4)'],
        ];
    @endphp
    @foreach($stats as $s)
        <div style="background:var(--bg-1);border:1px solid var(--bg-3);border-radius:12px;padding:10px 12px;text-align:center;">
            <div style="font-size:20px;font-weight:800;color:{{ $s['color'] }};line-height:1;">{{ $s['value'] }}</div>
            <div style="font-size:9px;color:var(--fg-4);margin-top:2px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">{{ $s['sub'] }}</div>
            <div style="font-size:10px;color:var(--fg-3);margin-top:3px;">{{ $s['label'] }}</div>
        </div>
    @endforeach
</div>

{{-- ── DROPDOWN DEPARTEMEN (hanya C-Level & Super Admin) ──────────────────── --}}
@if($isCLevel && $siblingMonthlyTargets->isNotEmpty())
<div class="m-card" style="padding:12px 16px;">
    <div style="font-size:10px;color:var(--fg-4);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;display:flex;align-items:center;gap:5px;">
        <svg class="lucide" style="width:12px;height:12px;" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Navigasi Departemen — {{ $monthNames[$monthlyTarget->month] }} {{ $monthlyTarget->year }}
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
        {{-- Dept aktif saat ini --}}
        <span class="dept-pill active">
            {{ ucfirst(str_replace('_',' ', $monthlyTarget->department)) }}
        </span>
        {{-- Dept lain di bulan yang sama --}}
        @foreach($siblingMonthlyTargets as $sibling)
            <a href="{{ route('monthly-targets.show', $sibling) }}" class="dept-pill">
                {{ ucfirst(str_replace('_',' ', $sibling->department)) }}
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- ── PENCARIAN & FILTER MINGGU + TOMBOL TAMBAH ──────────────────────────── --}}
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:12px;">
    {{-- Baris 1: Search & Tombol Tambah --}}
    <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
        <div style="position:relative;flex:1;max-width:320px;">
            <svg class="lucide" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="target-search" oninput="applyFilters()" placeholder="Cari target atau staf... (/)" 
                   style="width:100%;padding:8px 10px 8px 34px;border:1.5px solid var(--bg-3);border-radius:8px;font-size:13px;outline:none;transition:border-color .2s;">
            <button id="clear-search" onclick="clearSearch()" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--fg-4);padding:0;">
                <svg class="lucide" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <a href="{{ route('weekly-targets.create', ['monthly_target_id' => $monthlyTarget->id, 'context' => 'team']) }}"
           class="btn btn-primary btn-sm" style="flex-shrink:0;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </a>
    </div>

    {{-- Baris 2: Filter Minggu --}}
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
        <span style="font-size:11px;color:var(--fg-4);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-right:4px;">Filter:</span>
        <button class="week-filter-btn active" id="btn-week-0" onclick="setWeekFilter(0, this)">Semua</button>
        @foreach([1,2,3,4,5] as $wn)
            @if($monthlyTarget->weeklyTargets->where('week_number', $wn)->isNotEmpty())
                <button class="week-filter-btn" id="btn-week-{{ $wn }}" onclick="setWeekFilter({{ $wn }}, this)">Minggu {{ $wn }}</button>
            @endif
        @endforeach
    </div>
</div>

{{-- ── ACCORDION PER ORANG ─────────────────────────────────────────────────── --}}
@if($monthlyTarget->weeklyTargets->isEmpty())
    <div class="m-card">
        <div class="empty-state">
            <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Belum ada target mingguan</p>
            <p style="font-size:12px;color:var(--fg-3);">Klik "Tambah" untuk membuat target mingguan pertama.</p>
        </div>
    </div>
@else
    @foreach($byPersonSorted as $personKey => $personTargets)
        @php
            $isUmum   = $personKey === 'umum';
            $person   = $isUmum ? null : ($assignees[$personKey] ?? null);
            $pName    = $isUmum ? 'Target Umum (Seluruh Tim)' : ($person?->name ?? 'Staf');
            $pDiv     = $isUmum ? '' : ($person?->division ?? $person?->department ?? '');

            // Avatar inisial & warna
            $initials = $isUmum ? '🏢' : collect(explode(' ', $pName))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
            $colorIdx  = $isUmum ? null : (crc32($pName) % count($avatarColors));
            $bgColor   = $isUmum ? 'var(--bg-3)' : $avatarColors[abs($colorIdx)];

            // Stats ringkas
            $pTotalWt     = $personTargets->count();
            $pTotalEntry  = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['total'] ?? 0));
            $pDoneEntry   = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['done']  ?? 0));
            $pPendingRev  = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['pending_review'] ?? 0));
            $pProgress    = $pTotalEntry > 0 ? round($pDoneEntry / $pTotalEntry * 100) : 0;
        @endphp

        <a href="{{ route('monthly-targets.staff', ['monthlyTarget' => $monthlyTarget->id, 'assignee' => $personKey]) }}" class="person-accordion m-card" style="margin-bottom:12px; border:1.5px solid var(--bd-1); display:block; text-decoration:none; color:inherit; padding:14px 16px; transition:border-color .15s;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                <div style="flex:1;min-width:0;">
                    {{-- Chips --}}
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:8px;">
                        <span class="chip chip-success" style="font-size:10px;">Aktif</span>
                        @if($pDiv)
                            <span class="chip chip-dept-{{ str_replace('_','-', strtolower($pDiv)) }}" style="font-size:10px;">{{ $pDiv }}</span>
                        @endif
                    </div>
                    
                    {{-- Nama dan Avatar --}}
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:24px;height:24px;border-radius:6px;background:{{ $bgColor }};
                                    color:{{ $isUmum ? 'var(--fg-3)' : '#fff' }};
                                    font-size:10px;font-weight:700;
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            {{ $initials }}
                        </div>
                        <div style="font-size:15px;font-weight:600;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $pName }}
                        </div>
                    </div>

                    {{-- Progress --}}
                    @if($pTotalEntry > 0)
                        <div style="margin-top:10px;">
                            <div style="height:4px;background:var(--bg-3);border-radius:4px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pProgress }}%;background:{{ $pProgress >= 80 ? '#16A571' : ($pProgress >= 40 ? '#F59E0B' : 'var(--maxy-navy)') }};border-radius:4px;"></div>
                            </div>
                            <div style="font-size:10px;color:var(--fg-4);margin-top:4px;">
                                {{ $pDoneEntry }}/{{ $pTotalEntry }} laporan selesai
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Kanan --}}
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0;">
                    @if($pPendingRev > 0)
                        <span style="background:var(--danger);color:#fff;font-size:10px;padding:2px 7px;border-radius:99px;font-weight:700;">
                            {{ $pPendingRev }} pending review
                        </span>
                    @endif
                    <span class="chip chip-info" style="font-size:10px;">{{ $pTotalWt }} target mingguan</span>
                    <svg class="lucide sm chevron-icon" style="color:var(--fg-3);margin-top:2px;" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </div>
            </div>
        </a>
    @endforeach
@endif

{{-- Empty state jika filter tidak ada hasil --}}
<div id="search-empty-state" class="m-card" style="display:none; text-align:center; padding:30px 20px;">
    <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Tidak ada target yang sesuai</p>
    <p style="font-size:12px;color:var(--fg-3);">Coba gunakan kata kunci lain atau hapus filter minggu.</p>
</div>

{{-- ── HAPUS TARGET BULANAN ────────────────────────────────────────────────── --}}
<div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--bg-3);">
    <form method="POST" action="{{ route('monthly-targets.destroy', $monthlyTarget) }}"
          onsubmit="return confirm('Hapus target bulanan ini beserta semua target mingguan di dalamnya?');"
          style="margin:0;">
        @csrf @method('DELETE')
        <button type="submit"
                style="width:100%;padding:10px;background:transparent;border:1.5px solid var(--danger);
                       border-radius:8px;color:var(--danger);font-size:13px;font-weight:600;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;gap:6px;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Hapus Target Bulanan Ini
        </button>
    </form>
</div>

</div>{{-- end .page --}}

{{-- ── JAVASCRIPT ───────────────────────────────────────────────────────────── --}}
<script>
// ── Accordion toggle ──────────────────────────────────────────────────────────
function toggleAccordion(id) {
    const body     = document.getElementById(id);
    const wrapper  = body.closest('.person-accordion');
    const chevron  = wrapper.querySelector('.chevron-icon');
    const isOpen   = !body.classList.contains('collapsed');

    if (isOpen) {
        body.style.maxHeight = body.scrollHeight + 'px'; // snapshot dulu
        requestAnimationFrame(() => {
            body.style.maxHeight = body.scrollHeight + 'px';
            requestAnimationFrame(() => {
                body.classList.add('collapsed');
                body.style.maxHeight = '';
                chevron.style.transform = 'rotate(-90deg)';
            });
        });
    } else {
        body.classList.remove('collapsed');
        body.style.maxHeight = body.scrollHeight + 'px';
        chevron.style.transform = 'rotate(0deg)';
        // Reset ke auto setelah animasi selesai
        body.addEventListener('transitionend', () => {
            if (!body.classList.contains('collapsed')) {
                body.style.maxHeight = '9999px';
            }
        }, { once: true });
    }
}

// ── Search & Filter per minggu ────────────────────────────────────────────────
let activeWeek  = 0;
let searchQuery = '';

function setWeekFilter(weekNum, btn) {
    activeWeek = weekNum;
    document.querySelectorAll('.week-filter-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    else document.getElementById('btn-week-' + weekNum)?.classList.add('active');
    
    applyFilters();
}

function clearSearch() {
    document.getElementById('target-search').value = '';
    applyFilters();
    document.getElementById('target-search').focus();
}

function applyFilters() {
    const input = document.getElementById('target-search');
    searchQuery = input.value.toLowerCase().trim();
    
    const clearBtn = document.getElementById('clear-search');
    clearBtn.style.display = searchQuery ? 'block' : 'none';
    
    // UI input styling
    input.style.borderColor = searchQuery ? 'var(--maxy-navy)' : 'var(--bg-3)';

    const rows       = document.querySelectorAll('.wt-row');
    const accordions = document.querySelectorAll('.person-accordion');

    let visibleCount = 0;

    rows.forEach(row => {
        const rWeek       = parseInt(row.dataset.week);
        const weekMatch   = (activeWeek === 0) || (rWeek === activeWeek);
        const searchMatch = !searchQuery || row.dataset.searchText.includes(searchQuery);
        
        if (weekMatch && searchMatch) {
            row.classList.remove('week-hidden');
            visibleCount++;
        } else {
            row.classList.add('week-hidden');
        }
    });

    // Sembunyikan accordion yang semua baris-nya tersembunyi
    accordions.forEach(acc => {
        const visibleRows = acc.querySelectorAll('.wt-row:not(.week-hidden)');
        if (visibleRows.length === 0) {
            acc.classList.add('all-weeks-hidden');
        } else {
            acc.classList.remove('all-weeks-hidden');
            // Jika lagi nyari pakai teks, otomatis buka accordion-nya
            if (searchQuery) {
                const body = acc.querySelector('.person-body');
                if (body && body.classList.contains('collapsed')) {
                    toggleAccordion(body.id);
                }
            }
        }
    });
    
    // Empty state jika 0 hasil
    const emptyState = document.getElementById('search-empty-state');
    if (emptyState) {
        if (visibleCount === 0 && (searchQuery || activeWeek > 0)) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    }
}

// Keyboard shortcut `/` untuk search
document.addEventListener('keydown', function(e) {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        e.preventDefault();
        document.getElementById('target-search').focus();
    }
});
</script>

</x-app-layout>
