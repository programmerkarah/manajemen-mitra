<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Petugas Organik {{ $currentYear }}</title>
    @include('analisis._pdf-style')
    <style>
        .subtitle {
            margin: 0 0 6px 0;
            font-size: 10px;
            color: #4b5563;
            text-align: center;
        }
        .summary-cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
            margin-bottom: 6px;
        }
        .summary-cards td {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 6px;
            background: #f8fafc;
            width: 33.33%;
            text-align: center;
        }
        .summary-cards .summary-label {
            display: block;
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .summary-cards .summary-value {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }
        .metric-note {
            margin: 0 0 6px 0;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.4;
        }
        .striped tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .indicator-legend {
            margin: 0 0 8px 0;
            font-size: 10px;
            color: #374151;
        }
        .indicator-legend span {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    @php
        $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $activeMonths = range(1, max(1, (int) $currentMonth));
        $activeMonthChunks = array_chunk($activeMonths, 6);
        $trenByBulan = collect($trenBebanKerja)->keyBy('bulan');
    @endphp

    <h1>Analisis Petugas Organik &mdash; Tahun Anggaran {{ $currentYear }}</h1>
    <p class="subtitle">Distribusi beban kerja pegawai organik (Januari - {{ $bulanNames[$currentMonth - 1] ?? 'Des' }})</p>
    <p class="meta">Dicetak: {{ $tanggalCetak }}</p>

    <h2>Ringkasan</h2>
    <table class="summary-cards">
        <tbody>
            <tr>
                <td>
                    <span class="summary-label">Total Pegawai Organik Aktif</span>
                    <span class="summary-value">{{ $ringkasan['total_petugas_aktif'] }}</span>
                </td>
                <td>
                    <span class="summary-label">Pegawai Teralokasi</span>
                    <span class="summary-value">{{ $ringkasan['total_petugas_teralokasi'] }}</span>
                </td>
                <td>
                    <span class="summary-label">Total Alokasi</span>
                    <span class="summary-value">{{ $ringkasan['total_alokasi'] }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-block">
        <h2>Visualisasi Ringkas</h2>
        <table class="chart-grid chart-grid-two">
            <tbody>
                <tr>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pieChartSvg) }}" alt="Pie Distribusi Beban Kerja Organik">
                        </div>
                    </td>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($lineChartSvg) }}" alt="Tren Beban Kerja Organik">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <h2>Ringkasan Tabel Beban Kerja</h2>
        <table class="panel-grid">
            <tbody>
                <tr>
                    <td>
                        <div class="panel-card">
                            <p class="panel-title">Distribusi Beban Kerja</p>
                            <table class="striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Kelompok</th>
                                        <th style="width:90px">Jumlah Pegawai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($distribusiBebanKerja as $item)
                                        <tr>
                                            <td>{{ $item['label'] }}</td>
                                            <td class="text-center">{{ $item['count'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td>
                        <div class="panel-card">
                            <p class="panel-title">Tren Beban Kerja Bulanan</p>
                            <p class="metric-note">Periode Januari - {{ $bulanNames[$currentMonth - 1] ?? 'Des' }}</p>
                            @foreach($activeMonthChunks as $chunkIndex => $monthChunk)
                                @php
                                    $chunkStart = $bulanNames[$monthChunk[0] - 1] ?? '-';
                                    $chunkEnd = $bulanNames[$monthChunk[count($monthChunk) - 1] - 1] ?? '-';
                                @endphp
                                <p class="metric-note">{{ $chunkStart }} - {{ $chunkEnd }}</p>
                                <table class="striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width:120px">Metrik</th>
                                            @foreach($monthChunk as $monthNumber)
                                                <th class="month-names">{{ $bulanNames[$monthNumber - 1] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="font-bold">Petugas Teralokasi</td>
                                            @foreach($monthChunk as $monthNumber)
                                                <td class="text-center">{{ data_get($trenByBulan->get($monthNumber), 'jumlah_petugas', '-') }}</td>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            <td class="font-bold">Jumlah Kegiatan</td>
                                            @foreach($monthChunk as $monthNumber)
                                                <td class="text-center">{{ data_get($trenByBulan->get($monthNumber), 'jumlah_kegiatan', '-') }}</td>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            <td class="font-bold">Jumlah Alokasi</td>
                                            @foreach($monthChunk as $monthNumber)
                                                <td class="text-center">{{ data_get($trenByBulan->get($monthNumber), 'jumlah_alokasi', '-') }}</td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-section page-break-before">
        <h2>Detail Beban Kerja Pegawai Organik</h2>
        <p class="metric-note">Jumlah Kegiatan = kegiatan unik dialokasikan. Jumlah Alokasi = total alokasi kegiatan per pegawai. Rata-rata per bulan = total alokasi / jumlah bulan yang benar-benar dialokasikan.</p>
        <p class="metric-note">Aturan indikator statis: Under Performance &lt; 1 kegiatan/bulan, Normal = 1 kegiatan/bulan, Optimal &gt; 1-3 kegiatan/bulan, Overload &gt; 3 kegiatan/bulan.</p>
        <p class="indicator-legend">
            <span><span class="badge badge-red">Overload</span></span>
            <span><span class="badge badge-blue">Normal</span></span>
            <span><span class="badge badge-green">Optimal</span></span>
            <span><span class="badge badge-amber">Under Performance</span></span>
        </p>
        <table class="striped">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Nama</th>
                    <th style="width:140px">Jabatan</th>
                    <th style="width:80px">Kegiatan</th>
                    <th style="width:80px">Alokasi</th>
                    <th style="width:110px">Indikator</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bebanKerjaDetail as $index => $item)
                    @php
                        $badgeClass = $item['performance_status'] === 'overload'
                            ? 'badge-red'
                            : ($item['performance_status'] === 'under_performance' ? 'badge-amber' : 'badge-green');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['petugas_nama'] }}</td>
                        <td>{{ $item['jabatan'] ?: '-' }}</td>
                        <td class="text-center">{{ $item['jumlah_kegiatan'] }}</td>
                        <td class="text-center">{{ $item['jumlah_alokasi'] }}</td>
                        <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $item['performance_label'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data beban kerja pegawai organik.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
