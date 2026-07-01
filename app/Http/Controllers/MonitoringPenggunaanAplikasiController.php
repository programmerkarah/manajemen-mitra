<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class MonitoringPenggunaanAplikasiController extends Controller
{
    private const EXCLUDED_ACTIONS = [
        'Switch Role',
        'View As User',
        'Clear View As User',
    ];

    public function index(Request $request): Response
    {
        $filters = $this->resolveFilters($request);

        return Inertia::render('Monitoring/PenggunaanAplikasi', $this->buildReportData($filters));
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $reportData = $this->buildReportData($this->resolveFilters($request));
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
    *     filters: array{bulan: string, user_name: string|null},
    *     state_url: string,
     *     month_label: string,
     *     report_period: string,
     *     summary: array{active_users: int, total_logs: int, active_days: int, average_logs_per_day: float|int, administrative_actions: int, system_actions: int},
     *     daily_access: array<int, array{day: int, date: string, label: string, total_logs: int, unique_users: int}>,
         *     type_summary: array<int, array{type: string, label: string, total: int}>,
         *     top_actions: array<int, array{type: string, label: string, action: string, total: int}>,
         *     all_user_activity: array<int, array{user_id: int, user_name: string, total_logs: int, active_days: int}>,
         *     top_users: array<int, array{user_id: int, user_name: string, total_logs: int, active_days: int}>,
         *     user_name_options: array<int, array{value: string, label: string}>,
         *     selected_user_name: string|null,
         *     selected_user_summary: array{user_name: string|null, total_logs: int, active_days: int}|null,
         *     selected_user_daily_access: array<int, array{day: int, date: string, label: string, total_logs: int, activity_breakdown: array<int, array{label: string, total: int}>}>,
     *     impact_summary: array<int, array{label: string, count: int, description: string}>
     * }
     */
    private function buildReportData(array $filters): array
    {
        $activeYear = ActiveYearService::get();
        $selectedMonth = $this->normalizeMonthValue($filters['bulan'] ?? now()->format('m'));
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

        $userNameOptions = (clone $baseQuery)
            ->whereNotNull('user_name')
            ->select('user_name')
            ->distinct()
            ->orderBy('user_name')
            ->pluck('user_name')
            ->map(function (string $userName): array {
                return [
                    'value' => $userName,
                    'label' => $userName,
                ];
            })
            ->values()
            ->all();

        $allMonthlyLogs = (clone $baseQuery)
            ->get(['type', 'action', 'created_at', 'user_id', 'user_name']);

        $topActions = $allMonthlyLogs
            ->map(function ($row): array {
                return [
                    'type' => (string) $row->type,
                    'label' => $this->groupActivityLabel((string) $row->type, (string) $row->action),
                    'action' => $this->groupActivityLabel((string) $row->type, (string) $row->action),
                    'total' => 1,
                ];
            })
            ->groupBy(function (array $row): string {
                return $row['label'];
            })
            ->map(function ($rows, string $label): array {
                $firstRow = $rows->first();

                return [
                    'type' => (string) $firstRow['type'],
                    'label' => $label,
                    'action' => $label,
                    'total' => (int) $rows->sum('total'),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $topUsers = (clone $baseQuery)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, user_name, COUNT(*) as total_logs, COUNT(DISTINCT DATE(created_at)) as active_days')
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('total_logs')
            ->limit(4)
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

        $allUserActivity = (clone $baseQuery)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, user_name, COUNT(*) as total_logs, COUNT(DISTINCT DATE(created_at)) as active_days')
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('total_logs')
            ->orderByDesc('active_days')
            ->orderBy('user_name')
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

        $topUsers = array_slice($allUserActivity, 0, 4);

        $selectedUserName = $this->normalizeUserNameValue($filters['user_name'] ?? null);

        if ($selectedUserName !== null && ! in_array($selectedUserName, array_column($userNameOptions, 'value'), true)) {
            $selectedUserName = null;
        }

        $selectedUserSummary = null;
        $selectedUserDailyAccess = [];

        if ($selectedUserName !== null) {
            $selectedUserQuery = (clone $baseQuery)->where('user_name', $selectedUserName);

            $selectedUserSummary = [
                'user_name' => $selectedUserName,
                'total_logs' => (clone $selectedUserQuery)->count(),
                'active_days' => (int) ((clone $selectedUserQuery)
                    ->selectRaw('COUNT(DISTINCT DATE(created_at)) as total')
                    ->value('total') ?? 0),
            ];

            $selectedUserLogs = (clone $selectedUserQuery)
                ->get(['type', 'action', 'created_at']);

            $selectedUserDailyAccessRows = $selectedUserLogs
                ->groupBy(function ($row): string {
                    return Carbon::parse($row->created_at)->toDateString();
                });

            $selectedUserActivityRows = $selectedUserLogs
                ->groupBy(function ($row): string {
                    return Carbon::parse($row->created_at)->toDateString();
                })
                ->map(function ($rows) {
                    return $rows
                        ->map(function ($row): array {
                            return [
                                'label' => $this->groupActivityLabel((string) $row->type, (string) $row->action),
                                'total' => 1,
                            ];
                        })
                        ->groupBy('label')
                        ->map(function ($rows, string $label): array {
                            return [
                                'label' => $label,
                                'total' => $rows->count(),
                            ];
                        })
                        ->sortByDesc('total')
                        ->values();
                });

            $selectedUserDailyAccess = collect(range(1, $monthStart->daysInMonth))->map(function (int $day) use ($monthStart, $selectedUserDailyAccessRows, $selectedUserActivityRows) {
                $date = $monthStart->copy()->day($day);
                $dateKey = $date->toDateString();
                $dailyRow = $selectedUserDailyAccessRows->get($dateKey, collect());
                $activities = $selectedUserActivityRows->get($dateKey, collect())
                    ->map(function ($row): array {
                        return [
                            'label' => (string) $row['label'],
                            'total' => (int) $row['total'],
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'day' => $day,
                    'date' => $dateKey,
                    'label' => $date->locale('id')->translatedFormat('d M'),
                    'total_logs' => (int) $dailyRow->count(),
                    'activity_breakdown' => $activities,
                ];
            })->values()->all();
        }

        $impactSummary = [
            [
                'label' => 'Dokumen administrasi',
                'count' => (clone $baseQuery)->whereIn('type', ['sk_kpa', 'spk', 'bast'])->count(),
                'description' => 'Pembuatan, revisi, unggah dokumen bertandatangan, dan finalisasi dokumen',
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
                'user_name' => $selectedUserName,
            ],
            'state_url' => $this->buildStateUrl([
                'bulan' => $selectedMonth,
                'user_name' => $selectedUserName,
            ]),
            'month_label' => $monthName,
            'report_period' => sprintf('%s %s', $monthName, $activeYear),
            'summary' => [
                'active_users' => $activeUsers,
                'total_logs' => $totalLogs,
                'active_days' => $activeDays,
                'average_logs_per_day' => $averageLogsPerDay,
                'administrative_actions' => (clone $baseQuery)->whereIn('type', ['kegiatan', 'alokasi', 'mitra', 'spk', 'sk_kpa', 'bast', 'pengajuan_pulsa'])->count(),
                'system_actions' => (clone $baseQuery)->whereIn('type', ['auth', 'system', 'user'])->count(),
            ],
            'daily_access' => $dailyAccess,
            'type_summary' => $typeSummary,
            'top_actions' => $topActions,
            'all_user_activity' => $allUserActivity,
            'top_users' => $topUsers,
            'user_name_options' => $userNameOptions,
            'selected_user_name' => $selectedUserName,
            'selected_user_summary' => $selectedUserSummary,
            'selected_user_daily_access' => $selectedUserDailyAccess,
            'impact_summary' => $impactSummary,
        ];
    }

    /**
     * @return array{bulan: string, user_name: string|null}
     */
    private function resolveFilters(Request $request): array
    {
        $filters = [
            'bulan' => $request->input('bulan', now()->format('m')),
            'user_name' => $request->input('user_name'),
        ];

        if ($request->filled('state')) {
            $decryptedFilters = $this->decodeState((string) $request->input('state'));

            if ($decryptedFilters !== null) {
                $filters = array_merge($filters, $decryptedFilters);
            }
        }

        return [
            'bulan' => $this->normalizeMonthValue($filters['bulan'] ?? null),
            'user_name' => $this->normalizeUserNameValue($filters['user_name'] ?? null),
        ];
    }

    /**
     * @param array{bulan: string, user_name: string|null} $filters
     */
    private function buildStateUrl(array $filters): string
    {
        return route('monitoring.penggunaan-aplikasi', [
            'state' => Crypt::encryptString(json_encode($filters, JSON_THROW_ON_ERROR)),
        ]);
    }

    /**
     * @return array{bulan?: string, user_name?: string|null}|null
     */
    private function decodeState(string $state): ?array
    {
        try {
            $decoded = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        return [
            'bulan' => $decoded['bulan'] ?? null,
            'user_name' => $decoded['user_name'] ?? null,
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

    private function normalizeUserNameValue(mixed $userName): ?string
    {
        if ($userName === null) {
            return null;
        }

        $raw = trim((string) $userName);

        return $raw !== '' ? $raw : null;
    }

    private function groupActivityLabel(string $type, string $action): string
    {
        if ($type === 'kegiatan') {
            return 'Kelola Kegiatan';
        }

        return $this->labelForType($type);
    }

    private function labelForType(string $type): string
    {
        return match ($type) {
            'auth' => 'Autentikasi',
            'system' => 'Sistem',
            'user' => 'Kelola Pengguna',
            'kegiatan' => 'Kelola Kegiatan',
            'alokasi' => 'Kelola Alokasi',
            'mitra' => 'Kelola Mitra',
            'spk' => 'Kelola SPK',
            'sk_kpa' => 'Kelola SK KPA',
            'bast' => 'Kelola BAST',
            'pengajuan_pulsa' => 'Kelola Pulsa',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
