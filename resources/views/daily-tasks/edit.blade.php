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

                {{-- Preview bukti yang sudah ada --}}
                @if($dailyTask->proof_url || $dailyTask->proof_file)
                    <div style="background:#F0F7FF;border:1px solid #BFDBFE;border-radius:8px;padding:8px 10px;margin-bottom:8px;font-size:12px;">
                        <div style="font-weight:600;color:var(--maxy-navy);margin-bottom:4px;">📎 Bukti terlampir sebelumnya:</div>
                        @if($dailyTask->proof_url)
                            <div style="margin-bottom:2px;">
                                🔗 <a href="{{ $dailyTask->proof_url }}" target="_blank"
                                      style="color:var(--maxy-navy);word-break:break-all;">{{ Str::limit($dailyTask->proof_url, 60) }}</a>
                            </div>
                        @endif
                        @if($dailyTask->proof_file)
                            <div>
                                📄 <a href="{{ Storage::url($dailyTask->proof_file) }}" target="_blank"
                                      style="color:var(--maxy-navy);">{{ basename($dailyTask->proof_file) }}</a>
                                <span style="color:var(--fg-4);font-size:10px;"> — Upload file baru di bawah untuk mengganti</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Link URL bukti --}}
                <div style="margin-bottom:8px;">
                    <div style="font-size:11px;color:var(--fg-3);margin-bottom:4px;font-weight:600;">Link Bukti (Google Sheets, CRM, Drive, dll.)</div>
                    <input type="url" id="proof_url" name="proof_url"
                           class="m-input {{ $errors->has('proof_url') ? 'err' : '' }}"
                           value="{{ old('proof_url', $dailyTask->proof_url) }}"
                           placeholder="https://docs.google.com/…">
                    @error('proof_url')<span class="err">{{ $message }}</span>@enderror
                </div>

                {{-- Divider --}}
                <div style="display:flex;align-items:center;gap:8px;color:var(--fg-4);font-size:11px;margin-bottom:8px;">
                    <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                    atau upload file baru
                    <div style="flex:1;height:1px;background:var(--bg-3);"></div>
                </div>

                {{-- Upload file --}}
                <label for="proof_file"
                       style="display:flex;align-items:center;justify-content:center;gap:8px;
                              border:2px dashed var(--bg-3);border-radius:10px;padding:14px;
                              cursor:pointer;color:var(--fg-3);font-size:13px;
                              background:var(--bg-2);transition:border-color .2s;">
                    <svg class="lucide" style="width:16px;height:16px;flex-shrink:0;" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span id="edit_proof_file_text">{{ $dailyTask->proof_file ? 'Ganti: '.basename($dailyTask->proof_file) : 'JPG, PNG, atau PDF — maks. 5MB' }}</span>
                </label>
                <input type="file" id="proof_file" name="proof_file"
                       accept=".jpg,.jpeg,.png,.pdf"
                       class="{{ $errors->has('proof_file') ? 'err' : '' }}"
                       style="display:none;"
                       onchange="document.getElementById('edit_proof_file_text').textContent = this.files[0]?.name ?? 'JPG, PNG, atau PDF — maks. 5MB';">
                @error('proof_file')<span class="err">{{ $message }}</span>@enderror
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
</x-app-layout>
