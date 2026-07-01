<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penggunaan Aplikasi</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 24px;
            line-height: 1.45;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 6px 0;
            text-align: center;
        }
        .meta {
            margin: 0 0 16px 0;
            text-align: center;
            color: #4b5563;
        }
        .section {
            margin-top: 18px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            width: 25%;
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }
        .summary-label {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: 700;
        }
        table.report {
            width: 100%;
            border-collapse: collapse;
        }
        table.report th,
        table.report td {
            border: 1px solid #1f2937;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.report th {
            background: #f3f4f6;
            text-align: center;
        }
        .number {
            width: 50px;
            text-align: center;
        }
        .right {
            text-align: right;
            white-space: nowrap;
        }
        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Laporan Penggunaan Aplikasi</h1>
    <p class="meta">Periode: {{ $report_period }} | Dihasilkan: {{ $generated_at }}</p>
    @if(!empty($filters['user_name']))
        <p class="meta">Pengguna terpilih: {{ $filters['user_name'] }}</p>
    @endif

    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-label">Jumlah pengguna layanan</div>
                <div class="summary-value">{{ number_format($summary['active_users'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Total akses</div>
                <div class="summary-value">{{ number_format($summary['total_logs'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Hari aktif</div>
                <div class="summary-value">{{ number_format($summary['active_days'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Rata-rata akses per hari aktif</div>
                <div class="summary-value">{{ number_format((float) $summary['average_logs_per_day'], 1, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Akses Harian</div>
        <table class="report">
            <thead>
                <tr>
                    <th class="number">Tanggal</th>
                    <th>Label</th>
                    <th class="right">Akses</th>
                    <th class="right">Pengguna unik</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daily_access as $row)
                    <tr>
                        <td class="number">{{ $row['day'] }}</td>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ number_format($row['total_logs'], 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($row['unique_users'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Aktivitas Teratas</div>
        <table class="report">
            <thead>
                <tr>
                    <th class="number">No</th>
                    <th>Kelompok</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($top_actions as $index => $row)
                    <tr>
                        <td class="number">{{ $index + 1 }}</td>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="muted" style="text-align:center;">Tidak ada data aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Pengguna Paling Aktif</div>
        <table class="report">
            <thead>
                <tr>
                    <th class="number">No</th>
                    <th>Nama Pengguna</th>
                    <th class="right">Total Akses</th>
                    <th class="right">Hari Aktif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($top_users as $index => $row)
                    <tr>
                        <td class="number">{{ $index + 1 }}</td>
                        <td>{{ $row['user_name'] }}</td>
                        <td class="right">{{ number_format($row['total_logs'], 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($row['active_days'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" style="text-align:center;">Tidak ada data pengguna aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Aktivitas Pengguna</div>
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-label">Nama pengguna</div>
                    <div class="summary-value">{{ $selected_user_summary['user_name'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="summary-label">Total akses</div>
                    <div class="summary-value">{{ number_format($selected_user_summary['total_logs'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="summary-label">Hari aktif</div>
                    <div class="summary-value">{{ number_format($selected_user_summary['active_days'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="summary-label">Catatan</div>
                    <div class="muted">Aksi di /kegiatan digabung sebagai Kelola Kegiatan.</div>
                </td>
            </tr>
        </table>

        <table class="report" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th class="number">Tanggal</th>
                    <th class="right">Total Akses</th>
                    <th>Kelompok Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($selected_user_daily_access as $row)
                    <tr>
                        <td class="number">{{ $row['label'] }}</td>
                        <td class="right">{{ number_format($row['total_logs'], 0, ',', '.') }}</td>
                        <td>
                            @forelse($row['activity_breakdown'] as $activity)
                                <div>{{ $activity['label'] }}: {{ number_format($activity['total'], 0, ',', '.') }}</div>
                            @empty
                                <span class="muted">Tidak ada akses</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Dampak Administratif</div>
        <table class="report">
            <thead>
                <tr>
                    <th>Bidang</th>
                    <th class="right">Jumlah</th>
                    <th>Uraian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($impact_summary as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ number_format($row['count'], 0, ',', '.') }}</td>
                        <td>{{ $row['description'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
