import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Download, TrendingUp, Wallet } from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
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
    { title: 'Analisis Pulsa', href: '/analisis/pulsa' },
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

const PIE_COLORS = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444'];

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function formatRupiahCompact(value: number): string {
    if (value >= 1_000_000) {
        return `Rp ${(value / 1_000_000).toFixed(1)}jt`;
    }
    if (value >= 1_000) {
        return `Rp ${(value / 1_000).toFixed(0)}rb`;
    }
    return formatRupiah(value);
}

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

interface PulsaPerBulan {
    bulan: number;
    total_pengajuan: number;
    total_nominal: number;
    total_disetujui: number;
    jumlah_petugas: number;
    rata_rata_per_petugas: number;
}

interface AlokasiPulsaPerBulan {
    bulan: number;
    jumlah_petugas: number;
    jumlah_kegiatan: number;
    diajukan: number;
    disetujui: number;
    ditolak: number;
    menunggu: number;
}

interface DistribusiJenisPulsa {
    jenis: string;
    count: number;
    total: number;
}

interface Props {
    pulsaPerBulan: PulsaPerBulan[];
    rataRataPulsa: number;
    alokasiPulsaPerBulan: AlokasiPulsaPerBulan[];
    distribusiJenisPulsa: DistribusiJenisPulsa[];
    currentYear: number;
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
                {formatRupiah(value)} ({((percent ?? 0) * 100).toFixed(1)}%)
            </text>
        </g>
    );
}

