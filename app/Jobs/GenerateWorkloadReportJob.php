<?php

namespace App\Jobs;

use App\Http\Controllers\WorkloadReportController;
use App\Models\User;
use App\Models\WorkloadReport;
use App\Services\GeminiService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWorkloadReportJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $staffId;
    public $month;
    public $year;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct($staffId, $month, $year)
    {
        $this->staffId = $staffId;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $gemini): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $staff = User::find($this->staffId);
        if (!$staff) return;

        try {
            // Build the data using the same logic as the controller
            $controller = app(WorkloadReportController::class);
            
            // To access the private buildFullStaffData method, we use reflection
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('buildFullStaffData');
            $method->setAccessible(true);
            $data = $method->invoke($controller, $staff, $this->month, $this->year);

            // Generate report via AI
            $result = $gemini->generateWorkloadReport($data);

            // Save to DB
            WorkloadReport::updateOrCreate(
                [
                    'staff_id' => $this->staffId,
                    'month'    => $this->month,
                    'year'     => $this->year,
                ],
                [
                    'score'        => $result['score'] ?? null,
                    'summary_flag' => $result['summary_flag'] ?? '??',
                    'report_data'  => $result,
                ]
            );

        } catch (\Exception $e) {
            Log::error("Failed generating workload report for staff {$this->staffId}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
