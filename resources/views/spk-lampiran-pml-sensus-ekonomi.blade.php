<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $pdfTitle ?? 'Lampiran SPK PML Sensus Ekonomi - ' . $petugas->nama }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm 1.5cm;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        body {
            font-family: 'Bookman Old Style', 'Times New Roman', serif;
            font-size: 10pt;
            line-height: 1.2;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .header-title {
            font-size: 10pt;
            line-height: 1.2;
        }

        .section-title {
            margin: 6px 0 4px;
            font-size: 10pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9pt;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tbody.keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        tr.keep-together-row {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        td.center {
            text-align: center;
            vertical-align: middle;
        }

        td.left {
            text-align: left;
        }

        td.volume-cell {
            overflow-wrap: normal;
            word-break: normal;
            hyphens: none;
            line-height: 1.2;
            font-size: 9pt;
        }

        td.right {
            text-align: right;
            vertical-align: middle;
        }

        .money-cell {
            white-space: nowrap;
        }

        table.daftar-pl-table {
            margin-top: 4px;
        }
    </style>
</head>
<body>
    @php($specialLampiran = $lampiranPayload ?? [])

    <div class="header">
        <div class="header-title">
            LAMPIRAN<br>
            PERJANJIAN KERJA PETUGAS PEMERIKSA LAPANGAN<br>
            SENSUS EKONOMI 2026<br>
            PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
            NOMOR: {{ $nomorSpk }}
        </div>
    </div>

    <div class="section-title">
        I. DAFTAR URAIAN PEKERJAAN, WAKTU PENYELESAIAN, TARGET PEKERJAAN DAN NILAI PERJANJIAN
    </div>

    <table>
        <colgroup>
            <col style="width: 40%;">
            <col style="width: 20%;">
            <col style="width: 9%;">
            <col style="width: 20%;">
            <col style="width: 11%;">
        </colgroup>
        <thead>
            <tr style="height: 0; line-height: 0; font-size: 0;">
                <td style="width: 40%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                <td style="width: 20%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                <td style="width: 9%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                <td style="width: 20%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                <td style="width: 11%; padding: 0; border: none; height: 0; line-height: 0;"></td>
            </tr>
            <tr>
                <th rowspan="2">Uraian Pekerjaan</th>
                <th rowspan="2">Waktu Penyelesaian</th>
                <th colspan="2">Target Pekerjaan</th>
                <th rowspan="2">Nilai Perjanjian</th>
            </tr>
            <tr>
                <th>Persentase</th>
                <th>Volume</th>
            </tr>
            <tr>
                <th style="width: 40%;">(1)</th>
                <th style="width: 20%;">(2)</th>
                <th style="width: 9%;">(3)</th>
                <th style="width: 20%;">(4)</th>
                <th style="width: 11%;">(5)</th>
            </tr>
        </thead>
        @php($itemCounter = 1)
        @foreach(($specialLampiran['groups'] ?? []) as $group)
        @php($groupItems = $group['items'] ?? [])
        @php($groupCount = count($groupItems))
        <tbody class="keep-together">
            @foreach($groupItems as $index => $item)
            @php($formattedItem = preg_replace('/door to door/i', '<em>$0</em>', e($item)))
            <tr class="keep-together-row">
                <td class="left">{{ $itemCounter++ }}. {!! $formattedItem !!}</td>
                @if($index === 0)
                <td class="center" rowspan="{{ $groupCount }}">{{ $group['waktu_penyelesaian'] ?? '-' }}</td>
                <td class="center" rowspan="{{ $groupCount }}">{{ $group['persentase'] ?? '-' }}</td>
                <td class="left volume-cell" rowspan="{{ $groupCount }}">{{ $group['volume'] ?? '-' }}</td>
                <td class="right money-cell" rowspan="{{ $groupCount }}">Rp {{ number_format((float) ($group['nilai_perjanjian'] ?? 0), 0, ',', '.') }},00</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        @endforeach
        <tbody class="keep-together">
            <tr class="keep-together-row">
                <td class="left">Total</td>
                <td class="center">{{ $specialLampiran['total']['waktu_penyelesaian'] ?? '-' }}</td>
                <td rowspan="2" class="center">{{ $specialLampiran['total']['persentase'] ?? '-' }}</td>
                <td rowspan="2" class="left volume-cell">{{ $specialLampiran['total']['volume'] ?? '-' }}</td>
                <td rowspan="2" class="right money-cell">Rp {{ number_format((float) ($specialLampiran['total']['nilai_perjanjian'] ?? 0), 0, ',', '.') }},00</td>
            </tr>
            <tr class="keep-together-row">
                <td colspan="2" class="center"><em>Terbilang: {{ terbilang($totalHonor) }} rupiah</em></td>
            </tr>
        </tbody>
    </table>

    @if(!empty($specialLampiran['wilayah_kerja']))
    <div style="page-break-before: always; break-before: page;">
        <div class="header">
            <div class="header-title">
                LAMPIRAN<br>
                PERJANJIAN KERJA PETUGAS PEMERIKSA LAPANGAN<br>
                SENSUS EKONOMI 2026<br>
                PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
                NOMOR: {{ $nomorSpk }}
            </div>
        </div>

        <div class="section-title">
            II. DAFTAR WILAYAH KERJA
        </div>

        <table class="daftar-pl-table">
            <colgroup>
                <col style="width: 6%;">
                <col style="width: 35%;">
                <col style="width: 40%;">
                <col style="width: 19%;">
            </colgroup>
            <thead>
                <tr style="height: 0; line-height: 0; font-size: 0;">
                    <td style="width: 6%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                    <td style="width: 35%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                    <td style="width: 40%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                    <td style="width: 19%; padding: 0; border: none; height: 0; line-height: 0;"></td>
                </tr>
                <tr>
                    <th>No</th>
                    <th>Kecamatan</th>
                    <th>Desa/Kelurahan</th>
                    <th>Jumlah SLS/Sub-SLS</th>
                </tr>
                <tr>
                    <th>(1)</th>
                    <th>(2)</th>
                    <th>(3)</th>
                    <th>(4)</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($specialLampiran['wilayah_kerja'] ?? []) as $wilayah)
                <tr>
                    <td class="center">{{ $wilayah['no'] }}</td>
                    <td class="left">{{ $wilayah['kecamatan'] }}</td>
                    <td class="left">{{ $wilayah['desa'] }}</td>
                    <td class="center">{{ $wilayah['jumlah_sls'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $pageNumberOffset = {{ (int) ($pageNumberOffset ?? 0) }};

            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageNumberOffset) {
                $displayPage = $pageNumber + $pageNumberOffset;
                if ($displayPage <= 1) {
                    return;
                }

                $topMargin = 28.35; // 1 cm

                $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
                $size = 10;
                $text = '-' . $displayPage . '-';
                $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
                $x = ($canvas->get_width() - $textWidth) / 2;
                $y = $topMargin / 2;

                $canvas->text($x, $y, $text, $font, $size);
            });
        }
    </script>
</body>
</html>
