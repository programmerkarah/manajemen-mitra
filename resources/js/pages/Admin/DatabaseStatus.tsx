import { ConfirmActionDialog } from '@/components/confirm-action-dialog';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SummaryCard } from '@/components/summary-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatNumber } from '@/lib/format-number';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import Archive from 'lucide-react/icons/archive';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import Database from 'lucide-react/icons/database';
import Download from 'lucide-react/icons/download';
import HardDrive from 'lucide-react/icons/hard-drive';
import History from 'lucide-react/icons/history';
import RefreshCw from 'lucide-react/icons/refresh-cw';
import RotateCcw from 'lucide-react/icons/rotate-ccw';
import Table2 from 'lucide-react/icons/table-2';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Database', href: '/admin/database-status' },
];

type TableStat = {
    name: string;
    rows: number;
    size_mb: number;
};

type BackupFile = {
    filename: string;
    size: string;
    size_formatted: string;
    modified: string;
    created_at_formatted: string;
};

type PageProps = {
    status?: string;
    connection?: string;
    size?: string;
    tableCount?: number;
    lastBackup?: string | null;
    lastBackupFile?: string | null;
    tables?: TableStat[];
};

function csrfToken(): string {
    if (typeof document === 'undefined') return '';

    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export default function DatabaseStatus() {
    const { props } = usePage<PageProps>();
    const [backups, setBackups] = useState<BackupFile[]>([]);
    const [restoreFile, setRestoreFile] = useState('');
    const [loading, setLoading] = useState<'backup' | 'restore' | null>(null);
    const [confirm, setConfirm] = useState<'backup' | 'restore' | null>(null);
    const [feedback, setFeedback] = useState<{
        type: 'success' | 'error';
        text: string;
    } | null>(null);

    const tables = props.tables ?? [];
    const totalRows = useMemo(
        () =>
            tables.reduce(
                (total, table) => total + Number(table.rows || 0),
                0,
            ),
        [tables],
    );

    const loadBackups = async () => {
        try {
            const response = await fetch('/admin/database-list-backups', {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            if (data.success) setBackups(data.backups ?? []);
        } catch {
            setFeedback({
                type: 'error',
                text: 'Daftar backup belum dapat dimuat.',
            });
        }
    };

    useEffect(() => {
        void loadBackups();
    }, [props.lastBackup]);

    const createBackup = async () => {
        setLoading('backup');
        setFeedback(null);

        try {
            const response = await fetch('/admin/database-backup', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Backup gagal dibuat.');
            }

            setFeedback({
                type: 'success',
                text: `Backup berhasil dibuat: ${data.file}`,
            });
            setConfirm(null);
            await loadBackups();
            router.reload({
                only: ['lastBackup', 'lastBackupFile', 'tables', 'size'],
            });
        } catch (error) {
            setFeedback({
                type: 'error',
                text:
                    error instanceof Error
                        ? error.message
                        : 'Backup gagal dibuat.',
            });
        } finally {
            setLoading(null);
        }
    };

    const restoreBackup = async () => {
        if (!restoreFile) return;

        setLoading('restore');
        setFeedback(null);

        try {
            const response = await fetch('/admin/database-restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ file: restoreFile }),
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Restore database gagal.');
            }

            setFeedback({
                type: 'success',
                text: 'Restore database berhasil diselesaikan.',
            });
            setConfirm(null);
            router.reload();
        } catch (error) {
            setFeedback({
                type: 'error',
                text:
                    error instanceof Error
                        ? error.message
                        : 'Restore database gagal.',
            });
        } finally {
            setLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Database" />

            <div className="space-y-6 p-4 sm:p-6">
                <PageHeader
                    title="Database"
                    description="Pantau kesehatan database, ukuran tabel, serta kelola backup dan pemulihan dari satu tempat."
                >
                    <Button
                        variant="outline"
                        onClick={() => {
                            void loadBackups();
                            router.reload({
                                only: [
                                    'status',
                                    'size',
                                    'tables',
                                    'tableCount',
                                    'lastBackup',
                                    'lastBackupFile',
                                ],
                            });
                        }}
                    >
                        <RefreshCw className="mr-2 size-4" />
                        Segarkan
                    </Button>
                </PageHeader>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        label="Status"
                        value={props.status ?? '-'}
                        icon={<CheckCircle2 className="size-4" />}
                        meta="Koneksi database aplikasi"
                        accent="green"
                    />
                    <SummaryCard
                        label="Ukuran Database"
                        value={props.size ?? '0 MB'}
                        icon={<HardDrive className="size-4" />}
                        meta="Data + indeks"
                        accent="blue"
                    />
                    <SummaryCard
                        label="Jumlah Tabel"
                        value={formatNumber(props.tableCount ?? tables.length)}
                        icon={<Table2 className="size-4" />}
                        meta="Tabel pada schema aktif"
                        accent="violet"
                    />
                    <SummaryCard
                        label="Total Baris"
                        value={formatNumber(totalRows)}
                        icon={<Database className="size-4" />}
                        meta="Estimasi information_schema"
                        accent="orange"
                    />
                </div>

                {feedback && (
                    <div
                        className={
                            feedback.type === 'success'
                                ? 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200'
                                : 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200'
                        }
                    >
                        {feedback.text}
                    </div>
                )}

                <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <ContentCard>
                        <div className="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <div className="flex items-center gap-2">
                                    <Archive className="size-5 text-muted-foreground" />
                                    <h2 className="font-semibold">
                                        Backup Database
                                    </h2>
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Buat snapshot sebelum perubahan besar,
                                    migrasi, atau pemulihan data.
                                </p>
                            </div>
                            <Button
                                onClick={() => setConfirm('backup')}
                                disabled={loading !== null}
                            >
                                <Archive className="mr-2 size-4" />
                                Buat Backup
                            </Button>
                        </div>

                        <div className="rounded-xl border bg-muted/30 p-4">
                            <div className="flex items-center justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Backup terbaru
                                    </p>
                                    <p className="mt-1 truncate text-sm font-semibold">
                                        {backups[0]?.filename ??
                                            props.lastBackupFile ??
                                            'Belum ada backup'}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {backups[0]?.created_at_formatted ??
                                            props.lastBackup ??
                                            '-'}
                                        {backups[0]?.size_formatted
                                            ? ` · ${backups[0].size_formatted}`
                                            : ''}
                                    </p>
                                </div>
                                {backups[0] && (
                                    <Button variant="outline" asChild>
                                        <a
                                            href={`/storage/db_backup/${encodeURIComponent(
                                                backups[0].filename,
                                            )}`}
                                            download
                                        >
                                            <Download className="mr-2 size-4" />
                                            Unduh
                                        </a>
                                    </Button>
                                )}
                            </div>
                        </div>

                        <div className="mt-4 space-y-2">
                            {backups.slice(0, 5).map((backup) => (
                                <div
                                    key={backup.filename}
                                    className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {backup.filename}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {backup.created_at_formatted} ·{' '}
                                            {backup.size_formatted}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        asChild
                                    >
                                        <a
                                            href={`/storage/db_backup/${encodeURIComponent(
                                                backup.filename,
                                            )}`}
                                            download
                                        >
                                            <Download className="size-4" />
                                            <span className="sr-only">
                                                Unduh {backup.filename}
                                            </span>
                                        </a>
                                    </Button>
                                </div>
                            ))}
                            {backups.length === 0 && (
                                <p className="py-5 text-center text-sm text-muted-foreground">
                                    Belum ada file backup.
                                </p>
                            )}
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="mb-5 flex items-center gap-2">
                            <RotateCcw className="size-5 text-muted-foreground" />
                            <div>
                                <h2 className="font-semibold">
                                    Restore Database
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Pulihkan database dari snapshot yang
                                    tersedia.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Select
                                value={restoreFile}
                                onValueChange={setRestoreFile}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih file backup" />
                                </SelectTrigger>
                                <SelectContent>
                                    {backups.map((backup) => (
                                        <SelectItem
                                            key={backup.filename}
                                            value={backup.filename}
                                        >
                                            {backup.filename} ·{' '}
                                            {backup.size_formatted}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
                                Restore akan mengganti isi database saat ini.
                                Pastikan backup terbaru sudah tersedia sebelum
                                melanjutkan.
                            </div>

                            <Button
                                variant="outline"
                                className="w-full"
                                disabled={!restoreFile || loading !== null}
                                onClick={() => setConfirm('restore')}
                            >
                                <RotateCcw className="mr-2 size-4" />
                                Restore Backup Terpilih
                            </Button>
                        </div>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="mb-4 flex items-center justify-between gap-4">
                        <div>
                            <h2 className="font-semibold">Ukuran per Tabel</h2>
                            <p className="text-sm text-muted-foreground">
                                Diurutkan berdasarkan ukuran data dan indeks.
                            </p>
                        </div>
                        <Badge variant="outline">
                            {formatNumber(tables.length)} tabel
                        </Badge>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[620px] text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="px-3 py-3 font-medium">
                                        Tabel
                                    </th>
                                    <th className="px-3 py-3 text-right font-medium">
                                        Baris
                                    </th>
                                    <th className="px-3 py-3 text-right font-medium">
                                        Ukuran
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {tables.map((table) => (
                                    <tr
                                        key={table.name}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-3 py-3 font-mono text-xs">
                                            {table.name}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {formatNumber(table.rows)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {formatNumber(table.size_mb, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}{' '}
                                            MB
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="flex items-start gap-3">
                        <History className="mt-0.5 size-5 text-muted-foreground" />
                        <div>
                            <p className="font-medium">Koneksi aktif</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Driver: {props.connection ?? '-'}.
                                Kredensial, host, dan password tidak ditampilkan
                                pada antarmuka untuk mengurangi paparan
                                informasi sensitif.
                            </p>
                        </div>
                    </div>
                </ContentCard>
            </div>

            <ConfirmActionDialog
                open={confirm === 'backup'}
                onOpenChange={(open) => !open && setConfirm(null)}
                title="Buat backup database?"
                description="Snapshot baru akan dibuat dari kondisi database saat ini. Proses ini aman dan tidak mengubah data."
                confirmLabel="Buat Backup"
                processing={loading === 'backup'}
                onConfirm={() => void createBackup()}
            />
            <ConfirmActionDialog
                open={confirm === 'restore'}
                onOpenChange={(open) => !open && setConfirm(null)}
                title="Restore database?"
                description={`Isi database saat ini akan diganti menggunakan ${restoreFile || 'backup terpilih'}. Tindakan ini berisiko dan harus dilakukan hanya jika backup sudah diverifikasi.`}
                confirmLabel="Restore Database"
                destructive
                processing={loading === 'restore'}
                onConfirm={() => void restoreBackup()}
            />
        </AppLayout>
    );
}
