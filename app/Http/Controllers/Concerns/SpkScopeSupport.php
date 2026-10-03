<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use App\Services\SensusEkonomiReplacementReadService;
use App\Services\SpkActionDecisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use Vinkla\Hashids\Facades\Hashids;

trait SpkScopeSupport
{
    private function getNextNomorUrut(int $tahun): int
    {
        $usedNumbers = Spk::query()
            ->where('nomor_spk', 'like', "PPIS/13730/%/K/{$tahun}")
            ->whereNull('deleted_at')
            ->get()
            ->map(fn (Spk $spk): int => $this->extractNomorUrut((string) $spk->nomor_spk))
            ->filter(fn (int $nomor): bool => $nomor > 0)
            ->unique()
            ->sort()
            ->values();

        if ($usedNumbers->isEmpty()) {
            return 1;
        }

        $maxNomor = (int) $usedNumbers->last();

        for ($candidate = 1; $candidate <= $maxNomor + 1; $candidate++) {
            if (! $usedNumbers->contains($candidate)) {
                return $candidate;
            }
        }

        return $maxNomor + 1;
    }

    private function getNextNomorUrutForPeriode(PeriodeAlokasi $periode): int
    {
        if (! $this->usesPeriodBasedSpkFlow($periode)) {
            return $this->getNextNomorUrut((int) $periode->tahun);
        }

        $lastSpk = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', (int) $periode->tahun)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($periode) {
                $query->where('kegiatan_id', $periode->kegiatan_id);
            })
            ->orderByDesc('nomor_urut_base')
            ->first();

        if (! $lastSpk) {
            return 1;
        }

        $lastUrut = (int) ($lastSpk->nomor_urut_base ?? 0);
        if ($lastUrut <= 0) {
            $lastUrut = $this->extractNomorUrut((string) $lastSpk->nomor_spk);
        }

        return $lastUrut + 1;
    }

    private function formatNomorSpkForPeriode(PeriodeAlokasi $periode, int $nomorUrut): string
    {
        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return sprintf('B-%03d/SPK-SE2026/1373/PL.200/%d', $nomorUrut, (int) $periode->tahun);
        }

        return 'PPIS/13730/'.$nomorUrut.'/K/'.$periode->tahun;
    }

    private function extractNomorUrut(string $nomorSpk): int
    {
        if (preg_match('/^B-(\d+)/i', $nomorSpk, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }

        $parts = explode('/', $nomorSpk);
        if (! isset($parts[2])) {
            return 0;
        }

        // Remove suffix letters (e.g., "4A" -> "4")
        $nomorWithSuffix = $parts[2];

        return (int) preg_replace('/[^0-9]/', '', $nomorWithSuffix);
    }

    private function resolveDisplayNomorUrutSegment(string $nomorSpk, int $nomorUrutBase, ?string $fallbackSuffix = null): string
    {
        $nomorSpk = trim($nomorSpk);
        if ($nomorSpk !== '') {
            if (preg_match('/^B-(\d+)([A-Z])?(?:\/|$)/i', $nomorSpk, $matches) === 1) {
                $base = (string) ($matches[1] ?? $nomorUrutBase);
                $suffix = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : '';

                return $base.$suffix;
            }

            if (preg_match('/^PPIS\/13730\/(\d+)([A-Z])?(?:\/|$)/i', $nomorSpk, $matches) === 1) {
                $base = (string) ($matches[1] ?? $nomorUrutBase);
                $suffix = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : '';

                return $base.$suffix;
            }

            if (preg_match('/\/(\d+)([A-Z])?(?:\/|$)/', $nomorSpk, $matches) === 1) {
                $base = (string) ($matches[1] ?? $nomorUrutBase);
                $suffix = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : '';

                return $base.$suffix;
            }
        }

        $suffix = trim((string) ($fallbackSuffix ?? ''));

        return (string) $nomorUrutBase.($suffix !== '' ? strtoupper($suffix) : '');
    }

    private function usesPeriodBasedSpkFlow(PeriodeAlokasi $periode): bool
    {
        return $this->isSensusEkonomi2026($periode->kegiatan);
    }

    private function resolveSpkScopePeriodeIds(PeriodeAlokasi $periode, array $statuses = []): Collection
    {
        $query = PeriodeAlokasi::query();
        $bulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            $query->whereKey($periode->id);
        } else {
            $query->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                ->where('tahun', $periode->tahun)
                ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'));
        }

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        return $query->pluck('id');
    }

    private function hasDraftPeriodeInSpkScope(PeriodeAlokasi $periode): bool
    {
        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return false;
        }

        $bulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);

        return PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $periode->tahun)
            ->where('status', 'draft')
            ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
            ->exists();
    }

    private function baseSpkScopeQuery(PeriodeAlokasi $periode)
    {
        $query = Spk::query()->where(function ($builder) {
            $builder->where('addendum_number', 0)
                ->orWhereNull('addendum_number');
        });

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return $query->whereHas('alokasiPetugas', function ($builder) use ($periode) {
                $builder->where('periode_alokasi_id', $periode->id);
            });
        }

        $bulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);

        // Scope documents through their allocation period, not tanggal_spk.
        // The signing date may be outside the activity month.
        return $query->whereHas('alokasiPetugas.periodeAlokasi', function ($builder) use ($periode, $bulanFormatted) {
            $builder->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                ->where('tahun', $periode->tahun)
                ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'));
        });
    }

    private function resolveSpkIndexGroupKey(PeriodeAlokasi $periode): string
    {
        return sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
    }

    private function resolveSpkIndexPrimaryPeriode(Collection $monthPeriodes): PeriodeAlokasi
    {
        return $monthPeriodes->sort(function (PeriodeAlokasi $left, PeriodeAlokasi $right): int {
            $leftPriority = $this->resolveSpkIndexStatusPriority($left->status);
            $rightPriority = $this->resolveSpkIndexStatusPriority($right->status);

            if ($leftPriority !== $rightPriority) {
                return $rightPriority <=> $leftPriority;
            }

            $leftCreatedAt = $left->created_at?->getTimestamp() ?? 0;
            $rightCreatedAt = $right->created_at?->getTimestamp() ?? 0;

            return $rightCreatedAt <=> $leftCreatedAt;
        })->first();
    }

    private function resolveSpkIndexStatusPriority(string $status): int
    {
        return match ($status) {
            'perubahan' => 4,
            'direvisi' => 3,
            'disetujui' => 2,
            'dikirim' => 1,
            default => 0,
        };
    }

    private function resolveSpkIndexDisplayLabel(PeriodeAlokasi $periode): string
    {
        if (! $this->usesPeriodBasedSpkFlow($periode)) {
            return $this->getBulanLabel((int) $periode->bulan).' '.$periode->tahun;
        }

        if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
            $start = $periode->tanggal_mulai;
            $end = $periode->tanggal_selesai;

            if ($start->year === $end->year) {
                if ($start->month === $end->month) {
                    return $start->translatedFormat('d').'-'.$end->translatedFormat('d F Y');
                }

                return $start->translatedFormat('F').' - '.$end->translatedFormat('F Y');
            }

            return $start->translatedFormat('d F Y').' - '.$end->translatedFormat('d F Y');
        }

        return $this->getBulanLabel((int) $periode->bulan).' '.$periode->tahun;
    }
}
