<?php

namespace App\Console\Commands;

use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Impor KPI per INDIVIDU (L3) dari database/data/kpi-individual.json.
 *
 * JSON dihasilkan dari KPI Master (new) + roster Data Karyawan (nama+jabatan).
 * Tiap orang → posisi → daftar KPI. Command ini:
 *  1. Upsert user (buat yang baru; update nama+dept untuk yang ada; role/password
 *     lama tak diubah).
 *  2. Buat KPI L2 (benchmark dept) unik per (dept, kpi_name).
 *  3. Buat KPI L3 (per individu) di bawah L2, menempel ke user.
 *
 * RESET PENUH KPI dulu (clean slate). Default DRY-RUN; --commit untuk menulis.
 */
class ImportKpiIndividual extends Command
{
    protected $signature = 'import:kpi-individual {--commit}';

    protected $description = 'Impor KPI per individu (L2 benchmark + L3 per orang) dari kpi-individual.json';

    private const MARK = 'Impor KPI Individual';

    public function handle(): int
    {
        $path = database_path('data/kpi-individual.json');
        if (! is_file($path)) {
            $this->error("File tidak ditemukan: $path");
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || empty($data['people'])) {
            $this->error('JSON tidak valid / kosong.');
            return self::FAILURE;
        }

        $month = (int) ($data['month'] ?? now()->month);
        $year  = (int) ($data['year'] ?? now()->year);
        $people = $data['people'];

        // Statistik
        $newUsers = 0; $updUsers = 0; $l2 = 0; $l3 = 0;
        foreach ($people as $p) { $l3 += count($p['kpis'] ?? []); }
        $depts = [];
        foreach ($people as $p) foreach ($p['kpis'] as $k) { $depts[$k['dept'] . '|' . $k['name']] = true; }
        $this->line('Orang: ' . count($people) . ' | KPI L3: ' . $l3 . ' | KPI L2 unik: ' . count($depts) . " | periode $month/$year");

        if (! $this->option('commit')) {
            // Cek user mana yang baru
            foreach ($people as $p) {
                if (! User::where('email', $p['email'])->exists()) { $this->line('  🆕 akun baru: ' . $p['name'] . ' (' . $p['dept'] . ')'); $newUsers++; }
                else { $updUsers++; }
            }
            $this->warn("DRY-RUN. Akun baru: $newUsers, update: $updUsers. Tambahkan --commit untuk menulis.");
            return self::SUCCESS;
        }

        // ── RESET KPI (clean slate) ─────────────────────────────────────────
        KpiActual::query()->delete();
        KpiTarget::where('kpi_level', 3)->delete();
        KpiTarget::query()->delete();
        $this->line('KPI lama direset.');

        $adminId = User::where('role', 'super_admin')->value('id');

        // ── Upsert users ────────────────────────────────────────────────────
        $userIdByEmail = [];
        foreach ($people as $p) {
            $u = User::where('email', $p['email'])->first();
            if ($u) {
                $upd = ['name' => $p['name'], 'department' => $p['dept'], 'division' => $p['position'] ?? $u->division];
                // Promosi staff → leader bila jabatannya Head/Manager/SPV (tak pernah
                // menurunkan c_level/super_admin, password tak diubah).
                if ($u->role === 'staff' && ($p['role'] ?? '') === 'leader') {
                    $upd['role'] = 'leader';
                }
                $u->update($upd);
                $updUsers++;
            } else {
                $u = User::create([
                    'name'          => $p['name'],
                    'email'         => $p['email'],
                    'password'      => Hash::make('maxy2026'),
                    'role'          => $p['role'],
                    'department'    => $p['dept'],
                    'division'      => $p['position'] ?? null,
                    'is_management' => $p['role'] !== 'staff',
                    'is_active'     => true,
                ]);
                $newUsers++;
            }
            $userIdByEmail[$p['email']] = $u->id;
        }

        // ── Buat L2 + L3 ────────────────────────────────────────────────────
        $l2 = 0; $l3 = 0; // reset (di atas dipakai utk statistik)
        $l2Cache = []; // "dept|name" => id
        foreach ($people as $p) {
            $uid = $userIdByEmail[$p['email']];
            foreach ($p['kpis'] as $k) {
                $key = $k['dept'] . '|' . $k['name'];
                if (! isset($l2Cache[$key])) {
                    $parent = KpiTarget::create([
                        'parent_id'    => null,
                        'user_id'      => null,
                        'kpi_level'    => 2,
                        'department'   => $k['dept'],
                        'kpi_name'     => $k['name'],
                        'aggregation'  => $k['aggregation'],
                        'lower_is_better' => (bool) ($k['lower_is_better'] ?? false),
                        'target_value' => $k['target_value'],
                        'unit'         => $k['unit'],
                        'month'        => $month,
                        'year'         => $year,
                        'is_active'    => true,
                        'set_by'       => $adminId,
                        'notes'        => self::MARK,
                    ]);
                    $l2Cache[$key] = $parent->id;
                    $l2++;
                }
                KpiTarget::create([
                    'parent_id'    => $l2Cache[$key],
                    'user_id'      => $uid,
                    'kpi_level'    => 3,
                    'department'   => $k['dept'],
                    'kpi_name'     => $k['name'],
                    'aggregation'  => $k['aggregation'],
                    'lower_is_better' => (bool) ($k['lower_is_better'] ?? false),
                    'target_value' => $k['target_value'],
                    'unit'         => $k['unit'],
                    'month'        => $month,
                    'year'         => $year,
                    'is_active'    => true,
                    'set_by'       => $adminId,
                    'notes'        => self::MARK,
                ]);
                $l3++;
            }
        }

        $this->info("Commit selesai. User baru $newUsers, update $updUsers | KPI L2 $l2, L3 $l3 (periode $month/$year).");
        return self::SUCCESS;
    }
}
