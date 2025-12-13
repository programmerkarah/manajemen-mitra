import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardStats {
    total_mitra: number;
    total_kegiatan: number;
    alokasi_pending: number;
    bast_pending: number;
}

interface DashboardProps {
    stats: DashboardStats;
    recentAlokasi: any[];
    recentKegiatan: any[];
    userRole: string;
}

export default function Dashboard({
    stats,
    recentAlokasi,
    recentKegiatan,
    userRole,
}: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                            Total Mitra Aktif
                        </div>
                        <div className="mt-2 text-3xl font-bold">
                            {stats.total_mitra}
                        </div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                            Kegiatan Berjalan
                        </div>
                        <div className="mt-2 text-3xl font-bold">
                            {stats.total_kegiatan}
                        </div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                            Alokasi Menunggu Approval
                        </div>
                        <div className="mt-2 text-3xl font-bold text-amber-600">
                            {stats.alokasi_pending}
                        </div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                            BAST Pending
                        </div>
                        <div className="mt-2 text-3xl font-bold text-blue-600">
                            {stats.bast_pending}
                        </div>
                    </div>
                </div>

                {/* Recent Activities */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Recent Alokasi */}
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <h3 className="mb-4 text-lg font-semibold">
                            Alokasi Terbaru
                        </h3>
                        <div className="space-y-3">
                            {recentAlokasi.length > 0 ? (
                                recentAlokasi.map((alokasi) => (
                                    <div
                                        key={alokasi.id}
                                        className="flex items-center justify-between border-b border-neutral-200 pb-3 last:border-0 dark:border-neutral-700"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {alokasi.mitra.nama}
                                            </div>
                                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                {alokasi.kegiatan.nama_kegiatan}
                                            </div>
                                        </div>
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                alokasi.status === 'disetujui'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                    : alokasi.status ===
                                                        'diajukan'
                                                      ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                                                      : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200'
                                            }`}
                                        >
                                            {alokasi.status}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Belum ada alokasi
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Recent Kegiatan */}
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <h3 className="mb-4 text-lg font-semibold">
                            Kegiatan Terbaru
                        </h3>
                        <div className="space-y-3">
                            {recentKegiatan.length > 0 ? (
                                recentKegiatan.map((kegiatan) => (
                                    <div
                                        key={kegiatan.id}
                                        className="flex items-center justify-between border-b border-neutral-200 pb-3 last:border-0 dark:border-neutral-700"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {kegiatan.nama_kegiatan}
                                            </div>
                                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.kode_kegiatan}
                                            </div>
                                        </div>
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                kegiatan.status === 'aktif'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                    : kegiatan.status ===
                                                        'divalidasi'
                                                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                                      : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200'
                                            }`}
                                        >
                                            {kegiatan.status}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Belum ada kegiatan
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
