<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Petugas Sensus dan Survei BPS Kota Sawahlunto {{ $currentYear }}</title>
    @include('analisis._pdf-style')
</head>
<body>
    <h1>Analisis Petugas Sensus dan Survei di Lingkungan Badan Pusat Statistik Kota Sawahlunto &mdash; Tahun Anggaran {{ $currentYear }}</h1>
    <p class="meta">Dicetak: {{ $tanggalCetak }} &nbsp;|&nbsp; Total Petugas Aktif: {{ $totalPetugas }}</p>

    {{-- Distribusi Jenis Kelamin --}}
    <h2>Distribusi Jenis Kelamin</h2>
    <table style="width:auto">
        <thead>
            <tr>
                <th>Jenis Kelamin</th>
                <th style="width:80px">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribusiJenisKelamin as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td class="text-center">{{ $item['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Distribusi Kecamatan --}}
    <h2>Distribusi Kecamatan</h2>
    <table style="width:auto">
        <thead>
            <tr>
                <th>Kecamatan</th>
                <th style="width:80px">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribusiKecamatan as $item)
                <tr>
                    <td>{{ $item['kecamatan'] }}</td>
                    <td class="text-center">{{ $item['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Distribusi Usia --}}
    <h2>Distribusi Usia</h2>
    <table style="width:auto">
        <thead>
            <tr>
                <th>Rentang Usia</th>
                <th style="width:80px">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribusiUsia as $item)
                @if($item['count'] > 0)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td class="text-center">{{ $item['count'] }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    {{-- Distribusi Pendidikan --}}
    <h2>Distribusi Pendidikan</h2>
    <table style="width:auto">
        <thead>
            <tr>
                <th>Pendidikan</th>
                <th style="width:80px">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribusiPendidikan as $item)
                <tr>
                    <td>{{ $item['pendidikan'] ?: 'Belum Diisi' }}</td>
                    <td class="text-center">{{ $item['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Alokasi Per Bulan --}}
    <h2>Alokasi Petugas per Bulan</h2>
    @php
        $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    @endphp

    <div class="section-block">
        <h2>Visualisasi Ringkas</h2>
        <div class="chart-block">
            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pieChartSvg) }}" alt="Grafik Pie Analisis Petugas">
        </div>
    </div>

    <div class="chart-block">
        <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($lineChartSvg) }}" alt="Grafik Tren Analisis Petugas">
    </div>

    <div class="chart-section">
        <h2>Distribusi Kecamatan</h2>
        <div class="chart-block">
            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($kecamatanPieSvg) }}" alt="Grafik Pie Distribusi Kecamatan">
        </div>
    </div>

    <div class="chart-section">
        <h2>Distribusi Usia</h2>
        <div class="chart-block">
            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($usiaPieSvg) }}" alt="Grafik Pie Distribusi Usia">
        </div>
    </div>

    <div class="chart-section">
        <h2>Distribusi Pendidikan</h2>
        <div class="chart-block">
            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pendidikanPieSvg) }}" alt="Grafik Pie Distribusi Pendidikan">
        </div>
    </div>

    {{-- Alokasi Per Bulan --}}
    <div class="no-break">
        <h2>Alokasi Petugas per Bulan</h2>
        <table>
            <thead>
                <tr>
                    <th>Metrik</th>
                    @foreach($bulanNames as $b)
                        <th class="month-names">{{ $b }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">Jumlah Petugas</td>
                    @foreach($alokasiPerBulan as $item)
                        <td class="text-center">{{ $item['jumlah_petugas'] ?: '-' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="font-bold">Jumlah Kegiatan</td>
                    @foreach($alokasiPerBulan as $item)
                        <td class="text-center">{{ $item['jumlah_kegiatan'] ?: '-' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pemetaan Petugas per Kegiatan --}}
    <div class="table-section">
        <h2>Pemetaan Petugas per Kegiatan</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th style="width:220px">Nama Petugas</th>
                    <th style="width:70px">Jml Kegiatan</th>
                    <th>Kegiatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($petugasKegiatan as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['petugas_nama'] }}</td>
                        <td class="text-center">{{ $item['jumlah_kegiatan'] }}</td>
                        <td>
                            @if(!empty($item['kegiatan']))
                                @foreach($item['kegiatan'] as $k)
                                    <span>{{ $k['nama'] }}</span>@if(!$loop->last), @endif
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Tidak ada data pemetaan petugas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Detail Alokasi Seluruh Petugas --}}
    <div class="table-section page-break-before">
        <h2>Detail Alokasi per Petugas (Jan&ndash;Des) &mdash; Seluruh Petugas</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th style="width:180px">Nama Petugas</th>
                    @foreach($bulanNames as $b)
                        <th class="month-names">{{ $b }}</th>
                    @endforeach
                    <th style="width:65px">Total Kegiatan</th>
                    <th class="amount" style="width:120px">Total Honor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($petugasAlokasiDetail as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['petugas_nama'] }}</td>
                        @for($b = 1; $b <= 12; $b++)
                            <td class="text-center">{{ $item['bulan'][$b] ?? 0 }}</td>
                        @endfor
                        <td class="text-center font-bold">{{ $item['total'] }}</td>
                        <td class="amount">Rp {{ number_format($item['total_honor'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center">Tidak ada data alokasi petugas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top 5 Charts --}}
    <div class="chart-section">
        <h2>Top 5 Petugas &mdash; Tren Alokasi Kegiatan per Bulan</h2>
        <div class="chart-block">
            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($top5KegiatanSvg) }}" alt="Grafik Top 5 Alokasi Kegiatan">
        </div>
    </div>

    <div class="chart-section">
        <h2>Top 5 Petugas &mdash; Tren Alokasi Honor per Bulan</h2>
        <div class="chart-block">
            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($top5HonorSvg) }}" alt="Grafik Top 5 Alokasi Honor">
        </div>
    </div>

    {{-- Top 5 Monthly Breakdown --}}
    <div class="table-section tight">
        <h2>Rincian Bulanan Top 5 (Berdasarkan Total Kegiatan)</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th style="width:180px">Nama Petugas</th>
                    @foreach($bulanNames as $b)
                        <th class="month-names">{{ $b }}</th>
                    @endforeach
                    <th style="width:65px">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($top5DetailByKegiatan as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['petugas_nama'] }}</td>
                        @for($b = 1; $b <= 12; $b++)
                            <td class="text-center">{{ $item['bulan'][$b] ?? 0 }}</td>
                        @endfor
                        <td class="text-center font-bold">{{ $item['total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="text-center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="closing-block">
        <div class="table-section tight">
            <h2>Rincian Bulanan Top 5 (Berdasarkan Total Honor)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width:30px">No</th>
                        <th style="width:180px">Nama Petugas</th>
                        @foreach($bulanNames as $b)
                            <th class="month-names">{{ $b }}</th>
                        @endforeach
                        <th class="amount" style="width:120px">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($top5DetailByHonor as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item['petugas_nama'] }}</td>
                            @for($b = 1; $b <= 12; $b++)
                                <td class="amount" style="font-size:9px">{{ ($item['honor'][$b] ?? 0) > 0 ? number_format(($item['honor'][$b] ?? 0), 0, ',', '.') : '-' }}</td>
                            @endfor
                            <td class="amount font-bold">Rp {{ number_format($item['total_honor'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
    </div>
</body>
</html>
