<x-app-layout>
@php
    $months = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $deptColors = ['sales'=>'#2F6BD6','marketing'=>'#B43BB7','product_it'=>'#16A571','operational'=>'#E89B2A','ceo_office'=>'#232E66'];
@endphp

<div class="page">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg-1);margin:0;">Target Bulanan</h1>
            <p style="font-size:13px;color:var(--fg-3);margin:2px 0 0;">
                {{ ucfirst(str_replace('_',' ', auth()->user()->department ?? 'CEO Office')) }}
            </p>
        </div>
        <a href="{{ route('monthly-targets.create') }}" class="btn btn-primary btn-sm">
            <svg class="lucide sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Baru
        </a>
    </div>

    @if($targets->isEmpty())
        <div class="m-card">
            <div class="empty-state">
                <svg class="lucide lg" style="margin:0 auto 12px;color:var(--fg-4);" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p style="font-size:14px;margin-bottom:8px;">Belum ada target bulanan.</p>
                <a href="{{ route('monthly-targets.create') }}" style="font-size:13px;font-weight:600;color:var(--maxy-navy);">Buat target pertama →</a>
            </div>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($targets as $target)
                <div class="m-card" style="padding:14px 16px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                                <span class="chip chip-neutral">{{ $months[$target->month] }} {{ $target->year }}</span>
                                <span class="chip chip-dept-{{ str_replace('_','-', $target->department) }}">
                                    {{ ucfirst(str_replace('_',' ', $target->department)) }}
                                </span>
                            </div>
                            <div style="font-size:14px;font-weight:600;color:var(--fg-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $target->title }}
                            </div>
                            @if($target->description)
                                <p style="font-size:12px;color:var(--fg-3);margin:4px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    {{ $target->description }}
                                </p>
                            @endif
                            <div style="font-size:11px;color:var(--fg-4);margin-top:6px;">{{ $target->user->name }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                            <a href="{{ route('monthly-targets.edit', $target) }}"
                               class="icon-btn" title="Edit">
                                <svg class="lucide sm" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('monthly-targets.destroy', $target) }}"
                                  onsubmit="return confirm('Hapus target ini?');" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn" title="Hapus" style="color:var(--danger);">
                                    <svg class="lucide sm" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
</x-app-layout>
