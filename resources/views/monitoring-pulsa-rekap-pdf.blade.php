<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Pengadaan Pulsa</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 24px;
        }
        h1 {
            font-size: 13px;
            line-height: 1.45;
            margin: 0 0 8px 0;
            text-align: center;
        }
        .meta {
            margin: 0 0 12px 0;
            text-align: right;
            font-size: 9px;
            color: #374151;
        }
        .periode {
            margin: 0 0 12px 0;
            font-size: 9px;
            color: #374151;
        }
        table {
            width: 100%;
            font-family: DejaVu Sans, sans-serif;
            font-weight: normal;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: fixed;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        th,
        td {
            font-family: DejaVu Sans, sans-serif;
            font-weight: normal;
            border: 1px solid #1f2937;
            padding: 5px 6px;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
        }
        .col-number {
            width: 5%;
            text-align: center;
        }
        .col-name {
            width: 34%;
        }
        .col-phone {
            width: 18%;
        }
        .col-provider {
            width: 14%;
        }
        .col-amount {
            width: 29%;
        }
        td.number {
            text-align: center;
            vertical-align: top;
        }
        td.phone,
        td.provider {
            white-space: nowrap;
        }
        td.amount {
            text-align: right;
            white-space: nowrap;
        }
        tr.detail-row td {
            background: #fafafa;
        }
        td.detail-name {
            padding-left: 14px;
        }
        .empty {
            text-align: center;
            color: #6b7280;
        }
        .disclaimer {
            margin-top: 14px;
            font-size: 9px;
            line-height: 1.45;
            color: #374151;
        }
    </style>
</head>
<body>
    <h1>{{ $judul }}</h1>

    <p class="meta">Tanggal dicetak: {{ $tanggal_cetak }}</p>
    <p class="periode">Periode: {{ $periode_label }}</p>

    <table>
        <thead>
            <tr>
                <th class="col-number">No</th>
                <th class="col-name">Nama / Kegiatan</th>
                <th class="col-phone">No HP</th>
                <th class="col-provider">Provider</th>
                <th class="col-amount">Jumlah Pulsa yang Dialokasikan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $rincian = $row['rincian'] ?? [];
                    $rowspan = max(1, count($rincian) + 1);
                @endphp
                <tr class="petugas-row">
                    <td class="number" rowspan="{{ $rowspan }}">{{ $index + 1 }}</td>
                    <td>{{ $row['nama_petugas'] }}</td>
                    <td class="phone">{{ $row['nomor_hp'] }}</td>
                    <td class="provider">{{ $row['provider'] }}</td>
                    <td class="amount">Rp {{ number_format((float) $row['jumlah_pulsa'], 0, ',', '.') }}</td>
                </tr>
                @foreach($rincian as $detail)
                    <tr class="detail-row">
                        <td class="detail-name">- {{ $detail['nama_kegiatan'] }}</td>
                        <td></td>
                        <td></td>
                        <td class="amount">Rp {{ number_format((float) $detail['nominal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="empty">Tidak ada data pengajuan pulsa yang disetujui pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="disclaimer">{{ $disclaimer }}</p>
</body>
</html>
