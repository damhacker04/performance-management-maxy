<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CeoOverviewController;
use Illuminate\Http\Request;

/**
 * Overview versi Admin HR (super_admin) — halaman sendiri di /admin/overview.
 * Logika query dibagi dengan CeoOverviewController; hanya view-nya yang berbeda,
 * agar bisa dikustomisasi khusus HR tanpa mengganggu halaman CEO.
 */
class AdminOverviewController extends CeoOverviewController
{
    public function index(Request $request)
    {
        return view('admin.overview', $this->buildOverviewData($request));
    }
}
