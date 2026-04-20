<?php

namespace Database\Factories;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kegiatan>
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
            'ketua_tim_user_id' => fn () => User::factory(),
            'pj_lainnya_id' => fn () => User::factory(),
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
