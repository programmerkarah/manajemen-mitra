import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Clock,
    Users,
    Briefcase,
    CheckCircle2,
    AlertCircle,
    Calendar,
    FileText,
    Eye,
    Plus,
    CheckCircle,
    XCircle,
    AlertTriangle,
    Database,
    FolderOpen,
    UserCheck,
    BookOpen,
    FileSignature,
    ScrollText,
    TrendingUp,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/status-badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardStats {
    total_petugas: number;
    total_kegiatan: number;
    alokasi_pending: number;
    bast_pending: number;
}

interface AdditionalStats {
    sbml: {
        total: number;
        aktif: number;
        nonaktif: number;
    };
    dipa: {
        total: number;
        aktif: number;
        nonaktif: number;
    };
    penandatangan: {
        total: number;
        kepala: number;
        ppk: number;
        aktif: number;
    };
    dasar_hukum: {
        total: number;
        aktif: number;
    };
    sk: {
        total: number;
        draft: number;
        diterbitkan: number;
        dibatalkan: number;
    };
    spk: {
        total: number;
    };
    petugas_detail: {
        organik: number;
        non_organik: number;
    };
    kegiatan_detail: {
        sensus: number;
        survei: number;
    };
    alokasi_detail: {
        draft: number;
        diajukan: number;
        disetujui: number;
        ditolak: number;
    };
}

interface KegiatanBulanIni {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    status: string;
    periode_alokasi: {
        id: number;
        hashed_id: string;
        status: string;
        jumlah_petugas: number;
        has_alokasi: boolean;
    } | null;
    sk: {
        id: number;
        hashed_id: string;
        nomor_sk: string;
        status: string;
        is_signed: boolean;
    } | null;
    spk: {
        count: number;
        has_spk: boolean;
    };
}

