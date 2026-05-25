<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityLogExport;
use App\Http\Requests\Settings\UpdateMaintenanceRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Traits\EncryptsFilterParams;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemSettingsController
{
    use EncryptsFilterParams;

    private const SSO_SYNC_CACHE_KEY = 'settings:sso_sync_enabled';

    public function __construct(
        private DatabaseBackupService $backupService
    ) {}

    public function index(): Response
    {
        $maintenance = app()->isDownForMaintenance();
        $message = Storage::exists('framework/maintenance-message.txt')
            ? Storage::get('framework/maintenance-message.txt')
            : Config::get('app.maintenance_message');
        $ssoSyncEnabled = Cache::get(self::SSO_SYNC_CACHE_KEY);

        if (! is_bool($ssoSyncEnabled)) {
            $ssoSyncEnabled = (bool) config('services.sso.sync_enabled', true);
        }

        return Inertia::render('Admin/SystemSettings', [
            'maintenance' => $maintenance,
            'message' => $message,
            'sso_sync_enabled' => $ssoSyncEnabled,
            'session_lifetime' => (int) config('session.lifetime', 120),
        ]);
    }

    public function updateSsoSync(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['enabled'];

        Cache::forever(self::SSO_SYNC_CACHE_KEY, $enabled);

        ActivityLog::logSystem(
            'Pengaturan SSO Sync Diperbarui',
            'Sinkronisasi sesi SSO '.($enabled ? 'diaktifkan' : 'dinonaktifkan').'.',
            'info',
            [
                'enabled' => $enabled,
                'user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'session_lifetime' => (int) config('session.lifetime', 120),
        ]);
    }

    public function activityLog(Request $request): Response
    {
        $query = ActivityLog::with('user');
        $filters = [
            'status' => $request->input('status'),
            'user' => $request->input('user'),
            'date' => $request->input('date'),
        ];
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['user']) {
            $query->where('user_id', $filters['user']);
        }
        if ($filters['date']) {
            $query->whereDate('created_at', $filters['date']);
        }

        // Get page from request, default to 1
        $page = $request->input('page', 1);

        // Paginate with 50 items per page, sorted by latest first
        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'page', $page)
            ->through(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user?->name ?? 'System',
                    'user_id' => $log->user_id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'status' => $log->status,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'time' => $log->created_at?->format('d M Y H:i:s').' WIB',
                    'created_at' => $log->created_at?->toISOString(),
                    'properties' => $log->metadata,
                ];
            });
        $users = User::orderBy('name')->get(['id', 'name']);

        // Encrypt filter values for frontend
        $encryptedFilters = $this->encryptFilterParams($filters);

        // Encrypt logs data for secure transmission
        $encryptedLogs = encryptData($logs->items());

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $encryptedLogs,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'filters' => $encryptedFilters,
            'users' => $users,
        ]);
    }

    /**
     * Export activity log to Excel.
     */
    public function exportActivityLog(Request $request): BinaryFileResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'user' => $request->input('user'),
            'date' => $request->input('date'),
        ];

        // Remove null values
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        $filename = 'activity-log-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new ActivityLogExport($filters), $filename);
    }

    public function databaseStatus(): Response
    {
        // Get DB connection info
        $connection = Config::get('database.default');
        $status = 'Connected';
        $tables = [];
        $tableCount = 0;
        $dbSize = 0;
        $lastBackup = null;
        try {
            $dbName = DB::getDatabaseName();
            $tables = DB::select('SELECT table_name AS `name`, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `size_mb`, table_rows AS `rows` FROM information_schema.tables WHERE table_schema = ? ORDER BY (data_length + index_length) DESC', [$dbName]);
            $tableCount = count($tables);
            $dbSize = array_sum(array_map(fn ($t) => (float) ($t->size_mb), $tables));
        } catch (\Exception $e) {
            $status = 'Error: '.$e->getMessage();
        }
        // Find last backup file
        $backups = $this->backupService->listBackups();
        $lastBackup = $backups[0] ?? null;
        $lastBackupFile = $lastBackup['filename'] ?? null;

        return Inertia::render('Admin/DatabaseStatus', [
            'connection' => $connection,
            'status' => $status,
            'size' => round($dbSize, 2).' MB',
            'tables' => $tables,
            'tableCount' => $tableCount,
            'lastBackup' => $lastBackup['created_at'] ?? null,
            'lastBackupFile' => $lastBackupFile,
        ]);
    }

    /**
     * Trigger a database backup and return the result.
     * Using PHP-based backup (compatible with shared hosting)
     */
    public function backupDatabase(Request $request)
    {
        try {
            $result = $this->backupService->createBackup();

            if ($result['success']) {
                // Create log entry
                $log = ActivityLog::create([
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()?->name ?? 'System',
                    'action' => 'Backup Database',
                    'type' => 'system',
                    'description' => 'Database berhasil di-backup: '.$result['filename'].' ('.$result['size_formatted'].')',
                    'status' => 'success',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'filename' => $result['filename'],
                        'size' => $result['size'],
                        'method' => 'php_native',
                    ],
                ]);

                // Ensure log is committed to database before returning response
                if ($log) {
                    $log->refresh();
                }

                return response()->json([
                    'success' => true,
                    'file' => $result['filename'],
                    'size' => $result['size_formatted'],
                ]);
            }

            ActivityLog::logError(
                'Backup Database',
                'system',
                'Gagal membuat backup database: '.($result['error'] ?? 'Unknown error'),
                ['error' => $result['error'] ?? null]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            ActivityLog::logError(
                'Backup Database',
                'system',
                'Error saat backup database: '.$e->getMessage(),
                ['exception' => get_class($e)]
            );

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore database from a backup file.
     * Using PHP-based restore (compatible with shared hosting)
     */
    public function restoreDatabase(Request $request)
    {
        $file = $request->input('file');

        try {
            $result = $this->backupService->restoreBackup($file);

            if ($result['success']) {
                ActivityLog::logSystem(
                    'Restore Database',
                    'Database berhasil di-restore dari backup: '.$file,
                    'success',
                    ['filename' => $file, 'method' => 'php_native']
                );

                return response()->json($result);
            }

            ActivityLog::logError(
                'Restore Database',
                'system',
                'Gagal restore database dari: '.$file.' - '.($result['error'] ?? 'Unknown error'),
                ['filename' => $file, 'error' => $result['error'] ?? null]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            ActivityLog::logError(
                'Restore Database',
                'system',
                'Error saat restore database: '.$e->getMessage(),
                ['filename' => $file, 'exception' => get_class($e)]
            );

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update maintenance mode and message.
     */
    public function updateMaintenance(UpdateMaintenanceRequest $request)
    {
        $enabled = $request->boolean('enabled');
        $message = $request->input('message');

        // Save message to storage/framework/maintenance-message.txt for 503 page
        if ($message) {
            Storage::put('framework/maintenance-message.txt', $message);
        } else {
            if (Storage::exists('framework/maintenance-message.txt')) {
                Storage::delete('framework/maintenance-message.txt');
            }
        }

        // Set config value for fallback (optional, for config cache)
        // config(['app.maintenance_message' => $message]);

        // Enable/disable maintenance mode
        if ($enabled) {
            // Generate a bypass secret token
            $secret = Config::get('app.maintenance_bypass_secret') ?: Str::random(40);

            // Use Laravel's built-in maintenance mode with secret
            Artisan::call('down', [
                '--refresh' => 15, // allow refresh every 15s
                '--secret' => $secret,
            ]);

            // Remove prerendered template to allow middleware to work
            $this->removeMaintenancePrerenderTemplate();

            // Create bypass cookie for current admin user
            $bypassCookie = MaintenanceModeBypassCookie::create($secret);

            ActivityLog::logSystem(
                'Mode Maintenance Diaktifkan',
                'Sistem diubah ke mode maintenance'.($message ? ': '.$message : ''),
                'warning',
                ['message' => $message, 'user_id' => Auth::id()]
            );

            // Return response with bypass cookie
            return response()->json([
                'success' => true,
                'maintenance' => $enabled,
                'message' => $message,
            ])->withCookie($bypassCookie);
        } else {
            Artisan::call('up');

            ActivityLog::logSystem(
                'Mode Maintenance Dinonaktifkan',
                'Sistem kembali normal dan dapat diakses',
                'success',
                ['user_id' => Auth::id()]
            );
        }

        // Clear config cache if needed
        Cache::flush();

        return response()->json([
            'success' => true,
            'maintenance' => $enabled,
            'message' => $message,
        ]);
    }

    /**
     * Remove prerendered template from maintenance mode file
     */
    private function removeMaintenancePrerenderTemplate(): void
    {
        $downPath = storage_path('framework/down');

        if (! file_exists($downPath)) {
            return;
        }

        $data = json_decode(file_get_contents($downPath), true);

        if (! is_array($data) || ! array_key_exists('template', $data)) {
            return;
        }

        unset($data['template']);

        file_put_contents($downPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
