<x-app-layout>
@php
    $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $weekRanges = \App\Models\WeeklyTarget::WEEK_RANGES;
    $selectedMonthly = old('monthly_target_id', $weeklyTarget->monthly_target_id);
    $contextBanner = match($context ?? null) {
        'leader' => ['', 'Target Saya', 'Mingguan ini terkait Target Anda dari C-Level', '#eff6ff', '#1e40af'],
        'team'   => ['👥', 'Target Tim', 'Mingguan ini terkait Target yang Anda buat untuk tim', '#f0fdf4', '#166534'],
        default  => null,
    };
@endphp

<div class="page">
    <!-- Back -->
    <div style="display:flex;align-items:center;gap:8px;">
@php
    $backParam = request()->query('back') ? urldecode(request()->query('back')) : null;
    $backHref  = $backParam ?? (($weeklyTarget->monthlyTarget && ($weeklyTarget->assigned_to ?? $weeklyTarget->monthlyTarget->assigned_to))
        ? route('period.staff-weekly', [
            'year'          => $weeklyTarget->year,
            'month'         => $weeklyTarget->month,
            'staff'         => $weeklyTarget->assigned_to ?? $weeklyTarget->monthlyTarget->assigned_to,
            'monthlyTarget' => $weeklyTarget->monthly_target_id
        ])
        : route('period.staff-list', [
            'year'          => $weeklyTarget->year,
            'month'         => $weeklyTarget->month
        ]));
@endphp
        <a href="{{ $backHref }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Edit Target Mingguan</h1>
    </div>

    <div class="m-card">
        <form method="POST" action="{{ route('weekly-targets.update', $weeklyTarget) }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PATCH')
            {{-- Teruskan ?back= agar setelah update bisa redirect ke period context --}}
            @if($backParam ?? null)
                <input type="hidden" name="back" value="{{ $backParam }}">
            @endif

            <!-- Monthly Target (LOCKED on edit — tidak bisa diubah) -->
            <div class="field">
                <label>Terkait Target Bulanan <span style="color:var(--danger);">*</span></label>

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

                {{-- Selalu terkunci saat edit: monthly target tidak boleh diubah --}}
                <input type="hidden" name="monthly_target_id" value="{{ $weeklyTarget->monthly_target_id }}">
                <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;
                            border-radius:10px;background:var(--bg-2);border:1.5px solid var(--bd-1);">
                    <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:11px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">
                            Target Bulanan
                        </div>
                        <div style="font-size:13px;font-weight:700;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $weeklyTarget->monthlyTarget->title }}
                        </div>
                        <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">
                            {{ $months[$weeklyTarget->monthlyTarget->month] }} {{ $weeklyTarget->monthlyTarget->year }}
                            &nbsp;·&nbsp; {{ ucfirst(str_replace('_',' ', $weeklyTarget->monthlyTarget->department)) }}
                            @if($weeklyTarget->monthlyTarget->description)
                                &nbsp;·&nbsp; <span style="font-style:italic;">{{ Str::limit($weeklyTarget->monthlyTarget->description, 60) }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="chip chip-neutral" style="font-size:11px;flex-shrink:0;">Terkunci</span>
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

            <!-- Ditugaskan Kepada -->
            <div class="field">
                <label for="assigned_to">Ditugaskan Kepada <span style="color:var(--fg-3);font-weight:400;">(opsional)</span></label>

                @if($assignedUserModel)
                    {{-- ✅ LOCKED: Target ini sudah ditugaskan ke staf tertentu --}}
                    <input type="hidden" name="assigned_to" value="{{ $assignedUserModel->id }}">
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;
                                border-radius:10px;background:var(--bg-2);border:1.5px solid var(--bd-1);">
                        <svg class="lucide sm" style="color:var(--fg-3);flex-shrink:0;" viewBox="0 0 24 24">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:11px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">
                                Ditugaskan Kepada
                            </div>
                            <div style="font-size:13px;font-weight:700;color:var(--fg-1);">
                                {{ $assignedUserModel->name }}
                            </div>
                            <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">
                                {{ $assignedUserModel->role === 'leader' ? 'Leader' : 'Staff' }}
                                @if($assignedUserModel->department)
                                    &nbsp;·&nbsp; {{ ucfirst(str_replace('_',' ', $assignedUserModel->department)) }}
                                @endif
                            </div>
                        </div>
                        <span class="chip chip-neutral" style="font-size:11px;flex-shrink:0;">Terkunci</span>
                    </div>
                @else
                    {{-- Belum ada assignee → bisa dipilih --}}
                    <div class="select-wrap">
                        <select id="assigned_to" name="assigned_to"
                                class="m-select {{ $errors->has('assigned_to') ? 'err' : '' }}">
                            <option value="">-- Umum (Semua staf di departemen dapat mengambil target ini) --</option>
                            @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}" {{ old('assigned_to', $weeklyTarget->assigned_to) == $staff->id ? 'selected' : '' }}>
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
                <input id="title" type="text" name="title" value="{{ old('title', $weeklyTarget->title) }}"
                       class="m-input {{ $errors->has('title') ? 'err' : '' }}" required />
                @error('title')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Deskripsi -->
            <div class="field">
                <label for="description">Deskripsi <span style="color:var(--fg-3);font-weight:400;">(opsional)</span></label>
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
