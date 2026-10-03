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

trait BappNumberingSupport
{
    protected function resolveEntryTanggalBapp(array $entry, ?string $sharedTanggalBapp): ?string
    {
        $entryTanggalBapp = isset($entry['tanggal_bapp']) ? trim((string) $entry['tanggal_bapp']) : '';

        if ($entryTanggalBapp !== '') {
            return $entryTanggalBapp;
        }

        $sharedTanggalBapp = $sharedTanggalBapp !== null ? trim($sharedTanggalBapp) : '';

        return $sharedTanggalBapp !== '' ? $sharedTanggalBapp : null;
    }

    private function generateNomorBappMap(
        Collection $spks,
        string $roman,
        int $tahun,
        string $documentType = 'regular',
        int $terminCount = 2,
    ): array {
        $sorted = $spks->sortBy(function (Spk $spk): string {
            return mb_strtolower(trim($spk->petugas?->nama ?? ''));
        })->values();

        $map = [];
        $numberService = new SensusEkonomiBappNumberService;
        foreach ($sorted as $index => $spk) {
            $sequence = $index + 1;

            if ($documentType === 'stopped_petugas') {
                $map[$spk->id] = $numberService->formatStoppedPetugasNumber(
                    $sequence,
                    $tahun,
                );

                continue;
            }

            if ($documentType === 'replacement_pkpp') {
                $map[$spk->id] = $numberService->formatReplacementNumber(
                    $sequence,
                    $tahun,
                    $terminCount,
                    $roman,
                );

                continue;
            }

            $map[$spk->id] = sprintf(
                'B-%03d/BAPP-%s-SE2026/1373/PL.200/%d',
                $sequence,
                $roman,
                $tahun,
            );
        }

        return $map;
    }

    private function formatManualBappNumber(
        string $sequence,
        int $termin,
        int $tahun,
        string $documentType,
        int $replacementTerminCount,
    ): string {
        $cleanSequence = preg_replace('/\D+/', '', $sequence) ?: '';

        if ($cleanSequence === '') {
            throw new \InvalidArgumentException('Nomor BAPP wajib berupa angka.');
        }

        if ($documentType === 'replacement_pkpp' && $replacementTerminCount === 1) {
            return sprintf(
                'B-%s/BAPP-SE2026/1373/PL.200/%d',
                $cleanSequence,
                $tahun,
            );
        }

        $roman = self::TERMIN_CONFIG[$termin]['roman'] ?? ($termin === 2 ? 'II' : 'I');

        return sprintf(
            'B-%s/BAPP-%s-SE2026/1373/PL.200/%d',
            $cleanSequence,
            $roman,
            $tahun,
        );
    }

    private function extractManualDocumentSequence(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        if (preg_match('/^B-(\d+)\//', (string) $number, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('/^\d+$/', trim((string) $number)) === 1
            ? trim((string) $number)
            : null;
    }
}
