import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertCircle, Check, Copy, Download, Users, X } from 'lucide-react';
import { useMemo, useState } from 'react';
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

const KEGIATAN_CHIP_STYLES = [
    'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200',
    'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
    'bg-lime-100 text-lime-800 dark:bg-lime-900/40 dark:text-lime-200',
];

function kegiatanChipStyle(kegiatanId: number): string {
    const index = Math.abs(kegiatanId) % KEGIATAN_CHIP_STYLES.length;

    return KEGIATAN_CHIP_STYLES[index];
}

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

function toNumericAmount(value: unknown): number {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }

    if (typeof value === 'string') {
        const normalized = value.replace(/\./g, '').replace(',', '.').trim();
        const parsed = Number(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    return 0;
}

function formatHonorAxis(value: number): string {
    if (value >= 1_000_000) {
        const inMillions = value / 1_000_000;

        return Number.isInteger(inMillions)
            ? `${inMillions.toFixed(0)}jt`
            : `${inMillions.toFixed(1)}jt`;
    }

    if (value >= 1_000) {
        return `${Math.round(value / 1_000)}rb`;
    }

    return `${Math.round(value)}`;
}

interface PieLegendItem {
    label: string;
    count: number;
    color: string;
    percentage: number;
}

function buildPieLegendItems<T extends { count: number }>(
    data: T[],
    labelResolver: (item: T) => string,
    total: number,
): PieLegendItem[] {
    return data.map((item, index) => ({
        label: labelResolver(item),
        count: item.count,
        color: COLORS[index % COLORS.length],
        percentage: total > 0 ? (item.count / total) * 100 : 0,
    }));
}

function PieLegendList({ items }: { items: PieLegendItem[] }) {
    return (
        <div className="max-h-40 space-y-1.5 overflow-y-auto pr-1">
            {items.map((item) => (
                <div
                    key={`${item.label}-${item.color}`}
                    className="flex items-center justify-between gap-3 rounded-md border border-neutral-200/70 px-2 py-1.5 text-xs dark:border-neutral-700/70"
                >
                    <div className="flex min-w-0 items-center gap-2">
                        <span
                            className="h-2.5 w-2.5 shrink-0 rounded-full"
                            style={{ backgroundColor: item.color }}
                        />
                        <span
                            className="truncate text-neutral-700 dark:text-neutral-300"
                            title={item.label}
                        >
                            {item.label}
                        </span>
                    </div>
                    <span className="shrink-0 font-semibold text-neutral-900 dark:text-white">
                        {item.count} ({item.percentage.toFixed(1)}%)
                    </span>
                </div>
            ))}
        </div>
    );
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

interface DesaKelurahanItem {
    [key: string]: unknown;
    desa_kelurahan: string;
    count: number;
}

interface DistribusiTugasDesaKelurahanItem {
    kecamatan: string;
    desa_kelurahan: string;
    jumlah_petugas: number;
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

interface PetugasBelumDialokasikanItem {
    id: number;
    nama: string;
    kecamatan: string | null;
    jenis_kelamin: string | null;
    telepon: string | null;
}

interface KegiatanRutinItem {
    kegiatan_id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
    jumlah_bulan: number;
    bulan_list: string[];
}

interface PetugasRutinItem {
    petugas_id: number;
    petugas_nama: string;
    jumlah_kegiatan_rutin: number;
    kegiatan_rutin: KegiatanRutinItem[];
}

interface Props {
    distribusiJenisKelamin: DistribusiItem[];
    distribusiKecamatan: KecamatanItem[];
    distribusiDesaKelurahan: DesaKelurahanItem[];
    distribusiTugasDesaKelurahan: DistribusiTugasDesaKelurahanItem[];
    distribusiUsia: DistribusiItem[];
    distribusiPendidikan: PendidikanItem[];
    alokasiPerBulan: AlokasiPerBulan[];
    petugasKegiatan: PetugasKegiatanItem[];
    kegiatanList: KegiatanListItem[];
    petugasAlokasiDetail: PetugasAlokasiDetail[];
    petugasList: PetugasListItem[];
    petugasBelumDialokasikan: PetugasBelumDialokasikanItem[];
    petugasRutin: PetugasRutinItem[];
    totalPetugas: number;
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
                {value} ({(percent * 100).toFixed(1)}%)
            </text>
        </g>
    );
}

export default function AnalisisPetugas({
    distribusiJenisKelamin,
    distribusiKecamatan,
    distribusiDesaKelurahan,
    distribusiTugasDesaKelurahan,
    distribusiUsia,
    distribusiPendidikan,
    alokasiPerBulan,
    petugasKegiatan,
    kegiatanList,
    petugasAlokasiDetail,
    petugasList,
    petugasBelumDialokasikan,
    petugasRutin,
    totalPetugas,
    currentYear,
}: Props) {
    const [activePieJenisKelaminIndex, setActivePieJenisKelaminIndex] =
        useState<number | undefined>(undefined);
    const [activePieUsiaIndex, setActivePieUsiaIndex] = useState<
        number | undefined
    >(undefined);
    const [activePieKecamatanIndex, setActivePieKecamatanIndex] = useState<
        number | undefined
    >(undefined);
    const [activePieDesaIndex, setActivePieDesaIndex] = useState<
        number | undefined
    >(undefined);
    const [activePiePendidikanIndex, setActivePiePendidikanIndex] = useState<
        number | undefined
    >(undefined);

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

    const [searchHonorDetail, setSearchHonorDetail] = useState('');
    const [honorDetailPage, setHonorDetailPage] = useState(1);
    const honorDetailPageSize = 15;
    const [belumDialokasikanPage, setBelumDialokasikanPage] = useState(1);
    const belumDialokasikanPageSize = 10;
    const [distribusiWilayahPage, setDistribusiWilayahPage] = useState(1);
    const distribusiWilayahPageSize = 10;
    const [copiedBelumDialokasikanId, setCopiedBelumDialokasikanId] = useState<
        number | 'all' | null
    >(null);

    const copyBelumDialokasikanRow = (
        item: PetugasBelumDialokasikanItem,
        no: number,
    ) => {
        const text = item.telepon
            ? `${no}. ${item.nama} (${item.telepon})`
            : `${no}. ${item.nama}`;
        navigator.clipboard.writeText(text).then(() => {
            setCopiedBelumDialokasikanId(item.id);
            setTimeout(() => setCopiedBelumDialokasikanId(null), 1500);
        });
    };

    const copyAllBelumDialokasikan = () => {
        const lines = petugasBelumDialokasikan.map((item, idx) =>
            item.telepon
                ? `${idx + 1}. ${item.nama} (${item.telepon})`
                : `${idx + 1}. ${item.nama}`,
        );
        navigator.clipboard.writeText(lines.join('\n')).then(() => {
            setCopiedBelumDialokasikanId('all');
            setTimeout(() => setCopiedBelumDialokasikanId(null), 1500);
        });
    };
    const [searchPetugasRutin, setSearchPetugasRutin] = useState('');
    const [petugasRutinPage, setPetugasRutinPage] = useState(1);
    const petugasRutinPageSize = 10;

    const filteredPetugasRutin = useMemo(() => {
        if (!searchPetugasRutin.trim()) {
            return petugasRutin;
        }
        const q = searchPetugasRutin.toLowerCase();
        return petugasRutin.filter((p) =>
            p.petugas_nama.toLowerCase().includes(q),
        );
    }, [petugasRutin, searchPetugasRutin]);

    const petugasRutinTotalPages = Math.max(
        1,
        Math.ceil(filteredPetugasRutin.length / petugasRutinPageSize),
    );
    const petugasRutinCurrentPage = Math.min(
        petugasRutinPage,
        petugasRutinTotalPages,
    );
    const petugasRutinPageRows = filteredPetugasRutin.slice(
        (petugasRutinCurrentPage - 1) * petugasRutinPageSize,
        petugasRutinCurrentPage * petugasRutinPageSize,
    );

    const belumDialokasikanTotalPages = Math.max(
        1,
        Math.ceil(petugasBelumDialokasikan.length / belumDialokasikanPageSize),
    );
    const belumDialokasikanCurrentPage = Math.min(
        belumDialokasikanPage,
        belumDialokasikanTotalPages,
    );
    const belumDialokasikanPageRows = petugasBelumDialokasikan.slice(
        (belumDialokasikanCurrentPage - 1) * belumDialokasikanPageSize,
        belumDialokasikanCurrentPage * belumDialokasikanPageSize,
    );

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
                row[`kegiatan_${p.petugas_id}`] =
                    toNumericAmount(p.bulan[i + 1]) || 0;
                row[`honor_${p.petugas_id}`] =
                    toNumericAmount(p.honor[i + 1]) || 0;
            });
            return row;
        });
    }, [petugasAlokasiDetail, selectedPetugasIds]);

    const honorAxisConfig = useMemo(() => {
        if (!multiPetugasChartData || selectedPetugasIds.length === 0) {
            return {
                max: 1_000_000,
                ticks: [0, 250_000, 500_000, 750_000, 1_000_000],
            };
        }

        const maxValue = multiPetugasChartData.reduce((max, row) => {
            const rowMax = selectedPetugasIds.reduce((innerMax, id) => {
                const value = toNumericAmount(row[`honor_${id}`]);

                return Math.max(innerMax, value);
            }, 0);

            return Math.max(max, rowMax);
        }, 0);

        const step =
            maxValue <= 2_000_000
                ? 250_000
                : maxValue <= 5_000_000
                  ? 500_000
                  : 1_000_000;

        const axisMax = Math.max(step, Math.ceil(maxValue / step) * step);
        const ticks: number[] = [];

        for (let value = 0; value <= axisMax; value += step) {
            ticks.push(value);
        }

        return {
            max: axisMax,
            ticks,
        };
    }, [multiPetugasChartData, selectedPetugasIds]);

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

    const usiaChartData = useMemo(
        () => distribusiUsia.filter((item) => item.count > 0),
        [distribusiUsia],
    );

    const distribusiJenisKelaminChartData = useMemo(
        () => distribusiJenisKelamin.filter((item) => item.count > 0),
        [distribusiJenisKelamin],
    );

    const totalJenisKelamin = useMemo(
        () =>
            distribusiJenisKelaminChartData.reduce(
                (sum, item) => sum + item.count,
                0,
            ),
        [distribusiJenisKelaminChartData],
    );

    const distribusiJenisKelaminLegendItems = useMemo(
        () =>
            buildPieLegendItems(
                distribusiJenisKelaminChartData,
                (item) => item.label,
                totalJenisKelamin,
            ),
        [distribusiJenisKelaminChartData, totalJenisKelamin],
    );

    const totalUsia = useMemo(
        () => usiaChartData.reduce((sum, item) => sum + item.count, 0),
        [usiaChartData],
    );

    const distribusiUsiaLegendItems = useMemo(
        () =>
            buildPieLegendItems(usiaChartData, (item) => item.label, totalUsia),
        [usiaChartData, totalUsia],
    );

    const distribusiKecamatanChartData = useMemo(() => {
        if (distribusiKecamatan.length <= 8) {
            return distribusiKecamatan;
        }

        const topItems = distribusiKecamatan.slice(0, 7);
        const othersCount = distribusiKecamatan
            .slice(7)
            .reduce((sum, item) => sum + item.count, 0);

        return [
            ...topItems,
            {
                kecamatan: 'Lainnya',
                count: othersCount,
            },
        ];
    }, [distribusiKecamatan]);

    const totalKecamatan = useMemo(
        () => distribusiKecamatan.reduce((sum, item) => sum + item.count, 0),
        [distribusiKecamatan],
    );

    const distribusiKecamatanLegendItems = useMemo(
        () =>
            buildPieLegendItems(
                distribusiKecamatanChartData,
                (item) => item.kecamatan,
                totalKecamatan,
            ),
        [distribusiKecamatanChartData, totalKecamatan],
    );

    const distribusiDesaKelurahanChartData = useMemo(() => {
        if (distribusiDesaKelurahan.length <= 8) {
            return distribusiDesaKelurahan;
        }

        const topItems = distribusiDesaKelurahan.slice(0, 7);
        const othersCount = distribusiDesaKelurahan
            .slice(7)
            .reduce((sum, item) => sum + item.count, 0);

        return [
            ...topItems,
            {
                desa_kelurahan: 'Lainnya',
                count: othersCount,
            },
        ];
    }, [distribusiDesaKelurahan]);

    const totalDesaKelurahan = useMemo(
        () =>
            distribusiDesaKelurahan.reduce((sum, item) => sum + item.count, 0),
        [distribusiDesaKelurahan],
    );

    const distribusiDesaKelurahanLegendItems = useMemo(
        () =>
            buildPieLegendItems(
                distribusiDesaKelurahanChartData,
                (item) => item.desa_kelurahan,
                totalDesaKelurahan,
            ),
        [distribusiDesaKelurahanChartData, totalDesaKelurahan],
    );

    const distribusiPendidikanChartData = useMemo(() => {
        const normalized = distribusiPendidikan.map((item) => ({
            pendidikan: item.pendidikan || 'Belum Diisi',
            count: item.count,
        }));

        if (normalized.length <= 8) {
            return normalized;
        }

        const topItems = normalized.slice(0, 7);
        const othersCount = normalized
            .slice(7)
            .reduce((sum, item) => sum + item.count, 0);

        return [
            ...topItems,
            {
                pendidikan: 'Lainnya',
                count: othersCount,
            },
        ];
    }, [distribusiPendidikan]);

    const totalPendidikan = useMemo(
        () => distribusiPendidikan.reduce((sum, item) => sum + item.count, 0),
        [distribusiPendidikan],
    );

    const distribusiPendidikanLegendItems = useMemo(
        () =>
            buildPieLegendItems(
                distribusiPendidikanChartData,
                (item) => item.pendidikan,
                totalPendidikan,
            ),
        [distribusiPendidikanChartData, totalPendidikan],
    );

    const distribusiWilayahTotalPages = Math.max(
        1,
        Math.ceil(
            distribusiTugasDesaKelurahan.length / distribusiWilayahPageSize,
        ),
    );
    const distribusiWilayahCurrentPage = Math.min(
        distribusiWilayahPage,
        distribusiWilayahTotalPages,
    );
    const distribusiWilayahPageRows = distribusiTugasDesaKelurahan.slice(
        (distribusiWilayahCurrentPage - 1) * distribusiWilayahPageSize,
        distribusiWilayahCurrentPage * distribusiWilayahPageSize,
    );

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

                {/* KPI Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            {
                                label: 'Total Petugas Aktif',
                                value: totalPetugas,
                                sub: 'Petugas non-organik aktif',
                                subColor:
                                    'text-neutral-500 dark:text-neutral-400',
                                barPct: 100,
                                barColor: '#3b82f6',
                                icon: (
                                    <Users className="h-5 w-5 text-blue-500" />
                                ),
                            },
                            {
                                label: 'Sudah Dialokasikan',
                                value:
                                    totalPetugas -
                                    petugasBelumDialokasikan.length,
                                sub: `${totalPetugas > 0 ? Math.round(((totalPetugas - petugasBelumDialokasikan.length) / totalPetugas) * 100) : 0}% dari total petugas`,
                                subColor: 'text-green-600 dark:text-green-400',
                                barPct:
                                    totalPetugas > 0
                                        ? Math.round(
                                              ((totalPetugas -
                                                  petugasBelumDialokasikan.length) /
                                                  totalPetugas) *
                                                  100,
                                          )
                                        : 0,
                                barColor: '#22c55e',
                                icon: (
                                    <Users className="h-5 w-5 text-green-500" />
                                ),
                            },
                            {
                                label: 'Belum Dialokasikan',
                                value: petugasBelumDialokasikan.length,
                                sub: 'Belum ada alokasi kegiatan',
                                subColor:
                                    petugasBelumDialokasikan.length > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-neutral-500 dark:text-neutral-400',
                                barPct:
                                    totalPetugas > 0
                                        ? Math.round(
                                              (petugasBelumDialokasikan.length /
                                                  totalPetugas) *
                                                  100,
                                          )
                                        : 0,
                                barColor: '#f59e0b',
                                icon: (
                                    <AlertCircle className="h-5 w-5 text-amber-500" />
                                ),
                            },
                            {
                                label: 'Total Alokasi Tahun',
                                value: totalAlokasiTahun,
                                sub: 'Kumulatif slot alokasi bulanan',
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

                {/* Charts Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Jenis Kelamin */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Jenis Kelamin
                        </h3>
                        {distribusiJenisKelaminChartData.length > 0 ? (
                            <div className="space-y-4">
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart
                                        style={{ overflow: 'visible' }}
                                        margin={{
                                            top: 10,
                                            right: 70,
                                            bottom: 10,
                                            left: 70,
                                        }}
                                        onClick={() =>
                                            setActivePieJenisKelaminIndex(
                                                undefined,
                                            )
                                        }
                                    >
                                        <Pie
                                            data={
                                                distribusiJenisKelaminChartData
                                            }
                                            dataKey="count"
                                            nameKey="label"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={35}
                                            outerRadius={78}
                                            labelLine={false}
                                            shape={(p: PieSectorShapeProps) =>
                                                renderActivePieShape(
                                                    p,
                                                    activePieJenisKelaminIndex,
                                                )
                                            }
                                            onClick={(_, idx, e) => {
                                                e.stopPropagation();
                                                setActivePieJenisKelaminIndex(
                                                    idx ===
                                                        activePieJenisKelaminIndex
                                                        ? undefined
                                                        : idx,
                                                );
                                            }}
                                            cursor="pointer"
                                            stroke="none"
                                        >
                                            {distribusiJenisKelaminChartData.map(
                                                (_, index) => (
                                                    <Cell
                                                        key={`jenis-kelamin-cell-${index}`}
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
                                    </PieChart>
                                </ResponsiveContainer>
                                <PieLegendList
                                    items={distribusiJenisKelaminLegendItems}
                                />
                            </div>
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
                            <div className="space-y-4">
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart
                                        style={{ overflow: 'visible' }}
                                        margin={{
                                            top: 10,
                                            right: 70,
                                            bottom: 10,
                                            left: 70,
                                        }}
                                        onClick={() =>
                                            setActivePieUsiaIndex(undefined)
                                        }
                                    >
                                        <Pie
                                            data={usiaChartData}
                                            dataKey="count"
                                            nameKey="label"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={35}
                                            outerRadius={78}
                                            labelLine={false}
                                            shape={(p: PieSectorShapeProps) =>
                                                renderActivePieShape(
                                                    p,
                                                    activePieUsiaIndex,
                                                )
                                            }
                                            onClick={(_, idx, e) => {
                                                e.stopPropagation();
                                                setActivePieUsiaIndex(
                                                    idx === activePieUsiaIndex
                                                        ? undefined
                                                        : idx,
                                                );
                                            }}
                                            cursor="pointer"
                                            stroke="none"
                                        >
                                            {usiaChartData.map((_, index) => (
                                                <Cell
                                                    key={`usia-cell-${index}`}
                                                    fill={
                                                        COLORS[
                                                            index %
                                                                COLORS.length
                                                        ]
                                                    }
                                                />
                                            ))}
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <PieLegendList
                                    items={distribusiUsiaLegendItems}
                                />
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data tanggal lahir belum tersedia
                            </p>
                        )}
                    </div>
                </div>

                {/* Kecamatan, Desa/Kelurahan, & Pendidikan */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Kecamatan - Pie Chart */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Kecamatan
                        </h3>
                        {distribusiKecamatan.length > 0 ? (
                            <div className="space-y-4">
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart
                                        style={{ overflow: 'visible' }}
                                        margin={{
                                            top: 10,
                                            right: 70,
                                            bottom: 10,
                                            left: 70,
                                        }}
                                        onClick={() =>
                                            setActivePieKecamatanIndex(
                                                undefined,
                                            )
                                        }
                                    >
                                        <Pie
                                            data={distribusiKecamatanChartData}
                                            dataKey="count"
                                            nameKey="kecamatan"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={35}
                                            outerRadius={78}
                                            labelLine={false}
                                            shape={(p: PieSectorShapeProps) =>
                                                renderActivePieShape(
                                                    p,
                                                    activePieKecamatanIndex,
                                                )
                                            }
                                            onClick={(_, idx, e) => {
                                                e.stopPropagation();
                                                setActivePieKecamatanIndex(
                                                    idx ===
                                                        activePieKecamatanIndex
                                                        ? undefined
                                                        : idx,
                                                );
                                            }}
                                            cursor="pointer"
                                            stroke="none"
                                        >
                                            {distribusiKecamatanChartData.map(
                                                (_, index) => (
                                                    <Cell
                                                        key={`kecamatan-cell-${index}`}
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
                                    </PieChart>
                                </ResponsiveContainer>
                                <PieLegendList
                                    items={distribusiKecamatanLegendItems}
                                />
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data kecamatan belum tersedia
                            </p>
                        )}
                    </div>

                    {/* Desa/Kelurahan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-4 flex items-center justify-between gap-3">
                            <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                Distribusi Desa/Kelurahan
                            </h3>
                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {distribusiDesaKelurahan.length} wilayah
                            </span>
                        </div>
                        {distribusiDesaKelurahanChartData.length > 0 ? (
                            <div className="space-y-4">
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart
                                        style={{ overflow: 'visible' }}
                                        margin={{
                                            top: 10,
                                            right: 70,
                                            bottom: 10,
                                            left: 70,
                                        }}
                                        onClick={() =>
                                            setActivePieDesaIndex(undefined)
                                        }
                                    >
                                        <Pie
                                            data={
                                                distribusiDesaKelurahanChartData
                                            }
                                            dataKey="count"
                                            nameKey="desa_kelurahan"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={35}
                                            outerRadius={78}
                                            labelLine={false}
                                            shape={(p: PieSectorShapeProps) =>
                                                renderActivePieShape(
                                                    p,
                                                    activePieDesaIndex,
                                                )
                                            }
                                            onClick={(_, idx, e) => {
                                                e.stopPropagation();
                                                setActivePieDesaIndex(
                                                    idx === activePieDesaIndex
                                                        ? undefined
                                                        : idx,
                                                );
                                            }}
                                            cursor="pointer"
                                            stroke="none"
                                        >
                                            {distribusiDesaKelurahanChartData.map(
                                                (_, index) => (
                                                    <Cell
                                                        key={`desa-kel-cell-${index}`}
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
                                    </PieChart>
                                </ResponsiveContainer>

                                <PieLegendList
                                    items={distribusiDesaKelurahanLegendItems}
                                />
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data desa/kelurahan belum tersedia
                            </p>
                        )}
                    </div>

                    {/* Pendidikan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Pendidikan
                        </h3>
                        {distribusiPendidikan.length > 0 ? (
                            <div className="space-y-4">
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart
                                        style={{ overflow: 'visible' }}
                                        margin={{
                                            top: 10,
                                            right: 70,
                                            bottom: 10,
                                            left: 70,
                                        }}
                                        onClick={() =>
                                            setActivePiePendidikanIndex(
                                                undefined,
                                            )
                                        }
                                    >
                                        <Pie
                                            data={distribusiPendidikanChartData}
                                            dataKey="count"
                                            nameKey="pendidikan"
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={35}
                                            outerRadius={78}
                                            labelLine={false}
                                            shape={(p: PieSectorShapeProps) =>
                                                renderActivePieShape(
                                                    p,
                                                    activePiePendidikanIndex,
                                                )
                                            }
                                            onClick={(_, idx, e) => {
                                                e.stopPropagation();
                                                setActivePiePendidikanIndex(
                                                    idx ===
                                                        activePiePendidikanIndex
                                                        ? undefined
                                                        : idx,
                                                );
                                            }}
                                            cursor="pointer"
                                            stroke="none"
                                        >
                                            {distribusiPendidikanChartData.map(
                                                (_, index) => (
                                                    <Cell
                                                        key={`pendidikan-cell-${index}`}
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
                                    </PieChart>
                                </ResponsiveContainer>
                                <PieLegendList
                                    items={distribusiPendidikanLegendItems}
                                />
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Data pendidikan belum tersedia
                            </p>
                        )}
                    </div>
                </div>

                {/* Distribusi Petugas per Desa/Kelurahan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Petugas per Desa/Kelurahan
                        </h3>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            {distribusiTugasDesaKelurahan.length} wilayah
                        </span>
                    </div>
                    {distribusiTugasDesaKelurahan.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                No
                                            </th>
                                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Kecamatan
                                            </th>
                                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Desa/Kelurahan
                                            </th>
                                            <th className="py-2 pr-3 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                                Petugas
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {distribusiWilayahPageRows.map(
                                            (item, index) => {
                                                const rowNo =
                                                    (distribusiWilayahCurrentPage -
                                                        1) *
                                                        distribusiWilayahPageSize +
                                                    index +
                                                    1;

                                                return (
                                                    <tr
                                                        key={`${item.desa_kelurahan}-${index}`}
                                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                                    >
                                                        <td className="py-1.5 pr-3 text-neutral-500 dark:text-neutral-400">
                                                            {rowNo}
                                                        </td>
                                                        <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                            {item.kecamatan}
                                                        </td>
                                                        <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                            {
                                                                item.desa_kelurahan
                                                            }
                                                        </td>
                                                        <td className="py-1.5 pr-3 text-center font-semibold text-sky-600 dark:text-sky-400">
                                                            {
                                                                item.jumlah_petugas
                                                            }
                                                        </td>
                                                    </tr>
                                                );
                                            },
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            {distribusiWilayahTotalPages > 1 && (
                                <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                    <span className="text-neutral-500 dark:text-neutral-400">
                                        Halaman {distribusiWilayahCurrentPage}{' '}
                                        dari {distribusiWilayahTotalPages}{' '}
                                        &middot;{' '}
                                        {distribusiTugasDesaKelurahan.length}{' '}
                                        wilayah
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                distribusiWilayahCurrentPage <=
                                                1
                                            }
                                            onClick={() =>
                                                setDistribusiWilayahPage((p) =>
                                                    Math.max(p - 1, 1),
                                                )
                                            }
                                        >
                                            Sebelumnya
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                distribusiWilayahCurrentPage >=
                                                distribusiWilayahTotalPages
                                            }
                                            onClick={() =>
                                                setDistribusiWilayahPage((p) =>
                                                    Math.min(
                                                        p + 1,
                                                        distribusiWilayahTotalPages,
                                                    ),
                                                )
                                            }
                                        >
                                            Berikutnya
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Data distribusi petugas desa/kelurahan belum
                            tersedia
                        </p>
                    )}
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
                            <LineChart
                                data={alokasiChartData}
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
                                    content={<GlassTooltipContent />}
                                />
                                <Legend wrapperStyle={{ fontSize: '11px' }} />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_petugas"
                                    stroke="#3b82f6"
                                    name="Jumlah Petugas"
                                    strokeWidth={2}
                                    dot={false}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_kegiatan"
                                    stroke="#22c55e"
                                    name="Jumlah Kegiatan"
                                    strokeWidth={2}
                                    dot={false}
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

                {/* Petugas Belum Pernah Dialokasikan */}
                {petugasBelumDialokasikan.length > 0 && (
                    <div className="rounded-2xl border border-amber-200/60 bg-amber-50/50 p-5 shadow-2xl backdrop-blur-2xl dark:border-amber-700/30 dark:bg-amber-900/10">
                        <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                    Petugas Belum Pernah Dialokasikan
                                </h3>
                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    {petugasBelumDialokasikan.length} petugas
                                    aktif belum pernah mendapat alokasi kegiatan
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="gap-1.5 text-xs"
                                onClick={copyAllBelumDialokasikan}
                            >
                                {copiedBelumDialokasikanId === 'all' ? (
                                    <Check className="h-3.5 w-3.5 text-green-500" />
                                ) : (
                                    <Copy className="h-3.5 w-3.5" />
                                )}
                                {copiedBelumDialokasikanId === 'all'
                                    ? 'Tersalin!'
                                    : 'Salin Semua'}
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            No
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Nama
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Jenis Kelamin
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            No HP
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Kecamatan
                                        </th>
                                        <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {belumDialokasikanPageRows.map(
                                        (item, idx) => {
                                            const rowNo =
                                                (belumDialokasikanCurrentPage -
                                                    1) *
                                                    belumDialokasikanPageSize +
                                                idx +
                                                1;
                                            return (
                                                <tr
                                                    key={item.id}
                                                    className="border-b border-neutral-100 dark:border-neutral-700/50"
                                                >
                                                    <td className="py-1.5 pr-3 text-neutral-500 dark:text-neutral-400">
                                                        {rowNo}
                                                    </td>
                                                    <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                        {item.nama}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                                        {item.jenis_kelamin ??
                                                            '—'}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                                        {item.telepon ?? '—'}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                                        {item.kecamatan ?? '—'}
                                                    </td>
                                                    <td className="py-1.5 text-right">
                                                        <button
                                                            type="button"
                                                            title="Salin baris ini"
                                                            onClick={() =>
                                                                copyBelumDialokasikanRow(
                                                                    item,
                                                                    rowNo,
                                                                )
                                                            }
                                                            className="rounded p-1 text-neutral-400 transition hover:text-neutral-700 dark:hover:text-neutral-200"
                                                        >
                                                            {copiedBelumDialokasikanId ===
                                                            item.id ? (
                                                                <Check className="h-3.5 w-3.5 text-green-500" />
                                                            ) : (
                                                                <Copy className="h-3.5 w-3.5" />
                                                            )}
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {belumDialokasikanTotalPages > 1 && (
                            <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                <span className="text-neutral-500 dark:text-neutral-400">
                                    Halaman {belumDialokasikanCurrentPage} dari{' '}
                                    {belumDialokasikanTotalPages} &middot;{' '}
                                    {petugasBelumDialokasikan.length} petugas
                                </span>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            belumDialokasikanCurrentPage <= 1
                                        }
                                        onClick={() =>
                                            setBelumDialokasikanPage((p) =>
                                                Math.max(p - 1, 1),
                                            )
                                        }
                                    >
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            belumDialokasikanCurrentPage >=
                                            belumDialokasikanTotalPages
                                        }
                                        onClick={() =>
                                            setBelumDialokasikanPage((p) =>
                                                Math.min(
                                                    p + 1,
                                                    belumDialokasikanTotalPages,
                                                ),
                                            )
                                        }
                                    >
                                        Berikutnya
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Petugas dengan Kegiatan Rutin */}
                {petugasRutin.length > 0 && (
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-4 flex flex-wrap items-end gap-4">
                            <div className="flex-1">
                                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                    Petugas dengan Kegiatan Rutin
                                </h3>
                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    Petugas yang mengikuti kegiatan yang sama di
                                    minimal 2 bulan berbeda ·{' '}
                                    {filteredPetugasRutin.length} petugas
                                </p>
                            </div>
                            <div className="w-64">
                                <Input
                                    placeholder="Cari nama petugas..."
                                    value={searchPetugasRutin}
                                    onChange={(e) => {
                                        setSearchPetugasRutin(e.target.value);
                                        setPetugasRutinPage(1);
                                    }}
                                    className="h-9"
                                />
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Nama Petugas
                                        </th>
                                        <th className="py-2 pr-3 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                            Jml Rutin
                                        </th>
                                        <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Kegiatan Rutin (jumlah bulan)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {petugasRutinPageRows.map((item) => (
                                        <tr
                                            key={item.petugas_id}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-2 pr-3 font-medium text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </td>
                                            <td className="py-2 pr-3 text-center font-bold text-neutral-900 dark:text-white">
                                                {item.jumlah_kegiatan_rutin}
                                            </td>
                                            <td className="py-2">
                                                <div className="flex flex-wrap gap-1.5">
                                                    {item.kegiatan_rutin.map(
                                                        (k) => (
                                                            <span
                                                                key={
                                                                    k.kegiatan_id
                                                                }
                                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ${kegiatanChipStyle(k.kegiatan_id)}`}
                                                            >
                                                                {
                                                                    k.nama_kegiatan
                                                                }
                                                                <span className="rounded-full bg-black/10 px-1 py-px font-semibold dark:bg-white/15">
                                                                    {
                                                                        k.jumlah_bulan
                                                                    }
                                                                    ×
                                                                </span>
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {petugasRutinTotalPages > 1 && (
                            <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                <span className="text-neutral-500 dark:text-neutral-400">
                                    Halaman {petugasRutinCurrentPage} dari{' '}
                                    {petugasRutinTotalPages} &middot;{' '}
                                    {filteredPetugasRutin.length} petugas
                                </span>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={petugasRutinCurrentPage <= 1}
                                        onClick={() =>
                                            setPetugasRutinPage((p) =>
                                                Math.max(p - 1, 1),
                                            )
                                        }
                                    >
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            petugasRutinCurrentPage >=
                                            petugasRutinTotalPages
                                        }
                                        onClick={() =>
                                            setPetugasRutinPage((p) =>
                                                Math.min(
                                                    p + 1,
                                                    petugasRutinTotalPages,
                                                ),
                                            )
                                        }
                                    >
                                        Berikutnya
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

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
                                    <LineChart
                                        data={multiPetugasChartData}
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
                                            content={<GlassTooltipContent />}
                                        />
                                        <Legend
                                            wrapperStyle={{ fontSize: '11px' }}
                                        />
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
                                                    dot={false}
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
                                    <LineChart
                                        data={multiPetugasChartData}
                                        margin={{
                                            top: 0,
                                            right: 0,
                                            left: 10,
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
                                            domain={[0, honorAxisConfig.max]}
                                            ticks={honorAxisConfig.ticks}
                                            allowDecimals={false}
                                            tickFormatter={formatHonorAxis}
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
                                        <Legend
                                            wrapperStyle={{ fontSize: '11px' }}
                                        />
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
                                                    dot={false}
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
                {/* Honor Per Bulan Per Petugas */}
                {(() => {
                    const filteredHonor = searchHonorDetail.trim()
                        ? petugasAlokasiDetail.filter((p) =>
                              p.petugas_nama
                                  .toLowerCase()
                                  .includes(searchHonorDetail.toLowerCase()),
                          )
                        : petugasAlokasiDetail;
                    const sortedHonor = [...filteredHonor].sort(
                        (a, b) => b.total_honor - a.total_honor,
                    );
                    const totalPages = Math.max(
                        1,
                        Math.ceil(sortedHonor.length / honorDetailPageSize),
                    );
                    const currentPage = Math.min(honorDetailPage, totalPages);
                    const pageRows = sortedHonor.slice(
                        (currentPage - 1) * honorDetailPageSize,
                        currentPage * honorDetailPageSize,
                    );
                    const grandTotal = petugasAlokasiDetail.reduce(
                        (s, p) => s + p.total_honor,
                        0,
                    );
                    return (
                        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                            <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                                Honor per Bulan per Petugas (Jan – Des)
                            </h3>
                            <div className="mb-4 flex flex-wrap items-end gap-4">
                                <div className="w-64">
                                    <Input
                                        placeholder="Cari nama petugas..."
                                        value={searchHonorDetail}
                                        onChange={(e) => {
                                            setSearchHonorDetail(
                                                e.target.value,
                                            );
                                            setHonorDetailPage(1);
                                        }}
                                        className="h-9"
                                    />
                                </div>
                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {sortedHonor.length} petugas
                                </span>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="py-2 pr-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Nama Petugas
                                            </th>
                                            {monthNames.map((m) => (
                                                <th
                                                    key={m}
                                                    className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400"
                                                >
                                                    {m}
                                                </th>
                                            ))}
                                            <th className="py-2 pl-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pageRows.map((item) => (
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
                                                        className={`py-1.5 text-right tabular-nums ${
                                                            item.honor[b] > 0
                                                                ? 'font-medium text-neutral-900 dark:text-white'
                                                                : 'text-neutral-300 dark:text-neutral-600'
                                                        }`}
                                                    >
                                                        {item.honor[b] > 0
                                                            ? formatRupiah(
                                                                  item.honor[b],
                                                              )
                                                            : '—'}
                                                    </td>
                                                ))}
                                                <td className="py-1.5 pl-2 text-right font-bold text-neutral-900 tabular-nums dark:text-white">
                                                    {formatRupiah(
                                                        item.total_honor,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 border-neutral-300 dark:border-neutral-600">
                                            <td
                                                colSpan={13}
                                                className="py-2 pr-2 font-bold text-neutral-900 dark:text-white"
                                            >
                                                Total
                                            </td>
                                            <td className="py-2 pl-2 text-right font-bold text-neutral-900 tabular-nums dark:text-white">
                                                {formatRupiah(grandTotal)}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            {totalPages > 1 && (
                                <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                    <span className="text-neutral-500 dark:text-neutral-400">
                                        Halaman {currentPage} dari {totalPages}{' '}
                                        &middot; {sortedHonor.length} petugas
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={currentPage <= 1}
                                            onClick={() =>
                                                setHonorDetailPage((p) =>
                                                    Math.max(p - 1, 1),
                                                )
                                            }
                                        >
                                            Sebelumnya
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={currentPage >= totalPages}
                                            onClick={() =>
                                                setHonorDetailPage((p) =>
                                                    Math.min(p + 1, totalPages),
                                                )
                                            }
                                        >
                                            Berikutnya
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })()}

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
                                                            className={`inline-block rounded-full px-2 py-0.5 text-xs ${kegiatanChipStyle(k.id)}`}
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
