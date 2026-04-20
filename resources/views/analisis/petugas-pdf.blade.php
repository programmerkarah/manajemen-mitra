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

            <h2>Visualisasi Ringkas</h2>
            <div>{!! $pieChartSvg !!}</div>
            <div style="margin-top:8px">{!! $lineChartSvg !!}</div>
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

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
