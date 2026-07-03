<?php

namespace Tests\Feature;

use App\Http\Controllers\AlokasiPetugasController;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonitoringSkgbPdfExportControllerStub extends AlokasiPetugasController
{
    public function buildReportData(Kegiatan $kegiatan, PeriodeAlokasi $periode, ?Penandatangan $kepala = null): array
    {
        return $this->buildMonitoringReportData($kegiatan, $periode, $kepala);
    }
}

class MonitoringSkgbPdfExportTest extends TestCase
{
    public function test_it_builds_monitoring_pdf_data_with_dynamic_metadata_and_next_workday_signature_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-04 10:00:00'));

        $controller = new MonitoringSkgbPdfExportControllerStub;

        $ketuaTim = new User([
            'name' => 'Ketua Tim Monitoring',
        ]);

        $kegiatan = new Kegiatan([
            'id' => 1,
            'kode_kegiatan' => 'MON-2026-001',
            'nama_kegiatan' => 'Monitoring Penggilingan',
            'jenis_kegiatan' => 'survei',
            'metode_sampling' => Kegiatan::METODE_SAMPLING_PURPOSSIVE,
            'tahun_anggaran' => 2026,
            'tanggal_mulai' => '2026-06-01',
            'tanggal_selesai' => '2026-06-06',
        ]);
        $kegiatan->setRelation('ketuaTim', $ketuaTim);

