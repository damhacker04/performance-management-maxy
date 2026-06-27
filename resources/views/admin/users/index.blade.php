@extends('layouts.app')

@section('content')
<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola akun seluruh karyawan di sistem</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            + Tambah Karyawan
        </a>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="flex gap-3 mb-6">
        <select name="department" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Semua Departemen</option>
            @foreach ($departments as $key => $label)
                <option value="{{ $key }}" {{ request('department') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Semua Role</option>
            @foreach ($roles as $key => $label)
                <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
    </form>

    {{-- Tabel --}}
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Nama</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Email</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Departemen</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Divisi</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Role</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Status</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr class="{{ $user->is_active ? '' : 'bg-gray-50 opacity-60' }}">
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $user->department_label ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $user->division ?? '-' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                {{ $user->role === 'c_level' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $user->role === 'leader'  ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $user->role === 'staff'   ? 'bg-gray-100 text-gray-700' : '' }}">
                                {{ \App\Models\User::ROLES[$user->role] ?? $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 flex items-center gap-3">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>

                            <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}"
                                  data-confirm="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?" data-confirm-variant="{{ $user->is_active ? 'danger' : 'primary' }}" data-confirm-ok="{{ $user->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="{{ $user->is_active ? 'text-red-500 hover:text-red-700' : 'text-green-500 hover:text-green-700' }} font-medium">
                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">Tidak ada data pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-4">Total: {{ $users->count() }} pengguna</p>
</div>
@endsection
