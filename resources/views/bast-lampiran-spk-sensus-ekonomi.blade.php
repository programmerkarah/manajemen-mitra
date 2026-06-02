<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $nomor_bast ?? ($bast->nomor_bast ?? 'BAST') }} - Lampiran Sensus Ekonomi</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm 1.2cm;
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

        body {
            font-family: 'Bookman Old Style', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.15;
            color: #000;
        }

        .lampiran-header {
            margin: 0 0 14px;
            text-align: center;
            font-size: 11pt;
            line-height: 1.15;
        }

        .page + .page {
            page-break-before: always;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 11pt;
        }

        .subsection-title {
            margin: 4px 0 8px 12px;
            font-size: 11pt;
            line-height: 1.15;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        th.judul-kolom {
            text-align: center;
            font-weight: bold;
        }

        th.subjudul-kolom {
            text-align: center;
            font-size: 11pt;
            font-weight: normal;
        }

        td.left {
            text-align: left;
            vertical-align: top;
        }

        td.center {
            text-align: center;
        }

        td.right {
            text-align: right;
        }

        .muted-red {
            color: #d00;
            font-style: italic;
        }

        .note-cell {
            text-align: center;
            font-style: italic;
        }

        .signature-row {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }

        .signature-heading-cell {
            height: 56px;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }


        .signature-box {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }

        .signature-space {
            height: 88px;
        }

        .signature-name {
            display: inline-block;
            min-width: 180px;
            padding-top: 4px;
        }

        .screenshot-placeholder {
            margin-top: 10px;
            text-align: center;
        }

        .screenshot-image {
            display: block;
            max-width: 100%;
            max-height: 260px;
            margin: 10px auto 0;
            object-fit: contain;
        }

        .evidence-signature-block {
            page-break-inside: avoid;
        }

        .evidence-signature-block .signature-row,
        .evidence-signature-block .signature-row tr,
        .evidence-signature-block .signature-row td {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
@php
    $formatDate = static function ($value): string {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->locale('id')->isoFormat('D MMMM YYYY');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatVolume = static function ($jumlahSls, $jumlahUsaha, $jumlahKeluarga, ?int $fallbackTotal = null): string {
        $segments = [];
        $usahaKeluarga = [];

        if (is_numeric($jumlahSls) && (int) $jumlahSls > 0) {
            $segments[] = number_format((int) $jumlahSls, 0, ',', '.') . ' SLS/sub-SLS';
        }

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

        return '-';
    };

    $waktuPenyelesaian = $formatDate($bast->tanggal_pelaksanaan ?? null) . ' - ' . $formatDate($bast->tanggal_selesai ?? null);
    $targetVolume = $formatVolume(
        $bast->target_jumlah_frame_sampel ?? null,
        $bast->target_muatan_prelist_usaha ?? null,
        $bast->target_muatan_prelist_keluarga ?? null,
        $bast->muatan_prelist ?? null,
    );
    $realisasiVolume = $formatVolume(
        $bast->hasil_jumlah_frame_sampel ?? $bast->target_jumlah_frame_sampel ?? null,
        $bast->hasil_realisasi_usaha ?? null,
        $bast->hasil_realisasi_keluarga ?? null,
        $bast->muatan_input ?? null,
    );

    $resolveUraianItems = static function (array $kegiatan): array {
        $peran = mb_strtolower((string) ($kegiatan['peran'] ?? ''));
        $namaKegiatan = (string) ($kegiatan['nama_kegiatan'] ?? 'Sensus Ekonomi 2026');
        $isSensusEkonomi = str_contains(mb_strtolower($namaKegiatan), 'sensus ekonomi');

        if ($isSensusEkonomi) {
            if ($peran === 'pml') {
                return [
                    'Melakukan pemeriksaan hasil pendataan lapangan door to door Sensus Ekonomi 2026 termin I dan termin II',
                    'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door Sensus Ekonomi 2026',
                ];
            }

            if (in_array($peran, ['pcl_ppl', 'pcl', 'ppl', 'lapangan'], true)) {
                return [
                    'Melakukan pendataan lapangan door to door Sensus Ekonomi 2026 termin I dan termin II',
                ];
            }
        }

        $fallbackUraian = $kegiatan['uraian_pencacahan']
            ?? $kegiatan['uraian_pekerjaan']
            ?? ('Melakukan pendataan lapangan ' . $namaKegiatan);

        return [$fallbackUraian];
    };

    $formatUraianItem = static function (string $item): string {
        return preg_replace('/door to door/i', '<em>$0</em>', e($item));
    };

    $resolveScreenshotAbsolutePath = static function (?string $path): ?string {
        if (blank($path)) {
            return null;
        }

        $absolutePath = public_path(ltrim(str_replace('\\', '/', (string) $path), '/'));

        return file_exists($absolutePath) ? $absolutePath : null;
    };
@endphp

@foreach(($bast->kegiatan_list ?? []) as $index => $kegiatan)
    @php
        $peran = mb_strtolower((string) ($kegiatan['peran'] ?? ''));
        $namaKegiatan = strtoupper((string) ($kegiatan['nama_kegiatan'] ?? 'SENSUS EKONOMI 2026'));
        $isPemeriksaanLapangan = $peran === 'pml';
        $uraianItems = $resolveUraianItems($kegiatan);
        $wilayahKerja = $kegiatan['wilayah_kerja'] ?? [];
        $nilaiPerjanjian = (float) ($kegiatan['nilai_perjanjian'] ?? 0);
        $fasihScreenshotAbsolutePath = $resolveScreenshotAbsolutePath($kegiatan['fasih_screenshot_path'] ?? null);
    @endphp
    <div class="page">
        <div class="lampiran-header">
            <div>LAMPIRAN</div>
            <div>{{ $isPemeriksaanLapangan ? 'BERITA ACARA SERAH TERIMA' : 'BERITA ACARA SERAH TERIMA PEKERJAAN' }}</div>
            <div>{{ $isPemeriksaanLapangan ? 'PETUGAS PEMERIKSAAN LAPANGAN '.$namaKegiatan : 'PETUGAS LAPANGAN '.$namaKegiatan }}</div>
            <div>PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan ?? 'KABUPATEN/KOTA') }}</div>
            <div>NOMOR: {{ $bast->nomor_bast ?? '__________________' }}</div>
        </div>

        <div class="section-title">
            I.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DAFTAR URAIAN PEKERJAAN, WAKTU PENYELESAIAN, TARGET PEKERJAAN, REALISASI PEKERJAAN DAN NILAI PERJANJIAN
        </div>

        <table>
            <colgroup>
                <col style="width: 31%;">
                <col style="width: 13%;">
                <col style="width: 10%;">
                <col style="width: 14%;">
                <col style="width: 17%;">
                <col style="width: 15%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="judul-kolom" rowspan="2" style="width: 31%;">Uraian Pekerjaan</th>
                    <th class="judul-kolom" rowspan="2" style="width: 13%;">Waktu Penyelesaian</th>
                    <th class="judul-kolom" colspan="2">Target Pekerjaan</th>
                    <th class="judul-kolom" rowspan="2" style="width: 17%;">Realisasi Pekerjaan</th>
                    <th class="judul-kolom" rowspan="2" style="width: 15%;">Nilai Perjanjian</th>
                </tr>
                <tr>
                    <th class="judul-kolom">Presentase</th>
                    <th class="judul-kolom">Volume</th>
                </tr>
                <tr>
                    <th class="subjudul-kolom" style="width: 31%;">(1)</th>
                    <th class="subjudul-kolom" style="width: 13%;">(2)</th>
                    <th class="subjudul-kolom" style="width: 10%;">(3)</th>
                    <th class="subjudul-kolom" style="width: 14%;">(4)</th>
                    <th class="subjudul-kolom" style="width: 17%;">(5)</th>
                    <th class="subjudul-kolom" style="width: 15%;">(6)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="left">
                        @if(count($uraianItems) === 1)
                            1. {!! $formatUraianItem($uraianItems[0]) !!}
                        @else
                            @foreach($uraianItems as $itemIndex => $uraianItem)
                                <div>{{ $itemIndex + 1 }}. {!! $formatUraianItem($uraianItem) !!}</div>
                            @endforeach
                        @endif
                    </td>
                    <td class="center">{{ $waktuPenyelesaian }}</td>
                    <td class="center">100%</td>
                    <td class="center">{{ $targetVolume }}</td>
                    <td class="center">Telah mencapai target pekerjaan sebesar {{ $realisasiVolume }}</td>
                    <td class="right">Rp {{ number_format($nilaiPerjanjian, 0, ',', '.') }},00</td>
                </tr>
                <tr>
                    <td colspan="5" class="note-cell">Terbilang: {{ terbilang((int) round($nilaiPerjanjian)) }}</td>
                    <td class="right">Rp {{ number_format($nilaiPerjanjian, 0, ',', '.') }},00</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="page">
        <div class="section-title">
            II.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;BUKTI PENYELESAIAN PEKERJAAN
        </div>

        <div class="subsection-title">A. DAFTAR WILAYAH KERJA</div>

        <table>
            <colgroup>
                <col style="width: 8%;">
                <col style="width: 34%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
                <col style="width: 20%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="judul-kolom" style="width: 8%;">No</th>
                    <th class="judul-kolom" style="width: 24%;">KECAMATAN</th>
                    <th class="judul-kolom" style="width: 24%;">DESA/KELURAHAN</th>
                    <th class="judul-kolom" style="width: 14%;">Jumlah SLS/sub-SLS</th>
                    <th class="judul-kolom" style="width: 20%;">Muatan usaha/Keluarga</th>
                </tr>
                <tr>
                    <th class="subjudul-kolom" style="width: 8%;">(1)</th>
                    <th class="subjudul-kolom" style="width: 24%;">(2)</th>
                    <th class="subjudul-kolom" style="width: 24%;">(3)</th>
                    <th class="subjudul-kolom" style="width: 14%;">(4)</th>
                    <th class="subjudul-kolom" style="width: 20%;">(5)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wilayahKerja as $wilayah)
                    <tr>
                        <td class="center" style="width: 8%;">{{ $wilayah['no'] ?? '-' }}</td>
                        <td class="center" style="width: 24%;">{{ $wilayah['nama_kecamatan'] ?? $wilayah['kecamatan'] ?? '-' }}</td>
                        <td class="center" style="width: 24%;">{{ $wilayah['nama_desa'] ?? $wilayah['desa'] ?? '-' }}</td>
                        <td class="center" style="width: 14%;">{{ $wilayah['jumlah_sls'] ?? '-' }}</td>
                        <td class="center" style="width: 20%;">{{ $wilayah['muatan_label'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center" style="width: 8%;">1.</td>
                        <td class="center" style="width: 24%;">...</td>
                        <td class="center" style="width: 24%;">...</td>
                        <td class="center" style="width: 14%;">...</td>
                        <td class="center" style="width: 20%;">...</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="evidence-signature-block">
            <div style="page-break-inside: avoid;">
                <div class="subsection-title" style="margin-top: 14px; font-weight: normal;">B. Screenshot Aplikasi Fasih</div>
                <div class="screenshot-placeholder">
                    @if($fasihScreenshotAbsolutePath)
                        <img src="{{ $fasihScreenshotAbsolutePath }}" alt="Screenshot Aplikasi Fasih" class="screenshot-image">
                    @else
                        <span class="muted-red">(Screenshot Aplikasi Fasih)</span>
                    @endif
                </div>
            </div>

            <table class="signature-row">
                <tr>
                    <td class="signature-box signature-heading-cell">
                        <div>Mengetahui,</div>
                        <div>Ketua Tim Pelaksana selaku</div>
                        <div>Pemeriksa Pekerjaan,</div>
                    </td>
                    <td class="signature-box signature-heading-cell">
                        <div>PIHAK KEDUA,</div>
                    </td>
                    <td class="signature-box signature-heading-cell">
                        <div>PIHAK PERTAMA,</div>
                    </td>
                </tr>
                <tr>
                    <td class="signature-box"><div class="signature-space"></div></td>
                    <td class="signature-box"><div class="signature-space"></div></td>
                    <td class="signature-box"><div class="signature-space"></div></td>
                </tr>
                <tr>
                    <td class="signature-box"><div class="signature-name">({{ $kegiatan['ketua_tim']['nama'] ?? '-' }})</div></td>
                    <td class="signature-box"><div class="signature-name">({{ $bast->petugas['nama'] ?? '-' }})</div></td>
                    <td class="signature-box"><div class="signature-name">({{ $bast->nama_ppk ?? '-' }})</div></td>
                </tr>
            </table>
        </div>
    </div>
@endforeach

<script type="text/php">
    if (isset($pdf) && isset($fontMetrics)) {
        $pageNumberOffset = {{ (int) ($pageNumberOffset ?? 1) }};

        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageNumberOffset) {
            $displayPage = $pageNumber + $pageNumberOffset;
            if ($displayPage <= 1) {
                return;
            }

            $topMargin = 28.35; // 1 cm
            $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
            $size = 11;
            $text = '-' . $displayPage . '-';
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($canvas->get_width() - $textWidth) / 2;
            $y = $topMargin / 2;

            $canvas->text($x, $y, $text, $font, $size);
        });
    }
</script>
</body>
</html>
