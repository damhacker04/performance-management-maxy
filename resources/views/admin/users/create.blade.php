@extends('layouts.app')

@section('content')
<div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 hover:underline">← Kembali ke Daftar Pengguna</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tambah Karyawan Baru</h1>
        <p class="text-sm text-gray-500 mt-1">Karyawan akan login menggunakan akun Google sesuai email yang didaftarkan.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            {{-- Nama --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-400 @enderror">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Google <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    placeholder="nama@gmail.com"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-400 @enderror">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Role --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Pilih Role --</option>
                    @foreach ($roles as $key => $label)
                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Departemen --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                <select name="department"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Pilih Departemen --</option>
                    @foreach ($departments as $key => $label)
                        <option value="{{ $key }}" {{ old('department') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Divisi --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divisi / Jabatan</label>
                <input type="text" name="division" value="{{ old('division') }}"
                    placeholder="Contoh: Human Capital, Finance, dll"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Is Management --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_management" id="is_management" value="1"
                    {{ old('is_management') ? 'checked' : '' }}
                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                <label for="is_management" class="text-sm text-gray-700">Tandai sebagai Management (bisa export laporan)</label>
            </div>

            {{-- Submit --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Simpan Karyawan
                </button>
                <a href="{{ route('admin.users.index') }}"
                    class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
