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
        .dasar {
            page-break-inside: avoid;
        }
        .kegiatan-list {
            margin: 20px 0;
        }

        .kegiatan-item {
            margin: 10px 0 10px 30px;
        }

        .parties {
            margin: 20px 0 20px 30px;
            page-break-inside: avoid;
        }

        .party {
            margin-bottom: 15px;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .party-number {
            float: left;
            width: 30px;
        }

        .party-name {
            float: left;
            width: 200px;
            font-weight: bold;
        }

        .party-colon {
            float: left;
            width: 20px;
        }

        .party-detail {
            margin-left: 250px;
            text-align: justify;
        }

        .pasal-section {
            page-break-inside: avoid;
            margin: 15px 0;
        }

        .pasal-title {
            text-align: center;
            font-weight: bold;
            page-break-after: avoid;
            margin-bottom: 10px;
        }

        .pasal {
            margin: 15px 0;
            page-break-inside: avoid;
        }

        .pasal-header-group {
            margin-bottom: 15px;
        }

        .pasal-list {
            margin-left: 0;
        }

        .pasal-item {
            margin-bottom: 10px;
            display: table;
            width: 100%;
        }

        .pasal-item-number {
            display: table-cell;
            width: 40px;
            vertical-align: top;
            text-align: left;
        }

        .pasal-item-content {
            display: table-cell;
            vertical-align: top;
            text-align: justify;
            padding-left: 10px;
        }

        .sub-item {
            margin-top: 8px;
            display: table;
            width: 100%;
        }

        .sub-item-letter {
            display: table-cell;
            width: 30px;
            vertical-align: top;
            text-align: left;
        }

        .sub-item-content {
            display: table-cell;
            vertical-align: top;
            text-align: justify;
            padding-left: 5px;
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
                    $jenisKegiatan = strtolower($kegiatan->jenis_kegiatan ?? '');
                    $peran = strtolower($peran ?? ($petugas->jenis_petugas ?? ''));
                    $judulSpk = '';
                    if ($jenisKegiatan === 'sensus') {
                        if ($peran === 'pcl' || $peran === 'ppl') {
                            $judulSpk = 'PETUGAS LAPANGAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                            $judulSpk = 'PETUGAS PEMERIKSA LAPANGAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        } elseif (str_contains($peran, 'olah')) {
                            $judulSpk = 'PETUGAS PENGOLAHAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        } else {
                            $judulSpk = 'PETUGAS LAPANGAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        }
                    } else {
                        $judulSpk = 'PETUGAS LAPANGAN KEGIATAN SURVEI';
                    }
            $judulSpkText = strtolower($judulSpk);
                @endphp
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
        <h3>PERUBAHAN/{{ strtoupper($addendumLabel) }}</h3>
        <h3>PERJANJIAN KERJA {{ $judulSpk }}<br>
            BADAN PUSAT STATISTIK KOTA SAWAHLUNTO
            BULAN {{ strtoupper($bulan_label) }} {{ $tahun }}
            PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO <br>
            NOMOR: {{ $parent_nomor_spk }}</h3>
        <div style="text-align: center; margin-top: 10px;">NOMOR: {{ $nomorSpk }}</div>
    </div>
    <!-- CONTENT -->
    <div class="content">
        <p style="margin-bottom: 15px;">
            Pada hari ini, {{ $tanggalSpk->locale('id')->translatedFormat('l') }}, tanggal {{ terbilang($tanggalSpk->format('d')) }} {{ $tanggalSpk->locale('id')->translatedFormat('F') }} tahun {{ terbilang($tanggalSpk->format('Y'))}}, yang bertanda tangan di bawah ini:
        </p>

        <div class="parties">
            <div class="party">
                <div class="party-number">1.</div>
                <div class="party-name"><strong>{{ strtoupper($penandatangan) }}</strong></div>
                <div class="party-colon">:</div>
                <div class="party-detail">
                    Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto, berkedudukan di Jalan Bagindo Aziz Chan Kelurahan Aur Mulyo Kecamatan Lembah Segar Kota Sawahlunto, bertindak untuk dan atas nama Badan Pusat Statistik Kota Sawahlunto, selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>
                </div>
            </div>

            <div class="party">
                <div class="party-number">2.</div>
                <div class="party-name"><strong>{{ strtoupper($petugas->nama) }}</strong></div>
                <div class="party-colon">:</div>
                <div class="party-detail">
                    Mitra Statistik pada Badan Pusat Statistik Kota Sawahlunto, berkedudukan di {{ $petugas->alamat ?? 'Sawahlunto' }}, bertindak untuk dan atas nama diri sendiri, selanjutnya disebut <strong>PIHAK KEDUA</strong>.
                </div>
            </div>
        </div>
        
        <p style="margin-bottom: 10px;">
            bahwa <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> yang secara bersama-sama disebut <strong>PARA PIHAK</strong>, dengan ini menyatakan bahwa <strong>PARA PIHAK</strong> telah sepakat untuk mengikatkan diri dalam Perubahan/{{ $addendumLabel }} Perjanjian Kerja {{ ucwords($judulSpkText) }} Badan Pusat Statistik Kota Sawahlunto Bulan {{ $bulan_label }} Tahun {{ $tahun }} pada Badan Pusat Statistik Kota Sawahlunto Nomor: {{ $nomorSpk }}, dengan ketentuan-ketentuan sebagai berikut:
        </p>

        <p class="dasar" style="margin-bottom: 10px;">
            Dasar Perubahan/{{ $addendumLabel }} Perjanjian ini yaitu:<br>
            Terjadi perubahan realisasi pelaksanaan kegiatan yang dilakukan oleh <strong>PIHAK KEDUA</strong> sebagaimana tercantum dalam lampiran {{$addendumLabel}} yang disebabkan adanya {{ $hasUbinanKegiatan? '':  ' Non Response dari responden'}}{{ $hasUbinanKegiatan ? ', dan/atau terjadi lewat panen pada kegiatan Ubinan Padi/Palawija' : '' }}, dan/atau terjadi perubahan wilayah kerja pada kegiatan survei yang mengakibatkan perubahan beban kerja dan/atau honorarium yang diterima oleh <strong>PIHAK KEDUA</strong>.
        </p>


        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal I</strong></p>
            <p>
                Mengubah Pasal 6 dan Pasal 10 ayat (1) huruf b. Perjanjian Kerja {{ ucwords($judulSpkText) }} Badan Pusat Statistik Kota Sawahlunto Bulan {{ $bulan_label }} Tahun {{ $tahun }} pada Badan Pusat Statistik Kota Sawahlunto Nomor: {{ $parent_nomor_spk }}, sehingga Pasal 6 dan Pasal 10 ayat (1) seluruhnya berbunyi sebagai berikut:
            </p>
        </div>

        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal 6</strong></p>
            <p>
                <strong>PIHAK KEDUA</strong> berhak untuk mendapatkan honorarium dari <strong>PIHAK PERTAMA</strong> sebesar <strong>Rp {{ number_format($total_honor, 0, ',', '.') }} ({{ terbilang($total_honor) }} Rupiah)</strong> untuk pekerjaan sebagaimana dimaksud dalam Pasal 2, termasuk biaya pajak, bea meterai, dan jasa pelayanan keuangan.
            </p>
        </div>

        <div class="pasal">
            <div class="pasal-header-group">
                <div class="pasal-title">Pasal 10</div>
            </div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Apabila <strong>PIHAK KEDUA</strong> mengundurkan diri dengan tidak menyelesaikan pekerjaan sebagaimana dimaksud dalam Pasal 2, maka akan diberikan sanksi oleh <strong>PIHAK PERTAMA</strong>, sebagai berikut:
                        <div class="sub-item">
                            <div class="sub-item-letter">a.</div>
                            <div class="sub-item-content">
                                mengundurkan diri setelah pelatihan pada kegiatan yang diberikan pelatihan diberikan sanksi sebesar biaya pelatihan tersebut yang dikeluarkan oleh BPS Kota Sawahlunto;
                            </div>
                        </div>
                        <div class="sub-item">
                            <div class="sub-item-letter">b.</div>
                            <div class="sub-item-content">
                                mengundurkan diri pada saat pelaksanaan pekerjaan lapangan, maka <strong>WAJIB</strong> membayar ganti rugi sebesar Rp {{ number_format($total_honor, 0, ',', '.') }} ({{ terbilang($total_honor) }} rupiah).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal II</strong></p>
            <p>
                Mengubah Lampiran pada Perjanjian Kerja {{ ucwords($judulSpkText) }} Badan Pusat Statistik Kota Sawahlunto Bulan {{ $bulan_label }} Tahun {{ $tahun }} pada Badan Pusat Statistik Kota Sawahlunto Nomor: {{ $parent_nomor_spk }}, sebagaimana tercantum dalam Lampiran Perubahan/{{$addendumLabel}} ini sebagai bagian yang tidak terpisahkan dari Addendum ini.
            </p>
        </div>


        <div class="pasal-section">
            <p class="pasal-title"><strong>Pasal III</strong></p>
            <p>
                Ketentuan-ketentuan lainnya yang tidak diubah dalam Perubahan/Addendum atas Perjanjian Kerja {{ ucwords($judulSpkText) }} Badan Pusat Statistik Kota Sawahlunto Bulan {{ $bulan_label }} Tahun {{ $tahun }} pada Badan Pusat Statistik Kota Sawahlunto Nomor: {{ $parent_nomor_spk }} dinyatakan tetap berlaku.
            </p>
        </div>

        <p style="margin: 15px 0;">
            Demikian Perubahan/Addendum atas Perjanjian Kerja {{ ucwords($judulSpkText) }} Badan Pusat Statistik Kota Sawahlunto Bulan {{ $bulan_label }} Tahun {{ $tahun }} pada Badan Pusat Statistik Kota Sawahlunto Nomor: {{ $parent_nomor_spk }} ini dibuat dan ditandatangani oleh <strong>PARA PIHAK</strong> dalam 2 (dua) rangkap asli bermeterai cukup yang mempunyai kekuatan hukum sama dan masing-masing <strong>PIHAK</strong> mendapat 1 (satu) rangkap.
        </p>
    </div>

    <!-- SIGNATURE SECTION -->
    <div class="signature-section">
        <p style="text-align: right; margin-bottom: 20px;">
        </p>
        <div class="signature-row">
            <div class="signature-left">
                <p><strong>PIHAK KEDUA</strong></p>
                <p style="margin-top: 50px;">
                    <span class="signature-name">{{ strtoupper($petugas->nama) }}</span>
                </p>
            </div>
            <div class="signature-right">
                <p><strong>PIHAK PERTAMA</strong></p>
                <p style="margin-top: 50px;">
                    <span class="signature-name">{{ strtoupper($kepalaBps) }}</span>
                </p>
            </div>
            
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