        $frame = new KegiatanFrameSampel([
            'id' => 11,
            'kegiatan_id' => 1,
            'tahapan' => 'pencacahan',
            'nama_target' => 'UD Penggilingan Sinar Pagi',
            'sample_role' => 'utama',
            'is_active' => true,
            'nama_frame' => 'UD Penggilingan Sinar Pagi',
            'kode_kecamatan' => '010',
            'kode_desa' => '002',
            'kode_sls' => '001',
            'kode_sub_sls' => '001',
            'kode_segmen' => '03',
            'kdkec' => '010',
            'kdkec_label' => 'Sawahlunto Utara',
            'kddes' => '002',
            'kddes_label' => 'Desa A',
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Sawahlunto Utara',
                'kddes' => '002',
                'kddes_label' => 'Desa A',
                'nks' => '0102001',
                'nama_usaha_penggilingan' => 'UD Penggilingan Sinar Pagi',
                'pemilik' => 'Budi',
            ],
            'target_unit_sampel' => [
                '1' => 3,
                '2' => 2,
            ],
        ]);
        $kegiatan->setRelation('kegiatanFrameSampel', collect([$frame]));

        $petugas = new Petugas([
            'id' => 7,
            'nama' => 'Petugas Penggilingan',
            'jenis_petugas' => 'organik',
        ]);

        $alokasi = new AlokasiPetugas([
            'id' => 21,
            'periode_alokasi_id' => 41,
            'petugas_id' => 7,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 5,
            'jumlah_unit_sampel' => 5,
            'jumlah_satuan_listing' => 5,
            'total_honor' => 250000,
            'total_honor_listing' => 0,
        ]);
        $alokasi->setRelation('petugas', $petugas);

        $frameAllocation = new AlokasiPetugasFrameSampel([
            'id' => 31,
            'alokasi_petugas_id' => 21,
            'kegiatan_frame_sampel_id' => 11,
            'is_non_response' => false,
        ]);
        $frameAllocation->setRelation('kegiatanFrameSampel', $frame);
        $alokasi->setRelation('frameSampelAllocations', collect([$frameAllocation]));

        $periode = new PeriodeAlokasi([
            'id' => 41,
            'kegiatan_id' => 1,
            'bulan' => '06',
            'tahun' => 2026,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
            'tanggal_mulai' => '2026-06-01',
            'tanggal_selesai' => '2026-06-06',
        ]);
        $periode->setRelation('alokasiPetugas', collect([$alokasi]));

        $kepala = new Penandatangan([
            'nama' => 'Kepala BPS Kota Sawahlunto',
        ]);

        $reportData = $controller->buildReportData($kegiatan, $periode, $kepala);

        $this->assertSame('Monitoring Monitoring Penggilingan', $reportData['judul']);
        $this->assertSame('Badan Pusat Statistik Kota Sawahlunto', $reportData['lokasi']);
        $this->assertSame('Ketua Tim Monitoring', $reportData['ketua_tim_nama']);
        $this->assertSame('Kepala BPS Kota Sawahlunto', $reportData['kepala_nama']);
        $this->assertSame('08 Juni 2026', $reportData['tanggal_pengesahan']);
        $metadataLabels = array_column($reportData['frame_metadata_columns'], 'label');
        $metadataCodes = array_column($reportData['frame_metadata_columns'], 'code');
        $this->assertSame(['kode_kecamatan', 'kode_desa'], array_slice($metadataCodes, 0, 2));
        $this->assertSame(1, array_count_values($metadataLabels)['Kecamatan'] ?? 0);
        $this->assertSame(1, array_count_values($metadataLabels)['Desa/Kelurahan'] ?? 0);
        $this->assertFalse(in_array('Nama Usaha', $metadataLabels, true));
        $this->assertCount(1, $reportData['rows']);
        $this->assertTrue($reportData['show_nama_usaha_column']);
        $this->assertSame(1, $reportData['summary']['total_frame']);

        $html = view('monitoring-pdf', $reportData)->render();

        $this->assertStringContainsString('Badan Pusat Statistik Kota Sawahlunto', $html);
        $this->assertStringContainsString('Ketua Tim Monitoring', $html);
        $this->assertStringContainsString('Kepala BPS Kota Sawahlunto', $html);
        $this->assertStringNotContainsString('SKGB', $html);
        $this->assertStringContainsString('[010] Sawahlunto Utara', $html);
        $this->assertStringContainsString('[002] Desa A', $html);
        $this->assertStringContainsString('Nama Usaha', $html);
        $this->assertStringContainsString('UD Penggilingan Sinar Pagi', $html);
    }

    public function test_it_hides_nama_usaha_column_for_targeted_sampling(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-04 10:00:00'));

        $controller = new MonitoringSkgbPdfExportControllerStub;

        $kegiatan = new Kegiatan([
            'id' => 1,
            'kode_kegiatan' => 'MON-2026-001',
            'nama_kegiatan' => 'Monitoring Penggilingan',
            'jenis_kegiatan' => 'survei',
            'metode_sampling' => Kegiatan::METODE_SAMPLING_TARGETED,
            'tahun_anggaran' => 2026,
            'tanggal_mulai' => '2026-06-01',
            'tanggal_selesai' => '2026-06-06',
        ]);

        $kegiatan->setRelation('ketuaTim', new User(['name' => 'Ketua Tim Monitoring']));

        $frame = new KegiatanFrameSampel([
            'id' => 11,
            'kegiatan_id' => 1,
            'tahapan' => 'pencacahan',
            'nama_target' => 'UD Penggilingan Sinar Pagi',
            'sample_role' => 'utama',
            'is_active' => true,
            'nama_frame' => 'UD Penggilingan Sinar Pagi',
            'kode_kecamatan' => '010',
            'kode_desa' => '002',
            'kode_sls' => '001',
            'kode_sub_sls' => '001',
            'kode_segmen' => '03',
            'kdkec' => '010',
            'kdkec_label' => 'Sawahlunto Utara',
            'kddes' => '002',
            'kddes_label' => 'Desa A',
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Sawahlunto Utara',
                'kddes' => '002',
                'kddes_label' => 'Desa A',
                'nks' => '0102001',
                'nama_usaha_penggilingan' => 'UD Penggilingan Sinar Pagi',
                'pemilik' => 'Budi',
            ],
            'target_unit_sampel' => [
                '1' => 3,
                '2' => 2,
            ],
        ]);
        $kegiatan->setRelation('kegiatanFrameSampel', collect([$frame]));

        $petugas = new Petugas([
            'id' => 7,
            'nama' => 'Petugas Penggilingan',
            'jenis_petugas' => 'organik',
        ]);

        $alokasi = new AlokasiPetugas([
            'id' => 21,
            'periode_alokasi_id' => 41,
            'petugas_id' => 7,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 5,
            'jumlah_unit_sampel' => 5,
            'jumlah_satuan_listing' => 5,
            'total_honor' => 250000,
            'total_honor_listing' => 0,
        ]);
        $alokasi->setRelation('petugas', $petugas);

        $frameAllocation = new AlokasiPetugasFrameSampel([
            'id' => 31,
            'alokasi_petugas_id' => 21,
            'kegiatan_frame_sampel_id' => 11,
            'is_non_response' => false,
        ]);
        $frameAllocation->setRelation('kegiatanFrameSampel', $frame);
        $alokasi->setRelation('frameSampelAllocations', collect([$frameAllocation]));

        $periode = new PeriodeAlokasi([
            'id' => 41,
            'kegiatan_id' => 1,
            'bulan' => '06',
            'tahun' => 2026,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
            'tanggal_mulai' => '2026-06-01',
            'tanggal_selesai' => '2026-06-06',
        ]);
        $periode->setRelation('alokasiPetugas', collect([$alokasi]));

        $reportData = $controller->buildReportData($kegiatan, $periode, new Penandatangan(['nama' => 'Kepala BPS Kota Sawahlunto']));
        $html = view('monitoring-pdf', $reportData)->render();

        $this->assertFalse($reportData['show_nama_usaha_column']);
        $this->assertStringNotContainsString('Nama Usaha', $html);
        $this->assertStringContainsString('[010] Sawahlunto Utara', $html);
        $this->assertStringContainsString('[002] Desa A', $html);
    }
}
