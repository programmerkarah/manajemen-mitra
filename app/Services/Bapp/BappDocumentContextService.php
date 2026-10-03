<?php

namespace App\Services\Bapp;

use App\Models\Kegiatan;
use App\Models\Petugas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BappDocumentContextService
{
    private const DOCUMENT_TYPES = [
        'regular',
        'stopped_petugas',
        'replacement_pkpp',
    ];

    private static ?bool $hasTerminTable = null;

    private static ?bool $supportsContextColumns = null;

    private static bool $loggedMissingTerminTable = false;

    private static bool $loggedMissingContextColumns = false;

    public function stripTitles(string $name): string
    {
        $name = trim($name);
        $name = preg_replace(
            '/^(Prof\.?|Dr\.?|Drs\.?|Dra\.?|Ir\.?|H\.?|Hj\.?|KH\.?)\s+/i',
            '',
            $name,
        );
        $name = preg_replace(
            '/,\s*[A-Z][A-Za-z.]+(?:,?\s*[A-Z][A-Za-z.]+)*\.?$/',
            '',
            $name,
        );

        return ucwords(strtolower(trim($name, ' ,')));
    }

    public function ketuaTimNip(?Kegiatan $kegiatan): ?string
    {
        $user = $kegiatan?->ketuaTim;

        if ($user?->nip) {
            return $user->nip;
        }

        if ($user?->name) {
            return Petugas::query()
                ->where('nama', $user->name)
                ->first()?->nik;
        }

        return null;
    }

    public function resolveDocumentType(Request $request): string
    {
        $documentType = (string) $request->input(
            'document_type',
            'regular',
        );

        if (! in_array($documentType, self::DOCUMENT_TYPES, true)) {
            return 'regular';
        }

        return $this->supportsContextColumns()
            ? $documentType
            : 'regular';
    }

    public function resolveReplacementTerminCount(Request $request): int
    {
        return (int) $request->input('replacement_termin_count', 2) === 1
            ? 1
            : 2;
    }

    public function contextReplacementTerminCount(
        string $documentType,
        int $replacementTerminCount,
    ): int {
        if (! $this->supportsContextColumns()) {
            return 0;
        }

        return $documentType === 'replacement_pkpp'
            ? $replacementTerminCount
            : 0;
    }

    public function supportsContextColumns(): bool
    {
        if (self::$supportsContextColumns !== null) {
            return self::$supportsContextColumns;
        }

        if (! $this->hasTerminTable()) {
            $this->logMissingContextColumns();
            self::$supportsContextColumns = false;

            return false;
        }

        self::$supportsContextColumns =
            Schema::hasColumn('bapp_se_termin', 'document_type')
            && Schema::hasColumn(
                'bapp_se_termin',
                'replacement_termin_count',
            );

        if (! self::$supportsContextColumns) {
            $this->logMissingContextColumns();
        }

        return self::$supportsContextColumns;
    }

    public function hasTerminTable(): bool
    {
        if (self::$hasTerminTable !== null) {
            return self::$hasTerminTable;
        }

        self::$hasTerminTable = Schema::hasTable('bapp_se_termin');

        if (
            ! self::$hasTerminTable
            && ! self::$loggedMissingTerminTable
        ) {
            self::$loggedMissingTerminTable = true;

            Log::warning(
                'BAPP table is missing; compatibility mode is active.',
                ['table' => 'bapp_se_termin'],
            );
        }

        return self::$hasTerminTable;
    }

    public function applyScope(
        Builder $query,
        string $documentType,
        int $replacementTerminCount,
    ): Builder {
        if (! $this->supportsContextColumns()) {
            return $query;
        }

        $query->where('document_type', $documentType);

        if ($documentType === 'replacement_pkpp') {
            return $query->where(
                'replacement_termin_count',
                $replacementTerminCount,
            );
        }

        return $query->where(function ($contextQuery): void {
            $contextQuery
                ->whereNull('replacement_termin_count')
                ->orWhere('replacement_termin_count', 0);
        });
    }

    public function withAttributes(
        array $attributes,
        string $documentType,
        int $replacementTerminCount,
    ): array {
        if (! $this->supportsContextColumns()) {
            return $attributes;
        }

        $attributes['document_type'] = $documentType;
        $attributes['replacement_termin_count'] =
            $replacementTerminCount;

        return $attributes;
    }

    private function logMissingContextColumns(): void
    {
        if (self::$loggedMissingContextColumns) {
            return;
        }

        self::$loggedMissingContextColumns = true;

        Log::warning(
            'BAPP context columns are missing; fallback compatibility mode is active.',
            [
                'table' => 'bapp_se_termin',
                'required_columns' => [
                    'document_type',
                    'replacement_termin_count',
                ],
            ],
        );
    }
}
