import { SearchableSelect } from '@/components/searchable-select';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
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
    { title: 'Analisis Petugas', href: '/analisis/petugas' },
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
    '#ef4444',
    '#22c55e',
    '#f59e0b',
    '#8b5cf6',
    '#ec4899',
    '#14b8a6',
    '#f97316',
    '#6366f1',
    '#84cc16',
];

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

interface DistribusiItem {
    label: string;
    value?: string;
    count: number;
}

interface KecamatanItem {
    kecamatan: string;
    count: number;
}

interface PendidikanItem {
    pendidikan: string;
    count: number;
}

interface AlokasiPerBulan {
    bulan: number;
    jumlah_petugas: number;
    jumlah_kegiatan: number;
}

interface KegiatanItem {
    id: number;
    nama: string;
    kode: string;
}

interface PetugasKegiatanItem {
    petugas_id: number;
    petugas_nama: string;
    kegiatan: KegiatanItem[];
    jumlah_kegiatan: number;
}

interface KegiatanListItem {
    id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
}

interface PetugasAlokasiDetail {
    petugas_id: number;
    petugas_nama: string;
    bulan: Record<number, number>;
    total: number;
}

interface PetugasListItem {
    id: number;
    nama: string;
}

interface Props {
    distribusiJenisKelamin: DistribusiItem[];
    distribusiKecamatan: KecamatanItem[];
    distribusiUsia: DistribusiItem[];
    distribusiPendidikan: PendidikanItem[];
    alokasiPerBulan: AlokasiPerBulan[];
    petugasKegiatan: PetugasKegiatanItem[];
    kegiatanList: KegiatanListItem[];
    petugasAlokasiDetail: PetugasAlokasiDetail[];
    petugasList: PetugasListItem[];
    totalPetugas: number;
    currentYear: number;
}

