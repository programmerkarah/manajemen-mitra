<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lampiran I - {{ $nomor_bapp ?? 'BAPP' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("/Bookman Old Style Normal.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("/Bookman Old Style Bold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("/Bookman Old Style Italic.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Bookman Old Style', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            margin: 1cm 1.5cm;
            padding: 0;
        }

        .page-number {
            text-align: center;
            margin-bottom: 10px;
        }

        .lampiran-header {
            text-align: center;
            margin-bottom: 14px;
            line-height: 1.4;
        }

        .section-title {
            font-weight: bold;
            margin: 12px 0 6px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
        }

        table.data-table td.center {
            text-align: center;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table td.left {
            text-align: left;
        }
    </style>
</head>
<body>
@php
    $terminRoman = $termin_roman ?? 'I';
    $terminNumber = $termin_number ?? 1;
    $persentase = $persentase ?? 40;
    $isPetugasLapangan = ($jenis_pihak_kedua ?? 'petugas_lapangan') === 'petugas_lapangan';
    $isUsahaBesar = $is_usaha_besar ?? false;
    $namaKabkota = $nama_kabkota ?? '…………..';
    $nomorBapp = $nomor_bapp ?? '__________________';
    $nilaiPerjanjian = $nilai_perjanjian ?? 0;
    $terbilangNilai = $nilaiPerjanjian > 0
        ? ucfirst(trim(terbilang((int) $nilaiPerjanjian))) . ' rupiah'
        : '……………………………………………………';
    $nilaiFormatted = $nilaiPerjanjian > 0
        ? 'Rp ' . number_format((float) $nilaiPerjanjian, 0, ',', '.') . ',00'
        : 'Rp …………, 00';

    $targetSlsFormatted = $target_sls !== null ? number_format((int)$target_sls, 0, ',', '.') : '(jumlah)';
    $realisasiSlsFormatted = $realisasi_sls !== null ? number_format((int)$realisasi_sls, 0, ',', '.') : '…';

    if ($isUsahaBesar) {
        $targetUsahaBesar = $target_unit_sampel['usaha_besar'] ?? null;
        $realisasiText = 'Telah mencapai target pekerjaan sebesar ' . $realisasiSlsFormatted . ' usaha besar';
    } else {
        $targetUnits = $target_unit_sampel ?? [];
        $realisasiUnits = $realisasi_unit_sampel ?? [];
        $rParts = [];
        foreach ($realisasiUnits as $unitNama => $count) {
            if ($count > 0) {
                $rParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
            }
        }
        $realisasiUnitText = !empty($rParts) ? ' dan/atau ' . implode('/', $rParts) : '';
        $realisasiText = 'Telah mencapai target pekerjaan sebesar ' . $realisasiSlsFormatted . ' SLS/Sub-SLS' . $realisasiUnitText;
    }

    $judulJenisOfficer = $isPetugasLapangan ? 'PETUGAS LAPANGAN' : 'PEMERIKSA LAPANGAN';
@endphp

<div class="lampiran-header">
    LAMPIRAN<br>
    BERITA ACARA PEMERIKSAAN PEKERJAAN TERMIN {{ $terminRoman }}<br>
    {{ $judulJenisOfficer }} SENSUS EKONOMI 2026<br>
    PADA BADAN PUSAT STATISTIK KABUPATEN/KOTA {{ strtoupper($namaKabkota) }}<br>
    NOMOR: {{ $nomorBapp }}
</div>

<div class="section-title">I.&nbsp;&nbsp;&nbsp;DAFTAR URAIAN PEKERJAAN, WAKTU PENYELESAIAN, TARGET PEKERJAAN, REALISASI PEKERJAAN DAN NILAI PERJANJIAN</div>

@if($isUsahaBesar)
<table class="data-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:30%;">Uraian Pekerjaan</th>
            <th rowspan="2" style="width:14%;">Waktu<br>Penyelesaian</th>
            <th colspan="3">Target Pekerjaan</th>
            <th rowspan="2" style="width:20%;">Realisasi Pekerjaan</th>
            <th rowspan="2" style="width:14%;">Nilai Perjanjian</th>
        </tr>
        <tr>
            <th style="width:8%;">Presentase</th>
            <th style="width:8%;">Volume</th>
            <th style="width:10%;">SATUAN</th>
        </tr>
        <tr>
            <th>(1)</th>
            <th>(2)</th>
            <th>(3)</th>
            <th>(4)</th>
            <th>(5)</th>
            <th>(6)</th>
            <th>(7)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="left">
                @if(!$isPetugasLapangan)
                <ol style="margin:0; padding-left:16px;">
                    <li>Melakukan pemeriksaan hasil pendataan Petugas Lapangan usaha besar Sensus Ekonomi 2026 termin {{ $terminRoman }}</li>
                    <li>Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan usaha besar Sensus Ekonomi 2026</li>
                </ol>
                @else
                Melakukan pendataan lapangan usaha besar Sensus Ekonomi 2026 termin {{ $terminRoman }}
                @endif
            </td>
            <td class="center">Minimal 1 bulan</td>
            <td class="center">{{ $persentase }}%</td>
            <td class="center">…</td>
            <td class="center">usaha besar</td>
            <td class="left">{{ $realisasiText }}</td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
        <tr>
            <td colspan="6" class="left" style="font-style:italic;">
                Terbilang: {{ $terbilangNilai }}
            </td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
    </tbody>
</table>
@else
<table class="data-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:33%;">Uraian Pekerjaan</th>
            <th rowspan="2" style="width:14%;">Waktu<br>Pekerjaan</th>
            <th colspan="2">Target Pekerjaan</th>
            <th rowspan="2" style="width:22%;">Realisasi Pekerjaan</th>
            <th rowspan="2" style="width:17%;">Nilai Perjanjian</th>
        </tr>
        <tr>
            <th style="width:9%;">Presentase</th>
            <th style="width:14%;">Volume</th>
        </tr>
        <tr>
            <th>(1)</th>
            <th>(2)</th>
            <th>(3)</th>
            <th>(4)</th>
            <th>(5)</th>
            <th>(6)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="left">
                @if(!$isPetugasLapangan)
                <ol style="margin:0; padding-left:16px;">
                    <li>Melakukan pemeriksaan hasil pendataan Petugas Lapangan <em>door to door</em> Sensus Ekonomi 2026 termin {{ $terminRoman }}</li>
                    <li>Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan <em>door to door</em> Sensus Ekonomi 2026</li>
                </ol>
                @else
                Melakukan pendataan lapangan <em>door to door</em> Sensus Ekonomi 2026 termin {{ $terminRoman }}
                @endif
            </td>
            <td class="center">Minimal 1 bulan</td>
            <td class="center">{{ $persentase }}%</td>
            <td class="left" style="font-size:10pt;">
                {{ $targetSlsFormatted }} SLS/Sub-SLS
                @if(!empty($target_unit_sampel))
                @php
                    $unitParts = [];
                    foreach ($target_unit_sampel as $unitNama => $count) {
                        if ($count > 0) {
                            $unitParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
                        }
                    }
                @endphp
                @if(!empty($unitParts))
                dan/atau {{ implode('/', $unitParts) }}
                @endif
                @endif
            </td>
            <td class="left">{{ $realisasiText }}</td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
        <tr>
            <td colspan="5" class="left" style="font-style:italic;">
                Terbilang: {{ $terbilangNilai }}
            </td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
    </tbody>
</table>
@endif

<script type="text/php">
    if (isset($pdf) && isset($fontMetrics)) {
        $pageOffset = {{ (int)($page_number_offset ?? 2) }};
        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageOffset) {
            $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
            $size = 10;
            $text = '-' . ($pageNumber + $pageOffset) . '-';
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($canvas->get_width() - $textWidth) / 2;
            $y = 18; // ~0.6cm from top, inside top margin area
            $canvas->text($x, $y, $text, $font, $size);
        });
    }
</script>

</body>
</html>
