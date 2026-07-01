<x-app-layout>

    <div class="page">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;width:100%;">
            <div style="min-width:200px;">
                <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">
                    {{ $tab === 'review' ? 'Menunggu Review' : 'Tugas Saya' }}
                </h1>
                <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">{{ $entries->count() }} laporan tercatat</p>
            </div>
            @if($tab !== 'review')
            <a href="{{ route('daily-tasks.create') }}" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <svg class="lucide sm" viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Task
            </a>
            @endif
        </div>

        {{-- Navigation Tabs --}}
        {{-- Form Pencarian dan Filter (Auto-Submit) --}}
        <form id="filter-form" method="GET" action="{{ route('daily-tasks.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- Navigation Tabs --}}
        @if(auth()->user()->isLeadership())
        <div style="display:flex;align-items:center;border-bottom:1px solid var(--bg-3);margin-top:16px;padding-bottom:12px;overflow-x:auto;gap:8px;">
            <a href="{{ route('daily-tasks.index', ['tab' => 'mine']) }}"
               style="text-decoration:none;padding:7px 14px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:6px;
                      background:{{ $tab === 'mine' ? 'var(--maxy-navy)' : 'var(--bg-2)' }};
                      color:{{ $tab === 'mine' ? '#fff' : 'var(--fg-2)' }};">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Tugas Saya
            </a>
            <a href="{{ route('daily-tasks.index', ['tab' => 'review']) }}"
               style="text-decoration:none;padding:7px 14px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:6px;
                      background:{{ $tab === 'review' ? 'var(--maxy-navy)' : 'var(--bg-2)' }};
                      color:{{ $tab === 'review' ? '#fff' : 'var(--fg-2)' }};">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                Menunggu Review
                @if($pendingReviewCount > 0)
                <span style="background:var(--danger);color:#fff;font-size:11px;font-weight:700;padding:1px 7px;border-radius:99px;">{{ $pendingReviewCount }}</span>
                @endif
            </a>
            @php
                $backdatePendingCount = \App\Models\BackdateRequest::when(auth()->user()->role === 'leader', fn($q) =>
                    $q->whereHas('user', fn($uq) => $uq->where('department', auth()->user()->department))
                )->where('status', 'pending')->count();
            @endphp
            <a href="{{ route('backdate-requests.index') }}"
               style="text-decoration:none;padding:7px 14px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:6px;
                      background:var(--bg-2);color:var(--fg-2);">
                <svg class="lucide sm" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Izin Backdating
                @if($backdatePendingCount > 0)
                <span style="background:var(--danger);color:#fff;font-size:11px;font-weight:700;padding:1px 7px;border-radius:99px;">{{ $backdatePendingCount }}</span>
                @endif
            </a>
        </div>
        @endif

        {{-- Filter & Search Row --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:16px;margin-bottom:16px;flex-wrap:wrap;">
            
            {{-- Dropdown Filters --}}
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <label style="font-size:12px;font-weight:600;color:var(--fg-3);">Filter:</label>
                    <select name="status" class="filter-dropdown" style="padding:9px 12px;font-size:13px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;outline:none;min-height:40px;">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\DailyTaskEntry::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ ($statusFilter ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;align-items:center;gap:6px;">
                    <select name="date" class="filter-dropdown" style="padding:9px 12px;font-size:13px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;outline:none;min-height:40px;">
                        <option value="">Semua Tanggal</option>
                        <option value="{{ today()->toDateString() }}" {{ ($dateFilter ?? '') === today()->toDateString() ? 'selected' : '' }}>Hari Ini</option>
                        <option value="{{ today()->subDay()->toDateString() }}" {{ ($dateFilter ?? '') === today()->subDay()->toDateString() ? 'selected' : '' }}>Kemarin</option>
                        <option value="{{ today()->subDays(2)->toDateString() }}" {{ ($dateFilter ?? '') === today()->subDays(2)->toDateString() ? 'selected' : '' }}>H-2</option>
                    </select>
                </div>

                @if(isset($subordinateStaff) && $subordinateStaff->isNotEmpty())
                <div style="display:flex;align-items:center;gap:6px;">
                    <select name="staff" class="filter-dropdown" style="padding:9px 12px;font-size:13px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;outline:none;min-height:40px;">
                        <option value="">Semua Staf</option>
                        @foreach($subordinateStaff as $staff)
                            <option value="{{ $staff->id }}" {{ ($staffFilter ?? '') == $staff->id ? 'selected' : '' }}>{{ explode(' ', $staff->name)[0] }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            {{-- Search Input --}}
            <div style="position:relative;flex:1 1 220px;min-width:200px;">
                <svg class="lucide sm" viewBox="0 0 24 24" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--fg-4);">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="search-input" name="search" value="{{ $search ?? '' }}" placeholder="Cari laporan..."
                       style="width:100%;padding:9px 12px 9px 34px;font-size:16px;border-radius:8px;border:1px solid #E2E8F0;outline:none;box-shadow:none;min-height:40px;"
                       onfocus="this.style.borderColor='var(--maxy-navy)'; this.style.boxShadow='0 0 0 2px rgba(18,52,130,0.2)'"
                       onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none'">
            </div>

        </div>
        </form>

        <script>
            // Script Auto-Submit
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('filter-form');
                const dropdowns = document.querySelectorAll('.filter-dropdown');
                const searchInput = document.getElementById('search-input');
                let debounceTimer;

                // Submit saat dropdown berubah
                dropdowns.forEach(dropdown => {
                    dropdown.addEventListener('change', function() {
                        form.submit();
                    });
                });

                // Debounce submit saat mengetik di search input
                if(searchInput) {
                    searchInput.addEventListener('input', function() {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            form.submit();
                        }, 500); // Tunggu 0.5 detik setelah selesai mengetik
                    });

                    // Taruh kursor di akhir teks agar nyaman jika direfresh
                    const val = searchInput.value;
                    if(val) {
                        searchInput.focus();
                        searchInput.setSelectionRange(val.length, val.length);
                    }
                }
            });
        </script>

        @if($entries->isEmpty())
            <div class="m-card">
                <div class="empty-state">
                    <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                        <path
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p style="font-size:14px;margin-bottom:8px;">Belum ada laporan tugas.</p>
                    <span style="font-size:13px;font-weight:600;color:var(--fg-4);">Klik + Tambah untuk membuat laporan</span>
                </div>
            </div>
        @else
            <div class="dt-card-grid">
                @foreach($entries as $entry)
                    @php
                        $statusMap = [
                            'belum_mulai' => 'neutral',
                            'dalam_proses' => 'warning',
                            'terhambat' => 'danger',
                            'selesai' => 'success',
                        ];
                        $sChip = $statusMap[$entry->status] ?? 'neutral';
                        $priorityChip = [
                            'critical' => 'danger',
                            'high'     => 'warning',
                            'medium'   => 'info',
                            'low'      => 'neutral',
                        ][$entry->priority] ?? 'neutral';
                    @endphp
                    <a href="{{ route('daily-tasks.show', $entry->id) }}{{ $tab === 'review' ? '?from=review' : '' }}"
                       class="m-card" style="text-decoration:none;color:inherit;cursor:pointer;padding:16px;display:flex;flex-direction:column;gap:10px;">
                        {{-- Header card --}}
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                            <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:10px;">
                                
                                {{-- Nama Pengirim (Sender) --}}
                                <div style="display:flex; align-items:center; gap:6px; padding-bottom:8px; border-bottom:1px dashed var(--neutral-200); margin-bottom:4px;">
                                    <span class="sender-av">
                                        <svg class="lucide" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                    </span>
                                    <span style="font-size:13.5px; font-weight:700; color:var(--fg-1);">{{ $entry->user->name ?? 'Unknown' }}</span>
                                    <span style="font-size:12px; color:var(--fg-3);">({{ ucfirst($entry->user->role ?? 'Staf') }})</span>
                                </div>

                                @if($entry->weeklyTarget && $entry->weeklyTarget->monthlyTarget)
                                    {{-- Target Bulanan --}}
                                    <div>
                                        <span class="eyebrow">Target Bulanan</span>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            {{ Str::limit($entry->weeklyTarget->monthlyTarget->title, 80) }}
                                        </div>
                                    </div>
                                    {{-- Target Mingguan --}}
                                    <div>
                                        <span class="eyebrow eyebrow-amber">Target Mingguan</span>
                                        <div style="font-size:13px; font-weight:600; color:var(--fg-2); line-height:1.3;">
                                            {{ Str::limit($entry->weeklyTarget->title, 80) }}
                                        </div>
                                    </div>
                                @elseif($entry->monthlyTarget)
                                    {{-- Target Bulanan --}}
                                    <div>
                                        <span class="eyebrow">Target Bulanan</span>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            {{ Str::limit($entry->monthlyTarget->title, 80) }}
                                        </div>
                                    </div>
                                @else
                                    {{-- Tugas Tambahan --}}
                                    <div>
                                        <span class="eyebrow eyebrow-amber">Tipe Tugas</span>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            Tugas Tambahan
                                        </div>
                                    </div>
                                @endif

                                {{-- Laporan --}}
                                <div>
                                    <span class="eyebrow eyebrow-muted">Laporan Dikirim</span>
                                    <div style="font-size:13px; font-weight:400; color:var(--fg-2); line-height:1.45;">
                                        {{ $entry->task_description }}
                                    </div>
                                </div>

                            </div>
                            <span class="m-checkbox {{ $entry->status === 'selesai' ? 'done' : '' }}" style="flex-shrink:0;" aria-hidden="true">
                                @if($entry->status === 'selesai')
                                    <svg style="width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 16 16">
                                        <path d="M3 8l3.5 3.5L13 5" />
                                    </svg>
                                @endif
                            </span>
                        </div>
                        {{-- Chips --}}
                        <div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;">
                            <span class="chip chip-{{ $sChip }}">{{ $entry->status_label }}</span>
                            <span class="chip chip-{{ $entry->verification_chip }}">{{ $entry->verification_status_label }}</span>
                            @if($entry->priority !== 'medium')
                                <span class="chip chip-{{ $priorityChip }}">{{ $entry->priority_label }}</span>
                            @endif
                            @if($entry->is_overdue)
                                <span class="chip chip-danger">Terlambat</span>
                            @endif
                        </div>
                        {{-- Meta + petunjuk klik --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);border-top:1px solid var(--neutral-100);padding-top:10px;">
                            <span>{{ \Carbon\Carbon::parse($entry->task_date)->format('d M Y') }} · {{ $entry->duration_label }}</span>
                            <span style="display:inline-flex;align-items:center;gap:3px;color:var(--maxy-navy);font-weight:600;">
                                Lihat
                                <svg class="lucide" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Pagination --}}
        @if($entries->hasPages())
        <div style="padding:16px 0 8px;display:flex;flex-direction:column;align-items:center;gap:10px;">
            <div style="font-size:12px;color:var(--fg-3);">
                Menampilkan {{ $entries->firstItem() }}–{{ $entries->lastItem() }} dari {{ $entries->total() }} laporan
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;">
                {{-- Prev --}}
                @if($entries->onFirstPage())
                    <span style="padding:6px 14px;border-radius:8px;background:var(--neutral-100);color:var(--fg-4);font-size:13px;font-weight:600;cursor:default;">← Sebelumnya</span>
                @else
                    <a href="{{ $entries->previousPageUrl() }}" style="padding:6px 14px;border-radius:8px;background:var(--neutral-50);border:1px solid var(--neutral-200);color:var(--maxy-navy);font-size:13px;font-weight:600;text-decoration:none;">← Sebelumnya</a>
                @endif

                {{-- Page Numbers --}}
                @foreach($entries->getUrlRange(max(1, $entries->currentPage()-2), min($entries->lastPage(), $entries->currentPage()+2)) as $page => $url)
                    @if($page == $entries->currentPage())
                        <span style="padding:6px 12px;border-radius:8px;background:var(--maxy-navy);color:#fff;font-size:13px;font-weight:700;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;background:var(--neutral-50);border:1px solid var(--neutral-200);color:var(--fg-2);font-size:13px;font-weight:600;text-decoration:none;">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($entries->hasMorePages())
                    <a href="{{ $entries->nextPageUrl() }}" style="padding:6px 14px;border-radius:8px;background:var(--neutral-50);border:1px solid var(--neutral-200);color:var(--maxy-navy);font-size:13px;font-weight:600;text-decoration:none;">Berikutnya →</a>
                @else
                    <span style="padding:6px 14px;border-radius:8px;background:var(--neutral-100);color:var(--fg-4);font-size:13px;font-weight:600;cursor:default;">Berikutnya →</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</x-app-layout>