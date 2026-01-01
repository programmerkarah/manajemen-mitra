<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $nomor_bast ?? ($bast->nomor_bast ?? 'BAST') }} - {{$bast->petugas['nama']}} BAST Petugas Kegiatan Survei </title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("/Bookman Old Style Normal.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("/Bookman Old Style Bold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
			font-family: 'Bookman Old Style';
			src: url('{{ public_path("/Bookman Old Style Italic.ttf") }}') format('truetype');
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
            margin: 2cm 2cm 2cm 2cm;
            padding: 0;
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
    @php
    // Generate judul dinamis berdasarkan jenis kegiatan dan peran
    $jenisPetugasList = [];
    $isSurvei = false;
    $bulanBast = \Carbon\Carbon::parse($bast->tanggal_bast)->locale('id')->isoFormat('MMMM');
    $tahunBast = \Carbon\Carbon::parse($bast->tanggal_bast)->format('Y');
    
    if(isset($bast->kegiatan_list) && count($bast->kegiatan_list) > 0) {
        foreach($bast->kegiatan_list as $keg) {
            $jenisKegiatan = strtolower($keg['jenis_kegiatan'] ?? '');
            $peran = strtolower($keg['peran'] ?? '');
            $jenisPetugas = '';
            
            if ($jenisKegiatan === 'sensus') {
                if ($peran === 'pcl' || $peran === 'ppl' || $peran === 'pcl_ppl') {
                    $jenisPetugas = 'PETUGAS LAPANGAN (' . strtoupper($keg['nama_kegiatan']) . ')';
                } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                    $jenisPetugas = 'PETUGAS PEMERIKSA LAPANGAN (' . strtoupper($keg['nama_kegiatan']) . ')';
                } elseif (str_contains($peran, 'olah')) {
                    $jenisPetugas = 'PETUGAS PENGOLAHAN (' . strtoupper($keg['nama_kegiatan']) . ')';
                } else {
                    $jenisPetugas = 'PETUGAS LAPANGAN (' . strtoupper($keg['nama_kegiatan']) . ')';
                }
            } else {
                // Untuk survei
                $isSurvei = true;
                if ($peran === 'pcl' || $peran === 'ppl' || $peran === 'pcl_ppl') {
                    $jenisPetugas = 'PETUGAS LAPANGAN SURVEI';
                } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                    $jenisPetugas = 'PETUGAS PEMERIKSA LAPANGAN SURVEI';
                } elseif (str_contains($peran, 'olah') || str_contains($peran, 'pengawas')) {
                    $jenisPetugas = 'PETUGAS PENGOLAHAN SURVEI';
                } else {
                    $jenisPetugas = 'PETUGAS LAPANGAN SURVEI';
                }
            }
            
            if (!in_array($jenisPetugas, $jenisPetugasList)) {
                $jenisPetugasList[] = $jenisPetugas;
            }
        }
    } elseif(isset($nama_kegiatan)) {
        $jenisPetugasList[] = strtoupper($nama_kegiatan);
    }
    @endphp
    
    <div class="header">
        <div class="title">BERITA ACARA SERAH TERIMA</div>
        @if($isSurvei)
            @foreach($jenisPetugasList as $jenisPetugas)
                <div class="title">{{ $jenisPetugas }}</div>
            @endforeach
            <div class="title">BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}</div>
            <div class="title">BULAN {{ strtoupper($bulanBast) }} {{ $tahunBast }}</div>
            <div class="title">PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}</div>
        @else
            <div class="title">HASIL PEKERJAAN</div>
            @foreach($jenisPetugasList as $jenisPetugas)
                <div class="title">{{ $jenisPetugas }}</div>
            @endforeach
            <div class="title">TAHUN {{ $tahunBast }}</div>
            <div class="title">PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}</div>
        @endif
    </div>
    <div class="nomor" style="margin-top: 15px; text-align: center;">Nomor: {{ $bast->nomor_bast }}</div>

    <div class="content">
        @php
        // Gunakan hari kerja terakhir jika tanggal BAST jatuh di hari libur
        $tanggalBastAdjusted = getHariKerjaTerakhir($bast->tanggal_bast);
        @endphp
        <p>
            Pada hari ini {{ $tanggalBastAdjusted->locale('id')->isoFormat('dddd') }}, tanggal {{ tanggalTerbilang($tanggalBastAdjusted->format('d')) }}, bulan {{ $tanggalBastAdjusted->locale('id')->isoFormat('MMMM') }} tahun {{ tahunTerbilang($tanggalBastAdjusted->format('Y')) }} bertempat di Kantor Badan Pusat Statistik {{ $bast->lokasi_kegiatan }}, kami yang bertanda tangan di bawah ini:
        </p>

        <table style="border: none;">
            <tr style="border: none;">
                <td style="width: 5%; border: none; vertical-align: top;">1.</td>
                <td style="width: 20%; border: none; vertical-align: top;">Nama</td>
                <td style="width: 1%; border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $bast->nama_ppk }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">NIP</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $bast->nip_ppk ?? '-' }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Jabatan</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">{{ $jabatan_ppk ?? 'Pejabat Pembuat Komitmen Badan Pusat Statistik ' . $bast->lokasi_kegiatan . ' untuk Program Penyediaan dan Pelayanan Informasi Statistik' }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Unit Kerja</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">Badan Pusat Statistik {{ $bast->lokasi_kegiatan }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Alamat Unit Kerja</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">{{ $alamat_unit_kerja ?? 'Jl. Jend. Ahmad Yani No.7, ' . $bast->lokasi_kegiatan }}</td>
            </tr>
        </table>

        <span>bertindak untuk dan atas nama BPS {{ $bast->lokasi_kegiatan }}, selanjutnya disebut sebagai <strong>PIHAK PERTAMA.</strong></span>

        <table style="border: none; margin: 5px 0 20px 0;">
            <tr style="border: none;">
                <td style="width: 5%; border: none; vertical-align: top;">2.</td>
                <td style="width: 20%; border: none; vertical-align: top;">Nama</td>
                <td style="width: 1%; border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $bast->petugas['nama'] }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">NIK</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top;">{{ $bast->petugas['nik'] ?? '-' }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top;">Alamat</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">{{ $bast->petugas['alamat'] ?? '-' }}</td>
            </tr>
        </table>
        bertindak untuk dan atas namanya sendiri, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.

        @php
        $kegiatanText = '';
        $nomorSpkText = '';
        if(isset($bast->kegiatan_list) && count($bast->kegiatan_list) > 0) {
            // Filter hanya kegiatan yang memiliki nomor SPK (bukan "Belum ada SPK")
            $kegiatanWithSpk = collect($bast->kegiatan_list)->filter(function($keg) {
                return isset($keg['nomor_spk']) && $keg['nomor_spk'] !== 'Belum ada SPK';
            });
            
            if($kegiatanWithSpk->isNotEmpty()) {
                // Tampilkan hanya kegiatan yang memiliki SPK
                foreach($kegiatanWithSpk as $idx => $keg) {
                    $kegiatanText .= ($idx > 0 ? 'dan ' : '') . $keg['nama_kegiatan'] . ' ';
                    $nomorSpkText .= ($idx > 0 ? 'dan ' : '') . 'Nomor: ' . $keg['nomor_spk'] . ' ';
                }
            } else {
                // Jika semua kegiatan belum ada SPK
                foreach($bast->kegiatan_list as $idx => $keg) {
                    $kegiatanText .= ($idx > 0 ? 'dan ' : '') . $keg['nama_kegiatan'] . ' ';
                }
                $nomorSpkText = 'Belum ada SPK';
            }
        }
        @endphp

        <div style="page-break-inside: avoid; margin-top: 10px;">
            <p style="text-align: justify;">
                Dengan ini menyatakan:
            </p>

            <ol style="margin: 10px 0 0 20px;">
            @if($isSurvei)
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> telah melaksanakan kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan Perjanjian Kerja {{ $nomorSpkText }}.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> telah menyelesaikan kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan hasil pemeriksaan dan evaluasi pekerjaan sebagaimana tercantum dalam Lampiran Berita Acara ini.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                Berdasarkan angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} kepada <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil pekerjaan tersebut yang telah sesuai dengan seharusnya.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                Hasil pekerjaan kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} sebagaimana dimaksud dalam angka 3 di atas, berupa data hasil kegiatan Survei Badan Pusat Statistik bulan {{ $bulanBast }} {{ $tahunBast }} yang telah diperiksa, sebagaimana tercantum dalam Lampiran Berita Acara ini.
            </li>
            @if($menggunakan_fasih ?? false)
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> menghapus Aplikasi FASIH dan data hasil kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} pada perangkat handphone <strong>PIHAK KEDUA</strong> disaksikan oleh pegawai BPS {{ $bast->lokasi_kegiatan }} yang ditunjuk oleh <strong>PIHAK PERTAMA</strong>.
            </li>
            @endif
            <li style="margin: 6px 0; text-align: justify;">
                Untuk hasil kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} sebagaimana dimaksud pada angka 4 yang memerlukan pemeriksaan lanjutan, akan dilakukan pengecekan, perubahan, dan/atau kunjungan kembali ke lapangan merujuk pada perjanjian yang ditandatangani oleh <strong>PARA PIHAK</strong>.
            </li>
            @else
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> telah melaksanakan pekerjaan {{ $kegiatanText }}pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan Perjanjian Kerja {{ $nomorSpkText }}.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> telah menyelesaikan pekerjaan {{ $kegiatanText }}pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan hasil pemeriksaan dan evaluasi pekerjaan sebagaimana tercantum dalam lampiran.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                Berdasarkan angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil pekerjaan {{ $kegiatanText }}pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} kepada <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil pekerjaan tersebut yang telah sesuai dengan seharusnya.
            </li>
            <li style="margin: 6px 0; text-align: justify;">
                Hasil pekerjaan {{ $kegiatanText }}pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} sebagaimana dimaksud dalam angka 3 di atas, berupa data hasil {{ $kegiatanText }}yang telah diperiksa, sebagaimana tercantum dalam Lampiran Berita Acara ini.
            </li>
            @if($menggunakan_fasih ?? false)
            <li style="margin: 6px 0; text-align: justify;">
                <strong>PIHAK KEDUA</strong> menghapus Aplikasi FASIH dan data hasil {{ $kegiatanText }}pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} pada perangkat handphone <strong>PIHAK KEDUA</strong> disaksikan oleh pegawai BPS {{ $bast->lokasi_kegiatan }} yang ditunjuk oleh <strong>PIHAK PERTAMA</strong>.
            </li>
            @endif
            <li style="margin: 6px 0; text-align: justify;">
                Untuk hasil {{ $kegiatanText }}sebagaimana dimaksud pada angka 4 yang memerlukan pemeriksaan lanjutan, akan dilakukan pengecekan, perubahan, dan/atau kunjungan kembali ke lapangan merujuk pada perjanjian yang ditandatangani oleh <strong>PARA PIHAK</strong>.
            </li>
            @endif
            </ol>
        </div>

        <div style="page-break-inside: avoid;">
            <p style="margin-top: 20px; text-align: justify;">
                Demikian Berita Acara ini dibuat dengan sebenarnya dalam rangkap 2 (dua) dan menjadi sah berlaku setelah ditandatangani oleh <strong>PARA PIHAK</strong>.
            </p>
            <div style="height: 10px;"></div>
            <div class="signature-section">
                <div class="signature-box signature-left">
                    <div>Ketua Tim,</div>
                    <div style="height:10px;"></div>
                    <div class="signature-name">{{ strtoupper($bast->ketua_tim['nama'] ?? '') }}</div>
                </div>
                <div class="signature-box signature-right">
                    <div>PIHAK PERTAMA,</div>
                    <div style="height:10px;"></div>
                    <div class="signature-name">{{ strtoupper($bast->nama_ppk ?? '') }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</body>

</html>