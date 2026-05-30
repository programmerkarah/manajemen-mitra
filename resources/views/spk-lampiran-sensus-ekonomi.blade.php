<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $pdfTitle ?? 'Lampiran SPK Sensus Ekonomi - ' . $petugas->nama }}</title>
    <style>
        @page {
            size: A4 portrait;
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
            font-size: 10pt;
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

        td.right {
            text-align: right;
            vertical-align: middle;
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
        <tbody>
            @foreach(($specialLampiran['groups'] ?? []) as $group)
                @foreach(($group['items'] ?? []) as $index => $item)
                <tr>
                    <td class="left">{{ $index + 1 }}. {{ $item }}</td>
                    @if($index === 0)
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="center">{{ $group['waktu_penyelesaian'] ?? '-' }}</td>
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="center">{{ $group['persentase'] ?? '-' }}</td>
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="left">{{ $group['volume'] ?? '-' }}</td>
                    <td rowspan="{{ count($group['items'] ?? []) }}" class="right">Rp {{ number_format((float) ($group['nilai_perjanjian'] ?? 0), 0, ',', '.') }}, 00</td>
                    @endif
                </tr>
                @endforeach
            @endforeach
            <tr>
                <td class="left">Total</td>
                <td class="center">{{ $specialLampiran['total']['waktu_penyelesaian'] ?? '-' }}</td>
                <td class="center">{{ $specialLampiran['total']['persentase'] ?? '-' }}</td>
                <td class="left">{{ $specialLampiran['total']['volume'] ?? '-' }}</td>
                <td class="right">Rp {{ number_format((float) ($specialLampiran['total']['nilai_perjanjian'] ?? 0), 0, ',', '.') }}, 00</td>
            </tr>
            <tr>
                <td colspan="2" class="left"><em>Terbilang: {{ terbilang($totalHonor) }} rupiah</em></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>