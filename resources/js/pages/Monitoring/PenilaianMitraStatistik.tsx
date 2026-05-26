import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface ReviewRow {
    id: number;
    rating: number;
    ulasan: string | null;
    reviewed_at: string | null;
    reviewed_month: string;
    petugas_id: number;
    petugas_nama: string;
    kegiatan_id: number;
    kegiatan_kode: string;
    kegiatan_nama: string;
    periode_bulan: string;
    reviewer_name: string;
}

interface KegiatanOption {
    value: string;
    label: string;
}

type MonthlyBestMitraRow = {
    month: string;
    petugas_id: number;
    petugas_nama: string;
    review_count: number;
    avg_rating: number;
    balanced_score: number;
};

type HallOfFameTableRow = {
    petugas_id: number;
    petugas_nama: string;
    kegiatan_count: number;
    review_count: number;
    avg_review_per_kegiatan: number;
    avg_rating: number;
    balanced_score: number;
};

interface Props {
    active_year: number;
    generated_at: string;
    filters: {
        bulan: string;
        kegiatan_id: string;
        petugas_id: string;
    };
    show_kegiatan_terbanyak: boolean;
    show_mitra_top_bottom: boolean;
    show_kegiatan_rank_for_petugas: boolean;
    hall_of_fame: {
        petugas_id: number;
        petugas_nama: string;
        avg_rating: number;
        review_count: number;
        kegiatan_count: number;
        balanced_score: number;
    } | null;
    hall_of_fame_table: HallOfFameTableRow[];
    summary: {
        total_reviews: number;
        avg_rating: number;
        petugas_reviewed: number;
        kegiatan_reviewed: number;
        reviews_with_ulasan: number;
    };
    rating_distribution: Array<{
        rating: number;
        jumlah: number;
    }>;
    monthly_trend: Array<{
        month: string;
        jumlah_review: number;
        rata_rating: number;
    }>;
    top_petugas: Array<{
        petugas_id: number;
        petugas_nama: string;
        balanced_score: number;
        review_count: number;
        avg_rating: number;
        kegiatan_count: number;
    }>;
    bottom_petugas: Array<{
        petugas_id: number;
        petugas_nama: string;
        balanced_score: number;
        review_count: number;
        avg_rating: number;
        kegiatan_count: number;
    }>;
    kegiatan_stats: Array<{
        kegiatan_id: number;
        kegiatan_kode: string;
        kegiatan_nama: string;
        review_count: number;
        avg_rating: number;
        petugas_count: number;
    }>;
    kegiatan_options: KegiatanOption[];
    petugas_options: Array<{
        value: string;
        label: string;
    }>;
    top_kegiatan_for_petugas: Array<{
        kegiatan_id: number;
        kegiatan_nama: string;
        avg_rating: number;
        review_count: number;
    }>;
    bottom_kegiatan_for_petugas: Array<{
        kegiatan_id: number;
        kegiatan_nama: string;
        avg_rating: number;
        review_count: number;
    }>;
    review_rows: {
        encrypted: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Monitoring', href: '#' },
    {
        title: 'Penilaian Mitra Statistik',
        href: '/monitoring-penilaian-mitra',
    },
];

const MONTH_OPTIONS = [
    { value: 'all', label: 'Semua Bulan' },
    { value: '01', label: 'Januari' },
    { value: '02', label: 'Februari' },
    { value: '03', label: 'Maret' },
    { value: '04', label: 'April' },
    { value: '05', label: 'Mei' },
    { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' },
    { value: '08', label: 'Agustus' },
    { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

export default function PenilaianMitraStatistik({
    active_year,
    generated_at,
    filters,
    show_kegiatan_terbanyak,
    show_mitra_top_bottom,
    show_kegiatan_rank_for_petugas,
    hall_of_fame,
    hall_of_fame_table,
    summary,
    rating_distribution,
    monthly_trend,
    top_petugas,
    bottom_petugas,
    kegiatan_stats,
    kegiatan_options,
    petugas_options,
    top_kegiatan_for_petugas,
    bottom_kegiatan_for_petugas,
    review_rows,
}: Props) {
    const reviewRows = useDecryptedData<ReviewRow>(review_rows.encrypted);
    const [minimumRating, setMinimumRating] = useState('all');
    const [currentPage, setCurrentPage] = useState(1);
    const [sortConfig, setSortConfig] = useState<{
        key: 'petugas_nama' | 'kegiatan_nama' | 'rating' | 'reviewed_at';
        direction: 'asc' | 'desc';
    }>({
        key: 'reviewed_at',
        direction: 'desc',
    });
    const [mitraTablePage, setMitraTablePage] = useState(1);
    const [mitraTableSort, setMitraTableSort] = useState<{
        key: keyof HallOfFameTableRow;
        direction: 'asc' | 'desc';
    }>({
        key: 'balanced_score',
        direction: 'desc',
    });
    const pageSize = 10;

    const bestMitraPerMonth = useMemo<MonthlyBestMitraRow[]>(() => {
        if (reviewRows.length === 0) {
            return [];
        }

        const globalAverageRating =
            reviewRows.reduce((sum, row) => sum + row.rating, 0) /
            reviewRows.length;

        const monthGroups = new Map<string, ReviewRow[]>();

        reviewRows.forEach((row) => {
            const rows = monthGroups.get(row.reviewed_month) ?? [];
            rows.push(row);
            monthGroups.set(row.reviewed_month, rows);
        });

        return Array.from(monthGroups.entries())
            .map(([month, monthRows]) => {
                const petugasGroups = new Map<number, ReviewRow[]>();

                monthRows.forEach((row) => {
                    const petugasRows = petugasGroups.get(row.petugas_id) ?? [];
                    petugasRows.push(row);
                    petugasGroups.set(row.petugas_id, petugasRows);
                });

                const bestMitra = Array.from(petugasGroups.entries())
                    .map(([petugasId, petugasRows]) => {
                        const reviewCount = petugasRows.length;
                        const avgRating =
                            petugasRows.reduce(
                                (sum, row) => sum + row.rating,
                                0,
                            ) / reviewCount;
                        const confidence = Math.min(1, reviewCount / 5);
                        const balancedScore =
                            (avgRating * 0.7 + globalAverageRating * 0.3) *
                            (0.6 + 0.4 * confidence);

                        return {
                            petugas_id: petugasId,
                            petugas_nama: petugasRows[0]?.petugas_nama ?? '-',
                            review_count: reviewCount,
                            avg_rating: Number(avgRating.toFixed(2)),
                            balanced_score: Number(balancedScore.toFixed(3)),
                        };
                    })
                    .sort((a, b) => {
                        if (b.balanced_score !== a.balanced_score) {
                            return b.balanced_score - a.balanced_score;
                        }

                        return b.review_count - a.review_count;
                    })[0];

                return bestMitra
                    ? {
                          month,
                          ...bestMitra,
                      }
                    : null;
            })
            .filter((item): item is MonthlyBestMitraRow => item !== null)
            .sort((a, b) => a.month.localeCompare(b.month));
    }, [reviewRows]);

    const handleFilterChange = (
        bulan: string,
        kegiatanId: string,
        petugasId: string,
    ) => {
        router.post(
            '/monitoring-penilaian-mitra',
            {
                bulan,
                kegiatan_id: kegiatanId,
                petugas_id: petugasId,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    const filteredRows = useMemo(() => {
        return reviewRows.filter((row) => {
            const matchesRating =
                minimumRating === 'all'
                    ? true
                    : minimumRating === 'lt2'
                      ? row.rating < 2
                      : row.rating >= Number(minimumRating);

            return matchesRating;
        });
    }, [minimumRating, reviewRows]);

    const sortedRows = useMemo(() => {
        const rowsCopy = [...filteredRows];

        rowsCopy.sort((a, b) => {
            const directionMultiplier = sortConfig.direction === 'asc' ? 1 : -1;

            if (sortConfig.key === 'rating') {
                return (a.rating - b.rating) * directionMultiplier;
            }

            if (sortConfig.key === 'reviewed_at') {
                const aTime = a.reviewed_at
                    ? new Date(a.reviewed_at).getTime()
                    : 0;
                const bTime = b.reviewed_at
                    ? new Date(b.reviewed_at).getTime()
                    : 0;

                return (aTime - bTime) * directionMultiplier;
            }

            const aValue = (a[sortConfig.key] ?? '').toString().toLowerCase();
            const bValue = (b[sortConfig.key] ?? '').toString().toLowerCase();

            return aValue.localeCompare(bValue) * directionMultiplier;
        });

        return rowsCopy;
    }, [filteredRows, sortConfig]);

    const totalPages = Math.max(1, Math.ceil(sortedRows.length / pageSize));
    const safeCurrentPage = Math.min(currentPage, totalPages);

    const paginatedRows = useMemo(() => {
        const startIndex = (safeCurrentPage - 1) * pageSize;
        return sortedRows.slice(startIndex, startIndex + pageSize);
    }, [sortedRows, safeCurrentPage]);

    const handleSort = (
        key: 'petugas_nama' | 'kegiatan_nama' | 'rating' | 'reviewed_at',
    ) => {
        setSortConfig((prev) => {
            if (prev.key === key) {
                return {
                    key,
                    direction: prev.direction === 'asc' ? 'desc' : 'asc',
                };
            }

            return {
                key,
                direction: 'asc',
            };
        });
    };

    const getSortLabel = (
        key: 'petugas_nama' | 'kegiatan_nama' | 'rating' | 'reviewed_at',
    ) => {
        if (sortConfig.key !== key) {
            return '';
        }

        return sortConfig.direction === 'asc' ? ' (A-Z)' : ' (Z-A)';
    };

    const mitraRankLimit = filters.kegiatan_id === 'all' ? 5 : 3;

    const sortedMitraTableRows = useMemo(() => {
        const rowsCopy = [...hall_of_fame_table];

        rowsCopy.sort((a, b) => {
            const directionMultiplier =
                mitraTableSort.direction === 'asc' ? 1 : -1;

            if (mitraTableSort.key === 'petugas_nama') {
                return (
                    a.petugas_nama.localeCompare(b.petugas_nama) *
                    directionMultiplier
                );
            }

            const aValue = Number(a[mitraTableSort.key] ?? 0);
            const bValue = Number(b[mitraTableSort.key] ?? 0);

            return (aValue - bValue) * directionMultiplier;
        });

        return rowsCopy;
    }, [hall_of_fame_table, mitraTableSort]);

    const mitraTableTotalPages = Math.max(
        1,
        Math.ceil(sortedMitraTableRows.length / pageSize),
    );
    const safeMitraTablePage = Math.min(mitraTablePage, mitraTableTotalPages);

    const paginatedMitraTableRows = useMemo(() => {
        const startIndex = (safeMitraTablePage - 1) * pageSize;

        return sortedMitraTableRows.slice(startIndex, startIndex + pageSize);
    }, [sortedMitraTableRows, safeMitraTablePage]);

    const handleMitraTableSort = (key: keyof HallOfFameTableRow) => {
        setMitraTableSort((prev) => {
            if (prev.key === key) {
                return {
                    key,
                    direction: prev.direction === 'asc' ? 'desc' : 'asc',
                };
            }

            return {
                key,
                direction: key === 'petugas_nama' ? 'asc' : 'desc',
            };
        });
        setMitraTablePage(1);
    };

    const getMitraSortLabel = (key: keyof HallOfFameTableRow) => {
        if (mitraTableSort.key !== key) {
            return '';
        }

        return mitraTableSort.direction === 'asc' ? ' (Asc)' : ' (Desc)';
    };

    const getRatingBadgeClass = (rating: number) => {
        const ratingBucket = Math.max(1, Math.min(5, Math.round(rating)));

        if (ratingBucket === 1) {
            return 'border-transparent bg-rose-600 text-white hover:bg-rose-600';
        }

        if (ratingBucket === 2) {
            return 'border-transparent bg-rose-400 text-white hover:bg-rose-400';
        }

        if (ratingBucket === 3) {
            return 'border-transparent bg-amber-200 text-amber-900 hover:bg-amber-200';
        }

        if (ratingBucket === 4) {
            return 'border-transparent bg-emerald-400 text-emerald-950 hover:bg-emerald-400';
        }

        return 'border-transparent bg-emerald-700 text-white hover:bg-emerald-700';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penilaian Mitra Statistik" />

            <div className="space-y-6">
                <PageHeader
                    title="Penilaian Mitra Statistik"
                    description={`Analitik performa mitra berdasarkan Penilaian Mitra Statistik tahun ${active_year}`}
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <ContentCard>
                        <p className="text-xs text-neutral-500">
                            Total Penilaian
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {summary.total_reviews}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs text-neutral-500">
                            Rata-rata Rating
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {summary.avg_rating}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs text-neutral-500">
                            Mitra Dinilai
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {summary.petugas_reviewed}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs text-neutral-500">
                            Kegiatan Dinilai
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {summary.kegiatan_reviewed}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs text-neutral-500">
                            Review Berulasan
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {summary.reviews_with_ulasan}
                        </p>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs tracking-wide text-amber-600 uppercase dark:text-amber-400">
                                Hall Of Fame
                            </p>
                            <h3 className="mt-1 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                Mitra Terbaik Saat Ini
                            </h3>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Menggunakan filter aktif (bulan/kegiatan),
                                filter petugas diabaikan untuk objektivitas.
                            </p>
                        </div>
                        <Badge variant="outline">Balanced</Badge>
                    </div>

                    {hall_of_fame ? (
                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/40 dark:bg-amber-900/10">
                            <p className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                {hall_of_fame.petugas_nama}
                            </p>
                            <div className="mt-2 grid gap-2 text-sm text-neutral-700 md:grid-cols-4 dark:text-neutral-300">
                                <p>
                                    Balanced Score:{' '}
                                    {hall_of_fame.balanced_score}
                                </p>
                                <p>
                                    Rata-rata Rating: {hall_of_fame.avg_rating}
                                </p>
                                <p>Total Review: {hall_of_fame.review_count}</p>
                                <p>Kegiatan: {hall_of_fame.kegiatan_count}</p>
                            </div>
                        </div>
                    ) : (
                        <p className="mt-4 text-sm text-neutral-500">
                            Belum ada data kandidat Hall of Fame.
                        </p>
                    )}
                </ContentCard>

                <ContentCard>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs tracking-wide text-rose-600 uppercase dark:text-rose-400">
                                Mitra Terbaik per Bulan
                            </p>
                            <h3 className="mt-1 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                Ringkasan Bulanan
                            </h3>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Menggunakan data review yang sudah terenkripsi
                                di backend, lalu didekripsi untuk ringkasan ini.
                            </p>
                        </div>
                        <Badge variant="outline">Balanced</Badge>
                    </div>

                    {bestMitraPerMonth.length > 0 ? (
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {bestMitraPerMonth.map((item) => (
                                <div
                                    key={item.month}
                                    className="rounded-lg border border-rose-200 bg-rose-50/70 p-3 dark:border-rose-900/40 dark:bg-rose-900/10"
                                >
                                    <p className="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">
                                        {MONTH_OPTIONS.find(
                                            (option) => option.value === item.month,
                                        )?.label ?? item.month}
                                    </p>
                                    <p className="mt-1 truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        {item.petugas_nama}
                                    </p>
                                    <div className="mt-2 flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                                        <span>{item.review_count} review</span>
                                        <span>Rating {item.avg_rating}</span>
                                        <span>Balanced {item.balanced_score}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="mt-4 text-sm text-neutral-500">
                            Belum ada data review per bulan.
                        </p>
                    )}
                </ContentCard>

                <ContentCard>
                    <h3 className="text-sm font-semibold">
                        Tabel Review Mitra (Balanced)
                    </h3>
                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Berdasarkan filter aktif bulan/kegiatan, filter petugas
                        tidak digunakan.
                    </p>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full min-w-[980px] text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 text-left text-xs text-neutral-500 dark:border-neutral-800">
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleMitraTableSort(
                                                    'petugas_nama',
                                                )
                                            }
                                        >
                                            Nama Petugas
                                            {getMitraSortLabel('petugas_nama')}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleMitraTableSort(
                                                    'kegiatan_count',
                                                )
                                            }
                                        >
                                            Jumlah Kegiatan Di-Review
                                            {getMitraSortLabel(
                                                'kegiatan_count',
                                            )}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleMitraTableSort(
                                                    'review_count',
                                                )
                                            }
                                        >
                                            Jumlah Review
                                            {getMitraSortLabel('review_count')}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleMitraTableSort(
                                                    'avg_review_per_kegiatan',
                                                )
                                            }
                                        >
                                            Rata-Rata Review per Kegiatan
                                            {getMitraSortLabel(
                                                'avg_review_per_kegiatan',
                                            )}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleMitraTableSort(
                                                    'avg_rating',
                                                )
                                            }
                                        >
                                            Rata-Rata Rating
                                            {getMitraSortLabel('avg_rating')}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleMitraTableSort(
                                                    'balanced_score',
                                                )
                                            }
                                        >
                                            Balanced Score
                                            {getMitraSortLabel(
                                                'balanced_score',
                                            )}
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedMitraTableRows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-3 py-6 text-center text-neutral-500"
                                        >
                                            Belum ada data review mitra.
                                        </td>
                                    </tr>
                                )}

                                {paginatedMitraTableRows.map((item) => (
                                    <tr
                                        key={item.petugas_id}
                                        className="border-b border-neutral-100 dark:border-neutral-900"
                                    >
                                        <td className="px-3 py-2 font-medium">
                                            {item.petugas_nama}
                                        </td>
                                        <td className="px-3 py-2">
                                            {item.kegiatan_count}
                                        </td>
                                        <td className="px-3 py-2">
                                            {item.review_count}
                                        </td>
                                        <td className="px-3 py-2">
                                            {item.avg_review_per_kegiatan}
                                        </td>
                                        <td className="px-3 py-2">
                                            <Badge
                                                className={getRatingBadgeClass(
                                                    item.avg_rating,
                                                )}
                                            >
                                                {item.avg_rating}/5
                                            </Badge>
                                        </td>
                                        <td className="px-3 py-2">
                                            <Badge variant="outline">
                                                {item.balanced_score}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-neutral-500">
                        <p>
                            Menampilkan{' '}
                            {sortedMitraTableRows.length === 0
                                ? 0
                                : (safeMitraTablePage - 1) * pageSize + 1}{' '}
                            -{' '}
                            {Math.min(
                                safeMitraTablePage * pageSize,
                                sortedMitraTableRows.length,
                            )}{' '}
                            dari {sortedMitraTableRows.length} mitra
                        </p>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                className="rounded border border-neutral-300 px-2 py-1 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700"
                                onClick={() =>
                                    setMitraTablePage((prev) =>
                                        Math.max(1, prev - 1),
                                    )
                                }
                                disabled={safeMitraTablePage <= 1}
                            >
                                Prev
                            </button>
                            <span>
                                Halaman {safeMitraTablePage} /{' '}
                                {mitraTableTotalPages}
                            </span>
                            <button
                                type="button"
                                className="rounded border border-neutral-300 px-2 py-1 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700"
                                onClick={() =>
                                    setMitraTablePage((prev) =>
                                        Math.min(
                                            mitraTableTotalPages,
                                            prev + 1,
                                        ),
                                    )
                                }
                                disabled={
                                    safeMitraTablePage >= mitraTableTotalPages
                                }
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </ContentCard>
                <ContentCard className="relative z-30">
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="space-y-1">
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Bulan
                            </p>
                            <Select
                                value={filters.bulan}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        value,
                                        filters.kegiatan_id,
                                        filters.petugas_id,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {MONTH_OPTIONS.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1">
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Kegiatan
                            </p>
                            <Select
                                value={filters.kegiatan_id}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        filters.bulan,
                                        value,
                                        filters.petugas_id,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Semua Kegiatan
                                    </SelectItem>
                                    {kegiatan_options.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1">
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Petugas
                            </p>
                            <SearchableSelect
                                value={filters.petugas_id}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        filters.bulan,
                                        filters.kegiatan_id,
                                        value,
                                    )
                                }
                                options={[
                                    {
                                        value: 'all',
                                        label: 'Semua Petugas',
                                        searchKeywords: 'semua petugas all',
                                    },
                                    ...petugas_options.map((option) => ({
                                        value: option.value,
                                        label: option.label,
                                        searchKeywords: option.label,
                                    })),
                                ]}
                                placeholder="Pilih Petugas"
                                searchPlaceholder="Cari petugas..."
                                className="h-9 rounded-md border-input bg-transparent shadow-xs"
                            />
                        </div>
                    </div>
                </ContentCard>
                <div className="grid gap-4 xl:grid-cols-2">
                    <ContentCard>
                        <h3 className="text-sm font-semibold">
                            Distribusi Rating
                        </h3>
                        <div className="mt-4 h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={rating_distribution}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis
                                        dataKey="rating"
                                        label={{
                                            value: 'Skor',
                                            position: 'insideBottom',
                                            offset: -5,
                                        }}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        label={{
                                            value: 'Jumlah Review',
                                            angle: -90,
                                            position: 'insideLeft',
                                        }}
                                    />
                                    <Tooltip
                                        formatter={(value) => [
                                            value,
                                            'Jumlah Review',
                                        ]}
                                        labelFormatter={(value) =>
                                            `Rating ${value}`
                                        }
                                    />
                                    <Bar
                                        dataKey="jumlah"
                                        fill="#0ea5e9"
                                        radius={[6, 6, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <h3 className="text-sm font-semibold">
                            Tren Review Bulanan
                        </h3>
                        <div className="mt-4 h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={monthly_trend}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis
                                        yAxisId="left"
                                        allowDecimals={false}
                                    />
                                    <YAxis
                                        yAxisId="right"
                                        orientation="right"
                                        domain={[0, 5]}
                                    />
                                    <Tooltip
                                        formatter={(value, name) => {
                                            if (
                                                name === 'jumlah_review' ||
                                                name === 'Jumlah Review'
                                            ) {
                                                return [value, 'Jumlah Review'];
                                            }

                                            return [value, 'Rata-rata Rating'];
                                        }}
                                    />
                                    <Line
                                        yAxisId="left"
                                        type="monotone"
                                        dataKey="jumlah_review"
                                        name="Jumlah Review"
                                        stroke="#2563eb"
                                        strokeWidth={2}
                                    />
                                    <Line
                                        yAxisId="right"
                                        type="monotone"
                                        dataKey="rata_rating"
                                        name="Rata-rata Rating"
                                        stroke="#f59e0b"
                                        strokeWidth={2}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </ContentCard>
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    {show_kegiatan_terbanyak && (
                        <ContentCard className="xl:col-span-2">
                            <h3 className="text-sm font-semibold">
                                Kegiatan dengan Review Terbanyak
                            </h3>
                            <div className="mt-4 space-y-2">
                                {kegiatan_stats.length === 0 && (
                                    <p className="text-sm text-neutral-500">
                                        Belum ada data kegiatan.
                                    </p>
                                )}
                                {kegiatan_stats.map((item) => (
                                    <div
                                        key={item.kegiatan_id}
                                        className="flex items-center justify-between rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {item.kegiatan_nama}
                                            </p>
                                            <p className="text-xs text-neutral-500">
                                                {item.petugas_count} petugas |
                                                rata-rata {item.avg_rating}
                                            </p>
                                        </div>
                                        <Badge>
                                            {item.review_count} review
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </ContentCard>
                    )}

                    {show_mitra_top_bottom ? (
                        <ContentCard
                            className={
                                show_kegiatan_terbanyak ? '' : 'xl:col-span-3'
                            }
                        >
                            <h3 className="text-sm font-semibold">
                                Top {mitraRankLimit} Mitra
                            </h3>
                            <div className="mt-4 space-y-2">
                                {top_petugas.map((item) => (
                                    <div
                                        key={item.petugas_id}
                                        className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                                    >
                                        <p className="text-sm font-medium">
                                            {item.petugas_nama}
                                        </p>
                                        <div className="mt-1 flex flex-wrap gap-2 text-xs text-neutral-500">
                                            <span>
                                                Balanced {item.balanced_score}
                                            </span>
                                            <span>Rating {item.avg_rating}</span>
                                            <span>
                                                {item.review_count} review
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <h3 className="mt-6 text-sm font-semibold">
                                Bottom {mitraRankLimit} Mitra
                            </h3>
                            <div className="mt-4 space-y-2">
                                {bottom_petugas.map((item) => (
                                    <div
                                        key={item.petugas_id}
                                        className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                                    >
                                        <p className="text-sm font-medium">
                                            {item.petugas_nama}
                                        </p>
                                        <div className="mt-1 flex flex-wrap gap-2 text-xs text-neutral-500">
                                            <span>
                                                Balanced {item.balanced_score}
                                            </span>
                                            <span>Rating {item.avg_rating}</span>
                                            <span>
                                                {item.review_count} review
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </ContentCard>
                    ) : show_kegiatan_rank_for_petugas ? (
                        <ContentCard className="xl:col-span-3">
                            <div className="grid gap-4 xl:grid-cols-2">
                                <div>
                                    <h3 className="text-sm font-semibold">
                                        Top 3 Rating Kegiatan
                                    </h3>
                                    <div className="mt-4 space-y-2">
                                        {top_kegiatan_for_petugas.map(
                                            (item) => (
                                                <div
                                                    key={item.kegiatan_id}
                                                    className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                                                >
                                                    <p className="text-sm font-medium">
                                                        {item.kegiatan_nama}
                                                    </p>
                                                    <p className="text-xs text-neutral-500">
                                                        Rating {item.avg_rating}{' '}
                                                        | {item.review_count}{' '}
                                                        review
                                                    </p>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>

                                <div>
                                    <h3 className="text-sm font-semibold">
                                        Bottom 3 Rating Kegiatan
                                    </h3>
                                    <div className="mt-4 space-y-2">
                                        {bottom_kegiatan_for_petugas.map(
                                            (item) => (
                                                <div
                                                    key={item.kegiatan_id}
                                                    className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                                                >
                                                    <p className="text-sm font-medium">
                                                        {item.kegiatan_nama}
                                                    </p>
                                                    <p className="text-xs text-neutral-500">
                                                        Rating {item.avg_rating}{' '}
                                                        | {item.review_count}{' '}
                                                        review
                                                    </p>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            </div>
                        </ContentCard>
                    ) : null}
                </div>

                <ContentCard>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h3 className="text-sm font-semibold">Detail Review</h3>
                        <Select
                            value={minimumRating}
                            onValueChange={(value) => {
                                setMinimumRating(value);
                                setCurrentPage(1);
                            }}
                        >
                            <SelectTrigger className="w-52">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua Rating
                                </SelectItem>
                                <SelectItem value="4">Rating ≥ 4</SelectItem>
                                <SelectItem value="3">Rating ≥ 3</SelectItem>
                                <SelectItem value="2">Rating ≥ 2</SelectItem>
                                <SelectItem value="lt2">
                                    Rating {'<'} 2
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full min-w-[820px] text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 text-left text-xs text-neutral-500 dark:border-neutral-800">
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleSort('petugas_nama')
                                            }
                                        >
                                            Petugas
                                            {getSortLabel('petugas_nama')}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleSort('kegiatan_nama')
                                            }
                                        >
                                            Kegiatan
                                            {getSortLabel('kegiatan_nama')}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() => handleSort('rating')}
                                        >
                                            Rating{getSortLabel('rating')}
                                        </button>
                                    </th>
                                    <th className="px-3 py-2">Ulasan</th>
                                    <th className="px-3 py-2">
                                        <button
                                            type="button"
                                            className="hover:text-neutral-800 dark:hover:text-neutral-200"
                                            onClick={() =>
                                                handleSort('reviewed_at')
                                            }
                                        >
                                            Waktu Review
                                            {getSortLabel('reviewed_at')}
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedRows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-6 text-center text-neutral-500"
                                        >
                                            Tidak ada data review untuk filter
                                            saat ini.
                                        </td>
                                    </tr>
                                )}
                                {paginatedRows.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b border-neutral-100 dark:border-neutral-900"
                                    >
                                        <td className="px-3 py-2 font-medium">
                                            {row.petugas_nama}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.kegiatan_nama}
                                        </td>
                                        <td className="px-3 py-2">
                                            <Badge variant="outline">
                                                {row.rating}/5
                                            </Badge>
                                        </td>
                                        <td className="max-w-sm px-3 py-2 text-xs text-neutral-600 dark:text-neutral-300">
                                            {row.ulasan ? row.ulasan : '-'}
                                        </td>
                                        <td className="px-3 py-2 text-xs text-neutral-500">
                                            {row.reviewed_at ?? '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-neutral-500">
                        <p>
                            Menampilkan{' '}
                            {sortedRows.length === 0
                                ? 0
                                : (safeCurrentPage - 1) * pageSize + 1}{' '}
                            -{' '}
                            {Math.min(
                                safeCurrentPage * pageSize,
                                sortedRows.length,
                            )}{' '}
                            dari {sortedRows.length} review
                        </p>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                className="rounded border border-neutral-300 px-2 py-1 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700"
                                onClick={() =>
                                    setCurrentPage((prev) =>
                                        Math.max(1, prev - 1),
                                    )
                                }
                                disabled={safeCurrentPage <= 1}
                            >
                                Prev
                            </button>
                            <span>
                                Halaman {safeCurrentPage} / {totalPages}
                            </span>
                            <button
                                type="button"
                                className="rounded border border-neutral-300 px-2 py-1 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700"
                                onClick={() =>
                                    setCurrentPage((prev) =>
                                        Math.min(totalPages, prev + 1),
                                    )
                                }
                                disabled={safeCurrentPage >= totalPages}
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </ContentCard>

                <p className="text-right text-xs text-neutral-500">
                    Data dibuat pada: {generated_at}
                </p>
            </div>
        </AppLayout>
    );
}
