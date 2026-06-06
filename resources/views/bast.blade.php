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
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: #000;
            orphans: 3;
            widows: 3;
            margin: 1.5cm 2cm 1cm 2.5cm;
            padding: 0;
        }

        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .title {
            font-size: 11pt;
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
            margin-bottom: 0;
            font-weight: bold;
        }

        .signature-name.signature-name-collapsed {
            margin-bottom: 0;
            line-height: 1.15;
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
            font-size: 11pt;
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
            font-size: 11pt;
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
    $isSensusEkonomi = (bool) ($bast->is_sensus_ekonomi ?? false);
    $isSurvei = ! $isSensusEkonomi;
    $bulanBast = \Carbon\Carbon::parse($bast->tanggal_bast)->locale('id')->isoFormat('MMMM');
    $tahunBast = \Carbon\Carbon::parse($bast->tanggal_bast)->format('Y');
    
    if(isset($bast->kegiatan_list) && count($bast->kegiatan_list) > 0) {
        foreach($bast->kegiatan_list as $keg) {
            $jenisKegiatan = strtolower($keg['jenis_kegiatan'] ?? '');
            $peran = strtolower($keg['peran'] ?? '');
            $jenisPetugas = '';
            $isSensusEkonomiKegiatan = $isSensusEkonomi
                || $jenisKegiatan === 'sensus'
                || str_contains(strtolower((string) ($keg['nama_kegiatan'] ?? '')), 'sensus ekonomi')
                || str_contains((string) ($bast->nomor_bast ?? ''), 'BAST-SE2026');
            
            if ($isSensusEkonomiKegiatan) {
                if ($peran === 'pcl' || $peran === 'ppl' || $peran === 'pcl_ppl') {
                    $jenisPetugas = 'PETUGAS LAPANGAN ' . strtoupper($keg['nama_kegiatan']);
                } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                    $jenisPetugas = 'PETUGAS PEMERIKSA LAPANGAN ' . strtoupper($keg['nama_kegiatan']);
                } elseif (str_contains($peran, 'olah')) {
                    $jenisPetugas = 'PETUGAS PENGOLAHAN ' . strtoupper($keg['nama_kegiatan']);
                } else {
                    $jenisPetugas = 'PETUGAS LAPANGAN ' . strtoupper($keg['nama_kegiatan']);
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

    $formatSignatureName = static function (?string $rawName): array {
        $normalizedName = strtoupper(trim((string) $rawName));
        $normalizedName = preg_replace('/\s+/', ' ', $normalizedName ?? '') ?? '';

        if ($normalizedName === '') {
            return [
                'display' => '',
                'collapsed' => false,
            ];
        }

        $nameParts = explode(' ', $normalizedName);

        if (count($nameParts) <= 2) {
            return [
                'display' => $normalizedName,
                'collapsed' => false,
            ];
        }

        $firstLineWordCount = (int) ceil(count($nameParts) / 2);

        return [
            'display' => implode(' ', array_slice($nameParts, 0, $firstLineWordCount)) . '<br>' . implode(' ', array_slice($nameParts, $firstLineWordCount)),
            'collapsed' => true,
        ];
    };

    $signatureNamePihakKedua = $formatSignatureName($bast->petugas['nama'] ?? '');
    $rawPpkName = trim(preg_replace('/^(Dr\.?|Prof\.?|Drs\.?|Ir\.?|H\.?)\s+/i', '', explode(',', $bast->nama_ppk ?? '')[0]));
    $signatureNamePihakPertama = $formatSignatureName($rawPpkName);
    @endphp
    
    <div class="header">
        <div class="title">BERITA ACARA SERAH TERIMA HASIL PEKERJAAN</div>
        @if($isSurvei)
            @foreach($jenisPetugasList as $jenisPetugas)
                <div class="title">{{ $jenisPetugas }}</div>
            @endforeach
            <div class="title">BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}</div>
            <div class="title">BULAN {{ strtoupper($bulanBast) }} {{ $tahunBast }}</div>
            <div class="title">PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}</div>
        @else
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
                <td style="width: 25%; border: none; vertical-align: top;">Nama</td>
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
                <td style="border: none; vertical-align: top; text-align: justify;">{{ $jabatan_ppk ?? 'Pejabat Pembuat Komitmen Badan Pusat Statistik ' . $bast->lokasi_kegiatan }}.</td>
            </tr>            
        </table>

        <span>bertindak untuk dan atas nama Badan Pusat Statistik {{ $bast->lokasi_kegiatan }}, selanjutnya disebut sebagai <strong>PIHAK PERTAMA.</strong></span>

        <table style="border: none; margin: 5px 0 20px 0;">
            <tr style="border: none;">
                <td style="width: 5%; border: none; vertical-align: top;">2.</td>
                <td style="width: 25%; border: none; vertical-align: top;">Nama</td>
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
                <td style="border: none; vertical-align: top;">Jabatan</td>
                <td style="border: none; vertical-align: top;">:</td>
                <td style="border: none; vertical-align: top; text-align: justify;">{{ ucwords(strtolower($jenisPetugas) ?? '-'). ' Badan Pusat Statistik '. ucwords(strtolower($bast->lokasi_kegiatan)) . ' Bulan '. $bulanBast . ' ' . $tahunBast }}</td>
            </tr>
        </table>
        bertindak untuk dan atas namanya sendiri, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.

        @php
        $kegiatanText = '';
        $nomorSpkText = '';
        $muatanInput = isset($bast->muatan_input) ? (int) $bast->muatan_input : null;
        $muatanPrelist = isset($bast->muatan_prelist) ? (int) $bast->muatan_prelist : null;
        $formatSensusEkonomiVolume = static function ($jumlahSls, $jumlahUsaha, $jumlahKeluarga, ?int $fallbackTotal = null) {
            $segments = [];

            if (is_numeric($jumlahSls) && (int) $jumlahSls > 0) {
                $segments[] = number_format((int) $jumlahSls, 0, ',', '.') . ' SLS/sub-SLS';
            }

            $usahaKeluarga = [];

            if (is_numeric($jumlahUsaha) && (int) $jumlahUsaha > 0) {
                $usahaKeluarga[] = number_format((int) $jumlahUsaha, 0, ',', '.') . ' usaha';
            }

            if (is_numeric($jumlahKeluarga) && (int) $jumlahKeluarga > 0) {
                $usahaKeluarga[] = number_format((int) $jumlahKeluarga, 0, ',', '.') . ' keluarga';
            }

            if ($usahaKeluarga !== []) {
                $segments[] = implode('/', $usahaKeluarga);
            }

            if ($segments !== []) {
                return implode(' dan/atau ', $segments);
            }

            if (is_numeric($fallbackTotal) && (int) $fallbackTotal > 0) {
                return number_format((int) $fallbackTotal, 0, ',', '.');
            }

            return null;
        };
        if(isset($bast->kegiatan_list) && count($bast->kegiatan_list) > 0) {
            // Filter hanya kegiatan yang memiliki nomor SPK (bukan "Belum ada SPK")
            $kegiatanWithSpk = collect($bast->kegiatan_list)->filter(function($keg) {
                return isset($keg['nomor_spk']) && $keg['nomor_spk'] !== 'Belum ada SPK';
            });
            
            if($kegiatanWithSpk->isNotEmpty()) {
                // Tampilkan hanya kegiatan yang memiliki SPK
                foreach($kegiatanWithSpk as $idx => $keg) {
                    $kegiatanText .= ($idx > 0 ? 'dan ' : '') . $keg['nama_kegiatan'] . ' ';
                    $nomorSpkText .= ($idx > 0 ? 'dan ' : '') . 'Nomor: ' . $keg['nomor_spk'];
                }
            } else {
                // Jika semua kegiatan belum ada SPK
                foreach($bast->kegiatan_list as $idx => $keg) {
                    $kegiatanText .= ($idx > 0 ? 'dan ' : '') . $keg['nama_kegiatan'] . ' ';
                }
                $nomorSpkText = 'Belum ada SPK';
            }
        }
        $isSensusEkonomi = $isSensusEkonomi
            || str_contains(strtolower($kegiatanText), 'sensus ekonomi')
            || str_contains((string) ($bast->nomor_bast ?? ''), 'BAST-SE2026');
        $targetPekerjaanText = $isSensusEkonomi
            ? $formatSensusEkonomiVolume(
                $bast->target_jumlah_frame_sampel ?? null,
                $bast->target_muatan_prelist_usaha ?? null,
                $bast->target_muatan_prelist_keluarga ?? null,
                $muatanPrelist
            )
            : null;
        $hasilPekerjaanText = $isSensusEkonomi
            ? $formatSensusEkonomiVolume(
                $bast->hasil_jumlah_frame_sampel ?? $bast->target_jumlah_frame_sampel ?? null,
                $bast->hasil_realisasi_usaha ?? null,
                $bast->hasil_realisasi_keluarga ?? null,
                $muatanInput
            )
            : null;
        @endphp

        <div style="page-break-inside: auto; margin-top: 10px;">
            <p style="text-align: justify;">
                Dengan ini menyatakan:
            </p>

            @php $nomorPoin = 0; @endphp
            <table style="border: none; border-collapse: collapse; width: 100%; margin: 10px 0 0 0;">
            @if($isSurvei)
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;"><strong>PIHAK KEDUA</strong> telah melaksanakan kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan Perjanjian Kerja {{$nomorSpkText}}.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;"><strong>PIHAK KEDUA</strong> telah menyelesaikan kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan hasil pemeriksaan dan evaluasi pekerjaan sebagaimana tercantum dalam Lampiran Berita Acara ini.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;">Berdasarkan angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} kepada <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil pekerjaan tersebut yang telah sesuai dengan seharusnya.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;">Hasil pekerjaan kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} sebagaimana dimaksud dalam angka 3 di atas, berupa data hasil kegiatan Survei Badan Pusat Statistik {{$bast->lokasi_kegiatan}} bulan {{ $bulanBast }} {{ $tahunBast }} yang telah diperiksa, sebagaimana tercantum dalam Lampiran Berita Acara ini.</td>
            </tr>
            @if($menggunakan_fasih ?? false)
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;"><strong>PIHAK KEDUA</strong> menghapus Aplikasi FASIH dan data hasil kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} pada perangkat handphone <strong>PIHAK KEDUA</strong> disaksikan oleh pegawai BPS {{ $bast->lokasi_kegiatan }} yang ditunjuk oleh <strong>PIHAK PERTAMA</strong>.</td>
            </tr>
            @endif
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;">Untuk hasil kegiatan Survei Badan Pusat Statistik Kota Sawahlunto bulan {{ $bulanBast }} {{ $tahunBast }} sebagaimana dimaksud pada angka 4 yang memerlukan pemeriksaan lanjutan, akan dilakukan pengecekan, perubahan, dan/atau kunjungan kembali ke lapangan merujuk pada Perjanjian Kerja yang ditandatangani oleh <strong>PARA PIHAK</strong>.</td>
            </tr>
            @else
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;"><strong>PIHAK KEDUA</strong> telah melaksanakan pekerjaan {{$peran === 'pcl' ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan'}} {{ $kegiatanText }}2026 pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan Perjanjian Kerja {{ $nomorSpkText }}{{ ($isSensusEkonomi && filled($targetPekerjaanText)) ? ' dengan target pekerjaan yang ditetapkan sebesar ' . $targetPekerjaanText : '' }} selama pendataan 15 Juni 2026 sampai dengan 31 Agustus 2026.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;"><strong>PIHAK KEDUA</strong> telah menyelesaikan pekerjaan {{$peran === 'pcl' ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan'}} {{ $kegiatanText }}2026 pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} berdasarkan hasil pemeriksaan dan evaluasi pekerjaan sebagaimana tercantum dalam lampiran.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;">Berdasarkan angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil pekerjaan {{$peran === 'pcl' ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan'}} {{ $kegiatanText }}2026 pada Badan Pusat Statistik {{ $bast->lokasi_kegiatan }} kepada <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil pekerjaan tersebut yang telah sesuai dengan seharusnya.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;">Hasil pekerjaan {{$peran === 'pcl' ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan'}} {{ $kegiatanText }}2026 sebagaimana dimaksud dalam angka 3, berupa dokumen hasil {{$peran === 'pcl' ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan'}} {{ $kegiatanText }}2026, sejumlah {{ ($isSensusEkonomi && filled($hasilPekerjaanText)) ? $hasilPekerjaanText : '' }}.</td>
            </tr>
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;">{{$peran === 'pcl' ? 'Hasil pekerjaan pendataan lapangan' : 'Pekerjaan pemeriksaan'}} sebagaimana dimaksud pada angka 4 yang memerlukan pemeriksaan lanjutan, akan dilakukan pengecekan, perubahan, dan/atau kunjungan kembali ke lapangan merujuk pada perjanjian yang ditandatangani oleh <strong>PARA PIHAK.</strong></td>
            </tr>
            @if($menggunakan_fasih ?? false)
            @php $nomorPoin++; @endphp
            <tr>
                <td style="width: 22px; border: none; vertical-align: top; padding: 3px 5px 6px 0;">{{ $nomorPoin }}.</td>
                <td style="border: none; text-align: justify; padding: 3px 0 6px 0;"><strong>PIHAK KEDUA</strong> menghapus Aplikasi <em>FASIH</em> dan data hasil {{$peran === 'pcl' ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan'}} {{ $kegiatanText }}2026 pada perangkat handphone <strong>PIHAK KEDUA</strong> disaksikan oleh pegawai BPS {{ $bast->lokasi_kegiatan }} yang ditunjuk oleh <strong>PIHAK PERTAMA.</strong></td>
            </tr>
            @endif
            
            @endif
            </table>
        </div>

        <div style="page-break-inside: avoid;">
            <p style="margin-top: 20px; text-align: justify;">
                Demikian Berita Acara ini dibuat dengan sebenarnya dalam rangkap 2 (dua) dan menjadi sah berlaku setelah ditandatangani oleh <strong>PARA PIHAK</strong>.
            </p>
            <div style="height: 10px;"></div>
            <div class="signature-section">
                <div class="signature-box signature-left">
                    <div>PIHAK KEDUA,</div>
                    <div style="height:10px;"></div>
                    <div class="signature-name {{ $signatureNamePihakKedua['collapsed'] ? 'signature-name-collapsed' : '' }}">{!! $signatureNamePihakKedua['display'] !!}</div>
                </div>
                <div class="signature-box signature-right">
                    <div>PIHAK PERTAMA,</div>
                    <div style="height:10px;"></div>
                    <div class="signature-name {{ $signatureNamePihakPertama['collapsed'] ? 'signature-name-collapsed' : '' }}">{!! $signatureNamePihakPertama['display'] !!}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                if ($pageNumber <= 1) {
                    return;
                }

                $topMargin = 56.69; // 2 cm
                $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
                $size = 11;
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