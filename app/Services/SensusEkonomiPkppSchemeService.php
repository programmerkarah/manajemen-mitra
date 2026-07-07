<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class SensusEkonomiPkppSchemeService
{
    /**
     * Resolve PKPP payment scheme based on contract date.
     *
     * @return array{
     *   code:string,
     *   kontrak_deadline:string,
     *   lapangan_deadline:string,
     *   honor_ob:float,
     *   termin_count:int,
     *   termin_targets:array<int,int>,
     *   termin_shares:array<int,int>,
     *   asuransi:string,
     *   termin_satu_waktu?:string,
     *   termin_akhir_waktu:string,
     *   pasal_7_periode:string
     * }
     */
    public function resolveScheme(CarbonInterface|string $contractDate): array
    {
        $date = $contractDate instanceof CarbonInterface
            ? $contractDate->copy()->startOfDay()
            : Carbon::parse($contractDate)->startOfDay();

        $year = (int) $date->year;

        $schema = [
            [
                'code' => 'scheme_1',
                'kontrak_deadline' => Carbon::create($year, 6, 26)->startOfDay(),
                'lapangan_deadline' => Carbon::create($year, 7, 1)->startOfDay(),
                'honor_ob' => 2.0,
                'termin_count' => 2,
                'termin_targets' => [50, 50],
                'termin_shares' => [50, 50],
                'asuransi' => 'Juli-Agustus Penuh',
                'termin_satu_waktu' => 'Minimal 1 bulan',
                'termin_akhir_waktu' => sprintf('31 Agustus %d', $year),
                'pasal_7_periode' => 'Juli - Agustus',
            ],
            [
                'code' => 'scheme_2',
                'kontrak_deadline' => Carbon::create($year, 7, 2)->startOfDay(),
                'lapangan_deadline' => Carbon::create($year, 7, 6)->startOfDay(),
                'honor_ob' => 1.75,
                'termin_count' => 2,
                'termin_targets' => [50, 50],
                'termin_shares' => [50, 50],
                'asuransi' => 'Juli Proporsional Agustus Penuh',
                'termin_satu_waktu' => 'Minimal 1 bulan',
                'termin_akhir_waktu' => sprintf('31 Agustus %d', $year),
                'pasal_7_periode' => 'Juli - Agustus',
            ],
            [
                'code' => 'scheme_3',
                'kontrak_deadline' => Carbon::create($year, 7, 14)->startOfDay(),
                'lapangan_deadline' => Carbon::create($year, 7, 18)->startOfDay(),
                'honor_ob' => 1.5,
                'termin_count' => 1,
                'termin_targets' => [100],
                'termin_shares' => [100],
                'asuransi' => 'Juli Proporsional Agustus Penuh',
                'termin_akhir_waktu' => sprintf('31 Agustus %d', $year),
                'pasal_7_periode' => 'Juli - Agustus',
            ],
            [
                'code' => 'scheme_4',
                'kontrak_deadline' => Carbon::create($year, 7, 21)->startOfDay(),
                'lapangan_deadline' => Carbon::create($year, 7, 25)->startOfDay(),
                'honor_ob' => 1.25,
                'termin_count' => 1,
                'termin_targets' => [100],
                'termin_shares' => [100],
                'asuransi' => 'Juli Proporsional Agustus Penuh',
                'termin_akhir_waktu' => sprintf('31 Agustus %d', $year),
                'pasal_7_periode' => 'Juli - Agustus',
            ],
            [
                'code' => 'scheme_5',
                'kontrak_deadline' => Carbon::create($year, 7, 27)->startOfDay(),
                'lapangan_deadline' => Carbon::create($year, 8, 1)->startOfDay(),
                'honor_ob' => 1.0,
                'termin_count' => 1,
                'termin_targets' => [100],
                'termin_shares' => [100],
                'asuransi' => 'Agustus Penuh',
                'termin_akhir_waktu' => sprintf('31 Agustus %d', $year),
                'pasal_7_periode' => 'Agustus',
            ],
        ];

        foreach ($schema as $row) {
            if ($date->lessThanOrEqualTo($row['kontrak_deadline'])) {
                return [
                    'code' => $row['code'],
                    'kontrak_deadline' => $row['kontrak_deadline']->toDateString(),
                    'lapangan_deadline' => $row['lapangan_deadline']->toDateString(),
                    'honor_ob' => $row['honor_ob'],
                    'termin_count' => $row['termin_count'],
                    'termin_targets' => $row['termin_targets'],
                    'termin_shares' => $row['termin_shares'],
                    'asuransi' => $row['asuransi'],
                    'termin_satu_waktu' => $row['termin_satu_waktu'] ?? null,
                    'termin_akhir_waktu' => $row['termin_akhir_waktu'],
                    'pasal_7_periode' => $row['pasal_7_periode'],
                ];
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Tanggal kontrak %s berada di luar skema PKPP yang didukung.',
            $date->toDateString(),
        ));
    }
}
