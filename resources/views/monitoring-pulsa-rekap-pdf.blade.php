<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Pengadaan Pulsa</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 28px;
        }
        h1 {
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 8px 0;
            text-align: center;
        }
        .meta {
            margin: 0 0 14px 0;
            text-align: right;
            font-size: 11px;
            color: #374151;
        }
        .periode {
            margin: 0 0 14px 0;
            font-size: 11px;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #1f2937;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            text-align: center;
            font-weight: 700;
        }
        td.number {
            width: 52px;
            text-align: center;
        }
        td.amount {
            width: 160px;
            text-align: right;
            white-space: nowrap;
        }
        .empty {
            text-align: center;
            color: #6b7280;
        }
        .disclaimer {
            margin-top: 16px;
            font-size: 11px;
            line-height: 1.5;
            color: #374151;
        }
    </style>
</head>
<body>
    <h1>{{ $judul }}</h1>

    <p class="meta">Tanggal dicetak: {{ $tanggal_cetak }}</p>
    <p class="periode">Periode: {{ $bulan }}/{{ $tahun }}</p>

    <table>
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Nama Petugas</th>
                <th>Jumlah pulsa yang dialokasikan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td>{{ $row['nama_petugas'] }}</td>
                    <td class="amount">Rp {{ number_format((float) $row['jumlah_pulsa'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty">Tidak ada data pengajuan pulsa yang disetujui pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="disclaimer">{{ $disclaimer }}</p>
</body>
</html>
