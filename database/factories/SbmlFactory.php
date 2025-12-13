<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sbml>
 */
class SbmlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tahun_anggaran' => fake()->numberBetween(2020, 2027),
            'jenis_kegiatan' => fake()->randomElement(['sensus', 'survei']),
            'status_kepegawaian' => fake()->randomElement(['organik', 'non_organik']),
            'jenis_penugasan' => fake()->randomElement(['pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan']),
            'honor_max' => fake()->numberBetween(3000000, 8000000),
            'keterangan' => fake()->sentence(),
            'status' => fake()->randomElement(['aktif', 'nonaktif']),
        ];
    }
}
