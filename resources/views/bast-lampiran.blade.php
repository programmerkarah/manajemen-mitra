<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<title>Lampiran BAST</title>
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
		.lampiran-page {
			background: #fff;
			margin-bottom: 20px;
			page-break-inside: avoid !important;
		}
		.lampiran-page + .lampiran-page {
			page-break-before: always;
		}
		.lampiran-header {
			text-align: left;
			margin-left: 60%;
			font-size: 8pt;
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
		.lampiran-table {
			width: 100%;
			border-collapse: collapse;
			margin: 15px 0;
			font-size: 9pt;
			page-break-after: avoid !important;
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
			page-break-inside: avoid !important;
			page-break-before: avoid !important;
			width: 100%;
		}
		.closing-with-signature {
			page-break-inside: avoid !important;
			page-break-before: avoid !important;
			min-height: 200px;
		}
		.lampiran-table tbody tr:nth-last-child(-n+3) {
			page-break-inside: avoid !important;
			page-break-after: avoid;
		}
		.header-row {
			font-weight: normal !important;
		}
		.signature-box {
			width: 48%;
			text-align: center;
			line-height: 1.3;
			font-size: 10pt;
			padding: 0;
			display: inline-block;
			vertical-align: top;
		}
		.signature-title {
			margin-bottom: 4px;
			font-size: 10pt;
			line-height: 1.2;
		}
		.signature-space {
			height: 70px;
			width: 100%;
			margin: 10px 0 5px 0;
		}
		.signature-name {
			font-weight: bold;
			font-size: 10pt;
			text-align: center;
			margin: 0;
		}
	</style>
</head>
<body>

@php
$pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
@endphp
@if(collect($petugas)->where(function($p) {
	return isset($p['hasil_listing']) && $p['hasil_listing'] !== null && $p['hasil_listing'] > 0;
})->count() > 0)
<div class="lampiran-page">
	<div class="lampiran-header">
		<div class="lampiran-title">Lampiran</div>
		<div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} {{$tahun}} pelaksanaan Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
	</div>
	<div class="lampiran-subtitle">REKAPITULASI PELAKSANAAN {{ strtoupper($nama_kegiatan) }} {{ $tahun}}<br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }}<br>PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
	<div class="lampiran-subtitle" style="font-size:10pt; text-align:left;">Rekapitulasi Listing</div>
	<table class="lampiran-table">
		<thead>
			<tr>
				<th>No</th>
				<th>Nama Petugas</th>
				<th>Nomor Perjanjian Kerja</th>
				<th>Uraian Tugas</th>
				<th>Volume</th>
				<th>Satuan</th>
				<th>Keterangan</th>
			</tr>
			<tr class="header-row">
				<th>(1)</th>
				<th>(2)</th>
				<th>(3)</th>
				<th>(4)</th>
				<th>(5)</th>
				<th>(6)</th>
				<th>(7)</th>
			</tr>
		</thead>
		<tbody>
			@php 
				$counter = 1;
				$sortedPetugas = collect($petugas)->sortBy('nomor_spk', SORT_NATURAL);
			@endphp
			@foreach($sortedPetugas as $i => $p)
				@if((isset($p['hasil_listing']) && $p['hasil_listing'] !== null && $p['hasil_listing'] > 0))
				<tr>
					<td>{{ $counter++ }}</td>
					<td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
					<td>{{ $p['nomor_spk'] ?? '-' }}</td>
					<td>{{ $instrumen_listing ?? $p['instrumen_listing'] ?? '-' }}</td>
					<td class="right">{{ isset($p['hasil_listing']) && $p['hasil_listing'] !== null ? number_format($p['hasil_listing'],0,',','.') : '-' }}</td>
					<td class="left">{{ $p['satuan_listing'] ?? '-' }}</td>
					<td class="left">{{ $p['catatan'] ?? '-' }}</td>
				</tr>
				@endif
			@endforeach
		</tbody>
	</table>
	<div class="closing-with-signature" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
		<div style="margin-top:24px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>
		@if($menggunakan_fasih)
		<div style="margin-top:12px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menghapus aplikasi FASIH dan data hasil pengumpulan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} pada perangkat <em>handphone</em> yang bersangkutan.</div>
		@endif
		<div class="signature-section" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
			<div class="signature-box">
				<div class="signature-title">Yang melakukan pemeriksaan,</div>
				<div class="signature-title">Penanggung Jawab Teknis</div>
				<div class="signature-title">BPS Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
			</div>
			<div class="signature-box">
				<div class="signature-title">Mengetahui,</div>
				<div class="signature-title">Kepala Badan Pusat Statistik</div>
				<div class="signature-title">Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
			</div>
		</div>
	</div>
</div>
@endif

