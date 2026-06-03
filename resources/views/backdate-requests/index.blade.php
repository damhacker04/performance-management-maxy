<x-app-layout>
<div class="page">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Permintaan Backdating</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
                Izin staf untuk mengisi laporan tanggal sebelumnya
                @if($pendingCount > 0)
                    · <span style="color:var(--danger);font-weight:700;">{{ $pendingCount }} menunggu review</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Filter tabs --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach(['pending' => '⏳ Menunggu', 'approved' => '✅ Disetujui', 'rejected' => '❌ Ditolak'] as $val => $label)
            <a href="{{ route('backdate-requests.index', ['filter' => $val]) }}"
               style="text-decoration:none;padding:6px 14px;border-radius:99px;font-size:13px;font-weight:600;
                      background:{{ $filter === $val ? 'var(--maxy-navy)' : 'var(--bg-2)' }};
                      color:{{ $filter === $val ? '#fff' : 'var(--fg-2)' }};
                      border:1px solid {{ $filter === $val ? 'var(--maxy-navy)' : 'var(--bg-3)' }};">
                {{ $label }}
                @if($val === 'pending' && $pendingCount > 0)
                    <span style="background:var(--danger);color:#fff;font-size:10px;padding:1px 5px;border-radius:99px;margin-left:4px;">{{ $pendingCount }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @if($requests->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p style="font-size:14px;color:var(--fg-3);">Tidak ada permintaan {{ $filter === 'pending' ? 'yang menunggu' : ($filter === 'approved' ? 'yang disetujui' : 'yang ditolak') }}.</p>
            </div>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($requests as $req)
            @php
                $statusCfg = match($req->status) {
                    'pending'  => ['chip' => 'warning',  'icon' => '⏳', 'label' => 'Menunggu Review'],
                    'approved' => ['chip' => 'success',  'icon' => '✅', 'label' => 'Disetujui'],
                    'rejected' => ['chip' => 'danger',   'icon' => '❌', 'label' => 'Ditolak'],
                    default    => ['chip' => 'neutral',  'icon' => '🔔', 'label' => '-'],
                };
                $isExpired = $req->status === 'approved' && $req->token_expires_at?->isPast();
            @endphp
            <div class="m-card" style="display:flex;flex-direction:column;gap:12px;">
                {{-- Header --}}
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:10px;color:var(--fg-4);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Pengaju</div>
                        <div style="font-size:15px;font-weight:700;color:var(--fg-1);">{{ $req->user->name }}</div>
                        <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">{{ $req->user->division ?? $req->user->department ?? '-' }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <span class="chip chip-{{ $statusCfg['chip'] }}" style="font-size:12px;">
                            {{ $statusCfg['icon'] }} {{ $statusCfg['label'] }}
                        </span>
                        @if($isExpired)
                            <div style="font-size:10px;color:var(--fg-4);margin-top:4px;">Token kedaluwarsa</div>
                        @endif
                    </div>
                </div>

                {{-- Detail --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <div style="font-size:10px;color:var(--fg-4);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Tanggal Diminta</div>
                        <div style="font-size:13px;font-weight:600;color:var(--fg-1);">
                            {{ \Carbon\Carbon::parse($req->requested_date)->isoFormat('D MMMM YYYY') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--fg-4);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Diajukan</div>
                        <div style="font-size:13px;color:var(--fg-2);">{{ $req->created_at->isoFormat('D MMM YYYY, HH:mm') }}</div>
                    </div>
                </div>

                {{-- Alasan --}}
                <div style="background:var(--bg-2);border-radius:8px;padding:10px 12px;">
                    <div style="font-size:10px;color:var(--fg-4);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Alasan</div>
                    <div style="font-size:13px;color:var(--fg-2);line-height:1.5;">{{ $req->reason }}</div>
                </div>

                {{-- Info approved --}}
                @if($req->status === 'approved' && !$isExpired)
                    <div style="background:#E8F7EE;border:1px solid #16A571;border-radius:8px;padding:10px 12px;font-size:12px;color:#0F7A50;">
                        ✅ Disetujui oleh <strong>{{ $req->reviewer?->name ?? '-' }}</strong>
                        · {{ $req->reviewed_at?->isoFormat('D MMM, HH:mm') }}
                        · Token berlaku hingga <strong>{{ $req->token_expires_at?->isoFormat('D MMM HH:mm') }}</strong>
                    </div>
                @elseif($req->status === 'rejected')
                    <div style="background:#FFF1F2;border:1px solid #F87171;border-radius:8px;padding:10px 12px;font-size:12px;color:#B91C1C;">
                        ❌ Ditolak oleh <strong>{{ $req->reviewer?->name ?? '-' }}</strong>
                        @if($req->rejection_note)
                            · Alasan: {{ $req->rejection_note }}
                        @endif
                    </div>
                @endif

                {{-- Action buttons (hanya untuk pending) --}}
                @if($req->status === 'pending')
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        {{-- Setujui --}}
                        <form method="POST" action="{{ route('backdate-requests.approve', $req) }}"
                              onsubmit="return confirm('Setujui permintaan backdating {{ $req->user->name }} untuk tanggal {{ \Carbon\Carbon::parse($req->requested_date)->isoFormat("D MMMM") }}?');">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-primary btn-block" style="background:#16A571;">
                                ✅ Setujui — Izinkan Isi Laporan
                            </button>
                        </form>

                        {{-- Tolak --}}
                        <details style="border:1px solid #F87171;border-radius:8px;padding:10px 12px;">
                            <summary style="font-size:13px;font-weight:600;color:#B91C1C;cursor:pointer;">❌ Tolak Permintaan</summary>
                            <form method="POST" action="{{ route('backdate-requests.reject', $req) }}" style="margin-top:10px;">
                                @csrf @method('PATCH')
                                <textarea name="rejection_note" rows="2" required minlength="5"
                                    placeholder="Tuliskan alasan penolakan..."
                                    style="width:100%;font-size:13px;padding:8px 10px;border:1px solid var(--bg-3);border-radius:8px;resize:vertical;box-sizing:border-box;"></textarea>
                                <button type="submit" class="btn btn-sm" style="margin-top:8px;background:#EF4444;color:#fff;width:100%;">Tolak Permintaan</button>
                            </form>
                        </details>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    @endif
</div>
</x-app-layout>
