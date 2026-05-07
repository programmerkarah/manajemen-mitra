<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
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
                ->has('petugasBelumDialokasikan')
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
                ->has('trenAlokasi.0.total_kegiatan')
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

    public function test_perubahan_zero_allocation_is_excluded_from_analisis_petugas(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $currentYear,
            'status' => 'draft',
        ]);

        $petugasAktif = Petugas::factory()->create([
            'nama' => 'Petugas Aktif',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $nadya = Petugas::factory()->create([
            'nama' => 'Nadya Salsabillah',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => $currentYear,
            'status' => 'dikirim',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => $currentYear,
            'status' => 'perubahan',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugasAktif->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 120000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $nadya->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 100000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugasAktif->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 150000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $nadya->id,
            'jumlah_satuan' => 0,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 0,
            'total_honor_listing' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $petugasDetail = collect($props['petugasAlokasiDetail']);

        $this->assertFalse($petugasDetail->pluck('petugas_nama')->contains('Nadya Salsabillah'));

        $januari = collect($props['alokasiPerBulan'])->firstWhere('bulan', 1);
        $this->assertNotNull($januari);
        $this->assertSame(1, $januari['jumlah_petugas']);
        $this->assertSame(1, $januari['jumlah_kegiatan']);
    }

    public function test_petugas_belum_dialokasikan_appears_in_analisis_petugas(): void
    {
        $user = User::factory()->admin()->create();

        $petugasBelum = Petugas::factory()->create([
            'nama' => 'Petugas Belum Alokasi',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasSudah = Petugas::factory()->create([
            'nama' => 'Petugas Sudah Alokasi',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => (int) date('Y'),
            'status' => 'draft',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => (int) date('Y'),
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugasSudah->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 100000,
            'total_honor_listing' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $belumDialokasikan = collect($props['petugasBelumDialokasikan']);

        $this->assertTrue($belumDialokasikan->pluck('nama')->contains('Petugas Belum Alokasi'));
        $this->assertFalse($belumDialokasikan->pluck('nama')->contains('Petugas Sudah Alokasi'));
    }

    public function test_petugas_belum_dialokasikan_empty_when_all_allocated(): void
    {
        $user = User::factory()->admin()->create();

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Aktif Dialokasikan',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => (int) date('Y'),
            'status' => 'draft',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => (int) date('Y'),
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 100000,
            'total_honor_listing' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $belumDialokasikan = collect($props['petugasBelumDialokasikan']);

        $this->assertFalse($belumDialokasikan->pluck('nama')->contains('Petugas Aktif Dialokasikan'));
    }

    public function test_admin_can_export_all_analisis_pdf(): void
    {
        $user = User::factory()->admin()->create();

        foreach ([
            'analisis.umum.export-pdf',
            'analisis.petugas.export-pdf',
            'analisis.pulsa.export-pdf',
            'analisis.dokumen.export-pdf',
        ] as $routeName) {
            $response = $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk();

            $contentType = (string) $response->headers->get('content-type');
            $disposition = (string) $response->headers->get('content-disposition');

            $this->assertStringContainsString('application/pdf', $contentType);
            $this->assertStringContainsString('.pdf', $disposition);
        }
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
