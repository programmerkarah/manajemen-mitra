<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>BAST - {{ $petugas->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm 2.5cm;
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
        @font-face {
			font-family: 'Bookman Old Style';
			src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
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
            line-height: 1.6;
            color: #000;
        }

        .bast-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .bast-title {
            font-weight: bold;
            font-size: 12pt;
            line-height: 1.4;
        }

        .content {
            text-align: justify;
            margin: 15px 0;
        }

        .parties {
            margin: 20px 0 20px 30px;
        }

        .party {
            margin-bottom: 15px;
            display: table;
            width: 100%;
        }

        .party-number {
            display: table-cell;
            width: 30px;
            vertical-align: top;
        }

        .party-name {
            display: table-cell;
            width: 200px;
            vertical-align: top;
        }

        .party-colon {
            display: table-cell;
            width: 20px;
            vertical-align: top;
        }

        .party-detail {
            display: table-cell;
            vertical-align: top;
            text-align: justify;
        }

        .pasal-list {
            margin: 10px 0 10px 30px;
        }

        .pasal-item {
            margin: 8px 0;
            display: table;
            width: 100%;
        }

        .pasal-item-number {
            display: table-cell;
            width: 40px;
            vertical-align: top;
        }

        .pasal-item-content {
            display: table-cell;
            vertical-align: top;
            text-align: justify;
        }

        .signature {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-row {
            display: table;
            width: 100%;
        }

        .signature-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 80px;
        }

        .signature-name {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="bast-header">
        <div class="bast-title">
            BERITA ACARA SERAH TERIMA PEKERJAAN<br>
            PETUGAS LAPANGAN KEGIATAN {{ strtoupper($kegiatan->nama_kegiatan) }} TAHUN {{ $kegiatan->tahun_anggaran }}<br>
            PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
            Nomor: {{ $nomorBast }}
        </div>
    </div>

    <div class="content">
        Pada hari ini {{ $tanggalBast->locale('id')->translatedFormat('l') }}, tanggal {{ tanggalTerbilang($tanggalBast->day) }}, bulan {{ $tanggalBast->locale('id')->translatedFormat('F') }}, tahun {{ tahunTerbilang($tanggalBast->year) }}, bertempat di Kantor BPS Kota Sawahlunto, yang bertanda tangan di bawah ini:
    </div>

    <div class="parties">
        <div class="party">
            <div class="party-number">1.</div>
            <div class="party-name">Nama</div>
            <div class="party-colon">:</div>
            <div class="party-detail">
                {{ $penandatangan }}<br>
                Jabatan : Pejabat Pembuat Komitmen BPS Kota Sawahlunto<br>
                Alamat Kantor : Jalan Bagindo Aziz Chan Kelurahan Aur Mulyo Kecamatan Lembah Segar Kota Sawahlunto, yang selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.
            </div>
        </div>

        <div class="party">
            <div class="party-number">2.</div>
            <div class="party-name">Nama</div>
            <div class="party-colon">:</div>
            <div class="party-detail">
                {{ strtoupper($petugas->nama) }}<br>
                Jabatan : Petugas Lapangan Kegiatan {{ $kegiatan->nama_kegiatan }} Tahun {{ $kegiatan->tahun_anggaran }}<br>
                Alamat Rumah : {{ $petugas->alamat ?? 'Sawahlunto' }}, yang selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.
            </div>
        </div>
    </div>

    <div class="content">
        Berdasarkan Perjanjian Kerja Nomor {{ $nomorSpk }} tanggal {{ $tanggalSpk->format('d') }} {{ $tanggalSpk->locale('id')->translatedFormat('F Y') }}, bersama ini <strong>PIHAK KEDUA</strong> telah menyerahkan pekerjaan Petugas Lapangan Kegiatan {{ $kegiatan->nama_kegiatan }} Tahun {{ $kegiatan->tahun_anggaran }} sesuai lampiran pada Perjanjian Kerja pada wilayah tugasnya kepada <strong>PIHAK PERTAMA</strong>, dengan ketentuan sebagai berikut:
    </div>

    <div class="pasal-list" style="margin-top: 20px;">
        <div class="pasal-item">
            <div class="pasal-item-number">1.</div>
            <div class="pasal-item-content">
                Hasil pekerjaan <strong>PIHAK KEDUA</strong> telah sesuai dengan jumlah yang ditetapkan dalam Perjanjian Kerja.
            </div>
        </div>
        <div class="pasal-item">
            <div class="pasal-item-number">2.</div>
            <div class="pasal-item-content">
                Hasil pekerjaan sebagaimana tersebut pada butir 1 telah diterima kelengkapannya oleh BPS Kota Sawahlunto dan telah dilaporkan kepada <strong>PIHAK PERTAMA</strong>.
            </div>
        </div>
    </div>

    <div class="content" style="margin-top: 20px;">
        Demikian Berita Acara ini dibuat untuk dipergunakan sebagaimana mestinya.
    </div>

    <!-- Signatures -->
    <div class="signature">
        <div class="signature-row">
            <div class="signature-col">
                <div class="signature-title">PIHAK KEDUA,</div>
                <div class="signature-name">{{ strtoupper($petugas->nama) }}</div>
            </div>
            <div class="signature-col">
                <div class="signature-title">PIHAK PERTAMA,</div>
                <div class="signature-name">{{ strtoupper($penandatangan) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
