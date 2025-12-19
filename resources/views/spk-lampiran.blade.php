<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lampiran SPK - {{ $petugas->nama }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm 2cm;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("fonts/Bookman Old Style.TTF") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("fonts/Bookman Old Style Bold.TTF") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Bookman Old Style', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
        }

        .lampiran-header {
            margin-bottom: 20px;
        }

        .lampiran-title {
            text-align: right;
            line-height: 1.4;
            font-size: 10pt;
        }

        .lampiran-subtitle {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
        }

        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        table td.left {
            text-align: left;
        }

        table td.right {
            text-align: right;
        }

        .table-footer {
            font-style: italic;
            text-align: left;
            padding-left: 10px;
        }
    </style>
</head>
<body>
    <div class="lampiran-header">
        <div class="lampiran-title">
            Lampiran<br>
            PERJANJIAN KERJA PETUGAS LAPANGAN KEGIATAN {{ strtoupper($kegiatan->nama_kegiatan) }} TAHUN {{ $kegiatan->tahun_anggaran }} PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
            <strong>NOMOR: {{ $nomorSpk }}</strong>
        </div>
    </div>

    <div class="lampiran-subtitle">
        DAFTAR URAIAN TUGAS, JANGKA WAKTU, NILAI PERJANJIAN, DAN BEBAN ANGGARAN
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Uraian Tugas</th>
                <th rowspan="2">Jangka Waktu</th>
                <th colspan="2">Target Pekerjaan</th>
                <th rowspan="2">Harga Satuan<br>(Rp)</th>
                <th rowspan="2">Jumlah<br>(Rp)</th>
                <th rowspan="2">Beban Anggaran</th>
            </tr>
            <tr>
                <th>Volume</th>
                <th>Satuan</th>
            </tr>
            <tr>
                <th>(1)</th>
                <th>(2)</th>
                <th>(3)</th>
                <th>(4)</th>
                <th>(5)</th>
                <th>(6)</th>
                <th>(7)</th>
                <th>(8)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($uraianTugas as $index => $tugas)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="left">{{ $tugas['uraian'] }}</td>
                <td>{{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->format('d') }}-{{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth()->format('d') }} {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->locale('id')->translatedFormat('F Y') }}</td>
                <td>{{ $tugas['volume'] }}</td>
                <td>{{ $tugas['satuan'] }}</td>
                <td class="right">{{ number_format($tugas['harga_satuan'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($tugas['jumlah'], 0, ',', '.') }}</td>
                <td>{{ $bebanAnggaran }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="6" class="table-footer"><strong>TOTAL NILAI PERJANJIAN</strong></td>
                <td colspan="2" class="table-footer"><strong>Terbilang: {{ terbilang($totalHonor) }} rupiah</strong></td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right;"><strong>{{ number_format($totalHonor, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