export default function AnalisisPulsa({
    pulsaPerBulan,
    rataRataPulsa,
    alokasiPulsaPerBulan,
    distribusiJenisPulsa,
    currentYear,
}: Props) {
    const totalNominal = pulsaPerBulan.reduce(
        (s, i) => s + Number(i.total_nominal ?? 0),
        0,
    );
    const totalDisetujui = pulsaPerBulan.reduce(
        (s, i) => s + Number(i.total_disetujui ?? 0),
        0,
    );
    const totalPengajuan = pulsaPerBulan.reduce(
        (s, i) => s + Number(i.total_pengajuan ?? 0),
        0,
    );
    const approvalRate =
        totalNominal > 0
            ? Math.round((totalDisetujui / totalNominal) * 100)
            : 0;

    const barData = pulsaPerBulan.map((item) => ({
        name: monthNames[item.bulan - 1],
        diajukan: Math.round(item.total_nominal / 1000),
        disetujui: Math.round(item.total_disetujui / 1000),
    }));

    const lineData = pulsaPerBulan.map((item) => ({
        name: monthNames[item.bulan - 1],
        rata_rata: item.rata_rata_per_petugas,
        jumlah_petugas: item.jumlah_petugas,
    }));

    const pieData = distribusiJenisPulsa.filter((d) => d.total > 0);
    const totalPie = pieData.reduce((s, d) => s + d.total, 0);

    const approvalRateColor =
        approvalRate >= 80
            ? '#22c55e'
            : approvalRate >= 50
              ? '#f59e0b'
              : '#ef4444';
    const approvalRateTextColor =
        approvalRate >= 80
            ? 'text-green-600 dark:text-green-400'
            : approvalRate >= 50
              ? 'text-amber-600 dark:text-amber-400'
              : 'text-red-600 dark:text-red-400';

    const [activePieIndex, setActivePieIndex] = useState<number | undefined>(
        undefined,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Kebutuhan dan Pengadaan Pulsa" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-start justify-between rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                            Analisis Kebutuhan dan Pengadaan Pulsa
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Tahun {currentYear}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            window.open(
                                '/analisis/pulsa/export-pdf',
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
                                label: 'Total Pengajuan',
                                valueStr: totalPengajuan.toString(),
                                sub: 'Jumlah transaksi pulsa',
                                subColor:
                                    'text-neutral-500 dark:text-neutral-400',
                                barPct: 100,
                                barColor: '#3b82f6',
                                icon: (
                                    <Wallet className="h-5 w-5 text-blue-500" />
                                ),
                            },
                            {
                                label: 'Total Diajukan',
                                valueStr: formatRupiahCompact(totalNominal),
                                sub: formatRupiah(totalNominal),
                                subColor:
                                    'text-neutral-500 dark:text-neutral-400',
                                barPct: 100,
                                barColor: '#f59e0b',
                                icon: (
                                    <Wallet className="h-5 w-5 text-amber-500" />
                                ),
                            },
                            {
                                label: 'Total Disetujui',
                                valueStr: formatRupiahCompact(totalDisetujui),
                                sub: formatRupiah(totalDisetujui),
                                subColor: 'text-green-600 dark:text-green-400',
                                barPct: approvalRate,
                                barColor: approvalRateColor,
                                icon: (
                                    <TrendingUp className="h-5 w-5 text-green-500" />
                                ),
                            },
                            {
                                label: 'Approval Rate',
                                valueStr: `${approvalRate}%`,
                                sub: `Rata-rata/petugas: ${formatRupiahCompact(rataRataPulsa)}`,
                                subColor: approvalRateTextColor,
                                barPct: approvalRate,
                                barColor: approvalRateColor,
                                icon: (
                                    <TrendingUp className="h-5 w-5 text-purple-500" />
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
                                {card.valueStr}
                            </p>
                            <p
                                className={`mt-0.5 truncate text-xs ${card.subColor}`}
                            >
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

                {/* Charts */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Bar: nominal per bulan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Nominal Pulsa per Bulan (ribu Rp)
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Perbandingan nominal diajukan vs disetujui
                        </p>
                        <ResponsiveContainer width="100%" height={240}>
                            <BarChart
                                data={barData}
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
                                <YAxis fontSize={11} tickLine={false} />
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
                                <Bar
                                    dataKey="diajukan"
                                    name="Diajukan (rb)"
                                    fill="#f59e0b"
                                    radius={[4, 4, 0, 0]}
                                />
                                <Bar
                                    dataKey="disetujui"
                                    name="Disetujui (rb)"
                                    fill="#22c55e"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Pie: distribusi jenis */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi per Jenis Pulsa
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Komposisi nominal disetujui berdasarkan jenis
                            kegiatan
                        </p>
                        {pieData.length > 0 ? (
                            <>
                                <ResponsiveContainer width="100%" height={220}>
                                    <PieChart
                                        style={{ overflow: 'visible' }}
                                        margin={{
                                            top: 10,
                                            right: 70,
                                            bottom: 10,
                                            left: 70,
                                        }}
                                        onClick={() =>
                                            setActivePieIndex(undefined)
                                        }
                                    >
                                        <Pie
                                            data={pieData}
                                            dataKey="total"
                                            nameKey="jenis"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={44}
                                            outerRadius={72}
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
                                                            i %
                                                                PIE_COLORS.length
                                                        ]
                                                    }
                                                />
                                            ))}
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="mt-2 space-y-2">
                                    {pieData.map((item, i) => (
                                        <div
                                            key={item.jenis}
                                            className="flex items-center justify-between rounded-lg border border-neutral-200/70 bg-white/70 px-3 py-2 text-xs dark:border-neutral-700/60 dark:bg-neutral-900/40"
                                        >
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className="inline-block h-2.5 w-2.5 rounded-full"
                                                    style={{
                                                        backgroundColor:
                                                            PIE_COLORS[
                                                                i %
                                                                    PIE_COLORS.length
                                                            ],
                                                    }}
                                                />
                                                <span className="font-medium text-neutral-700 dark:text-neutral-200">
                                                    {item.jenis}
                                                </span>
                                            </div>
                                            <div className="text-right text-neutral-600 dark:text-neutral-300">
                                                <span className="font-semibold">
                                                    {formatRupiah(item.total)}
                                                </span>
                                                <span className="ml-2 text-neutral-400">
                                                    {totalPie > 0
                                                        ? (
                                                              (item.total /
                                                                  totalPie) *
                                                              100
                                                          ).toFixed(1)
                                                        : 0}
                                                    %
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <p className="py-16 text-center text-sm text-neutral-400">
                                Belum ada data pengajuan pulsa.
                            </p>
                        )}
                    </div>
                </div>

                {/* Line chart: rata-rata per petugas */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Rata-rata Pulsa per Petugas &amp; Jumlah Petugas per
                        Bulan
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Rata-rata nominal disetujui per petugas yang mengajukan
                    </p>
                    <ResponsiveContainer width="100%" height={220}>
                        <LineChart
                            data={lineData}
                            margin={{ top: 0, right: 20, left: 10, bottom: 0 }}
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
                                yAxisId="left"
                                fontSize={11}
                                tickLine={false}
                                tickFormatter={(v: number) =>
                                    `${Math.round(v / 1000)}rb`
                                }
                            />
                            <YAxis
                                yAxisId="right"
                                orientation="right"
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
                                                        &bull;
                                                    </span>{' '}
                                                    {entry.name}:{' '}
                                                    {entry.name ===
                                                    'Rata-rata/Petugas'
                                                        ? formatRupiah(
                                                              Number(
                                                                  entry.value,
                                                              ),
                                                          )
                                                        : entry.value}
                                                </p>
                                            ))}
                                        </div>
                                    );
                                }}
                            />
                            <Legend wrapperStyle={{ fontSize: '11px' }} />
                            <Line
                                yAxisId="left"
                                type="monotone"
                                dataKey="rata_rata"
                                name="Rata-rata/Petugas"
                                stroke="#8b5cf6"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                yAxisId="right"
                                type="monotone"
                                dataKey="jumlah_petugas"
                                name="Jumlah Petugas"
                                stroke="#f59e0b"
                                strokeWidth={2}
                                dot={false}
                                strokeDasharray="4 2"
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>

                {/* Tabel per Bulan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Ringkasan Pengajuan Pulsa per Bulan
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Detail status pengajuan pulsa setiap bulan
                    </p>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Bulan
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Petugas
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Kegiatan
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-blue-600 dark:text-blue-400">
                                        Diajukan
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-green-600 dark:text-green-400">
                                        Disetujui
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-red-600 dark:text-red-400">
                                        Ditolak
                                    </th>
                                    <th className="py-2 text-center text-xs font-medium text-amber-600 dark:text-amber-400">
                                        Menunggu
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {alokasiPulsaPerBulan.map((item) => (
                                    <tr
                                        key={item.bulan}
                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                    >
                                        <td className="py-2 text-xs font-medium text-neutral-900 dark:text-white">
                                            {monthNames[item.bulan - 1]}
                                        </td>
                                        <td className="py-2 text-center text-xs text-neutral-700 dark:text-neutral-300">
                                            {item.jumlah_petugas || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs text-neutral-700 dark:text-neutral-300">
                                            {item.jumlah_kegiatan || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-blue-600 dark:text-blue-400">
                                            {item.diajukan || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-green-600 dark:text-green-400">
                                            {item.disetujui || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-red-600 dark:text-red-400">
                                            {item.ditolak || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-amber-600 dark:text-amber-400">
                                            {item.menunggu || '-'}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t-2 border-neutral-300 bg-neutral-50/50 dark:border-neutral-600 dark:bg-neutral-800/50">
                                    <td className="py-2 text-xs font-bold text-neutral-900 dark:text-white">
                                        Total
                                    </td>
                                    <td className="py-2 text-center text-xs font-bold text-neutral-500">
                                        &mdash;
                                    </td>
                                    <td className="py-2 text-center text-xs font-bold text-neutral-500">
                                        &mdash;
                                    </td>
                                    <td className="py-2 text-center text-xs font-bold text-blue-600 dark:text-blue-400">
                                        {alokasiPulsaPerBulan.reduce(
                                            (s, i) => s + i.diajukan,
                                            0,
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-xs font-bold text-green-600 dark:text-green-400">
                                        {alokasiPulsaPerBulan.reduce(
                                            (s, i) => s + i.disetujui,
                                            0,
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-xs font-bold text-red-600 dark:text-red-400">
                                        {alokasiPulsaPerBulan.reduce(
                                            (s, i) => s + i.ditolak,
                                            0,
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-xs font-bold text-amber-600 dark:text-amber-400">
                                        {alokasiPulsaPerBulan.reduce(
                                            (s, i) => s + i.menunggu,
                                            0,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
