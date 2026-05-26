<?php

namespace App\Imports;

use App\Services\PetugasImportProcessor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PetugasImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use Importable;

    public function __construct(
        private readonly PetugasImportProcessor $processor = new PetugasImportProcessor,
    ) {}

    protected $errors = [];

    protected $successCount = 0;

    protected $failures = [];

    protected $createdCount = 0;

    protected $updatedCount = 0;

    /**
     * @param  Collection  $collection
     */
    public function collection(Collection $rows)
    {
        $result = $this->processor->process($rows, persist: true);

        $this->errors = $result['errors'];
        $this->successCount = $result['success_count'];
        $this->createdCount = $result['created_count'];
        $this->updatedCount = $result['updated_count'];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }
}
