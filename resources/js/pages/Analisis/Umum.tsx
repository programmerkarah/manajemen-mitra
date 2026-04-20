import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
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
    label: string;
    count: number;
}

interface TrenAlokasi {
    bulan: number;
    jumlah_petugas: number;
    total_honor: number;
}

interface KelengkapanDokumen {
    nama_kegiatan: string;
    kode_kegiatan: string;
    total_periode: number;
    sk_diterbitkan: number;
    spk_diterbitkan: number;
}

interface Props {
    utilisasiAnggaran: UtilisasiAnggaran[];
    distribusiBebanKerja: DistribusiBebanKerja[];
    trenAlokasi: TrenAlokasi[];
    kelengkapanDokumen: KelengkapanDokumen[];
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

function GlassTooltipContent({
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
                    {entry.value}
                </p>
            ))}
        </div>
    );
}

export default function AnalisisUmum({
    utilisasiAnggaran,
    distribusiBebanKerja,
    trenAlokasi,
    kelengkapanDokumen,
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
                <div className="rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                        Analisis Umum
                    </h1>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Utilisasi anggaran, beban kerja petugas, tren alokasi,
                        dan kelengkapan dokumen · Tahun {currentYear}
                    </p>
                </div>

                {/* Charts Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Beban Kerja */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Beban Kerja Petugas
                        </h3>
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
                                    label={({ label, count }) =>
                                        `${label}: ${count}`
                                    }
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
                                    content={<GlassTooltipContent />}
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
                                />
                                <YAxis
                                    yAxisId="right"
                                    fontSize={12}
                                    orientation="right"
                                />
                                <ChartTooltip
                                    content={<GlassTooltipContent />}
                                />
                                <Legend />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_petugas"
                                    stroke="#3b82f6"
                                    name="Jumlah Petugas"
                                    strokeWidth={2}
                                    yAxisId="left"
                                />
                                <Line
                                    type="monotone"
                                    dataKey="total_honor_jt"
                                    stroke="#22c55e"
                                    name="Total Honor (juta Rp)"
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

                {/* Kelengkapan Dokumen */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Kelengkapan Dokumen per Kegiatan
                    </h3>
                    {kelengkapanDokumen.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Kegiatan
                                        </th>
                                        <th className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                            Periode Aktif
                                        </th>
                                        <th className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                            SK Diterbitkan
                                        </th>
                                        <th className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                            Perjanjian Kerja Diterbitkan
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {kelengkapanDokumen.map((item, index) => (
                                        <tr
                                            key={index}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-1.5">
                                                <div className="text-neutral-900 dark:text-white">
                                                    {item.nama_kegiatan}
                                                </div>
                                            </td>
                                            <td className="py-1.5 text-right font-medium text-neutral-900 dark:text-white">
                                                {item.total_periode}
                                            </td>
                                            <td className="py-1.5 text-right font-medium text-green-600 dark:text-green-400">
                                                {item.sk_diterbitkan}
                                            </td>
                                            <td className="py-1.5 text-right font-medium text-blue-600 dark:text-blue-400">
                                                {item.spk_diterbitkan}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Belum ada kegiatan aktif
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
