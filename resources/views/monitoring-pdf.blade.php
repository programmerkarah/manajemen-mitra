<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul ?? 'Monitoring' }}</title>
    <style>
        @page {
            margin: 1cm 1.5cm 1.5cm 1.5cm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.35;
        }

        .header {
            text-align: center;
            margin-bottom: 14px;
        }

        .header .agency {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .header .title {
            font-size: 16px;
            font-weight: 700;
            margin-top: 4px;
            text-transform: uppercase;
        }

        .header .subtitle {
            margin-top: 4px;
            font-size: 10px;
            color: #4b5563;
        }

        .table-wrap {
            margin-top: 10px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        table.report th,
        table.report td {
            border: 1px solid #9ca3af;
            padding: 2px 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.report th {
            background: #e5e7eb;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            text-align: center;
        }

        table.report td {
            font-size: 9px;
        }

        .merged-cell {
            text-align: center;
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .signature {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .signature-block {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-heading {
            width: 100%;
        }

        .signature-heading td {
            height: 54px;
            vertical-align: top;
        }

        .signature-gap td {
            height: 40px;
        }

        .signature-name-cell {
            height: 18px;
            vertical-align: bottom;
        }

        .signature-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .signature-title-blank {
            visibility: hidden;
            margin-bottom: 4px;
        }

        .signature-space {
            height: 0;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 0;
        }

        .signature-role {
            margin-top: 2px;
            font-size: 9px;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 8px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $metadataColumns = $frame_metadata_columns ?? [];
        $rows = $rows ?? [];
        $showNamaUsahaColumn = (bool) ($show_nama_usaha_column ?? false);
        $hasNonResponse = collect($rows)->contains(fn ($row): bool => (bool) ($row['status_non_response'] ?? false));
        $targetTotal = collect($rows)->sum(fn ($row): float => (float) ($row['target_unit_total'] ?? 0));
        $realisasiTotal = collect($rows)->sum(function (array $row) use ($hasNonResponse): float {
            if (! $hasNonResponse) {
                return (float) ($row['realisasi_unit_total'] ?? 0);
            }

            return (float) ((! ($row['status_non_response'] ?? false)) ? ($row['realisasi_unit_total'] ?? 0) : 0);
        });
        $nonResponseTotal = collect($rows)->sum(function (array $row) use ($hasNonResponse): float {
            if (! $hasNonResponse) {
                return 0;
            }

            return (float) (($row['status_non_response'] ?? false) ? ($row['target_unit_total'] ?? 0) : 0);
        });
        $percentageTotal = $targetTotal > 0
            ? (($realisasiTotal + $nonResponseTotal) / $targetTotal) * 100
            : 0;
        
        // Hitung lebar kolom agar total = 100%
        $fixedWidth = 24; // No(4) + Pengawas(10) + Pencacah(10)

        if ($showNamaUsahaColumn) {
            $fixedWidth += 10;
        }

        if ($hasNonResponse) {
            $fixedWidth += 32; // Target + Berhasil + Non Response + %
        } else {
            $fixedWidth += 24; // Target + Realisasi + %
        }

        $metadataWidth = count($metadataColumns) > 0
            ? max((100 - $fixedWidth) / count($metadataColumns), 4)
            : 0;

        $formatNumber = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if (is_numeric($value) && (float) $value == (int) $value) {
                return number_format((int) $value, 0, ',', '.');
            }

            if (is_numeric($value)) {
                return number_format((float) $value, 2, ',', '.');
            }

            return trim((string) $value) !== '' ? (string) $value : '-';
        };
        $formatText = static function ($value): string {
            return trim((string) ($value ?? '')) !== '' ? trim((string) $value) : '-';
        };
        $formatDisplayValue = static function ($value): string {
            if (is_array($value)) {
                $code = trim((string) ($value['code'] ?? ''));
                $label = trim((string) ($value['label'] ?? ''));

                if ($code !== '' && $code !== '-' && $label !== '' && $label !== '-' && $label !== $code) {
                    return '['.$code.'] '.$label;
                }

                if ($label !== '' && $label !== '-') {
                    return $label;
                }

                if ($code !== '') {
                    return $code;
                }

                return '-';
            }

            $text = trim((string) ($value ?? ''));

            return $text !== '' ? $text : '-';
        };
    @endphp

    <div class="header">
        <div class="agency">{{ $lokasi ?? 'Badan Pusat Statistik Kota Sawahlunto' }}</div>
        <div class="title">
            {{ $judul ?? 'Monitoring' }}
        </div>
        <div class="subtitle">
            @php
                $periodeMulai = $periode_tanggal_mulai ?? '-';
                $periodeSelesai = $periode_tanggal_selesai ?? '-';
            @endphp

            {{ $kegiatan_nama ?? '-' }} • {{ strtoupper($periodeMulai) !== '-' && strtoupper($periodeSelesai) !== '-' ? $periodeMulai.' s/d '.$periodeSelesai : ($periode_label ?? '-') }}
        </div>
    </div>

    <div class="table-wrap">
        <table class="report">
            <thead>
                <tr>
                    <th style="width:4%;" rowspan="2">No</th>
                    <th style="width:10%;" rowspan="2">Pengawas</th>
                    <th style="width:10%;" rowspan="2">Pencacah</th>

                    @foreach ($metadataColumns as $column)
                        <th style="width:{{ number_format($metadataWidth, 2, '.', '') }}%;" rowspan="2">
                            {{ $column['label'] }}
                        </th>
                    @endforeach

                    @if ($showNamaUsahaColumn)
                        <th style="width:10%;" rowspan="2">Nama Usaha</th>
                    @endif

                    @if ($hasNonResponse)
                        <th style="width:8%;" rowspan="2">Target</th>
                        <th style="width:8%;" rowspan="2">Berhasil Didata</th>
                        <th style="width:8%;" rowspan="2">Non Response</th>
                        <th style="width:8%;" rowspan="2">%</th>
                    @else
                        <th style="width:8%;" rowspan="2">Target</th>
                        <th style="width:8%;" rowspan="2">Realisasi</th>
                        <th style="width:8%;" rowspan="2">%</th>
                    @endif
                </tr>
                <tr></tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        @if ($row['show_pengawas_cell'] ?? false)
                            <td class="merged-cell" rowspan="{{ $row['pengawas_rowspan'] ?? 1 }}">{{ $formatText($row['pengawas_nama'] ?? '-') }}</td>
                        @endif
                        @if ($row['show_pencacah_cell'] ?? false)
                            <td class="merged-cell" rowspan="{{ $row['pencacah_rowspan'] ?? 1 }}">{{ $formatText($row['pencacah_nama'] ?? '-') }}</td>
                        @endif
                        @foreach ($metadataColumns as $column)
                            <td>{{ $formatDisplayValue(data_get($row['metadata_values'] ?? [], $column['code'], '-')) }}</td>
                        @endforeach
                        @if ($showNamaUsahaColumn)
                            <td>{{ $formatText($row['nama_usaha'] ?? '-') }}</td>
                        @endif
                        <td class="text-right">{{ $formatNumber($row['target_unit_total'] ?? 0) }}</td>
                        @if ($hasNonResponse)
                            <td class="text-right">{{ $formatNumber(($row['status_non_response'] ?? false) ? 0 : ($row['realisasi_unit_total'] ?? 0)) }}</td>
                            <td class="text-right">{{ $formatNumber(($row['status_non_response'] ?? false) ? ($row['target_unit_total'] ?? 0) : 0) }}</td>
                            <td class="text-right">{{ $formatNumber($row['persentase'] ?? 0) }}</td>
                        @else
                            <td class="text-right">{{ $formatNumber($row['realisasi_unit_total'] ?? 0) }}</td>
                            <td class="text-right">{{ $formatNumber($row['persentase'] ?? 0) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ 8 + count($metadataColumns) + ($showNamaUsahaColumn ? 1 : 0) }}" class="text-center">Tidak ada data frame sampel yang dapat ditampilkan.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ 3 + count($metadataColumns) + ($showNamaUsahaColumn ? 1 : 0) }}" class="text-right"><strong>Jumlah</strong></td>
                    <td class="text-right"><strong>{{ $formatNumber($targetTotal) }}</strong></td>
                    @if ($hasNonResponse)
                        <td class="text-right"><strong>{{ $formatNumber($realisasiTotal) }}</strong></td>
                        <td class="text-right"><strong>{{ $formatNumber($nonResponseTotal) }}</strong></td>
                        <td class="text-right"><strong>{{ $formatNumber($percentageTotal) }}</strong></td>
                    @else
                        <td class="text-right"><strong>{{ $formatNumber($realisasiTotal) }}</strong></td>
                        <td class="text-right"><strong>{{ $formatNumber($percentageTotal) }}</strong></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>

    <table class="signature">
        <tr>
            <td>
                <table class="signature-block">
                    <tr class="signature-heading">
                        <td>
                            <div class="signature-title">Mengetahui,</div>
                            <div class="signature-title">Kepala Badan Pusat Statistik</div>
                            <div class="signature-title">Kota Sawahlunto</div>
                        </td>
                    </tr>
                    <tr class="signature-gap"><td></td></tr>
                    <tr>
                        <td class="signature-name-cell"><div class="signature-name">{{ $kepala_nama ?? '-' }}</div></td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-block">
                    <tr class="signature-heading">
                        <td>
                            <div class="signature-title">Sawahlunto, {{ $tanggal_pengesahan ?? '-' }}</div>
                            <div class="signature-title">Ketua Tim</div>
                            <div class="signature-title signature-title-blank">&nbsp;</div>
                        </td>
                    </tr>
                    <tr class="signature-gap"><td></td></tr>
                    <tr>
                        <td class="signature-name-cell"><div class="signature-name">{{ $ketua_tim_nama ?? '-' }}</div></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer-note">Dokumen ini dihasilkan otomatis dari data monitoring.</div>
</body>
</html>