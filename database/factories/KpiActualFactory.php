<?php

namespace Database\Factories;

use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KpiActualFactory extends Factory
{
    protected $model = KpiActual::class;

    public function definition(): array
    {
        return [
            'kpi_target_id' => KpiTarget::factory(),
            'staff_id'      => User::factory()->staff(),
            'department'    => 'product_it',
            'month'         => (int) now()->month,
            'year'          => (int) now()->year,
            'actual_value'  => fake()->numberBetween(1, 100),
            'source'        => 'manual',
            'notes'         => null,
            'created_by'    => User::factory()->cLevel(),
        ];
    }

    public function forKpi(KpiTarget $kpi, User $staff): static
    {
        return $this->state([
            'kpi_target_id' => $kpi->id,
            'staff_id'      => $staff->id,
            'department'    => $kpi->department,
            'month'         => $kpi->month,
            'year'          => $kpi->year,
        ]);
    }
}
