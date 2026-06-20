<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MigrateLegacyTargets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-targets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy "Target Bersama" to the new specific target hierarchy per leader/staff (retaining old titles)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy data migration (Splitting Monthly Targets per Staff)...');

        DB::beginTransaction();

        try {
            // Ambil semua weekly target
            $weeklyTargets = WeeklyTarget::with(['monthlyTarget', 'dailyTaskEntries'])->get();
            $migratedCount = 0;

            foreach ($weeklyTargets as $weekly) {
                $monthly = $weekly->monthlyTarget;
                if (!$monthly) continue;

                $dept = $monthly->department;
                $month = $monthly->month;
                $year = $monthly->year;

                $leader = User::where('department', $dept)->where('role', 'leader')->first();
                $fallbackUser = User::where('role', 'c_level')->first();
                $ownerId = $leader ? $leader->id : ($fallbackUser ? $fallbackUser->id : 1);

                // Siapa sebenarnya pemilik weekly target ini?
                $staffIds = $weekly->dailyTaskEntries->pluck('user_id')->unique()->filter();
                
                if ($staffIds->isEmpty() && $weekly->assigned_to) {
                    $staffIds = collect([$weekly->assigned_to]);
                }

                if ($staffIds->isEmpty()) {
                    // Jika tidak ada staf sama sekali, biarkan sebagai Target Umum
                    $clonedMonthly = MonthlyTarget::firstOrCreate(
                        [
                            'department' => $dept,
                            'month' => $month,
                            'year' => $year,
                            'title' => $monthly->title,
                            'user_id' => $ownerId,
                            'assigned_to' => null, // UMUM
                        ],
                        [
                            'description' => $monthly->description ?? 'Data migrasi dari target lama.',
                        ]
                    );

                    if ($weekly->monthly_target_id !== $clonedMonthly->id || $weekly->user_id !== $ownerId) {
                        $weekly->update([
                            'monthly_target_id' => $clonedMonthly->id,
                            'user_id' => $ownerId,
                            'assigned_to' => null,
                        ]);
                        $migratedCount++;
                    }
                    continue;
                }

                // Jika ada staf, pecah Target Bulanan UNTUK MASING-MASING STAF
                foreach ($staffIds as $staffId) {
                    $staff = User::find($staffId);
                    if (!$staff) continue;

                    // Buat/Cari Target Bulanan SPESIFIK untuk staf ini
                    $clonedMonthlyPerStaff = MonthlyTarget::firstOrCreate(
                        [
                            'department' => $dept,
                            'month' => $month,
                            'year' => $year,
                            'title' => $monthly->title,
                            'user_id' => $ownerId,
                            'assigned_to' => $staffId, // TARGET BULANAN PER STAF!
                        ],
                        [
                            'description' => $monthly->description ?? 'Data migrasi dari target lama.',
                        ]
                    );

                    // Jika hanya ada 1 staf di weekly target ini, pindahkan langsung
                    if ($staffIds->count() === 1) {
                        if ($weekly->monthly_target_id !== $clonedMonthlyPerStaff->id || $weekly->assigned_to !== $staffId) {
                            $weekly->update([
                                'monthly_target_id' => $clonedMonthlyPerStaff->id,
                                'user_id' => $ownerId,
                                'assigned_to' => $staffId,
                            ]);
                            
                            // Update referensi monthly_target_id di daily task
                            foreach ($weekly->dailyTaskEntries as $task) {
                                $task->update(['monthly_target_id' => $clonedMonthlyPerStaff->id]);
                            }
                            $migratedCount++;
                        }
                    } else {
                        // Jika dalam 1 weekly target dulunya dikeroyok banyak staf (jarang tapi mungkin)
                        $tasksForStaff = $weekly->dailyTaskEntries->where('user_id', $staffId);
                        if ($tasksForStaff->isNotEmpty()) {
                            $newWeekly = WeeklyTarget::create([
                                'monthly_target_id' => $clonedMonthlyPerStaff->id,
                                'user_id' => $ownerId,
                                'month' => $monthly->month,
                                'year' => $monthly->year,
                                'week_number' => $weekly->week_number,
                                'title' => $weekly->title,
                                'description' => $weekly->description,
                                'target_type' => $weekly->target_type,
                                'target_value' => $weekly->target_value,
                                'target_unit' => $weekly->target_unit,
                                'assigned_to' => $staffId,
                            ]);

                            foreach ($tasksForStaff as $task) {
                                $task->update([
                                    'weekly_target_id' => $newWeekly->id,
                                    'monthly_target_id' => $clonedMonthlyPerStaff->id
                                ]);
                            }
                            $migratedCount++;
                        }
                    }
                }

                // Hapus weekly target asli jika sudah kosong karena dipecah
                if ($staffIds->count() > 1 && $weekly->dailyTaskEntries()->count() === 0) {
                    $weekly->delete();
                }
            }

            // Bersihkan Target Bulanan lama yang sudah tidak punya weekly target
            $emptyMonthlies = MonthlyTarget::whereDoesntHave('weeklyTargets')->get();
            $deletedCount = $emptyMonthlies->count();
            foreach ($emptyMonthlies as $em) {
                $em->delete();
            }

            DB::commit();

            $this->info("Migration completed successfully! ✅");
            $this->line("- $migratedCount weekly targets moved/cloned & assigned to specific staff.");
            $this->line("- $deletedCount old/empty monthly targets cleaned up.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
}
