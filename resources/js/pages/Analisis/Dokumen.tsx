import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Download,
    FileCheck2,
    FileText,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
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

interface KelengkapanSKPerKegiatan {
    kegiatan_id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
    jenis_kegiatan: string;
    total_sk: number;
    sk_draft: number;
    sk_diterbitkan: number;
    sk_ditandatangani: number;
    status_dokumen: 'belum' | 'sebagian' | 'lengkap';
}

interface SkDraftLama {
    id: number;
    kegiatan_nama: string;
    kegiatan_kode: string;
    bulan: number;
    tahun: number;
    umur_hari: number;
}

interface Props {
    skPerBulan: SkPerBulan[];
    spkPerBulan: SpkPerBulan[];
    skTotal: number;
    skDiterbitkan: number;
    skDraft: number;
    spkTotal: number;
    spkDiterbitkan: number;
    spkDraft: number;
    kelengkapanSKPerKegiatan: KelengkapanSKPerKegiatan[];
    skDraftLama: SkDraftLama[];
    currentYear: number;
}

function ProgressBar({
    value,
    max,
    color,
}: {
    value: number;
    max: number;
    color: string;
}) {
    const pct = max > 0 ? Math.min((value / max) * 100, 100) : 0;
    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
            <div
                className="h-full rounded-full transition-all duration-500"
                style={{ width: `${pct}%`, backgroundColor: color }}
            />
        </div>
    );
}

