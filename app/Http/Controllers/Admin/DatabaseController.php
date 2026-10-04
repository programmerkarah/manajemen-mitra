<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backupService,
    ) {}

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

    public function listBackups()
    {
        return response()->json([
            'success' => true,
            'backups' => $this->backupService->listBackups(),
        ]);
    }
}
