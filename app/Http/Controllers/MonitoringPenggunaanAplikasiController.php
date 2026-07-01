<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MonitoringPenggunaanAplikasiController extends Controller
{
    private const EXCLUDED_ACTIONS = [
        'Switch Role',
    ];

    private const ADMINISTRATIVE_TYPES = [
        'kegiatan',
        'alokasi',
        'mitra',
        'spk',
        'sk_kpa',
        'bast',
        'pengajuan_pulsa',
    ];

    private const SYSTEM_TYPES = [
        'auth',
        'system',
        'user',
    ];

    public function index(Request $request): Response
    {
        return Inertia::render('Monitoring/PenggunaanAplikasi', $this->buildReportData($request));
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $reportData = $this->buildReportData($request);
        $pdf = Pdf::loadView('monitoring-penggunaan-aplikasi-pdf', $reportData)
            ->setPaper('a4', 'portrait');

        $filename = sprintf(
            'laporan_penggunaan_aplikasi_%s_%s_%s.pdf',
            $reportData['active_year'],
            $reportData['filters']['bulan'],
            now()->format('Ymd_His'),
        );

        return $pdf->download($filename);
    }

    /**
     * @return array{
     *     active_year: int,
     *     generated_at: string,
     *     filters: array{bulan: string},
     *     month_label: string,
     *     report_period: string,
     *     summary: array{active_users: int, total_logs: int, active_days: int, average_logs_per_day: float|int, administrative_actions: int, system_actions: int},
     *     daily_access: array<int, array{day: int, date: string, label: string, total_logs: int, unique_users: int}>,
     *     type_summary: array<int, array{type: string, label: string, total: int}>,
     *     top_actions: array<int, array{type: string, label: string, action: string, total: int}>,
     *     top_users: array<int, array{user_id: int, user_name: string, total_logs: int, active_days: int}>,
     *     impact_summary: array<int, array{label: string, count: int, description: string}>
     * }
     */
    private function buildReportData(Request $request): array
    {
        $activeYear = ActiveYearService::get();
        $selectedMonth = $this->normalizeMonthValue($request->input('bulan', now()->format('m')));
        $selectedMonthNumber = (int) $selectedMonth;
        $monthStart = Carbon::create($activeYear, $selectedMonthNumber, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthName = $monthStart->copy()->locale('id')->translatedFormat('F');

        $baseQuery = ActivityLog::query()
            ->whereNotIn('action', self::EXCLUDED_ACTIONS)
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $totalLogs = (clone $baseQuery)->count();
        $activeUsers = (clone $baseQuery)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
        $activeDays = (int) ((clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as total')
            ->value('total') ?? 0);
        $averageLogsPerDay = $activeDays > 0 ? round($totalLogs / $activeDays, 1) : 0;

        $dailyRows = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as log_date, COUNT(*) as total_logs, COUNT(DISTINCT user_id) as unique_users')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('log_date')
            ->get()
            ->keyBy('log_date');

        $dailyAccess = collect(range(1, $monthStart->daysInMonth))->map(function (int $day) use ($dailyRows, $monthStart) {
            $date = $monthStart->copy()->day($day);
            $row = $dailyRows->get($date->toDateString());

            return [
                'day' => $day,
                'date' => $date->toDateString(),
                'label' => $date->locale('id')->translatedFormat('d M'),
                'total_logs' => (int) ($row->total_logs ?? 0),
                'unique_users' => (int) ($row->unique_users ?? 0),
            ];
        })->values()->all();

        $typeSummary = (clone $baseQuery)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row): array {
                return [
                    'type' => $row->type,
                    'label' => $this->labelForType((string) $row->type),
                    'total' => (int) $row->total,
                ];
            })
            ->values()
            ->all();

        $topActions = (clone $baseQuery)
            ->selectRaw('type, action, COUNT(*) as total')
            ->groupBy('type', 'action')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row): array {
                return [
                    'type' => $row->type,
                    'label' => $this->labelForType((string) $row->type),
                    'action' => $row->action,
                    'total' => (int) $row->total,
                ];
            })
            ->values()
            ->all();

        $topUsers = (clone $baseQuery)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, user_name, COUNT(*) as total_logs, COUNT(DISTINCT DATE(created_at)) as active_days')
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('total_logs')
            ->limit(10)
            ->get()
            ->map(function ($row): array {
                return [
                    'user_id' => (int) $row->user_id,
                    'user_name' => $row->user_name,
                    'total_logs' => (int) $row->total_logs,
                    'active_days' => (int) $row->active_days,
                ];
            })
            ->values()
            ->all();

        $impactSummary = [
            [
                'label' => 'Dokumen administrasi',
                'count' => (clone $baseQuery)->whereIn('type', ['sk_kpa', 'spk', 'bast'])->count(),
                'description' => 'Pembuatan, revisi, unggah tanda tangan, dan finalisasi dokumen',
            ],
            [
                'label' => 'Proses operasional',
                'count' => (clone $baseQuery)->whereIn('type', ['kegiatan', 'alokasi', 'pengajuan_pulsa', 'mitra'])->count(),
                'description' => 'Pengelolaan kegiatan, alokasi, mitra, dan pengajuan pulsa',
            ],
            [
                'label' => 'Akses sistem',
                'count' => (clone $baseQuery)->whereIn('type', ['auth', 'system', 'user'])->count(),
                'description' => 'Login, dan pengelolaan akun',
            ],
        ];

        return [
            'active_year' => $activeYear,
            'generated_at' => now()
                ->timezone(config('app.timezone', 'Asia/Jakarta'))
                ->locale('id')
                ->translatedFormat('d F Y H:i'),
            'filters' => [
                'bulan' => $selectedMonth,
            ],
            'month_label' => $monthName,
            'report_period' => sprintf('%s %s', $monthName, $activeYear),
            'summary' => [
                'active_users' => $activeUsers,
                'total_logs' => $totalLogs,
                'active_days' => $activeDays,
                'average_logs_per_day' => $averageLogsPerDay,
                'administrative_actions' => (clone $baseQuery)->whereIn('type', self::ADMINISTRATIVE_TYPES)->count(),
                'system_actions' => (clone $baseQuery)->whereIn('type', self::SYSTEM_TYPES)->count(),
            ],
            'daily_access' => $dailyAccess,
            'type_summary' => $typeSummary,
            'top_actions' => $topActions,
            'top_users' => $topUsers,
            'impact_summary' => $impactSummary,
        ];
    }

    private function normalizeMonthValue(mixed $bulan): string
    {
        if ($bulan === null || $bulan === '') {
            return now()->format('m');
        }

        $raw = trim((string) $bulan);

        if (is_numeric($raw)) {
            $numericMonth = (int) $raw;

            if ($numericMonth >= 1 && $numericMonth <= 12) {
                return str_pad((string) $numericMonth, 2, '0', STR_PAD_LEFT);
            }
        }

        return now()->format('m');
    }

    private function labelForType(string $type): string
    {
        return match ($type) {
            'auth' => 'Autentikasi',
            'system' => 'Sistem',
            'user' => 'User',
            'kegiatan' => 'Kegiatan',
            'alokasi' => 'Alokasi',
            'mitra' => 'Mitra',
            'spk' => 'SPK',
            'sk_kpa' => 'SK KPA',
            'bast' => 'BAST',
            'pengajuan_pulsa' => 'Pulsa',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
