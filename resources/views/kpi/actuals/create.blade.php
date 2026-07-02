<x-app-layout>
<div class="page" style="max-width:580px;">

    {{-- Back --}}
    <a href="{{ route('kpi.actuals.index') }}" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:4px;">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Kembali ke KPI Actual
    </a>

    <div class="m-card">
        <h2 style="font-size:17px;font-weight:800;color:var(--maxy-navy);margin:0 0 20px;">Input Realisasi KPI Bulanan</h2>

        <form method="POST" action="{{ route('kpi.actuals.store') }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf

            {{-- Hidden staff_id (auto-filled by JS; kosong untuk KPI level dept) --}}
            <input type="hidden" name="staff_id" id="staff_id" value="{{ old('staff_id') }}">

            {{-- Pilih KPI --}}
            <div class="field">
                <label class="">KPI <span style="color:var(--danger);">*</span></label>
                <select name="kpi_target_id" id="kpi_target_id"
                        class="m-input @error('kpi_target_id') is-invalid @enderror"
                        onchange="onKpiChange(this)"
                        required>
                    <option value="">-- Pilih KPI --</option>
                    <optgroup label="KPI per Staff">
                        @foreach($kpiStaffs as $kpi)
                            <option value="{{ $kpi->id }}" {{ old('kpi_target_id') == $kpi->id ? 'selected' : '' }}>
                                [{{ ucfirst(str_replace('_',' ', $kpi->department ?? '')) }}]
                                {{ $kpi->staff?->name ?? 'Staf' }} · {{ $kpi->kpi_name }}
                                (Target: {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }})
                            </option>
                        @endforeach
                    </optgroup>
                    @if(($kpiDeptLevel ?? collect())->isNotEmpty())
                    <optgroup label="KPI Tim / Milestone (level dept)">
                        @foreach($kpiDeptLevel as $kpi)
                            <option value="{{ $kpi->id }}" {{ old('kpi_target_id') == $kpi->id ? 'selected' : '' }}>
                                [{{ ucfirst(str_replace('_',' ', $kpi->department ?? '')) }}]
                                {{ $kpi->kpi_name }}
                                @if($kpi->isMilestone()) (Milestone — progress %) @else (Target: {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}) @endif
                            </option>
                        @endforeach
                    </optgroup>
                    @endif
                </select>
                @error('kpi_target_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                {{-- Dynamic KPI info box --}}
                <div id="kpi-info-box" style="display:none;margin-top:10px;
                            background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                            border-radius:var(--r-md);padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--info,#2563eb);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                        Info KPI Terpilih
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Nama KPI</span>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);" id="ki-name">-</div>
                        </div>
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Departemen</span>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);" id="ki-dept">-</div>
                        </div>
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Target</span>
                            <div style="font-size:13px;font-weight:700;color:var(--maxy-navy);" id="ki-target">-</div>
                        </div>
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Staf</span>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);" id="ki-staff">-</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bulan & Tahun --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label class="">Bulan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap"><select name="month" class="m-select @error('month') is-invalid @enderror" required>
                        @foreach(['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $i => $m)
                            @if($i > 0)
                                <option value="{{ $i }}" {{ old('month', date('n')) == $i ? 'selected' : '' }}>
                                    {{ $m }}
                                </option>
                            @endif
                        @endforeach
                    </select></div>
                    @error('month')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label class="">Tahun <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap"><select name="year" class="m-select @error('year') is-invalid @enderror" required>
                        @foreach(range(2024, 2030) as $y)
                            <option value="{{ $y }}" {{ old('year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select></div>
                    @error('year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Nilai Aktual --}}
            <div class="field">
                <label class="" id="actual-label">Nilai Aktual Realisasi <span style="color:var(--danger);">*</span></label>
                <input type="number" name="actual_value" id="actual_value" step="0.01" min="0"
                       class="m-input @error('actual_value') is-invalid @enderror"
                       value="{{ old('actual_value') }}"
                       placeholder="0" required>
                @error('actual_value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Catatan --}}
            <div class="field">
                <label class="">Catatan <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea name="notes" rows="3"
                          class="m-input @error('notes') is-invalid @enderror"
                          placeholder="Keterangan tambahan mengenai realisasi ini...">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Info box --}}
            <div style="background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                        border-radius:var(--r-md);padding:12px 14px;margin-bottom:20px;">
                <div style="font-size:12px;color:var(--info,#2563eb);font-weight:600;margin-bottom:4px;">
                    Info Tentang Nilai Actual
                </div>
                <div style="font-size:12px;color:var(--fg-3);line-height:1.5;">
                    KPI per staff = capaian orang tsb. KPI level dept (tim/milestone) = capaian satu departemen.
                    Milestone diisi sebagai progress 0–100%.
                </div>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('kpi.actuals.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Realisasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const kpiData = {
@foreach($kpiStaffs as $kpi)
    {{ $kpi->id }}: {
        staff_id:     {{ $kpi->user_id ?? 'null' }},
        staff_name:   @json($kpi->staff?->name ?? ''),
        kpi_name:     @json($kpi->kpi_name),
        target_value: {{ $kpi->target_value }},
        unit:         @json($kpi->unit),
        dept:         @json($kpi->department ?? ''),
        is_dept:      false,
        is_milestone: false,
    },
@endforeach
@foreach(($kpiDeptLevel ?? collect()) as $kpi)
    {{ $kpi->id }}: {
        staff_id:     null,
        staff_name:   '— (level dept)',
        kpi_name:     @json($kpi->kpi_name),
        target_value: {{ $kpi->target_value }},
        unit:         @json($kpi->unit),
        dept:         @json($kpi->department ?? ''),
        is_dept:      true,
        is_milestone: {{ $kpi->isMilestone() ? 'true' : 'false' }},
    },
@endforeach
};

function setActualLabel(text) {
    const label = document.getElementById('actual-label');
    if (label && label.childNodes.length) label.childNodes[0].nodeValue = text + ' ';
}

function onKpiChange(select) {
    const box   = document.getElementById('kpi-info-box');
    const input = document.getElementById('actual_value');
    const id    = parseInt(select.value);

    if (!id || !kpiData[id]) {
        box.style.display = 'none';
        document.getElementById('staff_id').value = '';
        setActualLabel('Nilai Aktual Realisasi');
        if (input) { input.removeAttribute('max'); input.placeholder = '0'; }
        return;
    }

    const d = kpiData[id];
    document.getElementById('staff_id').value = d.is_dept ? '' : (d.staff_id || '');
    document.getElementById('ki-name').textContent = d.kpi_name || '-';
    document.getElementById('ki-dept').textContent = d.dept
        ? d.dept.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-';
    document.getElementById('ki-target').textContent = d.is_milestone
        ? 'Progress 0–100%'
        : (d.target_value ? Number(d.target_value).toLocaleString('id-ID') + ' ' + (d.unit || '') : '-');
    document.getElementById('ki-staff').textContent = d.staff_name || '-';

    if (d.is_milestone) {
        setActualLabel('Progress Milestone (0–100%)');
        if (input) { input.max = 100; input.placeholder = '0–100'; }
    } else {
        setActualLabel('Nilai Aktual Realisasi');
        if (input) { input.removeAttribute('max'); input.placeholder = '0'; }
    }

    box.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('kpi_target_id');
    if (sel && sel.value) onKpiChange(sel);
});
</script>
</x-app-layout>
