<x-app-layout>
@php
    $monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
@endphp

<div class="page">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:20px;font-weight:800;color:var(--maxy-navy);margin:0;letter-spacing:-.02em;">KPI Actual — Realisasi Bulanan</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:4px 0 0;">
                Pencatatan realisasi KPI per bulan (staf & level departemen)
            </p>
        </div>
        <a href="{{ route('kpi.actuals.create') }}" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Input Actual KPI
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:0;">{{ session('success') }}</div>
    @endif

    {{-- Filter Bar --}}
    <div class="m-card" style="padding:14px 16px;">
        <form method="GET" action="{{ route('kpi.actuals.index') }}"
              style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;">

            {{-- Bulan --}}
            <div style="flex:1;min-width:120px;">
                <label style="font-size:11px;font-weight:600;color:var(--fg-3);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Bulan</label>
                <div class="select-wrap"><select name="month" class="m-select" style="height:36px;font-size:13px;">
                    <option value="">Semua Bulan</option>
                    @foreach(['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $i => $m)
                        @if($i > 0)
                            <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>{{ $m }}</option>
                        @endif
                    @endforeach
                </select></div>
            </div>

            {{-- Tahun --}}
            <div style="flex:1;min-width:100px;">
                <label style="font-size:11px;font-weight:600;color:var(--fg-3);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Tahun</label>
                <div class="select-wrap"><select name="year" class="m-select" style="height:36px;font-size:13px;">
                    @foreach(range(2024, 2030) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select></div>
            </div>

            {{-- Departemen --}}
            <div style="flex:2;min-width:160px;">
                <label style="font-size:11px;font-weight:600;color:var(--fg-3);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Departemen</label>
                <div class="select-wrap"><select name="department" class="m-select" style="height:36px;font-size:13px;">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $key => $label)
                        <option value="{{ $key }}" {{ $dept == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select></div>
            </div>

            {{-- Filter Button --}}
            <div style="flex-shrink:0;">
                <button type="submit" class="btn btn-primary" style="height:36px;padding:0 16px;font-size:13px;">
                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                    Filter
                </button>
            </div>

            @if($month || $dept || $year != date('Y'))
                <div style="flex-shrink:0;">
                    <a href="{{ route('kpi.actuals.index') }}" class="btn btn-ghost" style="height:36px;padding:0 12px;font-size:13px;">Reset</a>
                </div>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="m-card" style="padding:0;overflow:hidden;">
        @if($actuals->isEmpty())
            <div style="padding:48px 24px;text-align:center;">
                <svg class="lucide" style="width:40px;height:40px;margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Belum Ada Data Actual</p>
                <p style="font-size:12px;color:var(--fg-3);margin:0 0 16px;">
                    Belum ada realisasi KPI yang diinput untuk periode ini.
                </p>
                <a href="{{ route('kpi.actuals.create') }}" class="btn btn-primary">+ Input Actual KPI</a>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:var(--neutral-50,#f8fafc);border-bottom:1.5px solid var(--neutral-200);">
                            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Staf</th>
                            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Departemen</th>
                            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">KPI</th>
                            <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Target</th>
                            <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Actual</th>
                            <th style="padding:10px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Capaian%</th>
                            <th style="padding:10px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Sumber</th>
                            <th style="padding:10px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--fg-3);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($actuals as $actual)
                            @php
                                $target  = $actual->kpiTarget;
                                $staff   = $actual->staff;
                                $isMile  = $target?->isMilestone() ?? false;
                                // Milestone: actual sudah %. Lainnya: actual/target*100.
                                $capaian = $isMile
                                    ? round(min(100, max(0, $actual->actual_value)), 1)
                                    : ($target && $target->target_value > 0
                                        ? round($actual->actual_value / $target->target_value * 100, 1)
                                        : 0);

                                if ($capaian >= 80) {
                                    $capColor = '#16A34A'; $capBg = '#DCFCE7';
                                } elseif ($capaian >= 60) {
                                    $capColor = '#D97706'; $capBg = '#FEF9C3';
                                } else {
                                    $capColor = '#DC2626'; $capBg = '#FEE2E2';
                                }

                                $isManual = ($actual->source ?? 'manual') === 'manual';
                            @endphp
                            <tr style="border-bottom:1px solid var(--neutral-100);transition:background .15s;"
                                onmouseenter="this.style.background='var(--neutral-50)'"
                                onmouseleave="this.style.background=''">

                                {{-- Staf --}}
                                <td style="padding:10px 14px;">
                                    <span style="font-weight:600;color:var(--fg-1);">{{ $staff?->name ?? '— (level dept)' }}</span>
                                </td>

                                {{-- Departemen --}}
                                <td style="padding:10px 14px;color:var(--fg-3);">
                                    {{ $target?->department ? ucfirst(str_replace('_', ' ', $target->department)) : '-' }}
                                </td>

                                {{-- KPI --}}
                                <td style="padding:10px 14px;">
                                    <span style="color:var(--fg-1);">{{ $target?->kpi_name ?? '-' }}</span>
                                    @if($target)
                                        <div style="font-size:11px;color:var(--fg-4);">
                                            {{ $monthNames[$actual->month] ?? '' }} {{ $actual->year }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Target --}}
                                <td style="padding:10px 14px;text-align:right;font-weight:600;color:var(--fg-2);">
                                    @if($isMile)
                                        <span style="color:var(--fg-4);">Progress %</span>
                                    @elseif($target)
                                        {{ number_format($target->target_value, 0, ',', '.') }} {{ $target->unit }}
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- Actual --}}
                                <td style="padding:10px 14px;text-align:right;font-weight:700;color:var(--maxy-navy);">
                                    {{ number_format($actual->actual_value, 0, ',', '.') }}
                                    <span style="font-size:11px;font-weight:400;color:var(--fg-4);">{{ $isMile ? '%' : $target?->unit }}</span>
                                </td>

                                {{-- Capaian% --}}
                                <td style="padding:10px 14px;text-align:center;">
                                    <span style="display:inline-block;padding:3px 9px;border-radius:20px;
                                                font-size:12px;font-weight:700;
                                                background:{{ $capBg }};color:{{ $capColor }};">
                                        {{ $capaian }}%
                                    </span>
                                </td>

                                {{-- Sumber --}}
                                <td style="padding:10px 14px;text-align:center;">
                                    @if($isManual)
                                        <span style="display:inline-block;padding:3px 9px;border-radius:20px;
                                                    font-size:11px;font-weight:600;
                                                    background:#DBEAFE;color:#1D4ED8;">
                                            Manual
                                        </span>
                                    @else
                                        <span style="display:inline-block;padding:3px 9px;border-radius:20px;
                                                    font-size:11px;font-weight:600;
                                                    background:var(--neutral-100);color:var(--fg-3);">
                                            Auto-detected
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td style="padding:10px 14px;text-align:center;">
                                    <a href="{{ route('kpi.actuals.edit', $actual) }}"
                                       class="btn btn-ghost btn-sm"
                                       style="display:inline-flex;align-items:center;gap:4px;">
                                        <svg class="lucide sm" viewBox="0 0 24 24">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination if needed --}}
            @if(method_exists($actuals, 'links') && $actuals->hasPages())
                <div style="padding:12px 16px;border-top:1px solid var(--neutral-100);">
                    {{ $actuals->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
</x-app-layout>
