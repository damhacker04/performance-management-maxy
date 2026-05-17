<x-app-layout>
@php
    $monthNamesId = [
        1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
        4  => 'April',    5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',     8  => 'Agustus',   9  => 'September',
        10 => 'Oktober',  11 => 'November',  12 => 'Desember',
    ];
    $priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
    $priorityColors = [
        'low'      => 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0',
        'medium'   => 'background:#fffbeb;color:#92400e;border:1px solid #fde68a',
        'high'     => 'background:#fff7ed;color:#c2410c;border:1px solid #fed7aa',
        'critical' => 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca',
    ];
    $statusChip = [
        'selesai'      => 'chip-success',
        'dalam_proses' => 'chip-info',
        'terhambat'    => 'chip-warning',
        'belum_mulai'  => 'chip-neutral',
    ];
@endphp

<div class="page">

    {{-- ── Page header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">
                Export Laporan
            </h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
                Unduh laporan KPI tim per bulan
            </p>
        </div>
        <a href="{{ route('export.download', ['month' => $month, 'year' => $year]) }}"
           class="btn btn-primary btn-sm" style="white-space:nowrap;flex-shrink:0;">
            <svg class="lucide sm" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Download CSV
        </a>
    </div>

    {{-- ── Filter bulan / tahun ── --}}
    <form method="GET" action="{{ route('export.index') }}">
        <div class="m-card" style="padding:14px 16px;">
            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:100px;">
                    <label style="font-size:10px;font-weight:700;color:var(--fg-3);
                                  text-transform:uppercase;letter-spacing:.06em;">
                        Bulan
                    </label>
                    <select name="month" style="border:1px solid var(--bd-1);border-radius:8px;
                                                padding:8px 10px;font-size:14px;color:var(--fg-1);
                                                background:#fff;width:100%;">
                        @foreach($months as $m)
                            <option value="{{ $m['value'] }}" {{ $m['value'] == $month ? 'selected' : '' }}>
                                {{ $m['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:80px;">
                    <label style="font-size:10px;font-weight:700;color:var(--fg-3);
                                  text-transform:uppercase;letter-spacing:.06em;">
                        Tahun
                    </label>
                    <select name="year" style="border:1px solid var(--bd-1);border-radius:8px;
                                               padding:8px 10px;font-size:14px;color:var(--fg-1);
                                               background:#fff;width:100%;">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                    <svg class="lucide sm" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    Tampilkan
                </button>
            </div>
        </div>
    </form>

    {{-- ── Empty state ── --}}
    @if(count($reports) === 0)
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">
                    Tidak ada data
                </p>
                <p style="font-size:13px;color:var(--fg-3);margin:0;">
                    Belum ada laporan untuk
                    <strong>{{ $monthNamesId[$month] }} {{ $year }}</strong>.
                </p>
            </div>
        </div>
    @else
        {{-- Summary --}}
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="overline-label">Hasil</span>
            <span class="chip chip-neutral" style="font-size:10px;">
                {{ count($reports) }} anggota · {{ $monthNamesId[$month] }} {{ $year }}
            </span>
        </div>

        {{-- ── Report per orang ── --}}
        @foreach($reports as $report)
            @php
                $u       = $report['user'];
                $entries = $report['entries'];
            @endphp
            <div class="m-card" style="overflow:hidden;padding:0;">

                {{-- Header nama --}}
                <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
                            background:linear-gradient(135deg,#eef2ff,#f0f9ff);
                            border-bottom:1px solid #e0e7ff;">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--maxy-navy);
                                color:#fff;display:flex;align-items:center;justify-content:center;
                                font-weight:800;font-size:14px;flex-shrink:0;">
                        {{ strtoupper(substr($u->name, 0, 1)) }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:14px;font-weight:700;color:var(--fg-1);">
                            {{ $u->name }}
                        </div>
                        <div style="font-size:11px;color:var(--fg-3);display:flex;align-items:center;gap:5px;">
                            {{ ucfirst(str_replace('_', ' ', $u->department ?? '-')) }}
                            <span class="chip {{ $u->role === 'leader' ? 'chip-info' : 'chip-success' }}"
                                  style="font-size:10px;padding:1px 6px;">
                                {{ ucfirst($u->role) }}
                            </span>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;">
                        <span class="chip chip-neutral" style="font-size:10px;">{{ $entries->count() }} task</span>
                        <span class="chip chip-success" style="font-size:10px;">
                            {{ $entries->where('status','selesai')->count() }} selesai
                        </span>
                    </div>
                </div>

                {{-- Tabel scroll horizontal --}}
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:680px;">
                        <thead>
                            <tr style="background:#1e40af;">
                                <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;
                                           font-weight:700;white-space:nowrap;width:28px;">#</th>
                                <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;
                                           font-weight:700;white-space:nowrap;width:72px;">Tanggal</th>
                                <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;
                                           font-weight:700;">Task / Deskripsi</th>
                                <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;
                                           font-weight:700;white-space:nowrap;width:65px;">Prioritas</th>
                                <th style="color:#fff;padding:7px 10px;text-align:center;font-size:10px;
                                           font-weight:700;white-space:nowrap;width:55px;">Durasi</th>
                                <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;
                                           font-weight:700;white-space:nowrap;width:78px;">Status</th>
                                <th style="color:#fff;padding:7px 10px;text-align:center;font-size:10px;
                                           font-weight:700;white-space:nowrap;width:50px;">% Done</th>
                                <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;
                                           font-weight:700;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $i => $entry)
                                @php
                                    $prio   = $entry->priority ?? 'medium';
                                    $sChip  = $statusChip[$entry->status] ?? 'chip-neutral';
                                    $sLabel = ucfirst(str_replace('_', ' ', $entry->status));
                                @endphp
                                <tr style="{{ $i % 2 === 1 ? 'background:#fafbff;' : '' }}">
                                    <td style="padding:6px 10px;text-align:center;color:var(--fg-4);
                                               border-bottom:1px solid #f3f4f6;">{{ $i + 1 }}</td>
                                    <td style="padding:6px 10px;white-space:nowrap;color:var(--fg-2);
                                               border-bottom:1px solid #f3f4f6;">
                                        {{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}
                                    </td>
                                    <td style="padding:6px 10px;font-weight:600;color:var(--fg-1);
                                               border-bottom:1px solid #f3f4f6;line-height:1.4;">
                                        {{ $entry->title }}
                                    </td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #f3f4f6;">
                                        <span style="display:inline-block;padding:2px 7px;border-radius:99px;
                                                     font-size:10px;font-weight:700;
                                                     {{ $priorityColors[$prio] ?? $priorityColors['medium'] }}">
                                            {{ $priorityLabels[$prio] ?? 'Medium' }}
                                        </span>
                                    </td>
                                    <td style="padding:6px 10px;text-align:center;color:var(--fg-2);
                                               border-bottom:1px solid #f3f4f6;">
                                        {{ $entry->duration ?? '-' }}<span style="font-size:10px;color:var(--fg-4)"> mnt</span>
                                    </td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #f3f4f6;">
                                        <span class="chip {{ $sChip }}" style="font-size:10px;">{{ $sLabel }}</span>
                                    </td>
                                    <td style="padding:6px 10px;text-align:center;font-weight:800;
                                               color:var(--maxy-navy);border-bottom:1px solid #f3f4f6;">
                                        {{ $entry->percent_done ?? 0 }}%
                                    </td>
                                    <td style="padding:6px 10px;color:var(--fg-3);font-size:11px;
                                               border-bottom:1px solid #f3f4f6;line-height:1.4;">
                                        {{ $entry->notes ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>{{-- /m-card --}}
        @endforeach
    @endif

</div>{{-- /page --}}
</x-app-layout>
