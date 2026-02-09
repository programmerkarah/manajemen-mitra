import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { Download, Database, AlertTriangle, CheckCircle, CircleAlert } from 'lucide-react';
import React from 'react';
import {
    Select,
    SelectTrigger,
    SelectContent,
    SelectItem,
    SelectValue,
} from "@/components/ui/select";
import { BreadcrumbItem } from '@/types';

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
    const [restoreResult, setRestoreResult] = React.useState<string | null>(null);
    const [backups, setBackups] = React.useState<any[]>([]);
    const [showBackupConfirm, setShowBackupConfirm] = React.useState(false);
    const [showRestoreConfirm, setShowRestoreConfirm] = React.useState(false);

    React.useEffect(() => {
        fetch('/admin/database-list-backups')
            .then(res => res.json())
            .then(data => {
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
                setBackupResult('Backup gagal: ' + (data.error || 'Unknown error'));
            }
        } catch (e: any) {
            setBackupResult('Backup gagal: ' + e.message);
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
                setRestoreResult('Restore gagal: ' + (data.error || 'Unknown error'));
            }
        } catch (e: any) {
            setRestoreResult('Restore gagal: ' + e.message);
        }
        setLoading(false);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Database Management" />
            <div className="rounded-xl overflow-hidden mb-6" style={{background: 'linear-gradient(90deg, #2e1065 0%, #1e293b 100%)'}}>
                <div className="p-6 flex items-center gap-4">
                    <Database className="w-10 h-10 text-white" />
                    <div>
                        <h1 className="text-2xl font-bold text-white mb-1">Database Management</h1>
                        <div className="text-gray-300">Kelola backup dan restore database aplikasi</div>
                    </div>
                </div>
            </div>
            <div className="grid md:grid-cols-3 gap-4 mb-6">
                <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-4 flex flex-col gap-2 border border-neutral-800">
                    <div className="text-xs text-gray-800 dark:text-gray-300 mb-1">Status Database</div>
                    <div className="text-green-400 font-bold text-lg">{props.status ?? '-'}</div>
                    <div className="text-xs text-gray-800 dark:text-gray-300">Koneksi database aktif dan berjalan normal</div>
                </div>
                <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-4 flex flex-col gap-2 border border-neutral-800">
                    <div className="text-xs text-gray-800 dark:text-gray-300 mb-1">Database Info</div>
                    <div className="font-mono text-gray-800 dark:text-white">{props.connection ?? '-'}</div>
                    <div className="text-xs text-gray-800 dark:text-gray-300">Host: 127.0.0.1 / User: {props.connection ?? '-'}</div>
                </div>
                <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-4 flex flex-col gap-2 border border-neutral-800">
                    <div className="text-xs text-gray-800 dark:text-gray-300 mb-1">Last Backup</div>
                    <div className="text-gray-800 dark:text-white font-bold text-lg">{props.lastBackup ?? '-'}</div>
                    <div className="text-xs text-gray-800 dark:text-gray-300">{backups[0]?.size_formatted} - {backups[0]?.filename}</div>
                </div>
            </div>
            {/* Backup Database */}
            <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-6 mb-6 border border-neutral-800">
                <div className="font-semibold text-gray-800 dark:text-white mb-2">Backup Database</div>
                <div className="text-xs text-gray-400 mb-4">Buat backup lengkap dari database aplikasi untuk keperluan recovery atau migrasi data.</div>
                <div className="bg-neutral-800 rounded p-3 text-blue-200 flex items-center gap-2 mb-4">
                    <CircleAlert className="w-5 h-5" />
                    Proses backup akan membuat file database yang dapat diunduh dan disimpan dengan aman.
                </div>
                <div className="flex gap-2">
                    <button type="button" onClick={() => setShowBackupConfirm(true)} disabled={loading} className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                        Create Backup
                    </button>
                    {backups[0] && (
                        <a
                            href={`/storage/db_backup/${encodeURIComponent(backups[0].filename)}`}
                            className="btn btn-success flex items-center gap-2 px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700"
                            download
                        >
                            <Download className="w-4 h-4" /> Download Latest Backup
                        </a>
                    )}
                </div>
                {showBackupConfirm && (
                    <div className="mt-4 bg-neutral-800 rounded p-4 border border-blue-700">
                        <div className="mb-2 text-blue-200 font-semibold">Konfirmasi Backup</div>
                        <div className="text-xs text-gray-300 mb-2">Backup akan membuat salinan seluruh data database saat ini. Lanjutkan?</div>
                        <div className="flex gap-2">
                            <button type="button" onClick={handleBackup} disabled={loading} className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">Lanjutkan</button>
                            <button type="button" onClick={() => setShowBackupConfirm(false)} className="px-4 py-2 rounded border border-blue-600 text-blue-600 bg-white dark:bg-neutral-900 hover:bg-blue-50">Batal</button>
                        </div>
                        {backupResult && <div className="text-green-400 text-sm mt-2">{backupResult}</div>}
                    </div>
                )}
            </div>
            {/* Restore Database */}
            <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-6 mb-6 border border-neutral-800">
                <div className="font-semibold text-gray-800 dark:text-white mb-2">Restore Database</div>
                <div className="text-xs text-gray-800 dark:text-gray-300 mb-4">Restore database dari file backup yang telah disimpan sebelumnya.</div>
                <div className="flex gap-2 items-center mb-2">
                    <Select
                        value={restoreFile ?? ''}
                        onValueChange={setRestoreFile}
                        disabled={loading}
                    >
                        <SelectTrigger className="w-64" aria-label="Pilih File Backup">
                            <SelectValue placeholder="Pilih File Backup" />
                        </SelectTrigger>
                        <SelectContent>
                            {backups.length === 0 ? (
                                <div className="px-3 py-2 text-gray-400 text-sm">Tidak ada backup</div>
                            ) : (
                                backups.map(b => (
                                    <SelectItem key={b.filename} value={b.filename}>
                                        {b.filename} - {b.size_formatted} - {b.created_at_formatted}
                                    </SelectItem>
                                ))
                            )}
                        </SelectContent>
                    </Select>
                    <button
                        type="button"
                        onClick={() => setShowRestoreConfirm(true)}
                        disabled={!restoreFile || loading}
                        className="px-4 py-2 rounded bg-yellow-600 text-white hover:bg-yellow-700 disabled:opacity-50 transition-all"
                    >
                        Restore from Backup
                    </button>
                </div>
                <div className="bg-yellow-900 rounded p-3 text-yellow-200 flex items-center gap-2 mb-2">
                    <AlertTriangle className="w-5 h-5" />
                    Proses restore akan mengganti seluruh data database saat ini. Pastikan Anda memiliki file backup data sebelum melakukan restore.
                </div>
                {showRestoreConfirm && (
                    <div className="mt-4 bg-neutral-800 rounded p-4 border border-yellow-700">
                        <div className="mb-2 text-yellow-200 font-semibold">Konfirmasi Restore</div>
                        <div className="text-xs text-gray-300 mb-2">Restore akan menimpa seluruh data database. Lanjutkan?</div>
                        <div className="flex gap-2">
                            <button type="button" onClick={handleRestore} disabled={loading} className="px-4 py-2 rounded bg-yellow-600 text-white hover:bg-yellow-700 disabled:opacity-50">Lanjutkan</button>
                            <button type="button" onClick={() => setShowRestoreConfirm(false)} className="px-4 py-2 rounded border border-yellow-600 text-yellow-600 bg-white dark:bg-neutral-900 hover:bg-yellow-50">Batal</button>
                        </div>
                        {restoreResult && <div className="text-green-400 text-sm mt-2">{restoreResult}</div>}
                    </div>
                )}
            </div>
            {/* Informasi Database */}
            <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-6 mb-6 border border-neutral-800">
                <div className="font-semibold text-gray-800 dark:text-white mb-2">Informasi Database</div>
                <div className="grid md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">Host</div>
                        <div className=" text-gray-800 dark:text-white font-mono">127.0.0.1</div>
                    </div>
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">Port</div>
                        <div className="text-gray-800 dark:text-white font-mono">3306</div>
                    </div>
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">Username</div>
                        <div className="text-gray-800 dark:text-gray-300 font-mono">{props.connection ?? '-'}</div>
                    </div>
                    <div>
                        <div className="text-gray-800 dark:text-gray-300">Driver</div>
                        <div className="text-gray-800 dark:text-gray-300 font-mono">MySQL</div>
                    </div>
                </div>
            </div>
            {/* Tabel Database */}
            <div className="bg-cyan-100 dark:bg-neutral-900 rounded-xl p-6 mb-6 border border-neutral-800">
                <div className="font-semibold text-gray-800 dark:text-white mb-2">Tabel Database</div>
                <div className="overflow-x-auto mt-2">
                    <DatabaseTable tables={props.tables} />
                </div>
            </div>
        </AppLayout>
    );
}

