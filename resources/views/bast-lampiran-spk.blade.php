<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<title>Lampiran BAST per SPK</title>
	<style>
		@page {
			size: A4;
			margin: 10px 10px 10px 10px;
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
			font-size: 10pt;
			line-height: 1.6;
			color: #000;
			margin: 1cm 1.5cm 1cm 1.5cm;
		}
		.lampiran-header {
			text-align: justify;
			margin-left: 50%;
			font-size: 10pt;
			margin-bottom: 10px;
		}
		.lampiran-title {
			font-weight: bold;
		}
		.lampiran-subtitle {
			text-align: center;
			font-weight: bold;
			margin: 15px 0;
			font-size: 11pt;
		}
		.lampiran-page {
			background: #fff;
			margin-bottom: 20px;
			page-break-inside: avoid !important;
		}
		.kegiatan-section {
			margin: 20px 0;
			page-break-inside: avoid;
		}
		.lampiran-table {
			width: 100%;
			border-collapse: collapse;
			margin: 10px 0;
			font-size: 9pt;
		}
		.lampiran-table th,
		.lampiran-table td {
			border: 1px solid #000;
			padding: 6px 8px;
			text-align: center;
			vertical-align: middle;
		}
		.lampiran-table th {
			font-weight: bold;
			background-color: #f0f0f0;
		}
		.lampiran-table td.left {
			text-align: left;
		}
		.lampiran-table td.right {
			text-align: right;
		}
		.signature-section {
			margin-top: 30px;
			page-break-inside: avoid;
		}
		.signature-box {
			width: 48%;
			text-align: center;
			line-height: 1.3;
			font-size: 10pt;
			display: inline-block;
			vertical-align: top;
		}
		.signature-space {
			height: 70px;
			margin: 10px 0 5px 0;
		}
		.signature-name {
			font-weight: bold;
			font-size: 10pt;
		}
		.page-break {
			page-break-after: always;
		}
	</style>
</head>
<body>

@foreach($bast->kegiatan_list as $index => $kegiatan)
@php
$bulanLabel = \Carbon\Carbon::parse($bast->tanggal_bast)->locale('id')->isoFormat('MMMM');
$tahunBast = \Carbon\Carbon::parse($bast->tanggal_bast)->format('Y');
$jenisKegiatan = strtolower($kegiatan['jenis_kegiatan'] ?? '');
$peran = strtolower($kegiatan['peran'] ?? '');
$jenisPetugas = '';

if ($jenisKegiatan === 'sensus') {
    if ($peran === 'pcl' || $peran === 'ppl' || $peran === 'pcl_ppl') {
        $jenisPetugas = 'PETUGAS LAPANGAN (' . strtoupper($kegiatan['nama_kegiatan']) . ')';
    } elseif ($peran === 'pml' || $peran === 'pemeriksa') {
        $jenisPetugas = 'PETUGAS PEMERIKSA LAPANGAN (' . strtoupper($kegiatan['nama_kegiatan']) . ')';
    } elseif (str_contains($peran, 'olah')) {
        $jenisPetugas = 'PETUGAS PENGOLAHAN (' . strtoupper($kegiatan['nama_kegiatan']) . ')';
    } else {
        $jenisPetugas = 'PETUGAS LAPANGAN (' . strtoupper($kegiatan['nama_kegiatan']) . ')';
    }
} else {
    // Untuk survei
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
@endphp

<div class="lampiran-page">
<div class="lampiran-header">
	<div>Lampiran</div>
	<div>BERITA ACARA SERAH TERIMA HASIL PEKERJAAN 
	@if($jenisKegiatan === 'sensus')
	{{ $jenisPetugas }}
	TAHUN {{ $tahunBast }}
	PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}
	@else
	{{ ($jenisPetugas) }}
	BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }} BULAN {{ strtoupper($bulanLabel) }} {{ $tahunBast }} PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}</div>
	@endif
	<div>NOMOR {{ $bast->nomor_bast }}</div>
</div>

<div class="lampiran-subtitle">
	REKAPITULASI PELAKSANAAN KEGIATAN<br>
	{{ strtoupper($kegiatan['nama_kegiatan']) }} {{ $tahunBast }} <br> BULAN {{ strtoupper($bulanLabel) }} {{ $tahunBast }}<br>
	PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}
</div>

