<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class KpiUserSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        protected $user,
        protected $entries,
        protected string $periodLabel,
    ) {}

    public function title(): string
    {
        // Sheet name maksimal 31 karakter di Excel
        return substr($this->user->name, 0, 28);
    }

    public function array(): array
    {
        $rows = [];

        // Baris info header
        $rows[] = ['Laporan KPI — ' . $this->periodLabel];
        $rows[] = [$this->user->name . '  |  ' . ucfirst($this->user->department ?? '-') . '  |  ' . ucfirst($this->user->role)];
        $rows[] = ['']; // baris kosong

        // Header kolom
        $rows[] = ['#', 'Tanggal', 'Target Bulanan', 'Target Mingguan', 'Deskripsi Tugas', 'Prioritas', 'Durasi', 'Status', 'Catatan / Progress'];

        // Baris data
        foreach ($this->entries as $i => $entry) {
            $rows[] = [
                $i + 1,
                Carbon::parse($entry->task_date)->format('d/m/Y'),
                $entry->weeklyTarget?->monthlyTarget?->title ?? '-',
                $entry->weeklyTarget?->title ?? 'Tugas Tambahan',
                $entry->task_description,
                $entry->priority_label,
                $entry->duration_label,
                $entry->status_label,
                $entry->notes ?? '',
            ];
        }

        // Baris ringkasan
        $rows[] = [''];
        $rows[] = [
            'Total Tugas: ' . $this->entries->count(),
            '',
            'Selesai: ' . $this->entries->where('status', 'selesai')->count(),
            '',
            'Dalam Proses: ' . $this->entries->where('status', 'dalam_proses')->count(),
            '',
            'Terhambat: ' . $this->entries->where('status', 'terhambat')->count(),
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->array()) + 1;

        return [
            // Judul laporan — baris 1
            1 => [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A8A']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            // Info user — baris 2
            2 => [
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '374151']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            ],
            // Header kolom — baris 4
            4 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 12,
            'C' => 22,
            'D' => 22,
            'E' => 40,
            'F' => 10,
            'G' => 10,
            'H' => 14,
            'I' => 35,
        ];
    }
}
