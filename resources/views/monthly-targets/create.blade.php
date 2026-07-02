<x-app-layout>
@php
    $backParam = request()->query('back') ? urldecode(request()->query('back')) : null;
    $backHref  = $backParam ?? route('monthly-targets.index');
@endphp

<div class="page">
    <!-- Back -->
    <div style="display:flex;align-items:center;gap:8px;">
        <x-back-button :fallback="$backHref" style="margin-left:-8px;" />
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Target Baru</h1>
    </div>

    {{-- KPI Acuan (read-only info box) --}}
    @if($kpiRefs->isNotEmpty())
        <div style="background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                    border-radius:var(--r-md);padding:14px 16px;">
            <div style="font-size:12px;font-weight:700;color:var(--info,#2563eb);margin-bottom:8px;letter-spacing:.04em;text-transform:uppercase;">
                KPI Departemen Aktif (Acuan)
            </div>
            @foreach($kpiRefs as $deptKey => $kpis)
                @php $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? $deptKey; @endphp
                <div style="font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.04em;margin:6px 0 4px;">
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
            <div style="font-size:11px;color:var(--fg-3);margin-top:8px;">
                Target yang dibuat harus mendukung pencapaian KPI di atas.
            </div>
        </div>
    @endif

    <div class="m-card">
        <form method="POST" action="{{ route('monthly-targets.store') . ($backParam ? '?back=' . urlencode($backParam) : '') }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf

            <!-- Departemen -->
            @if(auth()->user()->isExecutive())
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

            <!-- Assign ke penerima target -->
            @php $assignLabel = auth()->user()->isExecutive() ? 'Leader' : 'Staf'; @endphp
            <div class="field">
                <label for="assigned_to">
                    Target untuk {{ $assignLabel }}
                    <span style="color:var(--fg-3);font-weight:400;">(opsional — kosongkan jika target tim)</span>
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
                <small style="color:var(--fg-3);font-size:11px;">
                    Jika diisi, target ini akan muncul secara personal untuk {{ strtolower($assignLabel) }} tersebut dan digunakan sebagai acuan AI.
                </small>
            </div>

            <!-- KPI Acuan (opsional) -->
            @if($kpiRefs->flatten()->isNotEmpty())
                <div class="field">
                    <label for="kpi_target_id">
                        Acuan KPI
                        <span style="color:var(--fg-3);font-weight:400;">(opsional)</span>
                    </label>
                    <div class="select-wrap">
                        <select id="kpi_target_id" name="kpi_target_id" class="m-select">
                            <option value="">-- Tidak dikaitkan ke KPI spesifik --</option>
                            @foreach($kpiRefs as $deptKey => $kpis)
                                @php
                                    $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? $deptKey;
                                    // L2 = benchmark dept; L3 = breakdown per staff. Level null/legacy diperlakukan sbg L2.
                                    $deptKpis  = $kpis->filter(fn($k) => (int)$k->kpi_level !== 3);
                                    $staffKpis = $kpis->where('kpi_level', 3);
                                @endphp

                                @if($deptKpis->isNotEmpty())
                                    <optgroup label="KPI Dept — {{ $deptLabel }}">
                                        @foreach($deptKpis as $kpi)
                                            <option value="{{ $kpi->id }}" data-level="2" data-staff-id=""
                                                    data-kpiname="{{ $kpi->kpi_name }}"
                                                    data-figure="{{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}"
                                                    {{ old('kpi_target_id') == $kpi->id ? 'selected' : '' }}>
                                                {{ $kpi->kpi_name }} — {{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if($staffKpis->isNotEmpty())
                                    <optgroup label="KPI Staff — {{ $deptLabel }}">
                                        @foreach($staffKpis as $kpi)
                                            <option value="{{ $kpi->id }}" data-level="3" data-staff-id="{{ $kpi->user_id }}"
                                                    data-staff-name="{{ $kpi->staff?->name ?? 'Staf' }}"
                                                    data-kpiname="{{ $kpi->kpi_name }}"
                                                    data-figure="{{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}"
                                                    {{ old('kpi_target_id') == $kpi->id ? 'selected' : '' }}>
                                                {{ $kpi->staff?->name ?? 'Staf' }} · {{ $kpi->kpi_name }} — {{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @error('kpi_target_id')<span class="err">{{ $message }}</span>@enderror
                    <div id="kpi-preview" style="display:none;align-items:center;gap:8px;margin-top:8px;
                                padding:8px 12px;border-radius:8px;
                                background:var(--success-50,#ecfdf5);border:1px solid var(--success-200,#a7f3d0);
                                font-size:12px;color:var(--fg-2);line-height:1.4;"></div>
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
                <label for="description">Deskripsi <span style="color:var(--fg-3);font-weight:400;">(opsional)</span></label>
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
    onStaffChange();
}

// ── LAPIS 1: Filter Acuan KPI sesuai staff yang dipilih ──
// KPI Dept (L2) selalu valid. KPI Staff (L3) hanya tampil milik staff terpilih.
// Target Tim (tanpa staff) → semua L3 disembunyikan.
function filterKpiByStaff() {
    const staffSel = document.getElementById('assigned_to');
    const kpiSel   = document.getElementById('kpi_target_id');
    if (!staffSel || !kpiSel) return;

    const staffId = staffSel.value;

    Array.from(kpiSel.options).forEach(o => {
        if (!o.dataset.level) return; // opsi "-- tidak dikaitkan --"
        const show = o.dataset.level === '2'
            ? true
            : (!!staffId && o.dataset.staffId === staffId);
        o.hidden = !show;
        o.disabled = !show;
    });

    // Sembunyikan optgroup yang seluruh isinya tersembunyi
    Array.from(kpiSel.querySelectorAll('optgroup')).forEach(og => {
        og.hidden = !Array.from(og.children).some(o => !o.hidden);
    });

    // Kalau pilihan saat ini jadi tidak valid → lepaskan
    const cur = kpiSel.selectedOptions[0];
    if (cur && cur.hidden) kpiSel.value = '';
}

// Auto-pilih Acuan KPI berdasarkan staff yang dipilih (KPI L3 milik staff itu)
function autoPickKpiForStaff() {
    const staffSel = document.getElementById('assigned_to');
    const kpiSel   = document.getElementById('kpi_target_id');
    if (!staffSel || !kpiSel) return;

    const staffId = staffSel.value;
    const cur     = kpiSel.selectedOptions[0];

    if (!staffId) {
        if (cur && cur.dataset.level === '3') kpiSel.value = '';
        return;
    }

    const matches = Array.from(kpiSel.options)
        .filter(o => o.dataset.level === '3' && o.dataset.staffId === staffId);

    if (matches.length === 1) {
        kpiSel.value = matches[0].value; // tepat 1 KPI L3 → auto-isi
    } else if (cur && cur.dataset.level === '3' && cur.dataset.staffId !== staffId) {
        kpiSel.value = '';
    }
}

// ── LAPIS 2: Kotak konfirmasi acuan yang terpilih ──
function updateKpiPreview() {
    const kpiSel = document.getElementById('kpi_target_id');
    const box    = document.getElementById('kpi-preview');
    if (!kpiSel || !box) return;

    const o = kpiSel.selectedOptions[0];
    if (!o || !o.value || !o.dataset.level) { box.style.display = 'none'; return; }

    const isStaff    = o.dataset.level === '3';
    const levelLabel = isStaff ? 'KPI Staff' : 'KPI Dept';
    const owner      = isStaff ? (' · milik <strong>' + (o.dataset.staffName || 'Staf') + '</strong>') : '';
    const check      = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="var(--success,#16a34a)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M20 6 9 17l-5-5"/></svg>';

    box.innerHTML = check + '<span>Acuan: <strong>' + (o.dataset.kpiname || '') + ' — ' + (o.dataset.figure || '') + '</strong> · ' + levelLabel + owner + '</span>';
    box.style.display = 'flex';
}

function onStaffChange() {
    filterKpiByStaff();
    autoPickKpiForStaff();
    updateKpiPreview();
}

document.addEventListener('DOMContentLoaded', function () {
    const staffSel = document.getElementById('assigned_to');
    const kpiSel   = document.getElementById('kpi_target_id');
    if (staffSel) staffSel.addEventListener('change', onStaffChange);
    if (kpiSel)   kpiSel.addEventListener('change', updateKpiPreview);

    // Inisialisasi — hormati nilai old() setelah validasi gagal (tanpa auto-pick)
    filterKpiByStaff();
    updateKpiPreview();
});
</script>

</x-app-layout>
