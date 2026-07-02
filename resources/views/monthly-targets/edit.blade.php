<x-app-layout>
@php
    $backUrl = request()->query('back')
        ? urldecode(request()->query('back'))
        : (url()->previous() !== url()->current() ? url()->previous() : route('monthly-targets.index'));
@endphp

<div class="page">
    <div style="display:flex;align-items:center;gap:8px;">
        <x-back-button :fallback="$backUrl" style="margin-left:-8px;" />
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Edit Target</h1>
    </div>

    <div class="m-card">
        <form method="POST" action="{{ route('monthly-targets.update', $monthlyTarget) }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf @method('PATCH')
            {{-- Teruskan asal (?back=) agar setelah simpan kembali ke halaman yang benar (mis. /admin/targets/leader/..). --}}
            @if(request('back'))
                <input type="hidden" name="back" value="{{ request('back') }}">
            @endif

            <div class="field">
                <label>Departemen</label>
                <div class="m-input" style="display:flex;align-items:center;background:var(--neutral-50);color:var(--fg-2);cursor:default;">
                    <span class="chip chip-dept-{{ str_replace('_','-', $monthlyTarget->department) }}" style="pointer-events:none;">
                        {{ ucfirst(str_replace('_', ' ', $monthlyTarget->department)) }}
                    </span>
                </div>
            </div>

            <div class="field">
                <label for="title">Judul Target <span style="color:var(--danger);">*</span></label>
                <input id="title" type="text" name="title"
                       value="{{ old('title', $monthlyTarget->title) }}"
                       class="m-input {{ $errors->has('title') ? 'err' : '' }}" required />
                @error('title')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" class="m-textarea">{{ old('description', $monthlyTarget->description) }}</textarea>
            </div>

            {{-- Acuan KPI (opsional) — bisa ditautkan menyusul setelah KPI staff dibuat --}}
            <div class="field">
                <label for="kpi_target_id">Acuan KPI <span style="color:var(--fg-3);font-weight:400;">(opsional)</span></label>
                <div class="select-wrap">
                    <select id="kpi_target_id" name="kpi_target_id" class="m-select">
                        <option value="">-- Tidak dikaitkan ke KPI spesifik --</option>
                        @foreach(($kpiRefs ?? collect()) as $deptKey => $kpis)
                            @php
                                $deptLabel = \App\Models\User::DEPARTMENTS[$deptKey] ?? $deptKey;
                                $deptKpis  = $kpis->filter(fn($k) => (int)$k->kpi_level !== 3);
                                $staffKpis = $kpis->where('kpi_level', 3);
                            @endphp
                            @if($deptKpis->isNotEmpty())
                                <optgroup label="KPI Dept — {{ $deptLabel }}">
                                    @foreach($deptKpis as $kpi)
                                        <option value="{{ $kpi->id }}" {{ (int) old('kpi_target_id', $monthlyTarget->kpi_target_id) === (int) $kpi->id ? 'selected' : '' }}>
                                            {{ $kpi->kpi_name }} — {{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($staffKpis->isNotEmpty())
                                <optgroup label="KPI Staff — {{ $deptLabel }}">
                                    @foreach($staffKpis as $kpi)
                                        <option value="{{ $kpi->id }}" {{ (int) old('kpi_target_id', $monthlyTarget->kpi_target_id) === (int) $kpi->id ? 'selected' : '' }}>
                                            {{ $kpi->staff?->name ?? 'Staf' }} · {{ $kpi->kpi_name }} — {{ number_format($kpi->target_value,0,',','.') }} {{ $kpi->unit }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <small style="font-size:11px;color:var(--fg-4);line-height:1.5;display:block;margin-top:4px;">
                    Belum ada KPI individu untuk stafnya? Kaitkan ke <strong>KPI Departemen</strong> dulu, atau biarkan kosong — bisa ditautkan lagi di sini setelah HR/C-Level membuat KPI-nya.
                </small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label for="month">Bulan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="month" name="month" class="m-select" required>
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                                <option value="{{ $i+1 }}" {{ old('month', $monthlyTarget->month) == $i+1 ? 'selected' : '' }}>{{ $bulan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="year">Tahun <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="year" name="year" class="m-select" required>
                            @foreach(range(2024, now()->year + 1) as $y)
                                <option value="{{ $y }}" {{ old('year', $monthlyTarget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>
</x-app-layout>
