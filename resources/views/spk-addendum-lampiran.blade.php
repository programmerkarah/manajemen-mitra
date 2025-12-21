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
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        .lampiran-header {
            margin-bottom: 20px;
        }

        .lampiran-title {
            text-align: left;
            margin-left: 60%;
            line-height: 1.4;
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
            font-size: 9pt;
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
            Lampiran<br>
            {{ $addendumLabel }} PERJANJIAN KERJA PETUGAS LAPANGAN BULAN {{ strtoupper($bulan_label) }} {{ $tahun }}<br>
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
            @php $totalAllHonor = 0; $rowNumber = 1; @endphp
            @foreach ($kegiatan_list as $kegiatan)
                @php
                    // Untuk setiap kegiatan, bisa punya 2 baris: pencacahan dan listing (jika ada)
                    $uraianTugasPencacahan = $kegiatan['nama_kegiatan'] . ' - ' . $kegiatan['peran_label'];
                    
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
            @if($volumePencacahan > 0)
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
            @if($volumeListing > 0)
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
