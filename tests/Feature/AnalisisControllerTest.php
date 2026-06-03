<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\SkKpa;
use App\Models\User;
use Carbon\Carbon;
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
                ->has('distribusiDesaKelurahan')
                ->has('distribusiTugasDesaKelurahan')
                ->has('distribusiUsia')
                ->has('alokasiPerBulan')
                ->has('petugasKegiatan')
                ->has('petugasAlokasiDetail')
                ->has('petugasList')
                ->has('petugasBelumDialokasikan')
                ->has('petugasRutin')
                ->has('currentYear')
            );
    }

    public function test_admin_can_access_analisis_petugas_organik(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('analisis.petugas-organik'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Analisis/PetugasOrganik')
                ->has('ringkasan')
                ->has('distribusiBebanKerja')
                ->has('trenBebanKerja')
                ->has('bebanKerjaDetail')
                ->has('currentYear')
            );
    }

    public function test_analisis_petugas_organik_uses_new_performance_thresholds(): void
    {
        $user = User::factory()->admin()->create();
        $previousNow = Carbon::getTestNow();
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 12, 0, 0));

        try {
            $currentYear = 2026;

            $petugasUnder = Petugas::factory()->create([
                'nama' => 'Petugas Under',
                'jenis_petugas' => 'organik',
                'status' => 'aktif',
            ]);

            $petugasNormal = Petugas::factory()->create([
                'nama' => 'Petugas Normal',
                'jenis_petugas' => 'organik',
                'status' => 'aktif',
            ]);

            $petugasOptimal = Petugas::factory()->create([
                'nama' => 'Petugas Optimal',
                'jenis_petugas' => 'organik',
                'status' => 'aktif',
            ]);

            $petugasOverload = Petugas::factory()->create([
                'nama' => 'Petugas Overload',
                'jenis_petugas' => 'organik',
                'status' => 'aktif',
            ]);

            $petugasCases = [
                $petugasUnder->id => 0,
                $petugasNormal->id => 1,
                $petugasOptimal->id => 6,
                $petugasOverload->id => 11,
            ];

            foreach ($petugasCases as $petugasId => $jumlahKegiatan) {
                for ($index = 1; $index <= $jumlahKegiatan; $index++) {
                    $kegiatan = Kegiatan::factory()->create([
                        'tahun_anggaran' => $currentYear,
                        'status' => 'aktif',
                    ]);

                    $bulanIndex = $petugasId === $petugasOptimal->id || $petugasId === $petugasOverload->id
                        ? (string) (($index - 1) % 3 + 1)
                        : '6';

                    $periode = PeriodeAlokasi::factory()->create([
                        'kegiatan_id' => $kegiatan->id,
                        'bulan' => str_pad($bulanIndex, 2, '0', STR_PAD_LEFT),
                        'tahun' => $currentYear,
                        'status' => 'dikirim',
                    ]);

                    AlokasiPetugas::factory()->create([
                        'periode_alokasi_id' => $periode->id,
                        'petugas_id' => $petugasId,
                        'status_kepegawaian' => 'organik',
                        'total_honor' => 100000,
                        'total_honor_listing' => 0,
                    ]);
                }
            }

            $response = $this->actingAs($user)
                ->get(route('analisis.petugas-organik'))
                ->assertOk();

            $props = $response->original->getData()['page']['props'];
            $detail = collect($props['bebanKerjaDetail'])->keyBy('petugas_nama');

            $this->assertSame('under_performance', $detail->get('Petugas Under')['performance_status']);
            $this->assertSame('Under Performance', $detail->get('Petugas Under')['performance_label']);
            $this->assertSame('normal', $detail->get('Petugas Normal')['performance_status']);
            $this->assertSame('Normal', $detail->get('Petugas Normal')['performance_label']);
            $this->assertSame('optimal', $detail->get('Petugas Optimal')['performance_status']);
            $this->assertSame('Optimal', $detail->get('Petugas Optimal')['performance_label']);
            $this->assertSame('overload', $detail->get('Petugas Overload')['performance_status']);
            $this->assertSame('Overload', $detail->get('Petugas Overload')['performance_label']);
        } finally {
            Carbon::setTestNow($previousNow);
        }
    }

    public function test_analisis_petugas_organik_counts_draft_allocations(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $petugas = Petugas::factory()->create([
            'nama' => 'Pegawai Organik Uji',
            'jenis_petugas' => 'organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $currentYear,
            'status' => 'draft',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '02',
            'tahun' => $currentYear,
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'organik',
            'total_honor' => 150000,
            'total_honor_listing' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas-organik'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $detail = collect($props['bebanKerjaDetail'])->firstWhere('petugas_nama', 'Pegawai Organik Uji');

        $this->assertNotNull($detail);
        $this->assertSame(1, $detail['jumlah_kegiatan']);
        $this->assertSame(1, $detail['jumlah_alokasi']);
    }

    public function test_analisis_petugas_weights_sensus_honor_per_month(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Sensus Analisis',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasZeroHonor = Petugas::factory()->create([
            'nama' => 'Petugas Sensus Analisis Zero Honor',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Sensus Ekonomi 2026',
            'jenis_kegiatan' => 'sensus',
            'tahun_anggaran' => $currentYear,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-06-15',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $kegiatanZeroHonor = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan Tanpa Honor',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $currentYear,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-06-01',
            'tanggal_selesai' => '2026-06-30',
        ]);

        foreach ([6, 7, 8] as $bulan) {
            $bulanValue = $bulan === 6 ? '6' : str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => $bulanValue,
                'tahun' => $currentYear,
                'status' => 'dikirim',
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugas->id,
                'status_kepegawaian' => 'non_organik',
                'total_honor' => 250000,
                'total_honor_listing' => 0,
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugasZeroHonor->id,
                'status_kepegawaian' => 'non_organik',
                'total_honor' => 0,
                'total_honor_listing' => 0,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $detail = collect($props['petugasAlokasiDetail'])->firstWhere('petugas_nama', 'Petugas Sensus Analisis');
        $detailZeroHonor = collect($props['petugasAlokasiDetail'])->firstWhere('petugas_nama', 'Petugas Sensus Analisis Zero Honor');

        $this->assertNotNull($detail);
        $this->assertNotNull($detailZeroHonor);
        $this->assertEquals(0, $detail['honor'][6]);
        $this->assertEquals(100000, $detail['honor'][7]);
        $this->assertEquals(150000, $detail['honor'][8]);
        $this->assertEquals(250000, $detail['total_honor']);
        $this->assertEquals(0, $detailZeroHonor['honor'][6]);
        $this->assertEquals(0, $detailZeroHonor['total_honor']);

        $alokasiJuni = collect($props['alokasiPerBulan'])->firstWhere('bulan', 6);
        $alokasiJuli = collect($props['alokasiPerBulan'])->firstWhere('bulan', 7);
        $alokasiAgustus = collect($props['alokasiPerBulan'])->firstWhere('bulan', 8);

        $this->assertNotNull($alokasiJuni);
        $this->assertNotNull($alokasiJuli);
        $this->assertNotNull($alokasiAgustus);
        $this->assertSame(2, $alokasiJuni['jumlah_petugas']);
        $this->assertSame(2, $alokasiJuli['jumlah_petugas']);
        $this->assertSame(2, $alokasiAgustus['jumlah_petugas']);
        $this->assertSame(1, $alokasiJuni['jumlah_kegiatan']);
        $this->assertSame(1, $alokasiJuli['jumlah_kegiatan']);
        $this->assertSame(1, $alokasiAgustus['jumlah_kegiatan']);
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

    public function test_analisis_umum_weights_sensus_honor_in_tren_alokasi(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Sensus Umum',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasOrganik = Petugas::factory()->create([
            'nama' => 'Petugas Sensus Umum Organik',
            'jenis_petugas' => 'organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Sensus Ekonomi 2026',
            'jenis_kegiatan' => 'sensus',
            'tahun_anggaran' => $currentYear,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-06-15',
            'tanggal_selesai' => '2026-08-31',
        ]);

        foreach ([6, 7, 8] as $bulan) {
            $bulanValue = $bulan === 6 ? '6' : str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => $bulanValue,
                'tahun' => $currentYear,
                'status' => 'dikirim',
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugas->id,
                'status_kepegawaian' => 'non_organik',
                'total_honor' => 250000,
                'total_honor_listing' => 0,
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugasOrganik->id,
                'status_kepegawaian' => 'organik',
                'total_honor' => 250000,
                'total_honor_listing' => 0,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('analisis.umum'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $trenAlokasi = collect($props['trenAlokasi']);
        $juni = $trenAlokasi->firstWhere('bulan', 6);
        $juli = $trenAlokasi->firstWhere('bulan', 7);
        $agustus = $trenAlokasi->firstWhere('bulan', 8);

        $this->assertNotNull($juni);
        $this->assertNotNull($juli);
        $this->assertNotNull($agustus);
        $this->assertEquals(100000, $juni['total_honor']);
        $this->assertEquals(200000, $juli['total_honor']);
        $this->assertEquals(200000, $agustus['total_honor']);
        $this->assertEquals(2, $juni['jumlah_petugas']);
        $this->assertEquals(2, $juli['jumlah_petugas']);
        $this->assertEquals(2, $agustus['jumlah_petugas']);
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

    public function test_petugas_rutin_includes_recurring_kegiatan(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Rutin Test',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $currentYear,
            'status' => 'draft',
            'tanggal_mulai' => $currentYear.'-01-01',
            'tanggal_selesai' => $currentYear.'-06-30',
        ]);

        foreach (['01', '04'] as $bulan) {
            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => $bulan,
                'tahun' => $currentYear,
                'status' => 'dikirim',
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugas->id,
                'status_kepegawaian' => 'non_organik',
                'total_honor' => 100000,
                'total_honor_listing' => 0,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $petugasRutin = collect($props['petugasRutin']);

        $this->assertTrue($petugasRutin->pluck('petugas_nama')->contains('Petugas Rutin Test'));

        $rutinEntry = $petugasRutin->firstWhere('petugas_nama', 'Petugas Rutin Test');
        $this->assertSame(1, $rutinEntry['jumlah_kegiatan_rutin']);
        $this->assertSame(2, $rutinEntry['kegiatan_rutin'][0]['jumlah_bulan']);
    }

    public function test_petugas_rutin_excludes_single_month_kegiatan(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Sekali Test',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $currentYear,
            'status' => 'draft',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $currentYear,
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
        $petugasRutin = collect($props['petugasRutin']);

        $this->assertFalse($petugasRutin->pluck('petugas_nama')->contains('Petugas Sekali Test'));
    }

    public function test_petugas_rutin_excludes_short_range_kegiatan(): void
    {
        $user = User::factory()->admin()->create();
        $currentYear = (int) date('Y');

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Kegiatan Pendek',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        // Kegiatan with range <= 2 months (only 1 month apart)
        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $currentYear,
            'status' => 'draft',
            'tanggal_mulai' => $currentYear.'-01-01',
            'tanggal_selesai' => $currentYear.'-02-28',
        ]);

        foreach (['01', '02'] as $bulan) {
            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => $bulan,
                'tahun' => $currentYear,
                'status' => 'dikirim',
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugas->id,
                'status_kepegawaian' => 'non_organik',
                'total_honor' => 100000,
                'total_honor_listing' => 0,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('analisis.petugas'))
            ->assertOk();

        $props = $response->original->getData()['page']['props'];
        $petugasRutin = collect($props['petugasRutin']);

        $this->assertFalse($petugasRutin->pluck('petugas_nama')->contains('Petugas Kegiatan Pendek'));
    }

    public function test_admin_can_export_all_analisis_pdf(): void
    {
        $user = User::factory()->admin()->create();

        foreach ([
            'analisis.umum.export-pdf',
            'analisis.petugas.export-pdf',
            'analisis.petugas-organik.export-pdf',
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
