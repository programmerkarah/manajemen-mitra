import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { AlertTriangle, CircleAlert, Database, Download } from 'lucide-react';
import React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Status Database', href: '/admin/database-status' },
];

export default function DatabaseStatus() {
    // Define expected prop types
    type Table = { name: string; rows: number; size_mb: number };
    type PageProps = {
        status?: string;
        connection?: string;
        lastBackup?: string;
        tables?: Table[];
    };
    const { props } = usePage<PageProps>();
    const [restoreFile, setRestoreFile] = React.useState<string | null>(null);
    const [loading, setLoading] = React.useState(false);
    const [backupResult, setBackupResult] = React.useState<string | null>(null);
    const [restoreResult, setRestoreResult] = React.useState<string | null>(
        null,
    );
    const [backups, setBackups] = React.useState<
        Array<{ name: string; size: string; modified: string }>
    >([]);
    const [showBackupConfirm, setShowBackupConfirm] = React.useState(false);
    const [showRestoreConfirm, setShowRestoreConfirm] = React.useState(false);

    React.useEffect(() => {
        fetch('/admin/database-list-backups')
            .then((res) => res.json())
            .then((data) => {
                if (data.success) setBackups(data.backups);
            });
    }, [props.lastBackup]);

    function getCsrfToken() {
        if (typeof document !== 'undefined') {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta?.getAttribute('content') || '';
        }
        return '';
    }

    const handleBackup = async () => {
        setLoading(true);
        setBackupResult(null);
        setRestoreResult(null);
        try {
            const res = await fetch('/admin/database-backup', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });
            const data = await res.json();
            if (data.success) {
                setBackupResult('Backup berhasil: ' + data.file);
                setShowBackupConfirm(false);
                setTimeout(() => window.location.reload(), 1200);
            } else {
                setBackupResult(
                    'Backup gagal: ' + (data.error || 'Unknown error'),
                );
            }
        } catch (e: unknown) {
            const error = e as Error;
            setBackupResult('Backup gagal: ' + error.message);
        }
        setLoading(false);
    };

    const handleRestore = async () => {
        if (!restoreFile) return;
        setLoading(true);
        setRestoreResult(null);
        setBackupResult(null);
        try {
            const res = await fetch('/admin/database-restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ file: restoreFile }),
            });
            const data = await res.json();
            if (data.success) {
                setRestoreResult('Restore berhasil.');
                setShowRestoreConfirm(false);
                setTimeout(() => window.location.reload(), 1200);
            } else {
                setRestoreResult(
                    'Restore gagal: ' + (data.error || 'Unknown error'),
                );
            }
        } catch (e: unknown) {
            const error = e as Error;
            setRestoreResult('Restore gagal: ' + error.message);
        }
        setLoading(false);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Database Management" />
            <div
                className="mb-6 overflow-hidden rounded-xl"
                style={{
                    background:
                        'linear-gradient(90deg, #2e1065 0%, #1e293b 100%)',
                }}
            >
                <div className="flex items-center gap-4 p-6">
                    <Database className="h-10 w-10 text-white" />
                    <div>
                        <h1 className="mb-1 text-2xl font-bold text-white">
                            Database Management
                        </h1>
                        <div className="text-gray-300">
                            Kelola backup dan restore database aplikasi
                        </div>
                    </div>
                </div>
            </div>
            <div className="mb-6 grid gap-4 md:grid-cols-3">
                <div className="flex flex-col gap-2 rounded-xl border border-neutral-800 bg-cyan-100 p-4 dark:bg-neutral-900">
                    <div className="mb-1 text-xs text-gray-800 dark:text-gray-300">
                        Status Database
                    </div>
                    <div className="text-lg font-bold text-green-400">
                        {props.status ?? '-'}
                    </div>
                    <div className="text-xs text-gray-800 dark:text-gray-300">
                        Koneksi database aktif dan berjalan normal
                    </div>
                </div>
                <div className="flex flex-col gap-2 rounded-xl border border-neutral-800 bg-cyan-100 p-4 dark:bg-neutral-900">
                    <div className="mb-1 text-xs text-gray-800 dark:text-gray-300">
                        Database Info
                    </div>
                    <div className="font-mono text-gray-800 dark:text-white">
                        {props.connection ?? '-'}
                    </div>
                    <div className="text-xs text-gray-800 dark:text-gray-300">
                        Host: 127.0.0.1 / User: {props.connection ?? '-'}
                    </div>
                </div>
                <div className="flex flex-col gap-2 rounded-xl border border-neutral-800 bg-cyan-100 p-4 dark:bg-neutral-900">
                    <div className="mb-1 text-xs text-gray-800 dark:text-gray-300">
                        Last Backup
                    </div>
                    <div className="text-lg font-bold text-gray-800 dark:text-white">
                        {props.lastBackup ?? '-'}
                    </div>
                    <div className="text-xs text-gray-800 dark:text-gray-300">
                        {backups[0]?.size_formatted} - {backups[0]?.filename}
                    </div>
                </div>
            </div>
            {/* Backup Database */}
            <div className="mb-6 rounded-xl border border-neutral-800 bg-cyan-100 p-6 dark:bg-neutral-900">
                <div className="mb-2 font-semibold text-gray-800 dark:text-white">
                    Backup Database
                </div>
                <div className="mb-4 text-xs text-gray-400">
                    Buat backup lengkap dari database aplikasi untuk keperluan
                    recovery atau migrasi data.
                </div>
                <div className="mb-4 flex items-center gap-2 rounded bg-neutral-800 p-3 text-blue-200">
                    <CircleAlert className="h-5 w-5" />
                    Proses backup akan membuat file database yang dapat diunduh
                    dan disimpan dengan aman.
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => setShowBackupConfirm(true)}
                        disabled={loading}
                        className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        Create Backup
                    </button>
                    {backups[0] && (
                        <a
                            href={`/storage/db_backup/${encodeURIComponent(backups[0].filename)}`}
                            className="btn btn-success flex items-center gap-2 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                            download
                        >
                            <Download className="h-4 w-4" /> Download Latest
                            Backup
                        </a>
                    )}
                </div>
                {showBackupConfirm && (
                    <div className="mt-4 rounded border border-blue-700 bg-neutral-800 p-4">
                        <div className="mb-2 font-semibold text-blue-200">
                            Konfirmasi Backup
                        </div>
                        <div className="mb-2 text-xs text-gray-300">
                            Backup akan membuat salinan seluruh data database
                            saat ini. Lanjutkan?
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={handleBackup}
                                disabled={loading}
                                className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                Lanjutkan
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowBackupConfirm(false)}
                                className="rounded border border-blue-600 bg-white px-4 py-2 text-blue-600 hover:bg-blue-50 dark:bg-neutral-900"
                            >
                                Batal
                            </button>
                        </div>
                        {backupResult && (
                            <div className="mt-2 text-sm text-green-400">
                                {backupResult}
                            </div>
                        )}
                    </div>
                )}
            </div>
            {/* Restore Database */}
            <div className="mb-6 rounded-xl border border-neutral-800 bg-cyan-100 p-6 dark:bg-neutral-900">
                <div className="mb-2 font-semibold text-gray-800 dark:text-white">
                    Restore Database
                </div>
                <div className="mb-4 text-xs text-gray-800 dark:text-gray-300">
                    Restore database dari file backup yang telah disimpan
                    sebelumnya.
                </div>
                <div className="mb-2 flex items-center gap-2">
                    <Select
                        value={restoreFile ?? ''}
                        onValueChange={setRestoreFile}
                        disabled={loading}
                    >
                        <SelectTrigger
                            className="w-64"
                            aria-label="Pilih File Backup"
                        >
                            <SelectValue placeholder="Pilih File Backup" />
                        </SelectTrigger>
                        <SelectContent>
                            {backups.length === 0 ? (
                                <div className="px-3 py-2 text-sm text-gray-400">
                                    Tidak ada backup
                                </div>
                            ) : (
                                backups.map((b) => (
                                    <SelectItem
                                        key={b.filename}
                                        value={b.filename}
                                    >
                                        {b.filename} - {b.size_formatted} -{' '}
                                        {b.created_at_formatted}
                                    </SelectItem>
                                ))
                            )}
                        </SelectContent>
                    </Select>
                    <button
                        type="button"
                        onClick={() => setShowRestoreConfirm(true)}
                        disabled={!restoreFile || loading}
                        className="rounded bg-yellow-600 px-4 py-2 text-white transition-all hover:bg-yellow-700 disabled:opacity-50"
                    >
                        Restore from Backup
                    </button>
                </div>
                <div className="mb-2 flex items-center gap-2 rounded bg-yellow-900 p-3 text-yellow-200">
                    <AlertTriangle className="h-5 w-5" />
                    Proses restore akan mengganti seluruh data database saat
                    ini. Pastikan Anda memiliki file backup data sebelum
                    melakukan restore.
                </div>
                {showRestoreConfirm && (
                    <div className="mt-4 rounded border border-yellow-700 bg-neutral-800 p-4">
                        <div className="mb-2 font-semibold text-yellow-200">
                            Konfirmasi Restore
                        </div>
                        <div className="mb-2 text-xs text-gray-300">
                            Restore akan menimpa seluruh data database.
                            Lanjutkan?
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={handleRestore}
                                disabled={loading}
                                className="rounded bg-yellow-600 px-4 py-2 text-white hover:bg-yellow-700 disabled:opacity-50"
                            >
                                Lanjutkan
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowRestoreConfirm(false)}
                                className="rounded border border-yellow-600 bg-white px-4 py-2 text-yellow-600 hover:bg-yellow-50 dark:bg-neutral-900"
                            >
                                Batal
                            </button>
                        </div>
                        {restoreResult && (
                            <div className="mt-2 text-sm text-green-400">
                                {restoreResult}
                            </div>
                        )}
                    </div>
                )}
            </div>
            {/* Informasi Database */}
            <div className="mb-6 rounded-xl border border-neutral-800 bg-cyan-100 p-6 dark:bg-neutral-900">
                <div className="mb-2 font-semibold text-gray-800 dark:text-white">
                    Informasi Database
                </div>
                <div className="grid gap-4 text-sm md:grid-cols-4">
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">
                            Host
                        </div>
                        <div className="font-mono text-gray-800 dark:text-white">
                            127.0.0.1
                        </div>
                    </div>
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">
                            Port
                        </div>
                        <div className="font-mono text-gray-800 dark:text-white">
                            3306
                        </div>
                    </div>
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">
                            Username
                        </div>
                        <div className="font-mono text-gray-800 dark:text-gray-300">
                            {props.connection ?? '-'}
                        </div>
                    </div>
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">
                            Driver
                        </div>
                        <div className="font-mono text-gray-800 dark:text-gray-300">
                            MySQL
                        </div>
                    </div>
                </div>
            </div>
            {/* Tabel Database */}
            <div className="mb-6 rounded-xl border border-neutral-800 bg-cyan-100 p-6 dark:bg-neutral-900">
                <div className="mb-2 font-semibold text-gray-800 dark:text-white">
                    Tabel Database
                </div>
                <div className="mt-2 overflow-x-auto">
                    <DatabaseTable tables={props.tables} />
                </div>
            </div>
        </AppLayout>
    );
}

