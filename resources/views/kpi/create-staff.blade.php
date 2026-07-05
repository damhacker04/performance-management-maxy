<x-app-layout>
<div class="page" style="max-width:580px;">

    {{-- Back --}}
    <a href="{{ route('kpi') }}" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:4px;">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Kembali ke KPI
    </a>

    <div class="m-card">
        <h2 style="font-size:17px;font-weight:800;color:var(--maxy-navy);margin:0 0 20px;">Tambah KPI Staff (Level 3)</h2>

        <form method="POST" action="{{ route('kpi.staff.store') }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf

            {{-- KPI Departemen (Parent) --}}
            <div class="field">
                <label class="">KPI Departemen (Parent) <span style="color:var(--danger);">*</span></label>
                <select name="parent_id" id="parent_id"
                        class="m-input @error('parent_id') is-invalid @enderror"
                        onchange="updateParentInfo(this)"
                        required>
                    <option value="">-- Pilih KPI Departemen --</option>
                    @foreach($kpiDepts as $kpi)
                        <option value="{{ $kpi->id }}"
                                data-kpi-name="{{ $kpi->kpi_name }}"
                                data-target="{{ $kpi->target_value }}"
                                data-unit="{{ $kpi->unit }}"
                                data-dept="{{ $kpi->department }}"
                                {{ (old('parent_id') ?? request()->query('parent_id')) == $kpi->id ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ', $kpi->department)) }}
                            � {{ $kpi->kpi_name }}
                            � {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}
                        </option>
                    @endforeach
                </select></div>
                @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                {{-- Dynamic parent info box --}}
                <div id="parent-info-box" style="display:none;margin-top:10px;
                            background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                            border-radius:var(--r-md);padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--info,#2563eb);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                        Info KPI Parent
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Nama KPI</span>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);" id="pi-name">�</div>
                        </div>
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Departemen</span>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);" id="pi-dept">�</div>
                        </div>
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Target Dept</span>
                            <div style="font-size:13px;font-weight:700;color:var(--maxy-navy);" id="pi-target">�</div>
                        </div>
                        <div>
                            <span style="font-size:11px;color:var(--fg-4);">Satuan</span>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);" id="pi-unit">�</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Target Nilai untuk Staf Ini --}}
            <div class="field">
                <label class="">Target Nilai untuk Staf Ini <span style="color:var(--danger);">*</span></label>
                <input type="number" name="target_value" step="0.01" min="0"
                       class="m-input @error('target_value') is-invalid @enderror"
                       value="{{ old('target_value') }}"
                       placeholder="50" required>
                @error('target_value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="form-hint">Porsi dari target dept. Contoh: Dept 150 deal, staf ini 50 deal</p>
            </div>

            {{-- Pilih Staf --}}
            <div class="field">
                <label class="">Staf <span style="color:var(--danger);">*</span></label>
                <div class="select-wrap"><select name="user_id" id="user_id" class="m-select @error('user_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Staf --</option>
                    @foreach($staffs as $deptLabel => $deptStaffs)
                        <optgroup label="{{ $deptLabel }}">
                            @foreach($deptStaffs as $staf)
                                <option value="{{ $staf->id }}" {{ old('user_id') == $staf->id ? 'selected' : '' }}>
                                    {{ $staf->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select></div>
                @error('user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="form-hint" id="staff-hint">Pilih KPI Departemen dulu untuk menyaring daftar staf.</p>
            </div>

            {{-- Catatan --}}
            <div class="field">
                <label class="">Catatan <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea name="notes" rows="3"
                          class="m-input @error('notes') is-invalid @enderror"
                          placeholder="Konteks atau penjelasan tambahan untuk KPI staf ini...">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Info box --}}
            <div style="background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                        border-radius:var(--r-md);padding:12px 14px;margin-bottom:20px;">
                <div style="font-size:12px;color:var(--info,#2563eb);font-weight:600;margin-bottom:4px;">
                    Info Tentang KPI Level 3
                </div>
                <div style="font-size:12px;color:var(--fg-3);line-height:1.5;">
                    KPI L3 ini terhubung ke KPI departemen sebagai acuan. Unit dan nama KPI mengikuti parent.
                </div>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('kpi') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan KPI Staff
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function prettyDept(d) {
    return d ? d.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '';
}

/** Saring daftar staf agar hanya menampilkan staf di departemen yang sama dgn KPI parent */
function filterStaffByDept(dept) {
    const staffSel = document.getElementById('user_id');
    const hint     = document.getElementById('staff-hint');
    if (!staffSel) return;

    let visibleCount = 0;

    Array.from(staffSel.querySelectorAll('optgroup')).forEach(og => {
        const match = !dept || og.label === dept;
        og.hidden   = !match;
        Array.from(og.children).forEach(opt => {
            opt.hidden   = !match;
            opt.disabled = !match;
            if (match) visibleCount++;
        });
    });

    // Reset pilihan staf jika tak lagi cocok dgn departemen terpilih
    const cur = staffSel.selectedOptions[0];
    if (cur && cur.value && cur.hidden) staffSel.value = '';

    if (hint) {
        if (!dept) {
            hint.textContent = 'Pilih KPI Departemen dulu untuk menyaring daftar staf.';
        } else if (visibleCount === 0) {
            hint.textContent = 'Tidak ada staf aktif di departemen ' + prettyDept(dept) + '.';
        } else {
            hint.textContent = 'Menampilkan ' + visibleCount + ' staf di departemen ' + prettyDept(dept) + '.';
        }
    }
}

function updateParentInfo(select) {
    const opt = select.options[select.selectedIndex];
    const box = document.getElementById('parent-info-box');

    if (!select.value) {
        box.style.display = 'none';
        filterStaffByDept(null);
        return;
    }

    document.getElementById('pi-name').textContent   = opt.dataset.kpiName  || '-';
    document.getElementById('pi-dept').textContent   = prettyDept(opt.dataset.dept) || '-';
    document.getElementById('pi-target').textContent = opt.dataset.target
        ? Number(opt.dataset.target).toLocaleString('id-ID') + ' ' + (opt.dataset.unit || '')
        : '-';
    document.getElementById('pi-unit').textContent   = opt.dataset.unit  || '-';

    box.style.display = 'block';
    filterStaffByDept(opt.dataset.dept || null);
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('parent_id');
    if (sel && sel.value) updateParentInfo(sel);
    else filterStaffByDept(null);
});
</script>
</x-app-layout>


