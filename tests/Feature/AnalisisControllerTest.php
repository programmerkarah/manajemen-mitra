<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\SkKpa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AnalisisControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_analisis_petugas(): void
    {
        $this->get(route('analisis.petugas'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_analisis_petugas(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Analisis/Petugas')
                ->has('distribusiJenisKelamin')
                ->has('distribusiKecamatan')
                ->has('distribusiUsia')
                ->has('alokasiPerBulan')
                ->has('petugasKegiatan')
                ->has('petugasAlokasiDetail')
                ->has('petugasList')
                ->has('currentYear')
            );
    }

    public function test_admin_can_access_analisis_pulsa(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('analisis.pulsa'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Analisis/Pulsa')
                ->has('pulsaPerBulan')
                ->has('rataRataPulsa')
                ->has('alokasiPulsaPerBulan')
                ->has('currentYear')
            );
    }

    public function test_admin_can_access_analisis_dokumen(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('analisis.dokumen'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Analisis/Dokumen')
                ->has('skPerBulan')
                ->has('spkPerBulan')
                ->has('skTotal')
                ->has('spkTotal')
                ->has('currentYear')
            );
    }

    public function test_admin_can_access_analisis_umum(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('analisis.umum'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Analisis/Umum')
                ->has('utilisasiAnggaran')
                ->has('distribusiBebanKerja')
                ->has('trenAlokasi')
                ->has('kelengkapanDokumen')
                ->has('currentYear')
            );
    }

    public function test_operator_can_access_analisis_petugas(): void
    {
        $user = User::factory()->operator()->create();

        $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();
    }

    public function test_pj_can_access_analisis_petugas(): void
    {
        $user = User::factory()->pj()->create();

        $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();
    }

    public function test_guest_role_cannot_access_analisis(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_sk_per_bulan_does_not_double_count_signed(): void
    {
        $user = User::factory()->admin()->create();
        $kegiatan = Kegiatan::factory()->create(['tahun_anggaran' => (int) date('Y')]);
        $currentYear = (int) date('Y');

        // 2 draft, 3 diterbitkan (not signed), 1 diterbitkan + signed
        SkKpa::query()->where('tahun', $currentYear)->where('bulan', 1)->delete();

        foreach (range(1, 2) as $i) {
            SkKpa::create([
                'nomor_sk' => "SK-DRAFT-{$i}",
                'kegiatan_id' => $kegiatan->id,
                'bulan' => 1,
                'tahun' => $currentYear,
                'tanggal_sk' => now(),
                'nama_kpa' => 'Test KPA',
                'perihal' => 'Test perihal',
                'status' => 'draft',
                'is_signed' => false,
                'created_by' => $user->id,
            ]);
        }

        foreach (range(1, 3) as $i) {
            SkKpa::create([
                'nomor_sk' => "SK-TERBIT-{$i}",
                'kegiatan_id' => $kegiatan->id,
                'bulan' => 1,
                'tahun' => $currentYear,
                'tanggal_sk' => now(),
                'nama_kpa' => 'Test KPA',
                'perihal' => 'Test perihal',
                'status' => 'diterbitkan',
                'is_signed' => false,
                'created_by' => $user->id,
            ]);
        }

        SkKpa::create([
            'nomor_sk' => 'SK-SIGNED-1',
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => $currentYear,
            'tanggal_sk' => now(),
            'nama_kpa' => 'Test KPA',
            'perihal' => 'Test perihal',
            'status' => 'diterbitkan',
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by' => $user->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analisis.dokumen'))
            ->assertOk();

        $skPerBulan = $response->original->getData()['page']['props']['skPerBulan'];
        $jan = collect($skPerBulan)->firstWhere('bulan', 1);

        $this->assertNotNull($jan);
        $this->assertEquals(6, $jan['total']);
        $this->assertEquals(2, $jan['draft']);
        $this->assertEquals(3, $jan['diterbitkan']);
        $this->assertEquals(1, $jan['ditandatangani']);

        // Verify no double-counting: draft + diterbitkan + ditandatangani = total
        $this->assertEquals(
            $jan['draft'] + $jan['diterbitkan'] + $jan['ditandatangani'],
            $jan['total'],
        );
    }
}
