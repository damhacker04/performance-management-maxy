<x-app-layout>
@php
    $avatarColors = ['#1B4FD8','#6D28D9','#0E7490','#065F46','#9A3412','#1D4ED8','#7C3AED','#047857'];
@endphp

<style>
.person-accordion {
    border: 1.5px solid var(--neutral-200);
    border-radius: var(--r-lg);
    overflow: hidden;
    background: #fff;
    box-shadow: var(--shadow-sm);
}
.person-accordion + .person-accordion { margin-top: 10px; }

.person-header {
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    transition: background var(--dur-base);
}
.person-header:hover { background: var(--neutral-50); }

.person-body {
    overflow: hidden;
    transition: max-height 0.3s ease;
    padding: 0 16px 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-top: 1px solid var(--neutral-100);
}
.person-body.collapsed { max-height: 0 !important; padding-top: 0; padding-bottom: 0; border-top: none; }

.kpi-item-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1.5px solid var(--neutral-200);
    border-radius: var(--r-md);
    background: var(--neutral-50);
    transition: border-color var(--dur-base);
}
.kpi-item-row:hover { border-color: var(--maxy-navy); }
</style>

<div class="page">

    {{-- Header --}}
    <div>
        <h1 style="font-size:20px;font-weight:800;color:var(--maxy-navy);margin:0;letter-spacing:-0.02em;">KPI Organisasi</h1>
        <p style="font-size:13px;color:var(--fg-3);margin:4px 0 0;">Target KPI individual per staf</p>
    </div>

    @forelse ($groupedStaffs as $deptKey => $staffs)

        <div class="overline-label" style="margin-bottom:-6px;">
            {{ \App\Models\User::DEPARTMENTS[$deptKey] ?? ucfirst(str_replace('_', ' ', $deptKey)) }}
        </div>

        <div>
            @foreach ($staffs as $staff)
                @php
                    $targetCount = $staff->kpiTargets->count();
                    $initials    = collect(explode(' ', $staff->name))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
                    $colorIdx    = abs(crc32($staff->name) % count($avatarColors));
                    $bgColor     = $avatarColors[$colorIdx];
                    $accordionId = 'acc-' . $staff->id;
                    $bodyId      = 'body-' . $staff->id;
                    $chevronId   = 'chev-' . $staff->id;
                @endphp

                <div class="person-accordion" id="{{ $accordionId }}">
                    {{-- Header --}}
                    <div class="person-header" onclick="toggleAccordion('{{ $bodyId }}', '{{ $chevronId }}')">
                        {{-- Avatar --}}
                        <div style="width:38px;height:38px;border-radius:10px;background:{{ $bgColor }};
                                    color:#fff;font-size:13px;font-weight:700;
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            {{ $initials }}
                        </div>

                        {{-- Info --}}
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <span style="font-size:15px;font-weight:700;color:var(--fg-1);">{{ $staff->name }}</span>
                                @if($targetCount === 0)
                                    <span class="chip chip-neutral" style="font-size:10px;">Belum diset</span>
                                @else
                                    <span class="chip chip-success" style="font-size:10px;">{{ $targetCount }} KPI</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:var(--fg-4);margin-top:2px;">
                                {{ $targetCount === 0 ? 'Belum ada KPI yang ditetapkan' : 'Tap untuk lihat detail' }}
                            </div>
                        </div>

                        {{-- Chevron --}}
                        <svg id="{{ $chevronId }}" class="lucide sm" style="color:var(--fg-4);transition:transform 0.25s;flex-shrink:0;" viewBox="0 0 24 24">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </div>

                    {{-- Body (collapsed by default) --}}
                    <div class="person-body collapsed" id="{{ $bodyId }}" style="max-height:0;">
                        @if($targetCount > 0)
                            @foreach($staff->kpiTargets as $kpi)
                                <div class="kpi-item-row" style="@if($loop->first) margin-top:12px; @endif">
                                    {{-- Icon --}}
                                    <div style="width:36px;height:36px;border-radius:9px;background:var(--info-100);
                                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg class="lucide sm" style="color:var(--info);" viewBox="0 0 24 24">
                                            <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                                        </svg>
                                    </div>
                                    {{-- Info --}}
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:14px;font-weight:600;color:var(--fg-1);margin-bottom:2px;">
                                            {{ $kpi->kpi_name }}
                                        </div>
                                        <div style="font-size:12px;color:var(--fg-3);">
                                            Target: <span style="font-weight:700;color:var(--maxy-navy);">{{ number_format($kpi->target_value, 0, ',', '.') }} {{ $kpi->unit }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="text-align:center;padding:20px;color:var(--fg-4);font-size:13px;margin-top:4px;">
                                Belum ada KPI yang di-assign.
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    @empty
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--fg-1);margin:0 0 4px;">Tidak Ada Staf</p>
                <p style="font-size:12px;color:var(--fg-3);margin:0;">Belum ada staf yang terdaftar.</p>
            </div>
        </div>
    @endforelse

</div>

<script>
function toggleAccordion(bodyId, chevronId) {
    const body    = document.getElementById(bodyId);
    const chevron = document.getElementById(chevronId);
    const isOpen  = !body.classList.contains('collapsed');

    if (isOpen) {
        body.style.maxHeight = body.scrollHeight + 'px';
        requestAnimationFrame(() => {
            body.style.maxHeight = '0';
            setTimeout(() => body.classList.add('collapsed'), 280);
        });
        chevron.style.transform = 'rotate(0deg)';
    } else {
        body.classList.remove('collapsed');
        body.style.maxHeight = body.scrollHeight + 'px';
        chevron.style.transform = 'rotate(90deg)';
        setTimeout(() => body.style.maxHeight = 'none', 310);
    }
}
</script>
</x-app-layout>
