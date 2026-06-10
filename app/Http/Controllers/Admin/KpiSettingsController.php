<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KpiWeightSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * KpiSettingsController — Panel HR/Admin untuk mengatur
 * bobot 4 dimensi penilaian KPI (Pencapaian, Efisiensi,
 * Kontribusi Bisnis, Problem Solving).
 *
 * Bobot bisa diubah kapan saja oleh HR tanpa bantuan developer.
 * Saat disimpan, bobot baru otomatis berlaku untuk evaluasi AI berikutnya.
 */
class KpiSettingsController extends Controller
{
    /**
     * Halaman utama pengaturan bobot KPI.
     * GET /admin/kpi-settings
     */
    public function index()
    {
        // Setting yang sedang aktif (global)
        $current = KpiWeightSetting::whereNull('department_id')
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        // Histori semua perubahan bobot
        $history = KpiWeightSetting::with('setter')
            ->whereNull('department_id')
            ->latest('effective_from')
            ->paginate(10);

        return view('admin.kpi-settings.index', compact('current', 'history'));
    }

    /**
     * Simpan setting bobot KPI baru.
     * POST /admin/kpi-settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'weight_achievement'    => 'required|numeric|min:0|max:100',
            'weight_efficiency'     => 'required|numeric|min:0|max:100',
            'weight_contribution'   => 'required|numeric|min:0|max:100',
            'weight_problem_solving'=> 'required|numeric|min:0|max:100',
            'effective_from'        => 'required|date',
        ], [
            'weight_achievement.required'     => 'Bobot Pencapaian Target wajib diisi.',
            'weight_efficiency.required'      => 'Bobot Efisiensi Waktu wajib diisi.',
            'weight_contribution.required'    => 'Bobot Kontribusi Bisnis wajib diisi.',
            'weight_problem_solving.required' => 'Bobot Problem Solving wajib diisi.',
            'effective_from.required'         => 'Tanggal berlaku wajib diisi.',
        ]);

        // Validasi total harus = 100%
        $total = $request->weight_achievement
               + $request->weight_efficiency
               + $request->weight_contribution
               + $request->weight_problem_solving;

        if (abs($total - 100) > 0.01) {
            return back()
                ->withInput()
                ->withErrors(['total' => "Total bobot harus 100%. Saat ini: {$total}%"]);
        }

        // Nonaktifkan setting lama
        KpiWeightSetting::whereNull('department_id')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Simpan setting baru
        KpiWeightSetting::create([
            'department_id'          => null, // Global
            'weight_achievement'     => $request->weight_achievement,
            'weight_efficiency'      => $request->weight_efficiency,
            'weight_contribution'    => $request->weight_contribution,
            'weight_problem_solving' => $request->weight_problem_solving,
            'set_by'                 => Auth::id(),
            'effective_from'         => $request->effective_from,
            'is_active'              => true,
        ]);

        return redirect()->route('admin.kpi-settings.index')
            ->with('success', 'Pengaturan bobot KPI berhasil disimpan dan akan berlaku mulai ' . $request->effective_from . '.');
    }
}
