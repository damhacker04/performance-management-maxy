<x-app-layout>
@php
    $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;
    $selectedMonthly = old('monthly_target_id', $weeklyTarget->monthly_target_id);
    $contextBanner = match($context ?? null) {
        'leader' => ['🎯', 'Target Saya', 'Mingguan ini terkait Target Anda dari C-Level', '#eff6ff', '#1e40af'],
        'team'   => ['👥', 'Target Tim', 'Mingguan ini terkait Target yang Anda buat untuk tim', '#f0fdf4', '#166534'],
        default  => null,
    };
@endphp

<div class="page">
    <!-- Back -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('monthly-targets.show', $weeklyTarget->monthly_target_id) }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Edit Target Mingguan</h1>
    </div>

    <div class="m-card">
        <form method="POST" action="{{ route('weekly-targets.update', $weeklyTarget) }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PATCH')

            <!-- Monthly Target (required) -->
            <div class="field">
                <label for="monthly_target_id">
                    Terkait Target Bulanan <span style="color:var(--danger);">*</span>
                </label>

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

                <div class="select-wrap">
                    <select id="monthly_target_id" name="monthly_target_id"
                            class="m-select {{ $errors->has('monthly_target_id') ? 'err' : '' }}" required>
                        <option value="" disabled {{ empty($selectedMonthly) ? 'selected' : '' }}>
                            Pilih target bulanan...
                        </option>

                        {{-- Grup C-Level (hanya muncul jika konteks = leader) --}}
                        @if($cLevelTargets->isNotEmpty())
                            <optgroup label="🎯 Target Saya — dari C-Level">
                                @foreach($cLevelTargets as $mt)
                                    <option value="{{ $mt->id }}"
                                            {{ (int)$selectedMonthly === $mt->id ? 'selected' : '' }}>
                                        {{ $mt->title }} ({{ $months[$mt->month] }} {{ $mt->year }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        {{-- Grup Tim (hanya muncul jika konteks = team) --}}
                        @if($teamTargets->isNotEmpty())
                            <optgroup label="👥 Target Tim — untuk Staff">
                                @foreach($teamTargets as $mt)
                                    <option value="{{ $mt->id }}"
                                            {{ (int)$selectedMonthly === $mt->id ? 'selected' : '' }}>
                                        {{ $mt->title }} ({{ $months[$mt->month] }} {{ $mt->year }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>
                @error('monthly_target_id')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Minggu -->
            <div class="field">
                <label for="week_number">Nomor Minggu <span style="color:var(--danger);">*</span></label>
                <div class="select-wrap">
                    <select id="week_number" name="week_number"
                            class="m-select {{ $errors->has('week_number') ? 'err' : '' }}" required>
                        @foreach($weekRanges as $num => [$start, $end])
                            <option value="{{ $num }}"
                                    {{ old('week_number', $weeklyTarget->week_number) == $num ? 'selected' : '' }}>
                                Minggu {{ $num }} (tanggal {{ $start }}–{{ $end }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('week_number')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Judul -->
            <div class="field">
                <label for="title">Judul Target <span style="color:var(--danger);">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title', $weeklyTarget->title) }}"
                       class="m-input {{ $errors->has('title') ? 'err' : '' }}" required />
                @error('title')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Deskripsi -->
            <div class="field">
                <label for="description">Deskripsi <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea id="description" name="description"
                          class="m-textarea">{{ old('description', $weeklyTarget->description) }}</textarea>
                @error('description')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Tipe Target -->
            <div class="field">
                <label>Tipe Target <span style="color:var(--danger);">*</span></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <label class="radio-card" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--bd-1);border-radius:8px;cursor:pointer;">
                        <input type="radio" name="target_type" value="quantitative"
                               {{ old('target_type', $weeklyTarget->target_type) === 'quantitative' ? 'checked' : '' }}
                               onchange="syncTargetType()" />
                        <span style="font-size:13px;font-weight:600;">Kuantitatif</span>
                    </label>
                    <label class="radio-card" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--bd-1);border-radius:8px;cursor:pointer;">
                        <input type="radio" name="target_type" value="qualitative"
                               {{ old('target_type', $weeklyTarget->target_type) === 'qualitative' ? 'checked' : '' }}
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
                           value="{{ old('target_value', $weeklyTarget->target_value) }}"
                           min="0"
                           class="m-input {{ $errors->has('target_value') ? 'err' : '' }}"
                           placeholder="50" />
                    @error('target_value')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="target_unit">Satuan <span style="color:var(--danger);">*</span></label>
                    <input id="target_unit" type="text" name="target_unit"
                           value="{{ old('target_unit', $weeklyTarget->target_unit) }}"
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
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>
</x-app-layout>
