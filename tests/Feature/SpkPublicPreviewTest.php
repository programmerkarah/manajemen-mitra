<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SpkPublicPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.recaptcha.enabled', false);
    }

    public function test_public_preview_form_can_be_accessed_without_login(): void
    {
        $response = $this->get('/mitra');

        $response->assertOk();
    }

    public function test_public_preview_survei_is_blocked_when_month_has_draft_periode(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatanSurvei = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Sosial Ekonomi',
        ]);

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurvei->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurvei->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Survei',
            'nik' => '3201123412345678',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'total_honor' => 300000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $response = $this->post(
            '/mitra',
            [
                'nama' => 'Petugas Survei',
                'nik' => '3201123412345678',
                'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                'jenis_kegiatan' => 'survei',
                'survei_periode' => sprintf('%d-03', $tahun),
                'recaptcha_token' => 'test-recaptcha-token',
                'aksi' => 'preview',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Preview SPK survei belum dapat dilakukan karena masih ada kegiatan draft pada bulan tersebut.',
        ]);
    }

    public function test_public_preview_sensus_is_blocked_when_kegiatan_not_submitted(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatanSensus = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'sensus',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Sensus Pertanian',
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSensus->id,
            'bulan' => '04',
            'tahun' => $tahun,
            'status' => 'draft',
            'jenis_kegiatan' => 'sensus',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Sensus',
            'nik' => '3201123412340001',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $response = $this->post(
            '/mitra',
            [
                'nama' => 'Petugas Sensus',
                'nik' => '3201123412340001',
                'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                'jenis_kegiatan' => 'sensus',
                'sensus_kegiatan' => $kegiatanSensus->hashed_id,
                'recaptcha_token' => 'test-recaptcha-token',
                'aksi' => 'preview',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Kegiatan sensus belum dikirim sehingga preview belum tersedia.',
        ]);
    }

    public function test_public_preview_can_return_pdf_for_valid_survei_request(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatanSurvei = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Ongkos Hidup',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurvei->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Valid',
            'nik' => '3201123412349999',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'total_honor' => 300000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        Penandatangan::query()->create([
            'nama' => 'PPK Valid',
            'nip' => '198001012010011001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'PPK',
            'periode_mulai' => now()->subYear()->toDateString(),
            'periode_selesai' => now()->addYear()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->post(
            '/mitra',
            [
                'nama' => 'Petugas Valid',
                'nik' => '3201123412349999',
                'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                'jenis_kegiatan' => 'survei',
                'survei_periode' => sprintf('%d-05', $tahun),
                'recaptcha_token' => 'test-recaptcha-token',
                'aksi' => 'preview',
            ],
            [
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_options_only_return_allocations_owned_by_petugas(): void
    {
        $tahun = ActiveYearService::get();

        $satuan = Satuan::factory()->create([
            'kode' => 'RT',
            'nama' => 'Rumah Tangga',
            'status' => 'aktif',
        ]);

        $kegiatanSurveiOwned = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Harga Komoditas',
        ]);

        $kegiatanSensusOwned = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'sensus',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $kegiatanSurveiOther = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Lain',
        ]);

        $periodeSurveiOwned = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurveiOwned->id,
            'bulan' => '07',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatanSurveiOwned->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor survei',
            'satuan_id' => $satuan->id,
            'rate' => 48000,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        $periodeSensusOwned = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSensusOwned->id,
            'bulan' => '08',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'sensus',
        ]);

        $periodeSurveiOther = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurveiOther->id,
            'bulan' => '09',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $ownedPetugas = Petugas::factory()->create([
            'nama' => 'Mitra Opsi',
            'nik' => '3201123412311111',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $otherPetugas = Petugas::factory()->create([
            'nama' => 'Mitra Lain',
            'nik' => '3201123412322222',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeSurveiOwned->id,
            'petugas_id' => $ownedPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 150000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeSensusOwned->id,
            'petugas_id' => $ownedPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 175000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeSurveiOther->id,
            'petugas_id' => $otherPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'total_honor' => 210000,
            'total_honor_listing' => 0,
        ]);

        $response = $this->post(
            '/mitra/options',
            [
                'nama' => 'Mitra Opsi',
                'nik' => '3201123412311111',
                'telepon_4_digit' => $this->lastFourDigits((string) $ownedPetugas->telepon),
                'recaptcha_token' => 'test-recaptcha-token',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'survei_periods');
        $response->assertJsonCount(1, 'sensus_kegiatans');
        $response->assertJsonCount(2, 'penugasan_list');
        $response->assertJsonPath('survei_periods.0.value', sprintf('%d-07', $tahun));
        $response->assertJsonPath('sensus_kegiatans.0.label', 'Sensus Ekonomi');
        $response->assertJsonPath('penugasan_list.0.nama_kegiatan', 'Survei Harga Komoditas');
        $this->assertStringContainsString('Rumah Tangga', $response->getContent());
    }

    public function test_public_options_show_document_status_consistent_in_same_month(): void
    {
        $tahun = ActiveYearService::get();

        $satuan = Satuan::factory()->create([
            'kode' => 'RT2',
            'nama' => 'Rumah Tangga',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Final Seragam',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor survei final',
            'satuan_id' => $satuan->id,
            'rate' => 50000,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        $kegiatanLain = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Final Seragam Lain',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatanLain->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor survei final lain',
            'satuan_id' => $satuan->id,
            'rate' => 50000,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '11',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeLain = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanLain->id,
            'bulan' => '11',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Final Bulanan',
            'nik' => '3201123412399999',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasiFinal = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'total_honor' => 150000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $alokasiLain = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeLain->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'total_honor' => 100000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $signedDirectory = public_path('spk-export/tests');
        if (! is_dir($signedDirectory)) {
            mkdir($signedDirectory, 0755, true);
        }

        $signedRelativePath = 'spk-export/tests/final_same_month_test.pdf';
        $signedAbsolutePath = public_path($signedRelativePath);
        file_put_contents($signedAbsolutePath, Pdf::loadHTML('<h1>PK Final Bulanan</h1>')->output());

        try {
            Spk::query()->create([
                'nomor_spk' => 'PPIS/13730/123/K/'.$tahun,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasiFinal->id,
                'addendum_number' => 0,
                'nomor_urut_base' => 123,
                'tanggal_spk' => now()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja final bulanan',
                'nilai_kontrak' => 150000,
                'nama_ppk' => 'PPK Final',
                'nip_ppk' => '198001012010011001',
                'signed_file_path' => $signedRelativePath,
                'status' => 'diterbitkan',
                'created_by' => User::factory()->create()->id,
            ]);

            $response = $this->post(
                '/mitra/options',
                [
                    'nama' => 'Petugas Final Bulanan',
                    'nik' => '3201123412399999',
                    'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                    'recaptcha_token' => 'test-recaptcha-token',
                ],
                [
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ]
            );

            $response->assertOk();
            $response->assertJsonCount(2, 'penugasan_list');

            $statuses = collect($response->json('penugasan_list'))
                ->pluck('document_status')
                ->sort()
                ->values()
                ->all();

            $this->assertSame(['PK Final', 'PK Final'], $statuses);
        } finally {
            @unlink($signedAbsolutePath);
        }
    }

    public function test_public_options_reject_when_phone_verification_does_not_match(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Verifikasi HP',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Verifikasi HP',
            'nik' => '3201123412310101',
            'telepon' => '081299991234',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 125000,
            'total_honor_listing' => 0,
        ]);

        $response = $this->post(
            '/mitra/options',
            [
                'nama' => 'Petugas Verifikasi HP',
                'nik' => '3201123412310101',
                'telepon_4_digit' => '9999',
                'recaptcha_token' => 'test-recaptcha-token',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Verifikasi 4 digit nomor HP tidak sesuai.',
        ]);
    }

    public function test_public_preview_uses_signed_final_file_when_available(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Final',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Final',
            'nik' => '3201123412347777',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'total_honor' => 400000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $signedDirectory = public_path('spk-export/tests');
        if (! is_dir($signedDirectory)) {
            mkdir($signedDirectory, 0755, true);
        }

        $signedRelativePath = 'spk-export/tests/final_signed_preview_test.pdf';
        $signedAbsolutePath = public_path($signedRelativePath);
        file_put_contents($signedAbsolutePath, Pdf::loadHTML('<h1>PK Final Signed</h1>')->output());

        try {
            Spk::query()->create([
                'nomor_spk' => 'PPIS/13730/123/K/'.$tahun,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasi->id,
                'addendum_number' => 0,
                'nomor_urut_base' => 123,
                'tanggal_spk' => now()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja final signed',
                'nilai_kontrak' => 400000,
                'nama_ppk' => 'PPK Final',
                'nip_ppk' => '198001012010011001',
                'signed_file_path' => $signedRelativePath,
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            $response = $this->post(
                '/mitra',
                [
                    'nama' => 'Petugas Final',
                    'nik' => '3201123412347777',
                    'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                    'jenis_kegiatan' => 'survei',
                    'survei_periode' => sprintf('%d-06', $tahun),
                    'recaptcha_token' => 'test-recaptcha-token',
                    'aksi' => 'preview',
                ],
                [
                    'X-Requested-With' => 'XMLHttpRequest',
                ]
            );

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $response->assertHeader('content-disposition');
        } finally {
            @unlink($signedAbsolutePath);
        }
    }

    public function test_public_preview_merges_signed_main_and_addendum_when_available(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Addendum Final',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '10',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Addendum Final',
            'nik' => '3201123412399999',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'total_honor' => 500000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $signedDirectory = public_path('spk-export/tests');
        if (! is_dir($signedDirectory)) {
            mkdir($signedDirectory, 0755, true);
        }

        $mainSignedRelativePath = 'spk-export/tests/final_signed_main_addendum_test.pdf';
        $mainSignedAbsolutePath = public_path($mainSignedRelativePath);
        file_put_contents($mainSignedAbsolutePath, Pdf::loadHTML('<h1>PK Final Main</h1>')->output());

        $addendumSignedRelativePath = 'spk-export/tests/final_signed_addendum_test.pdf';
        $addendumSignedAbsolutePath = public_path($addendumSignedRelativePath);
        file_put_contents($addendumSignedAbsolutePath, Pdf::loadHTML('<h1>PK Final Addendum</h1>')->output());

        try {
            $mainSpk = Spk::query()->create([
                'nomor_spk' => 'PPIS/13730/201/K/'.$tahun,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasi->id,
                'addendum_number' => 0,
                'nomor_urut_base' => 201,
                'tanggal_spk' => now()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja final utama',
                'nilai_kontrak' => 500000,
                'nama_ppk' => 'PPK Final',
                'nip_ppk' => '198001012010011001',
                'signed_file_path' => $mainSignedRelativePath,
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            Spk::query()->create([
                'nomor_spk' => 'PPIS/13730/201/ADD-1/K/'.$tahun,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasi->id,
                'parent_spk_id' => $mainSpk->id,
                'addendum_number' => 1,
                'nomor_urut_base' => 201,
                'tanggal_spk' => now()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja final addendum',
                'nilai_kontrak' => 500000,
                'nama_ppk' => 'PPK Final',
                'nip_ppk' => '198001012010011001',
                'signed_file_path' => $addendumSignedRelativePath,
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            $response = $this->post(
                '/mitra',
                [
                    'nama' => 'Petugas Addendum Final',
                    'nik' => '3201123412399999',
                    'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                    'jenis_kegiatan' => 'survei',
                    'survei_periode' => sprintf('%d-10', $tahun),
                    'recaptcha_token' => 'test-recaptcha-token',
                    'aksi' => 'preview',
                ],
                [
                    'X-Requested-With' => 'XMLHttpRequest',
                ]
            );

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $contentDisposition = (string) $response->headers->get('content-disposition');
            $this->assertStringContainsString('_with_addendum.pdf', $contentDisposition);
        } finally {
            @unlink($mainSignedAbsolutePath);
            @unlink($addendumSignedAbsolutePath);
        }
    }

    public function test_public_preview_uses_main_signed_when_addendum_exists_but_not_signed(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Addendum Draft',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '09',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Addendum Draft',
            'nik' => '3201123412319191',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'total_honor' => 420000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $signedDirectory = public_path('spk-export/tests');
        if (! is_dir($signedDirectory)) {
            mkdir($signedDirectory, 0755, true);
        }

        $mainSignedRelativePath = 'spk-export/tests/final_signed_main_addendum_draft_test.pdf';
        $mainSignedAbsolutePath = public_path($mainSignedRelativePath);
        file_put_contents($mainSignedAbsolutePath, Pdf::loadHTML('<h1>PK Final Main Draft Addendum</h1>')->output());

        try {
            $mainSpk = Spk::query()->create([
                'nomor_spk' => 'PPIS/13730/301/K/'.$tahun,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasi->id,
                'addendum_number' => 0,
                'nomor_urut_base' => 301,
                'tanggal_spk' => now()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja final utama',
                'nilai_kontrak' => 420000,
                'nama_ppk' => 'PPK Final',
                'nip_ppk' => '198001012010011001',
                'signed_file_path' => $mainSignedRelativePath,
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            Spk::query()->create([
                'nomor_spk' => 'PPIS/13730/301/ADD-1/K/'.$tahun,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasi->id,
                'parent_spk_id' => $mainSpk->id,
                'addendum_number' => 1,
                'nomor_urut_base' => 301,
                'tanggal_spk' => now()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja addendum draft',
                'nilai_kontrak' => 420000,
                'nama_ppk' => 'PPK Final',
                'nip_ppk' => '198001012010011001',
                'signed_file_path' => null,
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            $optionsResponse = $this->post(
                '/mitra/options',
                [
                    'nama' => 'Petugas Addendum Draft',
                    'nik' => '3201123412319191',
                    'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                    'recaptcha_token' => 'test-recaptcha-token',
                ],
                [
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ]
            );

            $optionsResponse->assertOk();
            $optionsResponse->assertJsonPath('penugasan_list.0.document_status', 'PK Final + Addendum(draft)');

            $downloadResponse = $this->post(
                '/mitra',
                [
                    'nama' => 'Petugas Addendum Draft',
                    'nik' => '3201123412319191',
                    'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                    'jenis_kegiatan' => 'survei',
                    'survei_periode' => sprintf('%d-09', $tahun),
                    'recaptcha_token' => 'test-recaptcha-token',
                    'aksi' => 'download',
                ],
                [
                    'X-Requested-With' => 'XMLHttpRequest',
                ]
            );

            $downloadResponse->assertOk();
            $downloadResponse->assertHeader('content-type', 'application/pdf');
            $contentDisposition = (string) $downloadResponse->headers->get('content-disposition');
            $this->assertStringNotContainsString('_with_addendum.pdf', $contentDisposition);
        } finally {
            @unlink($mainSignedAbsolutePath);
        }
    }

    public function test_public_options_falls_back_to_non_empty_target_when_honor_exists(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Tanpa Rate Honor',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '12',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Fallback',
            'nik' => '3201123412348888',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 0,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'total_honor' => 238000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $response = $this->post(
            '/mitra/options',
            [
                'nama' => 'Petugas Fallback',
                'nik' => '3201123412348888',
                'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                'recaptcha_token' => 'test-recaptcha-token',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('penugasan_list.0.target_pekerjaan', '1 paket');
    }

    public function test_public_options_use_frame_sample_narrative_for_sensus_target(): void
    {
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'sensus',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Sensus Frame Sampel',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'sensus',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Frame',
            'nik' => '3201123412344444',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $unitRumahTangga = MasterUnitSampel::query()->create([
            'nama' => 'Rumah Tangga',
            'kode' => 'RTX',
            'is_active' => true,
        ]);

        $unitUsaha = MasterUnitSampel::query()->create([
            'nama' => 'Usaha',
            'kode' => 'USX',
            'is_active' => true,
        ]);

        $masterFrame = MasterFrameSampel::query()->create([
            'nama' => 'Frame Sampel Utama',
            'kode' => 'FSX',
            'is_active' => true,
        ]);

        $frameRow = KegiatanFrameSampel::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'nama_frame' => 'Frame Sampel Utama',
            'target_unit_sampel' => [
                $unitRumahTangga->id => 2,
                $unitUsaha->id => 1,
            ],
        ]);

        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 0,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'total_honor' => 125000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugasFrameSampel::query()->create([
            'alokasi_petugas_id' => $alokasi->id,
            'kegiatan_frame_sampel_id' => $frameRow->id,
        ]);

        $response = $this->post(
            '/mitra/options',
            [
                'nama' => 'Petugas Frame',
                'nik' => '3201123412344444',
                'telepon_4_digit' => $this->lastFourDigits((string) $petugas->telepon),
                'recaptcha_token' => 'test-recaptcha-token',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'penugasan_list');
        $response->assertJsonPath(
            'penugasan_list.0.target_pekerjaan',
            '1 SLS/sub-SLS dan/atau 2 rumah tangga/1 usaha',
        );
    }

    private function lastFourDigits(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return substr(str_pad($digits, 4, '0', STR_PAD_LEFT), -4);
    }
}
