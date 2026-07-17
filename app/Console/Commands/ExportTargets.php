<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Export monthly_targets + weekly_targets ke JSON untuk di-commit ke repo
 * dan di-deploy via route /import-targets di Railway.
 *
 * Usage: php artisan export:targets
 */
class ExportTargets extends Command
{
    protected $signature   = 'export:targets {--out= : Path output (default: database/data/monthly-targets.json)}';
    protected $description = 'Export monthly_targets + weekly_targets ke JSON seed file';

    public function handle(): int
    {
        $out  = $this->option('out') ?? database_path('data/monthly-targets.json');
        $conn = \DB::connection();

        $rows = $conn->table('monthly_targets as mt')
            ->leftJoin('users as u1', 'mt.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'mt.assigned_to', '=', 'u2.id')
            ->select(
                'mt.id', 'mt.department', 'mt.title', 'mt.description',
                'mt.month', 'mt.year',
                'u1.email as creator_email',
                'u2.email as assigned_email'
            )
            ->orderBy('mt.month')
            ->orderBy('mt.year')
            ->get();

        $data = $rows->map(function ($t) use ($conn) {
            $weekly = $conn->table('weekly_targets')
                ->where('monthly_target_id', $t->id)
                ->orderBy('week_number')
                ->get();

            return [
                'creator_email'  => $t->creator_email,
                'assigned_email' => $t->assigned_email,
                'department'     => $t->department,
                'title'          => $t->title,
                'description'    => $t->description,
                'month'          => (int) $t->month,
                'year'           => (int) $t->year,
                'weekly_targets' => $weekly->map(fn ($w) => [
                    'week_number'  => (int) $w->week_number,
                    'title'        => $w->title,
                    'description'  => $w->description ?? '',
                    'category'     => $w->category ?? 'planned',
                    'impact_level' => $w->impact_level ?? 'medium',
                    'target_type'  => $w->target_type ?? 'qualitative',
                    'target_label' => $w->target_label ?? null,
                    'month'        => (int) ($w->month ?? $t->month),
                    'year'         => (int) ($w->year ?? $t->year),
                ])->toArray(),
            ];
        });

        $dir = dirname($out);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json      = json_encode(
            ['targets' => $data->toArray()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $bytesWritten = file_put_contents($out, $json);

        if ($bytesWritten === false) {
            $this->error("Gagal menulis ke: {$out}");
            return self::FAILURE;
        }

        $this->info("✅ Exported {$data->count()} monthly targets");
        $this->info("   Total weekly: " . $data->sum(fn ($t) => count($t['weekly_targets'])));
        $this->info("   File: {$out} ({$bytesWritten} bytes)");

        return self::SUCCESS;
    }
}
