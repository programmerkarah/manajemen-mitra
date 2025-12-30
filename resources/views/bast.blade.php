<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ $nomor_bast }} - BAST Petugas - {{ $nama_kegiatan }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 3cm 3cm 3cm 4cm;
        }

        @page landscape {
            size: A4 landscape;
            margin: 2cm 2cm 2cm 2cm;
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
			font-weight: bold;
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
            line-height: 1.6;
            color: #000;
            orphans: 3;
            widows: 3;
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .title {
            font-size: 13pt;
        }

        .nomor {
            font-size: 11pt;
        }

        .content {
            text-align: justify;
            margin: 20px 0;
        }

        .indent {
            text-indent: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }

        .landscape-page {
            page: landscape;
            page-break-before: always;
        }

        table th,
        table td {
            border: 1px solid #000;
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

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            vertical-align: top;
        }

        .signature-left {
            float: left;
        }

        .signature-right {
            float: right;
        }

        .signature-name {
            margin-top: 80px;
            font-weight: bold;
        }

        .signature-nip {
            margin-top: 5px;
        }

        ul {
            margin: 10px 0;
            padding-left: 40px;
        }

        li {
            margin: 5px 0;
            page-break-inside: avoid;
        }

        /* Lampiran formatting */
        .lampiran-meta {
            float: right;
            width: 40%;
            text-align: right;
            font-size: 10pt;
        }

        .lampiran-title {
            text-align: center;
            font-weight: bold;
        }

        .lampiran-subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 6px;
        }

        .lampiran-table {
            margin-top: 12px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 11pt;
            background: #fff;
        }

        .lampiran-table th {
            background-color: #e9ecef;
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
            font-size: 11pt;
            border-bottom: 2px solid #bbb;
        }

        .lampiran-table td {
            padding: 8px 12px;
            vertical-align: top;
            font-size: 10.5pt;
        }

        .lampiran-table tbody tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        .lampiran-table tbody tr:hover {
            background-color: #e2eafc;
        }

        .lampiran-totals {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @if(!($render_lampiran ?? false))
    <div class="header">
        <div class="title">BERITA ACARA SERAH TERIMA</div>
        <div class="title">HASIL PEKERJAAN PETUGAS PENDATAAN LAPANGAN</div>
        <div class="title">{{ strtoupper($nama_kegiatan) }}</div>
        <div class="title">BULAN {{ strtoupper($bulan_label ?? '') }} TAHUN {{ $tahun ?? date('Y') }}</div>
        <div class="title">PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
    </div>
    <div class="nomor" style="margin-top: 15px; text-align: center;">Nomor: {{ $nomor_bast }}</div>

    <div class="content">
        <p>
            Pada hari ini {{ $hari ?? 'Jum\'at' }}, tanggal {{ tanggalTerbilang($tanggal_angka ?? date('d')) }}, bulan {{ $bulan_label ?? '-' }} tahun {{ tahunTerbilang($tahun ?? date('Y')) }} bertempat di Kantor Badan Pusat Statistik Kota Sawahlunto, kami yang bertanda tangan di bawah ini:
        </p>

        <table style="border: none;">
            <tr style="border: none;">
                <td style="width: 5%; border: none; vertical-align: top;">1.</td>
                <td style="width: 20%; border: none; vertical-align: top;">Nama</td>
                <td style="width: 1%; border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $nama_ppk }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">NIP</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $nip_ppk ?? '-' }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Jabatan</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">{{ $jabatan_ppk ?? 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto untuk Program Penyediaan dan Pelayanan Informasi Statistik' }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Unit Kerja</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">Badan Pusat Statistik Kota Sawahlunto</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Alamat Unit Kerja</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto</td>
            </tr>
        </table>

        <span>bertindak untuk dan atas nama BPS Kota Sawahlunto, selanjutnya disebut sebagai <strong>PIHAK PERTAMA.</strong></span>

        <table style="border: none; margin: 5px 0 20px 0;">
            <tr style="border: none;">
                <td style="width: 5%; border: none; vertical-align: top;">2.</td>
                <td style="width: 20%; border: none; vertical-align: top;">Nama</td>
                <td style="width: 1%; border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $nama_ketua_tim }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">NIP</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $nip_ketua_tim ?? '-' }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Jabatan</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">Ketua Tim Kegiatan {{ $nama_kegiatan }} Tahun {{ $tahun ?? date('Y') }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Alamat</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto</td>
            </tr>
        </table>
        <div style="page-break-inside: avoid;">bertindak sebagai Ketua Tim {{$nama_kegiatan}} Tahun {{ $tahun ?? date('Y') }} serta atas nama petugas kegiatan yang selanjutnya disebut sebagai <strong>PARA PETUGAS</strong> sebagaimana terlampir dalam Lampiran Berita Acara ini, selanjutnya disebut <strong>PIHAK KEDUA</strong></div>


        <p style="margin-top: 10px; text-align: justify;">
            Dengan ini menyatakan:
        </p>

        <ol style="margin: 10px 0 0 20px;">
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> selaku Ketua Tim {{$nama_kegiatan}} Tahun {{$tahun}} telah melakukan fungsi pengendalian dan pengawasan atas pelaksanaan kegiatan {{$nama_kegiatan}} Tahun {{$tahun}} pelaksanaan bulan {{ $bulan_label ?? '' }} Tahun {{ $tahun }} yang dilaksanakan oleh petugas, sesuai dengan tugas dan kewenangan yang diberikan oleh <strong>PIHAK PERTAMA</strong>.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> menyatakan bahwa pelaksanaan kegiatan sebagaimana dimaksud pada angka 1 telah diselesaikan sesuai dengan ketentuan dalam Perjanjian Kerja yang ditandatangani oleh <strong>PIHAK PERTAMA</strong> dan <strong>PARA PETUGAS</strong>, dan hasil kegiatan tersebut sudah diverifikasi sesuai prosedur yang berlaku.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                Berdasarkan ketentuan pada angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil pekerjaan kepada <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil pekerjaan tersebut.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                Hasil pekerjaan berupa jenis dokumen, jumlah dan rincian hasil kegiatan sebagaimana dimaksud pada angka 3 tercantum dalam Lampiran Berita Acara ini sebagai bagian yang tidak terpisahkan dari Berita Acara ini.
            </li>
            @if($menggunakan_fasih)
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> menyatakan bahwa sudah menyaksikan <strong>PARA PETUGAS</strong> dalam menghapus Aplikasi FASIH dan data hasil Pendataan Lapangan pada perangkat handphone <strong>PARA PETUGAS.</strong>
            </li>
            @endif
            <li style="margin: 6px 0; text-align: justify;">
                Dalam hal terdapat hasil pendataan lapangan yang memerlukan pemeriksaan lanjutan, maka akan dilakukan pengecekan, perbaikan, dan/atau kunjungan kembali ke lapangan sesuai dengan ketentuan yang berlaku dengan tetap berpedoman pada ketentuan dalam Perjanjain Kerja yang telah ditandatangani oleh <strong>PIHAK PERTAMA</strong> dan <strong>PARA PETUGAS</strong>.
            </li>
        </ol>

        <div style="page-break-inside: avoid;">
            <p style="margin-top: 20px; text-align: justify;">
                Demikian Berita Acara ini dibuat dengan sebenarnya dalam rangkap 2 (dua) dan menjadi sah berlaku setelah ditandatangani oleh <strong>PARA PIHAK</strong>.
            </p>
            <div style="height: 10px;"></div>
            <div class="signature-section">
                <div class="signature-box signature-left">
                    <div>PIHAK KEDUA,</div>
                    <div style="height:10px;"></div>
                    <div class="signature-name">{{ strtoupper($nama_ketua_tim) }}</div>
                </div>
                <div class="signature-box signature-right">
                    <div>PIHAK PERTAMA,</div>
                    <div style="height:10px;"></div>
                    <div class="signature-name">{{ strtoupper($nama_ppk ?? '') }}</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    </div>
</body>

</html>