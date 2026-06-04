import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { buildNavItems } from '@/lib/nav-items';
import { dashboard } from '@/routes';
import { index as bastIndex } from '@/routes/bast';
import { index as kegiatanIndex } from '@/routes/kegiatan';
import { index as petugasIndex } from '@/routes/petugas';
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    AlertTriangle,
    ArrowRight,
    Briefcase,
    Calendar,
    CheckCircle,
    ChevronRight,
    Clock,
    Eye,
    FileText,
    Plus,
    ScrollText,
    Search,
    Star,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Area,
    Bar,
    BarChart,
    CartesianGrid,
    Tooltip as ChartTooltip,
    ComposedChart,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

interface DashboardStats {
    total_petugas: number;
    total_kegiatan: number;
    draft_kegiatan: number;
    bast_pending: number;
}

interface AttentionItem {
    key: string;
    label: string;
    count: number;
    url: string;
    description: string;
    severity: 'warning' | 'danger';
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
    total_dialokasikan: number;
    total_kegiatan: number;
    avg_kegiatan: number;
    max_kegiatan: number;
    min_kegiatan: number;
    cv_kegiatan: number;
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
    sk_meta: {
        show_missing: boolean;
        source: 'bulan_berjalan' | 'periode_terakhir';
        source_bulan: number | null;
        source_tahun: number | null;
    };
    spk: {
        count: number;
        has_spk: boolean;
        required_count: number;
        requires_document: boolean;
        is_complete: boolean;
        detail_url: string | null;
    };
    bast: {
        count: number;
        has_bast: boolean;
        required_count: number;
        requires_document: boolean;
        is_complete: boolean;
        detail_url: string | null;
    };
}

interface PetugasMonitoringSummary {
    tidak_dialokasikan: number;
    kegiatan_1_2: number;
    kegiatan_3_5: number;
    kegiatan_lebih_5: number;
}

interface WorkloadInequalitySummary {
    has_data: boolean;
    avg_cv?: number;
    avg_kegiatan?: number;
    pct_overload?: number;
    pct_underutilized?: number;
    total_non_organik_aktif?: number;
    avg_allocated?: number;
    max_allocated?: number;
    min_allocated?: number;
    avg_total_kegiatan?: number;
    avg_kegiatan_count?: number;
    rekomendasi_min?: number;
    rekomendasi_max?: number;
    utilization_rate?: number;
    insights?: string[];
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
    insights?: string[];
}

interface MitraReviewSummary {
    year: {
        total_reviews: number;
        avg_rating: number;
        mitra_reviewed: number;
        positive_percentage: number;
    };
    current_month: {
        total_reviews: number;
        avg_rating: number;
        mitra_reviewed: number;
        positive_percentage: number;
    };
    top_mitra: Array<{
        petugas_id: number;
        petugas_nama: string;
        avg_rating: number;
        total_review: number;
        balanced_score: number;
    }>;
    best_mitra_current_month: {
        petugas_id: number;
        petugas_nama: string;
        avg_rating: number;
        total_review: number;
        balanced_score: number;
    } | null;
}

