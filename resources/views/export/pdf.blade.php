<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan KPI</title>
<style>
    @media print {
        @page { size: A4 landscape; margin: 12mm; }
        body  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .no-print { display: none !important; }
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1f2937; background: #fff; }

    .cover {
        padding: 40px;
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        color: white;
        text-align: center;
        margin-bottom: 30px;
        border-radius: 0 0 12px 12px;
    }
    .cover h1 { font-size: 22px; font-weight: bold; margin-bottom: 6px; }
    .cover p  { font-size: 13px; opacity: .85; }
    .cover .meta { margin-top: 18px; font-size: 11px; opacity: .7; }

    .section { margin: 0 20px 28px; page-break-inside: avoid; }

    .person-header {
        display: flex;
        align-items: center;
        background: #eff6ff;
        border-left: 4px solid #1e3a8a;
        border-radius: 0 8px 8px 0;
        padding: 10px 14px;
        margin-bottom: 8px;
    }
    .avatar {
        width: 34px; height: 34px; border-radius: 50%;
        background: #1e3a8a; color: white;
        font-size: 14px; font-weight: bold;
        display: flex; align-items: center; justify-content: center;
        margin-right: 12px; flex-shrink: 0;
    }
    .person-name  { font-size: 13px; font-weight: bold; color: #1e3a8a; }
    .person-meta  { font-size: 10px; color: #6b7280; margin-top: 2px; }
    .person-stats { margin-left: auto; text-align: right; font-size: 10px; }
    .person-stats strong { display: block; font-size: 12px; color: #1e3a8a; }

    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    thead tr { background: #1e3a8a; color: white; }
    thead th { padding: 5px 7px; text-align: left; font-size: 9px; font-weight: bold; white-space: nowrap; }
    tbody tr:nth-child(even) { background: #f8faff; }
    tbody td { padding: 5px 7px; border-bottom: 1px solid #e5e7eb; vertical-align: top; line-height: 1.4; }
    tbody td:first-child { text-align: center; color: #9ca3af; }

    .badge {
        display: inline-block; padding: 2px 6px; border-radius: 99px;
        font-size: 8px; font-weight: bold; white-space: nowrap;
    }
    .badge-selesai      { background: #dcfce7; color: #166534; }
    .badge-dalam_proses { background: #dbeafe; color: #1e40af; }
    .badge-terhambat    { background: #fee2e2; color: #991b1b; }
    .badge-belum_mulai  { background: #f3f4f6; color: #6b7280; }

    .prio-kritis  { background: #fef2f2; color: #991b1b; }
    .prio-tinggi  { background: #fff7ed; color: #c2410c; }
    .prio-sedang  { background: #fffbeb; color: #92400e; }
    .prio-rendah  { background: #f0fdf4; color: #166534; }

    .summary-row { background: #f0f9ff !important; }
    .summary-row td { font-weight: bold; color: #1e3a8a; padding: 6px 7px; }

    .footer { text-align: center; font-size: 8px; color: #9ca3af; margin: 20px 0 10px; }
</style>
</head>
<body>

{{-- Cover --}}
<div class="cover">
    <h1>📊 Laporan KPI Tim</h1>
    <p>{{ $periodLabel }}</p>
    <div class="meta">
        Dicetak: {{ now()->isoFormat('dddd, D MMMM YYYY · HH:mm') }}
        &nbsp;·&nbsp; Total anggota: {{ count($reports) }}
    </div>
</div>

@foreach($reports as $report)
@php
    $u       = $report['user'];
    $entries = $report['entries'];
    $total   = $entries->count();
    $selesai = $entries->where('status','selesai')->count();
    $proses  = $entries->where('status','dalam_proses')->count();
    $hambat  = $entries->where('status','terhambat')->count();

    $prioBadge = [
        'critical' => 'prio-kritis',
        'high'     => 'prio-tinggi',
        'medium'   => 'prio-sedang',
        'low'      => 'prio-rendah',
    ];
@endphp

<div class="section">
    <div class="person-header">
        <div class="avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
        <div>
            <div class="person-name">{{ $u->name }}</div>
            <div class="person-meta">{{ ucfirst($u->department ?? '-') }} &nbsp;·&nbsp; {{ ucfirst($u->role) }}</div>
        </div>
        <div class="person-stats">
            <strong>{{ $total }} tugas</strong>
            {{ $selesai }} selesai &nbsp;·&nbsp; {{ $proses }} proses &nbsp;·&nbsp; {{ $hambat }} terhambat
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22px;">#</th>
                <th style="width:58px;">Tanggal</th>
                <th>Deskripsi Tugas</th>
                <th style="width:55px;">Prioritas</th>
                <th style="width:40px;">Durasi</th>
                <th style="width:65px;">Status</th>
                <th>Catatan / Progress</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $i => $entry)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($entry->task_date)->format('d/m/Y') }}</td>
                <td>{{ $entry->task_description }}</td>
                <td>
                    <span class="badge {{ $prioBadge[$entry->priority] ?? 'prio-sedang' }}">
                        {{ $entry->priority_label }}
                    </span>
                </td>
                <td style="white-space:nowrap;">{{ $entry->duration_label }}</td>
                <td>
                    <span class="badge badge-{{ $entry->status }}">
                        {{ $entry->status_label }}
                    </span>
                </td>
                <td>{{ $entry->notes ?? '—' }}</td>
            </tr>
            @endforeach
            <tr class="summary-row">
                <td colspan="2"></td>
                <td>Total: {{ $total }} tugas</td>
                <td></td>
                <td></td>
                <td>✅ {{ $selesai }} selesai</td>
                <td>⚙️ {{ $proses }} proses &nbsp; ⚠️ {{ $hambat }} terhambat</td>
            </tr>
        </tbody>
    </table>
</div>
@endforeach

<div class="footer">
    Dokumen ini dibuat otomatis oleh Sistem Manajemen KPI &nbsp;·&nbsp; {{ now()->format('Y') }}
</div>

{{-- Tombol print (tersembunyi saat print) --}}
<div class="no-print" style="position:fixed;bottom:20px;right:20px;display:flex;gap:8px;">
    <button onclick="window.print()"
            style="background:#1e3a8a;color:#fff;border:none;border-radius:10px;
                   padding:10px 18px;font-size:13px;font-weight:600;cursor:pointer;
                   box-shadow:0 4px 12px rgba(0,0,0,.2);">
        🖨️ Print / Save as PDF
    </button>
    <button onclick="window.close()"
            style="background:#f3f4f6;color:#374151;border:none;border-radius:10px;
                   padding:10px 14px;font-size:13px;cursor:pointer;">
        ✕ Tutup
    </button>
</div>

<script>
    // Auto buka dialog print saat halaman dimuat
    window.addEventListener('load', function() { window.print(); });
</script>

</body>
</html>
