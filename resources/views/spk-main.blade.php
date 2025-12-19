<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>SPK - {{ $petugas->nama }}</title>
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

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h3 {
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

        .pasal {
            margin: 20px 0;
        }

        .pasal-title {
            text-align: center;
            font-weight: bold;
            margin: 15px 0 10px 0;
        }

        .pasal-content {
            text-align: justify;
            margin: 10px 0;
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

        .sub-item {
            margin: 8px 0 8px 30px;
            display: table;
            width: 100%;
        }

        .sub-item-letter {
            display: table-cell;
            width: 30px;
            vertical-align: top;
        }

        .sub-item-content {
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
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- MAIN SPK DOCUMENT -->
    <div class="header">
        <h3>PERJANJIAN KERJA</h3>
        <h3>PETUGAS LAPANGAN KEGIATAN {{ strtoupper($kegiatan->nama_kegiatan) }} TAHUN {{ $kegiatan->tahun_anggaran }}</h3>
        <h3>PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</h3>
        <span style="font-weight: bold; margin-top: 5px;">NOMOR: {{ $nomorSpk }}</span>
    </div>

    <div class="content">
        Pada hari ini {{ $tanggalSpk->locale('id')->translatedFormat('l') }}, tanggal {{ $tanggalSpk->locale('id')->translatedFormat('d') }}, bulan {{ $tanggalSpk->locale('id')->translatedFormat('F') }}, tahun {{ $tanggalSpk->locale('id')->translatedFormat('Y') }}, bertempat di Sawahlunto, yang bertanda tangan di bawah ini:
    </div>

    <div class="parties">
        <div class="party">
            <div class="party-number">1.</div>
            <div class="party-name"><strong>{{ strtoupper($kepalaBps) }}</strong></div>
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
                @if($petugas->jenis_petugas === 'organik')
                    PNS/ASN, berkedudukan di {{ $petugas->alamat ?? 'Sawahlunto' }}, bertindak untuk dan atas nama diri sendiri, selanjutnya disebut <strong>PIHAK KEDUA</strong>.
                @else
                    Wiraswasta, berkedudukan di {{ $petugas->alamat ?? 'Sawahlunto' }}, bertindak untuk dan atas nama diri sendiri, selanjutnya disebut <strong>PIHAK KEDUA</strong>.
                @endif
            </div>
        </div>
    </div>

    <div class="content">
        bahwa <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> yang secara bersama-sama disebut <strong>PARA PIHAK</strong>, sepakat untuk mengikatkan diri dalam Perjanjian Kerja Petugas Lapangan Kegiatan {{ $kegiatan->nama_kegiatan }} Tahun {{ $kegiatan->tahun_anggaran }} pada Badan Pusat Statistik Kota Sawahlunto, yang selanjutnya disebut Perjanjian, dengan ketentuan-ketentuan sebagai berikut:
    </div>

    <!-- Pasal 1 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 1</div>
        <div class="pasal-content">
            <strong>PIHAK PERTAMA</strong> memberikan pekerjaan kepada <strong>PIHAK KEDUA</strong> dan <strong>PIHAK KEDUA</strong> menerima pekerjaan dari <strong>PIHAK PERTAMA</strong> sebagai Petugas Lapangan Kegiatan {{ $kegiatan->nama_kegiatan }} Tahun {{ $kegiatan->tahun_anggaran }} pada Badan Pusat Statistik Kota Sawahlunto, dengan lingkup pekerjaan yang ditetapkan oleh <strong>PIHAK PERTAMA</strong>.
        </div>
    </div>

    <!-- Pasal 2 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 2</div>
        <div class="pasal-content">
            Ruang lingkup pekerjaan dalam Perjanjian ini mengacu pada wilayah kerja dan beban kerja sebagaimana tertuang dalam lampiran Perjanjian, pedoman Petugas Lapangan Kegiatan {{ $kegiatan->nama_kegiatan }} Tahun {{ $kegiatan->tahun_anggaran }} pada Badan Pusat Statistik Kota Sawahlunto, dan ketentuan-ketentuan yang ditetapkan oleh <strong>PIHAK PERTAMA</strong>.
        </div>
    </div>

    <!-- Pasal 3 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 3</div>
        <div class="pasal-content">
            Jangka Waktu Perjanjian terhitung sejak tanggal {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->format('d') }} {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->locale('id')->translatedFormat('F Y') }} sampai dengan tanggal {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth()->format('d') }} {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth()->locale('id')->translatedFormat('F Y') }}.
        </div>
    </div>

    <!-- Pasal 4 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 4</div>
        <div class="pasal-content">
            <strong>PIHAK KEDUA</strong> berkewajiban melaksanakan seluruh pekerjaan yang diberikan oleh <strong>PIHAK PERTAMA</strong> sampai selesai, sesuai ruang lingkup pekerjaan sebagaimana dimaksud dalam Pasal 2.
        </div>
    </div>

    <!-- Pasal 5 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 5</div>
        <div class="pasal-list">
            <div class="pasal-item">
                <div class="pasal-item-number">(1)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> berhak untuk mendapatkan honorarium petugas dari <strong>PIHAK PERTAMA</strong> sebesar Rp {{ number_format($totalHonor, 0, ',', '.') }} ({{ terbilang($totalHonor) }} rupiah) untuk pekerjaan sebagaimana dimaksud dalam Pasal 2, termasuk biaya pajak, bea materai, dan jasa pelayanan keuangan.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    <strong>PIHAK KEDUA</strong> tidak diberikan honorarium tambahan apabila melakukan pekerjaan di luar jadwal atau terdapat tambahan waktu pelaksanaan pekerjaan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 6 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 6</div>
        <div class="pasal-list">
            <div class="pasal-item">
                <div class="pasal-item-number">(1)</div>
                <div class="pasal-item-content">
                    Pembayaran honorarium sebagaimana dimaksud dalam Pasal 5 dilakukan setelah <strong>PIHAK KEDUA</strong> menyelesaikan dan menyerahkan seluruh hasil pekerjaan sebagaimana dimaksud dalam Pasal 2 kepada <strong>PIHAK PERTAMA</strong>.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Pembayaran sebagaimana dimaksud pada ayat (1) dilakukan oleh <strong>PIHAK PERTAMA</strong> kepada <strong>PIHAK KEDUA</strong> sesuai dengan ketentuan peraturan perundang-undangan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 7 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 7</div>
        <div class="pasal-content">
            Penyerahan hasil pekerjaan sebagaimana dimaksud dalam Pasal 2 dilakukan secara bertahap dan selambat-lambatnya seluruh hasil pekerjaan diserahkan sesuai jadwal yang tercantum dalam lampiran, yang dinyatakan dalam Berita Acara Serah Terima Hasil Pekerjaan yang ditandatangani oleh <strong>PARA PIHAK</strong>.
        </div>
    </div>

    <!-- Pasal 8 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 8</div>
        <div class="pasal-content">
            <strong>PIHAK PERTAMA</strong> dapat memutuskan Perjanjian ini secara sepihak sewaktu-waktu dalam hal <strong>PIHAK KEDUA</strong> tidak dapat melaksanakan kewajibannya sebagaimana dimaksud dalam Pasal 4, dengan menerbitkan Surat Pemutusan Perjanjian Kerja.
        </div>
    </div>

    <!-- Pasal 9 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 9</div>
        <div class="pasal-list">
            <div class="pasal-item">
                <div class="pasal-item-number">(1)</div>
                <div class="pasal-item-content">
                    Apabila <strong>PIHAK KEDUA</strong> mengundurkan diri dengan tidak menyelesaikan pekerjaan sebagaimana dimaksud dalam Pasal 2, maka akan diberikan sanksi oleh <strong>PIHAK PERTAMA</strong>, sebagai berikut:
                    <div class="sub-item">
                        <div class="sub-item-letter">a.</div>
                        <div class="sub-item-content">
                            mengundurkan diri setelah pelatihan diberikan sanksi sebesar Rp{{ number_format($totalHonor, 0, ',', '.') }} ({{ terbilang($totalHonor) }} rupiah)
                        </div>
                    </div>
                    <div class="sub-item">
                        <div class="sub-item-letter">b.</div>
                        <div class="sub-item-content">
                            mengundurkan diri pada saat pelaksanaan pekerjaan lapangan, diberikan sanksi tidak diberikan honorarium atas pekerjaan yang telah dilaksanakan.
                        </div>
                    </div>
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Dikecualikan tidak membayar ganti rugi sebagaimana dimaksud pada ayat (1) kepada <strong>PIHAK PERTAMA</strong>, apabila <strong>PIHAK KEDUA</strong> meninggal dunia, mengundurkan diri karena sakit dengan keterangan rawat inap, kecelakaan dengan keterangan kepolisian, dan/atau telah diberikan Surat Pemutusan Perjanjian Kerja dari <strong>PIHAK PERTAMA</strong>.
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

    <!-- Pasal 10 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 10</div>
        <div class="pasal-list">
            <div class="pasal-item">
                <div class="pasal-item-number">(1)</div>
                <div class="pasal-item-content">
                    Apabila terjadi Keadaan Kahar, yang meliputi bencana alam dan bencana sosial, <strong>PIHAK KEDUA</strong> memberitahukan kepada <strong>PIHAK PERTAMA</strong> dalam waktu paling lambat 7 (tujuh) hari sejak mengetahui atas kejadian Keadaan Kahar dengan menyertakan bukti.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Pada saat terjadi Keadaan Kahar, pelaksanaan pekerjaan oleh <strong>PIHAK KEDUA</strong> dihentikan sementara dan dilanjutkan kembali setelah Keadaan Kahar berakhir, namun apabila akibat Keadaan Kahar tidak memungkinkan dilanjutkan/diselesaikannya pelaksanaan pekerjaan, <strong>PIHAK KEDUA</strong> berhak menerima honorarium secara proporsional sesuai pekerjaan yang telah dilaksanakan.
                </div>
            </div>
        </div>
    </div>

    <!-- Pasal 11 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 11</div>
        <div class="pasal-content">
            Segala sesuatu yang belum atau tidak cukup diatur dalam Perjanjian ini, dituangkan dalam perjanjian tambahan/addendum dan merupakan bagian tidak terpisahkan dari perjanjian ini.
        </div>
    </div>

    <!-- Pasal 12 -->
    <div class="pasal">
        <div class="pasal-title">Pasal 12</div>
        <div class="pasal-list">
            <div class="pasal-item">
                <div class="pasal-item-number">(1)</div>
                <div class="pasal-item-content">
                    Segala perselisihan atau perbedaan pendapat yang timbul sebagai akibat adanya Perjanjian ini akan diselesaikan secara musyawarah untuk mufakat.
                </div>
            </div>
            <div class="pasal-item">
                <div class="pasal-item-number">(2)</div>
                <div class="pasal-item-content">
                    Apabila perselisihan tidak dapat diselesaikan sebagaimana dimaksud pada ayat (1), <strong>PARA PIHAK</strong> sepakat menyelesaikan perselisihan dengan memilih kedudukan/domisili hukum di Panitera Pengadilan Negeri Sawahlunto.
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        Demikian Perjanjian ini dibuat dan ditandatangani oleh <strong>PARA PIHAK</strong> dalam 2 (dua) rangkap asli, tanpa paksaan dari <strong>PIHAK</strong> manapun dan untuk dilaksanakan oleh <strong>PARA PIHAK</strong>.
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
                <div class="signature-name">{{ strtoupper($kepalaBps) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
