<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\KpiController;

/**
 * Halaman KPI versi Admin HR (super_admin) — index/monitoring sendiri di /admin/kpi.
 * "Form dibagi": tombol tambah/edit KPI & input Actual tetap memakai route KPI yang ada
 * (kpi.create, kpi.edit, kpi.actuals.*). Hanya halaman index yang punya versi admin.
 */
class AdminKpiController extends KpiController
{
    public function index()
    {
        return view('admin.kpi', $this->buildIndexData());
    }
}
