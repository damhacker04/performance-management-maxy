<x-app-layout>

<div class="page">
    <!-- Header -->
    <div style="display:flex;align-items:center;gap:8px;">
        <x-back-button :fallback="route('dashboard')" style="margin-left:-8px;" />
        <div style="flex:1;min-width:0;">
            <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.2;">Notifikasi</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">Semua riwayat notifikasi kamu</p>
        </div>
        @php
            $unreadNow = $notifications->filter(fn($n) => !$n->read_at)->count();
        @endphp
        @if($unreadNow > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-sm"
                        style="background:var(--bg-2);color:var(--maxy-navy);font-size:11px;font-weight:600;">
                    Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-3);" viewBox="0 0 24 24">
                    <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Belum ada notifikasi</p>
                <p style="font-size:13px;color:var(--fg-3);">Notifikasi revisi dan persetujuan laporan akan muncul di sini.</p>
            </div>
        </div>
    @else
        @php
            // Kelompokkan per tanggal
            $grouped = $notifications->groupBy(fn($n) => $n->created_at->toDateString());
        @endphp

        @foreach($grouped as $date => $notifs)
            @php
                $carbon   = \Carbon\Carbon::parse($date);
                $isToday  = $carbon->isToday();
                $isYest   = $carbon->isYesterday();
                $label    = $isToday ? 'Hari ini' : ($isYest ? 'Kemarin' : $carbon->isoFormat('D MMMM YYYY'));
            @endphp

            <!-- Date header -->
            <div style="display:flex;align-items:center;gap:8px;color:var(--fg-3);
                        font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">
                <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                {{ $label }}
                <div style="flex:1;height:1px;background:var(--bg-3);"></div>
            </div>

            <div class="m-card" style="padding:0;overflow:hidden;">
                @foreach($notifs as $i => $notif)
                    @php
                        $isUnread = !$notif->read_at;
                        $icons = [
                            'revision_requested' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a4 4 0 0 1 0 8h-1"/>',
                            'revision_submitted' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
                            'auto_rejected'      => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
                            'not_submitted'      => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/>',
                        ];
                        $colors = [
                            'revision_requested' => ['bg'=>'#FFF8E8','dot'=>'#F59E0B'],
                            'revision_submitted' => ['bg'=>'#E8F7F4','dot'=>'#16A571'],
                            'auto_rejected'      => ['bg'=>'#FFF1F2','dot'=>'#EF4444'],
                            'not_submitted'      => ['bg'=>'#FFF8E8','dot'=>'#F59E0B'],
                        ];
                        $ic  = $icons[$notif->type]  ?? '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>';
                        $col = $colors[$notif->type] ?? ['bg'=>'#F3F4F6','dot'=>'#9CA3AF'];
                    @endphp

                    <a href="{{ route('notifications.read', $notif) }}"
                       style="display:flex;align-items:flex-start;gap:12px;
                              padding:14px 16px;text-decoration:none;color:inherit;
                              background:{{ $isUnread ? '#F0F7FF' : '#fff' }};
                              border-bottom:{{ !$loop->last ? '1px solid #f3f4f6' : 'none' }};
                              transition:background .15s;">

                        <!-- Icon bubble -->
                        <div style="width:36px;height:36px;border-radius:50%;
                                    background:{{ $col['bg'] }};border:1px solid {{ $col['dot'] }}30;
                                    display:flex;align-items:center;justify-content:center;
                                    flex-shrink:0;color:{{ $col['dot'] }};">
                            <svg class="lucide sm" viewBox="0 0 24 24">{!! $ic !!}</svg>
                        </div>

                        <!-- Content -->
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:3px;">
                                <span style="font-size:13px;font-weight:{{ $isUnread ? '700' : '600' }};
                                             color:var(--fg-1);line-height:1.3;">
                                    {{ $notif->title }}
                                </span>
                                <span style="font-size:11px;color:var(--fg-3);flex-shrink:0;margin-top:2px;">
                                    {{ $notif->created_at->isoFormat('HH:mm') }}
                                </span>
                            </div>
                            <p style="font-size:12px;color:var(--fg-3);margin:0;line-height:1.5;">
                                {{ Str::limit($notif->body, 100) }}
                            </p>

                            {{-- Diff catatan revisi --}}
                            @if($notif->type === 'revision_submitted' && ($notif->getMeta('leader_note') || $notif->getMeta('staff_new_notes')))
                            <div style="margin-top:7px;border-radius:8px;overflow:hidden;border:1px solid #E2E8F0;font-size:11px;">
                                @if($notif->getMeta('leader_note'))
                                <div style="padding:5px 10px;background:#FEF2F2;">
                                    <span style="font-weight:700;color:#DC2626;">Catatan leader:</span><br>
                                    <span style="color:#7F1D1D;">{{ $notif->getMeta('leader_note') }}</span>
                                </div>
                                @endif
                                @if($notif->getMeta('staff_new_notes'))
                                <div style="padding:5px 10px;background:#F0FDF4;border-top:1px solid #BBF7D0;">
                                    <span style="font-weight:700;color:#16A34A;">Jawaban staff:</span><br>
                                    <span style="color:#14532D;">{{ $notif->getMeta('staff_new_notes') }}</span>
                                </div>
                                @endif
                            </div>
                            @endif

                            @if($notif->related_id)
                                <span style="font-size:11px;color:var(--maxy-navy);margin-top:4px;display:inline-block;font-weight:600;">
                                    Lihat laporan →
                                </span>
                            @endif
                        </div>

                        <!-- Unread dot -->
                        @if($isUnread)
                            <div style="width:8px;height:8px;border-radius:50%;
                                        background:#3B82F6;flex-shrink:0;margin-top:6px;"></div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach

        <!-- Pagination -->
        @if($notifications->hasPages())
            <div style="padding:4px 0;">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</div>

</x-app-layout>
