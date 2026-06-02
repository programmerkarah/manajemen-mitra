<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lampiran Addendum SPK - {{ $petugas->nama }}</title>
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
			font-weight: normal;
			font-style: italic;
		}

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
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
            font-size: 11pt;
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
            line-height: 1.15;
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
    @php
        $addendumLabel = match((int)$addendum_number) {
            1 => 'ADDENDUM',
            2 => 'ADDENDUM KEDUA',
            3 => 'ADDENDUM KETIGA',
            4 => 'ADDENDUM KEEMPAT',
            5 => 'ADDENDUM KELIMA',
            default => 'ADDENDUM KE-' . $addendum_number
        };
    @endphp
    <div class="lampiran-header">
        <div class="lampiran-title">
            @php
                    $jenisKegiatan = strtolower($kegiatan->jenis_kegiatan ?? '');
                    $peran = strtolower($peran ?? ($petugas->jenis_petugas ?? ''));
                    $judulSpk = '';
                    if ($jenisKegiatan === 'sensus') {
                        if ($peran === 'pcl' || $peran === 'ppl') {
                            $judulSpk = 'PERJANJIAN KERJA PETUGAS LAPANGAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
                            $judulSpk = 'PERJANJIAN KERJA PETUGAS PEMERIKSA LAPANGAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        } elseif (str_contains($peran, 'olah')) {
                            $judulSpk = 'PERJANJIAN KERJA PETUGAS PENGOLAHAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        } else {
                            $judulSpk = 'PERJANJIAN KERJA PETUGAS LAPANGAN (' . strtoupper($kegiatan->nama_kegiatan) . ')';
                        }
                    } else {
                        $judulSpk = 'PERJANJIAN KERJA PETUGAS LAPANGAN SURVEI';
                    }
                @endphp
            Lampiran<br>
               PERUBAHAN/{{ $addendumLabel }} {{ $judulSpk }} BADAN PUSAT STATISTIK KOTA SAWAHLUNTO BULAN {{ strtoupper($bulan_label) }} {{ $tahun }} PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO NOMOR: {{$nomorSpk}}<br>
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
            @php $totalAllHonor = 0; $rowNumber = 1; @endphp
            @foreach ($kegiatan_list as $kegiatan)
                @php
                    // Tentukan label peran sesuai aturan
                    $periodeType = '';
                    if (!empty($kegiatan['periode_mulai']) && !empty($kegiatan['periode_selesai'])) {
                        $periodeType = (str_contains(strtolower($kegiatan['nama_kegiatan']), 'listing')) ? 'listing' : 'lapangan';
                    }

                    $peranLabelPencacahan = '';
                    if (strtolower($kegiatan['peran']) === 'pcl_ppl') {
                        $peranLabelPencacahan = $periodeType === 'listing' ? 'pemutakhiran' : 'pendataan lapangan';
                    } elseif (strtolower($kegiatan['peran']) === 'pemeriksa') {
                        $peranLabelPencacahan = $periodeType === 'listing' ? 'pemeriksaan pemutakhiran' : 'pemeriksaan pendataan lapangan';
                    } else {
                        $peranLabelPencacahan = strtolower($kegiatan['peran_label']);
                    }

                    $uraianTugasPencacahan = 'Melaksanakan ' . $peranLabelPencacahan . ' pada ' . $kegiatan['nama_kegiatan'];

                    // Gunakan periode dari kegiatan jika ada, fallback ke tanggal SPK
                    if (!empty($kegiatan['periode_mulai']) && !empty($kegiatan['periode_selesai'])) {
                        $jangkaWaktu = \Carbon\Carbon::parse($kegiatan['periode_mulai'])->format('d') . '-' . \Carbon\Carbon::parse($kegiatan['periode_selesai'])->format('d') . ' ' . \Carbon\Carbon::parse($kegiatan['periode_mulai'])->locale('id')->translatedFormat('F Y');
                    } else {
                        $jangkaWaktu = $tanggalSpk->format('d') . '-' . $sampaiTanggal->format('d') . ' ' . $tanggalSpk->locale('id')->translatedFormat('F Y');
                    }

                    $bebanAnggaran = $kegiatan['kode_coa'] ?? '-';

                    // Data Pencacahan
                    $volumePencacahan = $kegiatan['jumlah_satuan'] ?? 0;
                    $totalHonorPencacahan = $kegiatan['total_honor'] ?? 0;
                    $hargaSatuanPencacahan = $volumePencacahan > 0 ? $totalHonorPencacahan / $volumePencacahan : 0;

                    // Data Listing (jika ada)
                    $volumeListing = $kegiatan['jumlah_satuan_listing'] ?? 0;
                    $totalHonorListing = $kegiatan['total_honor_listing'] ?? 0;
                    $hargaSatuanListing = $volumeListing > 0 ? $totalHonorListing / $volumeListing : 0;

                    $totalAllHonor += $totalHonorPencacahan + $totalHonorListing;
                @endphp
            
            {{-- Baris Pencacahan --}}
            @if($volumePencacahan > 0 && $totalHonorPencacahan > 0)
            <tr>
                <td>{{ $rowNumber++ }}</td>
                <td class="left">{{ $uraianTugasPencacahan }}</td>
                <td>{{ $jangkaWaktu }}</td>
                <td>{{ $volumePencacahan }}</td>
                <td>{{ $kegiatan['satuan_kode'] }}</td>
                <td class="right">{{ number_format($hargaSatuanPencacahan, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($totalHonorPencacahan, 0, ',', '.') }}</td>
                <td>{{ $bebanAnggaran }}</td>
            </tr>
            @endif
            
            {{-- Baris Listing (jika ada) --}}
            @if($volumeListing > 0 && $totalHonorListing > 0)
            <tr>
                <td>{{ $rowNumber++ }}</td>
                <td class="left">{{ $uraianTugasPencacahan }} (Listing)</td>
                <td>{{ $jangkaWaktu }}</td>
                <td>{{ $volumeListing }}</td>
                <td>{{ $kegiatan['satuan_kode'] }}</td>
                <td class="right">{{ number_format($hargaSatuanListing, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($totalHonorListing, 0, ',', '.') }}</td>
                <td>{{ $bebanAnggaran }}</td>
            </tr>
            @endif
            @endforeach
            <tr>
                <td colspan="5" class="table-footer" style="text-align: center;"><strong>Terbilang: {{ terbilang($totalAllHonor) }} rupiah</strong></td>
                <td colspan="2" class="table-footer" style="text-align: right;"><strong>Rp {{ number_format($totalAllHonor, 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
