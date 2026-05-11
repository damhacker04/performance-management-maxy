<x-app-layout>
@php
    $statusMap = [
        'belum_mulai'  => 'neutral',
        'dalam_proses' => 'warning',
        'terhambat'    => 'danger',
        'selesai'      => 'success',
    ];
    $priorityChip = [
        'critical' => 'danger',
        'high'     => 'warning',
        'medium'   => 'info',
        'low'      => 'neutral',
    ];
    $sChip = $statusMap[$dailyTask->status] ?? 'neutral';
    $pChip = $priorityChip[$dailyTask->priority] ?? 'neutral';
@endphp

<div class="page">
    <!-- Back & header -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('daily-tasks.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 style="font-size:17px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.3;">Detail Laporan</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">
                {{ \Carbon\Carbon::parse($dailyTask->task_date)->isoFormat('dddd, D MMMM YYYY') }}
            </p>
        </div>
        @if($dailyTask->canBeEdited() && $dailyTask->user_id === auth()->id())
            <a href="{{ route('daily-tasks.edit', $dailyTask) }}" class="icon-btn" title="Edit laporan" style="color:var(--maxy-navy);">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
        @endif
    </div>

    {{-- Info kapan bisa di-edit --}}
    @if($dailyTask->canBeEdited() && $dailyTask->user_id === auth()->id())
        <div style="background:#FFF8E8;border:1px solid #FBB041;border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;font-size:11px;color:#8B5A00;">
            <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                Laporan ini masih bisa diedit sampai akhir hari atau sampai kamu tandai selesai.
            </div>
        </div>
    @elseif($dailyTask->status === 'selesai')
        <div style="background:#E8F7EE;border:1px solid #16A571;border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;font-size:11px;color:#0F7A50;">
            <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                Laporan sudah ditandai selesai dan terkunci sebagai history.
            </div>
        </div>
    @elseif(!\Carbon\Carbon::parse($dailyTask->task_date)->isToday())
        <div style="background:var(--bg-2);border:1px solid var(--bg-3);border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;font-size:11px;color:var(--fg-3);">
            <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                Laporan hari sebelumnya sudah jadi history dan tidak bisa diubah.
                @if($dailyTask->status !== 'selesai')
                    Untuk update progres, buat <a href="{{ route('daily-tasks.create', ['continue_from' => $dailyTask->id]) }}" style="color:var(--maxy-navy);font-weight:600;">laporan lanjutan hari ini</a>.
                @endif
            </div>
        </div>
    @endif

    <!-- Status bar -->
    <div class="m-card" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="chip chip-{{ $sChip }}" style="font-size:13px;font-weight:600;">
            {{ $dailyTask->status_label }}
        </span>
        <span class="chip chip-{{ $pChip }}" style="font-size:11px;">
            {{ $dailyTask->priority_label }}
        </span>
        @if($dailyTask->is_overdue)
            <span class="chip chip-danger" style="font-size:11px;">⏰ Terlambat</span>
        @endif
        <span style="margin-left:auto;font-size:13px;font-weight:700;color:var(--maxy-navy);">
            {{ $dailyTask->percent_done }}%
        </span>
    </div>

    @if($dailyTask->status !== 'selesai')
        <div class="progress-bar" style="margin-top:-12px;">
            <i class="navy" style="width:{{ $dailyTask->percent_done }}%"></i>
        </div>
    @endif

    <!-- Detail card -->
    <div class="m-card" style="display:flex;flex-direction:column;gap:16px;">
        <!-- Target context -->
        @if($dailyTask->weeklyTarget)
            <div>
                <div class="overline-label" style="margin-bottom:6px;">Target Mingguan</div>
                <div style="font-size:14px;font-weight:600;color:var(--fg-1);line-height:1.4;">
                    {{ $dailyTask->weeklyTarget->title }}
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:6px;">
                    <span class="chip chip-info" style="font-size:11px;">
                        Minggu {{ $dailyTask->weeklyTarget->week_number }}
                    </span>
                    @if($dailyTask->weeklyTarget->target_type === 'quantitative')
                        <span class="chip chip-neutral" style="font-size:11px;">
                            Target: {{ $dailyTask->weeklyTarget->target_label }}
                        </span>
                    @endif
                </div>
                @if($dailyTask->weeklyTarget->monthlyTarget)
                    <div style="font-size:11px;color:var(--fg-3);margin-top:6px;">
                        ↳ Target bulanan: <strong>{{ $dailyTask->weeklyTarget->monthlyTarget->title }}</strong>
                    </div>
                @endif
            </div>

            <div style="height:1px;background:var(--bg-3);"></div>
        @endif

        <!-- Deskripsi -->
        <div>
            <div class="overline-label" style="margin-bottom:6px;">Deskripsi Tugas</div>
            <p style="font-size:14px;color:var(--fg-1);line-height:1.5;margin:0;white-space:pre-wrap;">{{ $dailyTask->task_description }}</p>
        </div>

        <!-- Catatan -->
        @if($dailyTask->notes)
            <div style="height:1px;background:var(--bg-3);"></div>
            <div>
                <div class="overline-label" style="margin-bottom:6px;">
                    @if($dailyTask->status === 'terhambat')
                        Catatan Hambatan
                    @else
                        Catatan Tambahan
                    @endif
                </div>
                <div style="font-size:13px;color:var(--fg-2);line-height:1.5;background:var(--bg-2);padding:10px 12px;border-radius:8px;border-left:3px solid {{ $dailyTask->status === 'terhambat' ? 'var(--danger)' : 'var(--fg-4)' }};white-space:pre-wrap;">{{ $dailyTask->notes }}</div>
            </div>
        @endif
    </div>

    <!-- Meta info -->
    <div class="m-card" style="display:flex;flex-direction:column;gap:12px;">
        <div class="overline-label">Detail Pengerjaan</div>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--fg-3);">Durasi</span>
            <span style="color:var(--fg-1);font-weight:600;">{{ $dailyTask->duration_label }}</span>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--fg-3);">Tanggal tugas</span>
            <span style="color:var(--fg-1);font-weight:600;">
                {{ \Carbon\Carbon::parse($dailyTask->task_date)->format('d M Y') }}
            </span>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--fg-3);">Dikirim</span>
            <span style="color:var(--fg-1);font-weight:600;">
                {{ $dailyTask->created_at->isoFormat('D MMM YYYY, HH:mm') }}
            </span>
        </div>

        @if($dailyTask->updated_at && $dailyTask->updated_at->ne($dailyTask->created_at))
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                <span style="color:var(--fg-3);">Terakhir diubah</span>
                <span style="color:var(--fg-1);font-weight:600;">
                    {{ $dailyTask->updated_at->isoFormat('D MMM YYYY, HH:mm') }}
                </span>
            </div>
        @endif
    </div>

    <!-- Quick action: mark as done (jika belum) -->
    @if($dailyTask->status !== 'selesai')
        <form method="POST" action="{{ route('daily-tasks.complete', $dailyTask->id) }}"
              onsubmit="return confirm('Apakah tugas ini sudah benar-benar selesai? Status tidak bisa diubah lagi setelah dikonfirmasi.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-primary btn-block">
                <svg class="lucide sm" viewBox="0 0 24 24" style="margin-right:4px;"><path d="M5 13l4 4L19 7"/></svg>
                Tandai Selesai
            </button>
        </form>
    @endif
</div>
</x-app-layout>
