import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import {
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

const COLORS = [
    '#2563eb',
    '#16a34a',
    '#d97706',
    '#dc2626',
    '#7c3aed',
    '#0891b2',
    '#ea580c',
];

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

interface Props {
    ringkasan: Ringkasan;
    distribusiBebanKerja: DistribusiBebanKerja[];
    trenBebanKerja: TrenBebanKerja[];
    bebanKerjaDetail: BebanKerjaDetail[];
    currentMonth: number;
    currentYear: number;
}

interface BebanKerjaDetail {
    petugas_id: number;
    petugas_nama: string;
    jabatan: string | null;
    jumlah_alokasi: number;
    jumlah_kegiatan: number;
    performance_status: 'overload' | 'optimal' | 'under_performance';
    performance_label: string;
}

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

export default function AnalisisPetugasOrganik({
    ringkasan,
    distribusiBebanKerja,
    trenBebanKerja,
    bebanKerjaDetail,
    currentMonth,
    currentYear,
}: Props) {
    const distribusiChartData = distribusiBebanKerja.filter(
        (item) => item.count > 0,
    );

    const trenChartData = trenBebanKerja.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Petugas Organik" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                            Analisis Petugas Organik
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Distribusi beban kerja pegawai organik · Tahun{' '}
                            {currentYear} (Januari -{' '}
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

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-2xl border border-white/20 bg-white/50 p-4 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Total Pegawai Organik Aktif
                        </p>
                        <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                            {ringkasan.total_petugas_aktif}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/50 p-4 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Pegawai Teralokasi
                        </p>
                        <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                            {ringkasan.total_petugas_teralokasi}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-white/20 bg-white/50 p-4 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <p className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Total Alokasi
                        </p>
                        <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                            {ringkasan.total_alokasi}
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Beban Kerja Organik
                        </h3>
                        {distribusiChartData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={260}>
                                <PieChart>
                                    <Pie
                                        data={distribusiChartData}
                                        dataKey="count"
                                        nameKey="label"
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={52}
                                        outerRadius={88}
                                        paddingAngle={2}
                                        labelLine={false}
                                    >
                                        {distribusiChartData.map((_, index) => (
                                            <Cell
                                                key={`organik-pie-${index}`}
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
                                            if (!active || !payload?.length) {
                                                return null;
                                            }

                                            return (
                                                <div
                                                    className={
                                                        glassTooltipClass
                                                    }
                                                >
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
                                                            {entry.value}
                                                        </p>
                                                    ))}
                                                </div>
                                            );
                                        }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="py-16 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                Belum ada data distribusi beban kerja.
                            </p>
                        )}
                    </div>

                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Tren Beban Kerja Bulanan
                        </h3>
                        <ResponsiveContainer width="100%" height={280}>
                            <LineChart data={trenChartData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis allowDecimals={false} fontSize={12} />
                                <ChartTooltip
                                    content={({ active, payload, label }) => {
                                        if (!active || !payload?.length) {
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
                                                        {entry.value}
                                                    </p>
                                                ))}
                                            </div>
                                        );
                                    }}
                                />
                                <Legend />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_petugas"
                                    name="Petugas Teralokasi"
                                    stroke="#2563eb"
                                    strokeWidth={2}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_kegiatan"
                                    name="Jumlah Kegiatan"
                                    stroke="#16a34a"
                                    strokeWidth={2}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_alokasi"
                                    name="Jumlah Alokasi"
                                    stroke="#d97706"
                                    strokeWidth={2}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Detail Beban Kerja Pegawai Organik
                    </h3>
                    <p className="mb-3 text-xs text-neutral-500 dark:text-neutral-400">
                        Jumlah Kegiatan = jumlah kegiatan unik yang
                        dialokasikan. Jumlah Alokasi = total alokasi kegiatan
                        per pegawai dari Januari sampai bulan berjalan.
                    </p>
                    <p className="mb-3 text-xs text-neutral-500 dark:text-neutral-400">
                        Aturan indikator statis berdasarkan rata-rata kegiatan
                        per bulan: Overload {'>'} 5, Optimal 2-4, Under
                        Performance {'<'} 2.
                    </p>
                    <div className="overflow-x-auto">
                        <table className="min-w-full border-collapse text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="px-2 py-2 text-left">
                                        Nama
                                    </th>
                                    <th className="px-2 py-2 text-left">
                                        Jabatan
                                    </th>
                                    <th className="px-2 py-2 text-center">
                                        Jumlah Kegiatan
                                    </th>
                                    <th className="px-2 py-2 text-center">
                                        Jumlah Alokasi
                                    </th>
                                    <th className="px-2 py-2 text-center">
                                        Indikator
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {bebanKerjaDetail.map((item) => (
                                    <tr
                                        key={item.petugas_id}
                                        className="border-b border-neutral-100 dark:border-neutral-800"
                                    >
                                        <td className="px-2 py-2 font-medium text-neutral-900 dark:text-white">
                                            {item.petugas_nama}
                                        </td>
                                        <td className="px-2 py-2 text-neutral-600 dark:text-neutral-300">
                                            {item.jabatan || '-'}
                                        </td>
                                        <td className="px-2 py-2 text-center text-neutral-700 dark:text-neutral-200">
                                            {item.jumlah_kegiatan}
                                        </td>
                                        <td className="px-2 py-2 text-center text-neutral-700 dark:text-neutral-200">
                                            {item.jumlah_alokasi}
                                        </td>
                                        <td className="px-2 py-2 text-center">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
                                                    item.performance_status ===
                                                    'overload'
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300'
                                                        : item.performance_status ===
                                                            'under_performance'
                                                          ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                                                          : 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300'
                                                }`}
                                            >
                                                {item.performance_label}
                                            </span>
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
