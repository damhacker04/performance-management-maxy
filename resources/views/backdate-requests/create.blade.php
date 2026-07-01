<x-app-layout>
<div class="page">
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="{{ route('daily-tasks.create') }}" class="icon-btn" style="margin-left:-8px;">
            <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 style="font-size:18px;font-weight:700;color:var(--fg-1);margin:0;line-height:1.2;">Ajukan Izin Backdating</h1>
            <p style="font-size:12px;color:var(--fg-3);margin:2px 0 0;">Minta izin untuk mengisi laporan hari sebelumnya</p>
        </div>
    </div>

    {{-- Info box --}}
    <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;">
        <svg class="lucide" style="width:16px;height:16px;flex-shrink:0;color:#1D4ED8;margin-top:1px;" viewBox="0 0 24 24"><path d="M12 16v-4M12 8h.01M22 12a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
        <div style="font-size:12px;color:#1E3A8A;line-height:1.6;">
            <strong>Cara kerja:</strong> Setelah mengajukan, leader departemenmu akan mendapat notifikasi dan bisa menyetujui atau menolak permintaan ini.
            Jika disetujui, kamu punya <strong>24 jam</strong> untuk mengisi laporannya. Backdating hanya bisa diajukan maksimal <strong>3 hari ke belakang</strong>.
        </div>
    </div>

    @if(count($availableDates) === 0)
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-3);" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:4px;">Tidak ada tanggal yang tersedia</p>
                <p style="font-size:13px;color:var(--fg-3);">Semua permintaan backdating 3 hari terakhir sudah kamu ajukan atau sudah disetujui.</p>
            </div>
        </div>
    @else
        <div class="m-card">
            <form method="POST" action="{{ route('backdate-requests.store') }}"
                  style="display:flex;flex-direction:column;gap:16px;">
                @csrf

                {{-- Pilih Tanggal --}}
                <div class="field">
                    <label for="requested_date">Tanggal yang Ingin Diisi <span style="color:var(--danger);">*</span></label>
                    <div class="select-wrap">
                        <select id="requested_date" name="requested_date"
                                class="m-select {{ $errors->has('requested_date') ? 'err' : '' }}" required>
                            <option value="" disabled selected>Pilih tanggal...</option>
                            @foreach($availableDates as $date)
                                <option value="{{ $date->toDateString() }}"
                                    {{ old('requested_date') === $date->toDateString() ? 'selected' : '' }}>
                                    {{ $date->isoFormat('dddd, D MMMM YYYY') }}
                                    @if($date->isYesterday()) (kemarin) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('requested_date')<span class="err">{{ $message }}</span>@enderror
                </div>

                {{-- Alasan --}}
                <div class="field">
                    <label for="reason">Alasan Pengajuan <span style="color:var(--danger);">*</span></label>
                    <textarea id="reason" name="reason"
                              class="m-textarea {{ $errors->has('reason') ? 'err' : '' }}"
                              style="min-height:96px;"
                              placeholder="Contoh: Kemarin saya masih mengerjakan pekerjaan sampai dini hari dan tidak sempat mengisi laporan..."
                              minlength="10"
                              required>{{ old('reason') }}</textarea>
                    <small style="color:var(--fg-3);font-size:11px;">Minimal 10 karakter. Jelaskan dengan jelas agar leader bisa mempertimbangkan.</small>
                    @error('reason')<span class="err">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <svg class="lucide sm" viewBox="0 0 24 24" style="margin-right:4px;"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    Kirim Permintaan ke Leader
                </button>
            </form>
        </div>
    @endif
</div>
</x-app-layout>
