<?php

namespace App\Exports;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlokasiPetugasTemplateExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $periodeAlokasiId,
        protected string $type = 'create',
        protected ?Kegiatan $kegiatan = null,
        protected ?string $tahapan = null,
    ) {}

    /**
     * Whether listing columns should appear:
     * only for survei with has_listing_updating=true where tahapan is not pencacahan_only.
     * Defaults to true (full template) when kegiatan is unknown.
     */
    private function hasListing(): bool
    {
        if ($this->kegiatan === null) {
            return true;
        }

        if ($this->kegiatan->jenis_kegiatan !== 'survei' || ! $this->kegiatan->has_listing_updating) {
            return false;
        }

        return $this->tahapan !== 'pencacahan_only';
    }

    /**
     * Whether parsial columns should appear: only for survei.
     * Defaults to true when kegiatan is unknown.
     */
    private function hasParsial(): bool
    {
        if ($this->kegiatan === null) {
            return true;
        }

        return $this->kegiatan->jenis_kegiatan === 'survei';
    }

    private function templateLastRow(): int
    {
        if ($this->type === 'edit' && $this->periodeAlokasiId) {
            $entries = AlokasiPetugas::query()
                ->where('periode_alokasi_id', $this->periodeAlokasiId)
                ->with(['petugas', 'frameSampelAllocations.kegiatanFrameSampel'])
                ->get();

            $rowCount = 0;
            $hasFrameSampelColumn = $this->hasFrameSampelColumn();

            foreach ($entries as $entry) {
                $frameRows = $entry->frameSampelAllocations
                    ->map(fn ($allocation) => $allocation->kegiatanFrameSampel)
                    ->filter(fn ($frameRow) => $frameRow instanceof KegiatanFrameSampel)
                    ->values();

                $rowCount += $hasFrameSampelColumn && $frameRows->isNotEmpty()
                    ? $frameRows->count()
                    : 1;
            }

            return max(1, $rowCount);
        }

        return 6;
    }

    /**
     * @return Collection<int, KegiatanFrameSampel>
     */
    private function frameSampelRows()
    {
        if ($this->kegiatan === null) {
            return collect();
        }

        $query = $this->kegiatan->kegiatanFrameSampel()
            ->select('id', 'kegiatan_id', 'tahapan', 'nama_frame', 'target_unit_sampel', 'identitas_tambahan')
            ->orderBy('tahapan')
            ->orderBy('id');

        if ($this->tahapan === 'listing_only') {
            $query->where('tahapan', 'listing');
        }

        if ($this->tahapan === 'pencacahan_only') {
            $query->where('tahapan', 'pencacahan');
        }

        return $query->get();
    }

    /**
     * @return Collection<int, array{code:string,label:string}>
     */
    private function frameMetadataColumns(): Collection
    {
        $preferredOrder = ['kdkec', 'kddes', 'kdsls', 'kdsubsls', 'idsegmen', 'kdsegmen'];
        $columns = collect();

        foreach ($this->frameSampelRows() as $frameRow) {
            $identitas = is_array($frameRow->identitas_tambahan)
                ? $frameRow->identitas_tambahan
                : [];

            foreach ($identitas as $key => $value) {
                if (! is_scalar($value) || Str::endsWith((string) $key, '_label')) {
                    continue;
                }

                $code = trim((string) $key);
                if ($code === '') {
                    continue;
                }

                $normalizedCode = Str::lower($code);

                if ($columns->contains(fn (array $column) => Str::lower($column['code']) === $normalizedCode)) {
                    continue;
                }

                $columns->push([
                    'code' => $code,
                    'label' => $this->formatMetadataLabel($code),
                ]);
            }
        }

        return $columns
            ->sortBy(function (array $column) use ($preferredOrder): int {
                $normalizedCode = Str::lower($column['code']);
                $priorityIndex = array_search($normalizedCode, $preferredOrder, true);

                return $priorityIndex === false ? 999 : $priorityIndex;
            })
            ->values();
    }

    private function formatMetadataLabel(string $code): string
    {
        $normalizedCode = Str::lower(trim($code));

        return match ($normalizedCode) {
            'kdkec', 'kode_kecamatan' => 'Kecamatan',
            'kddes', 'kode_desa' => 'Desa/Kelurahan',
            'kdsls', 'kode_sls' => 'SLS',
            'kdsubsls', 'kode_sub_sls' => 'Sub SLS',
            'idsegmen', 'kdsegmen', 'kode_segmen' => 'ID Segmen',
            default => Str::title(str_replace('_', ' ', $code)),
        };
    }

    private function resolveFrameMetadataValue(?KegiatanFrameSampel $frameRow, string $code): string
    {
        if (! $frameRow || ! is_array($frameRow->identitas_tambahan)) {
            return '';
        }

        foreach ($frameRow->identitas_tambahan as $key => $value) {
            if (Str::lower((string) $key) === Str::lower($code) && is_scalar($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function resolveTargetUnitSampelTotal(KegiatanFrameSampel $frameRow): int
    {
        $targetUnitSampel = $frameRow->target_unit_sampel;

        if (is_array($targetUnitSampel)) {
            return (int) array_sum(array_map(fn ($value) => (int) $value, $targetUnitSampel));
        }

        return (int) ($targetUnitSampel ?? 0);
    }

    /**
     * @return Collection<int, array{header:string, unit_id:int|null, unit_token:string|null, index:int|null}>
     */
    private function frameTargetColumns(): Collection
    {
        if ($this->kegiatan === null) {
            return collect([
                [
                    'header' => 'target_unit_sampel',
                    'unit_id' => null,
                    'unit_token' => null,
                    'index' => null,
                ],
            ]);
        }

        $unitItems = $this->kegiatan->unitSampelPencacahanItems();
        if ($unitItems->count() <= 1) {
            return collect([
                [
                    'header' => 'target_unit_sampel',
                    'unit_id' => null,
                    'unit_token' => null,
                    'index' => null,
                ],
            ]);
        }

        $orderedItems = $unitItems->sortBy(function ($item): array {
            $name = Str::lower((string) ($item->nama ?? ''));

            if (Str::contains($name, 'usaha')) {
                return [0, $name];
            }

            if (Str::contains($name, 'keluarga')) {
                return [1, $name];
            }

            return [2, $name];
        })->values();

        return $orderedItems->values()->map(function ($item, $index): array {
            $unitName = trim((string) ($item->nama ?? 'unit_sampel'));
            $token = Str::snake(Str::lower($unitName));
            $token = preg_replace('/[^a-z0-9_]/', '', $token) ?? '';
            $token = trim($token, '_');
            if ($token === '') {
                $token = 'unit_sampel_'.($index + 1);
            }

            return [
                'header' => 'target_'.$token,
                'unit_id' => (int) $item->id,
                'unit_token' => $token,
                'index' => $index,
            ];
        });
    }

    /**
     * @param  array{header:string, unit_id:int|null, unit_token:string|null, index:int|null}  $targetColumn
     */
    private function resolveFrameTargetValue(KegiatanFrameSampel $frameRow, array $targetColumn): int
    {
        $targetUnitSampel = $frameRow->target_unit_sampel;
        if (! is_array($targetUnitSampel)) {
            return (int) ($targetUnitSampel ?? 0);
        }

        if (($targetColumn['unit_id'] ?? null) !== null) {
            $unitId = (string) $targetColumn['unit_id'];
            if (array_key_exists($unitId, $targetUnitSampel)) {
                return (int) ($targetUnitSampel[$unitId] ?? 0);
            }

            if (array_key_exists((int) $unitId, $targetUnitSampel)) {
                return (int) ($targetUnitSampel[(int) $unitId] ?? 0);
            }
        }

        $unitToken = trim((string) ($targetColumn['unit_token'] ?? ''));
        if ($unitToken !== '') {
            foreach ($targetUnitSampel as $key => $value) {
                if (Str::snake(Str::lower((string) $key)) === $unitToken) {
                    return (int) $value;
                }
            }
        }

        $index = $targetColumn['index'] ?? null;
        if ($index !== null) {
            $values = array_values($targetUnitSampel);
            if (array_key_exists($index, $values)) {
                return (int) ($values[$index] ?? 0);
            }
        }

        return (int) array_sum(array_map(fn ($value) => (int) $value, $targetUnitSampel));
    }

    /**
     * @param  Collection<int, array{code:string,label:string}>  $frameMetadataColumns
     * @param  Collection<int, array{header:string, unit_id:int|null, unit_token:string|null, index:int|null}>  $frameTargetColumns
     */
    private function buildFrameTargetFormula(
        int $rowNumber,
        Collection $frameMetadataColumns,
        Collection $frameTargetColumns,
        int $targetColumnIndex,
        int $frameLastRow,
    ): string {
        $mainMetadataStartColumn = 3;
        $mainTargetStartColumn = $mainMetadataStartColumn + $frameMetadataColumns->count() + ($this->hasListing() ? 1 : 0);
        $frameMetadataStartColumn = 4;
        $frameTargetStartColumn = $frameMetadataStartColumn + $frameMetadataColumns->count();

        if (! is_array($frameTargetColumns->get($targetColumnIndex))) {
            return '';
        }

        $metadataCells = [];
        $sumRange = null;
        $criteriaParts = [];

        foreach ($frameMetadataColumns as $metadataIndex => $_column) {
            $mainMetadataColumnLetter = Coordinate::stringFromColumnIndex($mainMetadataStartColumn + $metadataIndex);
            $frameMetadataColumnLetter = Coordinate::stringFromColumnIndex($frameMetadataStartColumn + $metadataIndex);

            $metadataCells[] = $mainMetadataColumnLetter.$rowNumber;
            $criteriaParts[] = "'Daftar Frame Sampel'!$".$frameMetadataColumnLetter.'$2:$'.$frameMetadataColumnLetter.'$'.$frameLastRow;
            $criteriaParts[] = $mainMetadataColumnLetter.$rowNumber;
        }

        $frameTargetColumnLetter = Coordinate::stringFromColumnIndex($frameTargetStartColumn + $targetColumnIndex);
        $sumRange = "'Daftar Frame Sampel'!$".$frameTargetColumnLetter.'$2:$'.$frameTargetColumnLetter.'$'.$frameLastRow;

        if ($metadataCells === []) {
            return '='.$sumRange;
        }

        return '=IF(COUNTA('.implode(',', $metadataCells).')<'.$frameMetadataColumns->count().',"",SUMIFS('.$sumRange.','.implode(',', $criteriaParts).'))';
    }

    private function toNameSafeToken(string $value): string
    {
        $upperValue = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9_]/', '_', $upperValue) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'EMPTY';
    }

    private function metadataCodeToken(string $code): string
    {
        return 'C_'.$this->toNameSafeToken($code);
    }

    private function metadataValueToken(string $value): string
    {
        return 'V_'.$this->toNameSafeToken($value);
    }

    /**
     * @param  array<int, string>  $parentValues
     */
    private function metadataParentToken(array $parentValues): string
    {
        if (empty($parentValues)) {
            return 'ROOT';
        }

        return implode('_', array_map(fn (string $value) => $this->metadataValueToken($value), $parentValues));
    }

    private function hasFrameSampelColumn(): bool
    {
        if ($this->kegiatan === null) {
            return false;
        }

        return $this->frameSampelRows()->isNotEmpty();
    }

    /**
     * @return array<int, string>
     */
    private function sensusPencacahanUnitColumns(): array
    {
        if ($this->kegiatan === null || $this->kegiatan->jenis_kegiatan !== 'sensus') {
            return [];
        }

        $items = $this->kegiatan->unitSampelPencacahanItems();

        if ($items->count() <= 1) {
            return [];
        }

        $sortedItems = $items->sortBy(function ($item): array {
            $name = Str::lower((string) ($item->nama ?? ''));

            if (Str::contains($name, 'usaha')) {
                return [0, $name];
            }

            if (Str::contains($name, 'keluarga')) {
                return [1, $name];
            }

            return [2, $name];
        })->values();

        return $sortedItems
            ->map(fn ($item) => 'Jumlah '.Str::title(Str::lower((string) ($item->nama ?? 'Unit Sampel'))))
            ->all();
    }

    public function array(): array
    {
        $hasListing = $this->hasListing();
        $hasParsial = $this->hasParsial();
        $hasFrameSampelColumn = $this->hasFrameSampelColumn();
        $frameMetadataColumns = $this->frameMetadataColumns();
        $frameTargetColumns = $this->frameTargetColumns();
        $sensusPencacahanUnitColumns = $this->sensusPencacahanUnitColumns();
        $hasSensusPencacahanSplit = count($sensusPencacahanUnitColumns) > 0;
        $data = [];

        if ($this->type === 'edit' && $this->periodeAlokasiId) {
            $entries = AlokasiPetugas::where('periode_alokasi_id', $this->periodeAlokasiId)
                ->with(['petugas', 'frameSampelAllocations.kegiatanFrameSampel'])
                ->get();

            foreach ($entries as $entry) {
                $baseRow = [
                    $this->formatNikDropdownValue($entry->petugas?->nama, $entry->petugas?->nik),
                    $this->mapPeranCodeForTemplate($entry->peran),
                ];

                $frameRows = $entry->frameSampelAllocations
                    ->map(fn ($allocation) => $allocation->kegiatanFrameSampel)
                    ->filter(fn ($frameRow) => $frameRow instanceof KegiatanFrameSampel)
                    ->values();

                if (! $hasFrameSampelColumn || $frameRows->isEmpty()) {
                    $row = $baseRow;

                    if ($hasFrameSampelColumn) {
                        foreach ($frameMetadataColumns as $_column) {
                            $row[] = '';
                        }
                    }

                    if ($hasListing) {
                        $row[] = $entry->jumlah_satuan_listing ?? '';
                    }

                    if ($hasSensusPencacahanSplit) {
                        foreach ($sensusPencacahanUnitColumns as $columnIndex => $_columnLabel) {
                            $row[] = $columnIndex === 0 ? ($entry->jumlah_satuan ?? '') : '';
                        }
                    } else {
                        $row[] = $entry->jumlah_satuan ?? '';
                    }

                    if ($hasParsial) {
                        $row[] = $entry->is_partial_payment ? 'Ya' : 'Tidak';
                    }

                    if ($hasListing && $hasParsial) {
                        $row[] = $entry->partial_jumlah_satuan_listing ?? '';
                    }

                    if ($hasParsial) {
                        $row[] = $entry->partial_jumlah_satuan ?? '';
                    }

                    $data[] = $row;

                    continue;
                }

                foreach ($frameRows as $frameRow) {
                    $row = $baseRow;

                    foreach ($frameMetadataColumns as $column) {
                        $row[] = $this->resolveFrameMetadataValue($frameRow, $column['code']);
                    }

                    if ($hasListing) {
                        $row[] = $entry->jumlah_satuan_listing ?? '';
                    }

                    if ($hasSensusPencacahanSplit) {
                        foreach ($sensusPencacahanUnitColumns as $columnIndex => $_columnLabel) {
                            $row[] = $columnIndex === 0 ? ($entry->jumlah_satuan ?? '') : '';
                        }
                    } else {
                        $row[] = $entry->jumlah_satuan ?? '';
                    }

                    if ($hasParsial) {
                        $row[] = $entry->is_partial_payment ? 'Ya' : 'Tidak';
                    }

                    if ($hasListing && $hasParsial) {
                        $row[] = $entry->partial_jumlah_satuan_listing ?? '';
                    }

                    if ($hasParsial) {
                        $row[] = $entry->partial_jumlah_satuan ?? '';
                    }

                    $data[] = $row;
                }
            }
        } else {
            $sampleRow = ['Nama Petugas - 1234567890123456', 'PCL/PPL'];
            $firstFrameRow = null;

            if ($hasFrameSampelColumn) {
                $firstFrameRow = $this->frameSampelRows()->first();

                foreach ($frameMetadataColumns as $column) {
                    $sampleRow[] = $this->resolveFrameMetadataValue($firstFrameRow, $column['code']);
                }
            }

            if ($hasListing) {
                $sampleRow[] = '5';
            }

            if ($hasSensusPencacahanSplit) {
                foreach ($sensusPencacahanUnitColumns as $columnIndex => $columnLabel) {
                    if ($hasFrameSampelColumn && $firstFrameRow instanceof KegiatanFrameSampel) {
                        $targetColumn = $frameTargetColumns->get($columnIndex);
                        if (is_array($targetColumn)) {
                            $sampleRow[] = $this->resolveFrameTargetValue($firstFrameRow, $targetColumn);

                            continue;
                        }
                    }

                    $isUsahaColumn = Str::contains(Str::lower($columnLabel), 'usaha');
                    $sampleRow[] = $isUsahaColumn ? '6' : '4';
                }
            } else {
                $sampleRow[] = '10';
            }

            if ($hasParsial) {
                $sampleRow[] = 'Tidak';
            }

            if ($hasListing && $hasParsial) {
                $sampleRow[] = '';
            }

            if ($hasParsial) {
                $sampleRow[] = '';
            }

            $data[] = $sampleRow;

            $emptyRow = array_fill(0, count($sampleRow), '');
            for ($i = 0; $i < 5; $i++) {
                $data[] = $emptyRow;
            }
        }

        $data[] = [''];
        $data[] = ['Petunjuk Pengisian:'];
        $data[] = ['1. Pilih petugas dari dropdown di kolom NIK (format: Nama - NIK/NIP)'];
        $data[] = ['2. Pilih Kode Penugasan dari dropdown: PCL/PPL, PML, Petugas Pengolahan, Pengawas Pengolahan'];

        $num = 3;

        if ($hasListing) {
            $data[] = ["{$num}. Jumlah Satuan Listing diisi angka bulat >= 0"];
            $num++;
        }

        if ($hasSensusPencacahanSplit) {
            foreach ($sensusPencacahanUnitColumns as $columnLabel) {
                $data[] = ["{$num}. {$columnLabel} diisi angka bulat >= 0"];
                $num++;
            }
        } else {
            $data[] = ["{$num}. Jumlah Satuan Pencacahan diisi angka bulat >= 0"];
            $num++;
        }

        if ($hasParsial) {
            $data[] = ["{$num}. Pembayaran Parsial diisi Ya/Tidak"];
            $num++;

            if ($hasListing) {
                $data[] = ["{$num}. Jika Pembayaran Parsial = Ya, isi jumlah satuan parsial listing dan/atau pencacahan"];
            } else {
                $data[] = ["{$num}. Jika Pembayaran Parsial = Ya, isi jumlah satuan parsial pencacahan"];
            }
        }

        if ($hasFrameSampelColumn) {
            $data[] = [''];
            $data[] = ['Catatan Frame Sampel:'];
            $data[] = ['- Kolom metadata frame sampel wajib diisi sesuai metadata yang tersedia pada sheet Daftar Frame Sampel.'];
            $data[] = ['- Jika satu petugas menerima lebih dari satu frame sampel, gunakan beberapa baris (petugas yang sama dapat diulang).'];
            $data[] = ['- Saat import, baris dengan petugas + peran yang sama akan digabung otomatis saat penyimpanan.'];
        }

        return $data;
    }

    public function headings(): array
    {
        $hasListing = $this->hasListing();
        $hasParsial = $this->hasParsial();
        $hasFrameSampelColumn = $this->hasFrameSampelColumn();
        $frameMetadataColumns = $this->frameMetadataColumns();
        $sensusPencacahanUnitColumns = $this->sensusPencacahanUnitColumns();
        $hasSensusPencacahanSplit = count($sensusPencacahanUnitColumns) > 0;

        $columns = ['Nama - NIK', 'Kode Penugasan'];

        if ($hasFrameSampelColumn) {
            foreach ($frameMetadataColumns as $column) {
                $columns[] = $column['label'];
            }
        }

        if ($hasListing) {
            $columns[] = 'Jumlah Satuan Listing';
        }

        if ($hasSensusPencacahanSplit) {
            foreach ($sensusPencacahanUnitColumns as $columnLabel) {
                $columns[] = $columnLabel;
            }
        } else {
            $columns[] = 'Jumlah Satuan Pencacahan';
        }

        if ($hasParsial) {
            $columns[] = 'Pembayaran Parsial';
        }

        if ($hasListing && $hasParsial) {
            $columns[] = 'Jumlah Satuan Parsial Listing';
        }

        if ($hasParsial) {
            $columns[] = 'Jumlah Satuan Parsial Pencacahan';
        }

        return $columns;
    }

    public function styles(Worksheet $sheet)
    {
        $hasListing = $this->hasListing();
        $hasParsial = $this->hasParsial();
        $hasFrameSampelColumn = $this->hasFrameSampelColumn();
        $frameMetadataColumns = $this->frameMetadataColumns();
        $sensusPencacahanUnitColumns = $this->sensusPencacahanUnitColumns();
        $hasSensusPencacahanSplit = count($sensusPencacahanUnitColumns) > 0;

        $widths = [59, 30];

        if ($hasFrameSampelColumn) {
            foreach ($frameMetadataColumns as $_column) {
                $widths[] = 22;
            }
        }

        if ($hasListing) {
            $widths[] = 22;
        }

        if ($hasSensusPencacahanSplit) {
            foreach ($sensusPencacahanUnitColumns as $_columnLabel) {
                $widths[] = 26;
            }
        } else {
            $widths[] = 26;
        }

        if ($hasParsial) {
            $widths[] = 20;
        }

        if ($hasListing && $hasParsial) {
            $widths[] = 28;
        }

        if ($hasParsial) {
            $widths[] = 32;
        }

        $colLetter = 'A';
        foreach ($widths as $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
            $colLetter++;
        }

        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('1')->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('1')->getFill()->getStartColor()->setARGB(Color::COLOR_YELLOW);
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Pre-format the entire NIK column as text so that any value typed by
        // the user is stored as text. This prevents 16-digit NIKs from being
        // truncated due to IEEE 754 float precision (max ~15 significant digits).
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        // Required: NIK, Kode, (Listing?), Pencacahan, (Parsial flag?)
        $requiredPencacahanColumns = $hasSensusPencacahanSplit ? count($sensusPencacahanUnitColumns) : 1;
        $requiredCount = 2 + ($hasFrameSampelColumn ? $frameMetadataColumns->count() : 0) + ($hasListing ? 1 : 0) + $requiredPencacahanColumns + ($hasParsial ? 1 : 0);
        $requiredLastCol = chr(ord('A') + $requiredCount - 1);

        $templateLastRow = $this->templateLastRow();

        for ($col = 'A'; $col <= $requiredLastCol; $col++) {
            $sheet->getStyle("{$col}2:{$col}{$templateLastRow}")->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle("{$col}2:{$col}{$templateLastRow}")->getFill()->getStartColor()->setARGB('FFFFCCCC');
        }

        return [];
    }

    public function title(): string
    {
        if ($this->periodeAlokasiId) {
            $periode = PeriodeAlokasi::find($this->periodeAlokasiId);
            if ($periode) {
                return "Alokasi {$periode->bulan}/{$periode->tahun}";
            }
        }

        return 'Alokasi Petugas';
    }

    /**
     * Force column A data cells to be stored as a string type so NIK values
     * (16-digit numbers) are never rendered in scientific notation by Excel.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'A' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function mapPeranCodeForTemplate(string $peran): string
    {
        return match ($peran) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            default => 'PCL/PPL',
        };
    }

    private function formatNikDropdownValue(?string $nama, ?string $nik): string
    {
        $nama = trim((string) ($nama ?? ''));
        $nik = trim((string) ($nik ?? ''));

        if ($nama === '' && $nik === '') {
            return '';
        }

        if ($nama === '') {
            return $nik;
        }

        if ($nik === '') {
            return $nama;
        }

        return $nama.' - '.$nik;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $mainSheet = $event->sheet->getDelegate();
                $spreadsheet = $mainSheet->getParent();
                $sheet = new Worksheet($spreadsheet, 'Daftar Petugas Aktif');

                $spreadsheet->addSheet($sheet);

                $sheet->setCellValue('A1', 'nip_nik');
                $sheet->setCellValue('B1', 'nama_petugas');
                $sheet->setCellValue('C1', 'pilihan_dropdown');
                $sheet->setCellValue('D1', 'kode_penugasan_dropdown');

                $sheet->getStyle('A1:D1')->getFont()->setBold(true);
                $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:D1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(24);
                $sheet->getColumnDimension('B')->setWidth(42);
                $sheet->getColumnDimension('C')->setWidth(64);
                $sheet->getColumnDimension('D')->setWidth(32);

                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

                $row = 2;
                $activePetugas = Petugas::query()
                    ->where('status', 'aktif')
                    ->orderBy('nama')
                    ->get(['nik', 'nama']);

                foreach ($activePetugas as $petugas) {
                    $nik = (string) ($petugas->nik ?? '');
                    $nama = (string) ($petugas->nama ?? '');

                    $sheet->setCellValueExplicit('A'.$row, $nik, DataType::TYPE_STRING);
                    $sheet->setCellValue('B'.$row, $nama);
                    $sheet->setCellValue('C'.$row, $this->formatNikDropdownValue($nama, $nik));
                    $row++;
                }

                $sheet->setCellValue('D2', 'PCL/PPL');
                $sheet->setCellValue('D3', 'PML');
                $sheet->setCellValue('D4', 'Petugas Pengolahan');
                $sheet->setCellValue('D5', 'Pengawas Pengolahan');

                $sheet->freezePane('A2');

                $lastPetugasRow = max(2, $row - 1);
                if ($lastPetugasRow >= 2) {
                    $listFormula = "'Daftar Petugas Aktif'!\$C\$2:\$C\$".$lastPetugasRow;

                    $validation = $mainSheet->getCell('A2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('NIK tidak valid');
                    $validation->setError('Silakan pilih petugas dari dropdown yang tersedia.');
                    $validation->setPromptTitle('Pilih Petugas');
                    $validation->setPrompt('Pilih petugas (Nama - NIK/NIP) pada dropdown.');
                    $validation->setFormula1($listFormula);

                    $kodeValidation = $mainSheet->getCell('B2')->getDataValidation();
                    $kodeValidation->setType(DataValidation::TYPE_LIST);
                    $kodeValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $kodeValidation->setAllowBlank(true);
                    $kodeValidation->setShowInputMessage(true);
                    $kodeValidation->setShowErrorMessage(true);
                    $kodeValidation->setShowDropDown(true);
                    $kodeValidation->setErrorTitle('Kode penugasan tidak valid');
                    $kodeValidation->setError('Silakan pilih kode penugasan dari dropdown yang tersedia.');
                    $kodeValidation->setPromptTitle('Pilih Kode Penugasan');
                    $kodeValidation->setPrompt('Gunakan dropdown untuk memilih jenis penugasan.');
                    $kodeValidation->setFormula1("'Daftar Petugas Aktif'!\$D\$2:\$D\$5");

                    $templateLastRow = $this->templateLastRow();

                    for ($rowNumber = 3; $rowNumber <= $templateLastRow; $rowNumber++) {
                        $mainSheet->getCell('A'.$rowNumber)->setDataValidation(clone $validation);
                        $mainSheet->getCell('B'.$rowNumber)->setDataValidation(clone $kodeValidation);
                    }
                }

                $frameRows = $this->frameSampelRows();

                if ($frameRows->isNotEmpty()) {
                    $hasListing = $this->hasListing();
                    $frameMetadataColumns = $this->frameMetadataColumns();
                    $frameTargetColumns = $this->frameTargetColumns();
                    $frameLastRow = 1 + $frameRows->count();
                    $frameSheet = new Worksheet($spreadsheet, 'Daftar Frame Sampel');
                    $dropdownSheet = new Worksheet($spreadsheet, 'Referensi Dropdown Metadata');
                    $spreadsheet->addSheet($frameSheet);
                    $spreadsheet->addSheet($dropdownSheet);

                    $headerColumns = ['id_frame', 'tahapan', 'nama_frame'];
                    foreach ($frameMetadataColumns as $column) {
                        $headerColumns[] = $column['label'];
                    }
                    foreach ($frameTargetColumns as $targetColumn) {
                        $headerColumns[] = $targetColumn['header'];
                    }

                    $columnLetter = 'A';
                    foreach ($headerColumns as $header) {
                        $frameSheet->setCellValue($columnLetter.'1', $header);
                        $frameSheet->getColumnDimension($columnLetter)->setWidth(24);
                        $columnLetter++;
                    }

                    $lastHeaderColumn = chr(ord('A') + count($headerColumns) - 1);
                    $frameSheet->getStyle('A1:'.$lastHeaderColumn.'1')->getFont()->setBold(true);
                    $frameSheet->getStyle('A1:'.$lastHeaderColumn.'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $frameRow = 2;

                    foreach ($frameRows as $frame) {
                        $rowValues = [
                            (int) $frame->id,
                            (string) $frame->tahapan,
                            (string) ($frame->nama_frame ?: 'Frame #'.$frame->id),
                        ];

                        foreach ($frameMetadataColumns as $column) {
                            $rowValues[] = $this->resolveFrameMetadataValue($frame, $column['code']);
                        }

                        foreach ($frameTargetColumns as $targetColumn) {
                            if ($targetColumn['header'] === 'target_unit_sampel') {
                                $rowValues[] = $this->resolveTargetUnitSampelTotal($frame);

                                continue;
                            }

                            $rowValues[] = $this->resolveFrameTargetValue($frame, $targetColumn);
                        }

                        $columnLetter = 'A';
                        foreach ($rowValues as $rowValue) {
                            $frameSheet->setCellValue($columnLetter.$frameRow, $rowValue);
                            $columnLetter++;
                        }

                        $frameRow++;
                    }

                    $frameSheet->freezePane('A2');

                    $dropdownSheet->setCellValue('A1', 'range_name');
                    $dropdownSheet->setCellValue('B1', 'value');
                    $dropdownSheet->getStyle('A1:B1')->getFont()->setBold(true);

                    $metadataRows = $frameRows
                        ->map(function (KegiatanFrameSampel $frameRow) use ($frameMetadataColumns): array {
                            $valuesByCode = [];
                            foreach ($frameMetadataColumns as $column) {
                                $valuesByCode[$column['code']] = $this->resolveFrameMetadataValue($frameRow, $column['code']);
                            }

                            return $valuesByCode;
                        })
                        ->values();

                    $rangeDefinitions = [];
                    foreach ($frameMetadataColumns as $index => $column) {
                        $code = (string) $column['code'];
                        $codeToken = $this->metadataCodeToken($code);
                        $groups = [];

                        foreach ($metadataRows as $metadataRow) {
                            $value = trim((string) ($metadataRow[$code] ?? ''));
                            if ($value === '') {
                                continue;
                            }

                            $parentValues = [];
                            for ($parentIndex = 0; $parentIndex < $index; $parentIndex++) {
                                $parentCode = (string) $frameMetadataColumns[$parentIndex]['code'];
                                $parentValues[] = trim((string) ($metadataRow[$parentCode] ?? ''));
                            }

                            $parentToken = $this->metadataParentToken($parentValues);
                            $groups[$parentToken] = $groups[$parentToken] ?? [];
                            $groups[$parentToken][] = $value;
                        }

                        foreach ($groups as $parentToken => $values) {
                            $uniqueValues = array_values(array_unique(array_filter($values, fn (string $val): bool => trim($val) !== '')));
                            sort($uniqueValues);

                            if (empty($uniqueValues)) {
                                continue;
                            }

                            $rangeDefinitions[] = [
                                'name' => 'DD_'.$codeToken.'_'.$parentToken,
                                'values' => $uniqueValues,
                            ];
                        }
                    }

                    $rangeColumnIndex = 1;
                    foreach ($rangeDefinitions as $definition) {
                        $columnLetter = Coordinate::stringFromColumnIndex($rangeColumnIndex);
                        $dropdownSheet->setCellValue($columnLetter.'1', (string) $definition['name']);
                        $dropdownSheet->getColumnDimension($columnLetter)->setWidth(22);

                        $values = $definition['values'];
                        $currentRow = 2;
                        foreach ($values as $value) {
                            $dropdownSheet->setCellValueExplicit($columnLetter.$currentRow, (string) $value, DataType::TYPE_STRING);
                            $currentRow++;
                        }

                        $lastRow = max(2, $currentRow - 1);
                        $spreadsheet->addNamedRange(new NamedRange(
                            (string) $definition['name'],
                            $dropdownSheet,
                            '$'.$columnLetter.'$2:$'.$columnLetter.'$'.$lastRow
                        ));

                        $rangeColumnIndex++;
                    }

                    $mainMetadataStartColumn = 3; // A: NIK, B: Kode Penugasan
                    foreach ($frameMetadataColumns as $metaIndex => $column) {
                        $mainColumnIndex = $mainMetadataStartColumn + $metaIndex;
                        $mainColumnLetter = Coordinate::stringFromColumnIndex($mainColumnIndex);

                        $mainSheet->getStyle($mainColumnLetter.':'.$mainColumnLetter)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_TEXT);

                        $codeToken = $this->metadataCodeToken((string) $column['code']);
                        $buildFormulaForRow = function (int $rowNumber) use ($metaIndex, $mainMetadataStartColumn, $codeToken): string {
                            if ($metaIndex === 0) {
                                return 'INDIRECT("DD_'.$codeToken.'_ROOT")';
                            }

                            $parentParts = [];
                            for ($parentIndex = 0; $parentIndex < $metaIndex; $parentIndex++) {
                                $parentColumnLetter = Coordinate::stringFromColumnIndex($mainMetadataStartColumn + $parentIndex);
                                $parentParts[] = '"V_"&'.$parentColumnLetter.$rowNumber;
                            }

                            return 'INDIRECT("DD_'.$codeToken.'_"&'.implode('&"_"&', $parentParts).')';
                        };

                        $formula = $buildFormulaForRow(2);

                        $metaValidation = $mainSheet->getCell($mainColumnLetter.'2')->getDataValidation();
                        $metaValidation->setType(DataValidation::TYPE_LIST);
                        $metaValidation->setErrorStyle(DataValidation::STYLE_STOP);
                        $metaValidation->setAllowBlank(true);
                        $metaValidation->setShowInputMessage(true);
                        $metaValidation->setShowErrorMessage(true);
                        $metaValidation->setShowDropDown(true);
                        $metaValidation->setErrorTitle('Metadata frame sampel tidak valid');
                        $metaValidation->setError('Silakan pilih nilai metadata dari dropdown yang tersedia.');
                        $metaValidation->setPromptTitle('Pilih metadata frame sampel');
                        $metaValidation->setPrompt('Pilihan metadata mengikuti tingkatan kolom sebelumnya.');
                        $metaValidation->setFormula1((string) $formula);

                        $templateLastRow = $this->templateLastRow();

                        for ($rowNumber = 3; $rowNumber <= $templateLastRow; $rowNumber++) {
                            $validation = clone $metaValidation;
                            $validation->setFormula1($buildFormulaForRow($rowNumber));
                            $mainSheet->getCell($mainColumnLetter.$rowNumber)
                                ->setDataValidation($validation);
                        }
                    }

                    $dropdownSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                    $mainTargetStartColumn = 3 + $frameMetadataColumns->count() + ($hasListing ? 1 : 0);
                    $templateLastRow = $this->templateLastRow();

                    foreach (range(2, $templateLastRow) as $rowNumber) {
                        foreach ($frameTargetColumns as $targetIndex => $_targetColumn) {
                            $formula = $this->buildFrameTargetFormula(
                                $rowNumber,
                                $frameMetadataColumns,
                                $frameTargetColumns,
                                $targetIndex,
                                $frameLastRow,
                            );

                            if ($formula !== '') {
                                $targetColumnLetter = Coordinate::stringFromColumnIndex($mainTargetStartColumn + $targetIndex);
                                $mainSheet->setCellValue($targetColumnLetter.$rowNumber, $formula);
                            }
                        }
                    }
                }
            },
        ];
    }
}
