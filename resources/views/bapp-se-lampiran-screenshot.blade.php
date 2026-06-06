<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lampiran II - {{ $nomor_bapp ?? 'BAPP' }}</title>
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
            margin: 1.5cm 2cm 1cm 2.5cm;
            padding: 0;
        }

        .page-number {
            text-align: center;
            margin-bottom: 10px;
        }

        .lampiran-header {
            text-align: center;
            margin-bottom: 14px;
            line-height: 1.4;
        }

        .section-title {
            font-weight: bold;
            margin: 12px 0 6px;
        }

        .screenshot-area {
            margin: 10px 0;
            text-align: center;
        }

        .screenshot-image {
            display: block;
            max-width: 100%;
            max-height: 280px;
            margin: 6px auto 0;
            object-fit: contain;
        }

        .signature-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-box {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }

        .signature-space {
            height: 80px;
        }
    </style>
</head>
<body>
@php
    $terminRoman = $termin_roman ?? 'I';
    $isPetugasLapangan = ($jenis_pihak_kedua ?? 'petugas_lapangan') === 'petugas_lapangan';
    $namaKabkota = $nama_kabkota ?? '…………..';
    $nomorBapp = $nomor_bapp ?? '__________________';
    $namaKetuaTim = $nama_ketua_tim ?? '…………………………………';
    $namaPihakKedua = $nama_petugas ?? '-';
    $namaPpk = $nama_ppk ?? '…………………………………';
    $judulJenisOfficer = $isPetugasLapangan ? 'PETUGAS LAPANGAN' : 'PEMERIKSA LAPANGAN';
    $fasihScreenshotPath = $fasih_screenshot_path ?? null;
@endphp


<div class="section-title">II.&nbsp;&nbsp;BUKTI PENCAPAIAN PEKERJAAN</div>

<div class="screenshot-area">
    <div>Screenshoot Aplikasi Fasih</div>
    @if($fasihScreenshotPath && file_exists(storage_path('app/' . ltrim($fasihScreenshotPath, '/'))))
    <img class="screenshot-image" src="{{ storage_path('app/' . ltrim($fasihScreenshotPath, '/')) }}" alt="Screenshot Aplikasi Fasih">
    @elseif($fasihScreenshotPath && file_exists(public_path('storage/' . ltrim($fasihScreenshotPath, '/'))))
    <img class="screenshot-image" src="{{ public_path('storage/' . ltrim($fasihScreenshotPath, '/')) }}" alt="Screenshot Aplikasi Fasih">
    @else
    <div style="height:120px; border:1px dashed #aaa; margin:10px 0; display:flex; align-items:center; justify-content:center; color:#999; text-align:center;">
        [Screenshot Aplikasi Fasih]
    </div>
    @endif
</div>

<table class="signature-row" style="margin-top:28px;">
    <tr>
        <td class="signature-box" style="font-weight:bold; text-align:center;">
            <div>PIHAK KEDUA,</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaPihakKedua) }}</div>
        </td>
        <td class="signature-box" style="font-weight:bold; text-align:center;">
            <div>PIHAK PERTAMA,</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaKetuaTim) }}</div>
        </td>
    </tr>
</table>
<table class="signature-row" style="margin-top:20px;">
    <tr>
        <td style="width:100%; font-weight:bold; text-align:center; border:none; padding:0;">
            <div>Menyetujui,</div>
            <div>Pejabat Pembuat Komitmen</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaPpk) }}</div>
        </td>
    </tr>
</table>

<script type="text/php">
    if (isset($pdf) && isset($fontMetrics)) {
        $pageOffset = {{ (int)($page_number_offset ?? 3) }};
        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageOffset) {
            $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
            $size = 10;
            $text = '-' . ($pageNumber + $pageOffset) . '-';
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($canvas->get_width() - $textWidth) / 2;
            $y = 18; // ~0.6cm from top, inside top margin area
            $canvas->text($x, $y, $text, $font, $size);
        });
    }
</script>

</body>
</html>
