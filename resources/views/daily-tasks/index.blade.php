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
        @if(in_array(auth()->user()->role, ['leader', 'c_level', 'super_admin']))
        <div style="display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--bg-3);margin-top:16px;padding-bottom:12px;overflow-x:auto;">
            <a href="{{ route('daily-tasks.index', ['tab' => 'mine']) }}" 
               style="text-decoration:none;padding:6px 12px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;
                      background:{{ $tab === 'mine' ? 'var(--maxy-navy)' : 'var(--bg-2)' }};
                      color:{{ $tab === 'mine' ? '#fff' : 'var(--fg-2)' }};">
                📝 Tugas Saya
            </a>
            <a href="{{ route('daily-tasks.index', ['tab' => 'review']) }}" 
               style="text-decoration:none;padding:6px 12px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:6px;
                      background:{{ $tab === 'review' ? 'var(--maxy-navy)' : 'var(--bg-2)' }};
                      color:{{ $tab === 'review' ? '#fff' : 'var(--fg-2)' }};">
                👀 Menunggu Review
                @if($pendingReviewCount > 0)
                <span style="background:var(--danger);color:#fff;font-size:10px;padding:2px 6px;border-radius:99px;">{{ $pendingReviewCount }}</span>
                @endif
            </a>
            @php
                $backdatePendingCount = \App\Models\BackdateRequest::when(auth()->user()->role === 'leader', fn($q) =>
                    $q->whereHas('user', fn($uq) => $uq->where('department', auth()->user()->department))
                )->where('status', 'pending')->count();
            @endphp
            <a href="{{ route('backdate-requests.index') }}"
               style="text-decoration:none;padding:6px 12px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:6px;
                      background:var(--bg-2);color:var(--fg-2);">
                📅 Izin Backdating
                @if($backdatePendingCount > 0)
                <span style="background:var(--danger);color:#fff;font-size:10px;padding:2px 6px;border-radius:99px;">{{ $backdatePendingCount }}</span>
                @endif
            </a>
        </div>

        @endif

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
                    <a href="{{ route('daily-tasks.show', $entry->id) }}"
                       class="m-card" style="text-decoration:none;color:inherit;cursor:pointer;padding:16px;display:flex;flex-direction:column;gap:10px;">
                        {{-- Header card --}}
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                            <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:10px;">
                                
                                @if($entry->weeklyTarget && $entry->weeklyTarget->monthlyTarget)
                                    {{-- Target Bulanan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Target Bulanan</div>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            {{ Str::limit($entry->weeklyTarget->monthlyTarget->title, 80) }}
                                        </div>
                                    </div>
                                    {{-- Target Mingguan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Target Mingguan</div>
                                        <div style="font-size:13px; font-weight:600; color:var(--fg-2); line-height:1.3;">
                                            {{ Str::limit($entry->weeklyTarget->title, 80) }}
                                        </div>
                                    </div>
                                @elseif($entry->monthlyTarget)
                                    {{-- Target Bulanan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Target Bulanan</div>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            {{ Str::limit($entry->monthlyTarget->title, 80) }}
                                        </div>
                                    </div>
                                @else
                                    {{-- Tugas Tambahan --}}
                                    <div>
                                        <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Tipe Tugas</div>
                                        <div style="font-size:15px; font-weight:700; color:var(--fg-1); line-height:1.3;">
                                            Tugas Tambahan
                                        </div>
                                    </div>
                                @endif

                                {{-- Laporan --}}
                                <div>
                                    <div style="font-size:10px; color:var(--fg-4); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px; font-weight:600;">Laporan Dikirim</div>
                                    <div style="font-size:12px; font-weight:400; color:var(--fg-2); line-height:1.4;">
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
                        <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
                            <span class="chip chip-{{ $sChip }}">{{ $entry->status_label }}</span>
                            <span class="chip chip-{{ $entry->verification_chip }}" style="font-size:10px;">
                                @if($entry->verification_status === 'approved') ✅
                                @elseif($entry->verification_status === 'revision') ↩
                                @elseif($entry->verification_status === 'rejected') ❌
                                @else ⏳
                                @endif
                            </span>
                            @if($entry->priority !== 'medium')
                                <span class="chip chip-{{ $priorityChip }}" style="font-size:10px;">{{ $entry->priority_label }}</span>
                            @endif
                            @if($entry->is_overdue)
                                <span class="chip chip-danger" style="font-size:10px;">⏰ Terlambat</span>
                            @endif
                        </div>
                        {{-- Meta --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--fg-3);">
                            <span>{{ \Carbon\Carbon::parse($entry->task_date)->format('d M Y') }}</span>
                            <span>{{ $entry->duration_label }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>