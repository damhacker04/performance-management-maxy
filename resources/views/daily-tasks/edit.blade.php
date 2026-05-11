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

    {{-- Info banner: kondisi edit --}}
    <div style="background:#FFF8E8;border:1px solid #FBB041;border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;font-size:11px;color:#8B5A00;">
        <svg class="lucide" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div>
            <strong>Mode edit</strong> — kamu masih bisa update laporan ini sampai akhir hari atau sampai kamu tandai selesai. Setelahnya, laporan akan jadi history.
        </div>
    </div>

    <div class="m-card">
        <form method="POST" action="{{ route('daily-tasks.update', $dailyTask) }}"
              style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PATCH')

            <!-- Weekly Target -->
            <div class="field">
                <label for="weekly_target_id">Target Mingguan <span style="color:var(--danger);">*</span></label>
                <div class="select-wrap">
                    <select id="weekly_target_id" name="weekly_target_id"
                            class="m-select {{ $errors->has('weekly_target_id') ? 'err' : '' }}" required>
                        <option value="">Pilih target mingguan...</option>
                        @php
                            $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                            $selectedWeekly = old('weekly_target_id', $dailyTask->weekly_target_id);
                        @endphp
                        @foreach($weeklyTargets as $wt)
                            <option value="{{ $wt->id }}" {{ $selectedWeekly == $wt->id ? 'selected' : '' }}>
                                [M{{ $wt->week_number }} {{ $monthShort[$wt->month] }}] {{ $wt->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
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

            <!-- Status & % Done -->
            <div id="status_percent_wrap" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field">
                    <label for="status">Status <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="status" name="status"
                                class="m-select {{ $errors->has('status') ? 'err' : '' }}" required>
                            @php $defaultStatus = old('status', $dailyTask->status); @endphp
                            <option value="belum_mulai"  {{ $defaultStatus === 'belum_mulai' ? 'selected' : '' }}>Belum Mulai</option>
                            <option value="dalam_proses" {{ $defaultStatus === 'dalam_proses' ? 'selected' : '' }}>Dalam Proses</option>
                            <option value="terhambat"    {{ $defaultStatus === 'terhambat'    ? 'selected' : '' }}>Terhambat</option>
                            <option value="selesai"      {{ $defaultStatus === 'selesai'      ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    @error('status')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" id="percent_done_wrap">
                    @php $defaultPercent = old('percent_done', $dailyTask->percent_done); @endphp
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
                          placeholder="Ada hambatan? Catatan tambahan?">{{ old('notes', $dailyTask->notes) }}</textarea>
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
                        if (statusEl.value === 'belum_mulai') percentEl.value = 0;
                        if (statusEl.value === 'selesai')     percentEl.value = 100;
                        percentDisp.textContent = percentEl.value + '%';
                    }

                    function syncPercentVisibility() {
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
</x-app-layout>
