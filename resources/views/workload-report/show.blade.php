<x-app-layout>
<div class="page" style="max-width:860px;">

    {{-- Back --}}
    <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm"
       style="display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">
        <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Kembali ke Summary
    </a>

    {{-- Header Karyawan --}}
    <div class="m-card" style="margin-bottom:16px;padding:20px 24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">
                    AI Workload & Performance Report
                </div>
                <h1 style="font-size:20px;font-weight:800;color:var(--maxy-navy);margin:0 0 6px;">
                    {{ $staff->name }}
                </h1>
                <div style="display:flex;flex-wrap:wrap;gap:8px;font-size:12px;color:var(--fg-3);">
                    <span>{{ $staff->department ?? '—' }}</span>
                    <span>·</span>
                    <span>{{ $monthNames[$month] }} {{ $year }}</span>
                    <span>·</span>
                    <span>{{ $data['task_count'] }} entri task</span>
                    <span>·</span>
                    <span>📆 {{ $data['active_days'] }} dari {{ $data['working_days'] }} hari kerja
                        ({{ $data['working_days'] > 0 ? round($data['active_days']/$data['working_days']*100,1) : 0 }}%)</span>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;color:var(--fg-3);margin-bottom:6px;">Periode Data</div>
                <div style="font-size:13px;font-weight:600;color:var(--maxy-navy);">
                    {{ $data['date_range'][0] }} – {{ $data['date_range'][1] }}
                </div>
            </div>
        </div>

        {{-- Sumber Target Summary --}}
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);">
            <div style="font-size:11px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                Sumber Target
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @if($data['kpi_l3']->isNotEmpty())
                    @foreach($data['kpi_l3'] as $kpi)
                    <span style="background:var(--info-50,#eff6ff);color:var(--info,#2563eb);border:1px solid var(--info-200,#bfdbfe);padding:3px 10px;border-radius:20px;font-size:12px;">
                        KPI: {{ $kpi->kpi_name }} → {{ $kpi->target_value }} {{ $kpi->unit }}
                    </span>
                    @endforeach
                @endif
                @foreach($data['monthly_targets'] as $mt)
                <span style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:3px 10px;border-radius:20px;font-size:12px;">
                    {{ Str::limit($mt->title, 40) }}
                </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- AI Report Section --}}
    <div class="m-card" style="margin-bottom:16px;" id="ai-report-card">

        {{-- Tombol Generate --}}
        <div id="generate-section" style="padding:24px;text-align:center;">
            <div style="font-size:32px;margin-bottom:8px;">🤖</div>
            <div style="font-weight:700;color:var(--maxy-navy);margin-bottom:6px;">Generate AI Report</div>
            <div style="font-size:13px;color:var(--fg-3);margin-bottom:18px;max-width:400px;margin-left:auto;margin-right:auto;">
                AI akan menganalisis seluruh data kinerja {{ $staff->name }} dan menghasilkan laporan naratif komprehensif.
            </div>
            <button id="btn-generate" class="btn btn-primary"
                    onclick="generateReport({{ $staff->id }}, {{ $month }}, {{ $year }})">
                <svg class="lucide sm" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Generate Laporan AI
            </button>
        </div>

        {{-- Loading State --}}
        <div id="loading-section" style="padding:40px;text-align:center;display:none;">
            <div class="ai-spinner" style="width:40px;height:40px;border:3px solid var(--border);border-top-color:var(--maxy-navy);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px;"></div>
            <div style="font-weight:600;color:var(--maxy-navy);">AI sedang menganalisis data...</div>
            <div style="font-size:12px;color:var(--fg-3);margin-top:4px;">Membaca {{ $data['task_count'] }} entri task & semua target. Mohon tunggu ~15–30 detik.</div>
        </div>

        {{-- Error State --}}
        <div id="error-section" style="padding:24px;display:none;">
            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--r-md);padding:14px;color:#dc2626;font-size:13px;">
                <strong>Gagal generate laporan:</strong>
                <span id="error-message"></span>
            </div>
            <button class="btn btn-ghost" style="margin-top:12px;" onclick="resetGenerate()">Coba Lagi</button>
        </div>

        {{-- Report Result --}}
        <div id="report-section" style="display:none;">

            {{-- 1. Persentase Pencapaian --}}
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
                <h2 style="font-size:15px;font-weight:700;color:var(--maxy-navy);margin:0 0 14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:var(--maxy-navy);color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;">1</span>
                    Persentase Pencapaian
                </h2>
                <div id="achievement-content" style="font-size:13px;color:var(--fg-2);line-height:1.7;"></div>
            </div>

            {{-- 2. Area Optimasi --}}
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
                <h2 style="font-size:15px;font-weight:700;color:var(--maxy-navy);margin:0 0 14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:var(--maxy-navy);color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;">2</span>
                    Area Optimasi
                </h2>
                <div id="optimization-content"></div>
            </div>

            {{-- 3. Skor Objektif --}}
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
                <h2 style="font-size:15px;font-weight:700;color:var(--maxy-navy);margin:0 0 14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:var(--maxy-navy);color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;">3</span>
                    Skor Objektif
                </h2>
                <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                    <div id="score-badge"
                         style="font-size:42px;font-weight:900;min-width:80px;text-align:center;line-height:1.1;"></div>
                    <div id="score-reasoning"
                         style="font-size:13px;color:var(--fg-2);line-height:1.7;flex:1;"></div>
                </div>
            </div>

            {{-- 4. Rekomendasi CEO --}}
            <div style="padding:20px 24px;">
                <h2 style="font-size:15px;font-weight:700;color:var(--maxy-navy);margin:0 0 14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:var(--maxy-navy);color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;">4</span>
                    Rekomendasi
                </h2>
                <div id="recommendations-content"></div>

                {{-- Regenerate --}}
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="btn btn-ghost btn-sm" onclick="resetGenerate()">
                        <svg class="lucide sm" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Regenerate
                    </button>
                    <button class="btn btn-ghost btn-sm" onclick="window.print()">
                        <svg class="lucide sm" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Preview: Task Entries --}}
    <div class="m-card" style="padding:0;overflow:hidden;">
        <details>
            <summary style="padding:14px 18px;cursor:pointer;font-weight:600;color:var(--maxy-navy);font-size:14px;list-style:none;display:flex;align-items:center;gap:8px;">
                <svg class="lucide sm" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Log Aktivitas Harian ({{ $data['task_count'] }} entri)
                <span style="font-size:12px;color:var(--fg-3);font-weight:400;">— klik untuk lihat detail</span>
            </summary>
            <div style="border-top:1px solid var(--border);overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="background:var(--bg-2);">
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--fg-2);">Tanggal</th>
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--fg-2);">Judul Task</th>
                            <th style="padding:8px 14px;text-align:center;font-weight:600;color:var(--fg-2);">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['tasks'] as $task)
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:8px 14px;white-space:nowrap;color:var(--fg-3);">
                                {{ $task->task_date->format('d M') }}
                            </td>
                            <td style="padding:8px 14px;color:var(--fg-2);">
                                {{ $task->task_description ?? 'Tanpa Judul' }}
                            </td>
                            <td style="padding:8px 14px;text-align:center;">
                                @php $s = $task->status ?? '-'; @endphp
                                <span style="font-size:11px;padding:2px 8px;border-radius:12px;
                                    background:{{ $s === 'selesai' ? '#dcfce7' : ($s === 'revisi' ? '#fef9c3' : '#f3f4f6') }};
                                    color:{{ $s === 'selesai' ? '#166534' : ($s === 'revisi' ? '#92400e' : '#6b7280') }};">
                                    {{ $s }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
@media print {
    .btn, details summary { display: none !important; }
    #generate-section, #loading-section { display: none !important; }
    #report-section { display: block !important; }
}
</style>

<script>
const GENERATE_URL = "{{ route('workload-report.generate') }}";
const CSRF         = "{{ csrf_token() }}";
const EXISTING_REPORT = {!! $existingReportJson ?? 'null' !!};

document.addEventListener('DOMContentLoaded', () => {
    if (EXISTING_REPORT) {
        renderReport(EXISTING_REPORT);
        showSection('report');
        // Rename Generate button to Regenerate
        document.getElementById('btn-generate').innerHTML = '<svg class="lucide sm" viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21v-5h5"/></svg> Regenerate AI (Timpa)';
    } else {
        showSection('generate');
    }
});

async function generateReport(staffId, month, year) {
    if (EXISTING_REPORT) {
        if (!confirm('Laporan ini sudah digenerate sebelumnya. Apakah Anda yakin ingin meng-generate ulang dan menimpa laporan lama?')) {
            return;
        }
    }
    showSection('loading');

    try {
        const resp = await fetch(GENERATE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ staff_id: staffId, month, year }),
        });

        const data = await resp.json();

        if (!resp.ok || data.error) {
            showError(data.error || 'Terjadi kesalahan.');
            return;
        }

        renderReport(data.report);
        showSection('report');

    } catch (e) {
        showError('Koneksi gagal: ' + e.message);
    }
}

