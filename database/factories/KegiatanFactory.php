<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kegiatan>
 */
class KegiatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_kegiatan' => 'KEG-'.$this->faker->year.'-'.$this->faker->unique()->randomNumber(3),
            'nama_kegiatan' => $this->faker->sentence(3),
            'jenis_kegiatan' => $this->faker->randomElement(['sensus', 'survei']),
            'deskripsi' => $this->faker->optional()->text(50),
            'tanggal_mulai' => $this->faker->date('Y-m-d'),
            'tanggal_selesai' => $this->faker->date('Y-m-d'),
            'tahun_anggaran' => $this->faker->year,
            'pagu_pencacahan' => $this->faker->randomFloat(2, 100000, 10000000),
            'kode_coa' => $this->faker->optional()->numerify('COA-####'),
            'ketua_tim_user_id' => fn () => \App\Models\User::factory(),
            'pj_lainnya_id' => fn () => \App\Models\User::factory(),
            'rate_honor_id' => null,
            'rate_honor_status' => 'pending',
            'rate_honor_approved_by' => null,
            'rate_honor_approved_at' => null,
            'rate_honor_notes' => null,
            'status' => 'draft',
            'tanggal_validasi' => null,
            'catatan' => $this->faker->optional()->text(30),
            'has_listing_updating' => false,
            'pagu_listing' => null,
            'metode_pendataan_pencacahan' => $this->faker->randomElement(['PAPI', 'CAPI']),
            'metode_pendataan_listing' => null,
            'metode_pelatihan' => $this->faker->randomElement(['daring', 'luring', 'hybrid', 'tidak_ada_pelatihan']),
            'bulan_pelatihan' => $this->faker->numberBetween(1, 12),
        ];
    }
}
