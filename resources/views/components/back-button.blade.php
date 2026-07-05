@props(['fallback' => null])
{{--
    Tombol "back" terpusat.
    - Klik normal  → history.back() (retrace persis urutan klik user).
    - Fallback href → dipakai HANYA bila tak ada riwayat (buka link langsung / tab baru /
      middle-click). Menghormati ?back= bila ada, lalu $fallback yang dikirim halaman.
--}}
@php
    $backParam = request()->query('back');
    $href = $backParam ? urldecode($backParam) : ($fallback ?? url('/'));
@endphp
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'icon-btn']) }}
   onclick="if (history.length > 1) { event.preventDefault(); history.back(); }">
    <svg class="lucide" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
</a>
