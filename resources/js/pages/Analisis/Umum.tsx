import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    Activity,
    Banknote,
    BarChart2,
    Download,
    TrendingDown,
    TrendingUp,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import {
    CartesianGrid,
    Cell,
    Tooltip as ChartTooltip,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    PieSectorShapeProps,
    ResponsiveContainer,
    Sector,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analisis Umum', href: '/analisis/umum' },
];

const monthNames = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
];

const COLORS = [
    '#3b82f6',
    '#22c55e',
    '#f59e0b',
    '#ef4444',
    '#8b5cf6',
    '#ec4899',
    '#14b8a6',
    '#f97316',
];

interface UtilisasiAnggaran {
    kegiatan_id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
    jenis_kegiatan: string;
    total_pagu: number;
    total_terpakai: number;
    persentase: number;
}

interface DistribusiBebanKerja {
    [key: string]: unknown;
    label: string;
    count: number;
}

interface TrenAlokasi {
    bulan: number;
    jumlah_petugas: number;
    total_honor: number;
    total_kegiatan: number;
}

interface RingkasanKPI {
    total_pagu: number;
    total_terpakai: number;
    serapan_persen: number;
    total_petugas_aktif: number;
    total_kegiatan_aktif: number;
}

interface RingkasanJenisKegiatan {
    jenis: string;
    label: string;
    jumlah_kegiatan: number;
    total_pagu: number;
    total_terpakai: number;
    serapan_persen: number;
}

interface TopPetugas {
    petugas_id: number;
    nama: string;
    jabatan: string | null;
    jumlah_kegiatan: number;
    total_honor: number;
}

