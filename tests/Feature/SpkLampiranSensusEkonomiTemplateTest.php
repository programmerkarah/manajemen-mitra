<?php

namespace Tests\Feature;

use App\Http\Controllers\SpkController;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SpkLampiranSensusEkonomiTemplateTest extends TestCase
{
    public function test_sensus_ekonomi_lampiran_renders_special_columns_and_fixed_work_items(): void
    {
        $html = view('spk-lampiran-sensus-ekonomi', [
            'petugas' => (object) [
                'nama' => 'Petugas Uji',
                'jenis_petugas' => 'non-organik',
            ],
            'kegiatan' => (object) [
                'jenis_kegiatan' => 'sensus',
                'nama_kegiatan' => 'Sensus Ekonomi',
            ],
            'periode' => (object) [
                'tahun' => 2026,
                'tanggal_mulai' => Carbon::parse('2026-06-15'),
                'tanggal_selesai' => Carbon::parse('2026-08-31'),
            ],
            'nomorSpk' => '001/ABC/001',
            'totalHonor' => 2500000,
            'lampiranPayload' => [
                'groups' => [
                    [
                        'items' => [
                            'Melakukan pendataan lapangan door to door Sensus Ekonomi 2026 termin I',
                            'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door Sensus Ekonomi 2026',
                        ],
                        'waktu_penyelesaian' => 'Minimal 1 bulan',
                        'persentase' => '40%',
                        'volume' => '1 SLS/sub-SLS',
                        'nilai_perjanjian' => 1000000,
                    ],
                    [
                        'items' => [
                            'Melakukan pendataan lapangan door to door Sensus Ekonomi 2026 termin II',
                            'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door Sensus Ekonomi 2026',
                        ],
                        'waktu_penyelesaian' => '31 Agustus 2026',
                        'persentase' => '60%',
                        'volume' => '1 SLS/sub-SLS',
                        'nilai_perjanjian' => 1500000,
                    ],
                ],
                'total' => [
                    'waktu_penyelesaian' => '15 Juni-31 Agustus 2026',
                    'persentase' => '100%',
                    'volume' => '1 SLS/sub-SLS',
                    'nilai_perjanjian' => 2500000,
                ],
                'wilayah_kerja' => [
                    [
                        'no' => 1,
                        'kecamatan' => '[010] Kecamatan A',
                        'desa' => '[001] Desa A',
                        'jumlah_sls' => 12,
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Waktu Penyelesaian', $html);
        $this->assertStringContainsString('size: A4 landscape;', $html);
        $this->assertStringContainsString('Persentase', $html);
        $this->assertStringContainsString('Nilai Perjanjian', $html);
        $this->assertStringContainsString('Minimal 1 bulan', $html);
        $this->assertStringContainsString('31 Agustus 2026', $html);
        $this->assertStringContainsString('1 SLS/sub-SLS', $html);
        $this->assertStringContainsString('termin I', $html);
        $this->assertStringContainsString('termin II', $html);
        $this->assertStringContainsString('DAFTAR WILAYAH KERJA', $html);
        $this->assertStringContainsString('Jumlah SLS/Sub-SLS', $html);
        $this->assertStringNotContainsString('Muatan Prelist usaha/keluarga', $html);
        $this->assertStringNotContainsString('Harga Satuan', $html);
        $this->assertStringNotContainsString('Beban Anggaran', $html);
    }

    public function test_pml_sensus_ekonomi_lampiran_renders_four_column_wilayah_kerja(): void
    {
        $html = view('spk-lampiran-pml-sensus-ekonomi', [
            'petugas' => (object) [
                'nama' => 'Petugas Uji',
                'jenis_petugas' => 'non-organik',
            ],
            'kegiatan' => (object) [
                'jenis_kegiatan' => 'sensus',
                'nama_kegiatan' => 'Sensus Ekonomi',
            ],
            'periode' => (object) [
                'tahun' => 2026,
                'tanggal_mulai' => Carbon::parse('2026-06-15'),
                'tanggal_selesai' => Carbon::parse('2026-08-31'),
            ],
            'nomorSpk' => '001/ABC/001',
            'totalHonor' => 2500000,
            'lampiranPayload' => [
                'groups' => [
                    [
                        'items' => [
                            'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door Sensus Ekonomi 2026 termin I',
                        ],
                        'waktu_penyelesaian' => 'Minimal 1 bulan',
                        'persentase' => '40%',
                        'volume' => '1 SLS/sub-SLS',
                        'nilai_perjanjian' => 1000000,
                    ],
                ],
                'total' => [
                    'waktu_penyelesaian' => '15 Juni-31 Agustus 2026',
                    'persentase' => '100%',
                    'volume' => '1 SLS/sub-SLS',
                    'nilai_perjanjian' => 2500000,
                ],
                'wilayah_kerja' => [
                    [
                        'no' => 1,
                        'kecamatan' => '[010] Kecamatan A',
                        'desa' => '[001] Desa A',
                        'jumlah_sls' => 12,
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('1 SLS/sub-SLS', $html);
        $this->assertStringContainsString('II. DAFTAR WILAYAH KERJA', $html);
        $this->assertStringContainsString('Jumlah SLS/Sub-SLS', $html);
        $this->assertStringNotContainsString('Muatan Prelist usaha/keluarga', $html);
    }

    public function test_sensus_ekonomi_lampiran_payload_keeps_rule_based_volume_labels(): void
    {
        $controller = new SpkController;

        $periode = (object) [
            'tanggal_mulai' => Carbon::parse('2026-06-15'),
            'tanggal_selesai' => Carbon::parse('2026-08-31'),
        ];

        $kegiatan = new Kegiatan;
        $kegiatan->nama_kegiatan = 'Sensus Ekonomi';

        $sensusReflectionMethod = new \ReflectionMethod(SpkController::class, 'buildSensusEkonomiLampiranPayload');
        $sensusReflectionMethod->setAccessible(true);

        $pmlReflectionMethod = new \ReflectionMethod(SpkController::class, 'buildPmlSensusEkonomiLampiranPayload');
        $pmlReflectionMethod->setAccessible(true);

        $examples = [
            [
                'target_rows' => 3,
                'frame_muatan_totals' => [40, 60, 100],
                'termin_one_volume' => '1 SLS/sub-SLS',
                'termin_two_volume' => '2 SLS/sub-SLS',
                'total_volume' => 'Seluruh Muatan 3 SLS/sub-SLS',
            ],
            [
                'target_rows' => 4,
                'frame_muatan_totals' => [50, 50, 50, 50],
                'termin_one_volume' => '2 SLS/sub-SLS',
                'termin_two_volume' => '2 SLS/sub-SLS',
                'total_volume' => 'Seluruh Muatan 4 SLS/sub-SLS',
            ],
            [
                'target_rows' => 20,
                'frame_muatan_totals' => array_fill(0, 20, 1),
                'termin_one_volume' => '8 SLS/sub-SLS',
                'termin_two_volume' => '12 SLS/sub-SLS',
                'total_volume' => 'Seluruh Muatan 20 SLS/sub-SLS',
            ],
        ];

        foreach ($examples as $example) {
            $alokasiItems = collect();

            foreach ($example['frame_muatan_totals'] as $index => $frameMuatanTotal) {
                $frameSampel = new KegiatanFrameSampel([
                    'target_unit_sampel' => ['usaha' => $frameMuatanTotal],
                ]);

                $frameAllocation = new AlokasiPetugasFrameSampel([
                    'kegiatan_frame_sampel_id' => $index + 1,
                ]);
                $frameAllocation->setRelation('kegiatanFrameSampel', $frameSampel);

                $alokasi = new AlokasiPetugas;
                $alokasi->setRelation('frameSampelAllocations', new Collection([$frameAllocation]));

                $alokasiItems->push($alokasi);
            }

            $firstAlokasi = $alokasiItems->first();

            $payload = $sensusReflectionMethod->invoke(
                $controller,
                $periode,
                [],
                2500000.0,
                $kegiatan,
                $alokasiItems,
                $firstAlokasi,
            );

            $pmlResult = $pmlReflectionMethod->invoke(
                $controller,
                $periode,
                [],
                2500000.0,
                $kegiatan,
                $alokasiItems,
                $firstAlokasi,
            );

            $this->assertSame($example['termin_one_volume'], $payload['groups'][0]['volume']);
            $this->assertSame($example['termin_two_volume'], $payload['groups'][1]['volume']);
            $this->assertSame($example['total_volume'], $payload['total']['volume']);
            $this->assertSame($example['termin_one_volume'], $pmlResult['groups'][0]['volume']);
            $this->assertSame($example['termin_two_volume'], $pmlResult['groups'][1]['volume']);
            $this->assertSame($example['total_volume'], $pmlResult['total']['volume']);
            $this->assertSame('40%', $payload['groups'][0]['persentase']);
            $this->assertSame('60%', $payload['groups'][1]['persentase']);
            $this->assertSame('100%', $payload['total']['persentase']);
        }
    }

    public function test_pml_sensus_ekonomi_termin_satu_volume_aggregates_linked_ppl_targets(): void
    {
        $controller = new SpkController;

        $periode = (object) [
            'tanggal_mulai' => Carbon::parse('2026-06-15'),
            'tanggal_selesai' => Carbon::parse('2026-08-31'),
        ];

        $kegiatan = new Kegiatan;
        $kegiatan->nama_kegiatan = 'Sensus Ekonomi';

        $pmlReflectionMethod = new \ReflectionMethod(SpkController::class, 'buildPmlSensusEkonomiLampiranPayload');
        $pmlReflectionMethod->setAccessible(true);

        $periodeAlokasiId = 123;

        $buildFrameAllocation = function (int $frameId): AlokasiPetugasFrameSampel {
            $frameSampel = new KegiatanFrameSampel([
                'target_unit_sampel' => ['usaha' => 1],
            ]);

            $frameAllocation = new AlokasiPetugasFrameSampel([
                'kegiatan_frame_sampel_id' => $frameId,
            ]);
            $frameAllocation->setRelation('kegiatanFrameSampel', $frameSampel);

            return $frameAllocation;
        };

        $buildAlokasi = function (string $peran, array $frameIds) use ($periodeAlokasiId, $buildFrameAllocation): AlokasiPetugas {
            $alokasi = new AlokasiPetugas([
                'peran' => $peran,
                'periode_alokasi_id' => $periodeAlokasiId,
            ]);

            $frameAllocations = collect($frameIds)
                ->map(fn (int $frameId) => $buildFrameAllocation($frameId));

            $alokasi->setRelation('frameSampelAllocations', $frameAllocations);

            return $alokasi;
        };

        $pmlAlokasi = $buildAlokasi('pml', [1, 2, 3, 4, 5, 6, 7]);
        $pplSatu = $buildAlokasi('pcl_ppl', [1, 2]);
        $pplDua = $buildAlokasi('pcl_ppl', [3, 4]);
        $pplTiga = $buildAlokasi('pcl_ppl', [5, 6, 7]);

        $allAlokasi = collect([$pmlAlokasi, $pplSatu, $pplDua, $pplTiga]);

        $result = $pmlReflectionMethod->invoke(
            $controller,
            $periode,
            [],
            2500000.0,
            $kegiatan,
            $allAlokasi,
            $pmlAlokasi,
        );

        $this->assertSame('4 SLS/sub-SLS', $result['groups'][0]['volume']);
        $this->assertSame('3 SLS/sub-SLS', $result['groups'][1]['volume']);
        $this->assertSame('Seluruh Muatan 7 SLS/sub-SLS', $result['total']['volume']);
    }
}
