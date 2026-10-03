<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Kegiatan;
use App\Services\Bapp\BappDocumentContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait BappDocumentContextSupport
{
    private function stripGelar(string $nama): string
    {
        return app(BappDocumentContextService::class)
            ->stripTitles($nama);
    }

    private function getNipKetuaTim(?Kegiatan $kegiatan): ?string
    {
        return app(BappDocumentContextService::class)
            ->ketuaTimNip($kegiatan);
    }

    private function resolveDocumentType(Request $request): string
    {
        return app(BappDocumentContextService::class)
            ->resolveDocumentType($request);
    }

    private function resolveReplacementTerminCount(
        Request $request,
    ): int {
        return app(BappDocumentContextService::class)
            ->resolveReplacementTerminCount($request);
    }

    private function getContextReplacementTerminCount(
        string $documentType,
        int $replacementTerminCount,
    ): int {
        return app(BappDocumentContextService::class)
            ->contextReplacementTerminCount(
                $documentType,
                $replacementTerminCount,
            );
    }

    private function supportsBappDocumentContextColumns(): bool
    {
        return app(BappDocumentContextService::class)
            ->supportsContextColumns();
    }

    private function hasBappTerminTable(): bool
    {
        return app(BappDocumentContextService::class)
            ->hasTerminTable();
    }

    private function logMissingBappDocumentContextWarning(): void
    {
        app(BappDocumentContextService::class)
            ->supportsContextColumns();
    }

    private function applyBappDocumentContextScope(
        Builder $query,
        string $documentType,
        int $contextReplacementTerminCount,
    ): Builder {
        return app(BappDocumentContextService::class)->applyScope(
            $query,
            $documentType,
            $contextReplacementTerminCount,
        );
    }

    private function withBappDocumentContextAttributes(
        array $attributes,
        string $documentType,
        int $contextReplacementTerminCount,
    ): array {
        return app(BappDocumentContextService::class)->withAttributes(
            $attributes,
            $documentType,
            $contextReplacementTerminCount,
        );
    }
}
