<x-app-layout>

    <div class="page">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Tugas Saya</h1>
                <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">{{ $entries->count() }} laporan tercatat</p>
            </div>
            <a href="{{ route('daily-tasks.create') }}" class="btn btn-primary btn-sm">
                <svg class="lucide sm" viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Laporan Harian (Task)
            </a>
        </div>

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
            <div class="m-card" style="padding:4px 16px;">
                @foreach($entries as $entry)
                    @php
                        $statusMap = [
                            'belum_mulai' => 'neutral',
                            'dalam_proses' => 'warning',
                            'terhambat' => 'danger',
                            'selesai' => 'success',
                        ];
                        $sChip = $statusMap[$entry->status] ?? 'neutral';
                    @endphp
                    <div class="m-row">
                        <span class="m-checkbox {{ $entry->status === 'selesai' ? 'done' : '' }}" aria-hidden="true">
                            @if($entry->status === 'selesai')
                                <svg style="width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;"
                                    viewBox="0 0 16 16">
                                    <path d="M3 8l3.5 3.5L13 5" />
                                </svg>
                            @endif
                        </span>
                        <a href="{{ route('daily-tasks.show', $entry->id) }}" class="row-body"
                            style="text-decoration:none;color:inherit;cursor:pointer;">
                            <div class="row-title">
                                {{ $entry->task_description }}
                                @if($entry->is_overdue)
                                    <span class="chip chip-danger" style="margin-left:6px;font-size:10px;">⏰ Terlambat</span>
                                @endif
                            </div>
                            <div class="row-meta">
                                <span class="chip chip-{{ $sChip }}">{{ $entry->status_label }}</span>
                                {{-- Badge verifikasi --}}
                                <span class="chip chip-{{ $entry->verification_chip }}" style="font-size:10px;">
                                    @if($entry->verification_status === 'approved') ✅
                                    @elseif($entry->verification_status === 'revision') ↩
                                    @elseif($entry->verification_status === 'rejected') ❌
                                    @else ⏳
                                    @endif
                                </span>
                                @if($entry->weeklyTarget)
                                    <span>· {{ Str::limit($entry->weeklyTarget->title, 22) }}</span>
                                @elseif($entry->monthlyTarget)
                                    <span>· {{ Str::limit($entry->monthlyTarget->title, 22) }}</span>
                                @endif
                                <span>· {{ \Carbon\Carbon::parse($entry->task_date)->format('d M') }}</span>
                                <span>· {{ $entry->duration_label }}</span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>