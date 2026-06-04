import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertTriangle, Download, Users } from 'lucide-react';
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
    { title: 'Analisis Petugas Organik', href: '/analisis/petugas-organik' },
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

const PIE_COLORS = [
    '#2563eb',
    '#16a34a',
    '#d97706',
    '#dc2626',
    '#7c3aed',
    '#0891b2',
];

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

interface Ringkasan {
    total_petugas_aktif: number;
    total_petugas_teralokasi: number;
    total_alokasi: number;
}

interface DistribusiBebanKerja {
    label: string;
    count: number;
}

interface TrenBebanKerja {
    bulan: number;
    jumlah_petugas: number;
    jumlah_kegiatan: number;
    jumlah_alokasi: number;
}

interface BebanKerjaDetail {
    petugas_id: number;
    petugas_nama: string;
    jabatan: string | null;
    jumlah_alokasi: number;
    jumlah_kegiatan: number;
    rata_rata_kegiatan_per_bulan: number;
    performance_status: 'overload' | 'optimal' | 'normal' | 'under_performance';
    performance_label: string;
}

interface Props {
    ringkasan: Ringkasan;
    distribusiBebanKerja: DistribusiBebanKerja[];
    trenBebanKerja: TrenBebanKerja[];
    bebanKerjaDetail: BebanKerjaDetail[];
    currentMonth: number;
    currentYear: number;
}

type PerformanceKey = 'overload' | 'optimal' | 'normal' | 'under_performance';

const performanceConfig: Record<
    PerformanceKey,
    { label: string; badge: string; dot: string }
> = {
    overload: {
        label: 'Overload',
        badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
        dot: 'bg-red-500',
    },
    optimal: {
        label: 'Optimal',
        badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        dot: 'bg-green-500',
    },
    normal: {
        label: 'Normal',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        dot: 'bg-blue-500',
    },
    under_performance: {
        label: 'Under Performance',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
};

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
                {value} ({(percent * 100).toFixed(1)}%)
            </text>
        </g>
    );
}

