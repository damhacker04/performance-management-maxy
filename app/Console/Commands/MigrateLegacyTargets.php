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
    protected $description = 'Migrate legacy "Target Bersama" to the new specific target hierarchy per leader/staff';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy data migration...');

        DB::beginTransaction();

        try {
            // Ambil semua weekly target yang ada beserta task hariannya
            $weeklyTargets = WeeklyTarget::with(['monthlyTarget', 'dailyTaskEntries.user'])->get();
            $migratedCount = 0;

            foreach ($weeklyTargets as $weekly) {
                $monthly = $weekly->monthlyTarget;
                if (!$monthly) continue; // Skip jika yatim (seharusnya tidak ada)

                // Jika target ini sudah bernaung di bawah "Arsip Target Lama", lewati
                if (str_contains($monthly->title, 'Arsip Target Lama')) {
                    continue;
                }

                // Ambil daftar user_id (staf) yang pernah mengisi progress di weekly target ini
                $staffIds = $weekly->dailyTaskEntries->pluck('user_id')->unique()->filter();

                if ($staffIds->isEmpty()) {
                    // Weekly target belum pernah dikerjakan oleh siapa pun.
                    // Karena struktur lama membingungkan (siapa pemiliknya?), 
                    // dan ini belum ada progress harian, kita biarkan saja atau nanti otomatis terhapus
                    // saat monthly target utamanya dihapus jika sudah tidak relevan.
                    continue;
                }

                foreach ($staffIds as $staffId) {
                    $staff = User::find($staffId);
                    if (!$staff) continue;

                    $dept = $staff->department;
                    $month = $monthly->month;
                    $year = $monthly->year;

                    // 1. Cari/Buat Target Bulanan Dummy untuk departemen staf ini
                    // Cari leader di departemen ini untuk dijadikan owner (user_id)
                    $leader = User::where('department', $dept)->where('role', 'leader')->first();
                    $fallbackUser = User::where('role', 'c_level')->first(); // Fallback jika tidak ada leader
                    
                    $ownerId = $leader ? $leader->id : ($fallbackUser ? $fallbackUser->id : 1);

                    $dummyTitle = 'Arsip Target Lama - ' . ucfirst(str_replace('_', ' ', $dept));

                    $dummyMonthly = MonthlyTarget::firstOrCreate(
                        [
                            'department' => $dept,
                            'month' => $month,
                            'year' => $year,
                            'title' => $dummyTitle,
                        ],
                        [
                            'user_id' => $ownerId,
                            'description' => 'Wadah data target masa lalu yang dimigrasi otomatis.',
                        ]
                    );

                    // 2. Clone Weekly Target untuk staf ini secara spesifik
                    // (Karena di struktur lama, 1 weekly target bisa dikerjakan rame-rame, 
                    // sedangkan di struktur baru 1 weekly target = 1 staf)
                    $tasksForStaff = $weekly->dailyTaskEntries->where('user_id', $staffId);

                    if ($tasksForStaff->isNotEmpty()) {
                        $newWeekly = WeeklyTarget::create([
                            'monthly_target_id' => $dummyMonthly->id,
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
                $weekly->delete();
            }

            // 4. Bersihkan Monthly Target lama yang sudah kosong (tidak punya weekly target lagi)
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
