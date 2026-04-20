import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Download, X } from 'lucide-react';
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
            {payload.map((entry, i) => {
                const pct =
                    typeof entry.percent === 'number'
                        ? (entry.percent * 100).toFixed(1)
                        : null;
                return (
                    <p
                        key={i}
                        className="text-xs text-neutral-600 dark:text-neutral-400"
                    >
                        <span style={{ color: entry.color }}>●</span>{' '}
                        {entry.name}: {entry.value}
                        {pct !== null && ` (${pct}%)`}
                    </p>
                );
            })}
        </div>
    );
}

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

interface DistribusiItem {
    [key: string]: unknown;
    label: string;
    value?: string;
    count: number;
}

interface KecamatanItem {
    [key: string]: unknown;
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
    honor: Record<number, number>;
    total: number;
    total_honor: number;
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
    const [selectedPetugasIds, setSelectedPetugasIds] = useState<string[]>([]);
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

    const multiPetugasChartData = useMemo(() => {
        if (selectedPetugasIds.length === 0) {
            return null;
        }
        const selected = petugasAlokasiDetail.filter((p) =>
            selectedPetugasIds.includes(String(p.petugas_id)),
        );
        if (selected.length === 0) {
            return null;
        }
        return monthNames.map((name, i) => {
            const row: Record<string, number | string> = { name };
            selected.forEach((p) => {
                row[`kegiatan_${p.petugas_id}`] = p.bulan[i + 1] || 0;
                row[`honor_${p.petugas_id}`] = p.honor[i + 1] || 0;
            });
            return row;
        });
    }, [petugasAlokasiDetail, selectedPetugasIds]);

    const addPetugas = (id: string) => {
        if (
            id &&
            !selectedPetugasIds.includes(id) &&
            selectedPetugasIds.length < 5
        ) {
            setSelectedPetugasIds((prev) => [...prev, id]);
        }
    };

