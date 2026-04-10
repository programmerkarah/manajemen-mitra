<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlokasiPetugas>
 */
class AlokasiPetugasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periode_alokasi_id' => \App\Models\PeriodeAlokasi::factory(),
            'petugas_id' => \App\Models\Petugas::factory(),
            'jumlah_satuan' => fake()->numberBetween(1, 100),
            'total_honor' => fake()->randomFloat(2, 100000, 10000000),
            'peran' => fake()->randomElement(['pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan']),
            'status_kepegawaian' => fake()->randomElement(['organik', 'non_organik']),
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