function DatabaseTable({ tables }: { tables: { name: string; rows: number; size_mb: number }[] | undefined }) {
    return (
        <div className="overflow-x-auto mt-2">
            <table className="w-full text-sm border border-neutral-200 dark:border-neutral-700 rounded-xl overflow-hidden bg-white dark:bg-neutral-900">
                <thead className="bg-neutral-100 dark:bg-neutral-800">
                    <tr>
                        <th className="px-4 py-3 text-left text-gray-700 dark:text-gray-200 font-semibold border-b border-neutral-200 dark:border-neutral-700">Table</th>
                        <th className="px-4 py-3 text-right text-gray-700 dark:text-gray-200 font-semibold border-b border-neutral-200 dark:border-neutral-700">Rows</th>
                        <th className="px-4 py-3 text-right text-gray-700 dark:text-gray-200 font-semibold border-b border-neutral-200 dark:border-neutral-700">Size (MB)</th>
                    </tr>
                </thead>
                <tbody>
                    {Array.isArray(tables) && tables.length > 0 ? (
                        tables.map((t) => (
                            <tr key={t.name} className="hover:bg-blue-50 dark:hover:bg-neutral-800 transition-colors">
                                <td className="px-4 py-2 font-mono text-gray-800 dark:text-white border-b border-neutral-100 dark:border-neutral-800">{t.name}</td>
                                <td className="px-4 py-2 text-right text-gray-800 dark:text-white border-b border-neutral-100 dark:border-neutral-800">{t.rows}</td>
                                <td className="px-4 py-2 text-right text-gray-800 dark:text-white border-b border-neutral-100 dark:border-neutral-800">{t.size_mb}</td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan={3} className="text-center py-4 text-gray-400">No tables found</td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