interface DashboardProps {
    stats: DashboardStats;
    additionalStats: AdditionalStats;
    recentAlokasi: RecentAlokasi[];
    kegiatanBulanIni: KegiatanBulanIni[];
    chartData: ChartData[];
    petugasMonitoringData: PetugasMonitoringData[];
    honorInequalityData: HonorInequalityData[];
    honorPerPetugas: {
        petugas_id: number;
        nama: string;
        per_bulan: Record<string, number>;
        total: number;
    }[];
    honorMonths: string[];
    petugasMonitoringSummary: PetugasMonitoringSummary;
    workloadInequalitySummary: WorkloadInequalitySummary;
    honorInequalitySummary: HonorInequalitySummary;
    mitraReviewSummary: MitraReviewSummary;
    attentionItems: AttentionItem[];
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
    kegiatanBulanIni,
    chartData,
    petugasMonitoringData,
    honorInequalityData,
    honorPerPetugas,
    honorMonths,
    petugasMonitoringSummary,
    workloadInequalitySummary,
    honorInequalitySummary,
    mitraReviewSummary,
    attentionItems,
    currentMonth,
    currentYear,
    userRole,
}: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const accessibleLinks = useMemo(() => {
        const items = buildNavItems(auth.activeRole?.name);
        const links = new Set<string>();

        const collect = (
            navItems: Array<{ href: string; items?: Array<unknown> }>,
        ) => {
            navItems.forEach((item) => {
                if (typeof item.href === 'string' && item.href !== '#') {
                    links.add(item.href);
                }

                if (Array.isArray(item.items)) {
                    collect(
                        item.items as Array<{
                            href: string;
                            items?: Array<unknown>;
                        }>,
                    );
                }
            });
        };

        collect(items as Array<{ href: string; items?: Array<unknown> }>);

        return links;
    }, [auth.activeRole?.name]);

    const canViewPetugas = accessibleLinks.has('/petugas');
    const canViewKegiatan = accessibleLinks.has('/kegiatan');
    const canViewBast = accessibleLinks.has('/bast');
    const activeRoleName = auth.activeRole?.name ?? userRole;
    const canViewHonorPerPetugas = ['admin', 'operator', 'pj'].includes(
        activeRoleName ?? '',
    );
    const [mitraInsightMode, setMitraInsightMode] = useState<
        'current_month' | 'year'
    >('current_month');
    const [kegiatanSearch, setKegiatanSearch] = useState('');
    const [kegiatanFilter, setKegiatanFilter] = useState<
        | 'semua'
        | 'butuh_alokasi'
        | 'butuh_sk'
        | 'butuh_spk'
        | 'butuh_bast'
        | 'lengkap'
    >('semua');
    const [kegiatanPage, setKegiatanPage] = useState(1);
    const kegiatanPerPage = 6;
    const [honorPerPetugasPage, setHonorPerPetugasPage] = useState(1);
    const honorPerPetugasPageSize = 15;

    const kegiatanSummary = useMemo(() => {
        const butuhAlokasi = kegiatanBulanIni.filter(
            (kegiatan) => !kegiatan.periode_alokasi?.has_alokasi,
        ).length;
        const butuhSk = kegiatanBulanIni.filter(
            (kegiatan) =>
                kegiatan.periode_alokasi?.has_alokasi &&
                kegiatan.sk_meta.show_missing,
        ).length;
        const butuhSpk = kegiatanBulanIni.filter(
            (kegiatan) =>
                kegiatan.periode_alokasi?.has_alokasi &&
                kegiatan.spk.requires_document &&
                !kegiatan.spk.is_complete,
        ).length;
        const butuhBast = kegiatanBulanIni.filter(
            (kegiatan) =>
                kegiatan.periode_alokasi?.has_alokasi &&
                kegiatan.bast.requires_document &&
                !kegiatan.bast.is_complete,
        ).length;
        const lengkap = kegiatanBulanIni.filter(
            (kegiatan) =>
                kegiatan.periode_alokasi?.has_alokasi &&
                !!kegiatan.sk &&
                (!kegiatan.spk.requires_document || kegiatan.spk.is_complete) &&
                (!kegiatan.bast.requires_document || kegiatan.bast.is_complete),
        ).length;

        return {
            semua: kegiatanBulanIni.length,
            butuh_alokasi: butuhAlokasi,
            butuh_sk: butuhSk,
            butuh_spk: butuhSpk,
            butuh_bast: butuhBast,
            lengkap,
        };
    }, [kegiatanBulanIni]);

    const filteredKegiatanBulanIni = useMemo(() => {
        const search = kegiatanSearch.trim().toLowerCase();

        return kegiatanBulanIni.filter((kegiatan) => {
            const matchSearch =
                search.length === 0 ||
                kegiatan.nama_kegiatan.toLowerCase().includes(search) ||
                kegiatan.kode_kegiatan.toLowerCase().includes(search);

            if (!matchSearch) {
                return false;
            }

            switch (kegiatanFilter) {
                case 'butuh_alokasi':
                    return !kegiatan.periode_alokasi?.has_alokasi;
                case 'butuh_sk':
                    return (
                        kegiatan.periode_alokasi?.has_alokasi &&
                        kegiatan.sk_meta.show_missing
                    );
                case 'butuh_spk':
                    return (
                        kegiatan.periode_alokasi?.has_alokasi &&
                        kegiatan.spk.requires_document &&
                        !kegiatan.spk.is_complete
                    );
                case 'butuh_bast':
                    return (
                        kegiatan.periode_alokasi?.has_alokasi &&
                        kegiatan.bast.requires_document &&
                        !kegiatan.bast.is_complete
                    );
                case 'lengkap':
                    return (
                        kegiatan.periode_alokasi?.has_alokasi &&
                        !!kegiatan.sk &&
                        (!kegiatan.spk.requires_document ||
                            kegiatan.spk.is_complete) &&
                        (!kegiatan.bast.requires_document ||
                            kegiatan.bast.is_complete)
                    );
                case 'semua':
                default:
                    return true;
            }
        });
    }, [kegiatanBulanIni, kegiatanFilter, kegiatanSearch]);

    const totalKegiatanPages = Math.max(
        1,
        Math.ceil(filteredKegiatanBulanIni.length / kegiatanPerPage),
    );

    const currentKegiatanPage = Math.min(kegiatanPage, totalKegiatanPages);

    const handleKegiatanSearchChange = (value: string) => {
        setKegiatanSearch(value);
        setKegiatanPage(1);
    };

    const handleKegiatanFilterChange = (
        filter:
            | 'semua'
            | 'butuh_alokasi'
            | 'butuh_sk'
            | 'butuh_spk'
            | 'butuh_bast'
            | 'lengkap',
    ) => {
        setKegiatanFilter(filter);
        setKegiatanPage(1);
    };

    const paginatedKegiatanBulanIni = useMemo(() => {
        const start = (currentKegiatanPage - 1) * kegiatanPerPage;

        return filteredKegiatanBulanIni.slice(start, start + kegiatanPerPage);
    }, [filteredKegiatanBulanIni, currentKegiatanPage]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 overflow-x-hidden">
                {/* Welcome Section */}
                <div className="rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            <h1 className="text-xl font-bold break-words text-neutral-900 dark:text-white">
                                Selamat Datang, {auth.user.name}! 👋
                            </h1>
                            <p className="mt-1 text-sm break-words text-neutral-500 dark:text-neutral-400">
                                {new Date().toLocaleDateString('id-ID', {
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })}{' '}
                                · SIMANTIK — Kelola petugas, kegiatan, dan
                                alokasi dengan mudah
                            </p>
                        </div>
                        <div className="flex flex-shrink-0 items-center gap-2 rounded-lg bg-neutral-100 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                            <Calendar className="size-3.5" />
                            <span>
                                {monthNames[currentMonth - 1]} {currentYear}
                            </span>
                        </div>
                    </div>
                    {attentionItems.length > 0 && (
                        <div className="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800/40 dark:bg-amber-900/20">
                            <span className="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                Perlu Perhatian:
                            </span>
                            {attentionItems.map((item) => (
                                <Link
                                    key={item.key}
                                    href={item.url}
                                    className={`flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors ${
                                        item.severity === 'danger'
                                            ? 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-800/40 dark:text-red-300 dark:hover:bg-red-800/60'
                                            : 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-800/40 dark:text-amber-300 dark:hover:bg-amber-800/60'
                                    }`}
                                    title={item.description}
                                >
                                    {item.key === 'kegiatan_draft' ? (
                                        <Clock className="size-3" />
                                    ) : item.key === 'spk_missing' ? (
                                        <ScrollText className="size-3" />
                                    ) : item.key === 'sk_kpa_missing' ||
                                      item.key === 'sk_kpa_perlu_perubahan' ? (
                                        <FileText className="size-3" />
                                    ) : (
                                        <AlertCircle className="size-3" />
                                    )}
                                    {item.count} {item.label}
                                    <ChevronRight className="size-3" />
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {/* Stats Cards */}
                <div className="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {canViewPetugas ? (
                        <Link
                            href={petugasIndex().url}
                            className="group flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl transition-all hover:border-blue-200/60 hover:shadow-lg dark:border-neutral-700/30 dark:bg-neutral-800/50 dark:hover:border-blue-700/30"
                        >
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
                            <div className="mt-3 flex items-center gap-1 text-xs text-blue-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-blue-400">
                                Lihat semua <ArrowRight className="size-3" />
                            </div>
                        </Link>
                    ) : (
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
                    )}
                    {canViewKegiatan ? (
                        <Link
                            href={kegiatanIndex().url}
                            className="group flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl transition-all hover:border-green-200/60 hover:shadow-lg dark:border-neutral-700/30 dark:bg-neutral-800/50 dark:hover:border-green-700/30"
                        >
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
                            <div className="mt-3 flex items-center gap-1 text-xs text-green-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-green-400">
                                Lihat semua <ArrowRight className="size-3" />
                            </div>
                        </Link>
                    ) : (
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
                    )}
                    {canViewKegiatan ? (
                        <Link
                            href={kegiatanIndex().url}
                            className="group flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl transition-all hover:border-amber-200/60 hover:shadow-lg dark:border-neutral-700/30 dark:bg-neutral-800/50 dark:hover:border-amber-700/30"
                        >
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
                            <div className="mt-3 flex items-center gap-1 text-xs text-amber-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-amber-400">
                                Lihat semua <ArrowRight className="size-3" />
                            </div>
                        </Link>
                    ) : (
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
                    )}
                    {canViewBast ? (
                        <Link
                            href={bastIndex().url}
                            className="group flex min-w-0 flex-col justify-between rounded-2xl border border-white/20 bg-white/40 p-6 shadow-2xl backdrop-blur-2xl transition-all hover:border-purple-200/60 hover:shadow-lg dark:border-neutral-700/30 dark:bg-neutral-800/50 dark:hover:border-purple-700/30"
                        >
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
                            <div className="mt-3 flex items-center gap-1 text-xs text-purple-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-purple-400">
                                Lihat semua <ArrowRight className="size-3" />
                            </div>
                        </Link>
                    ) : (
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
                    )}
                </div>

                {/* Kondisi Ekuitas Mitra — replaces decorative SK/SPK + proportion cards */}
                <div className="min-w-0 rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="mb-5 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                        <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                            Kondisi Ekuitas Mitra —{' '}
                            {monthNames[currentMonth - 1]} {currentYear}
                        </h3>
                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Apakah beban kerja dan honor sudah terdistribusi
                            secara adil untuk seluruh mitra non-organik?
                        </p>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Workload Equity */}
                        {(() => {
                            const currentMonthWorkload =
                                petugasMonitoringData[
                                    petugasMonitoringData.length - 1
                                ];
                            const totalPool =
                                workloadInequalitySummary.total_non_organik_aktif ||
                                0;
                            if (!currentMonthWorkload) {
                                return null;
                            }
                            const allocated =
                                currentMonthWorkload.total_dialokasikan;
                            const idle =
                                currentMonthWorkload.tidak_dialokasikan;
                            const overload =
                                currentMonthWorkload.kegiatan_lebih_5;
                            const cv = currentMonthWorkload.cv_kegiatan;
                            const cvLevel =
                                cv < 30
                                    ? 'Baik'
                                    : cv < 50
                                      ? 'Sedang'
                                      : 'Tinggi';
                            const cvColor =
                                cv < 30
                                    ? 'text-green-600 dark:text-green-400'
                                    : cv < 50
                                      ? 'text-amber-600 dark:text-amber-400'
                                      : 'text-red-600 dark:text-red-400';
                            const utilizationPct =
                                totalPool > 0
                                    ? Math.round((allocated / totalPool) * 100)
                                    : 0;
                            return (
                                <div>
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Users className="size-4 text-indigo-600 dark:text-indigo-400" />
                                            <span className="text-sm font-semibold text-neutral-900 dark:text-white">
                                                Ekuitas Beban Kerja
                                            </span>
                                        </div>
                                        <span
                                            className={`text-xs font-medium ${cvColor}`}
                                        >
                                            CV {cv.toFixed(1)}% — {cvLevel}
                                        </span>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <div className="rounded-lg bg-neutral-50 p-3 dark:bg-neutral-800">
                                            <p className="text-[10px] text-neutral-500 uppercase dark:text-neutral-400">
                                                Mitra Aktif
                                            </p>
                                            <p className="text-2xl font-bold text-neutral-900 dark:text-white">
                                                {totalPool}
                                            </p>
                                            <p className="text-[10px] text-neutral-500 dark:text-neutral-400">
                                                non-organik
                                            </p>
                                        </div>
                                        <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/20">
                                            <p className="text-[10px] text-indigo-600 uppercase dark:text-indigo-400">
                                                Dialokasikan
                                            </p>
                                            <p className="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                                                {allocated}
                                            </p>
                                            <p className="text-[10px] text-indigo-500 dark:text-indigo-400">
                                                {utilizationPct}% utilisasi
                                            </p>
                                        </div>
                                        <div
                                            className={`rounded-lg p-3 ${
                                                idle > totalPool * 0.3
                                                    ? 'bg-red-50 dark:bg-red-900/20'
                                                    : 'bg-amber-50 dark:bg-amber-900/20'
                                            }`}
                                        >
                                            <p
                                                className={`text-[10px] uppercase ${
                                                    idle > totalPool * 0.3
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-amber-600 dark:text-amber-400'
                                                }`}
                                            >
                                                Idle
                                            </p>
                                            <p
                                                className={`text-2xl font-bold ${
                                                    idle > totalPool * 0.3
                                                        ? 'text-red-700 dark:text-red-300'
                                                        : 'text-amber-700 dark:text-amber-300'
                                                }`}
                                            >
                                                {idle}
                                            </p>
                                            <p
                                                className={`text-[10px] ${
                                                    idle > totalPool * 0.3
                                                        ? 'text-red-500 dark:text-red-400'
                                                        : 'text-amber-500 dark:text-amber-400'
                                                }`}
                                            >
                                                tidak dapat tugas
                                            </p>
                                        </div>
                                        <div
                                            className={`rounded-lg p-3 ${
                                                overload > 0
                                                    ? 'bg-red-50 dark:bg-red-900/20'
                                                    : 'bg-green-50 dark:bg-green-900/20'
                                            }`}
                                        >
                                            <p
                                                className={`text-[10px] uppercase ${
                                                    overload > 0
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-green-600 dark:text-green-400'
                                                }`}
                                            >
                                                Overload
                                            </p>
                                            <p
                                                className={`text-2xl font-bold ${
                                                    overload > 0
                                                        ? 'text-red-700 dark:text-red-300'
                                                        : 'text-green-700 dark:text-green-300'
                                                }`}
                                            >
                                                {overload}
                                            </p>
                                            <p
                                                className={`text-[10px] ${
                                                    overload > 0
                                                        ? 'text-red-500 dark:text-red-400'
                                                        : 'text-green-500 dark:text-green-400'
                                                }`}
                                            >
                                                &gt;5 kegiatan
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3">
                                        <div className="mb-1 flex justify-between text-[10px] text-neutral-500 dark:text-neutral-400">
                                            <span>
                                                Utilisasi mitra bulan ini
                                            </span>
                                            <span>{utilizationPct}%</span>
                                        </div>
                                        <div className="h-1.5 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-700">
                                            <div
                                                className={`h-full rounded-full transition-all ${
                                                    utilizationPct >= 80
                                                        ? 'bg-green-500'
                                                        : utilizationPct >= 50
                                                          ? 'bg-amber-500'
                                                          : 'bg-red-500'
                                                }`}
                                                style={{
                                                    width: `${utilizationPct}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            );
                        })()}

                        {/* Honor Equity */}
                        {(() => {
                            const currentMonthHonor =
                                honorInequalityData[
                                    honorInequalityData.length - 1
                                ];
                            if (
                                !currentMonthHonor ||
                                currentMonthHonor.total_petugas === 0
                            ) {
                                return (
                                    <div className="flex items-center justify-center rounded-lg border border-dashed border-neutral-300 p-6 dark:border-neutral-700">
                                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                            Belum ada data honor bulan ini
                                        </p>
                                    </div>
                                );
                            }
                            const cv = currentMonthHonor.koefisien_variasi;
                            const gap =
                                currentMonthHonor.honor_tertinggi -
                                currentMonthHonor.honor_terendah;
                            const ratio =
                                currentMonthHonor.honor_terendah > 0
                                    ? (
                                          currentMonthHonor.honor_tertinggi /
                                          currentMonthHonor.honor_terendah
                                      ).toFixed(1)
                                    : '∞';
                            const cvLevel =
                                cv < 30
                                    ? 'Baik'
                                    : cv < 50
                                      ? 'Sedang'
                                      : 'Tinggi';
                            const cvColor =
                                cv < 30
                                    ? 'text-green-600 dark:text-green-400'
                                    : cv < 50
                                      ? 'text-amber-600 dark:text-amber-400'
                                      : 'text-red-600 dark:text-red-400';
                            const fmtCompact = (n: number) =>
                                new Intl.NumberFormat('id-ID', {
                                    notation: 'compact',
                                    maximumFractionDigits: 1,
                                }).format(n);
                            return (
                                <div>
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <TrendingUp className="size-4 text-emerald-600 dark:text-emerald-400" />
                                            <span className="text-sm font-semibold text-neutral-900 dark:text-white">
                                                Ekuitas Honor
                                            </span>
                                        </div>
                                        <span
                                            className={`text-xs font-medium ${cvColor}`}
                                        >
                                            CV {cv.toFixed(1)}% — {cvLevel}
                                        </span>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <div className="rounded-lg bg-neutral-50 p-3 dark:bg-neutral-800">
                                            <p className="text-[10px] text-neutral-500 uppercase dark:text-neutral-400">
                                                Mitra Teralokasi
                                            </p>
                                            <p className="text-2xl font-bold text-neutral-900 dark:text-white">
                                                {
                                                    currentMonthHonor.total_petugas
                                                }
                                            </p>
                                            <p className="text-[10px] text-neutral-500 dark:text-neutral-400">
                                                bulan ini
                                            </p>
                                        </div>
                                        <div className="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/20">
                                            <p className="text-[10px] text-emerald-600 uppercase dark:text-emerald-400">
                                                Rata-rata
                                            </p>
                                            <p className="text-xl font-bold text-emerald-700 dark:text-emerald-300">
                                                {fmtCompact(
                                                    currentMonthHonor.rata_rata_honor,
                                                )}
                                            </p>
                                            <p className="text-[10px] text-emerald-500 dark:text-emerald-400">
                                                per petugas
                                            </p>
                                        </div>
                                        <div className="rounded-lg bg-sky-50 p-3 dark:bg-sky-900/20">
                                            <p className="text-[10px] text-sky-600 uppercase dark:text-sky-400">
                                                Tertinggi
                                            </p>
                                            <p className="text-xl font-bold text-sky-700 dark:text-sky-300">
                                                {fmtCompact(
                                                    currentMonthHonor.honor_tertinggi,
                                                )}
                                            </p>
                                            <p className="text-[10px] text-sky-500 dark:text-sky-400">
                                                {ratio}× di atas terendah
                                            </p>
                                        </div>
                                        <div
                                            className={`rounded-lg p-3 ${
                                                cv >= 50
                                                    ? 'bg-red-50 dark:bg-red-900/20'
                                                    : cv >= 30
                                                      ? 'bg-amber-50 dark:bg-amber-900/20'
                                                      : 'bg-green-50 dark:bg-green-900/20'
                                            }`}
                                        >
                                            <p
                                                className={`text-[10px] uppercase ${cvColor}`}
                                            >
                                                Gap Honor
                                            </p>
                                            <p
                                                className={`text-xl font-bold ${
                                                    cv >= 50
                                                        ? 'text-red-700 dark:text-red-300'
                                                        : cv >= 30
                                                          ? 'text-amber-700 dark:text-amber-300'
                                                          : 'text-green-700 dark:text-green-300'
                                                }`}
                                            >
                                                {fmtCompact(gap)}
                                            </p>
                                            <p
                                                className={`text-[10px] ${
                                                    cv >= 50
                                                        ? 'text-red-500 dark:text-red-400'
                                                        : cv >= 30
                                                          ? 'text-amber-500 dark:text-amber-400'
                                                          : 'text-green-500 dark:text-green-400'
                                                }`}
                                            >
                                                selisih max–min
                                            </p>
                                        </div>
                                    </div>
                                    {currentMonthHonor.honor_tertinggi > 0 && (
                                        <div className="mt-3 rounded-lg bg-neutral-50 p-3 dark:bg-neutral-800">
                                            <p className="mb-2 text-[10px] font-medium text-neutral-600 dark:text-neutral-400">
                                                Sebaran honor bulan ini
                                            </p>
                                            <div className="mb-2 flex items-center justify-between text-[10px] text-neutral-500 dark:text-neutral-400">
                                                <span className="flex items-center gap-1">
                                                    <span className="inline-block size-2 rounded-full bg-green-400" />
                                                    Terendah:{' '}
                                                    {fmtCompact(
                                                        currentMonthHonor.honor_terendah,
                                                    )}
                                                </span>
                                                <span>
                                                    Rata-rata:{' '}
                                                    {fmtCompact(
                                                        currentMonthHonor.rata_rata_honor,
                                                    )}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <span className="inline-block size-2 rounded-full bg-red-400" />
                                                    Tertinggi:{' '}
                                                    {fmtCompact(
                                                        currentMonthHonor.honor_tertinggi,
                                                    )}
                                                </span>
                                            </div>
                                            <div className="relative h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                <div className="absolute h-full w-full rounded-full bg-gradient-to-r from-green-400 to-red-400" />
                                                <div
                                                    className="absolute top-0 h-full w-0.5 bg-white dark:bg-neutral-900"
                                                    style={{
                                                        left: `${(currentMonthHonor.rata_rata_honor / currentMonthHonor.honor_tertinggi) * 100}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })()}
                    </div>
                </div>

                {/* Ringkasan Penilaian Mitra */}
                <div className="min-w-0 rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                        <div>
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                Ringkasan Penilaian Mitra Statistik
                            </h3>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Snapshot kualitas mitra untuk pemantauan cepat
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    mitraInsightMode === 'current_month'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    setMitraInsightMode('current_month')
                                }
                            >
                                Bulan Ini
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    mitraInsightMode === 'year'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => setMitraInsightMode('year')}
                            >
                                Tahun Ini
                            </Button>
                            <Link href="/monitoring-penilaian-mitra">
                                <Button size="sm" variant="outline">
                                    Detail
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="rounded-lg bg-sky-50 p-3 dark:bg-sky-900/20">
                            <p className="text-xs text-sky-700 dark:text-sky-300">
                                Total Review
                            </p>
                            <p className="mt-1 text-2xl font-bold text-sky-900 dark:text-sky-200">
                                {
                                    mitraReviewSummary[mitraInsightMode]
                                        .total_reviews
                                }
                            </p>
                        </div>
                        <div className="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                            <p className="text-xs text-amber-700 dark:text-amber-300">
                                Rata-rata Rating
                            </p>
                            <p className="mt-1 flex items-center gap-2 text-2xl font-bold text-amber-900 dark:text-amber-200">
                                {
                                    mitraReviewSummary[mitraInsightMode]
                                        .avg_rating
                                }
                                <Star className="size-4 fill-amber-500 text-amber-500" />
                            </p>
                        </div>
                        <div className="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/20">
                            <p className="text-xs text-emerald-700 dark:text-emerald-300">
                                Mitra Dinilai
                            </p>
                            <p className="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-200">
                                {
                                    mitraReviewSummary[mitraInsightMode]
                                        .mitra_reviewed
                                }
                            </p>
                        </div>
                        <div className="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20">
                            <p className="text-xs text-purple-700 dark:text-purple-300">
                                Rating Positif (4-5)
                            </p>
                            <p className="mt-1 text-2xl font-bold text-purple-900 dark:text-purple-200">
                                {
                                    mitraReviewSummary[mitraInsightMode]
                                        .positive_percentage
                                }
                                %
                            </p>
                        </div>
                        <div className="rounded-lg bg-rose-50 p-3 dark:bg-rose-900/20">
                            <p className="text-xs text-rose-700 dark:text-rose-300">
                                Mitra Terbaik Bulan Ini
                            </p>
                            {mitraReviewSummary.best_mitra_current_month ? (
                                <>
                                    <p className="mt-1 truncate text-sm font-bold text-rose-900 dark:text-rose-200">
                                        {
                                            mitraReviewSummary
                                                .best_mitra_current_month
                                                .petugas_nama
                                        }
                                    </p>
                                    <p className="mt-1 text-[11px] text-rose-700/80 dark:text-rose-200/80">
                                        {
                                            mitraReviewSummary
                                                .best_mitra_current_month
                                                .total_review
                                        }{' '}
                                        review · Balanced{' '}
                                        {
                                            mitraReviewSummary
                                                .best_mitra_current_month
                                                .balanced_score
                                        }
                                    </p>
                                </>
                            ) : (
                                <p className="mt-1 text-sm text-rose-700/80 dark:text-rose-200/80">
                                    Belum ada review bulan ini
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        {mitraReviewSummary.top_mitra.map((mitra, index) => (
                            <div
                                key={mitra.petugas_id}
                                className="rounded-lg border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900"
                            >
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Top {index + 1}
                                </p>
                                <p className="mt-1 truncate text-sm font-semibold text-neutral-900 dark:text-white">
                                    {mitra.petugas_nama}
                                </p>
                                <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                                    <span>{mitra.total_review} review</span>
                                    <span>Balanced {mitra.balanced_score}</span>
                                    <span className="font-semibold text-amber-600 dark:text-amber-400">
                                        {mitra.avg_rating} / 5
                                    </span>
                                </div>
                            </div>
                        ))}
                        {mitraReviewSummary.top_mitra.length === 0 && (
                            <div className="rounded-lg border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 md:col-span-3 dark:border-neutral-700 dark:text-neutral-400">
                                Belum ada data penilaian mitra pada tahun aktif.
                            </div>
                        )}
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
                                    <ChartTooltip
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
                                    <ChartTooltip
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
                                    rata-rata per bulan
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

                        {/* Workload Inequality Analysis */}
                        {workloadInequalitySummary.has_data && (
                            <div className="mt-6 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                <div className="mb-3 flex items-center gap-2">
                                    <AlertTriangle className="size-3.5 text-amber-500 dark:text-amber-400" />
                                    <h4 className="text-xs font-semibold tracking-wide text-neutral-700 uppercase dark:text-neutral-300">
                                        Analisis Ketimpangan Beban Kerja
                                    </h4>
                                </div>

                                <ResponsiveContainer width="100%" height={160}>
                                    <ComposedChart
                                        data={petugasMonitoringData}
                                        margin={{
                                            top: 5,
                                            right: 40,
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
                                            tickFormatter={(v) =>
                                                Number(v).toFixed(1)
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
                                            tickFormatter={(v) => `${v}%`}
                                        />
                                        <ChartTooltip
                                            contentStyle={{
                                                backgroundColor:
                                                    'var(--color-bg)',
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
                                            formatter={(rawValue, name) => {
                                                const value =
                                                    typeof rawValue === 'number'
                                                        ? rawValue
                                                        : undefined;
                                                if (name === 'CV Beban (%)') {
                                                    return [
                                                        (value?.toFixed(1) ??
                                                            '0') + '%',
                                                        name,
                                                    ];
                                                }
                                                return [
                                                    value?.toFixed(2) ?? '0',
                                                    name,
                                                ];
                                            }}
                                        />
                                        <Legend
                                            wrapperStyle={{
                                                paddingTop: '12px',
                                            }}
                                            iconSize={8}
                                        />
                                        <Bar
                                            yAxisId="left"
                                            dataKey="avg_kegiatan"
                                            fill="rgba(99, 102, 241, 0.5)"
                                            name="Rata-rata Kegiatan"
                                            radius={[3, 3, 0, 0]}
                                        />
                                        <Line
                                            yAxisId="right"
                                            type="monotone"
                                            dataKey="cv_kegiatan"
                                            stroke="rgb(251, 191, 36)"
                                            strokeWidth={2.5}
                                            strokeDasharray="5 5"
                                            dot={{
                                                fill: 'rgb(251, 191, 36)',
                                                r: 4,
                                            }}
                                            name="CV Beban (%)"
                                        />
                                    </ComposedChart>
                                </ResponsiveContainer>

                                <div className="mt-3 grid grid-cols-3 gap-3">
                                    <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/20">
                                        <div className="mb-1 flex items-center gap-2">
                                            <Briefcase className="size-3.5 text-indigo-600 dark:text-indigo-400" />
                                            <span className="text-[10px] font-medium text-indigo-600 uppercase dark:text-indigo-400">
                                                Beban Rata-rata
                                            </span>
                                        </div>
                                        <p className="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                                            {(
                                                workloadInequalitySummary.avg_kegiatan ||
                                                0
                                            ).toFixed(1)}
                                        </p>
                                        <p className="mt-0.5 text-[10px] text-indigo-600/70 dark:text-indigo-400/70">
                                            kegiatan/petugas/bulan
                                        </p>
                                    </div>

                                    <div
                                        className={`rounded-lg p-3 ${
                                            (workloadInequalitySummary.avg_cv ||
                                                0) > 50
                                                ? 'bg-red-50 dark:bg-red-900/20'
                                                : (workloadInequalitySummary.avg_cv ||
                                                        0) > 30
                                                  ? 'bg-amber-50 dark:bg-amber-900/20'
                                                  : 'bg-green-50 dark:bg-green-900/20'
                                        }`}
                                    >
                                        <div className="mb-1 flex items-center gap-2">
                                            <AlertTriangle
                                                className={`size-3.5 ${
                                                    (workloadInequalitySummary.avg_cv ||
                                                        0) > 50
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : (workloadInequalitySummary.avg_cv ||
                                                                0) > 30
                                                          ? 'text-amber-600 dark:text-amber-400'
                                                          : 'text-green-600 dark:text-green-400'
                                                }`}
                                            />
                                            <span
                                                className={`text-[10px] font-medium uppercase ${
                                                    (workloadInequalitySummary.avg_cv ||
                                                        0) > 50
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : (workloadInequalitySummary.avg_cv ||
                                                                0) > 30
                                                          ? 'text-amber-600 dark:text-amber-400'
                                                          : 'text-green-600 dark:text-green-400'
                                                }`}
                                            >
                                                CV Ketimpangan
                                            </span>
                                        </div>
                                        <p
                                            className={`text-2xl font-bold ${
                                                (workloadInequalitySummary.avg_cv ||
                                                    0) > 50
                                                    ? 'text-red-700 dark:text-red-300'
                                                    : (workloadInequalitySummary.avg_cv ||
                                                            0) > 30
                                                      ? 'text-amber-700 dark:text-amber-300'
                                                      : 'text-green-700 dark:text-green-300'
                                            }`}
                                        >
                                            {(
                                                workloadInequalitySummary.avg_cv ||
                                                0
                                            ).toFixed(1)}
                                            %
                                        </p>
                                        <p
                                            className={`mt-0.5 text-[10px] ${
                                                (workloadInequalitySummary.avg_cv ||
                                                    0) > 50
                                                    ? 'text-red-600/70 dark:text-red-400/70'
                                                    : (workloadInequalitySummary.avg_cv ||
                                                            0) > 30
                                                      ? 'text-amber-600/70 dark:text-amber-400/70'
                                                      : 'text-green-600/70 dark:text-green-400/70'
                                            }`}
                                        >
                                            {(workloadInequalitySummary.avg_cv ||
                                                0) > 50
                                                ? 'Tinggi (>50%)'
                                                : (workloadInequalitySummary.avg_cv ||
                                                        0) > 30
                                                  ? 'Sedang (30-50%)'
                                                  : 'Rendah (<30%)'}
                                        </p>
                                    </div>

                                    <div
                                        className={`rounded-lg p-3 ${
                                            (workloadInequalitySummary.pct_overload ||
                                                0) > 15
                                                ? 'bg-red-50 dark:bg-red-900/20'
                                                : (workloadInequalitySummary.pct_overload ||
                                                        0) > 5
                                                  ? 'bg-amber-50 dark:bg-amber-900/20'
                                                  : 'bg-green-50 dark:bg-green-900/20'
                                        }`}
                                    >
                                        <div className="mb-1 flex items-center gap-2">
                                            <XCircle
                                                className={`size-3.5 ${
                                                    (workloadInequalitySummary.pct_overload ||
                                                        0) > 15
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : (workloadInequalitySummary.pct_overload ||
                                                                0) > 5
                                                          ? 'text-amber-600 dark:text-amber-400'
                                                          : 'text-green-600 dark:text-green-400'
                                                }`}
                                            />
                                            <span
                                                className={`text-[10px] font-medium uppercase ${
                                                    (workloadInequalitySummary.pct_overload ||
                                                        0) > 15
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : (workloadInequalitySummary.pct_overload ||
                                                                0) > 5
                                                          ? 'text-amber-600 dark:text-amber-400'
                                                          : 'text-green-600 dark:text-green-400'
                                                }`}
                                            >
                                                % Overload
                                            </span>
                                        </div>
                                        <p
                                            className={`text-2xl font-bold ${
                                                (workloadInequalitySummary.pct_overload ||
                                                    0) > 15
                                                    ? 'text-red-700 dark:text-red-300'
                                                    : (workloadInequalitySummary.pct_overload ||
                                                            0) > 5
                                                      ? 'text-amber-700 dark:text-amber-300'
                                                      : 'text-green-700 dark:text-green-300'
                                            }`}
                                        >
                                            {(
                                                workloadInequalitySummary.pct_overload ||
                                                0
                                            ).toFixed(1)}
                                            %
                                        </p>
                                        <p
                                            className={`mt-0.5 text-[10px] ${
                                                (workloadInequalitySummary.pct_overload ||
                                                    0) > 15
                                                    ? 'text-red-600/70 dark:text-red-400/70'
                                                    : (workloadInequalitySummary.pct_overload ||
                                                            0) > 5
                                                      ? 'text-amber-600/70 dark:text-amber-400/70'
                                                      : 'text-green-600/70 dark:text-green-400/70'
                                            }`}
                                        >
                                            petugas dengan &gt;5 kegiatan
                                        </p>
                                    </div>
                                </div>

                                {/* Recommendation Card */}
                                {workloadInequalitySummary.rekomendasi_min !==
                                    undefined && (
                                    <div className="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                                        <div className="mb-3 flex items-center gap-2">
                                            <Briefcase className="size-3.5 text-indigo-600 dark:text-indigo-400" />
                                            <span className="text-xs font-semibold tracking-wide text-indigo-700 uppercase dark:text-indigo-300">
                                                Rekomendasi Utilisasi Mitra
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                                            <div className="rounded-lg bg-white/70 p-3 dark:bg-neutral-800/40">
                                                <p className="mb-0.5 text-[10px] font-medium text-indigo-600 uppercase dark:text-indigo-400">
                                                    Total Non-Organik
                                                </p>
                                                <p className="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                                                    {workloadInequalitySummary.total_non_organik_aktif ||
                                                        0}
                                                </p>
                                                <p className="mt-0.5 text-[10px] text-neutral-500 dark:text-neutral-400">
                                                    petugas aktif
                                                </p>
                                            </div>

                                            <div className="rounded-lg bg-white/70 p-3 dark:bg-neutral-800/40">
                                                <p className="mb-0.5 text-[10px] font-medium text-indigo-600 uppercase dark:text-indigo-400">
                                                    Rata-rata Dialokasikan
                                                </p>
                                                <p className="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                                                    {(
                                                        workloadInequalitySummary.avg_allocated ||
                                                        0
                                                    ).toFixed(1)}
                                                </p>
                                                <p className="mt-0.5 text-[10px] text-neutral-500 dark:text-neutral-400">
                                                    petugas/bulan &middot;{' '}
                                                    {(
                                                        workloadInequalitySummary.utilization_rate ||
                                                        0
                                                    ).toFixed(1)}
                                                    % utilisasi
                                                </p>
                                            </div>

                                            <div className="rounded-lg bg-white/70 p-3 dark:bg-neutral-800/40">
                                                <p className="mb-0.5 text-[10px] font-medium text-indigo-600 uppercase dark:text-indigo-400">
                                                    Rata-rata Kegiatan/Bulan
                                                </p>
                                                <p className="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                                                    {(
                                                        workloadInequalitySummary.avg_kegiatan_count ||
                                                        0
                                                    ).toFixed(1)}
                                                </p>
                                                <p className="mt-0.5 text-[10px] text-neutral-500 dark:text-neutral-400">
                                                    kegiatan tersedia
                                                </p>
                                            </div>

                                            <div className="rounded-lg bg-indigo-100/80 p-3 dark:bg-indigo-900/30">
                                                <p className="mb-0.5 text-[10px] font-medium text-indigo-700 uppercase dark:text-indigo-300">
                                                    Rekomendasi Alokasi
                                                </p>
                                                <p className="text-2xl font-bold text-indigo-800 dark:text-indigo-200">
                                                    {
                                                        workloadInequalitySummary.rekomendasi_min
                                                    }
                                                    –
                                                    {
                                                        workloadInequalitySummary.rekomendasi_max
                                                    }
                                                </p>
                                                <p className="mt-0.5 text-[10px] text-indigo-700/70 dark:text-indigo-300/70">
                                                    petugas/bulan (3–5
                                                    kegiatan/org)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {workloadInequalitySummary.insights &&
                                    workloadInequalitySummary.insights.length >
                                        0 && (
                                        <div className="mt-4 rounded-xl border border-amber-100 bg-amber-50/60 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                                            <div className="mb-2 flex items-center gap-2">
                                                <AlertTriangle className="size-3.5 text-amber-600 dark:text-amber-400" />
                                                <span className="text-xs font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-300">
                                                    Ringkasan
                                                </span>
                                            </div>
                                            <ul className="space-y-1.5">
                                                {workloadInequalitySummary.insights.map(
                                                    (insight, i) => (
                                                        <li
                                                            key={i}
                                                            className="flex items-start gap-2 text-xs text-amber-800 dark:text-amber-200"
                                                        >
                                                            <span className="mt-1 size-1 shrink-0 rounded-full bg-amber-400 dark:bg-amber-500" />
                                                            {insight}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}
                            </div>
                        )}
                    </div>

                    {/* Honor Per Bulan Per Petugas */}
                    {canViewHonorPerPetugas &&
                        (() => {
                            const totalHonorPages = Math.max(
                                1,
                                Math.ceil(
                                    honorPerPetugas.length /
                                        honorPerPetugasPageSize,
                                ),
                            );
                            const currentHonorPage = Math.min(
                                honorPerPetugasPage,
                                totalHonorPages,
                            );
                            const honorPageRows = honorPerPetugas.slice(
                                (currentHonorPage - 1) *
                                    honorPerPetugasPageSize,
                                currentHonorPage * honorPerPetugasPageSize,
                            );
                            return (
                                <div className="min-w-0 rounded-2xl border border-neutral-200/70 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                                    <div className="mb-4 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                                        <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                            Honor Per Petugas Per Bulan{' '}
                                            {currentYear}
                                        </h3>
                                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            Total honor survei non-organik per
                                            bulan (honor + listing), diurutkan
                                            dari terbesar
                                        </p>
                                    </div>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                                    <th className="sticky left-0 bg-white py-2 pr-3 text-left font-semibold whitespace-nowrap text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                                        Nama Petugas
                                                    </th>
                                                    {honorMonths.map((m) => (
                                                        <th
                                                            key={m}
                                                            className="px-2 py-2 text-right font-semibold whitespace-nowrap text-neutral-700 dark:text-neutral-300"
                                                        >
                                                            {m}
                                                        </th>
                                                    ))}
                                                    <th className="py-2 pl-3 text-right font-semibold whitespace-nowrap text-neutral-900 dark:text-white">
                                                        Total
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {honorPageRows.length > 0 ? (
                                                    honorPageRows.map(
                                                        (row, idx) => (
                                                            <tr
                                                                key={
                                                                    row.petugas_id
                                                                }
                                                                className={
                                                                    idx % 2 ===
                                                                    0
                                                                        ? 'bg-neutral-50/50 dark:bg-neutral-800/30'
                                                                        : ''
                                                                }
                                                            >
                                                                <td className="sticky left-0 bg-inherit py-1.5 pr-3 font-medium whitespace-nowrap text-neutral-800 dark:text-neutral-200">
                                                                    {row.nama}
                                                                </td>
                                                                {honorMonths.map(
                                                                    (m) => (
                                                                        <td
                                                                            key={
                                                                                m
                                                                            }
                                                                            className="px-2 py-1.5 text-right text-neutral-600 tabular-nums dark:text-neutral-400"
                                                                        >
                                                                            {row
                                                                                .per_bulan[
                                                                                m
                                                                            ]
                                                                                ? formatRupiah(
                                                                                      row
                                                                                          .per_bulan[
                                                                                          m
                                                                                      ],
                                                                                  )
                                                                                : '—'}
                                                                        </td>
                                                                    ),
                                                                )}
                                                                <td className="py-1.5 pl-3 text-right font-bold whitespace-nowrap text-neutral-900 tabular-nums dark:text-white">
                                                                    {formatRupiah(
                                                                        row.total,
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        ),
                                                    )
                                                ) : (
                                                    <tr>
                                                        <td
                                                            colSpan={
                                                                honorMonths.length +
                                                                2
                                                            }
                                                            className="py-8 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                                        >
                                                            Belum ada data honor
                                                            petugas untuk
                                                            periode saat ini.
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                            <tfoot>
                                                <tr className="border-t-2 border-neutral-300 dark:border-neutral-600">
                                                    <td className="sticky left-0 bg-white py-2 pr-3 font-bold text-neutral-900 dark:bg-neutral-900 dark:text-white">
                                                        Total
                                                    </td>
                                                    {honorMonths.map((m) => (
                                                        <td
                                                            key={m}
                                                            className="px-2 py-2 text-right font-bold text-neutral-900 tabular-nums dark:text-white"
                                                        >
                                                            {formatRupiah(
                                                                honorPerPetugas.reduce(
                                                                    (sum, r) =>
                                                                        sum +
                                                                        (r
                                                                            .per_bulan[
                                                                            m
                                                                        ] ?? 0),
                                                                    0,
                                                                ),
                                                            )}
                                                        </td>
                                                    ))}
                                                    <td className="py-2 pl-3 text-right font-bold text-neutral-900 tabular-nums dark:text-white">
                                                        {formatRupiah(
                                                            honorPerPetugas.reduce(
                                                                (sum, r) =>
                                                                    sum +
                                                                    r.total,
                                                                0,
                                                            ),
                                                        )}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    {honorPerPetugas.length >
                                        honorPerPetugasPageSize && (
                                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-800">
                                            <span className="text-neutral-500 dark:text-neutral-400">
                                                Halaman {currentHonorPage} dari{' '}
                                                {totalHonorPages} &middot;{' '}
                                                {honorPerPetugas.length} petugas
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={
                                                        currentHonorPage <= 1
                                                    }
                                                    onClick={() =>
                                                        setHonorPerPetugasPage(
                                                            (p) =>
                                                                Math.max(
                                                                    p - 1,
                                                                    1,
                                                                ),
                                                        )
                                                    }
                                                >
                                                    Sebelumnya
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={
                                                        currentHonorPage >=
                                                        totalHonorPages
                                                    }
                                                    onClick={() =>
                                                        setHonorPerPetugasPage(
                                                            (p) =>
                                                                Math.min(
                                                                    p + 1,
                                                                    totalHonorPages,
                                                                ),
                                                        )
                                                    }
                                                >
                                                    Berikutnya
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })()}

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
                                    <ChartTooltip
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
                                        itemSorter={(item) => {
                                            const order = [
                                                'honor_0_500rb',
                                                'honor_501rb_1500rb',
                                                'honor_1501rb_2500rb',
                                                'honor_2501rb_3500rb',
                                                'honor_lebih_3501rb',
                                            ];
                                            return order.indexOf(
                                                item.dataKey as string,
                                            );
                                        }}
                                    />
                                    <Legend
                                        wrapperStyle={{ paddingTop: '12px' }}
                                        iconType="square"
                                        iconSize={8}
                                        itemSorter={(item) => {
                                            const order = [
                                                'honor_0_500rb',
                                                'honor_501rb_1500rb',
                                                'honor_1501rb_2500rb',
                                                'honor_2501rb_3500rb',
                                                'honor_lebih_3501rb',
                                            ];
                                            return order.indexOf(
                                                item.dataKey as string,
                                            );
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
                                                maximumFractionDigits: 0,
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
                                    <ChartTooltip
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
                                        formatter={(rawValue, name) => {
                                            const value =
                                                typeof rawValue === 'number'
                                                    ? rawValue
                                                    : undefined;
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
                                            Rata-rata Honor
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
                                            Rata-rata Ketimpangan
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
                                            Rata-rata Gap Honor
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
                                            Rata-rata Petugas
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

                        {/* Insights Summary */}
                        {honorInequalitySummary.has_data &&
                            honorInequalitySummary.insights &&
                            honorInequalitySummary.insights.length > 0 && (
                                <div className="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                                    <div className="mb-2 flex items-center gap-2">
                                        <TrendingUp className="size-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <span className="text-xs font-semibold tracking-wide text-indigo-700 uppercase dark:text-indigo-300">
                                            Ringkasan
                                        </span>
                                    </div>
                                    <ul className="space-y-1.5">
                                        {honorInequalitySummary.insights.map(
                                            (insight, i) => (
                                                <li
                                                    key={i}
                                                    className="flex items-start gap-2 text-xs text-indigo-800 dark:text-indigo-200"
                                                >
                                                    <span className="mt-1 size-1 shrink-0 rounded-full bg-indigo-400 dark:bg-indigo-500" />
                                                    {insight}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}
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
                                {filteredKegiatanBulanIni.length} /{' '}
                                {kegiatanBulanIni.length} kegiatan
                            </span>
                        </div>
                    </div>
                    <div className="mb-4 space-y-3">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                            <Input
                                value={kegiatanSearch}
                                onChange={(event) =>
                                    handleKegiatanSearchChange(
                                        event.target.value,
                                    )
                                }
                                placeholder="Cari nama atau kode kegiatan..."
                                className="pl-9"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2 text-xs md:grid-cols-3 lg:grid-cols-6">
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    kegiatanFilter === 'semua'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    handleKegiatanFilterChange('semua')
                                }
                            >
                                Semua ({kegiatanSummary.semua})
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    kegiatanFilter === 'butuh_alokasi'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    handleKegiatanFilterChange('butuh_alokasi')
                                }
                            >
                                Butuh Alokasi ({kegiatanSummary.butuh_alokasi})
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    kegiatanFilter === 'butuh_sk'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    handleKegiatanFilterChange('butuh_sk')
                                }
                            >
                                Butuh SK ({kegiatanSummary.butuh_sk})
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    kegiatanFilter === 'butuh_spk'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    handleKegiatanFilterChange('butuh_spk')
                                }
                            >
                                Butuh SPK ({kegiatanSummary.butuh_spk})
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    kegiatanFilter === 'butuh_bast'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    handleKegiatanFilterChange('butuh_bast')
                                }
                            >
                                Butuh BAST ({kegiatanSummary.butuh_bast})
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    kegiatanFilter === 'lengkap'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    handleKegiatanFilterChange('lengkap')
                                }
                            >
                                Lengkap ({kegiatanSummary.lengkap})
                            </Button>
                        </div>
                    </div>
                    <div className="flex-1 overflow-auto">
                        {filteredKegiatanBulanIni.length > 0 ? (
                            <div className="space-y-4">
                                {paginatedKegiatanBulanIni.map((kegiatan) => {
                                    const canEditAlokasi = [
                                        'admin',
                                        'operator',
                                        'ketua_tim',
                                    ].includes(auth.activeRole?.name ?? '');
                                    const canEditSk = [
                                        'admin',
                                        'operator',
                                        'pj',
                                    ].includes(auth.activeRole?.name ?? '');
                                    const canViewMonthlyDocuments = [
                                        'admin',
                                        'operator',
                                        'pj',
                                        'approver',
                                        'ketua_tim',
                                    ].includes(auth.activeRole?.name ?? '');

                                    const hasAlokasi =
                                        !!kegiatan.periode_alokasi?.has_alokasi;
                                    const hasSk = !!kegiatan.sk;
                                    const hasSpk =
                                        !kegiatan.spk.requires_document ||
                                        kegiatan.spk.is_complete;
                                    const hasBast =
                                        !kegiatan.bast.requires_document ||
                                        kegiatan.bast.is_complete;
                                    const bastCreateHref = `/bast/create?bulan=${currentMonth}&tahun=${currentYear}`;
                                    const bastActionHref = kegiatan.bast
                                        .is_complete
                                        ? (kegiatan.bast.detail_url ??
                                          bastCreateHref)
                                        : bastCreateHref;
                                    const completionCount = [
                                        hasAlokasi,
                                        hasSk,
                                        hasSpk,
                                        hasBast,
                                    ].filter(Boolean).length;

                                    return (
                                        <div
                                            key={kegiatan.id}
                                            className={`rounded-lg border bg-white p-4 transition-shadow hover:shadow-sm dark:bg-neutral-900 ${
                                                completionCount === 4
                                                    ? 'border-l-4 border-neutral-200 border-l-green-500 dark:border-neutral-800 dark:border-l-green-600'
                                                    : completionCount > 0
                                                      ? 'border-l-4 border-neutral-200 border-l-amber-500 dark:border-neutral-800 dark:border-l-amber-600'
                                                      : 'border-l-4 border-neutral-200 border-l-red-400 dark:border-neutral-800 dark:border-l-red-500'
                                            }`}
                                        >
                                            <div className="mb-3 flex items-start justify-between gap-2">
                                                <div className="min-w-0 flex-1">
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {kegiatan.nama_kegiatan}
                                                    </div>
                                                </div>
                                                <div className="flex flex-shrink-0 items-center gap-0.5">
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span
                                                                className={`flex size-5 cursor-default items-center justify-center rounded-full text-[9px] font-bold ${
                                                                    hasAlokasi
                                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-700 dark:text-neutral-500'
                                                                }`}
                                                            >
                                                                1
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            Alokasi Petugas
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    <span
                                                        className={`mx-0.5 h-px w-3 ${hasSk ? 'bg-green-400' : 'bg-neutral-300 dark:bg-neutral-600'}`}
                                                    />
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span
                                                                className={`flex size-5 cursor-default items-center justify-center rounded-full text-[9px] font-bold ${
                                                                    hasSk
                                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-700 dark:text-neutral-500'
                                                                }`}
                                                            >
                                                                2
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            SK Petugas
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    <span
                                                        className={`mx-0.5 h-px w-3 ${hasSpk ? 'bg-green-400' : 'bg-neutral-300 dark:bg-neutral-600'}`}
                                                    />
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span
                                                                className={`flex size-5 cursor-default items-center justify-center rounded-full text-[9px] font-bold ${
                                                                    hasSpk
                                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-700 dark:text-neutral-500'
                                                                }`}
                                                            >
                                                                3
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            Perjanjian Kerja
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    <span
                                                        className={`mx-0.5 h-px w-3 ${hasBast ? 'bg-green-400' : 'bg-neutral-300 dark:bg-neutral-600'}`}
                                                    />
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span
                                                                className={`flex size-5 cursor-default items-center justify-center rounded-full text-[9px] font-bold ${
                                                                    hasBast
                                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-700 dark:text-neutral-500'
                                                                }`}
                                                            >
                                                                4
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            BAST
                                                        </TooltipContent>
                                                    </Tooltip>
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
                                                                    Belum ada
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
                                                                    {kegiatan.sk
                                                                        .is_signed
                                                                        ? 'Signed'
                                                                        : 'Draft'}
                                                                </span>
                                                                {kegiatan
                                                                    .sk_meta
                                                                    .source ===
                                                                    'periode_terakhir' && (
                                                                    <span className="text-[11px] text-neutral-500 dark:text-neutral-400">
                                                                        Periode
                                                                        terakhir
                                                                    </span>
                                                                )}
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
                                                        ) : kegiatan.sk_meta
                                                              .show_missing ? (
                                                            <>
                                                                <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                                    <XCircle className="size-4" />
                                                                    Belum dibuat
                                                                </span>
                                                                {canEditSk &&
                                                                    kegiatan
                                                                        .periode_alokasi
                                                                        ?.has_alokasi && (
                                                                        <Link
                                                                            href={`/sk-kpa/kegiatan/${kegiatan.hashed_id}/create`}
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
                                                        ) : (
                                                            <span className="flex items-center gap-1 text-neutral-500 dark:text-neutral-400">
                                                                <CheckCircle className="size-4" />
                                                                Mengikuti SK
                                                                periode terakhir
                                                            </span>
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
                                                        {!kegiatan.spk
                                                            .requires_document ? (
                                                            <span className="flex items-center gap-1 text-neutral-500 dark:text-neutral-400">
                                                                <CheckCircle className="size-4" />
                                                                Tidak memerlukan
                                                                Perjanjian Kerja
                                                            </span>
                                                        ) : kegiatan.spk
                                                              .is_complete ? (
                                                            <>
                                                                <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                    <CheckCircle className="size-4" />
                                                                    {
                                                                        kegiatan
                                                                            .spk
                                                                            .count
                                                                    }{' '}
                                                                    Perjanjian
                                                                    Kerja
                                                                </span>
                                                                {canViewMonthlyDocuments &&
                                                                    kegiatan.spk
                                                                        .detail_url && (
                                                                        <Link
                                                                            href={
                                                                                kegiatan
                                                                                    .spk
                                                                                    .detail_url
                                                                            }
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Eye className="mr-1 size-3" />
                                                                                Lihat
                                                                                Detail
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                            </>
                                                        ) : kegiatan.spk.count >
                                                          0 ? (
                                                            <>
                                                                <span className="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                                    <AlertTriangle className="size-4" />
                                                                    {
                                                                        kegiatan
                                                                            .spk
                                                                            .count
                                                                    }
                                                                    /
                                                                    {
                                                                        kegiatan
                                                                            .spk
                                                                            .required_count
                                                                    }{' '}
                                                                    Perjanjian
                                                                    Kerja
                                                                </span>
                                                                {canViewMonthlyDocuments &&
                                                                    kegiatan.spk
                                                                        .detail_url && (
                                                                        <Link
                                                                            href={
                                                                                kegiatan
                                                                                    .spk
                                                                                    .detail_url
                                                                            }
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Eye className="mr-1 size-3" />
                                                                                Lihat
                                                                                Detail
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
                                                                {canViewMonthlyDocuments &&
                                                                    kegiatan.spk
                                                                        .detail_url && (
                                                                        <Link
                                                                            href={
                                                                                kegiatan
                                                                                    .spk
                                                                                    .detail_url
                                                                            }
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Eye className="mr-1 size-3" />
                                                                                Lihat
                                                                                Detail
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                            </>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* BAST */}
                                                <div className="flex items-center justify-between text-sm">
                                                    <div className="flex items-center gap-2">
                                                        <ScrollText className="size-4 text-neutral-500" />
                                                        <span className="text-neutral-700 dark:text-neutral-300">
                                                            BAST
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {!kegiatan.bast
                                                            .requires_document ? (
                                                            <span className="flex items-center gap-1 text-neutral-500 dark:text-neutral-400">
                                                                <CheckCircle className="size-4" />
                                                                Tidak memerlukan
                                                                BAST
                                                            </span>
                                                        ) : kegiatan.bast
                                                              .is_complete ? (
                                                            <>
                                                                <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                                    <CheckCircle className="size-4" />
                                                                    {
                                                                        kegiatan
                                                                            .bast
                                                                            .count
                                                                    }{' '}
                                                                    BAST
                                                                </span>
                                                                {canViewMonthlyDocuments &&
                                                                    kegiatan
                                                                        .bast
                                                                        .detail_url && (
                                                                        <Link
                                                                            href={
                                                                                kegiatan
                                                                                    .bast
                                                                                    .detail_url
                                                                            }
                                                                        >
                                                                            <Button
                                                                                size="sm"
                                                                                variant="ghost"
                                                                            >
                                                                                <Eye className="mr-1 size-3" />
                                                                                Lihat
                                                                                Detail
                                                                            </Button>
                                                                        </Link>
                                                                    )}
                                                            </>
                                                        ) : kegiatan.bast
                                                              .count > 0 ? (
                                                            <>
                                                                <span className="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                                    <AlertTriangle className="size-4" />
                                                                    {
                                                                        kegiatan
                                                                            .bast
                                                                            .count
                                                                    }
                                                                    /
                                                                    {
                                                                        kegiatan
                                                                            .bast
                                                                            .required_count
                                                                    }{' '}
                                                                    BAST
                                                                </span>
                                                                {canViewMonthlyDocuments &&
                                                                    kegiatan
                                                                        .periode_alokasi
                                                                        ?.has_alokasi &&
                                                                    bastActionHref && (
                                                                        <Link
                                                                            href={
                                                                                bastActionHref
                                                                            }
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
                                                        ) : (
                                                            <>
                                                                <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                                    <XCircle className="size-4" />
                                                                    Belum dibuat
                                                                </span>
                                                                {canViewMonthlyDocuments &&
                                                                    kegiatan
                                                                        .periode_alokasi
                                                                        ?.has_alokasi && (
                                                                        <Link
                                                                            href={
                                                                                bastActionHref
                                                                            }
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

                                {totalKegiatanPages > 1 && (
                                    <div className="flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-800">
                                        <span className="text-neutral-500 dark:text-neutral-400">
                                            Halaman {currentKegiatanPage} dari{' '}
                                            {totalKegiatanPages}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                disabled={
                                                    currentKegiatanPage <= 1
                                                }
                                                onClick={() =>
                                                    setKegiatanPage((prev) =>
                                                        Math.max(prev - 1, 1),
                                                    )
                                                }
                                            >
                                                Sebelumnya
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                disabled={
                                                    currentKegiatanPage >=
                                                    totalKegiatanPages
                                                }
                                                onClick={() =>
                                                    setKegiatanPage((prev) =>
                                                        Math.min(
                                                            prev + 1,
                                                            totalKegiatanPages,
                                                        ),
                                                    )
                                                }
                                            >
                                                Berikutnya
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-neutral-300 bg-white p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                                <Calendar className="mx-auto size-8 text-neutral-400" />
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Tidak ada kegiatan yang sesuai filter
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
