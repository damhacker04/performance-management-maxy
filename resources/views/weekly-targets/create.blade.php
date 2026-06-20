<x-app-layout>
@php
    $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;
    $selectedMonthly = old('monthly_target_id', $preSelected);
@endphp

<div class="page">
    <!-- Back -->
    <div style="display:flex;align-items:center;gap:8px;">
@php
    $backParam = request()->query('back') ? urldecode(request()->query('back')) : null;
    $backHref  = $backParam ?? (url()->previous() !== url()->current() ? url()->previous() : route('monthly-targets.index'));
@endphp
        <a href="{{ $backHref }}"
           class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Target Mingguan Baru</h1>
    </div>

    <div class="m-card">
        <form method="POST" action="{{ route('weekly-targets.store') }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            {{-- Teruskan ?back= agar setelah store bisa redirect ke period context --}}
            @if($backParam ?? null)
                <input type="hidden" name="back" value="{{ $backParam }}">
            @endif

            <!-- Pilih Monthly Target (wajib) -->
            <div class="field">
                <label for="monthly_target_id">
                    Terkait Target Bulanan <span style="color:var(--danger);">*</span>
                </label>

                @php
                    // Banner konteks berdasarkan dari mana user buka form ini
                    $contextBanner = match($context) {
                        'leader' => ['🎯', 'Target Saya', 'Mingguan ini akan masuk ke Target Anda dari C-Level', '#eff6ff', '#1e40af'],
                        'team'   => ['👥', 'Target Tim', 'Mingguan ini akan masuk ke Target yang Anda buat untuk tim', '#f0fdf4', '#166534'],
                        default  => null,
                    };
                @endphp

                @if($contextBanner)
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;
                                border-radius:8px;margin-bottom:6px;
                                background:{{ $contextBanner[3] }};color:{{ $contextBanner[4] }};
                                font-size:12px;font-weight:600;">
                        <span style="font-size:16px;">{{ $contextBanner[0] }}</span>
                        <div>
                            <div>{{ $contextBanner[1] }}</div>
                            <div style="font-weight:400;opacity:.8;margin-top:1px;">{{ $contextBanner[2] }}</div>
                        </div>
                    </div>
                @endif

                @if($preSelected !== null && ($cLevelTargets->contains('id', $preSelected) || $teamTargets->contains('id', $preSelected)))
                    {{-- ✅ LOCKED: Target sudah ditentukan dari konteks sebelumnya --}}
                    @php
                        $lockedTarget = $cLevelTargets->firstWhere('id', $preSelected)
                            ?? $teamTargets->firstWhere('id', $preSelected);
                    @endphp
                    <input type="hidden" name="monthly_target_id" value="{{ $preSelected }}">
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;
                                border-radius:10px;background:var(--bg-2);border:1.5px solid var(--bd-1);">
                        <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">
                                Target Bulanan
                            </div>
                            <div style="font-size:13px;font-weight:700;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $lockedTarget->title }}
                            </div>
                            <div style="font-size:11px;color:var(--fg-4);margin-top:2px;">
                                {{ $months[$lockedTarget->month] }} {{ $lockedTarget->year }}
                                &nbsp;·&nbsp; {{ ucfirst(str_replace('_',' ', $lockedTarget->department)) }}
                                @if($lockedTarget->description)
                                    &nbsp;·&nbsp; <span style="font-style:italic;">{{ Str::limit($lockedTarget->description, 60) }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="chip chip-neutral" style="font-size:10px;flex-shrink:0;">Terkunci</span>
                    </div>
                @else
                    {{-- Normal dropdown jika belum ada pre-selection --}}
                    <div class="select-wrap">
                        <select id="monthly_target_id" name="monthly_target_id"
                                class="m-select {{ $errors->has('monthly_target_id') ? 'err' : '' }}"
                                required>
                            <option value="" disabled {{ $preSelected === null ? 'selected' : '' }}>
                                Pilih target bulanan...
                            </option>

                            {{-- ── Grup 1: Target dari C-Level (untuk leader sendiri) ── --}}
                            @if($cLevelTargets->isNotEmpty() && $context !== 'team')
                                <optgroup label="🎯 Target Saya — dari C-Level">
                                    @foreach($cLevelTargets as $mt)
                                        <option value="{{ $mt->id }}"
                                                {{ (int)$preSelected === $mt->id ? 'selected' : '' }}>
                                            {{ $mt->title }} ({{ $months[$mt->month] }} {{ $mt->year }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            {{-- ── Grup 2: Target untuk Tim (buatan leader) ── --}}
                            @if($teamTargets->isNotEmpty() && $context !== 'leader')
                                <optgroup label="👥 Target Tim — untuk Staff">
                                    @foreach($teamTargets as $mt)
                                        <option value="{{ $mt->id }}"
                                                {{ (int)$preSelected === $mt->id ? 'selected' : '' }}>
                                            {{ $mt->title }} ({{ $months[$mt->month] }} {{ $mt->year }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            {{-- Fallback jika konteks tapi grup kosong --}}
                            @if($context === 'leader' && $cLevelTargets->isEmpty())
                                <option value="" disabled>— Belum ada target dari C-Level bulan ini —</option>
                            @elseif($context === 'team' && $teamTargets->isEmpty())
                                <option value="" disabled>— Belum ada target tim bulan ini —</option>
                            @endif
                        </select>
                    </div>
                @endif
                @error('monthly_target_id')<span class="err">{{ $message }}</span>@enderror
            </div>


            <!-- Minggu -->
            <div class="field">
                <label for="week_number">Nomor Minggu <span style="color:var(--danger);">*</span></label>
                <div class="select-wrap">
                    <select id="week_number" name="week_number"
                            class="m-select {{ $errors->has('week_number') ? 'err' : '' }}" required>
                        <option value="">Pilih minggu...</option>
                        @foreach($weekRanges as $num => [$start, $end])
                            <option value="{{ $num }}"
                                    {{ old('week_number') == $num ? 'selected' : '' }}>
                                Minggu {{ $num }} (tanggal {{ $start }}–{{ $end }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('week_number')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Ditugaskan Kepada (Opsional) -->
            <div class="field">
                <label for="assigned_to">Ditugaskan Kepada <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>

                @if($preSelectedUserModel)
                    {{-- ✅ LOCKED: Staf sudah ditentukan dari konteks card yang diklik --}}
                    <input type="hidden" name="assigned_to" value="{{ $preSelectedUserModel->id }}">
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;
                                border-radius:10px;background:var(--bg-2);border:1.5px solid var(--bd-1);">
                        <svg class="lucide sm" style="color:var(--fg-4);flex-shrink:0;" viewBox="0 0 24 24">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">
                                Ditugaskan Kepada
                            </div>
                            <div style="font-size:13px;font-weight:700;color:var(--fg-1);">
                                {{ $preSelectedUserModel->name }}
                            </div>
                            <div style="font-size:11px;color:var(--fg-4);margin-top:2px;">
                                {{ ucfirst($preSelectedUserModel->role) }}
                                @if($preSelectedUserModel->department)
                                    &nbsp;·&nbsp; {{ ucfirst(str_replace('_',' ', $preSelectedUserModel->department)) }}
                                @endif
                            </div>
                        </div>
                        <span class="chip chip-neutral" style="font-size:10px;flex-shrink:0;">Terkunci</span>
                    </div>
                @else
                    {{-- Normal dropdown jika tidak ada pre-selected user --}}
                    <div class="select-wrap">
                        <select id="assigned_to" name="assigned_to"
                                class="m-select {{ $errors->has('assigned_to') ? 'err' : '' }}">
                            <option value="">-- Umum (Semua staf di departemen dapat mengambil target ini) --</option>
                            @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}" {{ old('assigned_to', $preSelectedUser ?? null) == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }} ({{ $staff->role == 'leader' ? 'Leader' : 'Staff' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @error('assigned_to')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Judul -->
            <div class="field">
                <label for="title">Judul Target <span style="color:var(--danger);">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                       class="m-input {{ $errors->has('title') ? 'err' : '' }}"
                       placeholder="cth. Follow-up 50 leads kualifikasi" required />
                @error('title')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Deskripsi -->
            <div class="field">
                <label for="description">Deskripsi <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea id="description" name="description"
                          class="m-textarea"
                          placeholder="Jelaskan detail target & cara pencapaiannya...">{{ old('description') }}</textarea>
                @error('description')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Tipe Target -->
            <div class="field">
                <label>Tipe Target <span style="color:var(--danger);">*</span></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <label class="radio-card" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--bd-1);border-radius:8px;cursor:pointer;">
                        <input type="radio" name="target_type" value="quantitative"
                               {{ old('target_type','quantitative') === 'quantitative' ? 'checked' : '' }}
                               onchange="syncTargetType()" />
                        <span style="font-size:13px;font-weight:600;">Kuantitatif</span>
                    </label>
                    <label class="radio-card" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--bd-1);border-radius:8px;cursor:pointer;">
                        <input type="radio" name="target_type" value="qualitative"
                               {{ old('target_type') === 'qualitative' ? 'checked' : '' }}
                               onchange="syncTargetType()" />
                        <span style="font-size:13px;font-weight:600;">Kualitatif</span>
                    </label>
                </div>
                @error('target_type')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Value & Unit -->
            <div id="quantitative_fields" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label for="target_value">Nilai Target <span style="color:var(--danger);">*</span></label>
                    <input id="target_value" type="number" name="target_value" step="0.01"
                           value="{{ old('target_value') }}"
                           min="0"
                           class="m-input {{ $errors->has('target_value') ? 'err' : '' }}"
                           placeholder="50" />
                    @error('target_value')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="target_unit">Satuan <span style="color:var(--danger);">*</span></label>
                    <input id="target_unit" type="text" name="target_unit"
                           value="{{ old('target_unit') }}"
                           maxlength="50"
                           class="m-input {{ $errors->has('target_unit') ? 'err' : '' }}"
                           placeholder="leads, %, klien..." />
                    @error('target_unit')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>

            <script>
                function syncTargetType() {
                    const isQuant = document.querySelector('input[name="target_type"]:checked')?.value === 'quantitative';
                    const quantFields = document.getElementById('quantitative_fields');
                    const valEl = document.getElementById('target_value');
                    const unitEl = document.getElementById('target_unit');
                    quantFields.style.display = isQuant ? 'grid' : 'none';
                    valEl.required = isQuant;
                    unitEl.required = isQuant;
                }
                syncTargetType();
            </script>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                Simpan Target Mingguan
            </button>
        </form>
    </div>
</div>
</x-app-layout>
