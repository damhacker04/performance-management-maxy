<x-app-layout>
<div class="page" style="max-width:580px;">

    {{-- Back --}}
    <a href="{{ route('kpi') }}" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:4px;">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Kembali ke KPI
    </a>

    <div class="m-card">
        <h2 style="font-size:17px;font-weight:800;color:var(--maxy-navy);margin:0 0 20px;">Tambah KPI Baru</h2>

        <form method="POST" action="{{ route('kpi.store') }}">
            @csrf

            {{-- Departemen --}}
            <div class="form-group">
                <label class="form-label">Departemen <span style="color:var(--danger);">*</span></label>
                <select name="department" class="form-control @error('department') is-invalid @enderror" required>
                    <option value="">-- Pilih Departemen --</option>
                    @foreach($departments as $key => $label)
                        <option value="{{ $key }}" {{ old('department') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('department')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="form-hint">KPI ini berlaku untuk semua staf di departemen tersebut.</p>
            </div>

            {{-- Nama KPI --}}
            <div class="form-group">
                <label class="form-label">Nama KPI <span style="color:var(--danger);">*</span></label>
                <input type="text" name="kpi_name"
                       class="form-control @error('kpi_name') is-invalid @enderror"
                       value="{{ old('kpi_name') }}"
                       placeholder="cth: Jumlah Closing Deal, Revenue Bulanan, Interview Kandidat"
                       required>
                @error('kpi_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Target & Satuan --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">Target Nilai <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="target_value" step="0.01" min="0"
                           class="form-control @error('target_value') is-invalid @enderror"
                           value="{{ old('target_value') }}"
                           placeholder="50" required>
                    @error('target_value')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Satuan <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="unit"
                           class="form-control @error('unit') is-invalid @enderror"
                           value="{{ old('unit') }}"
                           placeholder="deal, %, kandidat, rupiah..."
                           required>
                    @error('unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Berlaku Bulan & Tahun --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">Berlaku Mulai Bulan <span style="color:var(--danger);">*</span></label>
                    <select name="month" class="form-control @error('month') is-invalid @enderror" required>
                        @foreach(['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $i => $m)
                            @if($i > 0)
                                <option value="{{ $i }}" {{ old('month', date('n')) == $i ? 'selected' : '' }}>
                                    {{ $m }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('month')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tahun <span style="color:var(--danger);">*</span></label>
                    <select name="year" class="form-control @error('year') is-invalid @enderror" required>
                        @foreach(range(2024, 2030) as $y)
                            <option value="{{ $y }}" {{ old('year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    @error('year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Catatan --}}
            <div class="form-group">
                <label class="form-label">Catatan <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea name="notes" rows="3"
                          class="form-control @error('notes') is-invalid @enderror"
                          placeholder="Konteks atau penjelasan tambahan tentang KPI ini...">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Info box --}}
            <div style="background:var(--info-50,#eff6ff);border:1px solid var(--info-200,#bfdbfe);
                        border-radius:var(--r-md);padding:12px 14px;margin-bottom:20px;">
                <div style="font-size:12px;color:var(--info,#2563eb);font-weight:600;margin-bottom:4px;">
                    ℹ️ Tentang KPI
                </div>
                <div style="font-size:12px;color:var(--fg-3);line-height:1.5;">
                    KPI adalah standar tetap perusahaan. Berbeda dari Target yang bersifat per-orang dan dinamis tiap bulan.
                    KPI ini menjadi <strong>acuan AI</strong> saat mengevaluasi pencapaian staf.
                </div>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('kpi') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan KPI
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