function DatabaseTable({
    tables,
}: {
    tables: { name: string; rows: number; size_mb: number }[] | undefined;
}) {
    return (
        <div className="mt-2 overflow-x-auto">
            <table className="w-full overflow-hidden rounded-xl border border-neutral-200 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
                <thead className="bg-neutral-100 dark:bg-neutral-800">
                    <tr>
                        <th className="border-b border-neutral-200 px-4 py-3 text-left font-semibold text-gray-700 dark:border-neutral-700 dark:text-gray-200">
                            Table
                        </th>
                        <th className="border-b border-neutral-200 px-4 py-3 text-right font-semibold text-gray-700 dark:border-neutral-700 dark:text-gray-200">
                            Rows
                        </th>
                        <th className="border-b border-neutral-200 px-4 py-3 text-right font-semibold text-gray-700 dark:border-neutral-700 dark:text-gray-200">
                            Size (MB)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {Array.isArray(tables) && tables.length > 0 ? (
                        tables.map((t) => (
                            <tr
                                key={t.name}
                                className="transition-colors hover:bg-blue-50 dark:hover:bg-neutral-800"
                            >
                                <td className="border-b border-neutral-100 px-4 py-2 font-mono text-gray-800 dark:border-neutral-800 dark:text-white">
                                    {t.name}
                                </td>
                                <td className="border-b border-neutral-100 px-4 py-2 text-right text-gray-800 dark:border-neutral-800 dark:text-white">
                                    {t.rows}
                                </td>
                                <td className="border-b border-neutral-100 px-4 py-2 text-right text-gray-800 dark:border-neutral-800 dark:text-white">
                                    {t.size_mb}
                                </td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td
                                colSpan={3}
                                className="py-4 text-center text-gray-400"
                            >
                                No tables found
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
