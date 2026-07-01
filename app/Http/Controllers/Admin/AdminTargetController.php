<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CeoTargetController;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Halaman Target versi Admin HR (super_admin) — sendiri di /admin/targets.
 * Logika query dibagi dengan CeoTargetController; hanya view-nya yang berbeda.
 */
class AdminTargetController extends CeoTargetController
{
    public function index(Request $request)
    {
        return view('admin.targets.index', $this->buildIndexData($request));
    }

    public function showLeader(Request $request, User $leader)
    {
        return view('admin.targets.leader', $this->buildLeaderData($request, $leader));
    }
}
