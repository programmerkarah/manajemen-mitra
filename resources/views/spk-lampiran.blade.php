<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $pdfTitle ?? 'Lampiran SPK - ' . $petugas->nama }}</title>
    <style>
        @page {
            size: A4 landscape;
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
            line-height: 1.15;
            color: #000;
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        .lampiran-header {
            margin-bottom: 20px;
        }

        .lampiran-title {
            text-align: left;
            margin-left: 60%;
            line-height: 1.15;
            font-size: 10pt;
        }

        .lampiran-subtitle {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11pt;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
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

        .table-footer {
            text-align: left;
            padding-left: 10px;
        }

        /* Prevent page break between last row and footer */
        tbody tr:last-of-type {
            page-break-after: avoid;
        }

        /* Keep footer row with previous content */
        tbody tr:has(.table-footer) {
            page-break-before: avoid;
            page-break-inside: avoid;
        }

        /* Ensure at least one data row stays with footer */
        tbody tr:nth-last-child(2) {
            page-break-after: avoid;
        }
    </style>
</head>
<body>
    <div class="lampiran-header">
        <div class="lampiran-title">
            @php
                $jenisKegiatan = strtolower($kegiatan->jenis_kegiatan ?? '');
                
                // For SENSUS, use specific titles based on kegiatan name
                if ($jenisKegiatan === 'sensus') {
                    $peran = strtolower($peran ?? ($petugas->jenis_petugas ?? ''));
                    if ($peran === 'pcl' || $peran === 'ppl') {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS LAPANGAN ' . strtoupper($kegiatan->nama_kegiatan);
                    } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS PEMERIKSA LAPANGAN ' . strtoupper($kegiatan->nama_kegiatan);
                    } elseif (str_contains($peran, 'olah')) {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS PENGOLAHAN ' . strtoupper($kegiatan->nama_kegiatan);
                    } elseif (str_contains($peran, 'awas olah') || str_contains($peran, 'pengawas pengolahan')) {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS PENGAWAS PENGOLAHAN ' . strtoupper($kegiatan->nama_kegiatan);
                    } elseif (str_contains($peran, 'koseka')) {
                        $judulSpkLampiran = 'PERJANJIAN KERJA KOSEKA ' . strtoupper($kegiatan->nama_kegiatan);
                    } else {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS LAPANGAN ' . strtoupper($kegiatan->nama_kegiatan);
                    }
                } else {
                    // For SURVEI, use work type
                    $workType = $workType ?? 'lapangan';
                    if ($workType === 'lapangan_pengolahan') {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS LAPANGAN DAN PETUGAS PENGOLAHAN SURVEI';
                    } elseif ($workType === 'pengolahan') {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS PENGOLAHAN SURVEI';
                    } else {
                        $judulSpkLampiran = 'PERJANJIAN KERJA PETUGAS LAPANGAN SURVEI';
                    }
                }
            @endphp
            Lampiran<br>
            {{ strtoupper($judulSpkLampiran) }} BADAN PUSAT STATISTIK KOTA SAWAHLUNTO BULAN {{ strtoupper(bulanIndonesia($periode->bulan))}} TAHUN {{ $periode->tahun }} PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
            NOMOR: {{ $nomorSpk }}
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
            @php $rowNumber = 1; @endphp
            @foreach($uraianTugas as $tugas)
            @if(($tugas['jumlah'] ?? 0) <= 0)
                @continue
            @endif
            <tr>
                <td>{{ $rowNumber++ }}</td>
                <td class="left">{{ $tugas['uraian'] }}</td>
                <td>
                    @if(!empty($tugas['tanggal_mulai']) && !empty($tugas['tanggal_selesai']))
                        {{ \Carbon\Carbon::parse($tugas['tanggal_mulai'])->format('d') }}-{{ \Carbon\Carbon::parse($tugas['tanggal_selesai'])->format('d') }} {{ \Carbon\Carbon::parse($tugas['tanggal_mulai'])->locale('id')->translatedFormat('F Y') }}
                    @else
                        {{-- Fallback ke tanggal bulan jika tidak ada tanggal spesifik --}}
                        {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->format('d') }}-{{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth()->format('d') }} {{ \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->locale('id')->translatedFormat('F Y') }}
                    @endif
                </td>
                <td>{{ $tugas['volume'] }}</td>
                <td>{{ $tugas['satuan'] }}</td>
                <td class="right">{{ number_format($tugas['harga_satuan'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($tugas['jumlah'], 0, ',', '.') }}</td>
                <td>{{ $tugas['kode_coa'] ?? $bebanAnggaran }}</td>
            </tr>
            @endforeach
            <tr>
                <!-- <td colspan="2" class="table-footer"><strong>TOTAL NILAI PERJANJIAN</strong></td> -->
                <td colspan="5" class="table-footer" style="text-align: center;"><strong>Terbilang: {{ terbilang($totalHonor) }} rupiah</strong></td>
                <td colspan="2" class ="table-footer" style="text-align: right;"><strong>Rp {{ number_format($totalHonor, 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $pageNumberOffset = {{ (int) ($pageNumberOffset ?? 0) }};

            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageNumberOffset) {
                $displayPage = $pageNumber + $pageNumberOffset;
                if ($displayPage <= 1) {
                    return;
                }

                $topMargin = 56.69; // 2 cm

                $font = $fontMetrics->get_font('Bookman Old Style', 'normal');
                $size = 10;
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
