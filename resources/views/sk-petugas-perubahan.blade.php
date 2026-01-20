<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ $nomorSk }} - SK Petugas - {{ $kegiatan->nama_kegiatan }}</title>
    <style>
        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("Bookman Old Style.TTF") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ public_path("Bookman Old Style Bold.TTF") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
			font-family: 'Bookman Old Style';
			src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
			font-weight: normal;
			font-style: italic;
		}

        @font-face {
            font-family: 'Arial';
            src: url('{{ public_path("Arial Bold Italic.TTF") }}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }

        @page {
            size: A4;
            margin: 2cm 2.2cm;
            letter-spacing: -0.07em;
        }

        body {
            /* Reset default browser margins which can add unexpected gaps in print/pdf */
            margin: 0;
            padding: 0;
            font-family: 'Bookman Old Style', 'Palatino Linotype', 'Book Antiqua', serif;
            font-size: 12pt;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            /* reduce default bottom gap to avoid extra spacing in export */
            margin: 0 0 6px 0;
            padding: 0;
        }

        .header h3 {
            margin: 0;
            font-weight: bold;
            line-height: 1;
        }

        /* Make images block-level to remove the inline baseline gap below images in printed output */
        .header img {
            display: block;
            margin: 0 auto;
        }

        /* Ensure header spans appear as block lines with no extra margins */
        .header span {
            display: block;
            margin: 0;
            padding: 0;
            line-height: 1;
        }

        .title {
            text-align: center;
            margin: 20px 0;
        }

        .section-container,
        .dictum-container {
            margin: 15px 0;
            display: flex;
        }

        .section-label,
        .dictum-label {
            width: 120px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .section-separator,
        .dictum-separator {
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .section-content,
        .dictum-content {
            flex: 1;
            text-align: justify;
        }

        table.dictum {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            border: none;
            page-break-inside: auto;
        }

        table.dictum tr {
            page-break-inside: auto;
            page-break-after: auto;
        }

        table.dictum td {
            border: none;
            padding: 0;
            vertical-align: top;
            page-break-inside: auto;
        }

        table.dictum td:first-child {
            width: 120px;
            font-weight: bold;
        }

        table.dictum td:nth-child(2) {
            width: 20px;
            text-align: center;
        }

        table.dictum td:last-child {
            text-align: justify;
        }

        .dasar-hukum-item {
            margin-bottom: 10px;
            page-break-inside: auto;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
            page-break-before: avoid;
            page-break-inside: avoid;
        }

        .signature-content {
            display: inline-block;
            text-align: center;
        }

        .table-with-signature {
            page-break-inside: avoid;
        }

        .keep-with-signature {
            page-break-after: avoid !important;
        }

        .tembusan {
            margin-top: 40px;
        }

        .tembusan ol {
            margin: 5px 0;
            padding-left: 20px;
        }

        .lampiran-header {
            margin-bottom: 5px;
            font-size: small;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
        }

        .title {
            text-align: center;
            margin: 5px 0;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
        }

        table.petugas {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0 10px 0;
            font-size: 11pt;
            table-layout: fixed;
            page-break-before: avoid !important;
        }

        table.petugas th,
        table.petugas td {
            border-left: none;
            border-right: none;
            padding: 4px 8px;
        }

        /* thead will repeat on each page */
        table.petugas thead {
            display: table-header-group;
            page-break-after: avoid !important;
        }

        /* Keep first 3-5 rows on first page */
        table.petugas tbody tr.first-page-row {
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
        }

        /* First header row - double top border, single bottom */
        table.petugas thead tr:first-child th {
            border-top: 3px double black;
            border-bottom: 1px solid black;
            font-weight: bold;
            text-align: center;
        }

        /* Second header row - single top and bottom */
        table.petugas thead tr:nth-child(2) th {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            font-weight: bold;
            text-align: center;
        }

        /* Body rows - single top and bottom per row */
        table.petugas tbody tr td {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            vertical-align: top;
        }

        /* Prevent breaking rows with rowspan */
        table.petugas tbody tr td[rowspan] {
            page-break-inside: avoid;
        }

        /* Keep rows of same petugas together */
        table.petugas tbody tr.petugas-start {
            page-break-after: avoid !important;
        }

        table.petugas tbody tr.petugas-middle {
            page-break-before: avoid !important;
            page-break-after: avoid !important;
        }

        table.petugas tbody tr.petugas-end {
            page-break-before: avoid !important;
        }

        /* Keep last 1-2 rows with signature */
        table.petugas tbody tr.keep-with-signature,
        table.petugas tbody tr.keep-with-signature-last-2 {
            page-break-after: avoid !important;
        }

        /* Signature must stay with last rows */
        .signature {
            page-break-before: avoid !important;
            page-break-inside: avoid !important;
        }

        .page-break {
            page-break-after: always;
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
    </style>
</head>

<body>
    @php
        // Check if nama_kegiatan already contains a 4-digit year
        $namaKegiatanContainsYear = preg_match('/\b(19|20)\d{2}\b/', $kegiatan->nama_kegiatan);
        $namaKegiatanWithYear = $namaKegiatanContainsYear 
            ? $kegiatan->nama_kegiatan 
            : $kegiatan->nama_kegiatan . ' ' . $kegiatan->tahun_anggaran;
    @endphp
    <!-- Halaman Pertama: SK -->
    <div class="header" style="margin-bottom: 15px;">
        <div style="text-align: center; margin-bottom: 0px;">
            <img src="{{ public_path('favicon.svg') }}" alt="Logo BPS" style="width: 140px; height: auto; margin: -15px 0;">
            <h3 style="font-family: 'Arial', sans-serif; font-weight: bold; font-style: italic;">BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</h3>
        </div>
        <span style="margin-top: 15px;">{{ $kategoriKeputusan }} KEPALA BADAN PUSAT STATISTIK</span>
        <span>KOTA SAWAHLUNTO</span><br>
        <span>NOMOR {{ $nomorSk }} TAHUN {{ $tahunSk }}</span>
    </div>

    <div class="title">TENTANG</div>

    <div class="title">
        @if($revisionNumber > 0)
        @php
        $revisionText = '';
        if ($revisionNumber == 1) {
        $revisionText = 'PERUBAHAN ATAS';
        } else {
        $numberWords = ['', '', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH'];
        $revisionText = 'PERUBAHAN ' . ($numberWords[$revisionNumber] ?? $revisionNumber) . ' ATAS';
        }
        @endphp
        {{ $revisionText }} KEPUTUSAN KEPALA BADAN PUSAT STATISTIK
        KOTA SAWAHLUNTO NOMOR {{ $firstSkNumber }}
        @if($firstSkYear)
        TAHUN {{ $firstSkYear }}
        @endif
        TENTANG PETUGAS {{ strtoupper($namaKegiatanWithYear) }}
        BADAN PUSAT STATISTIK KOTA SAWAHLUNTO
        TAHUN ANGGARAN {{ $periode->tahun }}
        @else
        PETUGAS {{ strtoupper($namaKegiatanWithYear) }} <br>
        BADAN PUSAT STATISTIK KOTA SAWAHLUNTO <br>
        TAHUN ANGGARAN {{ $periode->tahun }}
        @endif
    </div>

    <div style="margin-top: 30px; text-align: center; ">
        KEPALA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO,
    </div>

    <table style="width: 100%; margin: 15px 0; border: none;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 120px; font-weight: bold; vertical-align: top; border: none; padding: 0;">Menimbang</td>
            <td style="width: 20px; text-align: center; vertical-align: top; border: none; padding: 0;">:</td>
            <td style="text-align: justify; vertical-align: top; border: none; padding: 0;">Bahwa untuk kelancaran pelaksanaan kegiatan Badan Pusat Statistik Tahun {{ $periode->tahun }} di wilayah Kota Sawahlunto, maka perlu menetapkan Petugas {{ $namaKegiatanWithYear }} Badan Pusat Statistik Kota Sawahlunto Tahun Anggaran {{ $periode->tahun }}.</td>
        </tr>
    </table>

    @foreach($dasarHukum as $index => $dh)
    <table style="width: 100%; margin: 0 0 10px 0; border: none;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 120px; font-weight: bold; vertical-align: top; border: none; padding: 0;">@if($index === 0)Mengingat @endif</td>
            <td style="width: 20px; text-align: center; vertical-align: top; border: none; padding: 0;">@if($index === 0):@endif</td>
            <td style="text-align: justify; vertical-align: top; border: none; padding: 0;">
                {{ $index + 1 }}. {{ $dh->nama_lengkap }} tentang {{ $dh->tentang }}@if($dh->lembaran), {{ $dh->lembaran }}@endif;
            </td>
        </tr>
    </table>
    @endforeach

    <div style="text-align: center; font-weight: bold; margin: 20px 0;">
        MEMUTUSKAN :
    </div>
    

    <table class="dictum">
        <tr>
            <td>Menetapkan</td>
            <td>:</td>
            <td>
                @if($revisionNumber > 0)
                @php
                $revisionText = '';
                if ($revisionNumber == 1) {
                $revisionText = 'PERUBAHAN ATAS';
                } else {
                $numberWords = ['', '', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH'];
                $revisionText = 'PERUBAHAN ' . ($numberWords[$revisionNumber] ?? $revisionNumber) . ' ATAS';
                }
                @endphp
                {{ strtoupper($kategoriKeputusan) }} KEPALA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO
                TENTANG {{ $revisionText }} KEPUTUSAN KEPALA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO
                NOMOR {{ $firstSkNumber }} TENTANG PETUGAS {{ strtoupper($namaKegiatanWithYear) }}
                BADAN PUSAT STATISTIK KOTA SAWAHLUNTO TAHUN ANGGARAN {{ $periode->tahun }}.
                @else
                {{ strtoupper($kategoriKeputusan) }} KEPALA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO TENTANG
                PETUGAS {{ strtoupper($namaKegiatanWithYear) }} BADAN PUSAT STATISTIK KOTA SAWAHLUNTO TAHUN ANGGARAN {{ $periode->tahun }}.
                @endif
            </td>
        </tr>
    </table>
    @if($revisionNumber > 0)
        @php
        $revisionText = '';
        if ($revisionNumber == 1) {
        $revisionText = 'PERUBAHAN ATAS';
        } else {
        $numberWords = ['', '', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH'];
        $revisionText = 'PERUBAHAN ' . ($numberWords[$revisionNumber] ?? $revisionNumber) . ' ATAS';
        }
        $revisionTextDisplay = ucwords(strtolower($revisionText)) ;
        @endphp
        @endif
    <table class="dictum">
        <tr>
            <td>KESATU</td>
            <td>:</td>
            <td>
                Mengubah Lampiran Keputusan Kepala Badan Pusat Statistik Kota Sawahlunto Nomor {{ $firstSkNumber }} Tahun {{ $firstSkYear }} tentang Petugas {{ $namaKegiatanWithYear }}, dengan 
                @if(count($deletedPetugas) > 0)
                memberhentikan  
                @foreach($deletedPetugas as $index => $nama)
                    {{ $nama, }}@if($index < count($deletedPetugas) - 1), @else, dan @endif
                @endforeach
                @endif
                @if(count($addedPetugas) > 0)
                mengangkat  
                @foreach($addedPetugas as $index => $nama)
                    {{ $nama }}@if($index < count($addedPetugas) - 1), @else, @endif
                @endforeach
                @endif
                @if(count($allCurrentPetugas) > 0)
                sehingga daftar nama dalam lampiran setelah perubahan adalah  
                @foreach($allCurrentPetugas as $index => $nama)
                    @if($index > 0 && $index == count($allCurrentPetugas) - 1)
                        dan {{ $nama }}.
                    @elseif($index == count($allCurrentPetugas) - 1)
                        {{ $nama }}.
                    @else
                        {{ $nama }}, 
                    @endif
                @endforeach
                @endif
            </td>
        </tr>
    </table>
    

    <table class="dictum">
        <tr>
            <td>KEDUA</td>
            <td>:</td>
            <td>
                Bahwa Petugas Survei {{ $namaKegiatanWithYear }} Badan Pusat Statistik Kota Sawahlunto Tahun Anggaran {{ $periode->tahun }} setelah perubahan secara lengkap sebagaimana tercantum dalam Lampiran Keputusan ini.
            </td>
        </tr>
    </table>

    <table class="dictum">
        <tr>
            <td>KETIGA</td>
            <td>:</td>
            <td>
                Keputusan ini mulai berlaku pada tanggal ditetapkan, dengan ketentuan apabila dikemudian hari terdapat
                kekeliruan akan diadakan perbaikan sebagaimana mestinya.
                <br>Petikan Keputusan ini disampaikan kepada yang bersangkutan untuk dipergunakan sebagaimana mestinya.
            </td>
        </tr>
    </table>
<!-- 
    <div class="pasal-section">
        @if($revisionNumber > 0)
        @php
        $revisionText = '';
        if ($revisionNumber == 1) {
        $revisionText = 'PERUBAHAN ATAS';
        } else {
        $numberWords = ['', '', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH'];
        $revisionText = 'PERUBAHAN ' . ($numberWords[$revisionNumber] ?? $revisionNumber) . ' ATAS';
        }
        $revisionTextDisplay = ucwords(strtolower($revisionText)) ;
        @endphp
        @endif
        <p class="pasal-title"><strong>Pasal I</strong></p>
        <p style="text-align: justify;">
            Mengubah Lampiran pada Keputusan Kepala Badan Pusat Statistik Kota Sawahlunto Nomor {{ $firstSkNumber }} Tahun {{ $firstSkYear }} tentang Petugas {{ $namaKegiatanWithYear }}, sebagaimana tercantum dalam Lampiran Keputusan Kepala Badan Pusat Statistik Kota Sawahlunto Nomor {{ $nomorSk }} Tahun {{ $tahunSk }} tentang {{ $revisionTextDisplay}} Keputusan Badan Pusat Statistik Nomor {{ $firstSkNumber }} Tahun {{ $firstSkYear }} ini.
        </p>
    </div>
    <div class="pasal-section">
        @if($revisionNumber > 0)
        @php
        $revisionText = '';
        if ($revisionNumber == 1) {
        $revisionText = 'PERUBAHAN ATAS';
        } else {
        $numberWords = ['', '', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH'];
        $revisionText = 'PERUBAHAN ' . ($numberWords[$revisionNumber] ?? $revisionNumber) . ' ATAS';
        }
        $revisionTextDisplay = ucwords(strtolower($revisionText)) ;
        @endphp
        @endif
        <p class="pasal-title"><strong>Pasal II</strong></p>
        <p style="text-align: justify;">
            Ketentuan-ketentuan lainnya yang tidak diubah dalam Keputusan Kepala Badan Pusat Statistik Kota Sawahlunto Nomor {{ $nomorSk }} Tahun {{ $tahunSk }} tentang {{ $revisionTextDisplay}} Keputusan Kepala Badan Pusat Statistik Kota Sawahlunto Nomor {{ $firstSkNumber }} Tahun {{ $firstSkYear }} dinyatakan tetap berlaku.
        </p>
    </div> -->
    <div class="signature">
        <div class="signature-content">
            <div>Ditetapkan di : Sawahlunto</div>
            <div>Pada tanggal : {{ \Carbon\Carbon::parse($tanggalSk)->locale('id')->translatedFormat('d F Y') }}</div>
            <div style="margin-top: 10px;">
                KUASA PENGGUNA ANGGARAN<br>
                BADAN PUSAT STATISTIKKOTA SAWAHLUNTO,
            </div>
            <div style="margin-top: 80px; font-weight: bold;">
                {{ strtoupper($kepalaBps) }}
            </div>
        </div>
    </div>

    <div style="position: absolute; bottom: -1.5cm; font-size: 8pt; line-height: 0.8; letter-spacing: 0.05em;" class="tembusan">
        <strong>Tembusan :</strong>
        <ol style="margin: 5px 0; padding-left: 20px;">
            <li>Yth. Kepala BPS Provinsi Sumatera Barat di Padang;</li>
            <li>Yth. Kepala Kantor Wilayah III Direktorat Jenderal Perbendaharaan Padang di Padang;</li>
            <li>Yth. Kepala Kantor Pelayanan Perbendaharaan Negara Sijunjung di Muaro Sijunjung.</li>
        </ol>
    </div>

    <div class="page-break"></div>

    <!-- Halaman Kedua: Lampiran -->
    <div class="lampiran-header">
        <div>Lampiran 1. Keputusan Kepala Badan Pusat Statistik Kota Sawahlunto</div>
        <div>Nomor <span style="margin-left: 30px;">: {{ $nomorSk }} Tahun {{ $tahunSk }}</span></div>
        <div>Tanggal <span style="margin-left: 20px;">: {{ \Carbon\Carbon::parse($tanggalSk)->isoFormat('D MMMM Y') }}</span></div>
    </div>

    <div class="title" style="margin: 10px 0;">
        @if($revisionNumber > 0)
        @php
        $revisionText = '';
        if ($revisionNumber == 1) {
        $revisionText = 'PERUBAHAN';
        } else {
        $numberWords = ['', '', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH'];
        $revisionText = 'PERUBAHAN ' . ($numberWords[$revisionNumber] ?? $revisionNumber) . '';
        }
        @endphp
        {{ $revisionText }} PETUGAS {{ strtoupper($namaKegiatanWithYear) }}<br>
        BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
        TAHUN ANGGARAN {{ $periode->tahun }}
        @else
        PETUGAS {{ strtoupper($namaKegiatanWithYear) }}<br>
        BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
        TAHUN ANGGARAN {{ $periode->tahun }}
        @endif
    </div>

    <table class="petugas">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 30%;">Nama/Jabatan</th>
                <th style="width: 25%;">NIP/Golongan</th>
                <th style="width: 20%;">Ditetapkan Sebagai</th>
                <th style="width: 20%;">Biaya Satuan (Rp)</th>
            </tr>
            <tr>
                <th>(1)</th>
                <th>(2)</th>
                <th>(3)</th>
                <th>(4)</th>
                <th>(5)</th>
            </tr>
        </thead>
        <tbody>
            @php
            $counter = 1;
            $globalRowCounter = 0;
            $totalRows = 0;
            foreach($alokasiList as $alok) {
                $totalRows += count($alok->roles);
            }
            @endphp
            @foreach($alokasiList as $alokasiIndex => $alokasi)
            @php
            $roleCount = count($alokasi->roles);
            @endphp
            @foreach($alokasi->roles as $roleIndex => $role)
            @php
            $globalRowCounter++;
            $groupClass = '';
            
            // Keep rows of same petugas together
            if ($roleCount > 1) {
                if ($roleIndex === 0) {
                    $groupClass .= ' petugas-start';
                } elseif ($roleIndex === $roleCount - 1) {
                    $groupClass .= ' petugas-end';
                } else {
                    $groupClass .= ' petugas-middle';
                }
            }
            
            // Keep first 4-5 rows on first page
            if ($globalRowCounter <= 5) {
                $groupClass .= ' first-page-row';
            }
            
            // Keep last 1-2 rows with signature
            if ($globalRowCounter >= $totalRows - 1) {
                $groupClass .= ' keep-with-signature';
            } elseif ($globalRowCounter >= $totalRows - 2) {
                $groupClass .= ' keep-with-signature-last-2';
            }
            @endphp
            <tr class="{{ $groupClass }}">
                @if($roleIndex === 0)
                <td style="text-align: center; vertical-align: top;" rowspan="{{ $roleCount }}">{{ $counter }}.</td>
                <td style="vertical-align: top;" rowspan="{{ $roleCount }}">
                    {{ $alokasi->nama }}/<br>
                    {{ $alokasi->jabatan }}
                </td>
                <td style="text-align: center; vertical-align: top;" rowspan="{{ $roleCount }}">
                    @if($alokasi->nip && $alokasi->nip !== '-'&& $alokasi->golongan !=="Non PNS")
                    {{ $alokasi->nip }}/<br>
                    {{ $alokasi->golongan }}
                    @else
                    Non PNS
                    @endif
                </td>
                @endif
                <td style="vertical-align: top;">{{ $role->peran }}</td>
                <td style="text-align: right; vertical-align: top;">{{ $role->biaya_satuan }}</td>
            </tr>
            @endforeach
            @php $counter++; @endphp
            @endforeach
        </tbody>
    </table>

    <div class="signature" style="margin-top: 60px;">
        <div class="signature-content">
            <div>
                KEPALA BADAN PUSAT STATISTIK<br>
                KOTA SAWAHLUNTO,
            </div>
            <div style="margin-top: 80px; font-weight: bold;">
                {{ strtoupper($kepalaBps) }}
            </div>
        </div>
    </div>
</body>

</html>