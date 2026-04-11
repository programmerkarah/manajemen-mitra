<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AlokasiPetugasPreviewImport implements WithMultipleSheets
{
    protected Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function setRows(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function rows(): Collection
    {
        return $this->rows;
    }

    public function sheets(): array
    {
        return [
            0 => new AlokasiPetugasPreviewMainSheetImport($this),
        ];
    }
}

class AlokasiPetugasPreviewMainSheetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function __construct(private readonly AlokasiPetugasPreviewImport $parent) {}

    public function collection(Collection $rows): void
    {
        $this->parent->setRows($rows);
    }
}