interface Props {
    utilisasiAnggaran: UtilisasiAnggaran[];
    distribusiBebanKerja: DistribusiBebanKerja[];
    trenAlokasi: TrenAlokasi[];
    ringkasanKPI: RingkasanKPI;
    ringkasanJenisKegiatan: RingkasanJenisKegiatan[];
    topPetugas: TopPetugas[];
    currentYear: number;
    currentMonth: number;
}

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function formatRupiahCompact(value: number): string {
    if (value >= 1_000_000_000) {
        return `${(value / 1_000_000_000).toFixed(1)}M`;
    }
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(1)}jt`;
    }
    return `${(value / 1_000).toFixed(0)}rb`;
}

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

interface PieLegendItem {
    label: string;
    value: number;
    percentage: number;
    color: string;
}

function buildPieLegendItems<T extends { count: number }>(
    items: T[],
    getLabel: (item: T) => string,
    total: number,
): PieLegendItem[] {
    return items.map((item, index) => ({
        label: getLabel(item),
        value: item.count,
        percentage: total > 0 ? (item.count / total) * 100 : 0,
        color: COLORS[index % COLORS.length],
    }));
}

function PieLegendList({ items }: { items: PieLegendItem[] }) {
    return (
        <div className="mt-4 max-h-40 space-y-2 overflow-y-auto pr-1">
            {items.map((item) => (
                <div
                    key={item.label}
                    className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200/70 bg-white/70 px-3 py-2 text-xs dark:border-neutral-700/60 dark:bg-neutral-900/40"
                >
                    <div className="flex items-center gap-2 text-neutral-700 dark:text-neutral-200">
                        <span
                            className="inline-block h-2.5 w-2.5 rounded-full"
                            style={{ backgroundColor: item.color }}
                        />
                        <span className="font-medium">{item.label}</span>
                    </div>
                    <div className="text-right text-neutral-600 dark:text-neutral-300">
                        <div className="font-semibold">{item.value}</div>
                        <div>{item.percentage.toFixed(1)}%</div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function SerapanColor(persen: number): string {
    if (persen >= 90) return 'text-red-600 dark:text-red-400';
    if (persen >= 70) return 'text-amber-600 dark:text-amber-400';
    return 'text-green-600 dark:text-green-400';
}

function SerapanBarColor(persen: number): string {
    if (persen >= 90) return '#ef4444';
    if (persen >= 70) return '#f59e0b';
    return '#22c55e';
}

const RADIAN = Math.PI / 180;

function renderActivePieShape(
    props: PieSectorShapeProps,
    clickedIndex?: number,
) {
    const { cx, cy, innerRadius, outerRadius, startAngle, endAngle, fill } =
        props;
    if (props.index !== clickedIndex) {
        return (
            <Sector
                cx={cx}
                cy={cy}
                innerRadius={innerRadius}
                outerRadius={outerRadius}
                startAngle={startAngle}
                endAngle={endAngle}
                fill={fill}
                stroke="none"
            />
        );
    }
    const { name, value, percent } = props;
    const midAngle = (startAngle + endAngle) / 2;
    const sin = Math.sin(-RADIAN * midAngle);
    const cos = Math.cos(-RADIAN * midAngle);
    const expandedOuter = outerRadius + 8;
    const sx = cx + (expandedOuter + 2) * cos;
    const sy = cy + (expandedOuter + 2) * sin;
    const mx = cx + (expandedOuter + 20) * cos;
    const my = cy + (expandedOuter + 20) * sin;
    const ex = mx + (cos >= 0 ? 1 : -1) * 18;
    const ey = my;
    const anchor = cos >= 0 ? 'start' : 'end';
    return (
        <g>
            <Sector
                cx={cx}
                cy={cy}
                innerRadius={innerRadius}
                outerRadius={expandedOuter}
                startAngle={startAngle}
                endAngle={endAngle}
                fill={fill}
                stroke="none"
            />
            <path
                d={`M${sx},${sy}L${mx},${my}L${ex},${ey}`}
                stroke={fill}
                fill="none"
                strokeWidth={1.5}
            />
            <circle cx={ex} cy={ey} r={3} fill={fill} />
            <text
                x={ex + (cos >= 0 ? 6 : -6)}
                y={ey - 3}
                textAnchor={anchor}
                fill={fill}
                fontSize={11}
                fontWeight={600}
            >
                {name}
            </text>
            <text
                x={ex + (cos >= 0 ? 6 : -6)}
                y={ey + 11}
                textAnchor={anchor}
                fill="#9ca3af"
                fontSize={10}
            >
                {value} ({((percent ?? 0) * 100).toFixed(1)}%)
            </text>
        </g>
    );
}

export default function AnalisisUmum({
    utilisasiAnggaran,
    distribusiBebanKerja,
    trenAlokasi,
    ringkasanKPI,
    ringkasanJenisKegiatan,
    topPetugas,
    currentYear,
    currentMonth,
}: Props) {
    const [activePieIndex, setActivePieIndex] = useState<number | undefined>(
        undefined,
    );

    const trenChartData = trenAlokasi.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
        total_honor_jt: Math.round((item.total_honor / 1_000_000) * 10) / 10,
    }));

    const filteredUtilisasi = utilisasiAnggaran.filter((u) => u.total_pagu > 0);
    const distribusiBebanKerjaChartData = distribusiBebanKerja.filter(
        (item) => item.count > 0,
    );
    const totalDistribusiBebanKerja = distribusiBebanKerjaChartData.reduce(
        (sum, item) => sum + item.count,
        0,
    );
    const distribusiBebanKerjaLegendItems = buildPieLegendItems(
        distribusiBebanKerjaChartData,
        (item) => item.label,
        totalDistribusiBebanKerja,
    );

    const maxTopHonor = topPetugas.length > 0 ? topPetugas[0].total_honor : 1;

    const kpiCards = [
        {
            label: 'Total Pagu',
            value: formatRupiahCompact(ringkasanKPI.total_pagu),
            sub: formatRupiah(ringkasanKPI.total_pagu),
            icon: Banknote,
            color: 'text-blue-600 dark:text-blue-400',
            bg: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            label: 'Total Terpakai',
            value: formatRupiahCompact(ringkasanKPI.total_terpakai),
            sub: formatRupiah(ringkasanKPI.total_terpakai),
            icon: TrendingUp,
            color: 'text-green-600 dark:text-green-400',
            bg: 'bg-green-50 dark:bg-green-900/20',
        },
        {
            label: 'Penyerapan Anggaran',
            value: `${ringkasanKPI.serapan_persen}%`,
            sub:
                ringkasanKPI.serapan_persen >= 90
                    ? 'Mendekati batas'
                    : ringkasanKPI.serapan_persen >= 70
                      ? 'Sedang'
                      : 'Masih rendah',
            icon: ringkasanKPI.serapan_persen >= 70 ? TrendingUp : TrendingDown,
            color:
                ringkasanKPI.serapan_persen >= 90
                    ? 'text-red-600 dark:text-red-400'
                    : ringkasanKPI.serapan_persen >= 70
                      ? 'text-amber-600 dark:text-amber-400'
                      : 'text-green-600 dark:text-green-400',
            bg:
                ringkasanKPI.serapan_persen >= 90
                    ? 'bg-red-50 dark:bg-red-900/20'
                    : ringkasanKPI.serapan_persen >= 70
                      ? 'bg-amber-50 dark:bg-amber-900/20'
                      : 'bg-green-50 dark:bg-green-900/20',
        },
        {
            label: 'Petugas Aktif',
            value: ringkasanKPI.total_petugas_aktif.toLocaleString('id-ID'),
            sub: 'Non-organik teralokasi',
            icon: Users,
            color: 'text-purple-600 dark:text-purple-400',
            bg: 'bg-purple-50 dark:bg-purple-900/20',
        },
        {
            label: 'Kegiatan Aktif',
            value: ringkasanKPI.total_kegiatan_aktif.toLocaleString('id-ID'),
            sub: `Tahun ${currentYear}`,
            icon: Activity,
            color: 'text-orange-600 dark:text-orange-400',
            bg: 'bg-orange-50 dark:bg-orange-900/20',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Umum" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-start justify-between rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                            Analisis Umum
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Ringkasan anggaran, beban kerja, dan tren alokasi ·
                            Tahun {currentYear}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            window.open(
                                '/analisis/umum/export-pdf',
                                '_blank',
                                'noopener,noreferrer',
                            )
                        }
                        className="inline-flex shrink-0 items-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                    >
                        <Download className="h-4 w-4" />
                        Export PDF
                    </button>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    {kpiCards.map((card) => (
                        <div
                            key={card.label}
                            className="rounded-2xl border border-neutral-200/70 bg-white/80 p-4 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80"
                        >
                            <div
                                className={`mb-3 inline-flex rounded-lg p-2 ${card.bg}`}
                            >
                                <card.icon
                                    className={`h-4 w-4 ${card.color}`}
                                />
                            </div>
                            <div className={`text-2xl font-bold ${card.color}`}>
                                {card.value}
                            </div>
                            <div className="mt-0.5 text-xs font-medium text-neutral-700 dark:text-neutral-300">
                                {card.label}
                            </div>
                            <div className="mt-0.5 truncate text-xs text-neutral-400 dark:text-neutral-500">
                                {card.sub}
                            </div>
                        </div>
                    ))}
                </div>

                {/* Tren Alokasi Bulanan - full width */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Tren Alokasi Bulanan
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Total honor terbayar, jumlah kegiatan aktif, dan petugas
                        teralokasi per bulan
                    </p>
                    <ResponsiveContainer width="100%" height={300}>
                        <LineChart data={trenChartData}>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="rgba(156,163,175,0.2)"
                            />
                            <XAxis
                                dataKey="name"
                                fontSize={12}
                                tick={{
                                    fill: 'currentColor',
                                    className: 'text-neutral-500',
                                }}
                            />
                            <YAxis
                                yAxisId="honor"
                                orientation="left"
                                fontSize={11}
                                tickFormatter={(v) =>
                                    formatRupiahCompact(v as number)
                                }
                                tick={{ fill: '#22c55e' }}
                                width={60}
                            />
                            <YAxis
                                yAxisId="count"
                                orientation="right"
                                fontSize={11}
                                allowDecimals={false}
                                tick={{ fill: '#3b82f6' }}
                                width={36}
                            />
                            <ChartTooltip
                                content={({ active, payload, label }) => {
                                    if (!active || !payload?.length)
                                        return null;
                                    return (
                                        <div className={glassTooltipClass}>
                                            <p className="mb-2 text-xs font-semibold text-neutral-900 dark:text-white">
                                                {label}
                                            </p>
                                            {payload.map((entry, i) => (
                                                <p
                                                    key={i}
                                                    className="text-xs text-neutral-600 dark:text-neutral-400"
                                                >
                                                    <span
                                                        style={{
                                                            color: entry.color,
                                                        }}
                                                    >
                                                        ●
                                                    </span>{' '}
                                                    {entry.name}:{' '}
                                                    {entry.dataKey ===
                                                    'total_honor'
                                                        ? formatRupiah(
                                                              entry.value as number,
                                                          )
                                                        : entry.value}
                                                </p>
                                            ))}
                                        </div>
                                    );
                                }}
                            />
                            <Legend
                                formatter={(value) => (
                                    <span className="text-xs text-neutral-600 dark:text-neutral-300">
                                        {value}
                                    </span>
                                )}
                            />
                            <Line
                                type="monotone"
                                dataKey="total_honor"
                                stroke="#22c55e"
                                name="Total Honor"
                                strokeWidth={2}
                                dot={{ r: 3 }}
                                activeDot={{ r: 5 }}
                                yAxisId="honor"
                            />
                            <Line
                                type="monotone"
                                dataKey="total_kegiatan"
                                stroke="#3b82f6"
                                name="Jumlah Kegiatan"
                                strokeWidth={2}
                                dot={{ r: 3 }}
                                activeDot={{ r: 5 }}
                                yAxisId="count"
                            />
                            <Line
                                type="monotone"
                                dataKey="jumlah_petugas"
                                stroke="#f59e0b"
                                name="Jumlah Petugas"
                                strokeWidth={2}
                                strokeDasharray="5 3"
                                dot={{ r: 3 }}
                                activeDot={{ r: 5 }}
                                yAxisId="count"
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>

                {/* Charts Row: Beban Kerja + Jenis Kegiatan */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Distribusi Beban Kerja */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Beban Kerja Petugas
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Sebaran petugas non-organik berdasarkan jumlah
                            kegiatan yang ditangani
                        </p>
                        <ResponsiveContainer width="100%" height={280}>
                            <PieChart
                                style={{ overflow: 'visible' }}
                                margin={{
                                    top: 10,
                                    right: 70,
                                    bottom: 10,
                                    left: 70,
                                }}
                                onClick={() => setActivePieIndex(undefined)}
                            >
                                <Pie
                                    data={distribusiBebanKerjaChartData}
                                    dataKey="count"
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={54}
                                    outerRadius={86}
                                    paddingAngle={2}
                                    labelLine={false}
                                    shape={(p: PieSectorShapeProps) =>
                                        renderActivePieShape(p, activePieIndex)
                                    }
                                    onClick={(_, idx, e) => {
                                        e.stopPropagation();
                                        setActivePieIndex(
                                            idx === activePieIndex
                                                ? undefined
                                                : idx,
                                        );
                                    }}
                                    cursor="pointer"
                                    stroke="none"
                                >
                                    {distribusiBebanKerjaChartData.map(
                                        (_, index) => (
                                            <Cell
                                                key={`beban-kerja-cell-${index}`}
                                                fill={
                                                    COLORS[
                                                        index % COLORS.length
                                                    ]
                                                }
                                            />
                                        ),
                                    )}
                                </Pie>
                            </PieChart>
                        </ResponsiveContainer>
                        <PieLegendList
                            items={distribusiBebanKerjaLegendItems}
                        />
                    </div>

                    {/* Ringkasan per Jenis Kegiatan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Penyerapan Anggaran per Jenis Kegiatan
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Perbandingan pagu dan penyerapan anggaran
                            berdasarkan jenis kegiatan
                        </p>
                        {ringkasanJenisKegiatan.length > 0 ? (
                            <div className="space-y-4">
                                {ringkasanJenisKegiatan.map((jenis) => (
                                    <div key={jenis.jenis}>
                                        <div className="mb-1 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <BarChart2 className="h-3.5 w-3.5 text-neutral-400" />
                                                <span className="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                                                    {jenis.label}
                                                </span>
                                                <span className="rounded-full bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400">
                                                    {jenis.jumlah_kegiatan} keg
                                                </span>
                                            </div>
                                            <span
                                                className={`text-sm font-bold ${SerapanColor(jenis.serapan_persen)}`}
                                            >
                                                {jenis.serapan_persen}%
                                            </span>
                                        </div>
                                        <div className="mb-1 h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                            <div
                                                className="h-full rounded-full transition-all"
                                                style={{
                                                    width: `${Math.min(jenis.serapan_persen, 100)}%`,
                                                    backgroundColor:
                                                        SerapanBarColor(
                                                            jenis.serapan_persen,
                                                        ),
                                                }}
                                            />
                                        </div>
                                        <div className="flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                            <span>
                                                Terpakai:{' '}
                                                {formatRupiahCompact(
                                                    jenis.total_terpakai,
                                                )}
                                            </span>
                                            <span>
                                                Pagu:{' '}
                                                {formatRupiahCompact(
                                                    jenis.total_pagu,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Belum ada data kegiatan
                            </p>
                        )}
                    </div>
                </div>

                {/* Top 10 Petugas */}
                {topPetugas.length > 0 && (
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            10 Petugas dengan Honor Tertinggi
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Petugas non-organik dengan total honor terbesar s.d.{' '}
                            {new Date(
                                currentYear,
                                currentMonth - 1,
                            ).toLocaleDateString('id-ID', {
                                month: 'long',
                                year: 'numeric',
                            })}{' '}
                            (dengan bobot alokasi SE2026)
                        </p>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="w-8 py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            #
                                        </th>
                                        <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Nama
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Kegiatan
                                        </th>
                                        <th className="min-w-[200px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Honor
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topPetugas.map((petugas, index) => {
                                        const barPct =
                                            maxTopHonor > 0
                                                ? (petugas.total_honor /
                                                      maxTopHonor) *
                                                  100
                                                : 0;
                                        return (
                                            <tr
                                                key={petugas.petugas_id}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-2 text-center">
                                                    <span
                                                        className={`inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                                            index === 0
                                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400'
                                                                : index === 1
                                                                  ? 'bg-neutral-200 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300'
                                                                  : index === 2
                                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                                                    : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'
                                                        }`}
                                                    >
                                                        {index + 1}
                                                    </span>
                                                </td>
                                                <td className="py-2">
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {petugas.nama}
                                                    </div>
                                                    {petugas.jabatan && (
                                                        <div className="text-xs text-neutral-400 dark:text-neutral-500">
                                                            {petugas.jabatan}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-2 text-center text-neutral-600 dark:text-neutral-400">
                                                    {petugas.jumlah_kegiatan}
                                                </td>
                                                <td className="py-2">
                                                    <div className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                                                        {formatRupiah(
                                                            petugas.total_honor,
                                                        )}
                                                    </div>
                                                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                        <div
                                                            className="h-full rounded-full bg-blue-500 transition-all"
                                                            style={{
                                                                width: `${barPct}%`,
                                                            }}
                                                        />
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Utilisasi Anggaran per Kegiatan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Penyerapan Anggaran per Kegiatan
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Perbandingan pagu dan honor yang sudah dibayarkan per
                        kegiatan
                    </p>
                    {filteredUtilisasi.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-white/80 dark:bg-neutral-800/80">
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="min-w-[200px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Kegiatan
                                            </th>
                                            <th className="min-w-[60px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Jenis
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Pagu
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Terpakai
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Sisa
                                            </th>
                                            <th className="min-w-[120px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                % Penyerapan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredUtilisasi.map((item) => (
                                            <tr
                                                key={item.kegiatan_id}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-2">
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {item.nama_kegiatan}
                                                    </div>
                                                </td>
                                                <td className="py-2">
                                                    <span className="rounded-md bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-600 capitalize dark:bg-neutral-700 dark:text-neutral-300">
                                                        {item.jenis_kegiatan}
                                                    </span>
                                                </td>
                                                <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_pagu,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_terpakai,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_pagu -
                                                            item.total_terpakai,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right">
                                                    <div
                                                        className={`text-xs font-bold ${SerapanColor(item.persentase)}`}
                                                    >
                                                        {item.persentase}%
                                                    </div>
                                                    <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                        <div
                                                            className="h-full rounded-full transition-all"
                                                            style={{
                                                                width: `${Math.min(item.persentase, 100)}%`,
                                                                backgroundColor:
                                                                    SerapanBarColor(
                                                                        item.persentase,
                                                                    ),
                                                            }}
                                                        />
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Belum ada data kegiatan
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