interface DashboardProps {
    stats: DashboardStats;
    additionalStats: AdditionalStats;
    recentAlokasi: any[];
    kegiatanBulanIni: KegiatanBulanIni[];
    currentMonth: number;
    currentYear: number;
    userRole: string;
}

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function Dashboard({
    stats,
    additionalStats,
    recentAlokasi,
    kegiatanBulanIni,
    currentMonth,
    currentYear,
    userRole,
}: DashboardProps) {
    const { auth } = usePage<{ auth: any }>().props;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-8">
                {/* Welcome Section */}
                <div className="rounded-2xl border border-neutral-200/70 bg-white/80 p-8 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">
                        Selamat Datang, {auth.user.name}! 👋
                    </h1>
                    <p className="mt-2 text-neutral-600 dark:text-neutral-400">
                        SIMANTIK (Sistem Manajemen Petugas dan Administrasi Kegiatan Statistik) - Kelola data petugas, kegiatan, dan alokasi dengan mudah
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-8 shadow-2xl flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-neutral-600 dark:text-neutral-400">Petugas Aktif</p>
                                <p className="mt-2 text-3xl font-bold text-neutral-900 dark:text-white">{stats.total_petugas}</p>
                            </div>
                            <div className="rounded-lg bg-blue-100 p-3 dark:bg-neutral-700/50">
                                <Users className="size-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-8 shadow-2xl flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-neutral-600 dark:text-neutral-400">Kegiatan Berjalan</p>
                                <p className="mt-2 text-3xl font-bold text-neutral-900 dark:text-white">{stats.total_kegiatan}</p>
                            </div>
                            <div className="rounded-lg bg-green-100 p-3 dark:bg-green-900/30">
                                <Briefcase className="size-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-8 shadow-2xl flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-neutral-600 dark:text-neutral-400">Menunggu Approval</p>
                                <p className="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{stats.alokasi_pending}</p>
                            </div>
                            <div className="rounded-lg bg-amber-100 p-3 dark:bg-amber-900/30">
                                <Clock className="size-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-8 shadow-2xl flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-neutral-600 dark:text-neutral-400">BAST Pending</p>
                                <p className="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">{stats.bast_pending}</p>
                            </div>
                            <div className="rounded-lg bg-purple-100 p-3 dark:bg-purple-900/30">
                                <AlertCircle className="size-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Comprehensive Statistics */}
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* SBML Stats */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-indigo-100 p-3 dark:bg-indigo-900/30">
                                <Database className="size-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">SBML</h3>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">{additionalStats.sbml.total}</p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Aktif</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.sbml.aktif}</span>
                                    <StatusBadge status="aktif" showIcon={false} />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Nonaktif</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.sbml.nonaktif}</span>
                                    <StatusBadge status="nonaktif" showIcon={false} />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* DIPA Stats */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-emerald-100 p-3 dark:bg-emerald-900/30">
                                <FolderOpen className="size-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">DIPA</h3>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">{additionalStats.dipa.total}</p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Aktif</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.dipa.aktif}</span>
                                    <StatusBadge status="aktif" showIcon={false} />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Nonaktif</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.dipa.nonaktif}</span>
                                    <StatusBadge status="nonaktif" showIcon={false} />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Penandatangan Stats */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-violet-100 p-3 dark:bg-violet-900/30">
                                <UserCheck className="size-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">Penandatangan</h3>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">{additionalStats.penandatangan.total}</p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">Kepala</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.penandatangan.kepala}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">PPK</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.penandatangan.ppk}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">Aktif</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.penandatangan.aktif}</span>
                            </div>
                        </div>
                    </div>

                    {/* Dasar Hukum Stats */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-cyan-100 p-3 dark:bg-cyan-900/30">
                                <BookOpen className="size-5 text-cyan-600 dark:text-cyan-400" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">Dasar Hukum</h3>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">{additionalStats.dasar_hukum.total}</p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Aktif</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.dasar_hukum.aktif}</span>
                                    <StatusBadge status="aktif" showIcon={false} />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* SK Stats */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-rose-100 p-3 dark:bg-rose-900/30">
                                <FileSignature className="size-5 text-rose-600 dark:text-rose-400" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">SK Petugas</h3>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">{additionalStats.sk.total}</p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">Draft</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.sk.draft}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">Diterbitkan</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.sk.diterbitkan}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">Dibatalkan</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.sk.dibatalkan}</span>
                            </div>
                        </div>
                    </div>

                    {/* SPK Stats */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-orange-100 p-3 dark:bg-orange-900/30">
                                <ScrollText className="size-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">SPK</h3>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">{additionalStats.spk.total}</p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">Total SPK Diterbitkan</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.spk.total}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Detailed Breakdown */}
                <div className="grid gap-6 md:grid-cols-3">
                    {/* Petugas Detail */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-sky-100 p-3 dark:bg-sky-900/30">
                                <Users className="size-5 text-sky-600 dark:text-sky-400" />
                            </div>
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-white">Petugas by Jenis</h3>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Organik</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.petugas_detail.organik}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Non-Organik</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.petugas_detail.non_organik}</span>
                            </div>
                        </div>
                    </div>

                    {/* Kegiatan Detail */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-teal-100 p-3 dark:bg-teal-900/30">
                                <Briefcase className="size-5 text-teal-600 dark:text-teal-400" />
                            </div>
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-white">Kegiatan by Jenis</h3>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Sensus</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.kegiatan_detail.sensus}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Survei</span>
                                <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.kegiatan_detail.survei}</span>
                            </div>
                        </div>
                    </div>

                    {/* Alokasi Detail */}
                    <div className="rounded-2xl border border-white/20 dark:border-neutral-700/30 bg-white/40 dark:bg-neutral-800/50 backdrop-blur-2xl p-6 shadow-2xl">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="rounded-lg bg-fuchsia-100 p-3 dark:bg-fuchsia-900/30">
                                <TrendingUp className="size-5 text-fuchsia-600 dark:text-fuchsia-400" />
                            </div>
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-white">Alokasi by Status</h3>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Draft</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.alokasi_detail.draft}</span>
                                    <StatusBadge status="draft" showIcon={false} />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Diajukan</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.alokasi_detail.diajukan}</span>
                                    <StatusBadge status="diajukan" showIcon={false} />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Disetujui</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.alokasi_detail.disetujui}</span>
                                    <StatusBadge status="disetujui" showIcon={false} />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">Ditolak</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">{additionalStats.alokasi_detail.ditolak}</span>
                                    <StatusBadge status="ditolak" showIcon={false} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recent Activities */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Recent Alokasi */}
                    <div className="rounded-2xl border border-neutral-200/70 bg-white p-8 shadow-md dark:border-neutral-800 dark:bg-neutral-900 flex flex-col">
                        <div className="border-b border-neutral-200 pb-6 mb-6 dark:border-neutral-800">
                            <div className="flex items-center gap-2">
                                <Activity className="size-5 text-blue-600 dark:text-blue-400" />
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">Alokasi Terbaru</h3>
                            </div>
                        </div>
                        <div className="flex-1">
                            <div className="space-y-4">
                                {recentAlokasi.length > 0 ? (
                                    recentAlokasi.map((alokasi) => (
                                        <div
                                            key={alokasi.id}
                                            className="flex items-start justify-between rounded-lg border border-neutral-200 p-4 transition-all hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700 bg-white dark:bg-neutral-900"
                                        >
                                            <div className="flex-1">
                                                <div className="font-medium text-neutral-900 dark:text-white">{alokasi.petugas.nama}</div>
                                                <div className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{alokasi.kegiatan.nama_kegiatan || alokasi.kegiatan.kode_kegiatan}</div>
                                            </div>
                                            <span
                                                className={`ml-4 inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-medium ${
                                                    alokasi.status === 'disetujui'
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                        : alokasi.status === 'diajukan'
                                                          ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
                                                          : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300'
                                                }`}
                                            >
                                                {alokasi.status}
                                            </span>
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-lg border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700 bg-white dark:bg-neutral-900">
                                        <Activity className="mx-auto size-8 text-neutral-400" />
                                        <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">Belum ada data alokasi</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Kegiatan Bulan Ini */}
                    <div className="rounded-2xl border border-neutral-200/70 bg-white p-8 shadow-md dark:border-neutral-800 dark:bg-neutral-900 flex flex-col">
                        <div className="border-b border-neutral-200 pb-6 mb-6 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Calendar className="size-5 text-green-600 dark:text-green-400" />
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Kegiatan {monthNames[currentMonth - 1]} {currentYear}
                                    </h3>
                                </div>
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    {kegiatanBulanIni.length} kegiatan
                                </span>
                            </div>
                        </div>
                        <div className="flex-1 overflow-auto">
                            {kegiatanBulanIni.length > 0 ? (
                                <div className="space-y-4">
                                    {kegiatanBulanIni.map((kegiatan) => {
                                        const canEditAlokasi = ['admin', 'operator','ketua_tim'].includes(auth.activeRole?.name);
                                        const canEditSk = ['admin', 'operator','pj'].includes(auth.activeRole?.name);
                                        const canEditSpk = ['admin', 'operator','approver'].includes(auth.activeRole?.name);
                                        
                                        const hasActions = canEditAlokasi || canEditSk || canEditSpk;

                                        return (
                                            <div
                                                key={kegiatan.id}
                                                className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800 bg-white dark:bg-neutral-900"
                                            >
                                                <div className="mb-3">
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {kegiatan.nama_kegiatan}
                                                    </div>
                                                    <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                        {kegiatan.kode_kegiatan}
                                                    </div>
                                                </div>

                                                <div className="space-y-2">
                                                    {/* Alokasi Petugas */}
                                                    <div className="flex items-center justify-between text-sm">
                                                        <div className="flex items-center gap-2">
                                                            <Users className="size-4 text-neutral-500" />
                                                            <span className="text-neutral-700 dark:text-neutral-300">
                                                                Alokasi Petugas
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {kegiatan.periode_alokasi?.has_alokasi ? (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                        <CheckCircle className="size-4" />
                                                                        {kegiatan.periode_alokasi.jumlah_petugas} petugas
                                                                    </span>
                                                                    {canEditAlokasi && (
                                                                        <Link href={`/alokasi/periode/${kegiatan.hashed_id}/${currentYear}/${String(currentMonth).padStart(2, '0')}`}>
                                                                            <Button size="sm" variant="ghost">
                                                                                <Eye className="size-3 mr-1" />
                                                                                Lihat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                                        <AlertTriangle className="size-4" />
                                                                        Belum ada
                                                                    </span>
                                                                    {canEditAlokasi && (
                                                                        <Link href={`/alokasi/create?kegiatan_id=${kegiatan.hashed_id}`}>
                                                                            <Button size="sm" variant="ghost">
                                                                                <Plus className="size-3 mr-1" />
                                                                                Buat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* SK Petugas */}
                                                    <div className="flex items-center justify-between text-sm">
                                                        <div className="flex items-center gap-2">
                                                            <FileText className="size-4 text-neutral-500" />
                                                            <span className="text-neutral-700 dark:text-neutral-300">
                                                                SK Petugas
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {kegiatan.sk ? (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                        <CheckCircle className="size-4" />
                                                                        {kegiatan.sk.is_signed ? 'Signed' : 'Draft'}
                                                                    </span>
                                                                    {canEditSk && (
                                                                        <Link href={`/sk-kpa/${kegiatan.sk.hashed_id}`}>
                                                                            <Button size="sm" variant="ghost">
                                                                                <Eye className="size-3 mr-1" />
                                                                                Lihat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                                        <XCircle className="size-4" />
                                                                        Belum dibuat
                                                                    </span>
                                                                    {canEditSk && kegiatan.periode_alokasi?.has_alokasi && (
                                                                        <Link href={`/sk-kpa/create?kegiatan_id=${kegiatan.id}`}>
                                                                            <Button size="sm" variant="ghost">
                                                                                <Plus className="size-3 mr-1" />
                                                                                Buat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* SPK */}
                                                    <div className="flex items-center justify-between text-sm">
                                                        <div className="flex items-center gap-2">
                                                            <FileText className="size-4 text-neutral-500" />
                                                            <span className="text-neutral-700 dark:text-neutral-300">
                                                                SPK
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {kegiatan.spk.has_spk ? (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                        <CheckCircle className="size-4" />
                                                                        {kegiatan.spk.count} SPK
                                                                    </span>
                                                                    {canEditSpk && kegiatan.sk && (
                                                                        <Link href={`/sk-kpa/${kegiatan.sk.hashed_id}`}>
                                                                            <Button size="sm" variant="ghost">
                                                                                <Eye className="size-3 mr-1" />
                                                                                Lihat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                                        <XCircle className="size-4" />
                                                                        Belum dibuat
                                                                    </span>
                                                                    {canEditSpk && kegiatan.sk && (
                                                                        <Link href={`/sk-kpa/${kegiatan.sk.hashed_id}`}>
                                                                            <Button size="sm" variant="ghost">
                                                                                <Plus className="size-3 mr-1" />
                                                                                Buat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="rounded-lg border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700 bg-white dark:bg-neutral-900">
                                    <Calendar className="mx-auto size-8 text-neutral-400" />
                                    <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                        Tidak ada kegiatan untuk bulan ini
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}



