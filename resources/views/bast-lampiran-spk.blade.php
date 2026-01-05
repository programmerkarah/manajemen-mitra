<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $nomor_bast ?? ($bast->nomor_bast ?? 'BAST') }} - {{$bast->petugas['nama']}} BAST Petugas Kegiatan Survei </title>
	<style>
		@page {
			size: A4;
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
			line-height: 1;
			color: #000;
			margin: 0.5cm 0.5cm 0 1cm;
		}

		.lampiran-header {
			text-align: justify;
			margin-left: 50%;
			font-size: 9pt;
			margin-bottom: 10px;
		}

		.lampiran-title {
			font-weight: bold;
		}

		.lampiran-subtitle {
			text-align: center;
			font-weight: bold;
			margin: 15px 0;
			font-size: 10pt;
		}

		.lampiran-page {
			background: #fff;
			margin-bottom: 20px;
			page-break-inside: avoid !important;
		}

		.kegiatan-section {
			margin: 20px 0;
			page-break-inside: avoid;
			font-size: 9pt;
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
			text-align: center;
			vertical-align: middle;
			padding: 5px;
			font-size: 9pt;
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

		.pengesahan {
			page-break-inside: avoid !important;
		}

		.signature-row {
			display: table;
			width: 100%;
			page-break-inside: avoid;
		}

		.signature-col {
			display: table-cell;
			width: 50%;
			vertical-align: top;
			text-align: center;
			font-size: 10pt;
			line-height: 1;
			page-break-inside: avoid !important;
		}

		.signature-col div {
			margin-bottom: 3px;
		}

		.signature-name {
			font-weight: bold;
			margin-top: 60px;
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
	$totalLampiran = count($bast->kegiatan_list);
	$nomorLampiran = $totalLampiran > 1 ? ' ' . ($index + 1) : '';

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

	$totalLampiran = count($bast->kegiatan_list);
	$nomorLampiran = $totalLampiran > 1 ? ' ' . ($index + 1) : '';
	@endphp

	<div class="lampiran-page">
		<div class="lampiran-header">
			<div>Lampiran{{ $nomorLampiran }}</div>
			<div>BERITA ACARA SERAH TERIMA HASIL PEKERJAAN
				@if($jenisKegiatan === 'sensus')
				{{ $jenisPetugas }}
				TAHUN {{ $tahunBast }}
				PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}
				@else
				{{ ($jenisPetugas) }}
				BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }} BULAN {{ strtoupper($bulanLabel) }} {{ $tahunBast }} PADA BADAN PUSAT STATISTIK {{ strtoupper($bast->lokasi_kegiatan) }}
			</div>
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
						<th style="width: 5%;" rowspan="3">No</th>
						<th style="width: 60%;" rowspan="3">Uraian Tugas</th>
						<th style="width: 10%;" colspan="3">Volume</th>
						<th style="width: 15%;" rowspan="3">Satuan</th>
						<th style="width: 10%;" rowspan="3">Keterangan</th>
					</tr>
					<tr>
						<th style="width: 5%;" rowspan="2">Target</th>
						<th style="width: 10%;" colspan="2">Realisasi</th>
					</tr>
					<tr>
						<th style="width: 5%;">Berhasil dikunjungi</th>
						<th style="width: 5%;">Non Response</th>
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
					@php
						$nonResponseListing = $kegiatan['non_response_listing'] ?? 0;
						$targetListing = $kegiatan['hasil_listing'] + $nonResponseListing;
					@endphp
					<tr>
						<td>{{ $rowNum++ }}</td>
						<td class="left">{{ $kegiatan['uraian_pekerjaan'] ?? 'Hasil Listing' }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_listing'], 0, ',', '.') }}</td>
						<td class="right">{{ number_format($nonResponseListing, 0, ',', '.') }}</td>
						<td class="right">{{ number_format($targetListing, 0, ',', '.') }}</td>
						<td>{{ $kegiatan['satuan_listing'] ?? 'Dokumen' }}</td>
						<td class="left">-</td>
					</tr>
					@endif

					@if($kegiatan['hasil_pendataan_lapangan'])
					@php
						$nonResponse = $kegiatan['non_response'] ?? 0;
						$target = $kegiatan['hasil_pendataan_lapangan'] + $nonResponse;
					@endphp
					<tr>
						<td>{{ $rowNum++ }}</td>
						<td class="left">{{ $kegiatan['uraian_pekerjaan'] ?? 'Hasil Pendataan Lapangan' }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pendataan_lapangan'], 0, ',', '.') }}</td>
						<td class="right">{{ number_format($nonResponse, 0, ',', '.') }}</td>
						<td class="right">{{ number_format($target, 0, ',', '.') }}</td>
						<td>{{ $kegiatan['satuan_pendataan'] ?? 'Dokumen' }}</td>
						<td class="left">-</td>
					</tr>
					@endif

					@if($kegiatan['hasil_pengolahan'])
					<tr>
						<td>{{ $rowNum++ }}</td>
						<td class="left">{{ $kegiatan['uraian_pekerjaan'] ?? 'Hasil Pengolahan' }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pengolahan'], 0, ',', '.') }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pengolahan'], 0, ',', '.') }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pengolahan'], 0, ',', '.') }}</td>
						<td>{{ $kegiatan['satuan_pengolahan'] ?? 'Dokumen' }}</td>
						<td class="left">-</td>
					</tr>
					@endif
					@if($kegiatan['hasil_pengolahan_listing'])
					<tr>
						<td>{{ $rowNum++ }}</td>
						<td class="left">{{ $kegiatan['uraian_pekerjaan'] ?? 'Hasil Pengolahan' }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pengolahan_listing'], 0, ',', '.') }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pengolahan_listing'], 0, ',', '.') }}</td>
						<td class="right">{{ number_format($kegiatan['hasil_pengolahan_listing'], 0, ',', '.') }}</td>
						<td>{{ $kegiatan['satuan_pengolahan'] ?? 'Dokumen' }}</td>
						<td class="left">-</td>
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
		<div class="pengesahan">
			<div style="margin-bottom: 25px; text-align: justify;">
				<span style="text-align: justify;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{ $kegiatan['nama_kegiatan'] ?? '' }} {{ $tahunBast }} pelaksanaan bulan {{ $bulanLabel }} Tahun {{ $tahunBast ?? '-' }} sesuai dengan keterangan di atas dan ketentuan yang sudah ditetapkan pada Perjanjian Kerja.</span>
			</div>
			<div class="signature-section">
				<div class="signature-row" style="margin-bottom: 20px;">
					<div class="signature-col" style="width: 50%;">&nbsp;</div>
					<div class="signature-col" style="width: 50%;">
						<div>Sawahlunto, {{ $kegiatan['tanggal_selesai_formatted'] ?? '' }}</div>
						<div>Yang melakukan pemeriksaan,</div>
						<div>Penanggung Jawab Teknis</div>
						<div>BPS Kota Sawahlunto</div>
						<div class="signature-name">{{ strtoupper($kegiatan['ketua_tim']['nama'] ?? '') }}</div>
					</div>
				</div>

				<div class="signature-row">
					<div class="signature-col">
						<div>Mengetahui,</div>
						<div>Kepala Badan Pusat Statistik</div>
						<div>Kota Sawahlunto</div>
						<div class="signature-name">{{ strtoupper(trim(preg_replace('/^(Dr\.?|Prof\.?|Drs\.?|Ir\.?|H\.?)\s+/i', '', explode(',', $nama_kepala ?? '')[0]))) }}</div>
					</div>
					<div class="signature-col">
						<div>Menyetujui,</div>
						<div>Pejabat Pembuat Komitmen</div>
						<div>Badan Pusat Statistik Kota Sawahlunto</div>
						<div class="signature-name">{{ strtoupper(trim(preg_replace('/^(Dr\.?|Prof\.?|Drs\.?|Ir\.?|H\.?)\s+/i', '', explode(',', $bast->nama_ppk ?? '')[0]))) }}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	@if($index < count($bast->kegiatan_list) - 1)
		<div style="page-break-after: always;"></div>
		@endif
		@endforeach



</body>

</html>