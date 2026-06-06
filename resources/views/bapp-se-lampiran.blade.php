@php
    $terminRoman = $termin_roman ?? 'I';
    $terminNumber = $termin_number ?? 1;
    $persentase = $persentase ?? 40;
    $isPetugasLapangan = ($jenis_pihak_kedua ?? 'petugas_lapangan') === 'petugas_lapangan';
    $isUsahaBesar = $is_usaha_besar ?? false;
    $namaKabkota = $nama_kabkota ?? '…………..';
    $nomorBapp = $nomor_bapp ?? '__________________';
    $nilaiPerjanjian = $nilai_perjanjian ?? 0;
    $terbilangNilai = $nilaiPerjanjian > 0
        ? ucfirst(trim(terbilang((int) $nilaiPerjanjian))) . ' Rupiah'
        : '……………………………………………………';
    $nilaiFormatted = $nilaiPerjanjian > 0
        ? 'Rp ' . number_format((float) $nilaiPerjanjian, 0, ',', '.') . ',00'
        : 'Rp …………, 00';

    $targetSlsFormatted = $target_sls !== null ? number_format((int)$target_sls, 0, ',', '.') : '(jumlah)';
    $realisasiSlsFormatted = $realisasi_sls !== null ? number_format((int)$realisasi_sls, 0, ',', '.') : '…';

    // Build volume text for lampiran table
    if ($isUsahaBesar) {
        $targetUsahaBesar = $target_unit_sampel['usaha_besar'] ?? null;
        $volumeText = ($targetUsahaBesar ? number_format((int)$targetUsahaBesar, 0, ',', '.') : '…') . ' usaha besar';
        $realisasiText = 'Telah mencapai target pekerjaan sebesar ' . $realisasiSlsFormatted . ' usaha besar';
        $satuanText = 'usaha besar';
    } else {
        $targetUnits = $target_unit_sampel ?? [];
        $volumeParts = [$targetSlsFormatted . ' SLS/Sub-SLS'];
        $muatanParts = [];
        foreach ($targetUnits as $unitNama => $count) {
            if ($count > 0) {
                $muatanParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
            }
        }
        if (!empty($muatanParts)) {
            $volumeParts[] = '(' . implode('/', $muatanParts) . ')';
        }
        $volumeText = implode(' dan/atau ', $volumeParts);

        $realisasiUnits = $realisasi_unit_sampel ?? [];
        $rParts = [];
        foreach ($realisasiUnits as $unitNama => $count) {
            if ($count > 0) {
                $rParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
            }
        }
        $realisasiUnitText = !empty($rParts) ? ' dan/atau ' . implode('/', $rParts) : '';
        $realisasiText = 'Telah mencapai target pekerjaan sebesar ' . $realisasiSlsFormatted . ' SLS/Sub-SLS' . $realisasiUnitText;
        $satuanText = null;
    }

    $fasihScreenshotPath = $fasih_screenshot_path ?? null;

    $judulJenisOfficer = $isPetugasLapangan ? 'PETUGAS LAPANGAN' : 'PEMERIKSA LAPANGAN';
@endphp

<div class="page-break"></div>

{{-- LAMPIRAN: Section I - Table --}}
<div class="page-number">-3-</div>

<div class="lampiran-header">
    LAMPIRAN<br>
    BERITA ACARA PEMERIKSAAN PEKERJAAN TERMIN {{ $terminRoman }}<br>
    {{ $judulJenisOfficer }} SENSUS EKONOMI 2026<br>
    PADA BADAN PUSAT STATISTIK KABUPATEN/KOTA {{ strtoupper($namaKabkota) }}<br>
    NOMOR: {{ $nomorBapp }}
</div>

<div class="section-title">I.&nbsp;&nbsp;&nbsp;DAFTAR URAIAN PEKERJAAN, WAKTU PENYELESAIAN, TARGET PEKERJAAN, REALISASI PEKERJAAN DAN NILAI PERJANJIAN</div>

