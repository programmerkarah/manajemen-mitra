<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $nomor_bapp ?? 'BAPP' }} - Berita Acara Pemeriksaan Pekerjaan Termin {{ $termin_roman ?? 'I' }}</title>
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
            line-height: 1.3;
            color: #000;
            margin: 1.5cm 2cm 1cm 2cm;
            padding: 0;
        }

        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .nomor {
            text-align: center;
            margin-bottom: 16px;
        }

        .content {
            text-align: justify;
            margin-bottom: 10px;
        }

        .content-intro {
            text-align: justify;
            margin-bottom: 10px;
        }

        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .parties-table td {
            vertical-align: top;
            padding: 1px 0;
        }

        .parties-table td.label {
            width: 140px;
            white-space: nowrap;
        }

        .parties-table td.colon {
            width: 12px;
            text-align: center;
        }

        .parties-table td.value {
            width: auto;
        }

        .numbered-list {
            margin: 10px 0;
        }

        .numbered-item {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            text-align: justify;
        }

        .numbered-item .num {
            display: table-cell;
            width: 24px;
            vertical-align: top;
        }

        .numbered-item .text {
            display: table-cell;
            vertical-align: top;
        }

        .closing {
            text-align: justify;
            margin-top: 10px;
            margin-bottom: 24px;
        }

        .signature-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-row-ppk {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-box {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }

        .signature-box-ppk {
            width: 100%;
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }

        .signature-space {
            height: 80px;
        }

        .signature-name {
            display: inline-block;
            min-width: 180px;
            border-top: 1px solid #000;
            padding-top: 2px;
        }

        .page-number {
            text-align: center;
            margin-bottom: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        /* LAMPIRAN styles */
        .lampiran-header {
            text-align: center;
            margin-bottom: 14px;
            line-height: 1.4;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
        }

        table.data-table td.center {
            text-align: center;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table td.left {
            text-align: left;
        }

        .section-title {
            font-weight: bold;
            margin: 12px 0 6px;
        }

        .screenshot-area {
            margin: 10px 0;
        }

        .screenshot-image {
            display: block;
            max-width: 100%;
            max-height: 280px;
            margin: 6px auto 0;
            object-fit: contain;
        }
    </style>
</head>
<body>
@php
    $terminRoman = $termin_roman ?? 'I';
    $terminNumber = $termin_number ?? 1;
    $persentase = $persentase ?? 40;
    $isPetugasLapangan = ($jenis_pihak_kedua ?? 'petugas_lapangan') === 'petugas_lapangan';
    $isUsahaBesar = ($is_usaha_besar ?? false);
    $namaPihakKedua = $nama_petugas ?? '-';
    $nikPihakKedua = $nik_petugas ?? '-';
    $jabatanPihakKedua = $isPetugasLapangan
        ? 'Petugas Lapangan Sensus Ekonomi 2026'
        : 'Pemeriksa Lapangan Sensus Ekonomi 2026';
    $kegiatanLabel = 'Sensus Ekonomi 2026';
    $jenisKegiatanLabel = $isPetugasLapangan ? 'Pendataan Lapangan' : 'Pemeriksaan Pendataan Lapangan';

    // Format target muatan text
    $targetMuatanParts = [];
    if ($is_usaha_besar ?? false) {
        $targetUsahaBesar = $target_unit_sampel['usaha_besar'] ?? null;
        if ($targetUsahaBesar) {
            $targetMuatanParts[] = number_format((int)$targetUsahaBesar, 0, ',', '.') . ' usaha besar';
        }
    } else {
        $targetUnits = $target_unit_sampel ?? [];
        $unitParts = [];
        foreach ($targetUnits as $unitNama => $count) {
            if ($count > 0) {
                $unitParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
            }
        }
        if (!empty($unitParts)) {
            $targetMuatanParts[] = implode('/', $unitParts);
        }
    }
    $targetMuatanText = !empty($targetMuatanParts) ? implode(' dan ', $targetMuatanParts) : null;

    // Format realisasi muatan text
    $realisasiMuatanParts = [];
    if ($is_usaha_besar ?? false) {
        $realisasiUsahaBesar = $realisasi_unit_sampel['usaha_besar'] ?? null;
        if ($realisasiUsahaBesar) {
            $realisasiMuatanParts[] = number_format((int)$realisasiUsahaBesar, 0, ',', '.') . ' usaha besar';
        }
    } else {
        $realisasiUnits = $realisasi_unit_sampel ?? [];
        $rParts = [];
        foreach ($realisasiUnits as $unitNama => $count) {
            if ($count > 0) {
                $rParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
            }
        }
        if (!empty($rParts)) {
            $realisasiMuatanParts[] = implode('/', $rParts);
        }
    }
    $realisasiMuatanText = !empty($realisasiMuatanParts) ? implode(' dan ', $realisasiMuatanParts) : null;

    $targetSlsFormatted = $target_sls !== null ? number_format((int)$target_sls, 0, ',', '.') : '…';
    $realisasiSlsFormatted = $realisasi_sls !== null ? number_format((int)$realisasi_sls, 0, ',', '.') : '…';

    // Build point 1 target phrase
    $point1TargetPhrase = $targetSlsFormatted . ' SLS/Sub-SLS';
    if ($targetMuatanText) {
        $point1TargetPhrase .= ' dan/atau ' . $targetMuatanText;
    }

    // Build point 4 realisasi phrase
    $point4RealisasiPhrase = $realisasiSlsFormatted . ' SLS/Sub-SLS';
    if ($realisasiMuatanText) {
        $point4RealisasiPhrase .= ' dan/atau ' . $realisasiMuatanText;
    }

    $nomorBapp = $nomor_bapp ?? ('……/BAPP-' . $terminRoman . '-SE2026/…/…/2026');
    $tanggalFormatted = isset($tanggal_bapp) && $tanggal_bapp
        ? \Carbon\Carbon::parse($tanggal_bapp)->locale('id')->isoFormat('dddd, D MMMM YYYY')
        : '……, …… ………………, dua ribu dua puluh enam';

    $hariLabel = isset($tanggal_bapp) && $tanggal_bapp
        ? \Carbon\Carbon::parse($tanggal_bapp)->locale('id')->isoFormat('dddd')
        : '……';
    $tanggalLabel = isset($tanggal_bapp) && $tanggal_bapp
        ? \Carbon\Carbon::parse($tanggal_bapp)->locale('id')->isoFormat('D')
        : '……';
    $tanggalLabelTerbilang = isset($tanggal_bapp) && $tanggal_bapp
        ? ucfirst(trim(terbilang((int) \Carbon\Carbon::parse($tanggal_bapp)->format('j'))))
        : '……';
    $bulanLabelPanjang = isset($tanggal_bapp) && $tanggal_bapp
        ? \Carbon\Carbon::parse($tanggal_bapp)->locale('id')->isoFormat('MMMM')
        : '………………';
    $tahunLabel = isset($tanggal_bapp) && $tanggal_bapp
        ? \Carbon\Carbon::parse($tanggal_bapp)->format('Y')
        : '2026';
    $tahunTerbilangText = 'dua ribu dua puluh enam';

    $namaKabkota = $nama_kabkota ?? '…';
    $namaPpk = $nama_ppk ?? '…………………………………';
    $nipPpk = $nip_ppk ?? '…………………………………';
    $jabatanPpk = $jabatan_ppk ?? 'PPK BPS Kabupaten/Kota ' . $namaKabkota;
    $namaKetuaTim = $nama_ketua_tim ?? '…………………………………';
    $nipKetuaTim = $nip_ketua_tim ?? '…………………………………';
    $nomorSpk = $nomor_spk ?? '…';
@endphp

{{-- HALAMAN 1: Dokumen Utama --}}

<div class="header">
    BERITA ACARA PEMERIKSAAN HASIL PEKERJAAN TERMIN {{ $terminRoman }}<br>
    {{ strtoupper($isPetugasLapangan ? 'PETUGAS LAPANGAN' : 'PEMERIKSA LAPANGAN') }}<br>
    SENSUS EKONOMI 2026
</div>

<div class="nomor">Nomor: {{ $nomorBapp }}</div>

<div class="content-intro">
    Pada hari ini {{ $hariLabel }}, tanggal {{ $tanggalLabelTerbilang }}, bulan {{ $bulanLabelPanjang }},
    tahun {{ $tahunTerbilangText }} ({{ $tanggalLabel }}-{{ isset($tanggal_bapp) && $tanggal_bapp ? \Carbon\Carbon::parse($tanggal_bapp)->format('m') : '…' }}-{{ $tahunLabel }}),
    berdasarkan Perjanjian Kerja pada Badan Pusat Statistik {{ $namaKabkota }}
    Nomor: {{ $nomorSpk }},
    bertempat di {{$namaKabkota}}, kami yang bertanda tangan di bawah ini:
</div>

<div style="margin-bottom: 10px;">
    {{-- PIHAK PERTAMA: Ketua Tim --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:4px;">
        <tr>
            <td style="width:18px; vertical-align:top; padding:0;">1.</td>
            <td style="padding:0; vertical-align:top;">
                <table class="parties-table">
                    <tr>
                        <td class="label">Nama</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $namaKetuaTim }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIP</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $nipKetuaTim }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td class="value">Ketua Tim Pelaksana Sensus Ekonomi 2026 BPS {{ $namaKabkota }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="margin-left:18px; margin-bottom:4px;">dalam hal ini mewakili:</div>
    <table class="parties-table" style="margin-left:18px; width:calc(100% - 18px);">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value">{{ $namaPpk }}</td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td class="colon">:</td>
            <td class="value">{{ $nipPpk }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td class="value">PPK BPS Kabupaten/Kota {{ $namaKabkota }},</td>
        </tr>
    </table>
    <div style="margin-left:18px; margin-bottom:8px;">selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.</div>

    {{-- PIHAK KEDUA: Petugas --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:4px;">
        <tr>
            <td style="width:18px; vertical-align:top; padding:0;">2.</td>
            <td style="padding:0; vertical-align:top;">
                <table class="parties-table">
                    <tr>
                        <td class="label">Nama</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $namaPihakKedua }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIK</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $nikPihakKedua }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $jabatanPihakKedua }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="margin-left:18px; margin-bottom:10px;">bertindak untuk dan atas namanya sendiri, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.</div>
</div>

<div class="content">Dengan ini menyatakan:</div>

<div class="numbered-list">
    <div class="numbered-item">
        <div class="num">1.</div>
        <div class="text">
            <strong>PIHAK KEDUA</strong> telah melaksanakan pekerjaan {{ $jenisKegiatanLabel }}
            {{ $kegiatanLabel }} pada Badan Pusat Statistik Kabupaten/Kota {{ $namaKabkota }}
            berdasarkan Perjanjian Kerja Nomor: {{ $nomorSpk }}, sesuai dengan target pekerjaan
            termin {{ $terminRoman }} yang ditetapkan sebesar {{ $point1TargetPhrase }}.
        </div>
    </div>
    <div class="numbered-item">
        <div class="num">2.</div>
        <div class="text">
            <strong>PIHAK KEDUA</strong> telah menyelesaikan pekerjaan termin {{ $terminRoman }}
            {{ $jenisKegiatanLabel }} {{ $kegiatanLabel }} berdasarkan hasil pemeriksaan
            dan evaluasi pekerjaan sebagaimana tercantum dalam lampiran.
        </div>
    </div>
    <div class="numbered-item">
        <div class="num">3.</div>
        <div class="text">
            Berdasarkan angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil
            pekerjaan {{ $jenisKegiatanLabel }} {{ $kegiatanLabel }} kepada
            <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil
            pekerjaan tersebut yang telah sesuai dengan seharusnya;
        </div>
    </div>
    <div class="numbered-item">
        <div class="num">4.</div>
        <div class="text">
            Hasil pekerjaan {{ $jenisKegiatanLabel }} {{ $kegiatanLabel }}
            sebagaimana dimaksud dalam angka 3, berupa dokumen hasil {{ $jenisKegiatanLabel }}
            {{ $kegiatanLabel }}, sejumlah {{ $point4RealisasiPhrase }}.
        </div>
    </div>
    <div class="numbered-item">
        <div class="num">5.</div>
        <div class="text">
            Hasil pekerjaan {{ strtolower($jenisKegiatanLabel) }} sebagaimana dimaksud pada angka 4 yang
            memerlukan pemeriksaan lanjutan, akan dilakukan pengecekan, perubahan, dan/atau kunjungan
            kembali ke lapangan merujuk pada perjanjian yang ditandatangani oleh <strong>PARA PIHAK</strong>.
        </div>
    </div>
</div>

<div class="closing">
    Demikian Berita Acara ini dibuat dengan sebenarnya dalam rangkap 2 (dua) dan menjadi sah berlaku
    setelah ditandatangani oleh <strong>KEDUA BELAH PIHAK</strong>.
</div>

<table class="signature-row">
    <tr>
        <td class="signature-box" style="font-weight:bold;text-align:center; width:50%;">
            <div>PIHAK KEDUA,</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaPihakKedua) }}</div>
        </td>
        <td class="signature-box" style="font-weight:bold; text-align:center; width:50%;">
            <div>PIHAK PERTAMA,</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaKetuaTim) }}</div>
        </td>
    </tr>
</table>
<table class="signature-row-ppk" style="margin-top:20px;">
    <tr>
        <td class="signature-box-ppk" style="font-weight:bold; text-align:center; width:100%;">
            <div>Menyetujui,</div>
            <div>Pejabat Pembuat Komitmen</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaPpk) }}</div>
        </td>
    </tr>
</table>

<script type="text/php">
    if (isset($pdf) && isset($fontMetrics)) {
        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            if ($pageNumber <= 1) {
                return;
            }
            $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
            $size = 10;
            $text = '-' . $pageNumber . '-';
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($canvas->get_width() - $textWidth) / 2;
            $y = 18; // ~0.6cm from top, inside top margin area
            $canvas->text($x, $y, $text, $font, $size);
        });
    }
</script>

</body>
</html>
