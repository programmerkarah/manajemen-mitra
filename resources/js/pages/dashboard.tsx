import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    Briefcase,
    Calendar,
    CheckCircle,
    Clock,
    Eye,
    FileSignature,
    FileText,
    Plus,
    ScrollText,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-react';
import {
    Area,
    Bar,
    BarChart,
    CartesianGrid,
    ComposedChart,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardStats {
    total_petugas: number;
    total_kegiatan: number;
    draft_kegiatan: number;
    bast_pending: number;
}

interface AdditionalStats {
    sbml: {
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
        dikirim: number;
        direvisi: number;
    };
}

interface RecentAlokasi {
    id: number;
    status: string;
    bulan: number;
    tahun: number;
    kegiatan: {
        nama_kegiatan: string;
        kode_kegiatan: string;
    };
    jumlah_organik: number;
    jumlah_non_organik: number;
    total_petugas: number;
}

interface ChartData {
    month: string;
    petugas_count: number;
    kegiatan_count: number;
}

interface PetugasMonitoringData {
    month: string;
    tidak_dialokasikan: number;
    kegiatan_1_2: number;
    kegiatan_3_5: number;
    kegiatan_lebih_5: number;
}

interface HonorInequalityData {
    month: string;
    rata_rata_honor: number;
    honor_tertinggi: number;
    honor_terendah: number;
    std_deviasi: number;
    koefisien_variasi: number;
    honor_0_500rb: number;
    honor_501rb_1500rb: number;
    honor_1501rb_2500rb: number;
    honor_2501rb_3500rb: number;
    honor_lebih_3501rb: number;
    total_petugas: number;
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

interface PetugasMonitoringSummary {
    tidak_dialokasikan: number;
    kegiatan_1_2: number;
    kegiatan_3_5: number;
    kegiatan_lebih_5: number;
}

interface HonorInequalitySummary {
    has_data: boolean;
    rata_rata_honor?: number;
    honor_tertinggi?: number;
    honor_terendah?: number;
    std_deviasi?: number;
    koefisien_variasi?: number;
    gap_honor?: number;
    total_petugas?: number;
}

interface DashboardProps {
    stats: DashboardStats;
    additionalStats: AdditionalStats;
    recentAlokasi: RecentAlokasi[];
    kegiatanBulanIni: KegiatanBulanIni[];
    chartData: ChartData[];
    petugasMonitoringData: PetugasMonitoringData[];
    honorInequalityData: HonorInequalityData[];
    petugasMonitoringSummary: PetugasMonitoringSummary;
    honorInequalitySummary: HonorInequalitySummary;
    currentMonth: number;
    currentYear: number;
    userRole: string;
}

const monthNames = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

export default function Dashboard({
    stats,
    additionalStats,
    recentAlokasi,
    kegiatanBulanIni,
    chartData,
    petugasMonitoringData,
    honorInequalityData,
    petugasMonitoringSummary,
    honorInequalitySummary,
    currentMonth,
    currentYear,
    userRole,
}: DashboardProps) {
    const { auth } = usePage<{ auth: any }>().props;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 overflow-x-hidden">
                {/* Welcome Section */}
                <div className="rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <h1 className="text-xl font-bold break-words text-neutral-900 dark:text-white">
                        Selamat Datang, {auth.user.name}! 👋
                    </h1>
                    <p className="mt-2 text-sm break-words text-neutral-600 dark:text-neutral-400">
                        SIMANTIK (Sistem Manajemen Petugas dan Administrasi
                        Kegiatan Statistik) - Kelola data petugas, kegiatan, dan
                        alokasi dengan mudah
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Petugas Aktif
                                </p>
                                <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                                    {stats.total_petugas}
                                </p>
                            </div>
                            <div className="flex-shrink-0 rounded-lg bg-blue-100 p-2.5 dark:bg-neutral-700/50">
                                <Users className="size-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>
                    <div className="flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Kegiatan Berjalan
                                </p>
                                <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                                    {stats.total_kegiatan}
                                </p>
                            </div>
                            <div className="flex-shrink-0 rounded-lg bg-green-100 p-2.5 dark:bg-green-900/30">
                                <Briefcase className="size-5 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>
                    <div className="flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Draft Kegiatan
                                </p>
                                <p className="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                                    {stats.draft_kegiatan}
                                </p>
                            </div>
                            <div className="flex-shrink-0 rounded-lg bg-amber-100 p-2.5 dark:bg-amber-900/30">
                                <Clock className="size-5 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </div>
                    <div className="flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    BAST Pending
                                </p>
                                <p className="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {stats.bast_pending}
                                </p>
                            </div>
                            <div className="flex-shrink-0 rounded-lg bg-purple-100 p-2.5 dark:bg-purple-900/30">
                                <AlertCircle className="size-5 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Comprehensive Statistics */}
                <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                    {/* SK Stats */}
                    <div className="min-w-0 rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-3 flex items-center gap-2.5">
                            <div className="flex-shrink-0 rounded-lg bg-rose-100 p-2.5 dark:bg-rose-900/30">
                                <FileSignature className="size-4 text-rose-600 dark:text-rose-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <h3 className="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                    SK Petugas
                                </h3>
                                <p className="text-xl font-bold text-neutral-900 dark:text-white">
                                    {additionalStats.sk.total}
                                </p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">
                                    Draft
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.sk.draft}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">
                                    Ditetapkan
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.sk.diterbitkan}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* SPK Stats */}
                    <div className="min-w-0 rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-3 flex items-center gap-2.5">
                            <div className="flex-shrink-0 rounded-lg bg-orange-100 p-2.5 dark:bg-orange-900/30">
                                <ScrollText className="size-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <h3 className="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                    Perjanjian Kerja
                                </h3>
                                <p className="text-xl font-bold text-neutral-900 dark:text-white">
                                    {additionalStats.spk.total}
                                </p>
                            </div>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-neutral-600 dark:text-neutral-400">
                                    Total Diterbitkan
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.spk.total}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Detailed Breakdown */}
                <div className="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {/* Petugas Detail */}
                    <div className="min-w-0 rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-3 flex items-center gap-2.5">
                            <div className="flex-shrink-0 rounded-lg bg-sky-100 p-2.5 dark:bg-sky-900/30">
                                <Users className="size-4 text-sky-600 dark:text-sky-400" />
                            </div>
                            <h3 className="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                Petugas by Jenis
                            </h3>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Organik
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.petugas_detail.organik}
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Non-Organik
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.petugas_detail.non_organik}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Kegiatan Detail */}
                    <div className="min-w-0 rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-3 flex items-center gap-2.5">
                            <div className="flex-shrink-0 rounded-lg bg-teal-100 p-2.5 dark:bg-teal-900/30">
                                <Briefcase className="size-4 text-teal-600 dark:text-teal-400" />
                            </div>
                            <h3 className="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                Kegiatan by Jenis
                            </h3>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Sensus
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.kegiatan_detail.sensus}
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Survei
                                </span>
                                <span className="font-medium text-neutral-900 dark:text-white">
                                    {additionalStats.kegiatan_detail.survei}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Alokasi Detail */}
                    <div className="min-w-0 rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-3 flex items-center gap-2.5">
                            <div className="flex-shrink-0 rounded-lg bg-fuchsia-100 p-2.5 dark:bg-fuchsia-900/30">
                                <TrendingUp className="size-4 text-fuchsia-600 dark:text-fuchsia-400" />
                            </div>
                            <h3 className="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                Alokasi by Status
                            </h3>
                        </div>
                        <div className="space-y-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Draft
                                </span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {additionalStats.alokasi_detail.draft}
                                    </span>
                                    <StatusBadge
                                        status="draft"
                                        showIcon={false}
                                    />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Dikirim
                                </span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {additionalStats.alokasi_detail.dikirim}
                                    </span>
                                    <StatusBadge
                                        status="dikirim"
                                        showIcon={false}
                                    />
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Direvisi
                                </span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {
                                            additionalStats.alokasi_detail
                                                .direvisi
                                        }
                                    </span>
                                    <StatusBadge
                                        status="direvisi"
                                        showIcon={false}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Monthly Charts */}
                <div className="grid min-w-0 gap-4">
                    {/* Combined Chart: Petugas & Kegiatan */}
                    <div className="min-w-0 rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="mb-4 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                    Tren Alokasi Bulanan {currentYear}
                                </h3>
                                <div className="flex items-center gap-3 text-xs">
                                    <div className="flex items-center gap-1.5">
                                        <div className="size-2 rounded-full bg-indigo-500"></div>
                                        <span className="font-medium text-neutral-600 dark:text-neutral-400">
                                            Petugas
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <div className="size-2 rounded-full bg-green-500"></div>
                                        <span className="font-medium text-neutral-600 dark:text-neutral-400">
                                            Kegiatan
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="space-y-2">
                            <ResponsiveContainer width="100%" height={280}>
                                <LineChart
                                    data={chartData}
                                    margin={{
                                        top: 5,
                                        right: 30,
                                        left: 0,
                                        bottom: 5,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="currentColor"
                                        className="text-neutral-200 dark:text-neutral-700"
                                        opacity={0.3}
                                    />
                                    <XAxis
                                        dataKey="month"
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 12 }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 12 }}
                                        tickLine={false}
                                        axisLine={false}
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'var(--color-bg)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: '8px',
                                            padding: '8px 12px',
                                        }}
                                        labelStyle={{
                                            color: 'var(--color-text)',
                                            fontWeight: 600,
                                            marginBottom: '4px',
                                        }}
                                        itemStyle={{ fontSize: '13px' }}
                                    />
                                    <Legend
                                        wrapperStyle={{ paddingTop: '16px' }}
                                        iconType="circle"
                                        iconSize={8}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="petugas_count"
                                        stroke="rgb(99, 102, 241)"
                                        strokeWidth={2.5}
                                        dot={{
                                            fill: 'rgb(99, 102, 241)',
                                            r: 4,
                                        }}
                                        activeDot={{
                                            r: 6,
                                            strokeWidth: 2,
                                            stroke: '#fff',
                                        }}
                                        name="Petugas"
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="kegiatan_count"
                                        stroke="rgb(34, 197, 94)"
                                        strokeWidth={2.5}
                                        dot={{ fill: 'rgb(34, 197, 94)', r: 4 }}
                                        activeDot={{
                                            r: 6,
                                            strokeWidth: 2,
                                            stroke: '#fff',
                                        }}
                                        name="Kegiatan"
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Petugas Monitoring Chart */}
                    <div className="min-w-0 rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="mb-4 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                    Distribusi Beban Kerja Petugas {currentYear}
                                </h3>
                            </div>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Monitoring alokasi kegiatan per petugas untuk
                                evaluasi workload
                            </p>
                        </div>
                        <div className="space-y-2">
                            <ResponsiveContainer width="100%" height={280}>
                                <BarChart
                                    data={petugasMonitoringData}
                                    margin={{
                                        top: 5,
                                        right: 30,
                                        left: 0,
                                        bottom: 5,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="currentColor"
                                        className="text-neutral-200 dark:text-neutral-700"
                                        opacity={0.3}
                                    />
                                    <XAxis
                                        dataKey="month"
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 12 }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 12 }}
                                        tickLine={false}
                                        axisLine={false}
                                        tickFormatter={(value) =>
                                            new Intl.NumberFormat(
                                                'id-ID',
                                            ).format(value)
                                        }
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'var(--color-bg)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: '8px',
                                            padding: '8px 12px',
                                        }}
                                        labelStyle={{
                                            color: 'var(--color-text)',
                                            fontWeight: 600,
                                            marginBottom: '4px',
                                        }}
                                        itemStyle={{ fontSize: '13px' }}
                                    />
                                    <Legend
                                        wrapperStyle={{ paddingTop: '16px' }}
                                        iconType="square"
                                        iconSize={10}
                                    />
                                    <Bar
                                        dataKey="tidak_dialokasikan"
                                        fill="rgba(69, 141, 236, 1)"
                                        name="Tidak Dialokasikan"
                                        radius={[4, 4, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="kegiatan_1_2"
                                        fill="rgb(251, 191, 36)"
                                        name="1-2 Kegiatan"
                                        radius={[4, 4, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="kegiatan_3_5"
                                        fill="rgb(34, 197, 94)"
                                        name="3-5 Kegiatan"
                                        radius={[4, 4, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="kegiatan_lebih_5"
                                        fill="rgb(239, 68, 68)"
                                        name=">5 Kegiatan (Overload)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Summary Cards */}
                        <div className="mt-4 grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 md:grid-cols-4 dark:border-neutral-800">
                            <div className="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                <div className="mb-1 flex items-center gap-2">
                                    <AlertCircle className="size-3.5 text-red-600 dark:text-red-400" />
                                    <span className="text-[10px] font-medium text-red-600 uppercase dark:text-red-400">
                                        Tidak Dialokasikan
                                    </span>
                                </div>
                                <p className="text-2xl font-bold text-red-700 dark:text-red-300">
                                    {
                                        petugasMonitoringSummary.tidak_dialokasikan
                                    }
                                </p>
                                <p className="mt-0.5 text-[10px] text-red-600/70 dark:text-red-400/70">
                                    Januari - sekarang {currentYear}
                                </p>
                            </div>

                            <div className="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                                <div className="mb-1 flex items-center gap-2">
                                    <AlertTriangle className="size-3.5 text-amber-600 dark:text-amber-400" />
                                    <span className="text-[10px] font-medium text-amber-600 uppercase dark:text-amber-400">
                                        Under-utilized
                                    </span>
                                </div>
                                <p className="text-2xl font-bold text-amber-700 dark:text-amber-300">
                                    {petugasMonitoringSummary.kegiatan_1_2}
                                </p>
                                <p className="mt-0.5 text-[10px] text-amber-600/70 dark:text-amber-400/70">
                                    1-2 kegiatan (Januari - sekarang{' '}
                                    {currentYear})
                                </p>
                            </div>

                            <div className="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                <div className="mb-1 flex items-center gap-2">
                                    <CheckCircle className="size-3.5 text-green-600 dark:text-green-400" />
                                    <span className="text-[10px] font-medium text-green-600 uppercase dark:text-green-400">
                                        Optimal
                                    </span>
                                </div>
                                <p className="text-2xl font-bold text-green-700 dark:text-green-300">
                                    {petugasMonitoringSummary.kegiatan_3_5}
                                </p>
                                <p className="mt-0.5 text-[10px] text-green-600/70 dark:text-green-400/70">
                                    3-5 kegiatan (Januari - sekarang{' '}
                                    {currentYear})
                                </p>
                            </div>

                            <div className="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                <div className="mb-1 flex items-center gap-2">
                                    <XCircle className="size-3.5 text-red-600 dark:text-red-400" />
                                    <span className="text-[10px] font-medium text-red-600 uppercase dark:text-red-400">
                                        Overload
                                    </span>
                                </div>
                                <p className="text-2xl font-bold text-red-700 dark:text-red-300">
                                    {petugasMonitoringSummary.kegiatan_lebih_5}
                                </p>
                                <p className="mt-0.5 text-[10px] text-red-600/70 dark:text-red-400/70">
                                    &gt;5 kegiatan (Januari - sekarang{' '}
                                    {currentYear})
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Honor Inequality Chart */}
                    <div className="min-w-0 rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="mb-4 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                        Analisis Ketimpangan Honor {currentYear}
                                    </h3>
                                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        Early warning system untuk distribusi
                                        honor yang tidak merata
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="space-y-2">
                            {/* Distribution Chart */}
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart
                                    data={honorInequalityData}
                                    margin={{
                                        top: 5,
                                        right: 30,
                                        left: 0,
                                        bottom: 5,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="currentColor"
                                        className="text-neutral-200 dark:text-neutral-700"
                                        opacity={0.3}
                                    />
                                    <XAxis
                                        dataKey="month"
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 11 }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 11 }}
                                        tickLine={false}
                                        axisLine={false}
                                        tickFormatter={(value) =>
                                            new Intl.NumberFormat(
                                                'id-ID',
                                            ).format(value)
                                        }
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'var(--color-bg)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: '8px',
                                            padding: '8px 12px',
                                        }}
                                        labelStyle={{
                                            color: 'var(--color-text)',
                                            fontWeight: 600,
                                            marginBottom: '4px',
                                        }}
                                        itemStyle={{ fontSize: '12px' }}
                                        itemSorter={(item: any) => {
                                            const order = [
                                                'honor_0_500rb',
                                                'honor_501rb_1500rb',
                                                'honor_1501rb_2500rb',
                                                'honor_2501rb_3500rb',
                                                'honor_lebih_3501rb',
                                            ];
                                            return order.indexOf(item.dataKey);
                                        }}
                                    />
                                    <Legend
                                        wrapperStyle={{ paddingTop: '12px' }}
                                        iconType="square"
                                        iconSize={8}
                                        itemSorter={(item: any) => {
                                            const order = [
                                                'honor_0_500rb',
                                                'honor_501rb_1500rb',
                                                'honor_1501rb_2500rb',
                                                'honor_2501rb_3500rb',
                                                'honor_lebih_3501rb',
                                            ];
                                            return order.indexOf(item.dataKey);
                                        }}
                                    />
                                    <Bar
                                        dataKey="honor_0_500rb"
                                        stackId="a"
                                        fill="rgba(48, 238, 197, 1)"
                                        name="0-500rb"
                                        radius={[0, 0, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="honor_501rb_1500rb"
                                        stackId="a"
                                        fill="rgba(56, 197, 240, 1)"
                                        name="501rb-1,5jt"
                                        radius={[0, 0, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="honor_1501rb_2500rb"
                                        stackId="a"
                                        fill="rgb(34, 197, 94)"
                                        name="1,5jt-2,5jt"
                                        radius={[0, 0, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="honor_2501rb_3500rb"
                                        stackId="a"
                                        fill="rgba(230, 116, 51, 1)"
                                        name="2,5jt-3,5jt"
                                        radius={[0, 0, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="honor_lebih_3501rb"
                                        stackId="a"
                                        fill="rgba(235, 57, 34, 1)"
                                        name=">3,5jt"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>

                            {/* Inequality Metric Chart */}
                            <ResponsiveContainer width="100%" height={180}>
                                <ComposedChart
                                    data={honorInequalityData}
                                    margin={{
                                        top: 20,
                                        right: 30,
                                        left: 0,
                                        bottom: 5,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="currentColor"
                                        className="text-neutral-200 dark:text-neutral-700"
                                        opacity={0.3}
                                    />
                                    <XAxis
                                        dataKey="month"
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 11 }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        yAxisId="left"
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 11 }}
                                        tickLine={false}
                                        axisLine={false}
                                        label={{
                                            value: 'Honor (Rp)',
                                            angle: -90,
                                            position: 'insideLeft',
                                            style: { fontSize: 10 },
                                        }}
                                        tickFormatter={(value) =>
                                            new Intl.NumberFormat('id-ID', {
                                                notation: 'compact',
                                                compactDisplay: 'short',
                                            }).format(value)
                                        }
                                    />
                                    <YAxis
                                        yAxisId="right"
                                        orientation="right"
                                        stroke="currentColor"
                                        className="text-neutral-600 dark:text-neutral-400"
                                        tick={{ fontSize: 11 }}
                                        tickLine={false}
                                        axisLine={false}
                                        label={{
                                            value: 'CV (%)',
                                            angle: 90,
                                            position: 'insideRight',
                                            style: { fontSize: 10 },
                                        }}
                                        tickFormatter={(value) => value + '%'}
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'var(--color-bg)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: '8px',
                                            padding: '8px 12px',
                                        }}
                                        labelStyle={{
                                            color: 'var(--color-text)',
                                            fontWeight: 600,
                                            marginBottom: '4px',
                                        }}
                                        itemStyle={{ fontSize: '12px' }}
                                        formatter={(
                                            value: number | undefined,
                                            name: string | undefined,
                                        ) => {
                                            if (name === 'Koef. Variasi (%)') {
                                                return (
                                                    (value?.toFixed(2) ?? '0') +
                                                    '%'
                                                );
                                            }
                                            return (
                                                'Rp ' +
                                                (value
                                                    ? new Intl.NumberFormat(
                                                          'id-ID',
                                                          {
                                                              minimumFractionDigits: 0,
                                                              maximumFractionDigits: 0,
                                                          },
                                                      ).format(value)
                                                    : '0')
                                            );
                                        }}
                                    />
                                    <Legend
                                        wrapperStyle={{ paddingTop: '12px' }}
                                        iconSize={8}
                                    />
                                    <Area
                                        yAxisId="left"
                                        type="monotone"
                                        dataKey="honor_tertinggi"
                                        fill="rgba(239, 68, 68, 0.1)"
                                        stroke="rgb(239, 68, 68)"
                                        strokeWidth={2}
                                        name="Honor Tertinggi"
                                    />
                                    <Area
                                        yAxisId="left"
                                        type="monotone"
                                        dataKey="honor_terendah"
                                        fill="rgba(34, 197, 94, 0.1)"
                                        stroke="rgb(34, 197, 94)"
                                        strokeWidth={2}
                                        name="Honor Terendah"
                                    />
                                    <Line
                                        yAxisId="left"
                                        type="monotone"
                                        dataKey="rata_rata_honor"
                                        stroke="rgb(99, 102, 241)"
                                        strokeWidth={2.5}
                                        dot={{
                                            fill: 'rgb(99, 102, 241)',
                                            r: 4,
                                        }}
                                        name="Rata-rata Honor"
                                    />
                                    <Line
                                        yAxisId="right"
                                        type="monotone"
                                        dataKey="koefisien_variasi"
                                        stroke="rgb(251, 191, 36)"
                                        strokeWidth={2.5}
                                        strokeDasharray="5 5"
                                        dot={{
                                            fill: 'rgb(251, 191, 36)',
                                            r: 4,
                                        }}
                                        name="Koef. Variasi (%)"
                                    />
                                </ComposedChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Key Metrics */}
                        {honorInequalitySummary.has_data ? (
                            <div className="mt-4 grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 md:grid-cols-4 dark:border-neutral-800">
                                <div className="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                                    <div className="mb-1 flex items-center gap-2">
                                        <TrendingUp className="size-3.5 text-blue-600 dark:text-blue-400" />
                                        <span className="text-[10px] font-medium text-blue-600 uppercase dark:text-blue-400">
                                            Rata-rata
                                        </span>
                                    </div>
                                    <p className="text-xl font-bold text-blue-700 dark:text-blue-300">
                                        {new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0,
                                        }).format(
                                            honorInequalitySummary.rata_rata_honor ||
                                                0,
                                        )}
                                    </p>
                                    <p className="mt-0.5 text-[10px] text-blue-600/70 dark:text-blue-400/70">
                                        Rp/bulan ({currentYear})
                                    </p>
                                </div>

                                <div
                                    className={`rounded-lg p-3 ${
                                        (honorInequalitySummary.koefisien_variasi ||
                                            0) > 50
                                            ? 'bg-red-50 dark:bg-red-900/20'
                                            : (honorInequalitySummary.koefisien_variasi ||
                                                    0) > 30
                                              ? 'bg-amber-50 dark:bg-amber-900/20'
                                              : 'bg-green-50 dark:bg-green-900/20'
                                    }`}
                                >
                                    <div className="mb-1 flex items-center gap-2">
                                        <AlertTriangle
                                            className={`size-3.5 ${
                                                (honorInequalitySummary.koefisien_variasi ||
                                                    0) > 50
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : (honorInequalitySummary.koefisien_variasi ||
                                                            0) > 30
                                                      ? 'text-amber-600 dark:text-amber-400'
                                                      : 'text-green-600 dark:text-green-400'
                                            }`}
                                        />
                                        <span
                                            className={`text-[10px] font-medium uppercase ${
                                                (honorInequalitySummary.koefisien_variasi ||
                                                    0) > 50
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : (honorInequalitySummary.koefisien_variasi ||
                                                            0) > 30
                                                      ? 'text-amber-600 dark:text-amber-400'
                                                      : 'text-green-600 dark:text-green-400'
                                            }`}
                                        >
                                            Ketimpangan
                                        </span>
                                    </div>
                                    <p
                                        className={`text-2xl font-bold ${
                                            (honorInequalitySummary.koefisien_variasi ||
                                                0) > 50
                                                ? 'text-red-700 dark:text-red-300'
                                                : (honorInequalitySummary.koefisien_variasi ||
                                                        0) > 30
                                                  ? 'text-amber-700 dark:text-amber-300'
                                                  : 'text-green-700 dark:text-green-300'
                                        }`}
                                    >
                                        {(
                                            honorInequalitySummary.koefisien_variasi ||
                                            0
                                        ).toFixed(1)}
                                        %
                                    </p>
                                    <p
                                        className={`mt-0.5 text-[10px] ${
                                            (honorInequalitySummary.koefisien_variasi ||
                                                0) > 50
                                                ? 'text-red-600/70 dark:text-red-400/70'
                                                : (honorInequalitySummary.koefisien_variasi ||
                                                        0) > 30
                                                  ? 'text-amber-600/70 dark:text-amber-400/70'
                                                  : 'text-green-600/70 dark:text-green-400/70'
                                        }`}
                                    >
                                        {(honorInequalitySummary.koefisien_variasi ||
                                            0) > 50
                                            ? 'Tinggi (>50%)'
                                            : (honorInequalitySummary.koefisien_variasi ||
                                                    0) > 30
                                              ? 'Sedang (30-50%)'
                                              : 'Rendah (<30%)'}
                                    </p>
                                </div>

                                <div className="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                    <div className="mb-1 flex items-center gap-2">
                                        <AlertCircle className="size-3.5 text-red-600 dark:text-red-400" />
                                        <span className="text-[10px] font-medium text-red-600 uppercase dark:text-red-400">
                                            Gap Honor
                                        </span>
                                    </div>
                                    <p className="text-xl font-bold text-red-700 dark:text-red-300">
                                        {new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0,
                                        }).format(
                                            honorInequalitySummary.gap_honor ||
                                                0,
                                        )}
                                    </p>
                                    <p className="mt-0.5 text-[10px] text-red-600/70 dark:text-red-400/70">
                                        Rata-rata/bulan ({currentYear})
                                    </p>
                                </div>

                                <div className="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                    <div className="mb-1 flex items-center gap-2">
                                        <Users className="size-3.5 text-green-600 dark:text-green-400" />
                                        <span className="text-[10px] font-medium text-green-600 uppercase dark:text-green-400">
                                            Total Petugas
                                        </span>
                                    </div>
                                    <p className="text-2xl font-bold text-green-700 dark:text-green-300">
                                        {honorInequalitySummary.total_petugas ||
                                            0}
                                    </p>
                                    <p className="mt-0.5 text-[10px] text-green-600/70 dark:text-green-400/70">
                                        Rata-rata/bulan ({currentYear})
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="mt-4 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                <div className="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 p-6 text-center dark:border-neutral-700 dark:bg-neutral-900">
                                    <AlertCircle className="mx-auto size-8 text-neutral-400" />
                                    <p className="mt-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                        Belum Ada Data Honor
                                    </p>
                                    <p className="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                                        Data honor petugas non-organik belum
                                        tersedia untuk tahun ini
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Recent Activities */}
                <div className="grid min-w-0 gap-4 lg:grid-cols-2">
                    {/* Recent Alokasi */}
                    <div className="flex min-w-0 flex-col rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="mb-4 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                            <div className="flex items-center gap-2">
                                <Activity className="size-4 text-blue-600 dark:text-blue-400" />
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                    Alokasi Terbaru
                                </h3>
                            </div>
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="space-y-3">
                                {recentAlokasi.length > 0 ? (
                                    recentAlokasi.map((alokasi) => (
                                        <div
                                            key={alokasi.id}
                                            className="min-w-0 rounded-lg border border-neutral-200 bg-white p-3 transition-all hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700"
                                        >
                                            <div className="mb-2 flex items-start justify-between gap-3">
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                                        {alokasi.kegiatan
                                                            .nama_kegiatan ||
                                                            alokasi.kegiatan
                                                                .kode_kegiatan}
                                                    </div>
                                                    <div className="mt-0.5 text-xs text-neutral-600 dark:text-neutral-400">
                                                        Bulan{' '}
                                                        {
                                                            monthNames[
                                                                alokasi.bulan -
                                                                    1
                                                            ]
                                                        }{' '}
                                                        {alokasi.tahun}
                                                    </div>
                                                </div>
                                                <StatusBadge
                                                    status={alokasi.status}
                                                    showIcon={false}
                                                />
                                            </div>
                                            <div className="flex items-center gap-4 text-xs">
                                                <div className="flex items-center gap-1.5">
                                                    <div className="size-1.5 rounded-full bg-blue-500"></div>
                                                    <span className="text-neutral-600 dark:text-neutral-400">
                                                        Organik:
                                                    </span>
                                                    <span className="font-medium text-neutral-900 dark:text-white">
                                                        {alokasi.jumlah_organik}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-1.5">
                                                    <div className="size-1.5 rounded-full bg-green-500"></div>
                                                    <span className="text-neutral-600 dark:text-neutral-400">
                                                        Non-Organik:
                                                    </span>
                                                    <span className="font-medium text-neutral-900 dark:text-white">
                                                        {
                                                            alokasi.jumlah_non_organik
                                                        }
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-center dark:border-neutral-700 dark:bg-neutral-900">
                                        <Activity className="mx-auto size-6 text-neutral-400" />
                                        <p className="mt-2 text-xs text-neutral-600 dark:text-neutral-400">
                                            Belum ada data alokasi
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Kegiatan Bulan Ini */}
                    <div className="flex min-w-0 flex-col rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="mb-4 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                            <div className="flex min-w-0 items-center justify-between gap-2">
                                <div className="flex min-w-0 flex-1 items-center gap-2">
                                    <Calendar className="size-4 flex-shrink-0 text-green-600 dark:text-green-400" />
                                    <h3 className="truncate text-base font-semibold text-neutral-900 dark:text-white">
                                        Kegiatan {monthNames[currentMonth - 1]}{' '}
                                        {currentYear}
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
                                        const canEditAlokasi = [
                                            'admin',
                                            'operator',
                                            'ketua_tim',
                                        ].includes(auth.activeRole?.name);
                                        const canEditSk = [
                                            'admin',
                                            'operator',
                                            'pj',
                                        ].includes(auth.activeRole?.name);
                                        const canEditSpk = [
                                            'admin',
                                            'operator',
                                            'approver',
                                        ].includes(auth.activeRole?.name);

                                        const hasActions =
                                            canEditAlokasi ||
                                            canEditSk ||
                                            canEditSpk;

                                        return (
                                            <div
                                                key={kegiatan.id}
                                                className="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
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
                                                            {kegiatan
                                                                .periode_alokasi
                                                                ?.has_alokasi ? (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                        <CheckCircle className="size-4" />
                                                                        {
                                                                            kegiatan
                                                                                .periode_alokasi
                                                                                .jumlah_petugas
                                                                        }{' '}
                                                                        petugas
                                                                    </span>
                                                                    {canEditAlokasi && (
                                                                        <Link
                                                                            href={`/alokasi/periode/${kegiatan.hashed_id}/${currentYear}/${String(currentMonth).padStart(2, '0')}`}
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Eye className="mr-1 size-3" />
                                                                                Lihat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                                        <AlertTriangle className="size-4" />
                                                                        Belum
                                                                        ada
                                                                    </span>
                                                                    {canEditAlokasi && (
                                                                        <Link
                                                                            href={`/alokasi/create?kegiatan_id=${kegiatan.hashed_id}`}
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Plus className="mr-1 size-3" />
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
                                                                        {kegiatan
                                                                            .sk
                                                                            .is_signed
                                                                            ? 'Signed'
                                                                            : 'Draft'}
                                                                    </span>
                                                                    {canEditSk && (
                                                                        <Link
                                                                            href={`/sk-kpa/${kegiatan.sk.hashed_id}`}
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Eye className="mr-1 size-3" />
                                                                                Lihat
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                                        <XCircle className="size-4" />
                                                                        Belum
                                                                        dibuat
                                                                    </span>
                                                                    {canEditSk &&
                                                                        kegiatan
                                                                            .periode_alokasi
                                                                            ?.has_alokasi && (
                                                                            <Link
                                                                                href={`/sk-kpa/create?kegiatan_id=${kegiatan.id}`}
                                                                            >
                                                                                <Button
                                                                                    size="sm"
                                                                                    variant="ghost"
                                                                                >
                                                                                    <Plus className="mr-1 size-3" />
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
                                                                Perjanjian Kerja
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {kegiatan.spk
                                                                .has_spk ? (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                        <CheckCircle className="size-4" />
                                                                        {
                                                                            kegiatan
                                                                                .spk
                                                                                .count
                                                                        }{' '}
                                                                        Perjanjian Kerja
                                                                    </span>
                                                                    {canEditSpk &&
                                                                        kegiatan.sk && (
                                                                            <Link
                                                                                href={`/sk-kpa/${kegiatan.sk.hashed_id}`}
                                                                            >
                                                                                <Button
                                                                                    size="sm"
                                                                                    variant="ghost"
                                                                                >
                                                                                    <Eye className="mr-1 size-3" />
                                                                                    Lihat
                                                                                </Button>
                                                                            </Link>
                                                                        )}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                                        <XCircle className="size-4" />
                                                                        Belum
                                                                        dibuat
                                                                    </span>
                                                                    {canEditSpk &&
                                                                        kegiatan.sk && (
                                                                            <Link
                                                                                href={`/sk-kpa/${kegiatan.sk.hashed_id}`}
                                                                            >
                                                                                <Button
                                                                                    size="sm"
                                                                                    variant="ghost"
                                                                                >
                                                                                    <Plus className="mr-1 size-3" />
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
                                <div className="rounded-lg border border-dashed border-neutral-300 bg-white p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
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
