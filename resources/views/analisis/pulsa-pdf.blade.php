<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Pulsa {{ $currentYear }}</title>
    @include('analisis._pdf-style')
</head>
<body>
    <h1>Analisis Kebutuhan dan Pengadaan Pulsa Sensus dan Survei di Lingkungan Badan Pusat Statistik Kota Sawahlunto &mdash; Tahun Anggaran {{ $currentYear }}</h1>
    <p class="meta">Dicetak: {{ $tanggalCetak }}</p>

    {{-- Distribusi Pulsa per Bulan --}}
    <h2>Distribusi Alokasi Pulsa per Bulan</h2>
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
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Pengajuan</td>

            <h2>Visualisasi Ringkas</h2>
            <div>{!! $pieChartSvg !!}</div>
            <div style="margin-top:8px">{!! $lineChartSvg !!}</div>
                @foreach($pulsaPerBulan as $item)
                    <td class="text-center">{{ $item['total_pengajuan'] ?: '-' }}</td>
                @endforeach
                <td class="text-center font-bold">{{ collect($pulsaPerBulan)->sum('total_pengajuan') }}</td>
            </tr>
            <tr>
                <td class="font-bold">Petugas</td>
                @foreach($pulsaPerBulan as $item)
                    <td class="text-center">{{ $item['jumlah_petugas'] ?: '-' }}</td>
                @endforeach
                <td class="text-center font-bold">-</td>
            </tr>
            <tr>
                <td class="font-bold">Nominal Diajukan</td>
                @foreach($pulsaPerBulan as $item)
                    <td class="amount" style="font-size:9px">{{ $item['total_nominal'] > 0 ? 'Rp ' . number_format($item['total_nominal'], 0, ',', '.') : '-' }}</td>
                @endforeach
                <td class="amount font-bold" style="font-size:9px">Rp {{ number_format(collect($pulsaPerBulan)->sum('total_nominal'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="font-bold">Nominal Disetujui</td>
                @foreach($pulsaPerBulan as $item)
                    <td class="amount" style="font-size:9px">{{ $item['total_disetujui'] > 0 ? 'Rp ' . number_format($item['total_disetujui'], 0, ',', '.') : '-' }}</td>
                @endforeach
                <td class="amount font-bold" style="font-size:9px">Rp {{ number_format(collect($pulsaPerBulan)->sum('total_disetujui'), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Distribusi Jenis Pulsa --}}
    <h2>Distribusi per Jenis Pulsa</h2>
    <table style="width:auto">
        <thead>
            <tr>
                <th>Jenis</th>
                <th style="width:80px">Jumlah</th>
                <th class="amount" style="width:130px">Total Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribusiJenisPulsa as $item)
                <tr>
                    <td>{{ $item['jenis'] }}</td>
                    <td class="text-center">{{ $item['count'] }}</td>
                    <td class="amount">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
