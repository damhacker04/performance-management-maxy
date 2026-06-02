<x-app-layout>

<div class="page">
    <!-- Back -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('monthly-targets.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Target Baru</h1>
    </div>

    <div class="m-card">
        <form method="POST" action="{{ route('monthly-targets.store') }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf

            <!-- Departemen -->
            @if(in_array(auth()->user()->role, ['c_level', 'super_admin']))
                {{-- C-Level / Super Admin: pilih departemen tujuan --}}
                <div class="field">
                    <label for="department">Departemen <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="department" name="department" class="m-select" required>
                            <option value="">Pilih departemen...</option>
                            @foreach(\App\Models\User::DEPARTMENTS as $key => $label)
                                <option value="{{ $key }}" {{ old('department') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('department')<span class="err">{{ $message }}</span>@enderror
                </div>
            @else
                {{-- Leader: read-only, hanya dept sendiri --}}
                <div class="field">
                    <label>Departemen</label>
                    <div class="m-input" style="display:flex;align-items:center;background:var(--neutral-50);color:var(--fg-2);cursor:default;">
                        <span class="chip chip-dept-{{ str_replace('_','-', auth()->user()->department ?? 'ceo') }}" style="pointer-events:none;">
                            {{ ucfirst(str_replace('_', ' ', auth()->user()->department ?? 'CEO Office')) }}
                        </span>
                    </div>
                </div>
            @endif

            <!-- Judul -->
            <div class="field">
                <label for="title">Judul Target <span style="color:var(--danger);">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                       class="m-input {{ $errors->has('title') ? 'err' : '' }}"
                       placeholder="cth. Tingkatkan konversi leads 20%" required />
                @error('title')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Deskripsi -->
            <div class="field">
                <label for="description">Deskripsi <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea id="description" name="description"
                          class="m-textarea"
                          placeholder="Jelaskan detail target dan cara pencapaiannya...">{{ old('description') }}</textarea>
                @error('description')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Bulan & Tahun -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label for="month">Bulan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="month" name="month" class="m-select" required>
                            <option value="">Pilih...</option>
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                                <option value="{{ $i+1 }}" {{ old('month', now()->month) == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('month')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="year">Tahun <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="year" name="year" class="m-select" required>
                            @foreach(range(now()->year, now()->year + 1) as $y)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('year')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                Simpan Target
            </button>
        </form>
    </div>
</div>
</x-app-layout>
