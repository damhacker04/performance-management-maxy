<x-app-layout>

<div class="page">
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('daily-tasks.show', $dailyTask) }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.2;">Edit Laporan</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">
                {{ \Carbon\Carbon::parse($dailyTask->task_date)->isoFormat('dddd, D MMMM YYYY') }}
                · dikirim {{ $dailyTask->created_at->isoFormat('HH:mm') }}
            </p>
        </div>
    </div>

    {{-- Banner info: complete_mode — diarahkan dari tombol tandai selesai --}}
    @if(session('complete_mode'))
        <div style="background:#E8F5E9;border:1px solid #4CAF50;border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;font-size:11px;color:#2E7D32;">
            <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong>Tandai Selesai</strong> — Pilih status "Selesai" dan isi catatan penyelesaian,
                lalu klik Simpan. Catatan wajib diisi sesuai aturan evaluasi KPI.
            </div>
        </div>
    @endif

    {{-- Banner info: kondisi edit --}}
    @if(!session('complete_mode'))
        <div style="background:#FFF8E8;border:1px solid #FBB041;border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;font-size:11px;color:#8B5A00;">
            <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <div>
                <strong>Mode edit</strong> — Kamu bisa update status dan catatan task ini selama belum ditandai
                selesai. Task yang sudah selesai bersifat final dan tidak bisa diubah.
            </div>
        </div>
    @endif

    <div class="m-card">
        <form method="POST" action="{{ route('daily-tasks.update', $dailyTask) }}"
              enctype="multipart/form-data"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PATCH')

            <!-- Weekly Target -->
            <div class="field">
                <label for="weekly_target_id">Target Mingguan</label>
                <div class="select-wrap">
                    <select id="weekly_target_id" name="weekly_target_id"
                            class="m-select {{ $errors->has('weekly_target_id') ? 'err' : '' }}">
                        @php
                            $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                            $selectedWeekly = old('weekly_target_id', $dailyTask->weekly_target_id);
                            $groupedTargets = $weeklyTargets->groupBy(function($item) {
                                return $item->monthlyTarget ? $item->monthlyTarget->id : 0;
                            });
                        @endphp
                        <option value="" {{ empty($selectedWeekly) ? 'selected' : '' }}>
                            📌 Tidak terkait target mingguan (tugas tambahan/mendadak)
                        </option>
                        @foreach($groupedTargets as $monthlyId => $wTargets)
                            @php
                                $monthly = $wTargets->first()->monthlyTarget;
                                $groupLabel = $monthly ? "Target Bulanan: {$monthly->title} ({$monthShort[$monthly->month]})" : "Tanpa Target Bulanan";
                            @endphp
                            <optgroup label="{{ Str::limit($groupLabel, 80) }}">
                                @foreach($wTargets as $wt)
                                    <option value="{{ $wt->id }}" {{ (int)$selectedWeekly === $wt->id ? 'selected' : '' }}>
                                        Minggu {{ $wt->week_number }} — {{ Str::limit($wt->title, 60) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <small style="color:var(--fg-4);font-size:11px;">Pilih target mingguan yang terkait, atau pilih "Tidak terkait" untuk tugas tambahan.</small>
                @error('weekly_target_id')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Tanggal (read-only — tanggal asli submit) -->
            <div class="field">
                <label>Tanggal Tugas <span style="color:var(--fg-4);font-weight:400;">(tidak bisa diubah)</span></label>
                <input type="text"
                       value="{{ \Carbon\Carbon::parse($dailyTask->task_date)->isoFormat('dddd, D MMMM YYYY') }}"
                       class="m-input"
                       readonly
                       style="background:var(--bg-2);color:var(--fg-3);cursor:not-allowed;" />
            </div>

            <!-- Deskripsi -->
            <div class="field">
                <label for="task_description">Deskripsi Tugas <span style="color:var(--danger);">*</span></label>
                <textarea id="task_description" name="task_description"
                          class="m-textarea {{ $errors->has('task_description') ? 'err' : '' }}"
                          placeholder="Apa yang kamu kerjakan hari ini?" required>{{ old('task_description', $dailyTask->task_description) }}</textarea>
                @error('task_description')<span class="err">{{ $message }}</span>@enderror
            </div>

            <!-- Prioritas & Durasi -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label for="priority">Prioritas <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="priority" name="priority"
                                class="m-select {{ $errors->has('priority') ? 'err' : '' }}" required>
                            @php $defaultPriority = old('priority', $dailyTask->priority); @endphp
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
                    @php
                        // Kalau habis dibagi 60, anggap user input dalam jam
                        $mins = $dailyTask->duration_minutes;
                        $defaultUnit = old('duration_unit', $mins >= 60 && $mins % 60 === 0 ? 'jam' : 'menit');
                        $defaultValue = old('duration_value', $defaultUnit === 'jam' ? intdiv($mins, 60) : $mins);
                    @endphp
                    <div style="display:grid;grid-template-columns:1fr 80px;gap:6px;">
                        <input id="duration_value" type="number" name="duration_value"
                               value="{{ $defaultValue }}"
                               min="1" max="1440"
                               class="m-input {{ $errors->has('duration_value') ? 'err' : '' }}" required />
                        <div class="select-wrap">
                            <select id="duration_unit" name="duration_unit" class="m-select" required>
                                <option value="menit" {{ $defaultUnit === 'menit' ? 'selected' : '' }}>Menit</option>
                                <option value="jam"   {{ $defaultUnit === 'jam'   ? 'selected' : '' }}>Jam</option>
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
                            // complete_mode: pre-select 'selesai' agar staff tinggal isi catatan
                            $defaultStatus = old('status', session('complete_mode') ? 'selesai' : $dailyTask->status);
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
                          required>{{ old('notes', $dailyTask->notes) }}</textarea>
                <small style="color:var(--fg-4);font-size:11px;">Minimal 5 karakter. Konteks task dibutuhkan untuk evaluasi KPI.</small>
                @error('notes')<span class="err">{{ $message }}</span>@enderror
            </div>

            @if($dailyTask->verification_status === 'revision')
            <div class="field" style="background:#FFF8E8;padding:12px;border:1px solid #FDE68A;border-radius:10px;">
                <label for="revision_response" style="color:#B45309;display:flex;align-items:center;gap:6px;">
                    <svg class="lucide" style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 15v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                    Balasan Revisi untuk Leader
                    <span style="color:var(--danger);">*</span>
                </label>
                <textarea id="revision_response" name="revision_response"
                          class="m-textarea {{ $errors->has('revision_response') ? 'err' : '' }}"
                          style="min-height:80px;border-color:#FBB041;background:#fff;"
                          placeholder="Tuliskan balasan atau konfirmasi bahwa revisi sudah dilakukan..."
                          required>{{ old('revision_response') }}</textarea>
                <small style="color:#8B5A00;font-size:11px;">Catatan ini akan muncul langsung di Activity Log saat membalas revisi leader.</small>
                @error('revision_response')<span class="err">{{ $message }}</span>@enderror
            </div>
            @endif

            <!-- Bukti Laporan Dinamis -->
            <div class="field">
                @php $isSales = auth()->user()->department === 'sales'; @endphp
                <label style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <svg class="lucide" style="width:14px;height:14px;color:var(--maxy-navy);" viewBox="0 0 24 24"><path d="M15.5 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V8.5L15.5 3z"/><polyline points="15 3 15 9 21 9"/></svg>
                        Bukti Laporan (Multi)
                        @if($isSales)
                            <span style="font-size:10px;color:var(--fg-3);font-weight:400;">(Wajib minimal 1 untuk Sales)</span>
                        @else
                            <span style="font-size:10px;color:var(--fg-3);font-weight:400;">(Opsional)</span>
                        @endif
                    </div>
                </label>

                <div id="evidences-container">
                    <!-- Dynamic rows will be added here -->
                </div>

                <button type="button" onclick="addEvidenceRow()" 
                        style="width:100%; border:1px dashed var(--maxy-navy); background:rgba(29,78,216,0.05); color:var(--maxy-navy); padding:10px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:500; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <svg class="lucide" style="width:16px;height:16px;" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Bukti Baru
                </button>
            </div>

            <div style="display:flex;gap:10px;">
                <a href="{{ route('daily-tasks.show', $dailyTask) }}"
                   class="btn btn-block"
                   style="flex:0 0 35%;background:var(--bg-2);color:var(--fg-2);text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary btn-block" style="flex:1;">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template Bukti Laporan -->
<template id="evidence-template">
    <div class="evidence-row" style="border: 1px solid var(--bg-3); border-radius: 8px; padding: 12px; margin-bottom: 12px; background: var(--bg-1); position:relative;">
        
        <div style="display:flex; gap: 12px; margin-bottom: 8px; padding-right: 50px;">
            <div style="flex:1;">
                <label style="font-size: 11px; color:var(--fg-3); font-weight:600;">Tipe Bukti</label>
                <select name="evidences[__INDEX__][type]" class="m-input evidence-type-select" onchange="changeEvidenceType(this)" style="padding: 6px; font-size:13px; height:auto;">
                    <option value="link">🔗 Link URL</option>
                    <option value="file">📄 Upload File (PDF/Image)</option>
                    <option value="image">🖼️ Screenshot (Ctrl+V)</option>
                </select>
            </div>
            <div style="flex:2;">
                <label style="font-size: 11px; color:var(--fg-3); font-weight:600;">Judul Bukti <span style="color:var(--danger)">*</span></label>
                <input type="text" name="evidences[__INDEX__][label]" class="m-input evidence-label-input" placeholder="Contoh: Draft Proposal" style="padding: 6px; font-size:13px; height:auto;" required>
            </div>
        </div>

        <div class="evidence-input-link">
            <div style="font-size:11px;color:var(--fg-3);margin-bottom:4px;">Bisa menambahkan lebih dari 1 link</div>
            <div class="link-list" style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;gap:8px;">
                    <input type="url" name="evidences[__INDEX__][path_or_url][]" class="m-input" placeholder="https://docs.google.com/..." style="font-size:13px;flex:1;" required>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addLinkField(this)" style="padding:0 12px;font-weight:bold;">+</button>
                </div>
            </div>
        </div>

        <div class="evidence-input-file" style="display: none;">
            <div style="font-size:11px;color:var(--fg-3);margin-bottom:4px;">Bisa pilih lebih dari 1 file sekaligus</div>
            <div class="custom-file-wrapper" style="border:1px dashed var(--bg-3);border-radius:8px;padding:12px;text-align:center;background:var(--bg-2);">
                <input type="file" name="evidences[__INDEX__][file][]" multiple class="m-input real-file-input" accept=".jpg,.jpeg,.png,.pdf" style="display:none;" disabled>
                <button type="button" class="btn btn-secondary btn-sm trigger-file-btn" style="background:#fff;border:1px solid #D1D5DB;color:#374151;font-size:12px;padding:4px 10px;">Pilih File...</button>
                <div class="file-list" style="margin-top:8px;text-align:left;display:flex;flex-direction:column;gap:4px;"></div>
            </div>
        </div>

        <div class="evidence-input-image" style="display: none;">
            <div style="font-size:11px;color:var(--fg-3);margin-bottom:4px;">Bisa paste lebih dari 1 screenshot secara berurutan</div>
            <div class="paste-zone" tabindex="0"
                 style="border:2px dashed var(--bg-3);border-radius:6px;padding:16px;text-align:center;color:var(--fg-4);font-size:12px;background:var(--bg-2);cursor:pointer;outline:none;transition:all 0.2s;"
                 title="Klik di sini lalu tekan Ctrl+V">
                <svg class="lucide" style="width:20px;height:20px;margin:0 auto 6px;display:block;" viewBox="0 0 24 24"><path d="M9 2h6v2H9zM4 6h16v16H4z"/></svg>
                Klik di sini, lalu tekan <kbd style="background:var(--bg-3);padding:1px 5px;border-radius:4px;font-size:11px;">Ctrl+V</kbd> untuk paste screenshot
            </div>
            <div class="clipboard-status" style="font-size:11px;color:var(--fg-3);margin-top:4px;text-align:center;"></div>
            
            <div class="clipboard-list" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;"></div>
        </div>
        
        <div style="display:flex; justify-content:flex-end; margin-top:12px; padding-top:12px; border-top:1px dashed var(--bg-3);">
            <button type="button" onclick="this.closest('.evidence-row').remove()" style="background: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; padding: 6px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size:12px; display:flex; align-items:center; gap:6px;">Hapus <span style="font-size:10px;">✖</span></button>
        </div>
    </div>
</template>

<script>
let evidenceCount = 0;

function addEvidenceRow(existingData = null) {
    const container = document.getElementById('evidences-container');
    const template = document.getElementById('evidence-template').innerHTML;
    const html = template.replace(/__INDEX__/g, evidenceCount);
    
    const div = document.createElement('div');
    div.innerHTML = html;
    const row = div.firstElementChild;
    container.appendChild(row);
    
    // Bind Custom File Logic
    const realInput = row.querySelector('.real-file-input');
    const triggerBtn = row.querySelector('.trigger-file-btn');
    const fileList = row.querySelector('.file-list');
    
    if (realInput && triggerBtn && fileList) {
        let dt = new DataTransfer();
        
        triggerBtn.addEventListener('click', () => {
            realInput.click();
        });
        
        realInput.addEventListener('change', (e) => {
            for (let file of e.target.files) {
                dt.items.add(file);
            }
            realInput.files = dt.files;
            renderFileList();
        });
        
        function renderFileList() {
            fileList.innerHTML = '';
            for (let i = 0; i < dt.files.length; i++) {
                const file = dt.files[i];
                const fileItem = document.createElement('div');
                fileItem.style = 'display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid var(--bg-3);padding:4px 8px;border-radius:6px;font-size:11px;';
                
                const nameSpan = document.createElement('span');
                nameSpan.style = 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;';
                nameSpan.textContent = '📄 ' + file.name;
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.style = 'color:var(--danger);background:transparent;border:none;cursor:pointer;font-weight:bold;margin-left:8px;';
                removeBtn.innerHTML = '✖';
                removeBtn.onclick = () => {
                    const newDt = new DataTransfer();
                    for(let j = 0; j < dt.files.length; j++) {
                        if (i !== j) newDt.items.add(dt.files[j]);
                    }
                    dt = newDt;
                    realInput.files = dt.files;
                    renderFileList();
                };
                
                fileItem.appendChild(nameSpan);
                fileItem.appendChild(removeBtn);
                fileList.appendChild(fileItem);
            }
        }
    }
    
    if (existingData) {
        // Add hidden ID field
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = `evidences[${evidenceCount}][id]`;
        idInput.value = existingData.id;
        row.appendChild(idInput);

        const select = row.querySelector('.evidence-type-select');
        select.value = existingData.type;
        
        // Lock type change for existing files to prevent complex UX states
        if (existingData.type !== 'link') {
            select.style.pointerEvents = 'none';
            select.style.opacity = '0.7';
        }

        row.querySelector('.evidence-label-input').value = existingData.label;

        changeEvidenceType(select);

        if (existingData.type === 'link') {
            row.querySelector('.evidence-input-link input').value = existingData.path_or_url;
        } else if (existingData.type === 'file') {
            const fileDiv = row.querySelector('.evidence-input-file');
            const filename = existingData.path_or_url.split('/').pop();
            fileDiv.innerHTML = `<div style="font-size:12px;color:var(--fg-3);margin-bottom:4px;">📄 File tersimpan: <a href="/storage/${existingData.path_or_url}" target="_blank" style="color:var(--maxy-navy);font-weight:600;">${filename}</a></div>
                                 <input type="hidden" name="evidences[${evidenceCount}][path_or_url]" value="${existingData.path_or_url}">
                                 <div style="font-size:11px;color:var(--fg-4);">(Hapus baris ini untuk mengganti file)</div>`;
        } else if (existingData.type === 'image') {
            const imgDiv = row.querySelector('.evidence-input-image');
            imgDiv.querySelector('.paste-zone').style.display = 'none';
            const list = imgDiv.querySelector('.clipboard-list');
            const div = document.createElement('div');
            div.style = 'position:relative;border:1px solid var(--bg-3);border-radius:8px;padding:4px;background:var(--bg-1);';
            div.innerHTML = `
                <input type="hidden" name="evidences[${evidenceCount}][path_or_url][]" value="${existingData.path_or_url}">
                <img src="/storage/${existingData.path_or_url}" style="width:100%;max-height:100px;object-fit:cover;border-radius:4px;">
            `;
            list.appendChild(div);
        }
    }

    evidenceCount++;
}

function addLinkField(btn) {
    const list = btn.closest('.link-list');
    const name = list.querySelector('input').name;
    const div = document.createElement('div');
    div.style = 'display:flex;gap:8px;';
    div.innerHTML = `
        <input type="url" name="${name}" class="m-input" placeholder="https://..." style="font-size:13px;flex:1;" required>
        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()" style="background:#FEE2E2;color:#B91C1C;border:1px solid #FCA5A5;padding:0 12px;font-weight:bold;">✖</button>
    `;
    list.appendChild(div);
}

function changeEvidenceType(select) {
    const row = select.closest('.evidence-row');
    const type = select.value;

    const linkDiv = row.querySelector('.evidence-input-link');
    const fileDiv = row.querySelector('.evidence-input-file');
    const imgDiv = row.querySelector('.evidence-input-image');

    linkDiv.style.display = type === 'link' ? 'block' : 'none';
    fileDiv.style.display = type === 'file' ? 'block' : 'none';
    imgDiv.style.display = type === 'image' ? 'block' : 'none';

    // Disable inputs that are not active so they don't get submitted
    const linkInputs = linkDiv.querySelectorAll('input');
    const fileInput = fileDiv.querySelector('input');
    const imgInputs  = imgDiv.querySelectorAll('input');

    linkInputs.forEach(inp => inp.disabled = type !== 'link');
    if (fileInput) fileInput.disabled = type !== 'file';
    imgInputs.forEach(inp => inp.disabled = type !== 'image');

    linkInputs.forEach(inp => inp.required = type === 'link');
    if (fileInput) fileInput.required = type === 'file';
}

function clearRowImage(btn) {
    const row = btn.closest('.evidence-row');
    row.querySelector('.clipboard-img').src = '';
    row.querySelector('.clipboard-preview').style.display = 'none';
    row.querySelector('.image-path-input').value = '';
}

(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Load existing evidences
    const existingEvidences = @json($dailyTask->evidences);
    if (existingEvidences && existingEvidences.length > 0) {
        existingEvidences.forEach(ev => addEvidenceRow(ev));
    } else {
        @if(auth()->user()->department === 'sales')
            addEvidenceRow();
        @endif
    }

    document.addEventListener('focusin', function(e) {
        if (e.target.classList.contains('paste-zone')) {
            e.target.style.borderColor = 'var(--maxy-navy)';
            e.target.style.background = 'rgba(29,78,216,0.05)';
        }
    });
    document.addEventListener('focusout', function(e) {
        if (e.target.classList.contains('paste-zone')) {
            e.target.style.borderColor = 'var(--bg-3)';
            e.target.style.background = 'var(--bg-2)';
        }
    });

    document.addEventListener('paste', function (e) {
        const active = document.activeElement;
        if (!active || !active.classList.contains('paste-zone')) return;

        const row = active.closest('.evidence-row');
        const items = e.clipboardData?.items;
        if (!items) return;

        for (const item of items) {
            if (!item.type.startsWith('image/')) continue;
            const blob = item.getAsFile();
            if (!blob) continue;

            const reader = new FileReader();
            reader.onload = async function (ev) {
                const dataUrl = ev.target.result;
                const status = row.querySelector('.clipboard-status');
                const list = row.querySelector('.clipboard-list');
                const selectElement = row.querySelector('select[name^="evidences"]');
                const index = selectElement ? selectElement.name.match(/\d+/)[0] : '0';
                const inputName = `evidences[${index}][path_or_url][]`;

                status.textContent = 'Menyimpan ke server…';
                status.style.color = 'var(--fg-3)';

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
                        status.textContent = '✅ Gambar berhasil disimpan.';
                        status.style.color = '#16A571';
                        
                        const div = document.createElement('div');
                        div.style = 'position:relative;border:1px solid var(--bg-3);border-radius:8px;padding:4px;background:var(--bg-1);';
                        div.innerHTML = `
                            <input type="hidden" name="${inputName}" value="${data.path}">
                            <img src="${dataUrl}" style="width:100%;max-height:100px;object-fit:cover;border-radius:4px;">
                            <button type="button" onclick="this.parentElement.remove()" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.55);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">×</button>
                        `;
                        list.appendChild(div);
                        
                        setTimeout(() => status.textContent = '', 3000);
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
            break; 
        }
    });
})();
</script>
