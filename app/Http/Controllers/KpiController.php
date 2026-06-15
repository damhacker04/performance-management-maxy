<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KpiTarget;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get users based on role
        if ($user->role === 'c_level' || $user->role === 'super_admin') {
            $staffs = User::with('kpiTargets')
                ->where('is_active', true)
                ->orderBy('department')
                ->orderBy('name')
                ->get();
        } else {
            // For leaders, only see staff in their department
            $staffs = User::with('kpiTargets')
                ->where('department', $user->department)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Group by department for C-Level, or just a single list for Leader
        $groupedStaffs = $staffs->groupBy('department');

        return view('kpi', compact('groupedStaffs'));
    }
}
