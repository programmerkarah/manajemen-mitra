<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\BappSeRealisasiTemplateExport;
use App\Imports\BappSeRealisasiImport;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\Petugas;
use App\Models\SensusEkonomiPetugasReplacement;
use App\Models\SensusEkonomiPkppContract;
use App\Models\Spk;
use App\Services\ActiveYearService;
use App\Services\SensusEkonomiBappNumberService;
use App\Services\SensusEkonomiPkNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Tcpdf\Fpdi;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

trait BappDocumentContextSupport
{
    private function stripGelar(string $nama): string
    {
        $nama = trim($nama);
        // Strip prefix titles (e.g. Dr., Drs., Prof., Ir., H., Hj.)
        $nama = preg_replace('/^(Prof\.?|Dr\.?|Drs\.?|Dra\.?|Ir\.?|H\.?|Hj\.?|KH\.?)\s+/i', '', $nama);
        // Strip suffix titles after comma (e.g. , S.E., M.M., S.AP.)
        $nama = preg_replace('/,\s*[A-Z][A-Za-z.]+(?:,?\s*[A-Z][A-Za-z.]+)*\.?$/', '', $nama);

        return ucwords(strtolower(trim($nama, ' ,')));
    }

    private function getNipKetuaTim(?Kegiatan $kegiatan): ?string
    {
        $user = $kegiatan?->ketuaTim;
        if ($user?->nip) {
            return $user->nip;
        }
        if ($user?->name) {
            return Petugas::query()->where('nama', $user->name)->first()?->nik;
        }

        return null;
    }

    private function resolveDocumentType(Request $request): string
    {
        $documentType = (string) $request->input('document_type', 'regular');

        if (! in_array($documentType, self::DOCUMENT_TYPES, true)) {
            return 'regular';
        }

        if (! $this->supportsBappDocumentContextColumns()) {
            return 'regular';
        }

        return $documentType;
    }

    private function resolveReplacementTerminCount(Request $request): int
    {
        return (int) $request->input('replacement_termin_count', 2) === 1 ? 1 : 2;
    }

    private function getContextReplacementTerminCount(string $documentType, int $replacementTerminCount): int
    {
        if (! $this->supportsBappDocumentContextColumns()) {
            return 0;
        }

        return $documentType === 'replacement_pkpp' ? $replacementTerminCount : 0;
    }

    private function supportsBappDocumentContextColumns(): bool
    {
        if (self::$supportsBappDocumentContextColumns !== null) {
            return self::$supportsBappDocumentContextColumns;
        }

        if (! $this->hasBappTerminTable()) {
            $this->logMissingBappDocumentContextWarning();
            self::$supportsBappDocumentContextColumns = false;

            return false;
        }

        self::$supportsBappDocumentContextColumns = Schema::hasColumn('bapp_se_termin', 'document_type')
            && Schema::hasColumn('bapp_se_termin', 'replacement_termin_count');

        if (! self::$supportsBappDocumentContextColumns) {
            $this->logMissingBappDocumentContextWarning();
        }

        return self::$supportsBappDocumentContextColumns;
    }

    private function hasBappTerminTable(): bool
    {
        if (self::$hasBappTerminTable !== null) {
            return self::$hasBappTerminTable;
        }

        self::$hasBappTerminTable = Schema::hasTable('bapp_se_termin');

        if (! self::$hasBappTerminTable && ! self::$hasLoggedMissingBappTerminTable) {
            self::$hasLoggedMissingBappTerminTable = true;

            Log::warning('BAPP table is missing; compatibility mode is active.', [
                'table' => 'bapp_se_termin',
            ]);
        }

        return self::$hasBappTerminTable;
    }

    private function logMissingBappDocumentContextWarning(): void
    {
        if (self::$hasLoggedMissingBappDocumentContextColumns) {
            return;
        }

        self::$hasLoggedMissingBappDocumentContextColumns = true;

        Log::warning('BAPP context columns are missing; fallback compatibility mode is active.', [
            'table' => 'bapp_se_termin',
            'required_columns' => ['document_type', 'replacement_termin_count'],
        ]);
    }

    private function applyBappDocumentContextScope(Builder $query, string $documentType, int $contextReplacementTerminCount): Builder
    {
        if (! $this->supportsBappDocumentContextColumns()) {
            return $query;
        }

        $query->where('document_type', $documentType);

        if ($documentType === 'replacement_pkpp') {
            return $query->where('replacement_termin_count', $contextReplacementTerminCount);
        }

        // Data reguler/stopped lama pernah disimpan dengan NULL. Perlakukan NULL
        // dan 0 sebagai konteks non-PKPP yang sama agar upload lama tetap terbaca.
        return $query->where(function ($contextQuery): void {
            $contextQuery
                ->whereNull('replacement_termin_count')
                ->orWhere('replacement_termin_count', 0);
        });
    }

    private function withBappDocumentContextAttributes(array $attributes, string $documentType, int $contextReplacementTerminCount): array
    {
        if (! $this->supportsBappDocumentContextColumns()) {
            return $attributes;
        }

        $attributes['document_type'] = $documentType;
        $attributes['replacement_termin_count'] = $contextReplacementTerminCount;

        return $attributes;
    }
}
