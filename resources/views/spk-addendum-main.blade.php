<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Addendum SPK - {{ $petugas->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm 3cm 2cm 3cm;
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
            line-height: 1.5;
            color: #000;
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h3 {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            line-height: 1.4;
            margin: 2px 0;
        }

        .title {
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            font-size: 12pt;
        }

        .content {
            text-align: justify;
            margin: 10px 0;
        }

        .kegiatan-list {
            margin: 20px 0;
        }

        .kegiatan-item {
            margin: 10px 0 10px 30px;
        }

        .pasal-section {
            page-break-inside: avoid;
            margin: 15px 0;
        }

        .pasal-title {
            text-align: center;
            font-weight: bold;
            page-break-after: avoid;
        }

        .signature-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-row {
            margin-bottom: 10px;
        }

        .signature-left {
            float: left;
            width: 48%;
            text-align: center;
        }

        .signature-right {
            float: right;
            width: 48%;
            text-align: center;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        $addendumLabel = match((int)$addendum_number) {
            1 => 'Addendum',
            2 => 'Addendum Kedua',
            3 => 'Addendum Ketiga',
            4 => 'Addendum Keempat',
            5 => 'Addendum Kelima',
            default => 'Addendum Ke-' . $addendum_number
        };
    @endphp
    <!-- HEADER -->
    <div class="header">
        <h3 style="text-decoration: underline; margin-bottom: 5px;">{{ strtoupper($addendumLabel) }}</h3>
        <h3>SURAT PERJANJIAN KERJA</h3>
        <h3>PETUGAS LAPANGAN BULAN {{ strtoupper($bulan_label) }} {{ $tahun }}</h3>
        <div style="font-weight: bold; text-align: center;">NOMOR: {{ $nomorSpk }}</div>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <p style="margin-bottom: 15px;">
            <strong>{{ $addendumLabel }}</strong> ini merupakan bagian yang tidak terpisahkan dari Surat Perjanjian Kerja Nomor {{ $parent_nomor_spk }} yang selanjutnya disebut Perjanjian, dibuat dan ditandatangani di Sawahlunto pada hari {{ $tanggalSpk->locale('id')->translatedFormat('l') }}, tanggal {{ terbilang($tanggalSpk->format('d')) }} {{ $tanggalSpk->locale('id')->translatedFormat('F') }} tahun {{ terbilang($tanggalSpk->format('Y')) }}.
        </p>

        <p style="text-align: center; margin-bottom: 10px;">Oleh dan antara:</p>
        
        <p style="margin-bottom: 10px;">
            1. Badan Pusat Statistik Kota Sawahlunto, yang dalam hal ini diwakili oleh <strong>{{ $kepalaBps }}</strong> selaku Pejabat Pembuat Komitmen, yang selanjutnya disebut <strong>PIHAK PERTAMA</strong>.
        </p>
        
        <p style="margin-bottom: 10px;">
            2. <strong>{{ $petugas->nama }}</strong>, Petugas Pendataan Lapangan Badan Pusat Statistik Kota Sawahlunto Bulan {{ ($bulan_label) }} {{ $tahun }}, yang selanjutnya disebut <strong>PIHAK KEDUA</strong>.
        </p>
        
        <p style="margin-bottom: 10px;">
            <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> selanjutnya secara bersama-sama disebut <strong>PARA PIHAK</strong>, sepakat untuk mengubah dan/atau menambahkan ketentuan dalam Perjanjian sebagai berikut:
        </p>

        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal 2</strong></p>
            <p>
                Ruang lingkup pekerjaan dalam Perjanjian ini mengacu pada wilayah kerja dan beban kerja sebagaimana tertuang dalam lampiran {{$addendumLabel}}, pedoman Petugas Lapangan Kegiatan Sensus dan Survei Badan Pusat Statistik Kota Sawahlunto Bulan {{ $bulan_label }} Tahun {{ $tahun }} pada Badan Pusat Statistik Kota Sawahlunto, dan ketentuan-ketentuan yang ditetapkan oleh <strong>PIHAK PERTAMA</strong>.
            </p>
        </div>

        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal 6</strong></p>
            <p>
                <strong>PIHAK KEDUA</strong> berhak untuk mendapatkan honorarium dari <strong>PIHAK PERTAMA</strong> sebesar <strong>Rp {{ number_format($total_honor, 0, ',', '.') }} ({{ terbilang($total_honor) }} Rupiah)</strong> untuk pekerjaan sebagaimana dimaksud dalam Pasal 2, termasuk biaya pajak, bea meterai, dan jasa pelayanan keuangan.
            </p>
        </div>

        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal 10</strong></p>
            <p>
                Mengubah ketentuan Pasal 10 ayat (1) huruf b. pada Perjanjian menjadi:
            </p>
            <p style="margin-left: 30px;">
                b. mengundurkan diri pada saat pelaksanaan pekerjaan lapangan, maka <strong>WAJIB</strong> membayar ganti rugi sebesar <strong>Rp {{ number_format($total_honor, 0, ',', '.') }} ({{ terbilang($total_honor) }} Rupiah)</strong>.
            </p>
        </div>

        <p style="margin: 25px 0 15px 0;">
            Ketentuan-ketentuan lainnya yang tidak diubah dalam Addendum ini tetap berlaku sebagaimana tercantum dalam Perjanjian.
        </p>

        <p style="margin: 15px 0;">
            Demikian Addendum ini dibuat dan ditandatangani oleh <strong>PARA PIHAK</strong> dalam keadaan sehat jasmani dan rohani tanpa ada paksaan dari pihak manapun, untuk dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <!-- SIGNATURE SECTION -->
    <div class="signature-section">
        <p style="text-align: right; margin-bottom: 20px;">
        </p>
        <div class="signature-row">
            <div class="signature-left">
                <br>
                <p><strong>PIHAK PERTAMA</strong></p>
                <p style="margin-top: 50px;">
                    <span class="signature-name">{{ strtoupper($kepalaBps) }}</span>
                </p>
            </div>
            <div class="signature-right">
            Sawahlunto, {{ $tanggalSpk->format('d') }} {{ $tanggalSpk->locale('id')->translatedFormat('F Y') }}
                <p><strong>PIHAK KEDUA</strong></p>
                <p style="margin-top: 50px;">
                    <span class="signature-name">{{ strtoupper($petugas->nama) }}</span>
                </p>
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
