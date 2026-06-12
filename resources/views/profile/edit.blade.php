<x-app-layout>
@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
    $deptLabel = $user->department ? ucfirst(str_replace('_', ' ', $user->department)) : ($user->role === 'super_admin' ? 'Super Admin' : 'Tanpa Departemen');
    $roleLabel = ['staff'=>'Staff','leader'=>'Leader','c_level'=>'C-Level','super_admin'=>'Super Admin'][$user->role] ?? $user->role;
@endphp

<div class="page">
    <!-- Profile card -->
    <div class="m-card" style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:24px 16px;">
        <div class="av-lg">{{ $initials }}</div>
        <h2 style="margin-top:12px;font-size:18px;font-weight:700;">{{ $user->name }}</h2>
        <p style="font-size:13px;color:var(--fg-3);margin-top:2px;">{{ $roleLabel }} · {{ $deptLabel }}</p>
        <span class="chip chip-dept-{{ str_replace('_','-', $user->department ?? 'neutral') }}" style="margin-top:10px;">
            {{ $deptLabel }}
        </span>
    </div>

    <!-- Update info -->
    <div class="m-card">
        <div style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--fg-3);margin-bottom:14px;">Edit Profil</div>
        @include('profile.partials.update-profile-information-form')
    </div>

    <!-- Change password -->
    <div class="m-card">
        <div style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--fg-3);margin-bottom:14px;">Ubah Kata Sandi</div>
        @include('profile.partials.update-password-form')
    </div>

</div>
</x-app-layout>
