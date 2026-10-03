<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\PetugasExistingExport;
use App\Exports\PetugasTemplateExport;
use App\Http\Requests\BatchUpdatePetugasRequest;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StorePetugasRequest;
use App\Http\Requests\UpdatePetugasRequest;
use App\Imports\PetugasImport;
use App\Imports\PetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\Petugas;
use App\Services\PetugasImportProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

trait PetugasDetailSupport
{
    public function show(string $petuga): Response
    {
        $id = Hashids::decode($petuga)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $petugas = Petugas::query()
            ->findOrFail($id);
        $petugas->load(['alokasi.periodeAlokasi.kegiatan.rateHonors.satuan']);

        // Priority order for picking the effective periode per kegiatan per bulan/tahun.
        // Same logic used in BastController and SpkController.
        $statusPriority = [
            'perubahan' => 4,
            'direvisi' => 3,
            'disetujui' => 2,
            'dikirim' => 1,
            'draft' => 0,
        ];

        // Group by (kegiatan_id, bulan, tahun) and keep only the effective one per group.
        $effectiveAlokasi = $petugas->alokasi
            ->reject(fn ($alok) => $alok->periodeAlokasi->status === 'dihapus')
            ->groupBy(fn ($alok) => $alok->periodeAlokasi->kegiatan_id
                .'_'.$alok->periodeAlokasi->bulan
                .'_'.$alok->periodeAlokasi->tahun)
            ->map(fn ($group) => $group
                ->sortByDesc(fn ($alok) => $statusPriority[$alok->periodeAlokasi->status] ?? -1)
                ->first()
            )
            ->filter()
            ->reject(fn ($alok) => ($alok->jumlah_satuan ?? 0) <= 0 && ($alok->jumlah_satuan_listing ?? 0) <= 0)
            ->values();

        // Transform alokasi to include bulan, tahun, jenis_kegiatan from periode
        $effectiveAlokasi->each(function ($alok) {
            $periode = $alok->periodeAlokasi;
            $alok->bulan = (int) $periode->bulan;
            $alok->tahun = $periode->tahun;
            $alok->jenis_kegiatan = $periode->jenis_kegiatan;
            $alok->status = $periode->status;
            $alok->kegiatan = $periode->kegiatan;

            // Find the appropriate rate_honor based on petugas type and peran
            $rateHonor = $periode->kegiatan->rateHonors->first(function ($rate) use ($alok) {
                return $rate->status_kepegawaian === $alok->status_kepegawaian
                    && $rate->jenis_penugasan === $alok->peran;
            });

            if ($rateHonor) {
                $alok->rate_honor = [
                    'posisi' => $this->getPositionLabel($alok->peran),
                    'rate' => $alok->jumlah_satuan > 0
                        ? $alok->total_honor / $alok->jumlah_satuan
                        : ($alok->jumlah_satuan_listing > 0 ? $alok->total_honor_listing / $alok->jumlah_satuan_listing : 0),
                    'satuan' => [
                        'nama' => $rateHonor->satuan->nama ?? '-',
                    ],
                ];
            } else {
                $alok->rate_honor = [
                    'posisi' => $this->getPositionLabel($alok->peran),
                    'rate' => $alok->jumlah_satuan > 0
                        ? $alok->total_honor / $alok->jumlah_satuan
                        : ($alok->jumlah_satuan_listing > 0 ? $alok->total_honor_listing / $alok->jumlah_satuan_listing : 0),
                    'satuan' => ['nama' => '-'],
                ];
            }

            unset($alok->periodeAlokasi);
        });

        $petugas->setRelation('alokasi', $effectiveAlokasi);

        $riwayatAlokasiRingkas = $effectiveAlokasi
            ->groupBy(fn ($alok) => $alok->kegiatan->kode_kegiatan.'|'.$alok->kegiatan->nama_kegiatan)
            ->map(function ($group) {
                $first = $group->first();
                $details = $group
                    ->sortByDesc(fn ($item) => sprintf('%04d%02d', (int) $item->tahun, (int) $item->bulan))
                    ->values()
                    ->map(function ($item) {
                        $tahapan = [];
                        if ((int) ($item->jumlah_satuan ?? 0) > 0) {
                            $tahapan[] = 'Pendataan';
                        }
                        if ((int) ($item->jumlah_satuan_listing ?? 0) > 0) {
                            $tahapan[] = 'Listing';
                        }

                        return [
                            'id' => $item->id,
                            'posisi' => $item->rate_honor['posisi'] ?? '-',
                            'periode' => [
                                'bulan' => (int) $item->bulan,
                                'tahun' => (int) $item->tahun,
                            ],
                            'tahapan' => $tahapan,
                            'jumlah_satuan' => (int) ($item->jumlah_satuan ?? 0),
                            'jumlah_satuan_listing' => (int) ($item->jumlah_satuan_listing ?? 0),
                            'satuan' => $item->rate_honor['satuan']['nama'] ?? '-',
                            'total_honor' => (float) (
                                (float) ($item->total_honor ?? 0)
                                + (float) ($item->total_honor_listing ?? 0)
                            ),
                            'status' => $item->status,
                        ];
                    })
                    ->values();

                return [
                    'kegiatan' => [
                        'kode_kegiatan' => $first->kegiatan->kode_kegiatan,
                        'nama_kegiatan' => $first->kegiatan->nama_kegiatan,
                    ],
                    'jumlah_periode' => $details->count(),
                    'total_honor' => (float) $details->sum('total_honor'),
                    'details' => $details->toArray(),
                ];
            })
            ->sortBy(fn ($item) => $item['kegiatan']['nama_kegiatan'])
            ->values()
            ->all();

        // Build monthly trend data for the active year (Jan–Des).
        $activeYear = (int) date('Y');
        $trenAlokasi = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanStr = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            $alokasiPerBulan = $effectiveAlokasi->filter(
                fn ($alok) => $alok->tahun === $activeYear && (int) $alok->bulan === $bulan
            );

            $trenAlokasi[] = [
                'bulan' => $bulanStr,
                'jumlah_kegiatan' => $alokasiPerBulan->count(),
                'total_honor' => (float) $alokasiPerBulan->sum(
                    fn ($alok) => ($alok->total_honor ?? 0) + ($alok->total_honor_listing ?? 0)
                ),
            ];
        }

        return Inertia::render('Petugas/Show', [
            'petugas' => $petugas,
            'tren_alokasi' => $trenAlokasi,
            'active_year' => $activeYear,
            'riwayat_alokasi_ringkas' => $riwayatAlokasiRingkas,
        ]);
    }

    private function getPositionLabel(string $jenisPenugasan): string
    {
        return match ($jenisPenugasan) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            default => $jenisPenugasan,
        };
    }
}
