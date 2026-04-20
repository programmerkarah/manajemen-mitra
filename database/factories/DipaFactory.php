<?php

namespace Database\Factories;

use App\Models\Dipa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dipa>
 */
class DipaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_dipa' => fake()->unique()->numerify('DIPA-####-####'),
            'tahun' => (int) fake()->year(),
            'tanggal_dipa' => fake()->date(),
            'is_active' => true,
        ];
    }
}
