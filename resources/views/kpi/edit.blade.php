<x-app-layout>
<div class="page" style="max-width:580px;">

    {{-- Back --}}
    <a href="{{ route('kpi') }}" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:4px;">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Kembali ke KPI
    </a>

    <div class="m-card">
        <h2 style="font-size:17px;font-weight:800;color:var(--maxy-navy);margin:0 0 20px;">Edit KPI</h2>

        <form method="POST" action="{{ route('kpi.update', $kpiTarget) }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PUT')

            {{-- Departemen --}}
            <div class="field">
                <label class="">Departemen <span style="color:var(--danger);">*</span></label>
                <div class="select-wrap"><select name="department" class="m-select @error('department') is-invalid @enderror" required>
                    <option value="">-- Pilih Departemen --</option>
                    @foreach($departments as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('department', $kpiTarget->department) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select></div>
                @error('department')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Nama KPI --}}
            <div class="field">
                <label class="">Nama KPI <span style="color:var(--danger);">*</span></label>
                <input type="text" name="kpi_name"
                       class="m-input @error('kpi_name') is-invalid @enderror"
                       value="{{ old('kpi_name', $kpiTarget->kpi_name) }}"
                       placeholder="cth: Jumlah Closing Deal"
                       required>
                @error('kpi_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Target & Satuan --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label class="">Target Nilai <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="target_value" step="0.01" min="0"
                           class="m-input @error('target_value') is-invalid @enderror"
                           value="{{ old('target_value', $kpiTarget->target_value) }}"
                           required>
                    @error('target_value')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label class="">Satuan <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="unit"
                           class="m-input @error('unit') is-invalid @enderror"
                           value="{{ old('unit', $kpiTarget->unit) }}"
                           placeholder="deal, %, kandidat..."
                           required>
                    @error('unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Berlaku Bulan & Tahun --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label class="">Berlaku Mulai Bulan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap"><select name="month" class="m-select @error('month') is-invalid @enderror" required>
                        @foreach(['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $i => $m)
                            @if($i > 0)
                                <option value="{{ $i }}"
                                    {{ old('month', $kpiTarget->month) == $i ? 'selected' : '' }}>
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
                            <option value="{{ $y }}"
                                {{ old('year', $kpiTarget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select></div>
                    @error('year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Status Aktif --}}
            <div class="field">
                <label class="">Status</label>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;
                            background:var(--neutral-50);border:1.5px solid var(--neutral-200);
                            border-radius:var(--r-md);">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           style="width:16px;height:16px;accent-color:var(--success);"
                           {{ old('is_active', $kpiTarget->is_active) ? 'checked' : '' }}>
                    <label for="is_active" style="font-size:13px;font-weight:500;color:var(--fg-2);cursor:pointer;margin:0;">
                        KPI ini masih aktif
                    </label>
                </div>
                <p class="form-hint">Nonaktifkan jika KPI ini sudah tidak berlaku.</p>
            </div>

            {{-- Catatan --}}
            <div class="field">
                <label class="">Catatan <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea name="notes" rows="3"
                          class="m-textarea @error('notes') is-invalid @enderror"
                          placeholder="Konteks atau penjelasan tambahan...">{{ old('notes', $kpiTarget->notes) }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('kpi') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>


