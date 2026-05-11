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
                                <span>· <strong style="color:var(--maxy-navy);">{{ $task->percent_done }}%</strong></span>
                                @if($task->weeklyTarget)
                                    <span>· M{{ $task->weeklyTarget->week_number }} {{ Str::limit($task->weeklyTarget->title, 16) }}</span>
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
                  style="display:flex;flex-direction:column;gap:16px;">
                @csrf

                {{-- Banner ketika user pilih "lanjutkan dari kemarin" --}}
                @if($continueFrom)
                    <div style="background:#E8F0FE;border:1px solid var(--maxy-navy);border-radius:10px;padding:12px;display:flex;gap:10px;align-items:flex-start;">
                        <svg class="lucide sm" style="color:var(--maxy-navy);flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9M3 12V3m0 9h9"/></svg>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;color:var(--maxy-navy);font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Melanjutkan task</div>
                            <div style="font-size:13px;font-weight:600;color:var(--fg-1);margin-top:2px;line-height:1.4;">{{ Str::limit($continueFrom->task_description, 80) }}</div>
                            <div style="font-size:11px;color:var(--fg-3);margin-top:3px;">
                                {{ \Carbon\Carbon::parse($continueFrom->task_date)->isoFormat('D MMM') }} · {{ $continueFrom->status_label }} · {{ $continueFrom->percent_done }}%
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
                                class="m-select {{ $errors->has('weekly_target_id') ? 'err' : '' }}" required>
                            <option value="">Pilih target mingguan...</option>
                            @php
                                $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                                $defaultWeekly = old('weekly_target_id', $continueFrom?->weekly_target_id);
                            @endphp
                            @foreach($weeklyTargets as $wt)
                                <option value="{{ $wt->id }}" {{ $defaultWeekly == $wt->id ? 'selected' : '' }}>
                                    [M{{ $wt->week_number }} {{ $monthShort[$wt->month] }}] {{ $wt->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('weekly_target_id')<span class="err">{{ $message }}</span>@enderror
                    <span style="font-size:11px;color:var(--fg-4);margin-top:4px;">Pilih minggu yang sedang kamu kerjakan.</span>
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
                                <option value="critical" {{ $defaultPriority === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                                <option value="high"     {{ $defaultPriority === 'high'     ? 'selected' : '' }}>🟠 High</option>
                                <option value="medium"   {{ $defaultPriority === 'medium'   ? 'selected' : '' }}>🟡 Medium</option>
                                <option value="low"      {{ $defaultPriority === 'low'      ? 'selected' : '' }}>🔵 Low</option>
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

                <!-- Status & % Done -->
                <div id="status_percent_wrap" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
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
                    <div class="field" id="percent_done_wrap">
                        @php $defaultPercent = old('percent_done', $continueFrom?->percent_done ?? 0); @endphp
                        <label for="percent_done">
                            % Selesai
                            <span id="percent_value" style="color:var(--maxy-navy);font-weight:700;">{{ $defaultPercent }}%</span>
                        </label>
                        <input id="percent_done" type="range" name="percent_done"
                               value="{{ $defaultPercent }}"
                               min="0" max="100" step="5"
                               style="width:100%;accent-color:var(--maxy-navy);" />
                        @error('percent_done')<span class="err">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Catatan -->
                <div class="field">
                    <label for="notes">
                        Catatan
                        <span id="notes_label_optional" style="color:var(--fg-4);font-weight:400;">(opsional)</span>
                        <span id="notes_label_required" style="color:var(--danger);display:none;">* (wajib jika Terhambat)</span>
                    </label>
                    <textarea id="notes" name="notes"
                              class="m-textarea {{ $errors->has('notes') ? 'err' : '' }}"
                              style="min-height:72px;"
                              placeholder="Ada hambatan? Catatan tambahan?">{{ old('notes') }}</textarea>
                    @error('notes')<span class="err">{{ $message }}</span>@enderror
                </div>

                <script>
                    (function () {
                        const statusEl    = document.getElementById('status');
                        const notesEl     = document.getElementById('notes');
                        const optLabel    = document.getElementById('notes_label_optional');
                        const reqLabel    = document.getElementById('notes_label_required');
                        const percentEl   = document.getElementById('percent_done');
                        const percentDisp = document.getElementById('percent_value');
                        const percentWrap = document.getElementById('percent_done_wrap');
                        const gridWrap    = document.getElementById('status_percent_wrap');

                        function syncNotesRequired() {
                            const isBlocked = statusEl.value === 'terhambat';
                            notesEl.required = isBlocked;
                            optLabel.style.display = isBlocked ? 'none' : '';
                            reqLabel.style.display = isBlocked ? '' : 'none';
                            notesEl.placeholder = isBlocked
                                ? 'Wajib diisi: jelaskan hambatan yang dialami…'
                                : 'Ada hambatan? Catatan tambahan?';
                        }

                        function syncPercentByStatus() {
                            // Auto-set percent berdasarkan status (helper, bukan hard rule)
                            if (statusEl.value === 'belum_mulai') percentEl.value = 0;
                            if (statusEl.value === 'selesai')     percentEl.value = 100;
                            percentDisp.textContent = percentEl.value + '%';
                        }

                        function syncPercentVisibility() {
                            // Slider hanya muncul jika status butuh progress partial
                            const showSlider = ['dalam_proses', 'terhambat'].includes(statusEl.value);
                            percentWrap.style.display = showSlider ? '' : 'none';
                            gridWrap.style.gridTemplateColumns = showSlider ? '1fr 1fr' : '1fr';
                        }

                        statusEl.addEventListener('change', () => {
                            syncNotesRequired();
                            syncPercentByStatus();
                            syncPercentVisibility();
                        });
                        percentEl.addEventListener('input', () => {
                            percentDisp.textContent = percentEl.value + '%';
                        });

                        syncNotesRequired();
                        syncPercentVisibility();
                    })();
                </script>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                    Kirim Laporan
                </button>
            </form>
        </div>
    @endif
</div>
</x-app-layout>
