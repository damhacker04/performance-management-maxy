<?php

namespace App\Exports;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KpiReportExport implements WithMultipleSheets
{
    public function __construct(
        protected $reportableUsers,
        protected $startDate,
        protected $endDate,
        protected string $periodLabel,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->reportableUsers as $user) {
            $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                ->where('user_id', $user->id)
                ->whereBetween('task_date', [$this->startDate, $this->endDate])
                ->orderBy('task_date')
                ->get();

            if ($entries->isNotEmpty()) {
                $sheets[] = new KpiUserSheet($user, $entries, $this->periodLabel);
            }
        }

        return $sheets;
    }
}
