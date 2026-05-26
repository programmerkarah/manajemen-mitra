import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Tooltip as ChartTooltip,
    Legend,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analisis Dokumen', href: '/analisis/dokumen' },
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

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

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

interface SkPerBulan {
    bulan: number;
    total: number;
    draft: number;
    diterbitkan: number;
    ditandatangani: number;
}

interface SpkPerBulan {
    bulan: number;
    total: number;
    draft: number;
    diterbitkan: number;
}

interface Props {
    skPerBulan: SkPerBulan[];
    spkPerBulan: SpkPerBulan[];
    skTotal: number;
    skDiterbitkan: number;
    spkTotal: number;
    spkDiterbitkan: number;
    currentYear: number;
}

export default function AnalisisDokumen({
    skPerBulan,
    spkPerBulan,
    skTotal,
    skDiterbitkan,
    spkTotal,
    spkDiterbitkan,
    currentYear,
}: Props) {
    const skChartData = skPerBulan.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    const spkChartData = spkPerBulan.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Dokumen SK dan Perjanjian Kerja" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-start justify-between rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                            Analisis Dokumen SK dan Perjanjian Kerja
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Tahun {currentYear}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            window.open(
                                '/analisis/dokumen/export-pdf',
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
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Total SK
                        </p>
                        <p className="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">
                            {skTotal}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            SK KPA Diterbitkan
                        </p>
                        <p className="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                            {skDiterbitkan}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Total Perjanjian Kerja
                        </p>
                        <p className="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">
                            {spkTotal}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Perjanjian Kerja Diterbitkan
                        </p>
                        <p className="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                            {spkDiterbitkan}
                        </p>
                    </div>
                </div>

                {/* SK Chart */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Jumlah SK yang Dibuat per Bulan
                    </h3>
                    <ResponsiveContainer width="100%" height={280}>
                        <BarChart data={skChartData}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="name" fontSize={12} />
                            <YAxis fontSize={12} allowDecimals={false} />
                            <ChartTooltip content={<GlassTooltipContent />} />
                            <Legend />
                            <Bar
                                dataKey="draft"
                                fill="#94a3b8"
                                name="Draft"
                                stackId="sk"
                                radius={[0, 0, 0, 0]}
                            />
                            <Bar
                                dataKey="ditandatangani"
                                fill="#3b82f6"
                                name="Diterbitkan & Ditandatangani"
                                stackId="sk"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="min-w-[100px] py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Status
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
                                            label: 'Total',
                                            key: 'total' as const,
                                            color: '',
                                        },
                                        {
                                            label: 'Draft',
                                            key: 'draft' as const,
                                            color: 'text-neutral-600 dark:text-neutral-400',
                                        },
                                        {
                                            label: 'Diterbitkan & Ditandatangani',
                                            key: 'ditandatangani' as const,
                                            color: 'text-blue-600 dark:text-blue-400',
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
                                        {skPerBulan.map((item) => (
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
                                            {skPerBulan.reduce(
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

                {/* SPK Chart */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Jumlah Perjanjian Kerja per Bulan
                    </h3>
                    <ResponsiveContainer width="100%" height={280}>
                        <BarChart data={spkChartData}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="name" fontSize={12} />
                            <YAxis fontSize={12} allowDecimals={false} />
                            <ChartTooltip content={<GlassTooltipContent />} />
                            <Legend />
                            <Bar
                                dataKey="draft"
                                fill="#94a3b8"
                                name="Draft"
                                stackId="spk"
                                radius={[0, 0, 0, 0]}
                            />
                            <Bar
                                dataKey="diterbitkan"
                                fill="#22c55e"
                                name="Diterbitkan"
                                stackId="spk"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="min-w-[100px] py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Status
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
                                            label: 'Total',
                                            key: 'total' as const,
                                            color: '',
                                        },
                                        {
                                            label: 'Draft',
                                            key: 'draft' as const,
                                            color: 'text-neutral-600 dark:text-neutral-400',
                                        },
                                        {
                                            label: 'Diterbitkan',
                                            key: 'diterbitkan' as const,
                                            color: 'text-green-600 dark:text-green-400',
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
                                        {spkPerBulan.map((item) => (
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
                                            {spkPerBulan.reduce(
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
