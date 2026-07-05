<x-app-layout>
    {{-- Halaman KPI versi Admin HR — isi dari partial bersama, self-link ke admin.kpi.
         Semua fitur (termasuk tombol AI) otomatis ikut karena satu sumber. --}}
    @include('kpi._body', ['kpiRouteName' => 'admin.kpi'])
</x-app-layout>
