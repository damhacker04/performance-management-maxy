<x-app-layout>
    {{-- Isi halaman KPI ada di partial bersama (dipakai juga oleh /admin/kpi). --}}
    @include('kpi._body', ['kpiRouteName' => 'kpi'])
</x-app-layout>
