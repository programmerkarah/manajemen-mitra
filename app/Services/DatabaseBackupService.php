<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseBackupService
{
    /**
     * Create database backup using PHP (compatible with shared hosting)
     *
     * @return array{success: bool, filename?: string, path?: string, size?: int, error?: string}
     */
    public function createBackup(): array
    {
        try {
            $filename = 'backup_'.date('Ymd_His').'.sql';
            $backupDir = public_path('storage/db_backup');

            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filePath = $backupDir.DIRECTORY_SEPARATOR.$filename;
            $dbName = DB::getDatabaseName();

            // Buka file untuk menulis
            $handle = fopen($filePath, 'w');
            if (! $handle) {
                return ['success' => false, 'error' => 'Tidak dapat membuat file backup'];
            }

            // Tulis header SQL
            fwrite($handle, "-- Database Backup: {$dbName}\n");
            fwrite($handle, '-- Generated: '.date('Y-m-d H:i:s')."\n");
            fwrite($handle, '-- MySQL version: '.DB::selectOne('SELECT VERSION() as version')->version."\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = '+07:00';\n\n");

            // Ambil semua tabel
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_'.$dbName;

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                if ($this->shouldSkipTable($tableName)) {
                    continue;
                }

                // Backup struktur tabel
                fwrite($handle, "\n-- Table: {$tableName}\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                $createTable = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
                fwrite($handle, $createTable->{'Create Table'}.";\n\n");

                // Backup data tabel (jika ada)
                $rows = DB::table($tableName)->get();

                if ($rows->count() > 0) {
                    fwrite($handle, "-- Data untuk tabel `{$tableName}`\n");

                    foreach ($rows as $row) {
                        $columns = array_keys((array) $row);
                        $values = array_map(function ($value) {
                            if ($value === null) {
                                return 'NULL';
                            }

                            return "'".addslashes($value)."'";
                        }, array_values((array) $row));

                        $columnsStr = '`'.implode('`, `', $columns).'`';
                        $valuesStr = implode(', ', $values);

                        fwrite($handle, "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES ({$valuesStr});\n");
                    }

                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            $fileSize = filesize($filePath);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filePath,
                'size' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Gagal membuat backup: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Restore database from backup file
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function restoreBackup(string $filename): array
    {
        try {
            $backupDir = public_path('storage/db_backup');
            $filePath = $backupDir.DIRECTORY_SEPARATOR.$filename;

            if (! file_exists($filePath)) {
                return ['success' => false, 'error' => 'File backup tidak ditemukan'];
            }

            // Set timezone untuk memastikan timestamp diinterpretasi dengan benar
            DB::statement("SET time_zone = '+07:00'");

            // Baca file SQL
            $sql = file_get_contents($filePath);

            // Parse SQL statements properly (handle multi-line statements)
            $statements = $this->parseSqlStatements($sql);

            // Execute statements without transaction
            // DDL statements (CREATE, DROP, ALTER) cause implicit commit in MySQL
            // So using transaction doesn't provide rollback capability anyway
            foreach ($statements as $statement) {
                if (! empty(trim($statement))) {
                    DB::statement($statement);
                }
            }

            return [
                'success' => true,
                'message' => 'Database berhasil di-restore dari backup: '.$filename,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Gagal restore database: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Parse SQL file into individual statements
     * Handles multi-line statements and comments properly
     */
    private function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $currentStatement = '';
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Skip comment-only lines
            if (str_starts_with($line, '--')) {
                continue;
            }

            // Add line to current statement
            $currentStatement .= $line.' ';

            // Check if statement is complete (ends with semicolon)
            if (str_ends_with($line, ';')) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
        }

        // Add any remaining statement
        if (! empty(trim($currentStatement))) {
            $statements[] = trim($currentStatement);
        }

        return $statements;
    }

    /**
     * List all backup files
     */
    public function listBackups(): array
    {
        $backupDir = public_path('storage/db_backup');
        $backups = [];

        if (! is_dir($backupDir)) {
            return $backups;
        }

        $files = array_filter(
            scandir($backupDir),
            fn ($f) => preg_match('/^backup_.*\.sql$/', $f)
        );

        foreach ($files as $file) {
            $filePath = $backupDir.DIRECTORY_SEPARATOR.$file;
            $fileTime = filemtime($filePath);
            $timeAgo = $this->getTimeAgo($fileTime);
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filePath),
                'size_formatted' => $this->formatBytes(filesize($filePath)),
                'created_at' => date('Y-m-d H:i:s', $fileTime),
                'created_at_formatted' => date('d M Y H:i:s', $fileTime).' ('.$timeAgo.' ago)',
                'created_timestamp' => $fileTime,
            ];
        }

        // Sort by created time (newest first)
        usort($backups, fn ($a, $b) => $b['created_timestamp'] <=> $a['created_timestamp']);

        return $backups;
    }

    /**
     * Delete a backup file
     *
     * @return array{success: bool, error?: string}
     */
    public function deleteBackup(string $filename): array
    {
        try {
            $backupDir = storage_path('app/backups');
            $filePath = $backupDir.DIRECTORY_SEPARATOR.$filename;

            if (! file_exists($filePath)) {
                return ['success' => false, 'error' => 'File tidak ditemukan'];
            }

            // Security: ensure filename doesn't contain path traversal
            if (str_contains($filename, '..') || str_contains($filename, '/')) {
                return ['success' => false, 'error' => 'Nama file tidak valid'];
            }

            unlink($filePath);

            return ['success' => true];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Gagal menghapus backup: '.$e->getMessage()];
        }
    }

    /**
     * Tentukan apakah tabel harus di-skip saat backup
     */
    private function shouldSkipTable(string $tableName): bool
    {
        // Skip Laravel session, cache, dan migration tables jika diinginkan
        $skipTables = [
            'sessions',
            'cache',
            'cache_locks',
            'users',
            'activity_logs',
        ];

        return in_array($tableName, $skipTables);
    }

    /**
     * Format bytes ke human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Calculate human-readable time difference
     */
    private function getTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff.' second'.($diff != 1 ? 's' : '');
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);

            return $minutes.' minute'.($minutes != 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);

            return $hours.' hour'.($hours != 1 ? 's' : '');
        } else {
            $days = floor($diff / 86400);

            return $days.' day'.($days != 1 ? 's' : '');
        }
    }
}