export default function AnalisisPetugasOrganik({
    ringkasan,
    distribusiBebanKerja,
    trenBebanKerja,
    bebanKerjaDetail,
    currentMonth,
    currentYear,
}: Props) {
    const pctTeralokasi =
        ringkasan.total_petugas_aktif > 0
            ? Math.round(
                  (ringkasan.total_petugas_teralokasi /
                      ringkasan.total_petugas_aktif) *
                      100,
              )
            : 0;

    const countByStatus: Record<PerformanceKey, number> = {
        overload: bebanKerjaDetail.filter(
            (d) => d.performance_status === 'overload',
        ).length,
        optimal: bebanKerjaDetail.filter(
            (d) => d.performance_status === 'optimal',
        ).length,
        normal: bebanKerjaDetail.filter(
            (d) => d.performance_status === 'normal',
        ).length,
        under_performance: bebanKerjaDetail.filter(
            (d) => d.performance_status === 'under_performance',
        ).length,
    };

    const overloadedPetugas = bebanKerjaDetail.filter(
        (d) => d.performance_status === 'overload',
    );

    const pieData = distribusiBebanKerja.filter((d) => d.count > 0);
    const trenData = trenBebanKerja.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    const PAGE_SIZE = 10;
    const [currentPage, setCurrentPage] = useState(1);
    const [activePieIndex, setActivePieIndex] = useState<number | undefined>(
        undefined,
    );
    const totalPages = Math.ceil(bebanKerjaDetail.length / PAGE_SIZE);
    const pagedItems = bebanKerjaDetail.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );

    const pctBarColor =
        pctTeralokasi >= 80
            ? '#22c55e'
            : pctTeralokasi >= 50
              ? '#f59e0b'
              : '#ef4444';
    const pctTextColor =
        pctTeralokasi >= 80
            ? 'text-green-600 dark:text-green-400'
            : pctTeralokasi >= 50
              ? 'text-amber-600 dark:text-amber-400'
              : 'text-red-600 dark:text-red-400';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Petugas Organik" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-start justify-between rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                            Analisis Petugas Organik
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Distribusi beban kerja pegawai organik &middot;
                            Tahun {currentYear} (Januari &ndash;{' '}
                            {monthNames[currentMonth - 1]})
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            window.open(
                                '/analisis/petugas-organik/export-pdf',
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
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            {
                                label: 'Total Pegawai Aktif',
                                value: ringkasan.total_petugas_aktif,
                                sub: 'Pegawai organik aktif',
                                subColor:
                                    'text-neutral-500 dark:text-neutral-400',
                                barPct: 100,
                                barColor: '#3b82f6',
                                icon: (
                                    <Users className="h-5 w-5 text-blue-500" />
                                ),
                            },
                            {
                                label: 'Pegawai Teralokasi',
                                value: ringkasan.total_petugas_teralokasi,
                                sub: `${pctTeralokasi}% dari total pegawai`,
                                subColor: pctTextColor,
                                barPct: pctTeralokasi,
                                barColor: pctBarColor,
                                icon: (
                                    <Users className="h-5 w-5 text-green-500" />
                                ),
                            },
                            {
                                label: 'Tidak Teralokasi',
                                value:
                                    ringkasan.total_petugas_aktif -
                                    ringkasan.total_petugas_teralokasi,
                                sub: 'Belum ada alokasi kegiatan',
                                subColor:
                                    'text-neutral-500 dark:text-neutral-400',
                                barPct: 100 - pctTeralokasi,
                                barColor: '#94a3b8',
                                icon: (
                                    <Users className="h-5 w-5 text-neutral-400" />
                                ),
                            },
                            {
                                label: 'Total Alokasi',
                                value: ringkasan.total_alokasi,
                                sub: 'Slot alokasi kegiatan',
                                subColor:
                                    'text-neutral-500 dark:text-neutral-400',
                                barPct: 100,
                                barColor: '#8b5cf6',
                                icon: (
                                    <Users className="h-5 w-5 text-purple-500" />
                                ),
                            },
                        ] as const
                    ).map((card) => (
                        <div
                            key={card.label}
                            className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50"
                        >
                            <div className="mb-3 flex items-center justify-between">
                                <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    {card.label}
                                </p>
                                {card.icon}
                            </div>
                            <p className="text-2xl font-bold text-neutral-900 dark:text-white">
                                {card.value}
                            </p>
                            <p className={`mt-0.5 text-xs ${card.subColor}`}>
                                {card.sub}
                            </p>
                            <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div
                                    className="h-full rounded-full transition-all duration-500"
                                    style={{
                                        width: `${card.barPct}%`,
                                        backgroundColor: card.barColor,
                                    }}
                                />
                            </div>
                        </div>
                    ))}
                </div>

                {/* Performance Distribution */}
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        Object.entries(performanceConfig) as [
                            PerformanceKey,
                            (typeof performanceConfig)[PerformanceKey],
                        ][]
                    ).map(([key, cfg]) => (
                        <div
                            key={key}
                            className="flex items-center justify-between rounded-xl border border-white/20 bg-white/40 px-4 py-3 shadow-lg backdrop-blur-xl dark:border-neutral-700/30 dark:bg-neutral-800/50"
                        >
                            <div className="flex items-center gap-2">
                                <span
                                    className={`h-2 w-2 rounded-full ${cfg.dot}`}
                                />
                                <span className="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                                    {cfg.label}
                                </span>
                            </div>
                            <span
                                className={`rounded-full px-2.5 py-0.5 text-sm font-bold ${cfg.badge}`}
                            >
                                {countByStatus[key]}
                            </span>
                        </div>
                    ))}
                </div>

                {/* Alert: Overloaded */}
                {overloadedPetugas.length > 0 && (
                    <div className="rounded-2xl border border-red-200 bg-red-50/80 p-5 shadow-lg backdrop-blur-xl dark:border-red-800/40 dark:bg-red-900/20">
                        <div className="mb-3 flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-red-600 dark:text-red-400" />
                            <h3 className="text-sm font-semibold text-red-800 dark:text-red-300">
                                {overloadedPetugas.length} pegawai dengan beban
                                kerja Overload (&gt; 3 kegiatan/bulan)
                            </h3>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {overloadedPetugas.map((p) => (
                                <span
                                    key={p.petugas_id}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-800 dark:bg-red-900/50 dark:text-red-300"
                                >
                                    <span className="font-semibold">
                                        {p.petugas_nama}
                                    </span>
                                    <span className="text-red-400">
                                        &middot;
                                    </span>
                                    <span>{p.jumlah_kegiatan} kegiatan</span>
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Charts */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Beban Kerja
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Jumlah kegiatan unik yang dialokasikan per pegawai
                        </p>
                        {pieData.length > 0 ? (
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
                                        data={pieData}
                                        dataKey="count"
                                        nameKey="label"
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={52}
                                        outerRadius={88}
                                        paddingAngle={2}
                                        labelLine={false}
                                        shape={(p: PieSectorShapeProps) =>
                                            renderActivePieShape(
                                                p,
                                                activePieIndex,
                                            )
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
                                        {pieData.map((_, i) => (
                                            <Cell
                                                key={i}
                                                fill={
                                                    PIE_COLORS[
                                                        i % PIE_COLORS.length
                                                    ]
                                                }
                                            />
                                        ))}
                                    </Pie>
                                    <Legend
                                        wrapperStyle={{ fontSize: '11px' }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="py-16 text-center text-sm text-neutral-400">
                                Belum ada data distribusi.
                            </p>
                        )}
                    </div>

                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Tren Beban Kerja Bulanan
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Petugas teralokasi, kegiatan, dan total alokasi per
                            bulan
                        </p>
                        <ResponsiveContainer width="100%" height={240}>
                            <LineChart
                                data={trenData}
                                margin={{
                                    top: 0,
                                    right: 0,
                                    left: -20,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    stroke="rgba(128,128,128,0.15)"
                                />
                                <XAxis
                                    dataKey="name"
                                    fontSize={11}
                                    tickLine={false}
                                />
                                <YAxis
                                    fontSize={11}
                                    tickLine={false}
                                    allowDecimals={false}
                                />
                                <ChartTooltip
                                    content={({ active, payload, label }) => {
                                        if (!active || !payload?.length)
                                            return null;
                                        return (
                                            <div className={glassTooltipClass}>
                                                <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                                                    {label}
                                                </p>
                                                {payload.map((entry, i) => (
                                                    <p
                                                        key={i}
                                                        className="text-xs text-neutral-600 dark:text-neutral-300"
                                                    >
                                                        <span
                                                            style={{
                                                                color: entry.color,
                                                            }}
                                                        >
                                                            ●
                                                        </span>{' '}
                                                        {entry.name}:{' '}
                                                        {entry.value}
                                                    </p>
                                                ))}
                                            </div>
                                        );
                                    }}
                                />
                                <Legend wrapperStyle={{ fontSize: '11px' }} />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_petugas"
                                    name="Petugas Teralokasi"
                                    stroke="#2563eb"
                                    strokeWidth={2}
                                    dot={false}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_kegiatan"
                                    name="Jumlah Kegiatan"
                                    stroke="#16a34a"
                                    strokeWidth={2}
                                    dot={false}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_alokasi"
                                    name="Jumlah Alokasi"
                                    stroke="#d97706"
                                    strokeWidth={2}
                                    dot={false}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Detail Table */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Detail Beban Kerja Pegawai Organik
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Aturan indikator:{' '}
                        <span className="font-medium text-red-600 dark:text-red-400">
                            Overload
                        </span>{' '}
                        &gt; 3 kegiatan/bulan &middot;{' '}
                        <span className="font-medium text-green-600 dark:text-green-400">
                            Optimal
                        </span>{' '}
                        1&ndash;3 &middot;{' '}
                        <span className="font-medium text-blue-600 dark:text-blue-400">
                            Normal
                        </span>{' '}
                        = 1 &middot;{' '}
                        <span className="font-medium text-amber-600 dark:text-amber-400">
                            Under Performance
                        </span>{' '}
                        &lt; 1
                    </p>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Nama
                                    </th>
                                    <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Jabatan
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Kegiatan
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Alokasi
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Rata-rata/Bln
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Indikator
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {pagedItems.map((item) => {
                                    const cfg =
                                        performanceConfig[
                                            item.performance_status
                                        ] ??
                                        performanceConfig.under_performance;
                                    return (
                                        <tr
                                            key={item.petugas_id}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-2 text-xs font-medium text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </td>
                                            <td className="py-2 text-xs text-neutral-500 dark:text-neutral-400">
                                                {item.jabatan || '-'}
                                            </td>
                                            <td className="py-2 text-center text-xs font-semibold text-neutral-900 dark:text-white">
                                                {item.jumlah_kegiatan}
                                            </td>
                                            <td className="py-2 text-center text-xs font-semibold text-neutral-900 dark:text-white">
                                                {item.jumlah_alokasi}
                                            </td>
                                            <td className="py-2 text-center text-xs text-neutral-600 dark:text-neutral-400">
                                                {
                                                    item.rata_rata_kegiatan_per_bulan
                                                }
                                            </td>
                                            <td className="py-2 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${cfg.badge}`}
                                                >
                                                    {item.performance_label}
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    {totalPages > 1 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 dark:border-neutral-700">
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                Menampilkan {(currentPage - 1) * PAGE_SIZE + 1}
                                &ndash;
                                {Math.min(
                                    currentPage * PAGE_SIZE,
                                    bebanKerjaDetail.length,
                                )}{' '}
                                dari {bebanKerjaDetail.length} pegawai
                            </p>
                            <div className="flex items-center gap-1">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setCurrentPage((p) =>
                                            Math.max(1, p - 1),
                                        )
                                    }
                                    disabled={currentPage === 1}
                                    className="rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50 disabled:opacity-40 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                                >
                                    &larr; Prev
                                </button>
                                {Array.from(
                                    { length: totalPages },
                                    (_, i) => i + 1,
                                ).map((page) => (
                                    <button
                                        key={page}
                                        type="button"
                                        onClick={() => setCurrentPage(page)}
                                        className={`rounded-lg border px-2.5 py-1 text-xs font-medium shadow-sm transition ${
                                            page === currentPage
                                                ? 'border-blue-500 bg-blue-500 text-white'
                                                : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700'
                                        }`}
                                    >
                                        {page}
                                    </button>
                                ))}
                                <button
                                    type="button"
                                    onClick={() =>
                                        setCurrentPage((p) =>
                                            Math.min(totalPages, p + 1),
                                        )
                                    }
                                    disabled={currentPage === totalPages}
                                    className="rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50 disabled:opacity-40 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                                >
                                    Next &rarr;
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
