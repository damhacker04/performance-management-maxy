<x-app-layout>

<div class="page">
    <!-- Back -->
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('monthly-targets.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Target Baru</h1>
    </div>

    {{-- KPI Acuan (read-only info box) --}}
    @if($kpiRefs->isNotEmpty())
        <div style="background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                    border-radius:var(--r-md);padding:14px 16px;">
            <div style="font-size:12px;font-weight:700;color:var(--info,#2563eb);margin-bottom:8px;letter-spacing:.04em;text-transform:uppercase;">
                📊 KPI Departemen Aktif (Acuan)
            </div>
            @foreach($kpiRefs as $deptKey => $kpis)
                @php $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? $deptKey; @endphp
                <div style="font-size:11px;font-weight:700;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;margin:6px 0 4px;">
                    {{ $deptLabel }}
                </div>
                @foreach($kpis as $kpi)
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:var(--info,#2563eb);flex-shrink:0;"></span>
                        <span style="font-size:13px;color:var(--fg-2);">
                            <strong>{{ $kpi->kpi_name }}</strong>:
                            {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}/bulan
                        </span>
                    </div>
                @endforeach
            @endforeach
            <div style="font-size:11px;color:var(--fg-4);margin-top:8px;">
                Target yang dibuat harus mendukung pencapaian KPI di atas.
            </div>
        </div>
    @endif

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
                        <select id="department" name="department" class="m-select" required
                                onchange="filterStaffByDept(this.value)">
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
                        <span class="chip chip-dept-{{ str_replace('_','-', auth()->user()->department ?? 'neutral') }}" style="pointer-events:none;">
                            {{ auth()->user()->department ? ucfirst(str_replace('_', ' ', auth()->user()->department)) : 'Tanpa Departemen' }}
                        </span>
                    </div>
                </div>
            @endif

            <!-- Assign ke Staf -->
            <div class="field">
                <label for="assigned_to">
                    Target untuk Staf
                    <span style="color:var(--fg-4);font-weight:400;">(opsional — kosongkan jika target tim)</span>
                </label>
                <div class="select-wrap">
                    <select id="assigned_to" name="assigned_to" class="m-select">
                        <option value="">-- Target Tim (tidak spesifik per orang) --</option>
                        @foreach($staffList as $deptKey => $staffs)
                            @php $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? $deptKey; @endphp
                            <optgroup label="{{ $deptLabel }}" data-dept="{{ $deptKey }}">
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}"
                                            data-dept="{{ $staff->department }}"
                                            {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                @error('assigned_to')<span class="err">{{ $message }}</span>@enderror
                <small style="color:var(--fg-4);font-size:11px;">
                    Jika diisi, target ini akan muncul secara personal untuk staf tersebut dan digunakan sebagai acuan AI.
                </small>
            </div>

            <!-- KPI Acuan (opsional) -->
            @if($kpiRefs->flatten()->isNotEmpty())
                <div class="field">
                    <label for="kpi_target_id">
                        Acuan KPI
                        <span style="color:var(--fg-4);font-weight:400;">(opsional)</span>
                    </label>
                    <div class="select-wrap">
                        <select id="kpi_target_id" name="kpi_target_id" class="m-select">
                            <option value="">-- Tidak dikaitkan ke KPI spesifik --</option>
                            @foreach($kpiRefs as $deptKey => $kpis)
                                @php $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? $deptKey; @endphp
                                <optgroup label="{{ $deptLabel }}">
                                    @foreach($kpis as $kpi)
                                        <option value="{{ $kpi->id }}"
                                                {{ old('kpi_target_id') == $kpi->id ? 'selected' : '' }}>
                                            {{ $kpi->kpi_name }} — {{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    @error('kpi_target_id')<span class="err">{{ $message }}</span>@enderror
                </div>
            @endif

            <!-- Judul -->
            <div class="field">
                <label for="title">Judul Target <span style="color:var(--danger);">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                       class="m-input {{ $errors->has('title') ? 'err' : '' }}"
                       placeholder="cth. Campaign 3 kota, Follow up 50 leads" required />
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
                            @foreach(range(now()->year - 1, now()->year + 1) as $y)
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

<script>
// Filter dropdown staf berdasarkan departemen yang dipilih (untuk C-Level)
function filterStaffByDept(selectedDept) {
    const select = document.getElementById('assigned_to');
    const options = select.querySelectorAll('option[data-dept]');
    const groups  = select.querySelectorAll('optgroup[data-dept]');

    groups.forEach(g => {
        g.hidden = selectedDept ? g.dataset.dept !== selectedDept : false;
    });

    // Reset pilihan staf kalau dept berubah
    select.value = '';
}
</script>

</x-app-layout>
