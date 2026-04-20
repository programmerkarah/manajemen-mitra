<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Dokumen SK &amp; Perjanjian Kerja Sensus dan Survei BPS Kota Sawahlunto &mdash; Tahun Anggaran {{ $currentYear }}</title>
    @include('analisis._pdf-style')
</head>
<body>
    <h1>
        <span class="title-line">Analisis Dokumen SK &amp; Perjanjian Kerja Sensus dan Survei</span>
        <span class="title-line">di Lingkungan Badan Pusat Statistik Kota Sawahlunto &mdash; Tahun Anggaran {{ $currentYear }}</span>
    </h1>
    <p class="meta">Dicetak: {{ $tanggalCetak }} &nbsp;|&nbsp; SK Total: {{ $skTotal }} &nbsp;|&nbsp; SPK Total: {{ $spkTotal }}</p>

    <h2>Visualisasi Ringkas</h2>
    <div>{!! $pieChartSvg !!}</div>
    <div style="margin-top:8px">{!! $lineChartSvg !!}</div>

    {{-- SK per Bulan --}}
    <h2>Surat Keputusan (SK) per Bulan</h2>
    @php
        $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    @endphp
    <table>
        <thead>
            <tr>
                <th>Status</th>
                @foreach($bulanNames as $b)
                    <th class="month-names">{{ $b }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Total</td>
                @foreach($skPerBulan as $item)
                    <td class="text-center">{{ $item['total'] ?: '-' }}</td>
                @endforeach
                <td class="text-center font-bold">{{ collect($skPerBulan)->sum('total') }}</td>
            </tr>
            <tr>
                <td class="font-bold">Draft</td>
                @foreach($skPerBulan as $item)
                    <td class="text-center">{{ $item['draft'] ?: '-' }}</td>
                @endforeach
                <td class="text-center font-bold">{{ collect($skPerBulan)->sum('draft') }}</td>
            </tr>
            <tr>
                <td class="font-bold">Diterbitkan</td>
                @foreach($skPerBulan as $item)
                    <td class="text-center">{{ $item['diterbitkan'] ?: '-' }}</td>
                @endforeach
                <td class="text-center font-bold">{{ collect($skPerBulan)->sum('diterbitkan') }}</td>
            </tr>
            <tr>
                <td class="font-bold">Ditandatangani</td>
                @foreach($skPerBulan as $item)
                    <td class="text-center">{{ $item['ditandatangani'] ?: '-' }}</td>
                @endforeach
                <td class="text-center font-bold">{{ collect($skPerBulan)->sum('ditandatangani') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- SPK per Bulan --}}
    <div class="no-break">
        <h2>Surat Perjanjian Kerja (SPK) per Bulan</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    @foreach($bulanNames as $b)
                        <th class="month-names">{{ $b }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">Total</td>
                    @foreach($spkPerBulan as $item)
                        <td class="text-center">{{ $item['total'] ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold">{{ collect($spkPerBulan)->sum('total') }}</td>
                </tr>
                <tr>
                    <td class="font-bold">Draft</td>
                    @foreach($spkPerBulan as $item)
                        <td class="text-center">{{ $item['draft'] ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold">{{ collect($spkPerBulan)->sum('draft') }}</td>
                </tr>
                <tr>
                    <td class="font-bold">Diterbitkan</td>
                    @foreach($spkPerBulan as $item)
                        <td class="text-center">{{ $item['diterbitkan'] ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold">{{ collect($spkPerBulan)->sum('diterbitkan') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
