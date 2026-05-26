import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
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
    ResponsiveContainer,
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

const COLORS = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444'];

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
    [key: string]: unknown;
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

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

interface PieLegendItem {
    label: string;
    value: number;
    formattedValue: string;
    percentage: number;
    color: string;
}

function buildPieLegendItems<T extends { total: number }>(
    items: T[],
    getLabel: (item: T) => string,
    total: number,
): PieLegendItem[] {
    return items.map((item, index) => ({
        label: getLabel(item),
        value: item.total,
        formattedValue: formatRupiah(item.total),
        percentage: total > 0 ? (item.total / total) * 100 : 0,
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
                        <div className="font-semibold">
                            {item.formattedValue}
                        </div>
                        <div>{item.percentage.toFixed(1)}%</div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function GlassTooltipContent({
    active,
    payload,
    label,
}: {
    active?: boolean;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    payload?: Array<Record<string, any>>;
    label?: string;
}) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }
    return (
        <div className={glassTooltipClass}>
            {label && (
                <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                    {label}
                </p>
            )}
            {payload.map((entry, i) => (
                <p
                    key={i}
                    className="text-xs text-neutral-600 dark:text-neutral-400"
                >
                    <span style={{ color: entry.color }}>●</span> {entry.name}:{' '}
                    {entry.value}
                    {typeof entry.percent === 'number' &&
                        ` (${(entry.percent * 100).toFixed(1)}%)`}
                </p>
            ))}
        </div>
    );
}

function GlassRupiahTooltipContent({
    active,
    payload,
    label,
}: {
    active?: boolean;
    payload?: Array<{ name: string; value: number; color: string }>;
    label?: string;
}) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }
    return (
        <div className={glassTooltipClass}>
            <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                {label}
            </p>
            {payload.map((entry, i) => (
                <p
                    key={i}
                    className="text-xs text-neutral-600 dark:text-neutral-400"
                >
                    <span style={{ color: entry.color }}>●</span> {entry.name}:{' '}
                    {formatRupiah(entry.value)}
                </p>
            ))}
        </div>
    );
}