@php
$pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
@endphp
@if(collect($petugas)->where(function($p) {
	return isset($p['hasil_pendataan_lapangan']) && $p['hasil_pendataan_lapangan'] !== null && $p['hasil_pendataan_lapangan'] > 0;
})->count() > 0)
<div class="lampiran-page">
	<div class="lampiran-header">
		<div class="lampiran-title">Lampiran</div>
		<div class="lampiran-title">Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} {{$tahun}} pelaksanaan Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
	</div>
	<div class="lampiran-subtitle">REKAPITULASI PELAKSANAAN {{ strtoupper($nama_kegiatan) }} {{ $tahun}}<br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }}<br>PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
	<div class="lampiran-subtitle" style="font-size:10pt; text-align:left;">Rekapitulasi Pendataan Lapangan</div>
	<table class="lampiran-table">
		<thead>
			<tr>
				<th>No</th>
				<th>Nama Petugas</th>
				<th>Nomor Perjanjian Kerja</th>
				<th>Uraian Tugas</th>
				<th>Volume</th>
				<th>Satuan</th>
				<th>Keterangan</th>
			</tr>
			<tr class="header-row">
				<th>(1)</th>
				<th>(2)</th>
				<th>(3)</th>
				<th>(4)</th>
				<th>(5)</th>
				<th>(6)</th>
				<th>(7)</th>
			</tr>
		</thead>
		<tbody>
			@php 
				$counter = 1;
				$sortedPetugas = collect($petugas)->sortBy('nomor_spk', SORT_NATURAL);
			@endphp
			@foreach($sortedPetugas as $i => $p)
				@if((isset($p['hasil_pendataan_lapangan']) && $p['hasil_pendataan_lapangan'] !== null && $p['hasil_pendataan_lapangan'] > 0))
				<tr>
					<td>{{ $counter++ }}</td>
					<td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
					<td>{{ $p['nomor_spk'] ?? '-' }}</td>
					<td>{{ $instrumen_pendataan_lapangan ?? $p['instrumen_pendataan_lapangan'] ?? '-' }}</td>
					<td class="right">{{ isset($p['hasil_pendataan_lapangan']) ? number_format($p['hasil_pendataan_lapangan'],0,',','.') : '-' }}</td>
					<td class="left">{{ $p['satuan_pendataan_lapangan'] ?? '-' }}</td>
					<td class="left">{{ $p['catatan'] ?? '-' }}</td>
				</tr>
				@endif
			@endforeach
		</tbody>
	</table>
	<div class="closing-with-signature" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
		<div style="margin-top:24px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>
		@if($menggunakan_fasih)
		<div style="margin-top:12px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menghapus aplikasi FASIH dan data hasil pengumpulan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} pada perangkat <em>handphone</em> yang bersangkutan.</div>
		@endif
		<div class="signature-section" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
			<div class="signature-box">
				<div class="signature-title">Yang melakukan pemeriksaan,</div>
				<div class="signature-title">Penanggung Jawab Teknis</div>
				<div class="signature-title">BPS Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
			</div>
			<div class="signature-box">
				<div class="signature-title">Mengetahui,</div>
				<div class="signature-title">Kepala Badan Pusat Statistik</div>
				<div class="signature-title">Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
			</div>
		</div>
	</div>
</div>
@endif

	{{-- Lampiran Pengolahan Listing --}}
@if(collect($petugas)->where(function($p) {
	return isset($p['hasil_pengolahan_listing']) && $p['hasil_pengolahan_listing'] !== null && $p['hasil_pengolahan_listing'] > 0;
})->count() > 0)
<div class="lampiran-page">
	<div class="lampiran-header">
		<div class="lampiran-title">Lampiran</div>
		<div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pengolahan Listing {{$nama_kegiatan}} {{$tahun}} pelaksanaan Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
	</div>
	<div class="lampiran-subtitle">REKAPITULASI PELAKSANAAN {{ strtoupper($nama_kegiatan) }} {{ $tahun}}<br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }}<br>PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
	<div class="lampiran-subtitle" style="font-size:10pt; text-align:left;">Rekapitulasi Pengolahan Listing</div>
	<table class="lampiran-table">
		<thead>
			<tr>
				<th>No</th>
				<th>Nama Petugas</th>
				<th>Nomor Perjanjian Kerja</th>
				<th>Uraian Tugas</th>
				<th>Volume</th>
				<th>Satuan</th>
				<th>Keterangan</th>
			</tr>
			<tr class="header-row">
				<th>(1)</th>
				<th>(2)</th>
				<th>(3)</th>
				<th>(4)</th>
				<th>(5)</th>
				<th>(6)</th>
				<th>(7)</th>
			</tr>
		</thead>
		<tbody>
			@php 
				$counter = 1;
				$sortedPetugas = collect($petugas)->sortBy('nomor_spk', SORT_NATURAL);
			@endphp
			@foreach($sortedPetugas as $i => $p)
				@if((isset($p['hasil_pengolahan_listing']) && $p['hasil_pengolahan_listing'] !== null && $p['hasil_pengolahan_listing'] > 0))
				<tr>
					<td>{{ $counter++ }}</td>
					<td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
					<td>{{ $p['nomor_spk'] ?? '-' }}</td>
					<td>{{ $instrumen_listing ?? $p['instrumen_listing'] ?? '-' }}</td>
					<td class="right">{{ isset($p['hasil_pengolahan_listing']) ? number_format($p['hasil_pengolahan_listing'],0,',','.') : '-' }}</td>
					<td class="left">{{ $p['satuan_pengolahan_listing'] ?? '-' }}</td>
					<td class="left">{{ $p['catatan'] ?? '-' }}</td>
				</tr>
				@endif
			@endforeach
		</tbody>
	</table>
	<div class="closing-with-signature" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
		<div style="margin-top:24px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>
		@if($menggunakan_fasih)
		<div style="margin-top:12px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menghapus aplikasi FASIH dan data hasil pengumpulan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} pada perangkat <em>handphone</em> yang bersangkutan.</div>
		@endif
		<div class="signature-section" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
			<div class="signature-box">
				<div class="signature-title">Yang melakukan pemeriksaan,</div>
				<div class="signature-title">Penanggung Jawab Teknis</div>
				<div class="signature-title">BPS Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
			</div>
			<div class="signature-box">
				<div class="signature-title">Mengetahui,</div>
				<div class="signature-title">Kepala Badan Pusat Statistik</div>
				<div class="signature-title">Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
			</div>
		</div>
	</div>
