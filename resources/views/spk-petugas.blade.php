<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>SPK {{ $nomorSpk }} {{ $tanggalSpk->locale('id')->translatedFormat('F') }} - {{ $petugas->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 3cm 3cm 3cm 4cm;
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
            line-height: 1.6;
            color: #000;
            orphans: 3;
            widows: 3;
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            page-break-inside: avoid;
            page-break-after: avoid;
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
            margin: 15px 0;
            font-size: 12pt;
            page-break-after: avoid;
        }

        .content {
            text-align: justify;
            margin: 15px 0;
            page-break-inside: avoid;
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

        .pasal {
            margin: 20px 0;
            page-break-before: auto;
        }

        /* Wrapper to keep title with first content together */
        .pasal-header-group {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .pasal-title {
            text-align: center;
            font-weight: bold;
            margin: 15px 0 10px 0;
        }

        .pasal-content {
            text-align: justify;
            margin: 10px 0;
            page-break-inside: avoid;
        }

        .pasal-list {
            margin: 10px 0 10px 30px;
        }

        .pasal-remaining-items {
            margin: 0;
        }

        /* Clear floats */
        .party:after,
        .pasal-item:after,
        .sub-item:after {
            content: "";
            display: table;
            clear: both;
        }

        .pasal-item {
            margin: 8px 0;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .pasal-item-number {
            float: left;
            width: 40px;
        }

        .pasal-item-content {
            margin-left: 40px;
            text-align: justify;
        }

        .sub-item {
            margin: 8px 0 8px 30px;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .sub-item-letter {
            float: left;
            width: 30px;
        }

        .sub-item-content {
            margin-left: 30px;
            text-align: justify;
        }

        .pasal-item-with-sub-items {
            page-break-inside: auto;
        }

        .pasal-item-with-sub-items > .pasal-item-content > .ayat-lead-with-first-sub-item {
            page-break-before: avoid;
            page-break-inside: avoid;
        }

        .signature {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-row {
            display: table;
            width: 100%;
            page-break-inside: avoid;
        }

        /* General page break control */
        .pasal + .pasal {
            page-break-before: auto;
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

        table.sensus-lampiran-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10pt;
        }

        table.sensus-lampiran-table col.col-uraian {
            width: 66%;
        }

        table.sensus-lampiran-table col.col-waktu {
            width: 11%;
        }

        table.sensus-lampiran-table col.col-persentase {
            width: 5%;
        }

        table.sensus-lampiran-table col.col-volume {
            width: 11%;
        }

        table.sensus-lampiran-table col.col-nilai {
            width: 7%;
        }

        table.sensus-lampiran-table td.volume-cell {
            overflow-wrap: normal;
            word-break: normal;
            hyphens: none;
            line-height: 1.35;
            font-size: 9.5pt;
        }

        table.sensus-lampiran-table td.money-cell {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- MAIN SPK DOCUMENT -->
    <div class="header content">
        @php
            $jenisKegiatan = strtolower($kegiatan->jenis_kegiatan ?? '');
            
            // For SENSUS, use specific titles based on kegiatan name
            if ($jenisKegiatan === 'sensus') {
                $peran = strtolower($peran ?? ($petugas->jenis_petugas ?? ''));
                if ($peran === 'pcl' || $peran === 'ppl') {
                    $judulSpk = 'PETUGAS LAPANGAN ' . strtoupper($kegiatan->nama_kegiatan);
                } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                    $judulSpk = 'PETUGAS PEMERIKSA LAPANGAN ' . strtoupper($kegiatan->nama_kegiatan);
                } elseif (str_contains($peran, 'olah')) {
                    $judulSpk = 'PETUGAS PENGOLAHAN ' . strtoupper($kegiatan->nama_kegiatan);
                } elseif (str_contains($peran, 'awas olah') || str_contains($peran, 'pengawas pengolahan')) {
                    $judulSpk = 'PETUGAS PENGAWAS PENGOLAHAN ' . strtoupper($kegiatan->nama_kegiatan);
                } elseif (str_contains($peran, 'koseka')) {
                    $judulSpk = 'KOSEKA  ' . strtoupper($kegiatan->nama_kegiatan); 
                } else {
                    $judulSpk = 'PETUGAS LAPANGAN ' . strtoupper($kegiatan->nama_kegiatan);
                }
                $judulSpkText = strtolower($judulSpk);
            } else {
                // For SURVEI, determine title based on work type
                $workType = $workType ?? 'lapangan';
                if ($workType === 'lapangan_pengolahan') {
                    $judulSpk = 'PETUGAS LAPANGAN DAN PETUGAS PENGOLAHAN SURVEI';
                    $judulSpkText = 'petugas lapangan dan petugas pengolahan survei';
                } elseif ($workType === 'pengolahan') {
                    $judulSpk = 'PETUGAS PENGOLAHAN SURVEI';
                    $judulSpkText = 'petugas pengolahan survei';
                } else {
                    $judulSpk = 'PETUGAS LAPANGAN SURVEI';
                    $judulSpkText = 'petugas lapangan survei';
                }
            }
        @endphp
        <h3>PERJANJIAN KERJA</h3>
        <h3>{{ $judulSpk }}</h3>
        @if($jenisKegiatan !== 'sensus')
        <h3>BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br> BULAN {{strtoupper(bulanIndonesia($periode->bulan))}} TAHUN {{ $periode->tahun }}</h3>
        @endif
        <h3>PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</h3>
        <div style="font-weight: bold; text-align: center; margin-top: 5px;">NOMOR: {{ $nomorSpk }}</div>
    </div>

    <div class="content">
        Pada hari ini {{ $tanggalSpk->locale('id')->translatedFormat('l') }}, tanggal {{ tanggalTerbilang($tanggalSpk->day) }}, bulan {{ $tanggalSpk->locale('id')->translatedFormat('F') }}, tahun {{ tahunTerbilang($tanggalSpk->year) }} ({{ $tanggalSpk->format('d-m-Y') }}), bertempat di Kota Sawahlunto, yang bertanda tangan di bawah ini:
    </div>

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
                @if($jenisKegiatan === 'sensus')
                {{ ucwords($judulSpkText) }}, berkedudukan di {{ ucwords($petugas->alamat ?? 'Sawahlunto') }}, bertindak untuk dan atas nama diri sendiri, selanjutnya disebut sebagai <strong>PIHAK KEDUA.</strong>
                @else
                Mitra Statistik pada Badan Pusat Statistik Kota Sawahlunto, berkedudukan di {{ ucwords($petugas->alamat ?? 'Sawahlunto') }}, bertindak untuk dan atas nama diri sendiri, selanjutnya disebut <strong>PIHAK KEDUA.</strong>
                @endif
            </div>
        </div>
    </div>

    <div class="content">
        bahwa <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> yang secara bersama-sama disebut <strong>PARA PIHAK</strong>, sepakat untuk mengikatkan diri dalam Perjanjian Kerja {{ ucwords($judulSpkText) }} @if($jenisKegiatan !== 'sensus')Badan Pusat Statistik Kota Sawahlunto Bulan {{bulanIndonesia($periode->bulan)}} Tahun {{ $periode->tahun }} pada @endif Badan Pusat Statistik Kota Sawahlunto Nomor: {{ $nomorSpk }}, yang selanjutnya disebut Perjanjian, dengan ketentuan-ketentuan sebagai berikut:
    </div>

    <!-- Pasal 1 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 1</div>
            <div class="pasal-content">
                <strong>PIHAK PERTAMA</strong> memberikan pekerjaan kepada <strong>PIHAK KEDUA</strong> dan <strong>PIHAK KEDUA</strong> menerima pekerjaan dari <strong>PIHAK PERTAMA</strong> sebagai {{ ucwords($judulSpkText) }} @if($jenisKegiatan !== 'sensus')Badan Pusat Statistik Kota Sawahlunto Bulan {{bulanIndonesia($periode->bulan)}} Tahun {{ $periode->tahun }} pada @endif Badan Pusat Statistik Kota Sawahlunto, dengan lingkup pekerjaan yang ditetapkan oleh <strong>PIHAK PERTAMA.</strong>
            </div>
        </div>
    </div>

    <!-- Pasal 2 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 2</div>
            <div class="pasal-content">
                @if($jenisKegiatan === 'sensus')
                Ruang lingkup pekerjaan dalam Perjanjian ini mengacu pada wilayah kerja dan beban kerja sebagaimana tertuang dalam lampiran Perjanjian, Buku Pedoman Teknis Sensus Ekonomi 2026, dan ketentuan-ketentuan lainnya yang ditetapkan oleh <strong>PIHAK PERTAMA.</strong>
                @else
                Ruang lingkup pekerjaan dalam Perjanjian ini mengacu pada wilayah kerja dan beban kerja sebagaimana tertuang dalam lampiran Perjanjian, pedoman {{ ucwords($judulSpkText) }} Badan Pusat Statistik Kota Sawahlunto Bulan {{bulanIndonesia($periode->bulan)}} Tahun {{ $periode->tahun }} pada Badan Pusat Statistik Kota Sawahlunto, dan ketentuan-ketentuan yang ditetapkan oleh <strong>PIHAK PERTAMA.</strong>
                @endif
            </div>
        </div>
    </div>

    <!-- Pasal 3 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 3</div>
            <div class="pasal-content">
                Jangka Waktu Perjanjian terhitung sejak Perjanjian ini ditandatangani sampai dengan tanggal {{ $sampaiTanggal->locale('id')->translatedFormat('d F Y') }}.
            </div>
        </div>
    </div>

    <!-- Pasal 4 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 4</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        @if($jenisKegiatan === 'sensus')
                        <strong>PIHAK KEDUA</strong> berkewajiban menyelesaikan pekerjaan yang diberikan oleh <strong>PIHAK PERTAMA</strong> sesuai ruang lingkup pekerjaan sebagaimana dimaksud dalam Pasal 2.
                        @else
                        <strong>PIHAK KEDUA</strong> berkewajiban melaksanakan pekerjaan yang diberikan oleh <strong>PIHAK PERTAMA</strong> sesuai ruang lingkup pekerjaan sebagaimana dimaksud dalam Pasal 2, dengan menerapkan protokol kesehatan yang berlaku di wilayah kerja masing-masing merujuk pada ketentuan pemerintah.
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> untuk waktu yang tidak terbatas dan/atau tidak terikat kepada masa berlakunya Perjanjian ini, menjamin untuk memberlakukan sebagai rahasia setiap data/informasi yang diterima atau diperolehnya dari <strong>PIHAK PERTAMA</strong>, serta menjamin bahwa keterangan demikian hanya dipergunakan untuk melaksanakan tujuan menurut Perjanjian ini.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 5 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 5</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <strong>PIHAK KEDUA</strong> apabila melakukan peminjaman dokumen/data/aset milik <strong>PIHAK PERTAMA</strong>, wajib menjaga dan menggunakan sesuai dengan tujuan Perjanjian dan mengembalikan dalam keadaan utuh sama dengan saat peminjaman, serta dilarang menggandakan, menyalin, menunjukkan, dan/atau mendokumentasikan dalam bentuk foto atau bentuk apapun untuk kepentingan pribadi ataupun kepentingan lain yang tidak berkaitan dengan tujuan Perjanjian ini.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> dilarang memberikan dokumen/data/aset milik <strong>PIHAK PERTAMA</strong> yang berada dalam penguasaan <strong>PIHAK KEDUA</strong>, baik secara langsung maupun tidak langsung, termasuk memberikan akses kepada pihak lain untuk menggunakan, menyalin, memfotokopi, menunjukkan, dan/atau mendokumentasikan dalam bentuk foto atau bentuk apapun, sehingga informasi diketahui oleh pihak lain untuk tujuan apapun.
                </div>
            </div>
            @if($jenisKegiatan === 'sensus')
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> dilarang meminta dan/atau menerima imbalan dalam bentuk apapun dari pihak lain untuk kepentingan pribadi ataupun kepentingan lain yang tidak berkaitan dengan tujuan perjanjian ini.
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Pasal 6 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 6</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <strong>PIHAK KEDUA</strong> berhak untuk mendapatkan honorarium dari <strong>PIHAK PERTAMA</strong> sebesar Rp {{ number_format($totalHonor, 0, ',', '.') }} ({{ terbilang($totalHonor) }} rupiah) untuk pekerjaan sebagaimana dimaksud dalam Pasal 2, termasuk biaya pajak, bea meterai, dan jasa pelayanan keuangan.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            @if($jenisKegiatan === 'sensus')
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Selain honorarium sebagaimana dimaksud pada ayat (1), <strong>PIHAK KEDUA</strong> dapat diberikan paket data dan komunikasi selama pelaksanaan pekerjaan sesuai dengan ketentuan yang ditetapkan <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
            @else
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Honorarium sebagaimana dimaksud pada ayat (1) dibayarkan oleh <strong>PIHAK PERTAMA</strong> kepada <strong>PIHAK KEDUA</strong> setelah menyelesaikan seluruh pekerjaan yang ditargetkan sebagaimana tercantum dalam Lampiran Perjanjian, dituangkan dalam Berita Acara Serah Terima Hasil Pekerjaan, dan diserahkan paling lambat tanggal {{ $sampaiTanggal->locale('id')->translatedFormat('d F Y') }}.
                </div>
            </div>
            @if($tanggalPerpanjangan)
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    Apabila terdapat hambatan dalam penyerahan hasil pekerjaan sebagaimana dimaksud pada ayat (2), <strong>PIHAK PERTAMA</strong> dapat memberikan tambahan waktu penyerahan seluruh hasil pekerjaan lapangan paling lambat pada tanggal {{ $tanggalPerpanjangan->locale('id')->translatedFormat('d F Y') }}.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(4)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> tidak diberikan honorarium tambahan apabila melakukan kunjungan di luar jadwal atau terdapat tambahan waktu pelaksanaan pekerjaan di luar jangka waktu Perjanjian sebagaimana dimaksud dalam Pasal 3.
                </div>
            </div>
            @else
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> tidak diberikan honorarium tambahan apabila melakukan kunjungan di luar jadwal atau terdapat tambahan waktu pelaksanaan pekerjaan di luar jangka waktu Perjanjian sebagaimana dimaksud dalam Pasal 3.
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>

    @if($jenisKegiatan === 'sensus')

    <!-- Pasal 7 - Sensus: Asuransi JKK/JKM -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 7</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <strong>PIHAK KEDUA</strong> berhak untuk mendapatkan asuransi berupa program perlindungan jaminan kecelakaan kerja (JKK) dan jaminan kematian (JKM) dari <strong>PIHAK PERTAMA</strong> untuk jangka waktu pelaksanaan pendataan pada bulan Juni sampai dengan Agustus 2026.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Asuransi sebagaimana dimaksud pada ayat (1) tidak termasuk dalam honorarium sebagaimana dimaksud dalam Pasal 6.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 8 - Sensus: Pembayaran -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 8</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Pembayaran honorarium sebagaimana dimaksud dalam Pasal 6, <strong>dilakukan</strong> setelah <strong>PIHAK KEDUA</strong> menyelesaikan dan menyerahkan hasil pekerjaan sebagaimana dimaksud dalam Pasal 2 kepada <strong>PIHAK PERTAMA.</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Pembayaran sebagaimana dimaksud pada ayat (1) dilakukan oleh <strong>PIHAK PERTAMA</strong> kepada <strong>PIHAK KEDUA</strong> sesuai dengan ketentuan peraturan perundang-undangan.
                </div>
            </div>
            <div class="pasal-item pasal-item-with-sub-items">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    <div class="ayat-lead-with-first-sub-item">
                        Pembayaran honorarium sebagaimana dimaksud pada ayat (1) dibayarkan oleh <strong>PIHAK PERTAMA</strong> kepada <strong>PIHAK KEDUA</strong> dalam 2 (dua) termin yang terdiri atas:
                        <div class="sub-item sub-item-first">
                            <div class="sub-item-letter">a.</div>
                            <div class="sub-item-content">
                                termin I dibayarkan setelah <strong>PIHAK KEDUA</strong> menyelesaikan dan menyerahkan minimal 40% dari seluruh pekerjaan yang ditargetkan dan telah memenuhi minimal 1 bulan pendataan sebagaimana tercantum dalam Berita Acara Pemeriksaan Pekerjaan termin I.
                            </div>
                        </div>
                    </div>
                    <div class="sub-item">
                        <div class="sub-item-letter">b.</div>
                        <div class="sub-item-content">
                            termin II dibayarkan setelah <strong>PIHAK KEDUA</strong> menyelesaikan dan menyerahkan seluruh pekerjaan yang ditargetkan sebagaimana tercantum dalam Lampiran Perjanjian, dituangkan dalam Berita Acara Serah Terima Hasil Pekerjaan, dan diserahkan paling lambat tanggal 15 September 2026.
                        </div>
                    </div>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(4)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> tidak diberikan honorarium tambahan apabila melakukan kunjungan di luar jadwal dan/atau terdapat tambahan waktu pelaksanaan pekerjaan di luar jangka waktu Perjanjian sebagaimana dimaksud dalam Pasal 3.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 9 - Sensus: Pemeriksaan dan Evaluasi -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 9</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <strong>PIHAK PERTAMA</strong> secara berjenjang melalui Ketua tim pelaksana Sensus Ekonomi 2026 di BPS Kota Sawahlunto melakukan pemeriksaan dan evaluasi atas target penyelesaian pekerjaan dan kualitas hasil pekerjaan yang dilaksanakan oleh <strong>PIHAK KEDUA</strong> secara berkala yang dituangkan dalam Berita Acara Pemeriksaan Pekerjaan.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Hasil pemeriksaan dan evaluasi sebagaimana dimaksud pada ayat (1) menjadi dasar pembayaran honorarium <strong>PIHAK KEDUA</strong> oleh <strong>PIHAK PERTAMA</strong> sebagaimana dimaksud dalam Pasal 8 ayat (3), yang dituangkan dalam Berita Acara Serah Terima Hasil Pekerjaan yang ditandatangani oleh <strong>PARA PIHAK.</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 10 - Sensus: Pemutusan -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 10</div>
            <div class="pasal-content">
                <strong>PIHAK PERTAMA</strong> dapat memutuskan Perjanjian ini secara sepihak sewaktu-waktu dalam hal <strong>PIHAK KEDUA</strong> tidak dapat melaksanakan kewajibannya sebagaimana dimaksud dalam Pasal 4, dengan menerbitkan Surat Pemutusan Perjanjian Kerja.
            </div>
        </div>
    </div>

    <!-- Pasal 11 - Sensus: Sanksi -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 11</div>
            <div class="pasal-list">
                <div class="pasal-item pasal-item-with-sub-items">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <div class="ayat-lead-with-first-sub-item">
                            Apabila <strong>PIHAK KEDUA</strong> mengundurkan diri dalam pelaksanaan ruang lingkup pekerjaan sebagaimana dimaksud dalam Pasal 2, maka akan diberikan sanksi oleh <strong>PIHAK PERTAMA</strong>, sebagai berikut:
                            <div class="sub-item sub-item-first">
                                <div class="sub-item-letter">a.</div>
                                <div class="sub-item-content">
                                    mengundurkan diri setelah pelatihan dan tidak melaksanakan pekerjaan diberikan sanksi sebesar Rp 1.727.440 (satu juta tujuh ratus dua puluh tujuh ribu empat ratus empat puluh rupiah);
                                </div>
                            </div>
                        </div>
                        <div class="sub-item">
                            <div class="sub-item-letter">b.</div>
                            <div class="sub-item-content">
                                mengundurkan diri pada saat pelaksanaan pekerjaan, diberikan sanksi tidak diberikan honorarium atas pekerjaan yang telah dilaksanakan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Dikecualikan tidak dikenakan sanksi sebagaimana dimaksud pada ayat (1) oleh <strong>PIHAK PERTAMA</strong>, apabila <strong>PIHAK KEDUA</strong> meninggal dunia, mengundurkan diri karena sakit dengan keterangan rawat inap, kecelakaan dengan keterangan kepolisian, dan/atau telah diberikan Surat Pemutusan Perjanjian Kerja dari <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> yang mengundurkan diri sebagaimana dimaksud pada ayat (1) dan/atau meninggal dunia, mengundurkan diri karena sakit dengan keterangan rawat inap, kecelakaan dengan keterangan kepolisian, telah diberikan Surat Pemutusan Perjanjian Kerja sebagaimana dimaksud pada ayat (2) wajib mengembalikan perlengkapan yang diberikan oleh <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(4)</div>
                <div class="pasal-item-content">
                    Dalam hal terjadi peristiwa sebagaimana dimaksud pada ayat (2), <strong>PIHAK PERTAMA</strong> membayarkan honorarium kepada <strong>PIHAK KEDUA</strong> secara proporsional sesuai pekerjaan yang telah dilaksanakan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 12 - Sensus: Keadaan Kahar -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 12</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Apabila terjadi Keadaan Kahar, yang meliputi bencana alam, bencana nonalam, dan bencana sosial, <strong>PIHAK KEDUA</strong> memberitahukan kepada <strong>PIHAK PERTAMA</strong> dalam waktu paling lambat 14 (empat belas) hari sejak mengetahui atas kejadian Keadaan Kahar dengan menyertakan bukti.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    @if(isset($peran) && $peran === 'pml')
                    Apabila terjadi kerusakan perangkat pencacahan yang menyebabkan pelaksanaan Pemeriksaan Lapangan Sensus Ekonomi 2026 tidak dapat dilakukan, <strong>PIHAK KEDUA</strong> memberitahukan kepada <strong>PIHAK PERTAMA</strong> dalam waktu paling lambat 7 (tujuh) hari kalender sejak terjadi kerusakan dimaksud.
                    @else
                    Apabila terjadi kerusakan perangkat pencacahan yang menyebabkan pelaksanaan Pendataan Lapangan Sensus Ekonomi 2026 tidak dapat dilakukan, <strong>PIHAK KEDUA</strong> melalui Petugas Pemeriksa Lapangan memberitahukan kepada <strong>PIHAK PERTAMA</strong> dalam waktu paling lambat 7 (tujuh) hari kalender sejak terjadi kerusakan dimaksud.
                    @endif
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    Dalam hal terjadi peristiwa sebagaimana dimaksud pada ayat (1) dan/atau ayat (2), pelaksanaan pekerjaan oleh <strong>PIHAK KEDUA</strong> dihentikan sementara dan dilanjutkan kembali setelah Keadaan Kahar berakhir, merujuk pada ketentuan yang ditetapkan oleh <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(4)</div>
                <div class="pasal-item-content">
                    Apabila akibat Keadaan Kahar tidak memungkinkan dilanjutkan/diselesaikannya pelaksanaan pekerjaan, <strong>PIHAK KEDUA</strong> berhak menerima honorarium secara proporsional sesuai pekerjaan yang telah diselesaikan dan diterima oleh <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 13 - Sensus: Addendum -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 13</div>
            <div class="pasal-content">
                Hal-hal yang belum diatur dalam Perjanjian ini atau segala perubahan terhadap Perjanjian ini diatur lebih lanjut oleh <strong>PARA PIHAK</strong> dalam perjanjian tambahan/adendum dan merupakan bagian tidak terpisahkan dari Perjanjian ini.
            </div>
        </div>
    </div>

    <!-- Pasal 14 - Sensus: Perselisihan -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 14</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Segala perselisihan atau perbedaan pendapat yang mungkin timbul sebagai akibat dari Perjanjian ini, diselesaikan secara musyawarah untuk mufakat oleh <strong>PARA PIHAK.</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Apabila musyawarah untuk mufakat sebagaimana dimaksud pada ayat (1) tidak berhasil, maka <strong>PARA PIHAK</strong> sepakat untuk menyelesaikan perselisihan dengan memilih kedudukan/domisili hukum di Kepaniteraan Pengadilan Negeri Sawahlunto.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    Selama perselisihan dalam proses penyelesaian pengadilan, <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> wajib tetap melaksanakan kewajiban masing-masing berdasarkan Perjanjian ini.
                </div>
            </div>
        </div>
    </div>

    @else

    <!-- Pasal 7 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 7</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Pembayaran honorarium sebagaimana dimaksud dalam Pasal 6, dilakukan setelah <strong>PIHAK KEDUA</strong> menyelesaikan dan menyerahkan hasil pekerjaan sebagaimana dimaksud dalam Pasal 2 kepada <strong>PIHAK PERTAMA.</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Pembayaran sebagaimana dimaksud pada ayat (1) dilakukan oleh <strong>PIHAK PERTAMA</strong> kepada <strong>PIHAK KEDUA</strong> sesuai dengan ketentuan peraturan perundang-undangan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 8 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 8</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <strong>PIHAK PERTAMA</strong> secara berjenjang melalui Tim Teknis BPS Kabupaten/Kota melakukan pemeriksaan dan evaluasi atas target penyelesaian dan kualitas hasil pekerjaan yang dilaksanakan oleh <strong>PIHAK KEDUA</strong> secara berkala.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Hasil pemeriksaan dan evaluasi sebagaimana dimaksud pada ayat (1) menjadi dasar pembayaran honorarium <strong>PIHAK KEDUA</strong> oleh <strong>PIHAK PERTAMA</strong> sebagaimana dimaksud dalam Pasal 6 ayat (2), yang menjadi lampiran Berita Acara Serah Terima Hasil Pekerjaan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 9 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 9</div>
            <div class="pasal-content">
                <strong>PIHAK PERTAMA</strong> dapat memutuskan Perjanjian ini secara sepihak sewaktu-waktu dalam hal <strong>PIHAK KEDUA</strong> tidak dapat melaksanakan kewajibannya sebagaimana dimaksud dalam Pasal 4, dengan menerbitkan Surat Pemutusan Perjanjian Kerja.
            </div>
        </div>
    </div>

    <!-- Pasal 10 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 10</div>
            <div class="pasal-list">
                <div class="pasal-item pasal-item-with-sub-items">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        <div class="ayat-lead-with-first-sub-item">
                            Apabila <strong>PIHAK KEDUA</strong> mengundurkan diri dengan tidak menyelesaikan pekerjaan sebagaimana dimaksud dalam Pasal 2, maka akan diberikan sanksi oleh <strong>PIHAK PERTAMA</strong>, sebagai berikut:
                            <div class="sub-item sub-item-first">
                                <div class="sub-item-letter">a.</div>
                                <div class="sub-item-content">
                                    mengundurkan diri setelah pelatihan pada kegiatan yang diberikan pelatihan diberikan sanksi sebesar biaya pelatihan tersebut yang dikeluarkan oleh BPS Kota Sawahlunto;
                                </div>
                            </div>
                        </div>
                        <div class="sub-item">
                            <div class="sub-item-letter">b.</div>
                            <div class="sub-item-content">
                                mengundurkan diri pada saat pelaksanaan pekerjaan lapangan, maka <strong>WAJIB</strong> membayar ganti rugi sebesar Rp{{ number_format($totalHonor, 0, ',', '.') }} ({{ terbilang($totalHonor) }} rupiah).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Dikecualikan tidak dikenakan sanksi sebagaimana dimaksud pada ayat (1) oleh <strong>PIHAK PERTAMA</strong>, apabila <strong>PIHAK KEDUA</strong> meninggal dunia, mengundurkan diri karena sakit dengan keterangan rawat inap, kecelakaan dengan keterangan kepolisian, dan/atau telah diberikan Surat Pemutusan Perjanjian Kerja dari <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    Dalam hal terjadi peristiwa sebagaimana dimaksud pada ayat (2), <strong>PIHAK PERTAMA</strong> membayarkan honorarium kepada <strong>PIHAK KEDUA</strong> secara proporsional sesuai pekerjaan yang telah dilaksanakan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 11 -->
    @php
        $allKegiatanList = collect($allAlokasi ?? [])
            ->map(fn($a) => $a->periodeAlokasi->kegiatan ?? null)
            ->filter()
            ->unique('id')
            ->values();
        $capiKegiatanList = $allKegiatanList->filter(
            fn($k) => ($k->metode_pendataan_pencacahan === 'CAPI') || ($k->metode_pendataan_listing === 'CAPI')
        );
        $hasCapiKegiatan = $capiKegiatanList->isNotEmpty();
        $kegiatanNamaText = $capiKegiatanList->isNotEmpty()
            ? $capiKegiatanList->pluck('nama_kegiatan')->filter()->unique()->implode(', ')
            : ($kegiatan->nama_kegiatan ?? '');
    @endphp
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 11</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Apabila terjadi Keadaan Kahar, yang meliputi bencana alam, bencana non alam, dan bencana sosial, <strong>PIHAK KEDUA</strong> memberitahukan kepada <strong>PIHAK PERTAMA</strong> dalam waktu paling lambat 14 (empat belas) hari kalender sejak mengetahui atas kejadian Keadaan Kahar dengan menyertakan bukti.
                    </div>
                </div>
            </div>
        </div>
        <div class="pasal-remaining-items">
            @if($hasCapiKegiatan)
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Apabila terjadi kerusakan perangkat pencacahan yang menyebabkan pelaksanaan pencacahan lapangan {{ $kegiatanNamaText }} pada Badan Pusat Statistik Kota Sawahlunto tidak dapat dilakukan, <strong>PIHAK KEDUA</strong> melalui Tim Teknis BPS Kota memberitahukan kepada <strong>PIHAK PERTAMA</strong> dalam waktu paling lambat 14 (empat belas) hari kalender sejak terjadi kerusakan dimaksud.
                </div>
            </div>
            @endif
            <div class="pasal-item">
                <div class="pasal-item-number">{{ $hasCapiKegiatan ? '(3)' : '(2)' }}</div>
                <div class="pasal-item-content">
                    Dalam hal terjadi peristiwa sebagaimana dimaksud pada ayat (1){{ $hasCapiKegiatan ? ' dan/atau ayat (2)' : '' }}, pelaksanaan pekerjaan oleh <strong>PIHAK KEDUA</strong> dihentikan sementara dan dilanjutkan kembali setelah peristiwa tersebut berakhir, merujuk pada ketentuan yang ditetapkan oleh <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">{{ $hasCapiKegiatan ? '(4)' : '(3)' }}</div>
                <div class="pasal-item-content">
                    Apabila akibat peristiwa sebagaimana dimaksud pada ayat (1){{ $hasCapiKegiatan ? ' dan/atau ayat (2)' : '' }} tidak memungkinkan dilanjutkan/diselesaikannya pelaksanaan pekerjaan, <strong>PIHAK KEDUA</strong> berhak menerima honorarium secara proporsional sesuai pekerjaan yang telah diselesaikan dan diterima oleh <strong>PIHAK PERTAMA.</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 12 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 12</div>
            <div class="pasal-content">
                Hal-hal yang belum diatur dalam Perjanjian ini atau segala perubahan terhadap Perjanjian ini diatur lebih lanjut oleh <strong>PARA PIHAK</strong> dalam perjanjian tambahan/adendum dan merupakan bagian tidak terpisahkan dari Perjanjian ini.
            </div>
        </div>
    </div>

    <!-- Pasal 13 -->
    <div class="pasal">
        <div class="pasal-header-group">
            <div class="pasal-title">Pasal 13</div>
            <div class="pasal-list">
                <div class="pasal-item">
                    <div class="pasal-item-number">(1)</div>
                    <div class="pasal-item-content">
                        Segala perselisihan atau perbedaan pendapat yang mungkin timbul sebagai akibat dari Perjanjian ini, diselesaikan secara musyawarah untuk mufakat oleh <strong>PARA PIHAK.</strong>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="pasal-remaining-items">
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Apabila musyawarah untuk mufakat sebagaimana dimaksud pada ayat (1) tidak berhasil, maka <strong>PARA PIHAK</strong> sepakat untuk menyelesaikan perselisihan dengan memilih kedudukan/domisili hukum di Kepaniteraan Pengadilan Negeri Sawahlunto.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(3)</div>
                <div class="pasal-item-content">
                    Selama perselisihan dalam proses penyelesaian pengadilan, <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> wajib tetap melaksanakan kewajiban masing-masing berdasarkan Perjanjian ini.
                </div>
            </div>
        </div>
    </div>

    @endif

    <div style="page-break-inside: avoid;">
        <div class="content">
            Demikian Perjanjian ini dibuat dan ditandatangani oleh <strong>PARA PIHAK</strong> dalam 2 (dua) rangkap asli bermeterai cukup, tanpa paksaan dari <strong>PIHAK</strong> manapun dan untuk dilaksanakan oleh <strong>PARA PIHAK.</strong>
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
    </div>

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                if ($pageNumber <= 1) {
                    return;
                }
                $topMargin = 85.04; // 3 cm

                $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
                $size = 10;
                $text = '-' . $pageNumber . '-';
                $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
                $x = ($canvas->get_width() - $textWidth) / 2;
                $y = $topMargin / 2;

                $canvas->text($x, $y, $text, $font, $size);
            });
        }
    </script>
</body>
</html>


    <!-- PAGE BREAK -->
    <div class="page-break"></div>

    <!-- LAMPIRAN -->
    <div class="lampiran-section" style="width: 297mm; height: 210mm;">
        @if(($lampiranTemplate ?? 'default') === 'sensus_ekonomi')
        @php($specialLampiran = $lampiranPayload ?? [])
        <div class="lampiran-header" style="margin-bottom: 12px;">
            <div class="lampiran-title" style="margin-left: 0; text-align: center; line-height: 1.3;">
                LAMPIRAN<br>
                PERJANJIAN KERJA PETUGAS LAPANGAN<br>
                SENSUS EKONOMI 2026<br>
                PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
                NOMOR: {{ $nomorSpk }}
            </div>
        </div>

        <div class="lampiran-subtitle">
            DAFTAR URAIAN PEKERJAAN, WAKTU PENYELESAIAN, TARGET PEKERJAAN DAN NILAI PERJANJIAN
        </div>

        <table class="sensus-lampiran-table">
            <colgroup>
                <col class="col-uraian">
                <col class="col-waktu">
                <col class="col-persentase">
                <col class="col-volume">
                <col class="col-nilai">
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
                    <th style="width: 66%;">(1)</th>
                    <th style="width: 11%;">(2)</th>
                    <th style="width: 5%;">(3)</th>
                    <th style="width: 11%;">(4)</th>
                    <th style="width: 7%;">(5)</th>
                </tr>
                    <tr>
                        <td class="left">
                            @foreach(($group['items'] ?? []) as $index => $item)
                                {{ $index + 1 }}. {{ $item }}@if($index < count($group['items'] ?? []) - 1)<br>@endif
                            @endforeach
                        </td>
                        <td>{{ $group['waktu_penyelesaian'] ?? '-' }}</td>
                        <td>{{ $group['persentase'] ?? '-' }}</td>
                        <td class="left volume-cell">{{ $group['volume'] ?? '-' }}</td>
                        <td class="right money-cell">Rp {{ number_format((float) ($group['nilai_perjanjian'] ?? 0), 0, ',', '.') }},00</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="left">Total</td>
                    <td>{{ $specialLampiran['total']['waktu_penyelesaian'] ?? '-' }}</td>
                    <td rowspan="2">{{ $specialLampiran['total']['persentase'] ?? '-' }}</td>
                    <td rowspan="2" class="left volume-cell">{{ $specialLampiran['total']['volume'] ?? '-' }}</td>
                    <td rowspan="2" class="right money-cell">Rp {{ number_format((float) ($specialLampiran['total']['nilai_perjanjian'] ?? 0), 0, ',', '.') }},00</td>
                </tr>
                <tr>
                    <td colspan="2" class="table-footer"><em>Terbilang: {{ terbilang($totalHonor) }} rupiah</em></td>
                </tr>
            </tbody>
        </table>
        @else
        <div class="lampiran-header">
            <div class="lampiran-title">
                @php
                    // Use same title logic
                    $workType = $workType ?? 'lapangan';
                    if ($workType === 'lapangan_pengolahan') {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS LAPANGAN DAN PETUGAS PENGOLAHAN SURVEI';
                    } elseif ($workType === 'pengolahan') {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS PENGOLAHAN SURVEI';
                    } else {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS LAPANGAN SURVEI';
                    }
                @endphp
                Lampiran<br>
                {{ $judulSpkLampiran }} TAHUN {{ $kegiatan->tahun_anggaran }} PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
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
        @endif
    </div>

</body>
</html>