    const removePetugas = (id: string) => {
        setSelectedPetugasIds((prev) => prev.filter((p) => p !== id));
    };

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
                <div className="flex items-start justify-between rounded-2xl border border-neutral-200/70 bg-white/80 p-6 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80">
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-white">
                            Analisis Petugas Non-Organik
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Tahun {currentYear} · Total petugas non-organik
                            aktif:{' '}
                            <span className="font-semibold text-neutral-900 dark:text-white">
                                {totalPetugas}
                            </span>
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            window.open(
                                '/analisis/petugas/export-pdf',
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
                                        label={(
                                            // eslint-disable-next-line @typescript-eslint/no-explicit-any
                                            props: any,
                                        ) => `${props.label}: ${props.count}`}
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
                                        content={({ active, payload }) => {
                                            if (!active || !payload?.length)
                                                return null;
                                            const entry = payload[0];
                                            const total =
                                                distribusiJenisKelamin.reduce(
                                                    (s, d) => s + d.count,
                                                    0,
                                                );
                                            const pct =
                                                total > 0
                                                    ? (Number(entry.value) /
                                                          total) *
                                                      100
                                                    : 0;
                                            const pctLabel = Number.isInteger(
                                                pct,
                                            )
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
                                                        {entry.value} (
                                                        {pctLabel}%)
                                                    </p>
                                                </div>
                                            );
                                        }}
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
                    {/* Kecamatan - Pie Chart */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Kecamatan
                        </h3>
                        {distribusiKecamatan.length > 0 ? (
                            <ResponsiveContainer width="100%" height={250}>
                                <PieChart>
                                    <Pie
                                        data={distribusiKecamatan}
                                        dataKey="count"
                                        nameKey="kecamatan"
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={80}
                                        label={(
                                            // eslint-disable-next-line @typescript-eslint/no-explicit-any
                                            props: any,
                                        ) =>
                                            `${props.kecamatan}: ${props.count}`
                                        }
                                    >
                                        {distribusiKecamatan.map((_, index) => (
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
                                                distribusiKecamatan.reduce(
                                                    (s, d) => s + d.count,
                                                    0,
                                                );
                                            const pct =
                                                total > 0
                                                    ? (Number(entry.value) /
                                                          total) *
                                                      100
                                                    : 0;
                                            const pctLabel = Number.isInteger(
                                                pct,
                                            )
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
                                                        {entry.value} (
                                                        {pctLabel}%)
                                                    </p>
                                                </div>
                                            );
                                        }}
                                    />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
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
                            Alokasi Petugas per Bulan
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
                                    <th className="min-w-[120px] py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Metrik
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
                                            label: 'Jumlah Petugas',
                                            key: 'jumlah_petugas' as const,
                                            color: 'text-blue-600 dark:text-blue-400',
                                        },
                                        {
                                            label: 'Jumlah Kegiatan',
                                            key: 'jumlah_kegiatan' as const,
                                            color: 'text-green-600 dark:text-green-400',
                                        },
                                    ] as const
                                ).map((row) => (
                                    <tr
                                        key={row.key}
                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                    >
                                        <td
                                            className={`py-1.5 font-medium ${row.color}`}
                                        >
                                            {row.label}
                                        </td>
                                        {alokasiPerBulan.map((item) => (
                                            <td
                                                key={item.bulan}
                                                className={`py-1.5 text-center font-medium ${row.color}`}
                                            >
                                                {item[row.key]}
                                            </td>
                                        ))}
                                        <td
                                            className={`py-1.5 text-center font-bold ${row.color}`}
                                        >
                                            {alokasiPerBulan.reduce(
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

                {/* Per-Petugas Chart - Dynamic Multi-select */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Grafik Alokasi per Petugas
                    </h3>
                    <div className="mb-4 flex flex-wrap items-end gap-3">
                        <div className="w-72">
                            <SearchableSelect
                                options={petugasList
                                    .filter(
                                        (p) =>
                                            !selectedPetugasIds.includes(
                                                String(p.id),
                                            ),
                                    )
                                    .map((p) => ({
                                        value: String(p.id),
                                        label: p.nama,
                                    }))}
                                value=""
                                onValueChange={addPetugas}
                                placeholder={
                                    selectedPetugasIds.length >= 5
                                        ? 'Maks 5 petugas'
                                        : 'Tambah petugas...'
                                }
                                searchPlaceholder="Cari petugas..."
                            />
                        </div>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            {selectedPetugasIds.length}/5 petugas dipilih
                        </span>
                    </div>
                    {selectedPetugasIds.length > 0 && (
                        <div className="mb-4 flex flex-wrap gap-2">
                            {selectedPetugasIds.map((id, idx) => {
                                const p = petugasList.find(
                                    (pt) => String(pt.id) === id,
                                );
                                return (
                                    <span
                                        key={id}
                                        className="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium text-white"
                                        style={{
                                            backgroundColor:
                                                COLORS[idx % COLORS.length],
                                        }}
                                    >
                                        {p?.nama ?? id}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="h-4 w-4 p-0 text-white hover:bg-white/20"
                                            onClick={() => removePetugas(id)}
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </span>
                                );
                            })}
                        </div>
                    )}
                    {multiPetugasChartData ? (
                        <div className="space-y-4">
                            <div>
                                <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Jumlah Kegiatan per Bulan
                                </p>
                                <ResponsiveContainer width="100%" height={250}>
                                    <LineChart data={multiPetugasChartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" fontSize={12} />
                                        <YAxis
                                            fontSize={12}
                                            allowDecimals={false}
                                        />
                                        <ChartTooltip
                                            content={<GlassTooltipContent />}
                                        />
                                        <Legend />
                                        {selectedPetugasIds.map((id, idx) => {
                                            const p = petugasList.find(
                                                (pt) => String(pt.id) === id,
                                            );
                                            return (
                                                <Line
                                                    key={id}
                                                    type="monotone"
                                                    dataKey={`kegiatan_${id}`}
                                                    stroke={
                                                        COLORS[
                                                            idx % COLORS.length
                                                        ]
                                                    }
                                                    name={
                                                        p?.nama ??
                                                        `Petugas ${id}`
                                                    }
                                                    strokeWidth={2}
                                                />
                                            );
                                        })}
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                            <div>
                                <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Total Honor per Bulan
                                </p>
                                <ResponsiveContainer width="100%" height={250}>
                                    <LineChart data={multiPetugasChartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" fontSize={12} />
                                        <YAxis
                                            fontSize={12}
                                            tickFormatter={(v) =>
                                                `${(v / 1_000_000).toFixed(0)}jt`
                                            }
                                        />
                                        <ChartTooltip
                                            content={({
                                                active,
                                                payload,
                                                label,
                                            }) => {
                                                if (
                                                    !active ||
                                                    !payload ||
                                                    payload.length === 0
                                                ) {
                                                    return null;
                                                }
                                                return (
                                                    <div
                                                        className={
                                                            glassTooltipClass
                                                        }
                                                    >
                                                        <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                                                            {label}
                                                        </p>
                                                        {payload.map(
                                                            (entry, i) => (
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
                                                                    {entry.name}
                                                                    :{' '}
                                                                    {formatRupiah(
                                                                        entry.value as number,
                                                                    )}
                                                                </p>
                                                            ),
                                                        )}
                                                    </div>
                                                );
                                            }}
                                        />
                                        <Legend />
                                        {selectedPetugasIds.map((id, idx) => {
                                            const p = petugasList.find(
                                                (pt) => String(pt.id) === id,
                                            );
                                            return (
                                                <Line
                                                    key={id}
                                                    type="monotone"
                                                    dataKey={`honor_${id}`}
                                                    stroke={
                                                        COLORS[
                                                            idx % COLORS.length
                                                        ]
                                                    }
                                                    name={
                                                        p?.nama ??
                                                        `Petugas ${id}`
                                                    }
                                                    strokeWidth={2}
                                                />
                                            );
                                        })}
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
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
