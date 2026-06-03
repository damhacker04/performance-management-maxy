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

    // Smart back: kembali ke halaman sebelumnya (referrer)
    $prev = url()->previous();
    $backUrl = ($prev !== url()->current() && !str_contains($prev, '/edit'))
        ? $prev 
        : route('daily-tasks.index');
@endphp

<div class="page">
    <!-- Back & header -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ $backUrl }}" class="icon-btn" style="margin-left:-8px;">
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

    {{-- Info kapan bisa di-edit (hanya untuk pemilik laporan) --}}
    @if($dailyTask->user_id === auth()->id())
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

    {{-- Jika leader/c-level mengakses laporan staff: tampilkan banner info --}}
    @if($dailyTask->user_id !== auth()->id())
        <div style="background:var(--bg-2);border:1px solid var(--bg-3);border-radius:10px;
                    padding:10px 12px;display:flex;gap:8px;align-items:center;font-size:11px;color:var(--fg-3);">
            <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;" viewBox="0 0 24 24">
                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span>Anda melihat laporan milik <strong>{{ $dailyTask->user->name ?? 'Staff' }}</strong> (mode baca saja)</span>
        </div>
    @endif

    {{-- Mulai grid desktop: kolom kiri = info & aksi, kolom kanan = activity log --}}
    <div class="dt-detail-grid">
    <div class="dt-detail-left">

    <!-- Status, Prioritas & Verifikasi -->
    <div class="m-card" style="display:flex;flex-direction:column;gap:10px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;color:var(--fg-3);">Status</span>
            <span class="chip chip-{{ $sChip }}" style="font-size:13px;font-weight:600;">
                {{ $dailyTask->status_label }}
            </span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;color:var(--fg-3);">Prioritas</span>
            <span class="chip chip-{{ $pChip }}" style="font-size:12px;">
                {{ $dailyTask->priority_label }}
            </span>
        </div>
        {{-- Badge Verifikasi --}}
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;color:var(--fg-3);">Verifikasi</span>
            <span class="chip chip-{{ $dailyTask->verification_chip }}" style="font-size:12px;">
                @if($dailyTask->verification_status === 'approved') ✅
                @elseif($dailyTask->verification_status === 'revision') ↩
                @elseif($dailyTask->verification_status === 'rejected') ❌
                @else ⏳
                @endif
                {{ $dailyTask->verification_status_label }}
            </span>
        </div>
        @if($dailyTask->is_overdue)
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:var(--fg-3);">Peringatan</span>
                <span class="chip chip-danger" style="font-size:12px;">⏰ Terlambat</span>
            </div>
        @endif
    </div>

    {{-- Notifikasi untuk STAFF: laporan butuh revisi atau ditolak --}}
    @if($dailyTask->user_id === auth()->id())

        {{-- Card: revisi sudah terkirim, menunggu persetujuan leader --}}
        @if($dailyTask->verification_status === 'pending' && $dailyTask->reviewed_at)
            <div style="background:#E8F7F4;border:1px solid #16A571;border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;">
                <span style="font-size:18px;flex-shrink:0;">📨</span>
                <div>
                    <div style="font-size:12px;font-weight:700;color:#0F7A50;margin-bottom:3px;">Revisi Sudah Terkirim</div>
                    <p style="font-size:12px;color:#0D6A44;margin:0;line-height:1.5;">
                        Laporan revisimu sudah dikirim ke leader pada
                        <strong>{{ $dailyTask->updated_at->isoFormat('D MMM YYYY, HH:mm') }}</strong>.
                        Tunggu persetujuan — kamu akan bisa melihat hasilnya di sini.
                    </p>
                </div>
            </div>
        @endif

        @if($dailyTask->verification_status === 'revision')
            <div style="background:#FFF8E8;border:1px solid #FBB041;border-radius:10px;padding:12px 14px;">
                <div style="font-size:12px;font-weight:700;color:#B45309;margin-bottom:10px;">↩ Laporan Perlu Direvisi</div>

                {{-- Timeline riwayat semua catatan revisi --}}
                @if($dailyTask->revision_history && count($dailyTask->revision_history) > 0)
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px;">
                        @foreach($dailyTask->revision_history as $i => $rev)
                            @php $isLatest = $loop->last; @endphp
                            <div style="display:flex;gap:8px;align-items:flex-start;">
                                {{-- Garis timeline --}}
                                <div style="display:flex;flex-direction:column;align-items:center;gap:2px;flex-shrink:0;">
                                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $isLatest ? '#F59E0B' : '#D1D5DB' }};margin-top:3px;"></div>
                                    @if(!$loop->last)
                                        <div style="width:1px;flex:1;min-height:20px;background:#E5E7EB;"></div>
                                    @endif
                                </div>
                                {{-- Konten catatan --}}
                                <div style="flex:1;background:{{ $isLatest ? '#FFFBEB' : '#F9FAFB' }};border:1px solid {{ $isLatest ? '#FDE68A' : '#E5E7EB' }};border-radius:8px;padding:8px 10px;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                        <span style="font-size:11px;font-weight:700;color:{{ $isLatest ? '#B45309' : '#6B7280' }};">
                                            {{ $rev['by'] ?? 'Leader' }}
                                            @if($isLatest) · <em>terbaru</em>@endif
                                        </span>
                                        <span style="font-size:10px;color:#9CA3AF;">{{ \Carbon\Carbon::parse($rev['at'])->isoFormat('D MMM, HH:mm') }}</span>
                                    </div>
                                    <p style="font-size:12px;color:{{ $isLatest ? '#8B5A00' : '#374151' }};margin:0;line-height:1.5;">{{ $rev['note'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Fallback untuk data lama sebelum fitur histori --}}
                    <p style="font-size:12px;color:#8B5A00;margin:0 0 8px;line-height:1.5;">
                        <strong>Catatan dari {{ $dailyTask->verifiedBy->name ?? 'Leader' }}:</strong><br>
                        {{ $dailyTask->rejection_note }}
                    </p>
                @endif

                @if($dailyTask->canBeRevised())
                    @php
                        $deadline    = $dailyTask->reviewed_at->addHours(10);
                        $sisaMenit   = max(0, now()->diffInMinutes($deadline, false));
                        $sisaJam     = floor($sisaMenit / 60);
                        $sisaMinSisa = $sisaMenit % 60;
                        $sisaStr     = $sisaJam > 0 ? "{$sisaJam} jam {$sisaMinSisa} menit" : "{$sisaMenit} menit";
                        $urgent      = $sisaMenit <= 60; // merah kalau sisa ≤ 1 jam
                    @endphp
                    <a href="{{ route('daily-tasks.edit', $dailyTask) }}" class="btn btn-primary btn-sm">✏️ Revisi Laporan</a>
                    <span style="font-size:11px;margin-left:8px;padding:3px 8px;border-radius:6px;
                                 background:{{ $urgent ? '#FEF2F2' : '#FFFBEB' }};
                                 color:{{ $urgent ? '#B91C1C' : '#8B5A00' }};
                                 font-weight:{{ $urgent ? '700' : '500' }};">
                        {{ $urgent ? '⚠️' : '⏱' }} Sisa: {{ $sisaStr }}
                    </span>
                @else
                    <span style="font-size:11px;color:#92400E;background:#FEF3C7;padding:4px 8px;border-radius:6px;">⏰ Masa revisi sudah berakhir</span>
                @endif
            </div>
        @elseif($dailyTask->verification_status === 'rejected')
            <div style="background:#FFF1F2;border:1px solid #F87171;border-radius:10px;padding:12px 14px;">
                <div style="font-size:12px;font-weight:700;color:#B91C1C;margin-bottom:4px;">❌ Laporan Ditolak</div>
                <p style="font-size:12px;color:#7F1D1D;margin:0;line-height:1.5;">
                    <strong>Alasan dari {{ $dailyTask->verifiedBy->name ?? 'Leader' }}:</strong><br>
                    {{ $dailyTask->rejection_note }}
                </p>
                <p style="font-size:11px;color:#9CA3AF;margin:8px 0 0;">Laporan ini ditolak secara permanen dan tidak dapat direvisi.</p>
            </div>
        @endif
    @endif

    {{-- Panel Aksi LEADER / C-LEVEL --}}
    @if(in_array(auth()->user()->role, ['leader', 'c_level', 'super_admin']) && $dailyTask->user_id !== auth()->id())
        @if($dailyTask->verification_status === 'approved')
            <div style="background:#E8F7EE;border:1px solid #16A571;border-radius:10px;padding:12px 14px;display:flex;gap:8px;align-items:center;font-size:12px;color:#0F7A50;">
                <svg class="lucide" style="width:16px;height:16px;flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Diverifikasi oleh <strong>{{ $dailyTask->verifiedBy->name ?? '-' }}</strong>
                pada {{ $dailyTask->verified_at?->isoFormat('D MMM YYYY, HH:mm') }}</span>
            </div>
        @elseif($dailyTask->verification_status === 'rejected')
            <div style="background:#FFF1F2;border:1px solid #F87171;border-radius:10px;padding:12px 14px;display:flex;gap:8px;align-items:flex-start;">
                <svg class="lucide" style="width:16px;height:16px;flex-shrink:0;color:#B91C1C;margin-top:1px;" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <div style="font-size:12px;font-weight:700;color:#B91C1C;margin-bottom:2px;">Laporan Ditolak Permanen</div>
                    <div style="font-size:12px;color:#7F1D1D;">
                        Ditolak oleh <strong>{{ $dailyTask->verifiedBy->name ?? '-' }}</strong>
                        · {{ $dailyTask->reviewed_at?->isoFormat('D MMM YYYY, HH:mm') }}
                    </div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Laporan ini tidak dapat diubah, disetujui, maupun ditolak ulang.</div>
                </div>
            </div>
        @else
            <div class="m-card" style="display:flex;flex-direction:column;gap:12px;">
                <div class="overline-label">Aksi Verifikasi</div>

                {{-- Tombol Setujui --}}
                <form method="POST" action="{{ route('daily-tasks.approve', $dailyTask) }}"
                      onsubmit="return confirm('Setujui laporan ini? Laporan akan terkunci setelah disetujui.');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-block" style="background:#16A571;">
                        ✅ Setujui Laporan
                    </button>
                </form>

                {{-- Form Kembalikan untuk Revisi --}}
                <details style="border:1px solid #FBB041;border-radius:8px;padding:12px;">
                    <summary style="font-size:13px;font-weight:600;color:#B45309;cursor:pointer;">↩ Kembalikan untuk Direvisi</summary>
                    <form method="POST" action="{{ route('daily-tasks.revision', $dailyTask) }}" style="margin-top:10px;">
                        @csrf @method('PATCH')
                        <textarea name="rejection_note" rows="3" required minlength="10"
                            placeholder="Tuliskan apa yang perlu diperbaiki staff..."
                            style="width:100%;font-size:13px;padding:8px 10px;border:1px solid var(--bg-3);border-radius:8px;resize:vertical;"></textarea>
                        <button type="submit" class="btn btn-sm" style="margin-top:8px;background:#FBB041;color:#fff;width:100%;">Kirim & Kembalikan ke Staff</button>
                    </form>
                </details>

                {{-- Form Tolak Permanen --}}
                <details style="border:1px solid #F87171;border-radius:8px;padding:12px;">
                    <summary style="font-size:13px;font-weight:600;color:#B91C1C;cursor:pointer;">❌ Tolak Permanen</summary>
                    <p style="font-size:11px;color:#9CA3AF;margin:6px 0;">Staff tidak akan bisa merevisi laporan ini setelah ditolak.</p>
                    <form method="POST" action="{{ route('daily-tasks.reject', $dailyTask) }}" style="margin-top:6px;"
                          onsubmit="return confirm('Tolak permanen laporan ini? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf @method('PATCH')
                        <textarea name="rejection_note" rows="3" required minlength="10"
                            placeholder="Tuliskan alasan penolakan..."
                            style="width:100%;font-size:13px;padding:8px 10px;border:1px solid var(--bg-3);border-radius:8px;resize:vertical;"></textarea>
                        <button type="submit" class="btn btn-sm" style="margin-top:8px;background:#EF4444;color:#fff;width:100%;">Tolak Laporan Ini</button>
                    </form>
                </details>
            </div>
        @endif
    @endif



    <!-- Detail card (full-width di bawah grid) -->


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
                    <div style="font-size:11px;color:var(--fg-3);margin-top:6px;display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                        <span>↳ Target bulanan: <strong>{{ $dailyTask->weeklyTarget->monthlyTarget->title }}</strong></span>
                        @if($dailyTask->weeklyTarget->monthlyTarget->user)
                            <span style="display:inline-flex;align-items:center;gap:4px;">
                                <svg class="lucide" style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                                Ditugaskan oleh: <strong>{{ $dailyTask->weeklyTarget->monthlyTarget->user->name }}</strong>
                                <span style="font-size:10px;padding:1px 5px;border-radius:4px;background:var(--bg-2);color:var(--fg-2);">
                                    {{ ucfirst(str_replace('_', ' ', $dailyTask->weeklyTarget->monthlyTarget->user->role)) }}
                                </span>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            <div style="height:1px;background:var(--bg-3);"></div>
        @else
            {{-- Task "Other" — tidak terikat weekly target --}}
            <div style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:8px;padding:10px 12px;">
                <div style="font-size:11px;font-weight:700;color:#B45309;text-transform:uppercase;letter-spacing:.05em;">📌 Tugas Tambahan / Mendadak</div>
                <p style="font-size:12px;color:#8B5A00;margin:4px 0 0;line-height:1.5;">
                    Tidak terkait target mingguan — ini merupakan tugas tambahan atau instruksi langsung dari atasan.
                </p>
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

        {{-- Bukti Laporan --}}
        @if($dailyTask->proof_url || $dailyTask->proof_file)
            <div style="height:1px;background:var(--bg-3);"></div>
            <div>
                <div class="overline-label" style="margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    <svg class="lucide" style="width:13px;height:13px;" viewBox="0 0 24 24"><path d="M15.5 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V8.5L15.5 3z"/><polyline points="15 3 15 9 21 9"/></svg>
                    Bukti Laporan
                </div>

                @if($dailyTask->proof_url)
                    <div style="display:flex;align-items:center;gap:8px;background:var(--bg-2);
                                border:1px solid var(--bg-3);border-radius:8px;padding:10px 12px;
                                margin-bottom:6px;">
                        <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;color:var(--maxy-navy);" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;color:var(--fg-4);margin-bottom:2px;">Link Bukti</div>
                            <a href="{{ $dailyTask->proof_url }}" target="_blank"
                               style="font-size:12px;color:var(--maxy-navy);font-weight:600;
                                      word-break:break-all;text-decoration:none;"
                               rel="noopener noreferrer">
                                {{ Str::limit($dailyTask->proof_url, 55) }}
                                <svg class="lucide" style="width:11px;height:11px;vertical-align:middle;" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        </div>
                    </div>
                @endif

                @if($dailyTask->proof_file)
                    @php
                        $ext      = strtolower(pathinfo($dailyTask->proof_file, PATHINFO_EXTENSION));
                        $isImage  = in_array($ext, ['jpg', 'jpeg', 'png']);
                        $fileUrl  = Storage::url($dailyTask->proof_file);
                        $fileName = basename($dailyTask->proof_file);
                    @endphp

                    @if($isImage)
                        <div style="border:1px solid var(--bg-3);border-radius:10px;overflow:hidden;">
                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $fileUrl }}" alt="Bukti laporan"
                                     style="width:100%;max-height:220px;object-fit:cover;display:block;">
                            </a>
                            <div style="padding:6px 10px;font-size:11px;color:var(--fg-3);background:var(--bg-2);">
                                📸 {{ $fileName }} · <a href="{{ $fileUrl }}" target="_blank" style="color:var(--maxy-navy);">Buka penuh</a>
                            </div>
                        </div>
                    @else
                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer"
                           style="display:flex;align-items:center;gap:8px;background:var(--bg-2);
                                  border:1px solid var(--bg-3);border-radius:8px;padding:10px 12px;
                                  text-decoration:none;color:inherit;">
                            <svg class="lucide" style="width:20px;height:20px;flex-shrink:0;color:#E53E3E;" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $fileName }}</div>
                                <div style="font-size:10px;color:var(--fg-4);">PDF · Tap untuk membuka</div>
                            </div>
                            <svg class="lucide" style="width:14px;height:14px;color:var(--fg-3);" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    @endif
                @endif
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
                {{ \Carbon\Carbon::parse($dailyTask->task_date)->isoFormat('D MMMM YYYY') }}
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
    @if($dailyTask->status !== 'selesai' && $dailyTask->user_id === auth()->id())
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
    </div>{{-- end dt-detail-left --}}

    {{-- Kolom kanan: Activity Log ──────────────────────────── --}}
    <div class="dt-detail-right">

    {{-- ── ACTIVITY LOG TIMELINE ────────────────────────────────────────────── --}}
    @php
        // Bangun array events dari semua sumber data
        $events = [];

        // Event 1: Laporan dikirim
        $events[] = [
            'type'  => 'submitted',
            'at'    => $dailyTask->created_at,
            'by'    => $dailyTask->user->name,
            'role'  => 'staff',
            'note'  => null,
        ];

        // Events dari revision_history (revisi leader + respons staff)
        if (!empty($dailyTask->revision_history)) {
            foreach ($dailyTask->revision_history as $rev) {
                // Catatan revisi dari Leader
                $events[] = [
                    'type'  => 'revision_requested',
                    'at'    => isset($rev['at']) ? \Carbon\Carbon::parse($rev['at']) : null,
                    'by'    => $rev['by'] ?? 'Leader',
                    'role'  => 'leader',
                    'note'  => $rev['note'] ?? null,
                ];
                // Respons Staff (jika ada)
                if (!empty($rev['staff_response'])) {
                    $events[] = [
                        'type'  => 'staff_responded',
                        'at'    => isset($rev['staff_responded_at']) ? \Carbon\Carbon::parse($rev['staff_responded_at']) : null,
                        'by'    => $rev['staff_name'] ?? $dailyTask->user->name,
                        'role'  => 'staff',
                        'note'  => $rev['staff_response'],
                    ];
                }
            }
        }

        // Event final: Approved atau Rejected
        if ($dailyTask->verification_status === 'approved' && $dailyTask->reviewed_at) {
            $events[] = [
                'type'  => 'approved',
                'at'    => $dailyTask->reviewed_at,
                'by'    => $dailyTask->reviewer?->name ?? 'Leader',
                'role'  => 'leader',
                'note'  => null,
            ];
        } elseif ($dailyTask->verification_status === 'rejected' && $dailyTask->reviewed_at) {
            $events[] = [
                'type'  => 'rejected',
                'at'    => $dailyTask->reviewed_at,
                'by'    => $dailyTask->reviewer?->name ?? 'Leader',
                'role'  => 'leader',
                'note'  => $dailyTask->rejection_note,
            ];
        }

        // Urutkan berdasarkan waktu
        usort($events, fn($a, $b) =>
            ($a['at'] ? $a['at']->timestamp : 0) <=> ($b['at'] ? $b['at']->timestamp : 0)
        );
    @endphp

    <div class="m-card" style="display:flex;flex-direction:column;gap:0;padding:16px 16px 8px;">
        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="font-size:14px;">🕐</span>
                <span class="overline-label" style="font-size:11px;">Activity Log</span>
            </div>
            <span style="font-size:10px;font-weight:600;background:#EDE9FE;color:#6D28D9;padding:2px 8px;border-radius:99px;">
                {{ count($events) }} aktivitas
            </span>
        </div>

        {{-- Timeline --}}
        <div style="display:flex;flex-direction:column;position:relative;max-height:400px;overflow-y:auto;padding-right:8px;scrollbar-width:thin;">
            @foreach($events as $idx => $event)
            @php
                $isLast = $idx === count($events) - 1;
                $cfg = match($event['type']) {
                    'submitted'          => ['icon'=>'📤','color'=>'#3B82F6','bg'=>'#EFF6FF','border'=>'#BFDBFE','label'=>'Laporan Dikirim','labelColor'=>'#1D4ED8'],
                    'revision_requested' => ['icon'=>'🔄','color'=>'#7C3AED','bg'=>'#F5F3FF','border'=>'#C4B5FD','label'=>'Revisi Diminta','labelColor'=>'#5B21B6'],
                    'staff_responded'    => ['icon'=>'✏️','color'=>'#059669','bg'=>'#ECFDF5','border'=>'#A7F3D0','label'=>'Staff Memperbarui','labelColor'=>'#065F46'],
                    'approved'           => ['icon'=>'✅','color'=>'#16A34A','bg'=>'#F0FDF4','border'=>'#86EFAC','label'=>'Disetujui','labelColor'=>'#15803D'],
                    'rejected'           => ['icon'=>'❌','color'=>'#DC2626','bg'=>'#FFF5F5','border'=>'#FECACA','label'=>'Ditolak','labelColor'=>'#B91C1C'],
                    default              => ['icon'=>'🔔','color'=>'#64748B','bg'=>'#F8FAFC','border'=>'#E2E8F0','label'=>'Event','labelColor'=>'#475569'],
                };
                $initial = strtoupper(mb_substr($event['by'], 0, 1));
            @endphp

            <div style="display:flex;gap:12px;position:relative;">
                {{-- Garis vertikal kiri --}}
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                    {{-- Dot icon --}}
                    <div style="
                        width:32px;height:32px;border-radius:50%;
                        background:{{ $cfg['bg'] }};
                        border:2px solid {{ $cfg['border'] }};
                        display:flex;align-items:center;justify-content:center;
                        font-size:13px;flex-shrink:0;z-index:1;
                    ">{{ $cfg['icon'] }}</div>
                    {{-- Connector line --}}
                    @if(!$isLast)
                    <div style="width:2px;flex:1;min-height:16px;background:linear-gradient({{ $cfg['color'] }}40,#E5E7EB);margin:2px 0;"></div>
                    @endif
                </div>

                {{-- Content --}}
                <div style="flex:1;padding-bottom:{{ $isLast ? '4px' : '14px' }};">
                    {{-- Label + waktu --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:4px;margin-bottom:4px;">
                        <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                            <span style="font-size:11px;font-weight:700;color:{{ $cfg['labelColor'] }};
                                         background:{{ $cfg['bg'] }};border:1px solid {{ $cfg['border'] }};
                                         padding:1px 7px;border-radius:99px;">
                                {{ $cfg['label'] }}
                            </span>
                            <span style="font-size:12px;font-weight:600;color:var(--fg-1);">{{ $event['by'] }}</span>
                            <span style="font-size:10px;color:var(--fg-3);background:var(--bg-2);padding:1px 5px;border-radius:4px;">
                                {{ $event['role'] === 'leader' ? 'Leader' : 'Staff' }}
                            </span>
                        </div>
                        @if($event['at'])
                        <span style="font-size:10px;color:var(--fg-3);">{{ $event['at']->isoFormat('D MMM, HH:mm') }}</span>
                        @endif
                    </div>

                    {{-- Isi catatan (jika ada) --}}
                    @if($event['note'])
                    <div style="
                        background:{{ $cfg['bg'] }};
                        border:1px solid {{ $cfg['border'] }};
                        border-radius:8px;
                        padding:8px 10px;
                        margin-top:4px;
                    ">
                        <p style="font-size:12px;color:var(--fg-2);margin:0;line-height:1.6;white-space:pre-wrap;">{{ $event['note'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Status aktif saat ini (bawah timeline) --}}
        @if($dailyTask->verification_status === 'revision')
        <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:8px 12px;
                    font-size:12px;color:#92400E;display:flex;align-items:center;gap:6px;margin-top:8px;">
            <span>⏳</span>
            <span>Menunggu staff merespons catatan revisi terbaru.</span>
        </div>
        @elseif($dailyTask->verification_status === 'pending' && !empty($dailyTask->revision_history))
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:8px 12px;
                    font-size:12px;color:#166534;display:flex;align-items:center;gap:6px;margin-top:8px;">
            <span>📬</span>
            <span>Staff sudah merespons — laporan menunggu review Anda.</span>
        </div>
        @endif
    </div>

    </div>{{-- end dt-detail-right --}}
    </div>{{-- end dt-detail-grid --}}

</div>
</x-app-layout>
