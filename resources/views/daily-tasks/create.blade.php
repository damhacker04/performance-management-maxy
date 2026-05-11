<x-app-layout>

<div class="page">
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('daily-tasks.index') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;">Laporan Harian</h1>
    </div>

    @if($targets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--maxy-amber);" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Belum ada target bulanan</p>
                <p style="font-size:13px;color:var(--fg-3);">Leader departemenmu belum membuat target bulan ini.</p>
            </div>
        </div>
    @else
        <div class="m-card">
            <form method="POST" action="{{ route('daily-tasks.store') }}"
                  style="display:flex;flex-direction:column;gap:16px;">
                @csrf

                <!-- Target -->
                <div class="field">
                    <label for="monthly_target_id">Target Bulanan <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="monthly_target_id" name="monthly_target_id"
                                class="m-select {{ $errors->has('monthly_target_id') ? 'err' : '' }}" required>
                            <option value="">Pilih target...</option>
                            @php $monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; @endphp
                            @foreach($targets as $target)
                                <option value="{{ $target->id }}" {{ old('monthly_target_id') == $target->id ? 'selected' : '' }}>
                                    [{{ $monthNames[$target->month] }} {{ $target->year }}] {{ $target->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('monthly_target_id')<span class="err">{{ $message }}</span>@enderror
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

                <!-- Durasi & Status -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label for="duration_value">Durasi <span style="color:var(--danger);">*</span></label>
                        <div style="display:grid;grid-template-columns:1fr 90px;gap:8px;">
                            <input id="duration_value" type="number" name="duration_value"
                                   value="{{ old('duration_value', 60) }}"
                                   min="1" max="1440"
                                   placeholder="0"
                                   class="m-input {{ $errors->has('duration_value') || $errors->has('duration_minutes') ? 'err' : '' }}" required />
                            <div class="select-wrap">
                                <select id="duration_unit" name="duration_unit" class="m-select" required>
                                    <option value="menit" {{ old('duration_unit','menit') === 'menit' ? 'selected' : '' }}>Menit</option>
                                    <option value="jam"   {{ old('duration_unit') === 'jam'           ? 'selected' : '' }}>Jam</option>
                                </select>
                            </div>
                        </div>
                        @error('duration_value')<span class="err">{{ $message }}</span>@enderror
                        @error('duration_minutes')<span class="err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="status">Status <span style="color:var(--danger);">*</span></label>
                        <div class="select-wrap">
                            <select id="status" name="status"
                                    class="m-select {{ $errors->has('status') ? 'err' : '' }}" required>
                                <option value="selesai"      {{ old('status') === 'selesai'      ? 'selected' : '' }}>Selesai</option>
                                <option value="dalam_proses" {{ old('status','dalam_proses') === 'dalam_proses' ? 'selected' : '' }}>Dalam Proses</option>
                                <option value="terhambat"    {{ old('status') === 'terhambat'    ? 'selected' : '' }}>Terhambat</option>
                            </select>
                        </div>
                        @error('status')<span class="err">{{ $message }}</span>@enderror
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
                        const statusEl   = document.getElementById('status');
                        const notesEl    = document.getElementById('notes');
                        const optLabel   = document.getElementById('notes_label_optional');
                        const reqLabel   = document.getElementById('notes_label_required');

                        function syncNotesRequired() {
                            const isBlocked = statusEl.value === 'terhambat';
                            notesEl.required = isBlocked;
                            optLabel.style.display = isBlocked ? 'none' : '';
                            reqLabel.style.display = isBlocked ? '' : 'none';
                            notesEl.placeholder = isBlocked
                                ? 'Wajib diisi: jelaskan hambatan yang dialami…'
                                : 'Ada hambatan? Catatan tambahan?';
                        }

                        statusEl.addEventListener('change', syncNotesRequired);
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
