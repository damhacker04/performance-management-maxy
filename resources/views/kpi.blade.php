<x-app-layout>
<div class="page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">KPI Organisasi</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
                Target KPI individual
            </p>
        </div>
        @if(auth()->user()->role === 'c_level')
            {{-- Placeholder untuk tombol tambah KPI bagi C-Level nanti --}}
            <!-- <button class="btn btn-primary">Tambah KPI</button> -->
        @endif
    </div>

    @forelse ($groupedStaffs as $deptKey => $staffs)
        <div class="overline-label" style="margin:20px 0 10px;">
            {{ \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_', ' ', $deptKey)) }}
        </div>
        
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach ($staffs as $staff)
                @php
                    $targetCount = $staff->kpiTargets->count();
                @endphp
                
                {{-- Card per staff --}}
                <div class="m-card" style="padding:0; overflow:hidden;" x-data="{ expanded: false }">
                    {{-- Header Card --}}
                    <div @click="expanded = !expanded" style="padding:16px; display:flex; justify-content:space-between; align-items:center; cursor:pointer; background:var(--bg-card); transition:background 0.2s;" :style="expanded ? 'background:var(--bg-body)' : ''">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="{{ $staff->avatar ? asset('storage/'.$staff->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($staff->name).'&background=E5EEFF&color=1D4ED8' }}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                            <div>
                                <div style="font-size:15px; font-weight:600; color:var(--fg-1);">{{ $staff->name }}</div>
                                <div style="font-size:12px; color:var(--fg-3);">
                                    {{ $targetCount }} KPI aktif
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            @if($targetCount == 0)
                                <span class="chip chip-neutral" style="font-size:10px;">Belum diset</span>
                            @endif
                            <svg class="lucide chevron-icon" :style="expanded ? 'transform:rotate(180deg);' : ''" style="transition:transform 0.2s;" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    </div>

                    {{-- Expanded Content --}}
                    <div x-show="expanded" x-collapse style="display:none; border-top:1px solid var(--border-color);">
                        <div style="padding:16px;">
                            @if($targetCount > 0)
                                <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                                    @foreach($staff->kpiTargets as $kpi)
                                        <div style="padding:12px; border:1px solid var(--border-color); border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                                            <div>
                                                <div style="font-size:14px; font-weight:600; color:var(--fg-1);">{{ $kpi->kpi_name }}</div>
                                                <div style="font-size:12px; color:var(--fg-3);">
                                                    Target: {{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}
                                                </div>
                                            </div>
                                            {{-- Nanti tombol Edit/Delete untuk C-Level --}}
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div style="text-align:center; padding:20px; color:var(--fg-4); font-size:13px;">
                                    Belum ada KPI yang di-assign untuk staff ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="m-card" style="text-align:center; padding:40px 20px;">
            <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <div style="font-size:15px;font-weight:600;color:var(--fg-1);">Tidak Ada Staf</div>
            <p style="font-size:13px;color:var(--fg-3);margin:4px 0 0;">Belum ada staf yang terdaftar.</p>
        </div>
    @endforelse
</div>
</x-app-layout>
