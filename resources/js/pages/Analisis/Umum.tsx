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
    { title: 'Dashboard', href: '/dashboard' },
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

interface Props {
    utilisasiAnggaran: UtilisasiAnggaran[];
    distribusiBebanKerja: DistribusiBebanKerja[];
    trenAlokasi: TrenAlokasi[];
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

export default function AnalisisUmum({
    utilisasiAnggaran,
    distribusiBebanKerja,
    trenAlokasi,
    currentYear,
}: Props) {
    const trenChartData = trenAlokasi.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
        total_honor_jt: Math.round((item.total_honor / 1_000_000) * 10) / 10,
    }));

    const filteredUtilisasi = utilisasiAnggaran.filter((u) => u.total_pagu > 0);

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
                            Utilisasi anggaran, beban kerja petugas, dan tren
                            alokasi · Tahun {currentYear}
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

                {/* Charts Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Beban Kerja */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Beban Kerja Petugas
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Total petugas berdasarkan jumlah kegiatan yang
                            ditangani sepanjang tahun
                        </p>
                        <ResponsiveContainer width="100%" height={280}>
                            <PieChart>
                                <Pie
                                    data={distribusiBebanKerja.filter(
                                        (d) => d.count > 0,
                                    )}
                                    dataKey="count"
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={80}
                                    label={(
                                        // eslint-disable-next-line @typescript-eslint/no-explicit-any
                                        props: any,
                                    ) => `${props.label}: ${props.count}`}
                                >
                                    {distribusiBebanKerja
                                        .filter((d) => d.count > 0)
                                        .map((_, index) => (
                                            <Cell
                                                key={`cell-${index}`}
                                                fill={
                                                    COLORS[
                                                        index % COLORS.length
                                                    ]
                                                }
                                            />
                                        ))}
                                </Pie>
                                <ChartTooltip
                                    content={({ active, payload }) => {
                                        if (!active || !payload?.length)
                                            return null;
                                        const entry = payload[0];
                                        const total =
                                            distribusiBebanKerja.reduce(
                                                (s, d) => s + d.count,
                                                0,
                                            );
                                        const pct =
                                            total > 0
                                                ? (Number(entry.value) /
                                                      total) *
                                                  100
                                                : 0;
                                        const pctLabel = Number.isInteger(pct)
                                            ? pct.toFixed(0)
                                            : pct.toFixed(1);
                                        return (
                                            <div className={glassTooltipClass}>
                                                <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                    <span
                                                        style={{
                                                            color: entry.color,
                                                        }}
                                                    >
                                                        ●
                                                    </span>{' '}
                                                    {entry.name}: {entry.value}{' '}
                                                    ({pctLabel}%)
                                                </p>
                                            </div>
                                        );
                                    }}
                                />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Tren Alokasi */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Tren Alokasi Bulanan
                        </h3>
                        <ResponsiveContainer width="100%" height={280}>
                            <LineChart data={trenChartData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis
                                    yAxisId="left"
                                    fontSize={12}
                                    orientation="left"
                                    tickFormatter={(v) =>
                                        `${(v / 1_000_000).toFixed(0)}jt`
                                    }
                                />
                                <YAxis
                                    yAxisId="right"
                                    fontSize={12}
                                    orientation="right"
                                    allowDecimals={false}
                                />
                                <ChartTooltip
                                    content={({ active, payload, label }) => {
                                        if (
                                            !active ||
                                            !payload ||
                                            payload.length === 0
                                        ) {
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
                                <Legend />
                                <Line
                                    type="monotone"
                                    dataKey="total_honor"
                                    stroke="#22c55e"
                                    name="Total Honor"
                                    strokeWidth={2}
                                    yAxisId="left"
                                />
                                <Line
                                    type="monotone"
                                    dataKey="total_kegiatan"
                                    stroke="#3b82f6"
                                    name="Total Kegiatan"
                                    strokeWidth={2}
                                    yAxisId="right"
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Utilisasi Anggaran */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Penyerapan Anggaran per Kegiatan
                    </h3>
                    {filteredUtilisasi.length > 0 ? (
                        <>
                            <ResponsiveContainer
                                width="100%"
                                height={Math.max(
                                    200,
                                    filteredUtilisasi.length * 40,
                                )}
                            >
                                <BarChart
                                    data={filteredUtilisasi}
                                    layout="vertical"
                                >
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis
                                        type="number"
                                        fontSize={12}
                                        domain={[0, 100]}
                                        tickFormatter={(v) => `${v}%`}
                                    />
                                    <YAxis
                                        dataKey="nama_kegiatan"
                                        type="category"
                                        fontSize={11}
                                        width={10}
                                        tick={false}
                                    />
                                    <ChartTooltip
                                        content={({ active, payload }) => {
                                            if (
                                                !active ||
                                                !payload ||
                                                payload.length === 0
                                            ) {
                                                return null;
                                            }
                                            const data = payload[0]
                                                .payload as UtilisasiAnggaran;
                                            return (
                                                <div
                                                    className={
                                                        glassTooltipClass
                                                    }
                                                >
                                                    <p className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                                                        {data.nama_kegiatan}
                                                    </p>
                                                    <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                        Pagu:{' '}
                                                        {formatRupiah(
                                                            data.total_pagu,
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                        Terpakai:{' '}
                                                        {formatRupiah(
                                                            data.total_terpakai,
                                                        )}
                                                    </p>
                                                    <p className="text-xs font-medium text-neutral-900 dark:text-white">
                                                        Penyerapan:{' '}
                                                        {data.persentase}%
                                                    </p>
                                                </div>
                                            );
                                        }}
                                    />
                                    <Bar
                                        dataKey="persentase"
                                        name="Penyerapan"
                                        radius={[0, 4, 4, 0]}
                                    >
                                        {filteredUtilisasi.map(
                                            (entry, index) => (
                                                <Cell
                                                    key={`cell-${index}`}
                                                    fill={
                                                        entry.persentase > 90
                                                            ? '#ef4444'
                                                            : entry.persentase >
                                                                70
                                                              ? '#f59e0b'
                                                              : '#22c55e'
                                                    }
                                                />
                                            ),
                                        )}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                            <div className="mt-4 max-h-96 overflow-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="min-w-[200px] py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Kegiatan
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                                Pagu
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                                Terpakai
                                            </th>
                                            <th className="min-w-[60px] py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                                %
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredUtilisasi.map((item) => (
                                            <tr
                                                key={item.kegiatan_id}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-1.5">
                                                    <div className="text-neutral-900 dark:text-white">
                                                        {item.nama_kegiatan}
                                                    </div>
                                                </td>
                                                <td className="py-1.5 text-right text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_pagu,
                                                    )}
                                                </td>
                                                <td className="py-1.5 text-right text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_terpakai,
                                                    )}
                                                </td>
                                                <td
                                                    className={`py-1.5 text-right font-medium ${
                                                        item.persentase > 90
                                                            ? 'text-red-600 dark:text-red-400'
                                                            : item.persentase >
                                                                70
                                                              ? 'text-amber-600 dark:text-amber-400'
                                                              : 'text-green-600 dark:text-green-400'
                                                    }`}
                                                >
                                                    {item.persentase}%
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
