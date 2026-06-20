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
        $this->info('Starting legacy data migration (Cloning original titles)...');

        DB::beginTransaction();

        try {
            // Ambil semua weekly target yang ada beserta task hariannya
            $weeklyTargets = WeeklyTarget::with(['monthlyTarget', 'dailyTaskEntries.user'])->get();
            $migratedCount = 0;

            foreach ($weeklyTargets as $weekly) {
                $monthly = $weekly->monthlyTarget;
                if (!$monthly) continue; // Skip jika yatim

                // Ambil daftar user_id (staf) yang pernah mengisi progress di weekly target ini
                $staffIds = $weekly->dailyTaskEntries->pluck('user_id')->unique()->filter();

                $dept = $monthly->department; // Menggunakan department dari MonthlyTarget
                $month = $monthly->month;
                $year = $monthly->year;

                // Cari leader di departemen ini untuk dijadikan owner (user_id)
                $leader = User::where('department', $dept)->where('role', 'leader')->first();
                $fallbackUser = User::where('role', 'c_level')->first();
                
                $ownerId = $leader ? $leader->id : ($fallbackUser ? $fallbackUser->id : 1);

                // 1. Kloning Target Bulanan Lama (Menggunakan Judul Asli!)
                $clonedMonthly = MonthlyTarget::firstOrCreate(
                    [
                        'department' => $dept,
                        'month' => $month,
                        'year' => $year,
                        'title' => $monthly->title, // MENGGUNAKAN JUDUL ASLI
                        'user_id' => $ownerId,      // KEPEMILIKAN BARU
                    ],
                    [
                        'description' => $monthly->description ?? 'Data migrasi dari target lama.',
                    ]
                );

                if ($staffIds->isEmpty()) {
                    // JIKA KOSONG: Pindahkan ke target bulanan yang benar (Leader)
                    // Status assigned_to biarkan seperti semula (Umum)
                    if ($weekly->monthly_target_id !== $clonedMonthly->id || $weekly->user_id !== $ownerId) {
                        $weekly->update([
                            'monthly_target_id' => $clonedMonthly->id,
                            'user_id' => $ownerId
                        ]);
                        $migratedCount++;
                    }
                    continue;
                }

                // JIKA ADA TASK: Pecah berdasarkan staf
                foreach ($staffIds as $staffId) {
                    $staff = User::find($staffId);
                    if (!$staff) continue;

                    // Skip jika monthly target yang sedang diproses ini memang sudah punya owner yang benar
                    if ($monthly->id === $clonedMonthly->id && $monthly->user_id === $ownerId) {
                        continue;
                    }

                    // 2. Clone Weekly Target untuk staf ini secara spesifik
                    $tasksForStaff = $weekly->dailyTaskEntries->where('user_id', $staffId);

                    if ($tasksForStaff->isNotEmpty()) {
                        $newWeekly = WeeklyTarget::create([
                            'monthly_target_id' => $clonedMonthly->id,
                            'user_id' => $ownerId, // Leader yang buat target ini
                            'month' => $monthly->month,
                            'year' => $monthly->year,
                            'week_number' => $weekly->week_number,
                            'title' => $weekly->title,
                            'description' => $weekly->description,
                            'target_type' => $weekly->target_type,
                            'target_value' => $weekly->target_value,
                            'target_unit' => $weekly->target_unit,
                            'assigned_to' => $staffId, // Kunci ke staf ini
                        ]);

                        // 3. Pindahkan Daily Task milik staf ini ke Weekly Target yang baru di-clone
                        foreach ($tasksForStaff as $task) {
                            $task->update(['weekly_target_id' => $newWeekly->id]);
                        }
                        
                        $migratedCount++;
                    }
                }

                // Setelah dipecah ke masing-masing staf, hapus weekly target original-nya
                if ($weekly->dailyTaskEntries()->count() === 0 && $weekly->monthly_target_id !== $clonedMonthly->id) {
                    $weekly->delete();
                }
            }

            // 4. Bersihkan Monthly Target lama yang kepemilikannya salah/kosong dan sudah tidak punya weekly target
            $emptyMonthlies = MonthlyTarget::whereDoesntHave('weeklyTargets')->get();
            $deletedCount = $emptyMonthlies->count();
            foreach ($emptyMonthlies as $em) {
                $em->delete();
            }

            DB::commit();

            $this->info("Migration completed successfully! ✅");
            $this->line("- $migratedCount weekly targets cloned & assigned to specific staff.");
            $this->line("- $deletedCount old/empty monthly targets cleaned up.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
}
