<x-app-layout>
<div class="page">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Manajemen Pengguna</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">Kelola akun seluruh karyawan di sistem</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Karyawan
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px;">
        <div class="select-wrap" style="min-width:170px;">
            <select name="department" class="m-select m-input" style="font-size:13px;">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $key => $label)
                    <option value="{{ $key }}" {{ request('department') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="select-wrap" style="min-width:140px;">
            <select name="role" class="m-select m-input" style="font-size:13px;">
                <option value="">Semua Role</option>
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Filter
        </button>
        <a href="{{ route('admin.users.index') }}" style="font-size:13px;color:var(--fg-3);text-decoration:none;font-weight:600;">Reset</a>
    </form>

    {{-- Tabel --}}
    <style>
        .au-table-wrap { background:#fff; border:1px solid var(--bg-3); border-radius:12px; overflow:hidden; margin-top:8px; }
        .au-table { width:100%; border-collapse:collapse; font-size:13px; }
        .au-table th { padding:12px 16px; background:var(--bg-2); color:var(--fg-3); text-transform:uppercase; letter-spacing:.05em; font-size:11px; text-align:left; border-bottom:1px solid var(--bg-3); white-space:nowrap; }
        .au-table td { padding:12px 16px; border-bottom:1px solid var(--bg-3); color:var(--fg-2); vertical-align:middle; }
        .au-table tr:last-child td { border-bottom:none; }
        @media (max-width: 767px) { .au-table-wrap { overflow-x:auto; } }
    </style>

    <div class="au-table-wrap">
        <table class="au-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Departemen</th>
                    <th>Divisi</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr style="{{ $user->is_active ? '' : 'opacity:.55;' }}">
                        <td style="font-weight:700;color:var(--fg-1);">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->department_label ?? '-' }}</td>
                        <td>{{ $user->division ?? '-' }}</td>
                        <td>
                            @php
                                $roleChip = match($user->role) {
                                    'c_level'    => 'info',
                                    'leader'     => 'info',
                                    'super_admin'=> 'warning',
                                    default      => 'neutral',
                                };
                            @endphp
                            <span class="chip chip-{{ $roleChip }}" style="font-size:11px;">
                                {{ \App\Models\User::ROLES[$user->role] ?? $user->role }}
                            </span>
                        </td>
                        <td>
                            <span class="chip chip-{{ $user->is_active ? 'success' : 'danger' }}" style="font-size:11px;">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;">
                                <a href="{{ route('admin.users.edit', $user) }}" style="font-size:12px;font-weight:600;color:var(--maxy-navy);text-decoration:none;">Edit</a>
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}"
                                      data-confirm="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?" data-confirm-variant="{{ $user->is_active ? 'danger' : 'primary' }}" data-confirm-ok="{{ $user->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}"
                                      style="margin:0;">
                                    @csrf @method('PATCH')
                                    <button type="submit" style="background:none;border:none;cursor:pointer;padding:0;font-size:12px;font-weight:600;color:{{ $user->is_active ? 'var(--danger)' : 'var(--success)' }};">
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:32px 16px;text-align:center;color:var(--fg-3);">Tidak ada data pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="font-size:12px;color:var(--fg-3);margin-top:4px;">Total: {{ $users->count() }} pengguna</p>
</div>
</x-app-layout>
