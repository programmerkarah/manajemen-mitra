<?php

namespace Tests\Unit;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Services\AlokasiPetugas\AlokasiValidationService;
use App\Services\Bapp\BappDocumentContextService;
use App\Services\Bapp\BappNumberService;
use App\Services\Spk\SpkScopeService;
use Carbon\Carbon;
use Tests\TestCase;

class ExtractedBusinessServicesTest extends TestCase
{
    public function test_bapp_number_service_preserves_manual_number_rules(): void
    {
        $service = app(BappNumberService::class);

        $this->assertSame(
            '202',
            $service->extractSequence('B-202/BAPP-I-SE2026/1373/PL.200/2026'),
        );
        $this->assertSame(
            'B-007/BAPP-II-SE2026/1373/PL.200/2026',
            $service->formatManualNumber(
                '007',
                2026,
                'regular',
                2,
                'II',
            ),
        );
        $this->assertSame(
            'B-007/BAPP-SE2026/1373/PL.200/2026',
            $service->formatManualNumber(
                '007',
                2026,
                'replacement_pkpp',
                1,
                'I',
            ),
        );
    }

    public function test_bapp_number_service_prefers_entry_date(): void
    {
        $service = app(BappNumberService::class);

        $this->assertSame(
            '2026-08-20',
            $service->resolveEntryDate(
                ['tanggal_bapp' => '2026-08-20'],
                '2026-08-31',
            ),
        );
        $this->assertSame(
            '2026-08-31',
            $service->resolveEntryDate([], '2026-08-31'),
        );
    }

    public function test_bapp_document_context_service_strips_titles(): void
    {
        $service = app(BappDocumentContextService::class);

        $this->assertSame(
            'Budi Santoso',
            $service->stripTitles('Dr. BUDI SANTOSO, S.E.'),
        );
    }

    public function test_spk_scope_service_keeps_sensus_period_scoped(): void
    {
        $service = app(SpkScopeService::class);
        $periode = new PeriodeAlokasi([
            'bulan' => '06',
            'tahun' => 2026,
            'tanggal_mulai' => Carbon::parse('2026-06-15'),
            'tanggal_selesai' => Carbon::parse('2026-08-31'),
        ]);
        $periode->id = 99;
        $periode->exists = true;
        $periode->setRelation(
            'kegiatan',
            new Kegiatan([
                'nama_kegiatan' => 'Sensus Ekonomi',
                'jenis_kegiatan' => 'sensus',
            ]),
        );

        $this->assertTrue($service->usesPeriodBasedFlow($periode));
        $this->assertSame(
            'periode-99',
            $service->indexGroupKey($periode),
        );
        $this->assertSame(
            'Juni - Agustus 2026',
            $service->indexDisplayLabel($periode),
        );
    }

    public function test_spk_scope_service_keeps_regular_month_scoped(): void
    {
        $service = app(SpkScopeService::class);
        $periode = new PeriodeAlokasi([
            'bulan' => '5',
            'tahun' => 2026,
        ]);
        $periode->id = 16;
        $periode->exists = true;
        $periode->setRelation(
            'kegiatan',
            new Kegiatan([
                'nama_kegiatan' => 'Survei Harga',
                'jenis_kegiatan' => 'survei',
            ]),
        );

        $this->assertFalse($service->usesPeriodBasedFlow($periode));
        $this->assertSame(
            '2026-05',
            $service->indexGroupKey($periode),
        );
        $this->assertSame(
            'Juni 2026',
            $service->indexDisplayLabel(
                new PeriodeAlokasi([
                    'bulan' => '6',
                    'tahun' => 2026,
                ]),
            ),
        );
    }

    public function test_allocation_validation_service_preserves_numeric_rules(): void
    {
        $service = app(AlokasiValidationService::class);

        $this->assertFalse($service->hasDecimalPart(10));
        $this->assertTrue($service->hasDecimalPart(10.5));
        $this->assertSame(10, $service->normalizeSatuan('10.0'));
        $this->assertSame(10.5, $service->normalizeSatuan('10.5'));

        $sensus = new Kegiatan([
            'nama_kegiatan' => 'Sensus Ekonomi',
            'jenis_kegiatan' => 'sensus',
        ]);

        $this->assertTrue($service->isSensusEkonomi2026($sensus));
        $this->assertSame(
            25.0,
            $service->pencacahanWorkload($sensus, 10),
        );
    }
}
