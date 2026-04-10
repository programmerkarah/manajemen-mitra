<?php

namespace Tests\Feature;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Models\Petugas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AlokasiPetugasTemplateExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_template_export_contains_active_petugas_sheet_with_nip_nik_and_name_columns(): void
    {
        Petugas::factory()->create([
            'nama' => 'Budi Aktif',
            'nik' => '1234567890123456',
            'status' => 'aktif',
        ]);

        Petugas::factory()->create([
            'nama' => 'Andi Aktif',
            'nik' => '1111222233334444',
            'status' => 'aktif',
        ]);

        Petugas::factory()->create([
            'nama' => 'Cici Nonaktif',
            'nik' => '9999888877776666',
            'status' => 'nonaktif',
        ]);

        $binary = Excel::raw(
            new AlokasiPetugasTemplateExport(null, 'create', null, null),
            ExcelFormat::XLSX,
        );

        $tempPath = storage_path('framework/testing/alokasi-petugas-template-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $binary);

        $spreadsheet = IOFactory::load($tempPath);

        $this->assertCount(2, $spreadsheet->getAllSheets());

        $activeSheet = $spreadsheet->getSheetByName('Daftar Petugas Aktif');

        $this->assertNotNull($activeSheet);
        $this->assertSame('nip_nik', $activeSheet->getCell('A1')->getValue());
        $this->assertSame('nama_petugas', $activeSheet->getCell('B1')->getValue());

        $this->assertSame('1111222233334444', (string) $activeSheet->getCell('A2')->getValue());
        $this->assertSame('Andi Aktif', (string) $activeSheet->getCell('B2')->getValue());

        $this->assertSame('1234567890123456', (string) $activeSheet->getCell('A3')->getValue());
        $this->assertSame('Budi Aktif', (string) $activeSheet->getCell('B3')->getValue());

        $this->assertSame('', (string) $activeSheet->getCell('A4')->getValue());
        $this->assertSame('', (string) $activeSheet->getCell('B4')->getValue());
    }
}
