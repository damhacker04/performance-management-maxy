<x-app-layout>
@php
    $isMile = $kpiActual->kpiTarget?->isMilestone() ?? false;
@endphp
<div class="page" style="max-width:580px;">

    {{-- Back --}}
    <a href="{{ route('kpi.actuals.index') }}" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:4px;">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Kembali ke KPI Actual
    </a>

    <div class="m-card">
        <h2 style="font-size:17px;font-weight:800;color:var(--maxy-navy);margin:0 0 20px;">Edit Realisasi KPI</h2>

        <form method="POST" action="{{ route('kpi.actuals.update', $kpiActual) }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PATCH')

            {{-- Readonly Info --}}
            <div style="background:var(--neutral-50,#f8fafc);border:1.5px solid var(--neutral-200);
                        border-radius:var(--r-md);padding:14px 16px;margin-bottom:20px;">
                <div style="font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                    Detail KPI
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <span style="font-size:11px;color:var(--fg-4);">Staf</span>
                        <div style="font-size:13px;font-weight:600;color:var(--fg-1);">{{ $kpiActual->staff?->name ?? '— (level dept)' }}</div>
                    </div>
                    <div>
                        <span style="font-size:11px;color:var(--fg-4);">Nama KPI</span>
                        <div style="font-size:13px;font-weight:600;color:var(--fg-1);">{{ $kpiActual->kpiTarget?->kpi_name ?? '-' }}</div>
                    </div>
                    <div>
                        <span style="font-size:11px;color:var(--fg-4);">Target</span>
                        <div style="font-size:13px;font-weight:700;color:var(--maxy-navy);">
                            @if($isMile)
                                Progress 0–100%
                            @elseif($kpiActual->kpiTarget)
                                {{ number_format($kpiActual->kpiTarget->target_value, 0, ',', '.') }} {{ $kpiActual->kpiTarget->unit }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div>
                        <span style="font-size:11px;color:var(--fg-4);">Periode</span>
                        <div style="font-size:13px;font-weight:600;color:var(--fg-1);">
                            @php
                                $monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
                            @endphp
                            {{ $monthNames[$kpiActual->month] ?? '-' }} {{ $kpiActual->year }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Nilai Aktual (editable) --}}
            <div class="field">
                <label class="">{{ $isMile ? 'Progress Milestone (0–100%)' : 'Nilai Aktual Realisasi' }} <span style="color:var(--danger);">*</span></label>
                <input type="number" name="actual_value" step="0.01" min="0" {{ $isMile ? 'max=100' : '' }}
                       class="m-input @error('actual_value') is-invalid @enderror"
                       value="{{ old('actual_value', $kpiActual->actual_value) }}"
                       required>
                @error('actual_value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if($isMile)
                    <p class="form-hint">Isi progress 0–100%.</p>
                @elseif($kpiActual->kpiTarget)
                    <p class="form-hint">
                        Target: {{ number_format($kpiActual->kpiTarget->target_value, 0, ',', '.') }} {{ $kpiActual->kpiTarget->unit }}
                    </p>
                @endif
            </div>

            {{-- Catatan (editable) --}}
            <div class="field">
                <label class="">Catatan <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                <textarea name="notes" rows="3"
                          class="m-input @error('notes') is-invalid @enderror"
                          placeholder="Keterangan tambahan mengenai realisasi ini...">{{ old('notes', $kpiActual->notes) }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('kpi.actuals.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
