<x-app-layout>
<div class="page">

    {{-- Header --}}
    <div>
        <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Assign Target ke Staff</h1>
        <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">Pilih departemen untuk melihat daftar staff dan target yang tersedia.</p>
    </div>

    {{-- Filter Departemen --}}
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px;">
        <div class="select-wrap" style="min-width:200px;">
            <select name="department" class="m-select m-input" style="font-size:13px;">
                <option value="">-- Pilih Departemen --</option>
                @foreach ($departments as $key => $label)
                    <option value="{{ $key }}" {{ $selectedDept === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Tampilkan
        </button>
    </form>

    <style>
        .ta-table { width:100%; border-collapse:collapse; font-size:13px; }
        .ta-table th { padding:11px 16px; background:var(--bg-2); color:var(--fg-3); text-transform:uppercase; letter-spacing:.05em; font-size:11px; text-align:left; border-bottom:1px solid var(--bg-3); white-space:nowrap; }
        .ta-table td { padding:11px 16px; border-bottom:1px solid var(--bg-3); color:var(--fg-2); vertical-align:middle; }
        .ta-table tr:last-child td { border-bottom:none; }
        .ta-wrap { background:#fff; border:1px solid var(--bg-3); border-radius:12px; overflow-x:auto; }
    </style>

    @if ($selectedDept)

        {{-- Daftar Staff di Departemen --}}
        <div style="margin-top:8px;">
            <h2 style="font-size:15px;font-weight:700;color:var(--fg-1);margin:0 0 10px;">
                Staff di Departemen: <span style="color:var(--maxy-navy);">{{ \App\Models\User::DEPARTMENTS[$selectedDept] }}</span>
            </h2>
            @if ($staffList->isEmpty())
                <div class="m-card"><div class="empty-state"><p style="font-size:13px;color:var(--fg-3);">Belum ada staff di departemen ini.</p></div></div>
            @else
                <div class="ta-wrap">
                    <table class="ta-table">
                        <thead>
                            <tr><th>Nama</th><th>Divisi</th><th>Role</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($staffList as $staff)
                                <tr>
                                    <td style="font-weight:700;color:var(--fg-1);">{{ $staff->name }}</td>
                                    <td>{{ $staff->division ?? '-' }}</td>
                                    <td>
                                        <span class="chip chip-{{ $staff->role === 'leader' ? 'info' : 'neutral' }}" style="font-size:11px;">
                                            {{ \App\Models\User::ROLES[$staff->role] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Monthly Targets & Weekly Targets --}}
        @if ($monthlyTargets->isEmpty())
            <div style="margin-top:16px;background:#FFF8E8;border:1px solid #FBB041;border-radius:12px;padding:14px 16px;font-size:13px;color:#8B5A00;">
                Belum ada Monthly Target di departemen ini. Minta Leader untuk membuat target terlebih dahulu.
            </div>
        @else
            @foreach ($monthlyTargets as $monthly)
                <div style="margin-top:16px;background:#fff;border:1px solid var(--bg-3);border-radius:12px;overflow:hidden;">
                    {{-- Monthly Target Header --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;background:var(--bg-2);border-bottom:1px solid var(--bg-3);">
                        <div>
                            <p style="font-weight:700;color:var(--maxy-navy);margin:0;">{{ $monthly->title }}</p>
                            <p style="font-size:11px;color:var(--fg-3);margin:2px 0 0;">
                                Bulan {{ \Carbon\Carbon::create()->month($monthly->month)->translatedFormat('F') }} {{ $monthly->year }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.monthly-targets.destroy', $monthly) }}"
                              data-confirm="Hapus Monthly Target '{{ $monthly->title }}' beserta semua data di dalamnya? Tindakan ini tidak dapat dibatalkan." data-confirm-variant="danger" data-confirm-ok="Ya, Hapus"
                              style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:12px;font-weight:600;color:var(--danger);">Hapus Target Ini</button>
                        </form>
                    </div>

                    {{-- Weekly Targets --}}
                    @if ($monthly->weeklyTargets->isEmpty())
                        <p style="padding:14px 16px;font-size:13px;color:var(--fg-3);margin:0;">Belum ada weekly target di bawah monthly target ini.</p>
                    @else
                        <div style="overflow-x:auto;">
                        <table class="ta-table">
                            <thead>
                                <tr>
                                    <th>Weekly Target</th>
                                    <th>Minggu ke</th>
                                    <th>Saat Ini Diassign ke</th>
                                    <th>Assign ke Staff</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthly->weeklyTargets as $weekly)
                                    @php $assignedUser = $staffList->firstWhere('id', $weekly->assigned_to); @endphp
                                    <tr>
                                        <td style="font-weight:600;color:var(--fg-1);">{{ $weekly->title }}</td>
                                        <td>Minggu {{ $weekly->week_number }}</td>
                                        <td>
                                            @if ($assignedUser)
                                                <span class="chip chip-warning" style="font-size:11px;">🎯 {{ $assignedUser->name }}</span>
                                            @else
                                                <span class="chip chip-neutral" style="font-size:11px;">🏢 Umum (Semua)</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.target-assignment.assign-weekly') }}"
                                                  style="display:flex;align-items:center;gap:6px;margin:0;">
                                                @csrf
                                                <input type="hidden" name="weekly_target_id" value="{{ $weekly->id }}">
                                                <div class="select-wrap" style="min-width:150px;">
                                                    <select name="user_id" required class="m-select m-input" style="font-size:12px;padding:6px 28px 6px 10px;height:auto;">
                                                        <option value="">-- Pilih Staff --</option>
                                                        @foreach ($staffList as $staff)
                                                            <option value="{{ $staff->id }}" {{ $weekly->assigned_to == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm" style="font-size:12px;padding:6px 12px;">Assign</button>
                                            </form>
                                        </td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                @if ($weekly->assigned_to)
                                                    <form method="POST" action="{{ route('admin.target-assignment.unassign-weekly') }}"
                                                          data-confirm="Lepas assignment weekly target ini?" data-confirm-variant="danger" data-confirm-ok="Ya, Lepas" style="margin:0;">
                                                        @csrf
                                                        <input type="hidden" name="weekly_target_id" value="{{ $weekly->id }}">
                                                        <button type="submit" style="background:none;border:none;cursor:pointer;font-size:12px;font-weight:600;color:#B45309;">Lepas</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('admin.weekly-targets.destroy', $weekly) }}"
                                                      data-confirm="Hapus Weekly Target '{{ $weekly->title }}'? Semua laporan harian di dalamnya juga akan terhapus." data-confirm-variant="danger" data-confirm-ok="Ya, Hapus" style="margin:0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:12px;font-weight:600;color:var(--danger);">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif

    @else
        <div class="m-card" style="margin-top:8px;">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-3);" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p style="font-size:13px;color:var(--fg-3);">Pilih departemen di atas untuk memulai.</p>
            </div>
        </div>
    @endif

</div>
</x-app-layout>
