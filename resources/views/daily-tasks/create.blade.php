<x-app-layout>

<div class="page">
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('daily-tasks.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.2;">Laporan Harian</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
        </div>
    </div>

    {{-- Banner info: mode normal atau mode backdating --}}
    @if(isset($backdateRequest) && $backdateRequest)
        {{-- Mode Backdating: token valid --}}
        <div style="background:#FFF8E8;border:1px solid #FBB041;border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;">
            <svg class="lucide" style="width:15px;height:15px;flex-shrink:0;color:#B45309;margin-top:1px;" viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            <div style="flex:1;font-size:12px;color:#8B5A00;line-height:1.6;">
                📅 <strong>Mode Backdating</strong> — Laporan ini akan tercatat untuk tanggal
                <strong>{{ \Carbon\Carbon::parse($backdateRequest->requested_date)->isoFormat('dddd, D MMMM YYYY') }}</strong>
                (disetujui oleh {{ $backdateRequest->reviewer?->name ?? 'Leader' }}).
                Token berlaku hingga <strong>{{ $backdateRequest->token_expires_at?->isoFormat('D MMM, HH:mm') }}</strong>.
                <input type="hidden" name="backdate_token" value="{{ $backdateRequest->approval_token }}">
            </div>
        </div>
    @else
        {{-- Mode Normal --}}
        <div style="background:#F8FAFF;border:1px solid #BFDBFE;border-radius:10px;padding:10px 14px;display:flex;gap:10px;align-items:flex-start;">
            <svg class="lucide" style="width:15px;height:15px;flex-shrink:0;color:#1D4ED8;margin-top:1px;" viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            <div style="flex:1;font-size:12px;color:#1E3A8A;line-height:1.6;">
                Laporan ini otomatis tercatat untuk <strong>hari ini</strong>.
                Perlu mengisi laporan untuk tanggal kemarin atau sebelumnya?
                <a href="{{ route('backdate-requests.create') }}" style="color:var(--maxy-navy);font-weight:700;text-decoration:underline;">
                    Ajukan Izin Backdating →
                </a>
            </div>
        </div>
    @endif


    {{-- Section: Lanjutkan dari hari sebelumnya (cuma muncul kalau ada task belum selesai) --}}
    @if(!$continueFrom && $continuableTasks->isNotEmpty())
        @php
            $statusMap = [
                'belum_mulai' => 'neutral',
                'dalam_proses' => 'warning',
                'terhambat' => 'danger',
            ];
        @endphp
        <div class="m-card" style="padding:0;background:linear-gradient(to bottom,#FFF8E8,#fff);border:1px solid #FBB041;">
            <div class="section-head" style="border-bottom:1px solid rgba(251,176,65,.25);">
                <span class="overline-label" style="color:#8B5A00;display:flex;align-items:center;gap:6px;">
                    <svg class="lucide" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9M3 12V3m0 9h9"/></svg>
                    Lanjutkan task sebelumnya
                </span>
                <span style="font-size:11px;color:#A06A00;font-weight:600;">{{ $continuableTasks->count() }} belum selesai</span>
            </div>
            <div style="padding:0 16px 8px;">
                @foreach($continuableTasks as $task)
                    @php $sChip = $statusMap[$task->status] ?? 'neutral'; @endphp
                    <a href="{{ route('daily-tasks.create', ['continue_from' => $task->id]) }}"
                       class="m-row"
                       style="text-decoration:none;color:inherit;">
                        <div class="row-body">
                            <div class="row-title" style="font-size:13px;">
                                {{ Str::limit($task->task_description, 48) }}
                            </div>
                            <div class="row-meta">
                                <span class="chip chip-{{ $sChip }}">{{ $task->status_label }}</span>
                                @if($task->weeklyTarget)
                                    <span>· Minggu {{ $task->weeklyTarget->week_number }} · {{ Str::limit($task->weeklyTarget->title, 16) }}</span>
                                @endif
                                <span>· {{ \Carbon\Carbon::parse($task->task_date)->isoFormat('D MMM') }}</span>
                            </div>
                        </div>
                        <svg class="lucide sm" style="color:#A06A00;flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                @endforeach
            </div>
            <div style="padding:0 16px 12px;font-size:11px;color:#8B5A00;font-style:italic;">
                Pilih salah satu untuk mengisi form dengan data hari sebelumnya, lalu update progress hari ini.
            </div>
        </div>

        {{-- Divider visual antara opsi "lanjutkan" dan "buat baru" --}}
        <div style="display:flex;align-items:center;gap:10px;color:var(--fg-4);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;margin:4px 0;">
            <div style="flex:1;height:1px;background:var(--bg-3);"></div>
            atau buat laporan baru
            <div style="flex:1;height:1px;background:var(--bg-3);"></div>
        </div>
    @endif

    @if($weeklyTargets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--maxy-amber);" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Belum ada target mingguan</p>
                <p style="font-size:13px;color:var(--fg-3);">Leader departemenmu belum membuat target mingguan untuk bulan ini.</p>
            </div>
        </div>
    @else
        <div class="m-card">
            <form method="POST" action="{{ route('daily-tasks.store') }}"
                  enctype="multipart/form-data"
                  style="display:flex;flex-direction:column;gap:16px;">
                @csrf
                {{-- Hidden token untuk mode backdating --}}
                @if(isset($backdateRequest) && $backdateRequest)
                    <input type="hidden" name="backdate_token" value="{{ $backdateRequest->approval_token }}">
                @endif

                {{-- Banner ketika user pilih "lanjutkan dari kemarin" --}}
                @if($continueFrom)
                    <div style="background:#E8F0FE;border:1px solid var(--maxy-navy);border-radius:10px;padding:12px;display:flex;gap:10px;align-items:flex-start;">
                        <svg class="lucide sm" style="color:var(--maxy-navy);flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9M3 12V3m0 9h9"/></svg>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;color:var(--maxy-navy);font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Melanjutkan task</div>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);margin-top:2px;line-height:1.4;">{{ Str::limit($continueFrom->task_description, 80) }}</div>
                            <div style="font-size:11px;color:var(--fg-3);margin-top:3px;">
                                {{ \Carbon\Carbon::parse($continueFrom->task_date)->isoFormat('D MMM') }} · {{ $continueFrom->status_label }}
                            </div>
                        </div>
                        <a href="{{ route('daily-tasks.create') }}"
                           style="font-size:11px;color:var(--fg-3);text-decoration:underline;flex-shrink:0;align-self:center;"
                           title="Buat laporan baru dari kosong">Batal</a>
                    </div>
                @endif

                <!-- Weekly Target -->
                <div class="field">
                    <label for="weekly_target_id">Target Mingguan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="weekly_target_id" name="weekly_target_id"
                                class="m-select {{ $errors->has('weekly_target_id') ? 'err' : '' }}">
                            @php
                                $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                                $defaultWeekly = old('weekly_target_id', $continueFrom?->weekly_target_id ?? $preSelectedWeeklyId);
                                $groupedTargets = $weeklyTargets->groupBy(function($item) {
                                    return $item->monthlyTarget ? $item->monthlyTarget->id : 0;
                                });
                            @endphp
                            <option value="" {{ empty($defaultWeekly) ? 'selected' : '' }}>
                                📌 Tidak terkait target mingguan (tugas tambahan/mendadak)
                            </option>
                            @foreach($groupedTargets as $monthlyId => $wTargets)
                                @php
                                    $monthly = $wTargets->first()->monthlyTarget;
                                    $groupLabel = $monthly ? "Target Bulanan: {$monthly->title} ({$monthShort[$monthly->month]})" : "Tanpa Target Bulanan";
                                @endphp
                                <optgroup label="{{ Str::limit($groupLabel, 80) }}">
                                    @foreach($wTargets as $wt)
                                        @php
                                            $indicator = $wt->assigned_to ? '🎯 Pribadi' : '🏢 Umum';
                                        @endphp
                                        <option value="{{ $wt->id }}" {{ (int)$defaultWeekly === $wt->id ? 'selected' : '' }}>
                                            [{{ $indicator }}] Minggu {{ $wt->week_number }} — {{ Str::limit($wt->title, 50) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    @error('weekly_target_id')<span class="err">{{ $message }}</span>@enderror
                    <span style="font-size:11px;color:var(--fg-4);margin-top:4px;">
                        Pilih target mingguan yang sedang kamu kerjakan.
                    </span>
                </div>

                <!-- Tanggal (locked ke hari ini) -->
                <div class="field">
                    <label>Tanggal Tugas <span style="color:var(--fg-4);font-weight:400;">(otomatis hari ini)</span></label>
                    <input type="text"
                           value="{{ now()->isoFormat('dddd, D MMMM YYYY') }}"
                           class="m-input"
                           readonly
                           style="background:var(--bg-2);color:var(--fg-3);cursor:not-allowed;" />
                </div>

                <!-- Deskripsi -->
                <div class="field">
                    <label for="task_description">Deskripsi Tugas <span style="color:var(--danger);">*</span></label>
                    @php
                        $defaultDesc = old(
                            'task_description',
                            $continueFrom ? 'Lanjut: ' . Str::limit($continueFrom->task_description, 120) : ''
                        );
                    @endphp
                    <textarea id="task_description" name="task_description"
                              class="m-textarea {{ $errors->has('task_description') ? 'err' : '' }}"
                              placeholder="Apa yang kamu kerjakan hari ini?" required>{{ $defaultDesc }}</textarea>
                    @error('task_description')<span class="err">{{ $message }}</span>@enderror
                    @if($continueFrom)
                        <span style="font-size:11px;color:#8B5A00;margin-top:4px;">💡 Edit deskripsi untuk menjelaskan progres hari ini.</span>
                    @endif
                </div>

                <!-- Prioritas & Durasi -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label for="priority">Prioritas <span style="color:var(--danger);">*</span></label>
                        <div class="select-wrap">
                            <select id="priority" name="priority"
                                    class="m-select {{ $errors->has('priority') ? 'err' : '' }}" required>
                                @php $defaultPriority = old('priority', $continueFrom?->priority ?? 'medium'); @endphp
                                <option value="critical" {{ $defaultPriority === 'critical' ? 'selected' : '' }}>🔴 Kritis</option>
                                <option value="high"     {{ $defaultPriority === 'high'     ? 'selected' : '' }}>🟠 Tinggi</option>
                                <option value="medium"   {{ $defaultPriority === 'medium'   ? 'selected' : '' }}>🟡 Sedang</option>
                                <option value="low"      {{ $defaultPriority === 'low'      ? 'selected' : '' }}>🔵 Rendah</option>
                            </select>
                        </div>
                        @error('priority')<span class="err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="duration_value">Durasi <span style="color:var(--danger);">*</span></label>
                        <div style="display:grid;grid-template-columns:1fr 80px;gap:6px;">
                            <input id="duration_value" type="number" name="duration_value"
                                   value="{{ old('duration_value', 60) }}"
                                   min="1" max="1440"
                                   placeholder="0"
                                   class="m-input {{ $errors->has('duration_value') ? 'err' : '' }}" required />
                            <div class="select-wrap">
                                <select id="duration_unit" name="duration_unit" class="m-select" required>
                                    <option value="menit" {{ old('duration_unit','menit') === 'menit' ? 'selected' : '' }}>Menit</option>
                                    <option value="jam"   {{ old('duration_unit') === 'jam'           ? 'selected' : '' }}>Jam</option>
                                </select>
                            </div>
                        </div>
                        @error('duration_value')<span class="err">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Status -->
                <div class="field">
                    <label for="status">Status <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="status" name="status"
                                class="m-select {{ $errors->has('status') ? 'err' : '' }}" required>
                            @php
                                // Kalau lanjutan, default status pakai status sebelumnya (biasanya dalam_proses).
                                $defaultStatus = old('status', $continueFrom?->status ?? 'belum_mulai');
                            @endphp
                            <option value="belum_mulai"  {{ $defaultStatus === 'belum_mulai' ? 'selected' : '' }}>Belum Mulai</option>
                            <option value="dalam_proses" {{ $defaultStatus === 'dalam_proses' ? 'selected' : '' }}>Dalam Proses</option>
                            <option value="terhambat"    {{ $defaultStatus === 'terhambat'    ? 'selected' : '' }}>Terhambat</option>
                            <option value="selesai"      {{ $defaultStatus === 'selesai'      ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    @error('status')<span class="err">{{ $message }}</span>@enderror
                </div>

                <!-- Catatan / Progress narrative — WAJIB untuk semua status -->
                <div class="field">
                    <label for="notes">
                        Catatan / Progress
                        <span style="color:var(--danger);">*</span>
                    </label>
                    <textarea id="notes" name="notes"
                              class="m-textarea {{ $errors->has('notes') ? 'err' : '' }}"
                              style="min-height:96px;"
                              placeholder="Jelaskan apa yang sudah dikerjakan / progres / hambatan…"
                              minlength="5"
                              required>{{ old('notes') }}</textarea>
                    <small style="color:var(--fg-4);font-size:11px;">Minimal 5 karakter. Konteks task dibutuhkan untuk evaluasi KPI.</small>
                    @error('notes')<span class="err">{{ $message }}</span>@enderror
                </div>

                <!-- Bukti Laporan -->
                <div class="field">
                    @php $isSales = auth()->user()->department === 'sales'; @endphp
                    <label style="display:flex;align-items:center;gap:6px;">
                        <svg class="lucide" style="width:14px;height:14px;color:var(--maxy-navy);" viewBox="0 0 24 24"><path d="M15.5 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V8.5L15.5 3z"/><polyline points="15 3 15 9 21 9"/></svg>
                        Bukti Laporan
                        @if($isSales)
                            <span style="color:var(--danger);">*</span>
                            <span style="font-size:10px;color:var(--fg-3);font-weight:400;">(Wajib untuk Sales)</span>
                        @else
                            <span style="font-size:10px;color:var(--fg-3);font-weight:400;">(Opsional)</span>
                        @endif
                    </label>

                    {{-- Link URL bukti --}}
                    <div style="margin-bottom:8px;">
                        <div style="font-size:11px;color:var(--fg-3);margin-bottom:4px;font-weight:600;">Link Bukti (Google Sheets, CRM, Drive, dll.)</div>
                        <input type="url" id="proof_url" name="proof_url"
                               class="m-input {{ $errors->has('proof_url') ? 'err' : '' }}"
                               value="{{ old('proof_url') }}"
                               placeholder="https://docs.google.com/…">
                        @error('proof_url')<span class="err">{{ $message }}</span>@enderror
                    </div>

                    {{-- Divider --}}
                    <div style="display:flex;align-items:center;gap:8px;color:var(--fg-4);font-size:11px;margin-bottom:8px;">
                        <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                        atau upload file
                        <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                    </div>

                    {{-- Upload file --}}
                    <label for="proof_file"
                           style="display:flex;align-items:center;justify-content:center;gap:8px;
                                  border:2px dashed var(--bg-3);border-radius:10px;padding:16px;
                                  cursor:pointer;color:var(--fg-3);font-size:13px;
                                  background:var(--bg-2);transition:border-color .2s;"
                           id="proof_file_label"
                           ondragover="this.style.borderColor='var(--maxy-navy)'"
                           ondragleave="this.style.borderColor='var(--bg-3)'">
                        <svg class="lucide" style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span id="proof_file_text">JPG, PNG, atau PDF — maks. 5MB</span>
                    </label>
                    <input type="file" id="proof_file" name="proof_file"
                           accept=".jpg,.jpeg,.png,.pdf"
                           class="{{ $errors->has('proof_file') ? 'err' : '' }}"
                           style="display:none;"
                           onchange="document.getElementById('proof_file_text').textContent = this.files[0]?.name ?? 'JPG, PNG, atau PDF — maks. 5MB';">
                    @error('proof_file')<span class="err">{{ $message }}</span>@enderror

                    {{-- Divider paste --}}
                    <div style="display:flex;align-items:center;gap:8px;color:var(--fg-4);font-size:11px;margin-top:8px;margin-bottom:8px;">
                        <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                        atau paste screenshot (Ctrl+V)
                        <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                    </div>

                    {{-- Paste Zone --}}
                    <div id="clipboard-paste-zone"
                         tabindex="0"
                         style="border:2px dashed var(--bg-3);border-radius:10px;padding:16px;
                                text-align:center;color:var(--fg-4);font-size:12px;
                                background:var(--bg-2);cursor:pointer;outline:none;
                                transition:border-color .2s,background .2s;"
                         title="Klik di sini lalu tekan Ctrl+V untuk paste screenshot">
                        <svg class="lucide" style="width:20px;height:20px;margin:0 auto 6px;display:block;" viewBox="0 0 24 24"><path d="M9 2h6v2H9zM4 6h16v16H4z"/></svg>
                        Klik di sini, lalu tekan <kbd style="background:var(--bg-3);padding:1px 5px;border-radius:4px;font-size:11px;">Ctrl+V</kbd> untuk paste screenshot
                    </div>

                    {{-- Preview gambar dari clipboard --}}
                    <div id="clipboard-preview" style="display:none;margin-top:8px;position:relative;">
                        <img id="clipboard-img" src="" alt="Preview"
                             style="width:100%;border-radius:8px;border:1px solid var(--bg-3);max-height:220px;object-fit:contain;background:var(--bg-2);">
                        <button type="button" onclick="clearClipboardImage()"
                                style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.55);color:#fff;
                                       border:none;border-radius:50%;width:24px;height:24px;font-size:14px;
                                       cursor:pointer;display:flex;align-items:center;justify-content:center;">×</button>
                        <div id="clipboard-status" style="font-size:11px;color:var(--fg-3);margin-top:4px;text-align:center;">Menyimpan…</div>
                    </div>
                    {{-- Hidden field untuk path gambar clipboard --}}
                    <input type="hidden" id="clipboard_proof_file" name="clipboard_proof_file" value="">
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                    Kirim Laporan
                </button>
            </form>
        </div>
    @endif
</div>

<script>
(function () {
    const zone       = document.getElementById('clipboard-paste-zone');
    const preview    = document.getElementById('clipboard-preview');
    const img        = document.getElementById('clipboard-img');
    const status     = document.getElementById('clipboard-status');
    const hiddenPath = document.getElementById('clipboard_proof_file');
    const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    if (!zone) return;

    // Focus/blur styling
    zone.addEventListener('focus', () => zone.style.borderColor = 'var(--maxy-navy)');
    zone.addEventListener('blur',  () => zone.style.borderColor = 'var(--bg-3)');
    zone.addEventListener('click', () => zone.focus());

    // Global paste listener (aktif saat zone difokus ATAU tidak ada input lain yang aktif)
    document.addEventListener('paste', function (e) {
        const active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA') && active.id !== 'clipboard-paste-zone') return;

        const items = e.clipboardData?.items;
        if (!items) return;

        for (const item of items) {
            if (!item.type.startsWith('image/')) continue;

            const blob = item.getAsFile();
            if (!blob) continue;

            const reader = new FileReader();
            reader.onload = async function (ev) {
                const dataUrl = ev.target.result;

                // Tampilkan preview
                img.src = dataUrl;
                preview.style.display = 'block';
                status.textContent = 'Menyimpan ke server…';
                status.style.color = 'var(--fg-3)';
                hiddenPath.value = '';

                // Upload ke server
                try {
                    const resp = await fetch('{{ route("daily-tasks.upload-clipboard") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ image: dataUrl }),
                    });

                    const data = await resp.json();

                    if (resp.ok && data.path) {
                        hiddenPath.value = data.path;
                        status.textContent = '✅ Gambar berhasil disimpan — akan dilampirkan saat submit.';
                        status.style.color = '#16A571';
                    } else {
                        status.textContent = '⚠️ Gagal menyimpan: ' + (data.error ?? 'Coba lagi.');
                        status.style.color = 'var(--danger)';
                    }
                } catch (err) {
                    status.textContent = '⚠️ Gagal terhubung ke server.';
                    status.style.color = 'var(--danger)';
                }
            };
            reader.readAsDataURL(blob);
            break; // Hanya proses gambar pertama
        }
    });

    window.clearClipboardImage = function () {
        img.src = '';
        preview.style.display = 'none';
        hiddenPath.value = '';
    };
})();
</script>
</x-app-layout>

