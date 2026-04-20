<?php

namespace Database\Factories;

use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlokasiPetugas>
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
            'periode_alokasi_id' => PeriodeAlokasi::factory(),
            'petugas_id' => Petugas::factory(),
            'jumlah_satuan' => fake()->numberBetween(1, 100),
            'total_honor' => fake()->randomFloat(2, 100000, 10000000),
            'peran' => fake()->randomElement(['pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan']),
            'status_kepegawaian' => fake()->randomElement(['organik', 'non_organik']),
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
