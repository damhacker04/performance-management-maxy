@extends('layouts.app')

@section('content')
<div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Assign Target ke Staff</h1>
        <p class="text-sm text-gray-500 mt-1">Pilih departemen untuk melihat daftar staff dan target yang tersedia.</p>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Filter Departemen --}}
    <form method="GET" class="flex gap-3 mb-8">
        <select name="department" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">-- Pilih Departemen --</option>
            @foreach ($departments as $key => $label)
                <option value="{{ $key }}" {{ $selectedDept === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            Tampilkan
        </button>
    </form>

    @if ($selectedDept)

        {{-- Daftar Staff di Departemen --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">
                Staff di Departemen: <span class="text-indigo-600">{{ \App\Models\User::DEPARTMENTS[$selectedDept] }}</span>
            </h2>
            @if ($staffList->isEmpty())
                <p class="text-sm text-gray-500">Belum ada staff di departemen ini.</p>
            @else
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                    <table class="min-w-full text-sm divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Nama</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Divisi</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($staffList as $staff)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $staff->name }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $staff->division ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium
                                            {{ $staff->role === 'leader' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
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
            <div class="p-6 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl text-sm">
                Belum ada Monthly Target di departemen ini. Minta Leader untuk membuat target terlebih dahulu.
            </div>
        @else
            @foreach ($monthlyTargets as $monthly)
                <div class="mb-6 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Monthly Target Header --}}
                    <div class="flex items-center justify-between px-5 py-4 bg-indigo-50 border-b border-indigo-100">
                        <div>
                            <p class="font-semibold text-indigo-800">{{ $monthly->title }}</p>
                            <p class="text-xs text-indigo-500 mt-0.5">
                                Bulan {{ \Carbon\Carbon::create()->month($monthly->month)->translatedFormat('F') }} {{ $monthly->year }}
                            </p>
                        </div>
                        {{-- Tombol hapus Monthly Target --}}
                        <form method="POST" action="{{ route('admin.monthly-targets.destroy', $monthly) }}"
                              onsubmit="return confirm('Hapus Monthly Target \"{{ $monthly->title }}\" beserta semua data di dalamnya? Tindakan ini tidak dapat dibatalkan.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">
                                Hapus Target Ini
                            </button>
                        </form>
                    </div>

                    {{-- Weekly Targets --}}
                    @if ($monthly->weeklyTargets->isEmpty())
                        <p class="px-5 py-4 text-sm text-gray-400">Belum ada weekly target di bawah monthly target ini.</p>
                    @else
                        <table class="min-w-full text-sm divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Weekly Target</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Minggu ke</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Saat Ini Diassign ke</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Assign ke Staff</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($monthly->weeklyTargets as $weekly)
                                    @php
                                        $assignedUser = $staffList->firstWhere('id', $weekly->assigned_to);
                                    @endphp
                                    <tr>
                                        <td class="px-5 py-3 font-medium text-gray-800">{{ $weekly->title }}</td>
                                        <td class="px-5 py-3 text-gray-600">Minggu {{ $weekly->week_number }}</td>
                                        <td class="px-5 py-3">
                                            @if ($assignedUser)
                                                <span class="inline-block px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
                                                    🎯 {{ $assignedUser->name }}
                                                </span>
                                            @else
                                                <span class="inline-block px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">
                                                    🏢 Umum (Semua)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <form method="POST" action="{{ route('admin.target-assignment.assign-weekly') }}"
                                                  class="flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="weekly_target_id" value="{{ $weekly->id }}">
                                                <select name="user_id" required
                                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                                    <option value="">-- Pilih Staff --</option>
                                                    @foreach ($staffList as $staff)
                                                        <option value="{{ $staff->id }}"
                                                            {{ $weekly->assigned_to == $staff->id ? 'selected' : '' }}>
                                                            {{ $staff->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700 transition">
                                                    Assign
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-2">
                                                {{-- Lepas Assignment --}}
                                                @if ($weekly->assigned_to)
                                                    <form method="POST" action="{{ route('admin.target-assignment.unassign-weekly') }}"
                                                          onsubmit="return confirm('Lepas assignment weekly target ini?')">
                                                        @csrf
                                                        <input type="hidden" name="weekly_target_id" value="{{ $weekly->id }}">
                                                        <button type="submit" class="text-xs text-orange-500 hover:text-orange-700">Lepas</button>
                                                    </form>
                                                @endif
                                                {{-- Hapus Weekly Target --}}
                                                <form method="POST" action="{{ route('admin.weekly-targets.destroy', $weekly) }}"
                                                      onsubmit="return confirm('Hapus Weekly Target \"{{ $weekly->title }}\"? Semua laporan harian di dalamnya juga akan terhapus.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        @endif

    @else
        <div class="p-8 bg-gray-50 border border-gray-200 rounded-xl text-center text-gray-400 text-sm">
            Pilih departemen di atas untuk memulai.
        </div>
    @endif

</div>
@endsection
