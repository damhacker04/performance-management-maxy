<x-app-layout>

<div class="page">
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Tugas Saya</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">{{ $entries->count() }} laporan tercatat</p>
        </div>
        <a href="{{ route('daily-tasks.create') }}" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </a>
    </div>

    @if($entries->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p style="font-size:14px;margin-bottom:8px;">Belum ada laporan tugas.</p>
                <a href="{{ route('daily-tasks.create') }}" style="font-size:13px;font-weight:600;color:var(--maxy-navy);">Tambah laporan pertama →</a>
            </div>
        </div>
    @else
        <div class="m-card" style="padding:4px 16px;">
            @foreach($entries as $entry)
                @php
                    $statusMap = ['selesai'=>'success','dalam_proses'=>'warning','terhambat'=>'danger'];
                    $sChip  = $statusMap[$entry->status] ?? 'neutral';
                    $sLabel = ['selesai'=>'Selesai','dalam_proses'=>'Dalam Proses','terhambat'=>'Terhambat'][$entry->status] ?? $entry->status;
                @endphp
                <div class="m-row">
                    <span class="m-checkbox {{ $entry->status === 'selesai' ? 'done' : '' }}" aria-hidden="true">
                        @if($entry->status === 'selesai')
                            <svg style="width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 16 16"><path d="M3 8l3.5 3.5L13 5"/></svg>
                        @endif
                    </span>
                    <div class="row-body">
                        <div class="row-title">{{ $entry->task_description }}</div>
                        <div class="row-meta">
                            <span class="chip chip-{{ $sChip }}">{{ $sLabel }}</span>
                            @if($entry->monthlyTarget)
                                <span>· {{ Str::limit($entry->monthlyTarget->title, 22) }}</span>
                            @endif
                            <span>· {{ \Carbon\Carbon::parse($entry->task_date)->format('d M') }}</span>
                            <span>· {{ $entry->duration_minutes }}m</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <a href="{{ route('daily-tasks.create') }}" class="btn btn-primary btn-block">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Tambah laporan harian
    </a>
</div>
</x-app-layout>
