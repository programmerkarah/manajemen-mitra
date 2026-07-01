import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Download } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Summary {
    active_users: number;
    total_logs: number;
    active_days: number;
    average_logs_per_day: number;
    administrative_actions: number;
    system_actions: number;
}

interface DailyAccessRow {
    day: number;
    date: string;
    label: string;
    total_logs: number;
    unique_users: number;
}

interface TypeSummaryRow extends Record<string, string | number> {
    type: string;
    label: string;
    total: number;
}

interface AllUserActivityRow {
    user_id: number;
    user_name: string;
    total_logs: number;
    active_days: number;
}

interface ActionRow {
    type: string;
    label: string;
    action: string;
    total: number;
}

interface TopUserRow {
    user_id: number;
    user_name: string;
    total_logs: number;
    active_days: number;
}

interface UserNameOption {
    value: string;
    label: string;
}

interface SelectedUserSummary {
    user_name: string | null;
    total_logs: number;
    active_days: number;
}

interface SelectedUserDailyAccessRow {
    day: number;
    date: string;
    label: string;
    total_logs: number;
    activity_breakdown: Array<{
        label: string;
        total: number;
    }>;
}

interface ImpactRow {
    label: string;
    count: number;
    description: string;
}

interface Props {
    active_year: number;
    generated_at: string;
    filters: {
        bulan: string;
        user_name?: string | null;
    };
    state_url: string;
    month_label: string;
    summary: Summary;
    daily_access: DailyAccessRow[];
    type_summary: TypeSummaryRow[];
    top_actions: ActionRow[];
    all_user_activity: AllUserActivityRow[];
    top_users: TopUserRow[];
    user_name_options: UserNameOption[];
    selected_user_name: string | null;
    selected_user_summary: SelectedUserSummary | null;
    selected_user_daily_access: SelectedUserDailyAccessRow[];
    impact_summary: ImpactRow[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Monitoring', href: '#' },
    { title: 'Penggunaan Aplikasi', href: '/monlap-pa' },
];

const MONTH_OPTIONS = [
    { value: '01', label: 'Januari' },
    { value: '02', label: 'Februari' },
    { value: '03', label: 'Maret' },
    { value: '04', label: 'April' },
    { value: '05', label: 'Mei' },
    { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' },
    { value: '08', label: 'Agustus' },
    { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const TYPE_COLORS: Record<string, string> = {
    auth: '#2563eb',
    system: '#7c3aed',
    user: '#059669',
    kegiatan: '#d97706',
    alokasi: '#0f766e',
    mitra: '#db2777',
    spk: '#4f46e5',
    sk_kpa: '#0ea5e9',
    bast: '#ea580c',
    pengajuan_pulsa: '#16a34a',
};

interface ChartTooltipEntry {
    name?: string;
    value?: number;
}

function ChartTooltipContent({
    active,
    payload,
    label,
}: {
    active?: boolean;
    payload?: ChartTooltipEntry[];
    label?: string;
}) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    return (
        <div className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-xl dark:border-slate-700 dark:bg-neutral-800 dark:text-slate-100">
            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">
                Akses Harian
            </p>
            {label ? (
                <p className="mt-0.5 mb-1 text-xs text-slate-500 dark:text-slate-400">
                    Tanggal: {label}
                </p>
            ) : null}
            <div className="space-y-1">
                {payload.map((entry, index) => {
                    const displayLabel = entry.name ?? 'Nilai';

                    return (
                        <p
                            key={`${displayLabel}-${index}`}
                            className="font-medium"
                        >
                            <span className="mr-2">{displayLabel}:</span>
                            <span>
                                {formatNumber(Number(entry.value ?? 0))}
                            </span>
                        </p>
                    );
                })}
            </div>
        </div>
    );
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat('id-ID').format(value);
}

function buildMonitoringUrl(filters: {
    bulan: string;
    user_name?: string | null;
}): string {
    const searchParams = new URLSearchParams();
    searchParams.set('bulan', filters.bulan);

    if (filters.user_name) {
        searchParams.set('user_name', filters.user_name);
    }

    return `/monlap-pa?${searchParams.toString()}`;
}

function toUcwords(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value
        .trim()
        .toLowerCase()
        .split(/\s+/)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatUserName(value: string | null | undefined): string {
    return toUcwords(value);
}

export default function PenggunaanAplikasi({
    active_year,
    generated_at,
    filters,
    state_url,
    month_label,
    summary,
    daily_access,
    type_summary,
    top_actions,
    all_user_activity,
    top_users,
    selected_user_name,
    selected_user_summary,
    selected_user_daily_access,
    impact_summary,
}: Props) {
    const selectedUserSummary = selected_user_summary;
    const [drilldownPage, setDrilldownPage] = useState(1);
    const drilldownPageSize = 10;

    useEffect(() => {
        if (typeof window === 'undefined' || !state_url) {
            return;
        }

        const nextUrl = new URL(state_url, window.location.origin);

        if (
            `${window.location.pathname}${window.location.search}` !==
            `${nextUrl.pathname}${nextUrl.search}`
        ) {
            window.history.replaceState(
                window.history.state,
                '',
                `${nextUrl.pathname}${nextUrl.search}`,
            );
        }
    }, [state_url]);

    const handleMonthChange = (month: string) => {
        setDrilldownPage(1);
        router.get(
            buildMonitoringUrl({
                bulan: month,
                user_name: selected_user_name,
            }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleUserChange = (userName: string) => {
        setDrilldownPage(1);
        router.get(
            buildMonitoringUrl({
                bulan: filters.bulan,
                user_name: userName || null,
            }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const drilldownTotalPages = Math.max(
        1,
        Math.ceil(selected_user_daily_access.length / drilldownPageSize),
    );

    const drilldownRows = selected_user_daily_access.slice(
        (drilldownPage - 1) * drilldownPageSize,
        drilldownPage * drilldownPageSize,
    );

    const drilldownPlaceholderRows = Array.from({
        length: Math.max(0, drilldownPageSize - drilldownRows.length),
    });

    const drilldownStart =
        selected_user_daily_access.length === 0
            ? 0
            : (drilldownPage - 1) * drilldownPageSize + 1;
    const drilldownEnd = Math.min(
        drilldownPage * drilldownPageSize,
        selected_user_daily_access.length,
    );

    const goToDrilldownPage = (page: number) => {
        setDrilldownPage(Math.min(Math.max(page, 1), drilldownTotalPages));
    };

    const exportHref = state_url.replace('/monlap-pa', '/monlap-pa/export-pdf');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penggunaan Aplikasi" />

            <div className="space-y-6">
                <PageHeader
                    title="Laporan Penggunaan Aplikasi"
                    description={`Rekap aktivitas aplikasi bulan ${month_label} ${active_year}. Hanya data di tahun aktif yang ditampilkan.`}
                >
                    <div className="flex flex-wrap items-center gap-3">
                        <Button asChild variant="outline" className="shrink-0">
                            <a href={exportHref}>
                                <Download className="size-4" />
                                Export PDF
                            </a>
                        </Button>
                        <Select
                            value={filters.bulan}
                            onValueChange={handleMonthChange}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Pilih bulan" />
                            </SelectTrigger>
                            <SelectContent>
                                {MONTH_OPTIONS.map((month) => (
                                    <SelectItem
                                        key={month.value}
                                        value={month.value}
                                    >
                                        {month.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </PageHeader>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <ContentCard className="border border-blue-200/60 bg-gradient-to-br from-blue-50 to-white dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                        <p className="text-sm font-medium text-slate-600 dark:text-slate-300">
                            Jumlah pengguna layanan
                        </p>
                        <p className="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                            {formatNumber(summary.active_users)}
                        </p>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Pengguna unik yang aktif pada bulan terpilih
                        </p>
                    </ContentCard>
                    <ContentCard className="border border-emerald-200/60 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                        <p className="text-sm font-medium text-slate-600 dark:text-slate-300">
                            Total akses
                        </p>
                        <p className="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                            {formatNumber(summary.total_logs)}
                        </p>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Seluruh aktivitas yang terekam di bulan terpilih
                        </p>
                    </ContentCard>
                    <ContentCard className="border border-amber-200/60 bg-gradient-to-br from-amber-50 to-white dark:border-amber-900/40 dark:from-amber-950/30 dark:to-neutral-900">
                        <p className="text-sm font-medium text-slate-600 dark:text-slate-300">
                            Hari aktif
                        </p>
                        <p className="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                            {formatNumber(summary.active_days)}
                        </p>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Hari yang memiliki minimal satu aktivitas
                        </p>
                    </ContentCard>
                    <ContentCard className="border border-violet-200/60 bg-gradient-to-br from-violet-50 to-white dark:border-violet-900/40 dark:from-violet-950/30 dark:to-neutral-900">
                        <p className="text-sm font-medium text-slate-600 dark:text-slate-300">
                            Rata-rata akses per hari aktif
                        </p>
                        <p className="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                            {summary.average_logs_per_day.toFixed(1)}
                        </p>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Menggambarkan intensitas penggunaan aplikasi
                        </p>
                    </ContentCard>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.9fr)]">
                    <ContentCard className="space-y-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                    Akses Harian
                                </h2>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Grafik jumlah akses dan pengguna unik setiap
                                    hari.
                                </p>
                            </div>
                        </div>
                        <div className="h-80 text-slate-600 dark:text-slate-300">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={daily_access}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="currentColor"
                                        className="text-slate-200 dark:text-slate-700"
                                    />
                                    <XAxis
                                        dataKey="label"
                                        stroke="currentColor"
                                        className="text-slate-600 dark:text-slate-300"
                                        tick={{ fontSize: 12 }}
                                        interval={Math.max(
                                            0,
                                            Math.floor(daily_access.length / 8),
                                        )}
                                    />
                                    <YAxis
                                        stroke="currentColor"
                                        className="text-slate-600 dark:text-slate-300"
                                        tick={{ fontSize: 12 }}
                                        allowDecimals={false}
                                    />
                                    <Tooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    <Legend
                                        wrapperStyle={{ color: 'inherit' }}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="total_logs"
                                        name="Akses"
                                        stroke="#2563eb"
                                        strokeWidth={3}
                                        dot={false}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="unique_users"
                                        name="Pengguna unik"
                                        stroke="#059669"
                                        strokeWidth={3}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </ContentCard>

                    <ContentCard className="space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Dampak Administratif
                            </h2>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Aktivitas yang berdampak langsung pada proses
                                kerja.
                            </p>
                        </div>
                        <div className="space-y-3">
                            {impact_summary.map((item) => (
                                <div
                                    key={item.label}
                                    className="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-neutral-900/60"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="font-medium text-slate-900 dark:text-white">
                                                {item.label}
                                            </p>
                                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                {item.description}
                                            </p>
                                        </div>
                                        <p className="text-2xl font-bold text-slate-900 dark:text-white">
                                            {formatNumber(item.count)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </ContentCard>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <ContentCard className="space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Komposisi Aktivitas
                            </h2>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Distribusi aktivitas berdasarkan kategori
                                aplikasi.
                            </p>
                        </div>
                        <div className="h-80 text-slate-600 dark:text-slate-300">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={type_summary}
                                        dataKey="total"
                                        nameKey="label"
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={110}
                                        innerRadius={70}
                                        paddingAngle={3}
                                    >
                                        {type_summary.map((entry) => (
                                            <Cell
                                                key={entry.type}
                                                fill={
                                                    TYPE_COLORS[entry.type] ??
                                                    '#64748b'
                                                }
                                            />
                                        ))}
                                    </Pie>
                                    <Tooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    <Legend
                                        wrapperStyle={{ color: 'inherit' }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </ContentCard>

                    <ContentCard className="space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Aktivitas Teratas
                            </h2>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Aksi yang paling sering dilakukan pada bulan
                                terpilih.
                            </p>
                        </div>
                        <div className="h-80 text-slate-600 dark:text-slate-300">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={top_actions.slice(0, 8)}
                                    layout="vertical"
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="currentColor"
                                        className="text-slate-200 dark:text-slate-700"
                                    />
                                    <XAxis
                                        type="number"
                                        stroke="currentColor"
                                        className="text-slate-600 dark:text-slate-300"
                                        tick={{ fontSize: 12 }}
                                        allowDecimals={false}
                                    />
                                    <YAxis
                                        type="category"
                                        dataKey="action"
                                        width={140}
                                        stroke="currentColor"
                                        className="text-slate-600 dark:text-slate-300"
                                        tick={{ fontSize: 12 }}
                                    />
                                    <Tooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    <Bar
                                        dataKey="total"
                                        fill="#2563eb"
                                        radius={[0, 10, 10, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </ContentCard>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    <ContentCard className="space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Pengguna Paling Aktif
                            </h2>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Ringkasan pengguna dengan aktivitas paling
                                tinggi pada bulan terpilih.
                            </p>
                        </div>
                        <div className="grid gap-3">
                            {top_users.map((user, index) => (
                                <div
                                    key={user.user_id}
                                    className="flex items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-neutral-900/60"
                                >
                                    <div>
                                        <p className="text-sm font-medium text-slate-900 dark:text-white">
                                            {formatUserName(user.user_name)}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Peringkat #{index + 1}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-5 text-right">
                                        <div>
                                            <p className="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                                Total akses
                                            </p>
                                            <p className="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                                                {formatNumber(user.total_logs)}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                                Hari aktif
                                            </p>
                                            <p className="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                                                {formatNumber(user.active_days)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {top_users.length === 0 ? (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-slate-500 dark:border-slate-800 dark:bg-neutral-900/40 dark:text-slate-400">
                                    Belum ada aktivitas pada bulan ini.
                                </div>
                            ) : null}
                        </div>
                    </ContentCard>

                    <ContentCard className="space-y-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                    Aktivitas Pengguna
                                </h2>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Lihat daftar pengguna dulu, lalu klik nama
                                    pengguna untuk membuka rincian per hari.
                                </p>
                            </div>
                        </div>
                        {selectedUserSummary ? (
                            <div className="space-y-4 rounded-2xl border border-emerald-200/60 bg-emerald-50/80 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 className="text-base font-semibold text-slate-900 dark:text-white">
                                            Rincian Aktivitas Pengguna
                                        </h3>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">
                                            Rincian per hari untuk{' '}
                                            {formatUserName(
                                                selectedUserSummary.user_name,
                                            )}
                                            .
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleUserChange('')}
                                    >
                                        Kembali
                                    </Button>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="rounded-2xl border border-emerald-200/70 bg-white p-4 dark:border-emerald-900/40 dark:bg-neutral-900/60">
                                        <p className="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                            Nama
                                        </p>
                                        <p className="mt-2 text-lg font-semibold text-slate-900 dark:text-white">
                                            {formatUserName(
                                                selectedUserSummary.user_name,
                                            )}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-emerald-200/70 bg-white p-4 dark:border-emerald-900/40 dark:bg-neutral-900/60">
                                        <p className="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                            Jumlah akses
                                        </p>
                                        <p className="mt-2 text-lg font-semibold text-slate-900 dark:text-white">
                                            {formatNumber(
                                                selectedUserSummary.total_logs,
                                            )}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-emerald-200/70 bg-white p-4 dark:border-emerald-900/40 dark:bg-neutral-900/60">
                                        <p className="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                            Hari yang aktif
                                        </p>
                                        <p className="mt-2 text-lg font-semibold text-slate-900 dark:text-white">
                                            {formatNumber(
                                                selectedUserSummary.active_days,
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div className="overflow-hidden rounded-2xl border border-emerald-200/70 dark:border-emerald-900/40">
                                    <table className="min-w-full table-fixed divide-y divide-emerald-200 text-sm dark:divide-emerald-900/40">
                                        <thead className="bg-emerald-50 dark:bg-emerald-950/40">
                                            <tr>
                                                <th className="w-[140px] px-4 py-3 text-left font-semibold text-emerald-700 dark:text-emerald-200">
                                                    Hari
                                                </th>
                                                <th className="w-[120px] px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-200">
                                                    Jumlah akses
                                                </th>
                                                <th className="px-4 py-3 text-left font-semibold text-emerald-700 dark:text-emerald-200">
                                                    Rincian aktivitas
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-emerald-100 bg-white dark:divide-emerald-900/30 dark:bg-neutral-900/60">
                                            {drilldownRows.map((row) => (
                                                <tr
                                                    key={row.date}
                                                    className="h-16 align-top"
                                                >
                                                    <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                                        {row.label}
                                                    </td>
                                                    <td className="px-4 py-3 text-right text-slate-700 dark:text-slate-300">
                                                        {formatNumber(
                                                            row.total_logs,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-slate-700 dark:text-slate-300">
                                                        {row.activity_breakdown
                                                            .length > 0 ? (
                                                            <div className="flex flex-wrap gap-2">
                                                                {row.activity_breakdown.map(
                                                                    (
                                                                        activity,
                                                                    ) => (
                                                                        <span
                                                                            key={`${row.date}-${activity.label}`}
                                                                            className="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200"
                                                                        >
                                                                            {
                                                                                activity.label
                                                                            }
                                                                            :{' '}
                                                                            {formatNumber(
                                                                                activity.total,
                                                                            )}
                                                                        </span>
                                                                    ),
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-slate-400 dark:text-slate-500">
                                                                Tidak ada
                                                                aktivitas
                                                            </span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                            {drilldownRows.length === 0 ? (
                                                <tr>
                                                    <td
                                                        colSpan={3}
                                                        className="px-4 py-8 text-center text-slate-500 dark:text-slate-400"
                                                    >
                                                        Belum ada rincian
                                                        aktivitas untuk pengguna
                                                        ini.
                                                    </td>
                                                </tr>
                                            ) : null}
                                            {drilldownPlaceholderRows.map(
                                                (_, index) => (
                                                    <tr
                                                        key={`drilldown-placeholder-${index}`}
                                                        className="h-16"
                                                    >
                                                        <td className="px-4 py-3 text-slate-900 dark:text-white">
                                                            &nbsp;
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-slate-700 dark:text-slate-300">
                                                            &nbsp;
                                                        </td>
                                                        <td className="px-4 py-3 text-slate-700 dark:text-slate-300">
                                                            &nbsp;
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200/70 bg-white px-4 py-3 text-sm dark:border-emerald-900/40 dark:bg-neutral-900/60 dark:text-slate-300">
                                    <p>
                                        Menampilkan {drilldownStart}-
                                        {drilldownEnd} dari{' '}
                                        {selected_user_daily_access.length}{' '}
                                        hari.
                                    </p>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                goToDrilldownPage(
                                                    drilldownPage - 1,
                                                )
                                            }
                                            disabled={drilldownPage <= 1}
                                        >
                                            <ChevronLeft className="size-4" />
                                            Sebelumnya
                                        </Button>
                                        <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200">
                                            Halaman {drilldownPage} dari{' '}
                                            {drilldownTotalPages}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                goToDrilldownPage(
                                                    drilldownPage + 1,
                                                )
                                            }
                                            disabled={
                                                drilldownPage >=
                                                drilldownTotalPages
                                            }
                                        >
                                            Berikutnya
                                            <ChevronRight className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-800">
                                    <table className="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                        <thead className="bg-slate-50 dark:bg-neutral-900">
                                            <tr>
                                                <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">
                                                    Pengguna
                                                </th>
                                                <th className="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">
                                                    Total Akses
                                                </th>
                                                <th className="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">
                                                    Hari Aktif
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-neutral-900/60">
                                            {all_user_activity.map((user) => {
                                                return (
                                                    <tr key={user.user_id}>
                                                        <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    handleUserChange(
                                                                        user.user_name,
                                                                    )
                                                                }
                                                                className="text-left font-medium text-blue-700 transition-colors hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200"
                                                            >
                                                                {formatUserName(
                                                                    user.user_name,
                                                                )}
                                                            </button>
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-slate-700 dark:text-slate-300">
                                                            {formatNumber(
                                                                user.total_logs,
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 text-right text-slate-700 dark:text-slate-300">
                                                            {formatNumber(
                                                                user.active_days,
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                            {all_user_activity.length === 0 ? (
                                                <tr>
                                                    <td
                                                        colSpan={3}
                                                        className="px-4 py-8 text-center text-slate-500 dark:text-slate-400"
                                                    >
                                                        Belum ada aktivitas pada
                                                        bulan ini.
                                                    </td>
                                                </tr>
                                            ) : null}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-slate-500 dark:border-slate-800 dark:bg-neutral-900/40 dark:text-slate-400">
                                    Pilih nama pengguna pada tabel di atas untuk
                                    melihat rincian harian.
                                </div>
                            </>
                        )}
                    </ContentCard>
                </div>

                <ContentCard className="space-y-5">
                    <div>
                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                            Informasi Laporan
                        </h2>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Laporan ini dibangkitkan berdasarkan tahun aktif
                            sistem.
                        </p>
                    </div>
                    <div className="space-y-3 rounded-2xl border border-slate-200/70 bg-slate-50 p-4 dark:border-slate-800 dark:bg-neutral-900/60">
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Tahun aktif
                            </span>
                            <span className="font-medium text-slate-900 dark:text-white">
                                {active_year}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Bulan laporan
                            </span>
                            <span className="font-medium text-slate-900 dark:text-white">
                                {month_label}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Diolah pada
                            </span>
                            <span className="font-medium text-slate-900 dark:text-white">
                                {generated_at}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Akses administratif
                            </span>
                            <span className="font-medium text-slate-900 dark:text-white">
                                {formatNumber(summary.administrative_actions)}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Akses sistem
                            </span>
                            <span className="font-medium text-slate-900 dark:text-white">
                                {formatNumber(summary.system_actions)}
                            </span>
                        </div>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
