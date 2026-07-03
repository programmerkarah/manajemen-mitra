<?php

namespace Tests\Unit;

use App\Exports\FrameSampelDetailTemplateExport;
use App\Models\Kegiatan;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrameSampelDetailTemplateExportTest extends TestCase
{
    #[Test]
    public function it_exports_purpossive_without_prefix_columns(): void
    {
        $export = new FrameSampelDetailTemplateExport(
            [
                [
                    'code' => 'kdkec',
                    'label' => 'Kecamatan',
                    'description' => 'Kode wilayah kecamatan',
                ],
            ],
            [
                [
                    'id' => 1,
                    'nama' => 'Usaha',
                ],
            ],
            'purpossive',
        );

        $this->assertSame([
            'Kode Kecamatan',
            'Kecamatan',
            'Nama Sampel',
            'Jenis Sampel',
        ], $export->headings());
    }

    #[Test]
    public function it_omits_the_name_column_for_code_only_metadata(): void
    {
        $export = new FrameSampelDetailTemplateExport(
            [
                [
                    'code' => 'kdkec',
                    'label' => 'Kecamatan',
                    'description' => 'Kode wilayah kecamatan',
                    'mode' => 'code_only',
                ],
            ],
            [],
            'targeted',
        );

        $this->assertSame([
            'Kode Kecamatan',
            'Jumlah Sampel Dalam Frame',
        ], $export->headings());
    }

    #[Test]
    public function it_prefills_existing_rows_and_adds_purpossive_dropdown_validation(): void
    {
        $binary = Excel::raw(
            new FrameSampelDetailTemplateExport(
                [
                    [
                        'code' => 'kdkec',
                        'label' => 'Kecamatan',
                        'description' => 'Kode wilayah kecamatan',
                    ],
                ],
                [],
                Kegiatan::METODE_SAMPLING_PURPOSSIVE,
                [
                    [
                        'identitas_tambahan' => [
                            'kdkec' => '010',
                            'kdkec_label' => 'Kecamatan 010',
                        ],
                        'sample_name' => 'Sampel A',
                        'sample_role' => 'utama',
                    ],
                ],
            ),
            ExcelFormat::XLSX,
        );

        $tempPath = storage_path('framework/testing/frame-sampel-template-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $binary);

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('010', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('Kecamatan 010', (string) $sheet->getCell('B2')->getValue());
        $this->assertSame('Sampel A', (string) $sheet->getCell('C2')->getValue());
        $this->assertSame('utama', (string) $sheet->getCell('D2')->getValue());

        $validation = $sheet->getCell('D2')->getDataValidation();

        $this->assertSame('list', $validation->getType());
        $this->assertSame('"utama,cadangan,lainnya"', $validation->getFormula1());
    }
}
