<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $pdfTitle ?? 'Lampiran SPK Sensus Ekonomi - ' . $petugas->nama }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm 2cm;
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
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .header-title {
            font-size: 10.5pt;
            line-height: 1.25;
        }

        .section-title {
            margin: 14px 0 10px;
            font-size: 11pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10pt;
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
            padding: 6px 8px;
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
            line-height: 1.35;
            font-size: 9.5pt;
        }

        td.right {
            text-align: right;
            vertical-align: middle;
        }

        .money-cell {
            white-space: nowrap;
        }

        .nilai-column {
            width: 14%;
        }

        .volume-column {
            width: 24%;
        }

        table.summary-table {
            margin-top: 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>
    @php($specialLampiran = $lampiranPayload ?? [])

    <div class="header">
        <div class="header-title">
            Lampiran<br>
            PERJANJIAN KERJA PETUGAS LAPANGAN<br>
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
            <col style="width: 38%;">
            <col style="width: 17%;">
            <col style="width: 7%;">
            <col class="volume-column">
            <col class="nilai-column">
        </colgroup>
        <thead>
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
                <th>(1)</th>
                <th>(2)</th>
                <th>(3)</th>
                <th>(4)</th>
                <th>(5)</th>
            </tr>
        </thead>
        @foreach(($specialLampiran['groups'] ?? []) as $group)
        <tbody class="keep-together">
                @foreach(($group['items'] ?? []) as $index => $item)
                <tr>
                    <td class="left">{{ $index + 1 }}. {{ $item }}</td>
                    @if($index === 0)
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="center">{{ $group['waktu_penyelesaian'] ?? '-' }}</td>
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="center">{{ $group['persentase'] ?? '-' }}</td>
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="left volume-cell">{{ $group['volume'] ?? '-' }}</td>
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="right money-cell">Rp {{ number_format((float) ($group['nilai_perjanjian'] ?? 0), 0, ',', '.') }},00</td>
                    @endif
                </tr>
                @endforeach
        </tbody>
        @endforeach
    </table>

    <table class="summary-table">
        <colgroup>
            <col style="width: 38%;">
            <col style="width: 17%;">
            <col style="width: 7%;">
            <col class="volume-column">
            <col class="nilai-column">
        </colgroup>
        <thead>
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
                <th>(1)</th>
                <th>(2)</th>
                <th>(3)</th>
                <th>(4)</th>
                <th>(5)</th>
            </tr>
        </thead>
        <tbody class="keep-together">
            <tr class="keep-together-row">
                <td class="left">Total</td>
                <td class="center">{{ $specialLampiran['total']['waktu_penyelesaian'] ?? '-' }}</td>
                <td rowspan="2" class="center">{{ $specialLampiran['total']['persentase'] ?? '-' }}</td>
                <td rowspan="2" class="left volume-cell">{{ $specialLampiran['total']['volume'] ?? '-' }}</td>
                <td rowspan="2" class="right money-cell">Rp {{ number_format((float) ($specialLampiran['total']['nilai_perjanjian'] ?? 0), 0, ',', '.') }},00</td>
            </tr>
            <tr class="keep-together-row">
                <td colspan="2" class="left"><em>Terbilang: {{ terbilang($totalHonor) }} rupiah</em></td>
            </tr>
        </tbody>
    </table>
</body>
</html>