export default function AnalisisPetugas({
    distribusiJenisKelamin,
    distribusiKecamatan,
    distribusiUsia,
    distribusiPendidikan,
    alokasiPerBulan,
    petugasKegiatan,
    kegiatanList,
    petugasAlokasiDetail,
    petugasList,
    totalPetugas,
    currentYear,
}: Props) {
    const [kegiatanFilter, setKegiatanFilter] = useState<string>('');
    const [searchPetugas, setSearchPetugas] = useState('');
    const [selectedPetugasChart, setSelectedPetugasChart] =
        useState<string>('');
    const [searchAlokasiDetail, setSearchAlokasiDetail] = useState('');

    const filteredPetugasKegiatan = useMemo(() => {
        let data = petugasKegiatan;

        if (kegiatanFilter) {
            const kId = Number(kegiatanFilter);
            data = data.filter((p) => p.kegiatan.some((k) => k.id === kId));
        }

        if (searchPetugas.trim()) {
            const q = searchPetugas.toLowerCase();
            data = data.filter((p) => p.petugas_nama.toLowerCase().includes(q));
        }

        return data;
    }, [petugasKegiatan, kegiatanFilter, searchPetugas]);

    const filteredAlokasiDetail = useMemo(() => {
        if (!searchAlokasiDetail.trim()) {
            return petugasAlokasiDetail;
        }
        const q = searchAlokasiDetail.toLowerCase();
        return petugasAlokasiDetail.filter((p) =>
            p.petugas_nama.toLowerCase().includes(q),
        );
    }, [petugasAlokasiDetail, searchAlokasiDetail]);

    const selectedPetugasChartData = useMemo(() => {
        if (!selectedPetugasChart) {
            return null;
        }
        const petugas = petugasAlokasiDetail.find(
            (p) => String(p.petugas_id) === selectedPetugasChart,
        );
        if (!petugas) {
            return null;
        }
        return monthNames.map((name, i) => ({
            name,
            jumlah_kegiatan: petugas.bulan[i + 1] || 0,
        }));
    }, [petugasAlokasiDetail, selectedPetugasChart]);

    const alokasiChartData = alokasiPerBulan.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    const totalAlokasiTahun = alokasiPerBulan.reduce(
        (sum, item) => sum + item.jumlah_petugas,
        0,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Petugas Non-Organik" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                        Analisis Petugas Non-Organik
                    </h1>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Tahun {currentYear} · Total petugas non-organik aktif:{' '}
                        <span className="font-semibold text-neutral-900 dark:text-white">
                            {totalPetugas}
                        </span>
                    </p>
                </div>

                {/* Charts Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Jenis Kelamin */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Jenis Kelamin
                        </h3>
                        {distribusiJenisKelamin.length > 0 ? (
                            <ResponsiveContainer width="100%" height={250}>
                                <PieChart>
                                    <Pie
                                        data={distribusiJenisKelamin}
                                        dataKey="count"
                                        nameKey="label"
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={80}
                                        label={({ label, count }) =>
                                            `${label}: ${count}`
                                        }
                                    >
                                        {distribusiJenisKelamin.map(
                                            (_, index) => (
                                                <Cell
                                                    key={`cell-${index}`}
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
                                        content={<GlassTooltipContent />}
                                    />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data jenis kelamin belum tersedia
                            </p>
                        )}
                    </div>

                    {/* Usia */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Usia
                        </h3>
                        {distribusiUsia.some((d) => d.count > 0) ? (
                            <ResponsiveContainer width="100%" height={250}>
                                <BarChart data={distribusiUsia}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="label" fontSize={12} />
                                    <YAxis fontSize={12} />
                                    <ChartTooltip
                                        content={<GlassTooltipContent />}
                                    />
                                    <Bar
                                        dataKey="count"
                                        fill="#3b82f6"
                                        name="Jumlah"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data tanggal lahir belum tersedia
                            </p>
                        )}
                    </div>
                </div>

                {/* Kecamatan & Pendidikan */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Kecamatan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Kecamatan
                        </h3>
                        {distribusiKecamatan.length > 0 ? (
                            <div className="max-h-64 overflow-y-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Kecamatan
                                            </th>
                                            <th className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                                Jumlah
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {distribusiKecamatan.map((item) => (
                                            <tr
                                                key={item.kecamatan}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-1.5 text-neutral-900 dark:text-white">
                                                    {item.kecamatan}
                                                </td>
                                                <td className="py-1.5 text-right font-medium text-neutral-900 dark:text-white">
                                                    {item.count}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data kecamatan belum tersedia
                            </p>
                        )}
                    </div>

                    {/* Pendidikan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Pendidikan
                        </h3>
                        <ResponsiveContainer width="100%" height={250}>
                            <BarChart
                                data={distribusiPendidikan}
                                layout="vertical"
                            >
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis type="number" fontSize={12} />
                                <YAxis
                                    dataKey="pendidikan"
                                    type="category"
                                    fontSize={12}
                                    width={40}
                                />
                                <ChartTooltip
                                    content={<GlassTooltipContent />}
                                />
                                <Bar
                                    dataKey="count"
                                    fill="#22c55e"
                                    name="Jumlah"
                                    radius={[0, 4, 4, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Alokasi Petugas per Bulan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                            Tabel Alokasi Petugas per Bulan
                        </h3>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            Total alokasi setahun: {totalAlokasiTahun}
                        </span>
                    </div>
                    <div className="mb-4">
                        <ResponsiveContainer width="100%" height={250}>
                            <LineChart data={alokasiChartData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis fontSize={12} />
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
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_kegiatan"
                                    stroke="#22c55e"
                                    name="Jumlah Kegiatan"
                                    strokeWidth={2}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Bulan
                                    </th>
                                    <th className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                        Jumlah Petugas
                                    </th>
                                    <th className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                        Jumlah Kegiatan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {alokasiPerBulan.map((item) => (
                                    <tr
                                        key={item.bulan}
                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                    >
                                        <td className="py-1.5 text-neutral-900 dark:text-white">
                                            {monthNames[item.bulan - 1]}
                                        </td>
                                        <td className="py-1.5 text-right font-medium text-neutral-900 dark:text-white">
                                            {item.jumlah_petugas}
                                        </td>
                                        <td className="py-1.5 text-right font-medium text-neutral-900 dark:text-white">
                                            {item.jumlah_kegiatan}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Per-Petugas Chart */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Grafik Alokasi per Petugas
                    </h3>
                    <div className="mb-4 w-72">
                        <SearchableSelect
                            options={petugasList.map((p) => ({
                                value: String(p.id),
                                label: p.nama,
                            }))}
                            value={selectedPetugasChart}
                            onValueChange={setSelectedPetugasChart}
                            placeholder="Pilih petugas untuk chart..."
                            searchPlaceholder="Cari petugas..."
                        />
                    </div>
                    {selectedPetugasChartData ? (
                        <div>
                            <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                Jumlah kegiatan per bulan:{' '}
                                {petugasList.find(
                                    (p) =>
                                        String(p.id) === selectedPetugasChart,
                                )?.nama ?? ''}
                            </p>
                            <ResponsiveContainer width="100%" height={200}>
                                <LineChart data={selectedPetugasChartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" fontSize={12} />
                                    <YAxis
                                        fontSize={12}
                                        allowDecimals={false}
                                    />
                                    <ChartTooltip
                                        content={<GlassTooltipContent />}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="jumlah_kegiatan"
                                        stroke="#8b5cf6"
                                        name="Kegiatan"
                                        strokeWidth={2}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Pilih petugas untuk melihat grafik alokasi bulanan
                        </p>
                    )}
                </div>

                {/* Per-Petugas Alokasi Detail Table */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Detail Alokasi per Petugas (Jan – Des)
                    </h3>
                    <div className="mb-4 flex flex-wrap items-end gap-4">
                        <div className="w-64">
                            <Input
                                placeholder="Cari nama petugas..."
                                value={searchAlokasiDetail}
                                onChange={(e) =>
                                    setSearchAlokasiDetail(e.target.value)
                                }
                                className="h-9"
                            />
                        </div>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            {filteredAlokasiDetail.length} petugas
                        </span>
                    </div>
                    <div className="max-h-96 overflow-auto">
                        <table className="w-full text-sm">
                            <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="py-2 pr-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Nama Petugas
                                    </th>
                                    {monthNames.map((m) => (
                                        <th
                                            key={m}
                                            className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400"
                                        >
                                            {m}
                                        </th>
                                    ))}
                                    <th className="py-2 pl-2 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredAlokasiDetail
                                    .slice(0, 100)
                                    .map((item) => (
                                        <tr
                                            key={item.petugas_id}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-1.5 pr-2 text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </td>
                                            {Array.from(
                                                { length: 12 },
                                                (_, i) => i + 1,
                                            ).map((b) => (
                                                <td
                                                    key={b}
                                                    className={`py-1.5 text-center ${
                                                        item.bulan[b] > 0
                                                            ? 'font-medium text-neutral-900 dark:text-white'
                                                            : 'text-neutral-300 dark:text-neutral-600'
                                                    }`}
                                                >
                                                    {item.bulan[b] || 0}
                                                </td>
                                            ))}
                                            <td className="py-1.5 pl-2 text-center font-bold text-neutral-900 dark:text-white">
                                                {item.total}
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                        {filteredAlokasiDetail.length > 100 && (
                            <p className="py-2 text-center text-xs text-neutral-400">
                                Menampilkan 100 dari{' '}
                                {filteredAlokasiDetail.length} petugas
                            </p>
                        )}
                    </div>
                </div>

                {/* Petugas-Kegiatan Mapping */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Pemetaan Petugas dan Kegiatan
                    </h3>
                    <div className="mb-4 flex flex-wrap gap-3">
                        <Input
                            placeholder="Cari nama petugas..."
                            value={searchPetugas}
                            onChange={(e) => setSearchPetugas(e.target.value)}
                            className="h-9 w-64"
                        />
                        <div className="w-80">
                            <SearchableSelect
                                options={[
                                    {
                                        value: '',
                                        label: 'Semua Kegiatan',
                                    },
                                    ...kegiatanList.map((k) => ({
                                        value: String(k.id),
                                        label: `${k.nama_kegiatan}`,
                                    })),
                                ]}
                                value={kegiatanFilter}
                                onValueChange={setKegiatanFilter}
                                placeholder="Filter kegiatan..."
                                searchPlaceholder="Cari kegiatan..."
                            />
                        </div>
                        <span className="self-center text-xs text-neutral-500 dark:text-neutral-400">
                            {filteredPetugasKegiatan.length} petugas
                        </span>
                    </div>
                    <div className="max-h-96 overflow-y-auto">
                        <table className="w-full text-sm">
                            <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Nama Petugas
                                    </th>
                                    <th className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                        Jml Kegiatan
                                    </th>
                                    <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Kegiatan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredPetugasKegiatan
                                    .slice(0, 50)
                                    .map((item) => (
                                        <tr
                                            key={item.petugas_id}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-1.5 text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </td>
                                            <td className="py-1.5 text-center font-medium text-neutral-900 dark:text-white">
                                                {item.jumlah_kegiatan}
                                            </td>
                                            <td className="py-1.5">
                                                <div className="flex flex-wrap gap-1">
                                                    {item.kegiatan.map((k) => (
                                                        <span
                                                            key={k.id}
                                                            className="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800 dark:bg-blue-900/40 dark:text-blue-300"
                                                        >
                                                            {k.nama}
                                                        </span>
                                                    ))}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                        {filteredPetugasKegiatan.length > 50 && (
                            <p className="py-2 text-center text-xs text-neutral-400">
                                Menampilkan 50 dari{' '}
                                {filteredPetugasKegiatan.length} petugas
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
