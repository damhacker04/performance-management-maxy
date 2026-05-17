<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    /**
     * Halaman preview export laporan.
     * Hanya bisa diakses oleh c_level atau user dengan is_management = true.
     */
    public function index(Request $request)
    {
        $this->authorizeExport();

        // Default: bulan & tahun saat ini
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        // Ambil semua user yang bukan c_level dan bukan management (mereka yang dilaporkan)
        $reportableUsers = User::whereIn('role', ['staff', 'leader'])
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        // Ambil data laporan per user untuk bulan yang dipilih
        $period    = Carbon::createFromDate($year, $month, 1);
        $startDate = $period->copy()->startOfMonth();
        $endDate   = $period->copy()->endOfMonth();

        $reports = [];
        foreach ($reportableUsers as $user) {
            $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                ->where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get();

            if ($entries->isNotEmpty()) {
                $reports[] = [
                    'user'    => $user,
                    'entries' => $entries,
                ];
            }
        }

        // Daftar bulan untuk dropdown filter
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $months = collect(range(1, 12))->map(fn($m) => [
            'value' => $m,
            'label' => $monthNames[$m],
        ]);

        // Tahun tersedia — ambil dari data paling lama di DB, fallback ke tahun ini
        try {
            $minRaw     = DailyTaskEntry::min('date');
            $firstYear  = ($minRaw && strlen((string)$minRaw) >= 4)
                        ? (int) Carbon::parse((string)$minRaw)->format('Y')
                        : now()->year;
        } catch (\Throwable) {
            $firstYear = now()->year;
        }
        $years = range($firstYear, now()->year);

        return view('export.index', compact(
            'reports', 'months', 'years', 'month', 'year', 'period'
        ));
    }

    /**
     * Download laporan sebagai file CSV.
     * (Fase ini: CSV dulu, Excel menyusul setelah package diinstall)
     */
    public function download(Request $request)
    {
        $this->authorizeExport();

        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $period    = Carbon::createFromDate($year, $month, 1);
        $startDate = $period->copy()->startOfMonth();
        $endDate   = $period->copy()->endOfMonth();

        $reportableUsers = User::whereIn('role', ['staff', 'leader'])
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $filename = 'laporan_kpi_' . $period->format('Y_m') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reportableUsers, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel agar bisa baca UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($reportableUsers as $user) {
                $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                    ->where('user_id', $user->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->orderBy('date')
                    ->get();

                if ($entries->isEmpty()) continue;

                // Header nama orang
                fputcsv($handle, [$user->name . ' — ' . ($user->departmentLabel ?? '-')]);

                // Header kolom
                fputcsv($handle, [
                    'No', 'Tanggal', 'Project', 'Task / Deskripsi Pekerjaan',
                    'Prioritas', 'Durasi (mnt)', 'Status', '% Done', 'Kendala / Notes',
                ]);

                // Baris data
                foreach ($entries as $i => $entry) {
                    $project = $entry->weeklyTarget?->monthlyTarget?->title
                             ?? $entry->weeklyTarget?->title
                             ?? 'Lainnya';

                    fputcsv($handle, [
                        $i + 1,
                        Carbon::parse($entry->date)->format('d/m/Y'),
                        $project,
                        $entry->title,
                        ucfirst($entry->priority ?? 'medium'),
                        $entry->duration ?? '-',
                        ucfirst($entry->status),
                        $entry->percent_done ?? 0,
                        $entry->notes ?? '',
                    ]);
                }

                // Baris kosong pemisah antar orang
                fputcsv($handle, []);
                fputcsv($handle, []);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Guard: hanya c_level atau is_management yang bisa akses export.
     */
    private function authorizeExport(): void
    {
        $user = auth()->user();
        if (!$user || !$user->canExport()) {
            abort(403, 'Anda tidak memiliki akses ke fitur Export.');
        }
    }
}
