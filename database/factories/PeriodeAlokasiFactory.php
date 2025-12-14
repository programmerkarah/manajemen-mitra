<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PeriodeAlokasi>
 */
class PeriodeAlokasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kegiatan_id' => \App\Models\Kegiatan::factory(),
            'bulan' => str_pad(fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT),
            'tahun' => fake()->numberBetween(2020, 2025),
            'jenis_kegiatan' => fake()->randomElement(['sensus', 'survei']),
            'status' => 'draft',
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diajukan',
            'submitted_by' => \App\Models\User::factory(),
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disetujui',
            'submitted_by' => \App\Models\User::factory(),
            'submitted_at' => now()->subDays(2),
            'approved_by' => \App\Models\User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'submitted_by' => \App\Models\User::factory(),
            'submitted_at' => now()->subDays(2),
            'rejected_by' => \App\Models\User::factory(),
            'rejected_at' => now(),
            'catatan' => fake()->sentence(),
        ]);
    }
}
