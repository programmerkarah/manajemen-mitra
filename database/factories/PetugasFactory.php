<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Petugas>
 */
class PetugasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'nik' => fake()->unique()->numerify('################'),
            'email' => fake()->unique()->safeEmail(),
            'telepon' => fake()->numerify('08##########'),
            'alamat' => fake()->address(),
            'pendidikan' => fake()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3']),
            'tahun_bergabung' => (int) fake()->year(),
            'jenis_petugas' => fake()->randomElement(['organik', 'non-organik']),
            'status' => 'aktif',
            'npwp' => null,
            'bank' => null,
            'no_rekening' => null,
            'nama_rekening' => null,
            'catatan' => null,
        ];
    }
}
