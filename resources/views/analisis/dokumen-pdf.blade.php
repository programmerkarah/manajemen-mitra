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
    <p class="meta">Dicetak: {{ $tanggalCetak }}</p>

    {{-- KPI Ringkasan --}}
    <div class="section-block">
        <h2>Ringkasan Dokumen</h2>
        <table class="striped">
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Diterbitkan</th>
                    <th class="text-center">Draft</th>
                    <th class="text-center">% Selesai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">SK KPA</td>
                    <td class="text-center">{{ $skTotal }}</td>
                    <td class="text-center text-green">{{ $skDiterbitkan }}</td>
                    <td class="text-center text-amber">{{ $skDraft }}</td>
                    <td class="text-center font-bold {{ $skTotal > 0 && round($skDiterbitkan / $skTotal * 100) >= 80 ? 'text-green' : ($skTotal > 0 && round($skDiterbitkan / $skTotal * 100) >= 50 ? 'text-amber' : 'text-red') }}">
                        {{ $skTotal > 0 ? round($skDiterbitkan / $skTotal * 100) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="font-bold">Perjanjian Kerja (SPK)</td>
                    <td class="text-center">{{ $spkTotal }}</td>
                    <td class="text-center text-green">{{ $spkDiterbitkan }}</td>
                    <td class="text-center text-amber">{{ $spkDraft }}</td>
                    <td class="text-center font-bold {{ $spkTotal > 0 && round($spkDiterbitkan / $spkTotal * 100) >= 80 ? 'text-green' : ($spkTotal > 0 && round($spkDiterbitkan / $spkTotal * 100) >= 50 ? 'text-amber' : 'text-red') }}">
                        {{ $spkTotal > 0 ? round($spkDiterbitkan / $spkTotal * 100) : 0 }}%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Visualisasi --}}
    <div class="section-block">
        <h2>Visualisasi</h2>
        <table class="chart-grid chart-grid-two">
            <tbody>
                <tr>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pieChartSvg) }}" alt="Status Dokumen SK & SPK">
                        </div>
                    </td>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($lineChartSvg) }}" alt="Tren Dokumen Diterbitkan per Bulan">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Kelengkapan SK per Kegiatan --}}
    <div class="table-section page-break-before">
        <h2>Kelengkapan SK per Kegiatan</h2>
        <table class="striped">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Kegiatan</th>
                    <th class="text-center" style="width:70px">Jenis</th>
                    <th class="text-center" style="width:55px">Total SK</th>
                    <th class="text-center" style="width:70px">Diterbitkan</th>
                    <th class="text-center" style="width:80px">Ditandatangani</th>
                    <th class="text-center" style="width:50px">Draft</th>
                    <th class="text-center" style="width:80px">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kelengkapanSKPerKegiatan as $i => $item)
                    <tr>
                        <td class="number">{{ $i + 1 }}</td>
                        <td>
                            <span class="font-bold">{{ $item['nama_kegiatan'] }}</span><br>
                        </td>
                        <td class="text-center" style="font-size:9px">{{ ucfirst($item['jenis_kegiatan']) }}</td>
                        <td class="text-center">{{ $item['total_sk'] ?: '-' }}</td>
                        <td class="text-center text-green">{{ $item['sk_diterbitkan'] ?: '-' }}</td>
                        <td class="text-center" style="color:#3b82f6">{{ $item['sk_ditandatangani'] ?: '-' }}</td>
                        <td class="text-center text-amber">{{ $item['sk_draft'] ?: '-' }}</td>
                        <td class="text-center">
                            <span class="{{ $item['status_dokumen'] === 'Diterbitkan' ? 'text-green' : ($item['status_dokumen'] === 'Ada Draft' ? 'text-amber' : 'text-red') }} font-bold" style="font-size:9px">
                                {{ $item['status_dokumen'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- SK Draft Lama --}}
    @if(count($skDraftLama) > 0)
    <div class="no-break">
        <h2>SK Masih Draft &gt; 14 Hari</h2>
        <table class="striped">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Kegiatan</th>
                    <th class="text-center" style="width:80px">Bulan SK</th>
                    <th class="text-center" style="width:70px">Umur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($skDraftLama as $i => $sk)
                    <tr>
                        <td class="number">{{ $i + 1 }}</td>
                        <td>
                            <span class="font-bold">{{ $sk['kegiatan_nama'] }}</span>
                        </td>
                        <td class="text-center" style="font-size:9px">
                            @php $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; @endphp
                            {{ $bulanNames[$sk['bulan'] - 1] ?? $sk['bulan'] }} {{ $sk['tahun'] }}
                        </td>
                        <td class="text-center text-amber font-bold">{{ $sk['umur_hari'] }} hari</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Tren per Bulan --}}
    @php $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; @endphp
    <div class="no-break">
        <h2>Tren Dokumen per Bulan</h2>
        <table class="striped">
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
                    <td class="font-bold">SK Diterbitkan</td>
                    @foreach($skPerBulan as $item)
                        <td class="text-center text-green">{{ ($item['diterbitkan'] + $item['ditandatangani']) ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold text-green">{{ collect($skPerBulan)->sum(fn($i) => $i['diterbitkan'] + $i['ditandatangani']) }}</td>
                </tr>
                <tr>
                    <td class="font-bold">SK Draft</td>
                    @foreach($skPerBulan as $item)
                        <td class="text-center text-amber">{{ $item['draft'] ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold text-amber">{{ collect($skPerBulan)->sum('draft') }}</td>
                </tr>
                <tr>
                    <td class="font-bold">SPK Diterbitkan</td>
                    @foreach($spkPerBulan as $item)
                        <td class="text-center" style="color:#3b82f6">{{ $item['diterbitkan'] ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold" style="color:#3b82f6">{{ collect($spkPerBulan)->sum('diterbitkan') }}</td>
                </tr>
                <tr>
                    <td class="font-bold">SPK Draft</td>
                    @foreach($spkPerBulan as $item)
                        <td class="text-center text-amber">{{ $item['draft'] ?: '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold text-amber">{{ collect($spkPerBulan)->sum('draft') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