@if($isUsahaBesar)
{{-- Usaha Besar variant: has SATUAN column --}}
<table class="data-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:30%;">Uraian Pekerjaan</th>
            <th rowspan="2" style="width:14%;">Waktu<br>Penyelesaian</th>
            <th colspan="3">Target Pekerjaan</th>
            <th rowspan="2" style="width:20%;">Realisasi Pekerjaan</th>
            <th rowspan="2" style="width:14%;">Nilai Perjanjian</th>
        </tr>
        <tr>
            <th style="width:8%;">Presentase</th>
            <th style="width:8%;">Volume</th>
            <th style="width:10%;">SATUAN</th>
        </tr>
        <tr>
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
        <tr>
            <td class="left">
                @if(!$isPetugasLapangan)
                <ol style="margin:0; padding-left:16px;">
                    <li>Melakukan pemeriksaan hasil pendataan Petugas Lapangan usaha besar Sensus Ekonomi 2026 termin {{ $terminRoman }}</li>
                    <li>Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan usaha besar Sensus Ekonomi 2026</li>
                </ol>
                @else
                Melakukan pendataan lapangan usaha besar Sensus Ekonomi 2026 termin {{ $terminRoman }}
                @endif
            </td>
            <td class="center">Minimal 1 bulan</td>
            <td class="center">{{ $persentase }}%</td>
            <td class="center">…</td>
            <td class="center">usaha besar</td>
            <td class="left">{{ $realisasiText }}</td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
        <tr>
            <td colspan="{{ $isUsahaBesar ? 6 : 5 }}" class="left" style="font-style:italic;">
                Terbilang: {{ $terbilangNilai }}
            </td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
    </tbody>
</table>
@else
{{-- Regular variant: SLS/Sub-SLS + keluarga/usaha --}}
<table class="data-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:35%;">Uraian Pekerjaan</th>
            <th rowspan="2" style="width:14%;">Waktu<br>Pekerjaan</th>
            <th colspan="2">Target Pekerjaan</th>
            <th rowspan="2" style="width:22%;">Realisasi Pekerjaan</th>
            <th rowspan="2" style="width:15%;">Nilai Perjanjian</th>
        </tr>
        <tr>
            <th style="width:9%;">Presentase</th>
            <th style="width:14%;">Volume</th>
        </tr>
        <tr>
            <th>(1)</th>
            <th>(2)</th>
            <th>(3)</th>
            <th>(4)</th>
            <th>(5)</th>
            <th>(6)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="left">
                @if(!$isPetugasLapangan)
                <ol style="margin:0; padding-left:16px;">
                    <li>Melakukan pemeriksaan hasil pendataan Petugas Lapangan <em>door to door</em> Sensus Ekonomi 2026 termin {{ $terminRoman }}</li>
                    <li>Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan <em>door to door</em> Sensus Ekonomi 2026</li>
                </ol>
                @else
                Melakukan pendataan lapangan <em>door to door</em> Sensus Ekonomi 2026 termin {{ $terminRoman }}
                @endif
            </td>
            <td class="center">Minimal 1 bulan</td>
            <td class="center">{{ $persentase }}%</td>
            <td class="left" style="font-size:10pt;">
                <em>{{ $targetSlsFormatted }}</em> SLS/Sub-SLS
                @if(!empty($target_unit_sampel))
                @php
                    $unitParts = [];
                    foreach ($target_unit_sampel as $unitNama => $count) {
                        if ($count > 0) {
                            $unitParts[] = number_format((int)$count, 0, ',', '.') . ' ' . $unitNama;
                        }
                    }
                @endphp
                @if(!empty($unitParts))
                <br>dan/atau (<em>{{ implode('/', $unitParts) }}</em>)
                @endif
                @endif
            </td>
            <td class="left">{{ $realisasiText }}</td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
        <tr>
            <td colspan="5" class="left" style="font-style:italic;">
                Terbilang: {{ $terbilangNilai }}
            </td>
            <td class="right">{{ $nilaiFormatted }}</td>
        </tr>
    </tbody>
</table>
@endif

<div class="page-break"></div>

{{-- LAMPIRAN: Section II - Screenshot --}}
<div class="page-number">-4-</div>

<div class="lampiran-header">
    LAMPIRAN<br>
    BERITA ACARA PEMERIKSAAN PEKERJAAN TERMIN {{ $terminRoman }}<br>
    {{ $judulJenisOfficer }} SENSUS EKONOMI 2026<br>
    PADA BADAN PUSAT STATISTIK KABUPATEN/KOTA {{ strtoupper($namaKabkota) }}<br>
    NOMOR: {{ $nomorBapp }}
</div>

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
        <td class="signature-box" style="font-weight:bold; text-align:center; width:50%;">
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
<table class="signature-row" style="margin-top:20px;">
    <tr>
        <td class="signature-box" style="font-weight:bold; text-align:center; width:100%;">
            <div>Menyetujui,</div>
            <div>Pejabat Pembuat Komitmen</div>
            <div class="signature-space"></div>
            <div>{{ strtoupper($namaPpk) }}</div>
        </td>
    </tr>
</table>
