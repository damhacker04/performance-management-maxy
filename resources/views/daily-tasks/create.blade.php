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

                <!-- Tanggal -->
                <div class="field">
                    <label for="task_date">Tanggal Tugas <span style="color:var(--danger);">*</span></label>
                    <input id="task_date" type="date" name="task_date"
                           value="{{ old('task_date', now()->toDateString()) }}"
                           max="{{ now()->toDateString() }}"
                           class="m-input {{ $errors->has('task_date') ? 'err' : '' }}" required />
                    @error('task_date')<span class="err">{{ $message }}</span>@enderror
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
                        <label for="duration_minutes">Durasi (menit) <span style="color:var(--danger);">*</span></label>
                        <input id="duration_minutes" type="number" name="duration_minutes"
                               value="{{ old('duration_minutes', 60) }}"
                               min="1" max="1440"
                               class="m-input {{ $errors->has('duration_minutes') ? 'err' : '' }}" required />
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
                    <label for="notes">Catatan <span style="color:var(--fg-4);font-weight:400;">(opsional)</span></label>
                    <textarea id="notes" name="notes"
                              class="m-textarea"
                              style="min-height:72px;"
                              placeholder="Ada hambatan? Catatan tambahan?">{{ old('notes') }}</textarea>
                    @error('notes')<span class="err">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                    Kirim Laporan
                </button>
            </form>
        </div>
    @endif
</div>
</x-app-layout>
