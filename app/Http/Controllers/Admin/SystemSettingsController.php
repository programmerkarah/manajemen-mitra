<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Settings\UpdateMaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController
{
    public function index(): Response
    {
        $maintenance = app()->isDownForMaintenance();
        $message = Storage::exists('framework/maintenance-message.txt')
            ? Storage::get('framework/maintenance-message.txt')
            : config('app.maintenance_message');

        return Inertia::render('Admin/SystemSettings', [
            'maintenance' => $maintenance,
            'message' => $message,
        ]);
    }

    public function activityLog(Request $request): Response
    {
        $query = \App\Models\ActivityLog::with('user');
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
        $logs = $query->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user?->name ?? 'System',
                    'action' => $log->action,
                    'status' => $log->status,
                    'meta' => $log->meta,
                    'time' => $log->created_at?->format('Y-m-d H:i'),
                ];
            });
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $users,
        ]);
    }

    public function databaseStatus(): Response
    {
        // Get DB connection info
        $connection = config('database.default');
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
        // Find last backup file in backup dir
        $backupDir = public_path('storage/db_backup');
        $lastBackupFile = null;
        if (is_dir($backupDir)) {
            $files = array_filter(scandir($backupDir), fn ($f) => preg_match('/backup_.*\\.sql$/', $f));
            if ($files) {
                $files = array_map(fn ($f) => [
                    'file' => $f,
                    'time' => filemtime($backupDir.DIRECTORY_SEPARATOR.$f),
                ], $files);
                usort($files, fn ($a, $b) => $b['time'] <=> $a['time']);
                $lastBackupFile = $files[0]['file'];
                $lastBackup = date('Y-m-d H:i', $files[0]['time']);
            }
        }

        return Inertia::render('Admin/DatabaseStatus', [
            'connection' => $connection,
            'status' => $status,
            'size' => round($dbSize, 2).' MB',
            'tables' => $tables,
            'tableCount' => $tableCount,
            'lastBackup' => $lastBackup,
            'lastBackupFile' => $lastBackupFile,
        ]);
    }

    /**
     * Trigger a database backup and return the result.
     */
    public function backupDatabase(Request $request)
    {
        $dbName = DB::getDatabaseName();
        $filename = 'manajemen_mitra_backup_'.date('Ymd_His').'.sql';
        $backupDir = public_path('storage/db_backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $filePath = $backupDir.DIRECTORY_SEPARATOR.$filename;
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $mysqldump = 'E:/xampp/mysql/bin/mysqldump.exe';
        if (! file_exists($mysqldump)) {
            $mysqldump = 'mysqldump'; // fallback jika sudah ada di PATH
        }
        $cmd = sprintf('"%s" -h%s -P%s -u%s %s %s > %s',
            $mysqldump,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass ? '-p'.escapeshellarg($pass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );
        $result = null;
        $output = null;
        try {
            $cmdWithError = $cmd.' 2>&1';
            exec($cmdWithError, $output, $result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
        if ($result === 0 && file_exists($filePath)) {
            return response()->json(['success' => true, 'file' => $filename]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Backup failed',
            'cmd' => $cmd,
            'output' => $output,
            'result' => $result,
        ]);
    }

    /**
     * Restore database from a backup file.
     */
    public function restoreDatabase(Request $request)
    {
        $file = $request->input('file');
        $backupDir = public_path('storage/db_backup');
        $filePath = $backupDir.DIRECTORY_SEPARATOR.$file;
        if (! file_exists($filePath)) {
            return response()->json(['success' => false, 'error' => 'File not found']);
        }
        $dbName = DB::getDatabaseName();
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $mysql = 'E:/xampp/mysql/bin/mysql.exe';
        if (! file_exists($mysql)) {
            $mysql = 'mysql';
        }
        $cmd = sprintf('"%s" -h%s -P%s -u%s %s %s < %s',
            $mysql,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass ? '-p'.escapeshellarg($pass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );
        $result = null;
        $output = null;
        try {
            exec($cmd, $output, $result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
        if ($result === 0) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'error' => 'Restore failed', 'cmd' => $cmd]);
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
            // Use Laravel's built-in maintenance mode
            Artisan::call('down', [
                '--refresh' => 15, // allow refresh every 15s
                '--render' => 'errors.503',
            ]);
        } else {
            Artisan::call('up');
        }

        // Clear config cache if needed
        Cache::flush();

        return response()->json([
            'success' => true,
            'maintenance' => $enabled,
            'message' => $message,
        ]);
    }
}
