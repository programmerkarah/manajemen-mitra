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

    {{-- KPI Summary --}}
    <div class="no-break">
        <h2>Ringkasan Kinerja Pengadaan Pulsa</h2>
        <table class="striped" style="width:440px">
            <thead>
                <tr>
                    <th>Indikator</th>
                    <th class="amount" style="width:180px">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Pengajuan (transaksi)</td>
                    <td class="text-center font-bold">{{ number_format($totalPengajuan) }}</td>
                </tr>
                <tr>
                    <td>Total Nominal Diajukan</td>
                    <td class="amount font-bold">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Total Nominal Disetujui</td>
                    <td class="amount font-bold" style="color:#16a34a">Rp {{ number_format($totalDisetujui, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Approval Rate</td>
                    <td class="text-center font-bold" style="color:{{ $approvalRate >= 80 ? '#16a34a' : ($approvalRate >= 50 ? '#d97706' : '#dc2626') }}">
                        {{ $approvalRate }}%
                    </td>
                </tr>
                <tr>
                    <td>Rata-rata Disetujui per Petugas</td>
                    <td class="amount font-bold">Rp {{ number_format($rataRataPulsa, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section-block">
        <h2>Visualisasi Ringkas</h2>
        <table class="chart-grid chart-grid-two">
            <tbody>
                <tr>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pieChartSvg) }}" alt="Grafik Pie Analisis Pulsa">
                        </div>
                    </td>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($lineChartSvg) }}" alt="Grafik Tren Analisis Pulsa">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Alokasi per Bulan --}}
    <div class="no-break">
        <h2>Ringkasan Pengajuan Pulsa per Bulan</h2>
        @php
            $bulanNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        @endphp
        <table class="striped">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="text-center" style="width:60px">Petugas</th>
                    <th class="text-center" style="width:70px">Kegiatan</th>
                    <th class="text-center" style="width:70px">Diajukan</th>
                    <th class="text-center" style="width:70px;color:#16a34a">Disetujui</th>
                    <th class="text-center" style="width:60px;color:#dc2626">Ditolak</th>
                    <th class="text-center" style="width:70px;color:#d97706">Menunggu</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alokasiPulsaPerBulan as $item)
                    <tr>
                        <td class="font-bold">{{ $bulanNames[$item['bulan'] - 1] }}</td>
                        <td class="text-center">{{ $item['jumlah_petugas'] ?: '-' }}</td>
                        <td class="text-center">{{ $item['jumlah_kegiatan'] ?: '-' }}</td>
                        <td class="text-center">{{ $item['diajukan'] ?: '-' }}</td>
                        <td class="text-center" style="color:#16a34a;font-weight:600">{{ $item['disetujui'] ?: '-' }}</td>
                        <td class="text-center" style="color:#dc2626;font-weight:600">{{ $item['ditolak'] ?: '-' }}</td>
                        <td class="text-center" style="color:#d97706;font-weight:600">{{ $item['menunggu'] ?: '-' }}</td>
                    </tr>
                @endforeach
                <tr style="border-top:2px solid #6b7280;font-weight:700">
                    <td>Total</td>
                    <td class="text-center">&mdash;</td>
                    <td class="text-center">&mdash;</td>
                    <td class="text-center">{{ collect($alokasiPulsaPerBulan)->sum('diajukan') }}</td>
                    <td class="text-center" style="color:#16a34a">{{ collect($alokasiPulsaPerBulan)->sum('disetujui') }}</td>
                    <td class="text-center" style="color:#dc2626">{{ collect($alokasiPulsaPerBulan)->sum('ditolak') }}</td>
                    <td class="text-center" style="color:#d97706">{{ collect($alokasiPulsaPerBulan)->sum('menunggu') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Distribusi Jenis Pulsa --}}
    <div class="no-break">
        <h2>Distribusi per Jenis Pulsa</h2>
        <table class="striped" style="width:auto">
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th style="width:80px">Jumlah</th>
                    <th class="amount" style="width:140px">Total Nominal</th>
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
    </div>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>

        <table class="chart-grid chart-grid-two">
            <tbody>
                <tr>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($pieChartSvg) }}" alt="Grafik Pie Analisis Pulsa">
                        </div>
                    </td>
                    <td>
                        <div class="chart-block">
                            <img class="chart-image" src="data:image/svg+xml;base64,{{ base64_encode($lineChartSvg) }}" alt="Grafik Tren Analisis Pulsa">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Distribusi Jenis Pulsa --}}
    <div class="no-break">
        <h2>Distribusi per Jenis Pulsa</h2>
        <table class="striped" style="width:auto">
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th style="width:80px">Jumlah</th>
                    <th class="amount" style="width:140px">Total Nominal</th>
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
    </div>

    <p class="disclaimer">Dokumen ini di-generate secara otomatis oleh SIMANTIK.</p>
</body>
</html>
