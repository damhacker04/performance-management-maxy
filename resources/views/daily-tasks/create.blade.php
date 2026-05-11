<x-app-layout>

<div class="page">
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('daily-tasks.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Laporan Harian</h1>
    </div>

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

                <!-- Weekly Target -->
                <div class="field">
                    <label for="weekly_target_id">Target Mingguan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="weekly_target_id" name="weekly_target_id"
                                class="m-select {{ $errors->has('weekly_target_id') ? 'err' : '' }}" required>
                            <option value="">Pilih target mingguan...</option>
                            @php $monthShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; @endphp
                            @foreach($weeklyTargets as $wt)
                                <option value="{{ $wt->id }}" {{ old('weekly_target_id') == $wt->id ? 'selected' : '' }}>
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
                    <textarea id="task_description" name="task_description"
                              class="m-textarea {{ $errors->has('task_description') ? 'err' : '' }}"
                              placeholder="Apa yang kamu kerjakan hari ini?" required>{{ old('task_description') }}</textarea>
                    @error('task_description')<span class="err">{{ $message }}</span>@enderror
                </div>

                <!-- Prioritas & Durasi -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label for="priority">Prioritas <span style="color:var(--danger);">*</span></label>
                        <div class="select-wrap">
                            <select id="priority" name="priority"
                                    class="m-select {{ $errors->has('priority') ? 'err' : '' }}" required>
                                <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                                <option value="high"     {{ old('priority') === 'high'     ? 'selected' : '' }}>🟠 High</option>
                                <option value="medium"   {{ old('priority','medium') === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                                <option value="low"      {{ old('priority') === 'low'      ? 'selected' : '' }}>🔵 Low</option>
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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label for="status">Status <span style="color:var(--danger);">*</span></label>
                        <div class="select-wrap">
                            <select id="status" name="status"
                                    class="m-select {{ $errors->has('status') ? 'err' : '' }}" required>
                                <option value="belum_mulai"  {{ old('status','belum_mulai') === 'belum_mulai' ? 'selected' : '' }}>Belum Mulai</option>
                                <option value="dalam_proses" {{ old('status') === 'dalam_proses' ? 'selected' : '' }}>Dalam Proses</option>
                                <option value="terhambat"    {{ old('status') === 'terhambat'    ? 'selected' : '' }}>Terhambat</option>
                                <option value="selesai"      {{ old('status') === 'selesai'      ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </div>
                        @error('status')<span class="err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="percent_done">
                            % Selesai
                            <span id="percent_value" style="color:var(--maxy-navy);font-weight:700;">{{ old('percent_done', 0) }}%</span>
                        </label>
                        <input id="percent_done" type="range" name="percent_done"
                               value="{{ old('percent_done', 0) }}"
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

                        statusEl.addEventListener('change', () => {
                            syncNotesRequired();
                            syncPercentByStatus();
                        });
                        percentEl.addEventListener('input', () => {
                            percentDisp.textContent = percentEl.value + '%';
                        });

                        syncNotesRequired();
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