export default function AnalisisPulsa({
    pulsaPerBulan,
    rataRataPulsa,
    alokasiPulsaPerBulan,
    distribusiJenisPulsa,
    currentYear,
}: Props) {
    const chartData = pulsaPerBulan.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
        total_nominal_rb: Math.round((item.total_nominal / 1_000) * 10) / 10,
        total_disetujui_rb:
            Math.round((item.total_disetujui / 1_000) * 10) / 10,
    }));

    const totalDisetujui = pulsaPerBulan.reduce(
        (sum, item) => sum + item.total_disetujui,
        0,
    );

    const totalNominal = pulsaPerBulan.reduce(
        (sum, item) => sum + item.total_nominal,
        0,
    );
    const distribusiJenisPulsaChartData = distribusiJenisPulsa.filter(
        (item) => item.total > 0,
    );
    const totalDistribusiJenisPulsa = distribusiJenisPulsaChartData.reduce(
        (sum, item) => sum + item.total,
        0,
    );
    const distribusiJenisPulsaLegendItems = buildPieLegendItems(
        distribusiJenisPulsaChartData,
        (item) => item.jenis,
        totalDistribusiJenisPulsa,
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

                {/* Summary Cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Total Diajukan
                        </p>
                        <p className="mt-1 text-xl font-bold text-neutral-900 dark:text-white">
                            {formatRupiah(totalNominal)}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Total Disetujui
                        </p>
                        <p className="mt-1 text-xl font-bold text-green-600 dark:text-green-400">
                            {formatRupiah(totalDisetujui)}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Rata-rata per Petugas (Disetujui)
                        </p>
                        <p className="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">
                            {formatRupiah(rataRataPulsa)}
                        </p>
                    </div>
                </div>

                {/* Charts Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Distribusi Pulsa per Bulan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Alokasi Pulsa per Bulan (ribu Rp)
                        </h3>
                        <ResponsiveContainer width="100%" height={280}>
                            <BarChart data={chartData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis fontSize={12} />
                                <ChartTooltip
                                    content={<GlassTooltipContent />}
                                />
                                <Legend />
                                <Bar
                                    dataKey="total_nominal_rb"
                                    fill="#3b82f6"
                                    name="Diajukan"
                                    radius={[4, 4, 0, 0]}
                                />
                                <Bar
                                    dataKey="total_disetujui_rb"
                                    fill="#22c55e"
                                    name="Disetujui"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Distribusi Jenis Pulsa */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi per Jenis Pulsa
                        </h3>
                        {distribusiJenisPulsa.length > 0 ? (
                            <>
                                <ResponsiveContainer width="100%" height={220}>
                                    <PieChart>
                                        <Pie
                                            data={distribusiJenisPulsaChartData}
                                            dataKey="total"
                                            nameKey="jenis"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={54}
                                            outerRadius={86}
                                            paddingAngle={2}
                                            labelLine={false}
                                        >
                                            {distribusiJenisPulsaChartData.map(
                                                (_, index) => (
                                                    <Cell
                                                        key={`jenis-pulsa-cell-${index}`}
                                                        fill={
                                                            COLORS[
                                                                index %
                                                                    COLORS.length
                                                            ]
                                                        }
                                                    />
                                                ),
                                            )}
                                        </Pie>
                                        <ChartTooltip
                                            content={({ active, payload }) => {
                                                if (!active || !payload?.length)
                                                    return null;
                                                const entry = payload[0];
                                                const total =
                                                    totalDistribusiJenisPulsa;
                                                const pct =
                                                    total > 0
                                                        ? (Number(entry.value) /
                                                              total) *
                                                          100
                                                        : 0;
                                                const pctLabel =
                                                    Number.isInteger(pct)
                                                        ? pct.toFixed(0)
                                                        : pct.toFixed(1);
                                                return (
                                                    <div
                                                        className={
                                                            glassTooltipClass
                                                        }
                                                    >
                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                            <span
                                                                style={{
                                                                    color: entry.color,
                                                                }}
                                                            >
                                                                ●
                                                            </span>{' '}
                                                            {entry.name}:{' '}
                                                            {formatRupiah(
                                                                Number(
                                                                    entry.value,
                                                                ),
                                                            )}{' '}
                                                            ({pctLabel}%)
                                                        </p>
                                                    </div>
                                                );
                                            }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                                <PieLegendList
                                    items={distribusiJenisPulsaLegendItems}
                                />
                            </>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Belum ada data pengajuan pulsa
                            </p>
                        )}
                    </div>
                </div>

                {/* Rata-rata per Petugas per Bulan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Rata-rata Penggunaan Pulsa per Petugas per Bulan
                    </h3>
                    <ResponsiveContainer width="100%" height={250}>
                        <LineChart data={chartData}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="name" fontSize={12} />
                            <YAxis fontSize={12} />
                            <ChartTooltip
                                content={<GlassRupiahTooltipContent />}
                            />
                            <Legend />
                            <Line
                                type="monotone"
                                dataKey="rata_rata_per_petugas"
                                stroke="#8b5cf6"
                                name="Rata-rata per Petugas"
                                strokeWidth={2}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>

                {/* Tabel Alokasi per Bulan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Tabel Alokasi Petugas dan Kegiatan (Pulsa)
                    </h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="min-w-[120px] py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Kategori
                                    </th>
                                    {monthNames.map((m) => (
                                        <th
                                            key={m}
                                            className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400"
                                        >
                                            {m}
                                        </th>
                                    ))}
                                    <th className="py-2 text-center font-semibold text-neutral-600 dark:text-neutral-400">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(
                                    [
                                        {
                                            label: 'Petugas',
                                            key: 'jumlah_petugas' as const,
                                            color: '',
                                        },
                                        {
                                            label: 'Kegiatan',
                                            key: 'jumlah_kegiatan' as const,
                                            color: '',
                                        },
                                        {
                                            label: 'Pengajuan Dikirim [Petugas x Kegiatan]',
                                            key: 'diajukan' as const,
                                            color: 'text-blue-600 dark:text-blue-400',
                                        },
                                        {
                                            label: 'Pengajuan Disetujui [Petugas x Kegiatan]',
                                            key: 'disetujui' as const,
                                            color: 'text-green-600 dark:text-green-400',
                                        },
                                        {
                                            label: 'Pengajuan Ditolak [Petugas x Kegiatan]',
                                            key: 'ditolak' as const,
                                            color: 'text-red-600 dark:text-red-400',
                                        },
                                        {
                                            label: 'Pengajuan Pending [Petugas x Kegiatan]',
                                            key: 'menunggu' as const,
                                            color: 'text-yellow-600 dark:text-yellow-400',
                                        },
                                    ] as const
                                ).map((row) => (
                                    <tr
                                        key={row.key}
                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                    >
                                        <td
                                            className={`py-1.5 font-medium ${row.color || 'text-neutral-900 dark:text-white'}`}
                                        >
                                            {row.label}
                                        </td>
                                        {alokasiPulsaPerBulan.map((item) => (
                                            <td
                                                key={item.bulan}
                                                className={`py-1.5 text-center font-medium ${row.color || 'text-neutral-900 dark:text-white'}`}
                                            >
                                                {item[row.key]}
                                            </td>
                                        ))}
                                        <td
                                            className={`py-1.5 text-center font-bold ${row.color || 'text-neutral-900 dark:text-white'}`}
                                        >
                                            {alokasiPulsaPerBulan.reduce(
                                                (s, i) => s + i[row.key],
                                                0,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
