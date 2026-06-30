<x-app-layout>
<div class="page">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('admin.users.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div>
            <h1 style="font-size:20px;font-weight:700;color:var(--fg-1);margin:0;">Edit Data: {{ $user->name }}</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">Email tidak dapat diubah jika sudah pernah login via Google.</p>
        </div>
    </div>

    <div class="m-card p-4" style="max-width:640px;">
        @if($errors->any())
            <div style="background:#FEE2E2;border-left:4px solid #EF4444;color:#991B1B;padding:12px;border-radius:4px;margin-bottom:16px;">
                <strong>Gagal menyimpan perubahan!</strong>
                <ul style="margin:8px 0 0 20px;padding:0;font-size:13px;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf @method('PUT')

            <div class="field">
                <label for="name">Nama Lengkap <span style="color:var(--danger);">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                       class="m-input {{ $errors->has('name') ? 'err' : '' }}">
                @error('name')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="email">Email Google <span style="color:var(--danger);">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="m-input {{ $errors->has('email') ? 'err' : '' }}">
                @error('email')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="role">Role <span style="color:var(--danger);">*</span></label>
                <div class="select-wrap">
                    <select id="role" name="role" required class="m-select {{ $errors->has('role') ? 'err' : '' }}">
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @error('role')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="department">Departemen</label>
                <div class="select-wrap">
                    <select id="department" name="department" class="m-select">
                        <option value="">-- Tidak Ada --</option>
                        @foreach ($departments as $key => $label)
                            <option value="{{ $key }}" {{ old('department', $user->department) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="division">Divisi / Jabatan</label>
                <input type="text" id="division" name="division" value="{{ old('division', $user->division) }}" class="m-input">
            </div>

            <div class="field">
                <label for="password">Reset Password <span style="color:var(--fg-3);font-weight:400;">(opsional)</span></label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       placeholder="Min. 8 karakter — kosongkan untuk membiarkan password lama"
                       class="m-input {{ $errors->has('password') ? 'err' : '' }}">
                <small style="color:var(--fg-3);font-size:11px;">
                    {{ empty($user->password) ? 'Akun ini belum punya password (login via Google). Isi untuk membuatkan password.' : 'Isi untuk mengganti password karyawan. Dikosongkan = password tidak berubah.' }}
                </small>
                @error('password')<span class="err">{{ $message }}</span>@enderror
            </div>

            <label for="is_management" style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--fg-2);cursor:pointer;">
                <input type="checkbox" name="is_management" id="is_management" value="1" {{ old('is_management', $user->is_management) ? 'checked' : '' }}
                       style="width:16px;height:16px;">
                Tandai sebagai Management (bisa export laporan)
            </label>

            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.users.index') }}" class="btn btn-block"
                   style="flex:0 0 35%;background:var(--bg-2);color:var(--fg-2);text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary btn-block" style="flex:1;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
