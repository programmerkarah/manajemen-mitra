<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Umum {{ $currentYear }}</title>
    @include('analisis._pdf-style')
</head>
<body>
    <h1>Analisis Umum Sensus dan Survei di Lingkungan Badan Pusat Statistik Kota Sawahlunto &mdash; Tahun Anggaran {{ $currentYear }}</h1>
    <p class="meta">Dicetak: {{ $tanggalCetak }}</p>

    <div class="section-block">
        <h2>Visualisasi Ringkas</h2>
        <table class="chart-grid chart-grid-two">
            <tbody>
                <tr>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pieChartSvg) }}" alt="Grafik Pie Analisis Umum">
                        </div>
                    </td>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($lineChartSvg) }}" alt="Grafik Tren Analisis Umum">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Penyerapan Anggaran --}}
    <div class="table-section page-break-before">
        <h2>Penyerapan Anggaran per Kegiatan</h2>
        <table class="striped">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Kegiatan</th>
                    <th class="amount" style="width:130px">Pagu</th>
                    <th class="amount" style="width:130px">Terpakai</th>
                    <th style="width:60px">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($utilisasiAnggaran as $i => $item)
                    <tr>
                        <td class="number">{{ $i + 1 }}</td>
                        <td>{{ $item['nama_kegiatan'] }}</td>
                        <td class="amount">Rp {{ number_format($item['total_pagu'], 0, ',', '.') }}</td>
                        <td class="amount">Rp {{ number_format($item['total_terpakai'], 0, ',', '.') }}</td>
                        <td class="text-center {{ $item['persentase'] > 90 ? 'text-red' : ($item['persentase'] > 70 ? 'text-amber' : 'text-green') }} font-bold">
                            {{ $item['persentase'] }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tren Alokasi Bulanan --}}
    <div class="no-break">
        <h2>Tren Alokasi Bulanan</h2>
        @php
            $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        @endphp
        <table class="striped">
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
                    <td class="font-bold">Total Honor</td>
                    @foreach($trenAlokasi as $item)
                        <td class="amount" style="font-size:9px">{{ $item['total_honor'] > 0 ? number_format($item['total_honor'] / 1000000, 1, ',', '.') . 'jt' : '-' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="font-bold">Total Kegiatan</td>
                    @foreach($trenAlokasi as $item)
                        <td class="text-center">{{ $item['total_kegiatan'] ?: '-' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="font-bold">Jumlah Petugas</td>
                    @foreach($trenAlokasi as $item)
                        <td class="text-center">{{ $item['jumlah_petugas'] ?: '-' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
