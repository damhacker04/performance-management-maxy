<x-app-layout>
<div class="page">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h1 style="font-size:20px;font-weight:800;color:var(--maxy-navy);margin:0;">
                📊 AI Workload & Performance Report
            </h1>
            <p style="font-size:13px;color:var(--fg-3);margin:4px 0 0;">
                Laporan kinerja berbasis AI — analisis pola kerja, pencapaian target, dan rekomendasi.
            </p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="m-card" style="padding:14px 18px;margin-bottom:20px;">
        <form method="GET" action="{{ route('workload-report.index') }}"
              style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

            <div class="form-group" style="margin:0;min-width:130px;">
                <label class="form-label" style="margin-bottom:4px;">Bulan</label>
                <select name="month" class="form-control form-control-sm">
                    @foreach(['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $i => $m)
                        @if($i > 0)
                            <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>{{ $m }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin:0;min-width:100px;">
                <label class="form-label" style="margin-bottom:4px;">Tahun</label>
                <select name="year" class="form-control form-control-sm">
                    @foreach(range(2024, date('Y') + 1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            @if(in_array(auth()->user()->role, ['c_level','super_admin']) || auth()->user()->is_management)
            <div class="form-group" style="margin:0;min-width:160px;">
                <label class="form-label" style="margin-bottom:4px;">Departemen</label>
                <select name="department" class="form-control form-control-sm">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $key => $label)
                        <option value="{{ $key }}" {{ $dept == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <button type="submit" class="btn btn-primary btn-sm">
                <svg class="lucide sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Filter
            </button>
            
            <button type="button" class="btn btn-success btn-sm ml-auto" id="btn-generate-batch" style="margin-left: auto;">
                <svg class="lucide sm" viewBox="0 0 24 24"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72Z"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg>
                ✨ Generate Semua Laporan
            </button>
        </form>
    </div>

    {{-- Summary Table --}}
    <div class="m-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:15px;font-weight:700;color:var(--maxy-navy);margin:0;">
                Summary — {{ $monthNames[$month] }} {{ $year }}
            </h2>
            <span style="font-size:12px;color:var(--fg-3);">
                {{ $staffData->count() }} karyawan
            </span>
        </div>

        @if($staffData->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--fg-3);">
            <div style="font-size:32px;margin-bottom:8px;">📭</div>
            <div style="font-weight:600;">Tidak ada data karyawan</div>
            <div style="font-size:13px;margin-top:4px;">Coba ubah filter bulan, tahun, atau departemen.</div>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--bg-2,#f8f9fb);">
                        <th style="padding:10px 16px;text-align:left;font-weight:600;color:var(--fg-2);white-space:nowrap;">Nama Karyawan</th>
                        <th style="padding:10px 12px;text-align:left;font-weight:600;color:var(--fg-2);">Departemen</th>
                        <th style="padding:10px 12px;text-align:center;font-weight:600;color:var(--fg-2);">Total Task</th>
                        <th style="padding:10px 12px;text-align:center;font-weight:600;color:var(--fg-2);">Hari Aktif</th>
                        <th style="padding:10px 12px;text-align:center;font-weight:600;color:var(--fg-2);">KPI Capaian</th>
                        <th style="padding:10px 12px;text-align:center;font-weight:600;color:var(--fg-2);">Flag</th>
                        <th style="padding:10px 16px;text-align:center;font-weight:600;color:var(--fg-2);">Laporan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staffData as $row)
                    @php $staff = $row['staff']; @endphp
                    <tr style="border-top:1px solid var(--border);" class="workload-row">
                        <td style="padding:12px 16px;">
                            <div style="font-weight:600;color:var(--maxy-navy);">{{ $staff->name }}</div>
                            <div style="font-size:11px;color:var(--fg-3);">{{ $staff->role }}</div>
                        </td>
                        <td style="padding:12px;">
                            <span style="font-size:12px;background:var(--bg-2);padding:3px 8px;border-radius:20px;color:var(--fg-2);">
                                {{ $staff->department ?? '—' }}
                            </span>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="font-weight:700;color:var(--maxy-navy);">{{ $row['task_count'] }}</span>
                            <div style="font-size:11px;color:var(--fg-3);">entri</div>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="font-weight:600;">{{ $row['active_days'] }}</span>
                            <div style="font-size:11px;color:var(--fg-3);">hari</div>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            @if($row['kpi_pct'] !== null)
                                @php
                                    $pct = $row['kpi_pct'];
                                    $color = $pct >= 80 ? '#16a34a' : ($pct >= 60 ? '#ca8a04' : '#dc2626');
                                    $bg    = $pct >= 80 ? '#dcfce7' : ($pct >= 60 ? '#fef9c3' : '#fee2e2');
                                @endphp
                                <span style="background:{{ $bg }};color:{{ $color }};padding:3px 10px;border-radius:20px;font-weight:700;font-size:12px;">
                                    {{ $pct }}%
                                </span>
                            @else
                                <span style="color:var(--fg-4);font-size:12px;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px;text-align:center;font-size:18px;">
                            {{ $row['flag'] }}
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <a href="{{ route('workload-report.show', [$staff->id, $month, $year]) }}"
                               class="btn btn-ghost btn-sm"
                               style="display:inline-flex;align-items:center;gap:5px;">
                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Lihat
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Legend --}}
    <div style="display:flex;gap:16px;margin-top:14px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);">
            <span>✅</span> Skor ≥ 80
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);">
            <span>🟡</span> Skor 60–79
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);">
            <span>🔴</span> Skor &lt; 60
        </div>
        <div style="font-size:12px;color:var(--fg-3);">
            · Flag dihitung dari KPI capaian & task completion. Klik "Lihat" untuk laporan AI lengkap.
        </div>
    </div>

</div>
</x-app-layout>