<div class="kegiatan-section">
	<table class="lampiran-table">
		<thead>
			<tr>
				<th style="width: 5%;">No</th>
				<th style="width: 40%;">Uraian Tugas</th>
				<th style="width: 15%;">Volume</th>
				<th style="width: 15%;">Satuan</th>
				<th style="width: 25%;">Keterangan</th>
			</tr>
		</thead>
		<tbody>
			@php
			$rowNum = 1;
			$isPendataanRole = in_array($kegiatan['peran'], ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan']);
			$isPengolahanRole = in_array($kegiatan['peran'], ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan']);
			
			// Tentukan tahapan: pemutakhiran (jika ada listing) atau pendataan
			$hasilListing = $kegiatan['hasil_listing'] ?? 0;
			$tahapan = $hasilListing > 0 ? 'Pemutakhiran' : 'Pendataan Lapangan';
			
			// Fungsi untuk mendapatkan label peran yang sesuai dengan tahapan
			$getLabelPeran = function($peran, $tahapan) {
				$labels = [
					'pcl_ppl' => "Petugas {$tahapan}",
					'pml' => "Petugas Pemeriksaan {$tahapan}",
					'pcl' => 'PCL (Pencacah Lapangan)',
					'ppl' => 'PPL (Petugas Pengumpul Lapangan)',
					'lapangan' => 'Petugas Lapangan',
					'pengolahan' => "Petugas Pengolahan {$tahapan}",
					'pengawas_pengolahan' => "Petugas Pemeriksa Pengolahan {$tahapan}",
					'pemeriksa_pengolahan' => "Pemeriksa Pengolahan {$tahapan}",
				];
				return $labels[$peran] ?? ucwords(str_replace('_', ' ', $peran));
			};
			@endphp

			@if($kegiatan['hasil_listing'])
			<tr>
				<td>{{ $rowNum++ }}</td>
				<td class="left">Hasil Listing</td>
				<td class="right">{{ number_format($kegiatan['hasil_listing'], 0, ',', '.') }}</td>
				<td>{{ $kegiatan['satuan_listing'] ?? 'Dokumen' }}</td>
				<td class="left">Peran: {{ $getLabelPeran($kegiatan['peran'], $tahapan) }}</td>
			</tr>
			@endif

			@if($kegiatan['hasil_pendataan_lapangan'])
			<tr>
				<td>{{ $rowNum++ }}</td>
				<td class="left">Hasil Pendataan Lapangan</td>
				<td class="right">{{ number_format($kegiatan['hasil_pendataan_lapangan'], 0, ',', '.') }}</td>
				<td>{{ $kegiatan['satuan_pendataan'] ?? 'Dokumen' }}</td>
				<td class="left">Peran: {{ $getLabelPeran($kegiatan['peran'], $tahapan) }}</td>
			</tr>
			@endif

			@if($kegiatan['hasil_pengolahan'])
			<tr>
				<td>{{ $rowNum++ }}</td>
				<td class="left">Hasil Pengolahan</td>
				<td class="right">{{ number_format($kegiatan['hasil_pengolahan'], 0, ',', '.') }}</td>
				<td>{{ $kegiatan['satuan_pengolahan'] ?? 'Dokumen' }}</td>
				<td class="left">Peran: {{ $getLabelPeran($kegiatan['peran'], $tahapan) }}</td>
			</tr>
			@endif

			@if(!$kegiatan['hasil_listing'] && !$kegiatan['hasil_pendataan_lapangan'] && !$kegiatan['hasil_pengolahan'])
			<tr>
				<td colspan="5" class="left">Tidak ada data hasil pekerjaan</td>
			</tr>
			@endif

			@if($kegiatan['keterangan'])
			<tr>
				<td colspan="5" class="left">
					<strong>Catatan:</strong> {{ $kegiatan['keterangan'] }}
				</td>
			</tr>
			@endif
		</tbody>
	</table>
</div>

</div>
@if($index < count($bast->kegiatan_list) - 1)
<div style="page-break-after: always;"></div>
@endif
@endforeach

<div class="signature-section">
	<div style="text-align: center; margin-bottom: 40px;">
		<strong>Mengesahkan,</strong>
	</div>
	<table style="width: 100%; border: none;">
		<tr style="border: none;">
			<td style="width: 50%; border: none; vertical-align: top;">
				<div class="signature-box">
					<div class="signature-title">Ketua Tim,</div>
					<div class="signature-title" style="font-weight: normal; font-size: 9pt;">Penanggung Jawab Teknis</div>
					<div class="signature-space"></div>
					<div class="signature-name">{{ strtoupper($bast->ketua_tim['nama'] ?? '') }}</div>
				</div>
			</td>
			<td style="width: 50%; border: none; vertical-align: top;">
				<div class="signature-box">
					<div class="signature-title">Pejabat Pembuat Komitmen,</div>
					<div class="signature-title" style="font-weight: normal; font-size: 9pt;">Badan Pusat Statistik Kota Sawahlunto</div>
					<div class="signature-space"></div>
					<div class="signature-name">{{ strtoupper($bast->nama_ppk ?? '') }}</div>
				</div>
			</td>
		</tr>
	</table>
</div>

</body>
</html>