export default function AnalisisDokumen({
    skPerBulan,
    spkPerBulan,
    skTotal,
    skDiterbitkan,
    skDraft,
    spkTotal,
    spkDiterbitkan,
    spkDraft,
    kelengkapanSKPerKegiatan,
    skDraftLama,
    currentYear,
}: Props) {
    const skPctDiterbitkan =
        skTotal > 0 ? Math.round((skDiterbitkan / skTotal) * 100) : 0;
    const spkPctDiterbitkan =
        spkTotal > 0 ? Math.round((spkDiterbitkan / spkTotal) * 100) : 0;

    const trenData = skPerBulan.map((sk, i) => ({
        name: monthNames[i],
        sk_diterbitkan: sk.diterbitkan + sk.ditandatangani,
        sk_draft: sk.draft,
        spk_diterbitkan: spkPerBulan[i]?.diterbitkan ?? 0,
        spk_draft: spkPerBulan[i]?.draft ?? 0,
    }));

    const kpiCards = [
        {
            label: 'Total SK KPA',
            value: skTotal,
            sub: `${skDiterbitkan} diterbitkan`,
            subColor: 'text-green-600 dark:text-green-400',
            pct: skPctDiterbitkan,
            barColor: '#22c55e',
            icon: <FileText className="h-5 w-5 text-blue-500" />,
            draft: skDraft,
        },
        {
            label: 'SK Diterbitkan',
            value: skDiterbitkan,
            sub: `${skPctDiterbitkan}% dari total`,
            subColor:
                skPctDiterbitkan >= 80
                    ? 'text-green-600 dark:text-green-400'
                    : skPctDiterbitkan >= 50
                      ? 'text-amber-600 dark:text-amber-400'
                      : 'text-red-600 dark:text-red-400',
            pct: skPctDiterbitkan,
            barColor:
                skPctDiterbitkan >= 80
                    ? '#22c55e'
                    : skPctDiterbitkan >= 50
                      ? '#f59e0b'
                      : '#ef4444',
            icon: <FileCheck2 className="h-5 w-5 text-green-500" />,
            draft: null,
        },
        {
            label: 'Total Perjanjian Kerja',
            value: spkTotal,
            sub: `${spkDiterbitkan} diterbitkan`,
            subColor: 'text-green-600 dark:text-green-400',
            pct: spkPctDiterbitkan,
            barColor: '#3b82f6',
            icon: <FileText className="h-5 w-5 text-purple-500" />,
            draft: spkDraft,
        },
        {
            label: 'SPK Diterbitkan',
            value: spkDiterbitkan,
            sub: `${spkPctDiterbitkan}% dari total`,
            subColor:
                spkPctDiterbitkan >= 80
                    ? 'text-green-600 dark:text-green-400'
                    : spkPctDiterbitkan >= 50
                      ? 'text-amber-600 dark:text-amber-400'
                      : 'text-red-600 dark:text-red-400',
            pct: spkPctDiterbitkan,
            barColor:
                spkPctDiterbitkan >= 80
                    ? '#22c55e'
                    : spkPctDiterbitkan >= 50
                      ? '#f59e0b'
                      : '#ef4444',
            icon: <FileCheck2 className="h-5 w-5 text-blue-500" />,
            draft: null,
        },
    ];

    const statusConfig = {
        belum: {
            label: 'Belum Ada SK',
            icon: <XCircle className="h-4 w-4 text-red-500" />,
            badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
        },
        sebagian: {
            label: 'Ada Draft',
            icon: <Clock className="h-4 w-4 text-amber-500" />,
            badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        },
        lengkap: {
            label: 'Diterbitkan',
            icon: <CheckCircle2 className="h-4 w-4 text-green-500" />,
            badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        },
    };

    const jenisLabel: Record<string, string> = {
        sensus: 'Sensus',
        survei: 'Survei',
        kompilasi: 'Kompilasi',
    };

    const PAGE_SIZE = 10;
    const [currentPage, setCurrentPage] = useState(1);
    const totalPages = Math.ceil(kelengkapanSKPerKegiatan.length / PAGE_SIZE);
    const pagedItems = kelengkapanSKPerKegiatan.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );

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

                {/* KPI Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {kpiCards.map((card) => (
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
                            <p
                                className={`mt-0.5 text-xs ${
                                    card.draft !== null && card.draft > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'invisible'
                                }`}
                            >
                                {card.draft !== null && card.draft > 0
                                    ? `${card.draft} masih draft`
                                    : '\u00A0'}
                            </p>
                            <div className="mt-3">
                                <ProgressBar
                                    value={card.pct}
                                    max={100}
                                    color={card.barColor}
                                />
                            </div>
                        </div>
                    ))}
                </div>

                {/* Alert: SK Draft Lama */}
                {skDraftLama.length > 0 && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 shadow-lg backdrop-blur-xl dark:border-amber-800/40 dark:bg-amber-900/20">
                        <div className="mb-3 flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            <h3 className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                {skDraftLama.length} SK masih draft lebih dari
                                14 hari
                            </h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-amber-200 dark:border-amber-700/50">
                                        <th className="py-1.5 text-left text-xs font-medium text-amber-700 dark:text-amber-400">
                                            Kegiatan
                                        </th>
                                        <th className="py-1.5 text-center text-xs font-medium text-amber-700 dark:text-amber-400">
                                            Bulan
                                        </th>
                                        <th className="py-1.5 text-center text-xs font-medium text-amber-700 dark:text-amber-400">
                                            Umur
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {skDraftLama.map((sk) => (
                                        <tr
                                            key={sk.id}
                                            className="border-b border-amber-100 dark:border-amber-800/30"
                                        >
                                            <td className="py-1.5 text-xs text-amber-900 dark:text-amber-200">
                                                <span className="font-medium">
                                                    {sk.kegiatan_nama}
                                                </span>
                                            </td>
                                            <td className="py-1.5 text-center text-xs text-amber-800 dark:text-amber-300">
                                                {monthNames[sk.bulan - 1]}{' '}
                                                {sk.tahun}
                                            </td>
                                            <td className="py-1.5 text-center text-xs font-semibold text-amber-700 dark:text-amber-400">
                                                {sk.umur_hari} hari
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Kelengkapan SK per Kegiatan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Kelengkapan SK per Kegiatan
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Status penerbitan SK KPA untuk setiap kegiatan aktif
                        tahun {currentYear}
                    </p>
                    {kelengkapanSKPerKegiatan.length === 0 ? (
                        <p className="text-sm text-neutral-400 dark:text-neutral-500">
                            Tidak ada data kegiatan aktif.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Kegiatan
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Jenis
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Total SK
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Diterbitkan
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Ditandatangani
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Draft
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedItems.map((item) => {
                                        const cfg =
                                            statusConfig[item.status_dokumen];
                                        return (
                                            <tr
                                                key={item.kegiatan_id}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-2">
                                                    <p className="text-xs font-medium text-neutral-900 dark:text-white">
                                                        {item.nama_kegiatan}
                                                    </p>
                                                </td>
                                                <td className="py-2 text-center text-xs text-neutral-600 dark:text-neutral-400">
                                                    {jenisLabel[
                                                        item.jenis_kegiatan
                                                    ] ?? item.jenis_kegiatan}
                                                </td>
                                                <td className="py-2 text-center text-xs font-semibold text-neutral-900 dark:text-white">
                                                    {item.total_sk || '-'}
                                                </td>
                                                <td className="py-2 text-center text-xs font-medium text-green-600 dark:text-green-400">
                                                    {item.sk_diterbitkan || '-'}
                                                </td>
                                                <td className="py-2 text-center text-xs font-medium text-blue-600 dark:text-blue-400">
                                                    {item.sk_ditandatangani ||
                                                        '-'}
                                                </td>
                                                <td className="py-2 text-center text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    {item.sk_draft || '-'}
                                                </td>
                                                <td className="py-2 text-center">
                                                    <span
                                                        className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${cfg.badge}`}
                                                    >
                                                        {cfg.icon}
                                                        {cfg.label}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 dark:border-neutral-700">
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                Menampilkan {(currentPage - 1) * PAGE_SIZE + 1}–
                                {Math.min(
                                    currentPage * PAGE_SIZE,
                                    kelengkapanSKPerKegiatan.length,
                                )}{' '}
                                dari {kelengkapanSKPerKegiatan.length} kegiatan
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
                                    ← Prev
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
                                    Next →
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Tren Dokumen per Bulan */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* SK per Bulan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Tren SK per Bulan
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Jumlah SK diterbitkan vs draft per bulan
                        </p>
                        <ResponsiveContainer width="100%" height={220}>
                            <BarChart
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
                                            <div className="rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80">
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
                                    dataKey="sk_diterbitkan"
                                    fill="#22c55e"
                                    name="Diterbitkan"
                                    stackId="sk"
                                    radius={[0, 0, 0, 0]}
                                />
                                <Bar
                                    dataKey="sk_draft"
                                    fill="#94a3b8"
                                    name="Draft"
                                    stackId="sk"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* SPK per Bulan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Tren Perjanjian Kerja per Bulan
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Jumlah SPK diterbitkan vs draft per bulan
                        </p>
                        <ResponsiveContainer width="100%" height={220}>
                            <BarChart
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
                                            <div className="rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80">
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
                                    dataKey="spk_diterbitkan"
                                    fill="#3b82f6"
                                    name="Diterbitkan"
                                    stackId="spk"
                                    radius={[0, 0, 0, 0]}
                                />
                                <Bar
                                    dataKey="spk_draft"
                                    fill="#94a3b8"
                                    name="Draft"
                                    stackId="spk"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