</div>
@endif

{{-- Lampiran Pengolahan Lapangan --}}
@php
$pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
@endphp
@if(collect($petugas)->where(function($p) {
	return isset($p['hasil_pengolahan']) && $p['hasil_pengolahan'] !== null && $p['hasil_pengolahan'] > 0;
})->count() > 0)
<div class="lampiran-page">
	<div class="lampiran-header">
		<div class="lampiran-title">Lampiran</div>
		<div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pengolahan Lapangan {{$nama_kegiatan}} {{$tahun}} pelaksanaan Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
	</div>
	<div class="lampiran-subtitle">REKAPITULASI PELAKSANAAN {{ strtoupper($nama_kegiatan) }} {{ $tahun}}<br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }}<br>PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
	<div class="lampiran-subtitle" style="font-size:10pt; text-align:left;">Rekapitulasi Pengolahan Lapangan</div>
	<table class="lampiran-table">
		<thead>
			<tr>
				<th>No</th>
				<th>Nama Petugas</th>
				<th>Nomor Perjanjian Kerja</th>
				<th>Uraian Tugas</th>
				<th>Volume</th>
				<th>Satuan</th>
				<th>Keterangan</th>
			</tr>
			<tr class="header-row">
				<th>(1)</th>
				<th>(2)</th>
				<th>(3)</th>
				<th>(4)</th>
				<th>(5)</th>
				<th>(6)</th>
				<th>(7)</th>
			</tr>
		</thead>
		<tbody>
			@php 
				$counter = 1;
				$sortedPetugas = collect($petugas)->sortBy('nomor_spk', SORT_NATURAL);
			@endphp
			@foreach($sortedPetugas as $i => $p)
				@if((isset($p['hasil_pengolahan']) && $p['hasil_pengolahan'] !== null && $p['hasil_pengolahan'] > 0))
				<tr>
					<td>{{ $counter++ }}</td>
					<td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
					<td>{{ $p['nomor_spk'] ?? '-' }}</td>
					<td>{{ $instrumen_pendataan_lapangan ?? $p['instrumen_pendataan_lapangan'] ?? '-' }}</td>
					<td class="right">{{ isset($p['hasil_pengolahan']) ? number_format($p['hasil_pengolahan'],0,',','.') : '-' }}</td>
					<td class="left">{{ $p['satuan_pengolahan'] ?? '-' }}</td>
					<td class="left">{{ $p['catatan'] ?? '-' }}</td>
				</tr>
				@endif
			@endforeach
		</tbody>
	</table>
	<div class="closing-with-signature" style="page-break-before: avoid !important; page-break-inside: avoid !important;">
		<div style="margin-top:24px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>
		@if($menggunakan_fasih)
		<div style="margin-top:12px; text-align:justify; page-break-before: avoid !important; page-break-inside: avoid !important;">Petugas yang bersangkutan telah menghapus aplikasi FASIH dan data hasil pengumpulan {{$nama_kegiatan}} {{$tahun}} pelaksanaan bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }} pada perangkat <em>handphone</em> yang bersangkutan.</div>
		@endif
		<div class="signature-section">
			<div class="signature-box">
				<div class="signature-title">Yang melakukan pemeriksaan,</div>
				<div class="signature-title">Penanggung Jawab Teknis</div>
				<div class="signature-title">BPS Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
			</div>
			<div class="signature-box">
				<div class="signature-title">Mengetahui,</div>
				<div class="signature-title">Kepala Badan Pusat Statistik</div>
				<div class="signature-title">Kota Sawahlunto</div>
				<div class="signature-space"></div>
				<div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
			</div>
		</div>
	</div>
</div>
@endif
</body>
</html>