function renderReport(report) {
    // 1. Achievement
    const achDiv = document.getElementById('achievement-content');
    let achHTML = '';
    if (report.achievement && typeof report.achievement === 'object') {
        for (const [target, narasi] of Object.entries(report.achievement)) {
            achHTML += `<div style="margin-bottom:14px;">
                <div style="font-weight:600;color:var(--maxy-navy);margin-bottom:4px;">${escHtml(target)}</div>
                <div style="color:var(--fg-2);line-height:1.7;">${escHtml(narasi)}</div>
            </div>`;
        }
    }
    achDiv.innerHTML = achHTML || '<p style="color:var(--fg-3);">Tidak ada data achievement.</p>';

    // 2. Optimization Areas
    const optDiv = document.getElementById('optimization-content');
    let optHTML = '';
    if (Array.isArray(report.optimization_areas)) {
        report.optimization_areas.forEach((item, i) => {
            optHTML += `<div style="display:flex;gap:12px;margin-bottom:12px;padding:12px;background:var(--bg-2);border-radius:var(--r-md);">
                <div style="flex-shrink:0;color:var(--warning);"><svg class="lucide sm" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg></div>
                <div>
                    <div style="font-weight:600;color:var(--maxy-navy);margin-bottom:3px;">${escHtml(item.title ?? '')}</div>
                    <div style="font-size:13px;color:var(--fg-2);line-height:1.6;">${escHtml(item.detail ?? '')}</div>
                </div>
            </div>`;
        });
    }
    optDiv.innerHTML = optHTML || '<p style="color:var(--fg-3);">Tidak ada area optimasi yang teridentifikasi.</p>';

    // 3. Score
    const score    = parseInt(report.score ?? 0);
    const scoreEl  = document.getElementById('score-badge');
    const color    = score >= 80 ? '#16a34a' : score >= 60 ? '#ca8a04' : '#dc2626';
    scoreEl.innerHTML = `<span style="color:${color};">${score}</span><span style="font-size:16px;color:var(--fg-3);">/100</span>`;
    document.getElementById('score-reasoning').textContent = report.score_reasoning ?? '';

    // 4. Recommendations
    const recDiv = document.getElementById('recommendations-content');
    let recHTML = '';
    if (Array.isArray(report.ceo_recommendations)) {
        report.ceo_recommendations.forEach((rec, i) => {
            recHTML += `<div style="display:flex;gap:10px;margin-bottom:10px;align-items:flex-start;">
                <span style="background:var(--maxy-navy);color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;margin-top:1px;">${i+1}</span>
                <div style="font-size:13px;color:var(--fg-2);line-height:1.6;">${escHtml(rec)}</div>
            </div>`;
        });
    }
    recDiv.innerHTML = recHTML || '<p style="color:var(--fg-3);">Tidak ada rekomendasi.</p>';
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}

function showSection(name) {
    document.getElementById('generate-section').style.display = name === 'generate' ? '' : 'none';
    document.getElementById('loading-section').style.display  = name === 'loading'  ? '' : 'none';
    document.getElementById('error-section').style.display    = name === 'error'    ? '' : 'none';
    document.getElementById('report-section').style.display   = name === 'report'   ? '' : 'none';
}

function showError(msg) {
    document.getElementById('error-message').textContent = ' ' + msg;
    showSection('error');
}

function resetGenerate() {
    showSection('generate');
}
</script>

</x-app-layout>
