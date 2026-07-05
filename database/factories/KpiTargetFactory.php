<?php

namespace Database\Factories;

use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KpiTargetFactory extends Factory
{
    protected $model = KpiTarget::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'kpi_level' => 2,
            'aggregation' => 'sum',
            'user_id' => null,
            'department' => 'product_it',
            'kpi_name' => fake()->words(3, true),
            'target_value' => fake()->numberBetween(10, 1000),
            'unit' => 'unit',
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'set_by' => User::factory()->cLevel(),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function level2(): static
    {
        return $this->state(['kpi_level' => 2, 'parent_id' => null, 'user_id' => null]);
    }

    public function level3(KpiTarget $parent, User $staff): static
    {
        return $this->state([
            'kpi_level' => 3,
            'parent_id' => $parent->id,
            'user_id' => $staff->id,
            'department' => $parent->department,
            'kpi_name' => $parent->kpi_name,
            'unit' => $parent->unit,
        ]);
    }

    public function forDepartment(string $dept): static
    {
        return $this->state(['department' => $dept]);
    }

    public function aggregation(string $aggregation): static
    {
        return $this->state(['aggregation' => $aggregation]);
    }

    public function milestone(): static
    {
        return $this->state(['aggregation' => 'milestone', 'target_value' => 100, 'unit' => '%']);
    }

    public function shared(): static
    {
        return $this->state(['aggregation' => 'shared']);
    }
}
