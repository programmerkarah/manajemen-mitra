import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { encryptData } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    FileText,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Sparkles,
    Trash2,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
];

interface DasarHukum {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    status: 'aktif' | 'nonaktif';
    jenis: 'pertama' | 'perubahan';
    induk_id: number | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    dasarHukum: {
        encrypted: string;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        encrypted?: string;
        decrypted?: {
            search: string;
            status: string;
        };
    };
}

export default function Index({ dasarHukum }: Props) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';

    const allDasarHukum = useDecryptedData<DasarHukum>(dasarHukum.encrypted);

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [jenis, setJenis] = useState<'all' | 'pertama' | 'perubahan'>('all');
    const [sortField, setSortField] = useState<'nomor' | 'tahun' | 'tentang'>(
        'tahun',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const prevFiltersRef = useRef({ search, status, jenis });

    // Client-side filtering and sorting
    const filteredAndSortedDasarHukum = useMemo(() => {
        let result: DasarHukum[] = [...allDasarHukum];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (item: DasarHukum) =>
                    item.nomor?.toLowerCase().includes(query) ||
                    item.tentang?.toLowerCase().includes(query) ||
                    item.kategori?.toLowerCase().includes(query),
            );
        }

        // Filter by status
        if (status && status !== 'all') {
            result = result.filter(
                (item: DasarHukum) => item.status === status,
            );
        }

        if (jenis !== 'all') {
            result = result.filter((item: DasarHukum) => item.jenis === jenis);
        }

        // Sort
        result.sort((a: DasarHukum, b: DasarHukum) => {
            let aVal: string | number = '';
            let bVal: string | number = '';
            switch (sortField) {
                case 'nomor':
                    aVal = a.nomor?.toLowerCase() || '';
                    bVal = b.nomor?.toLowerCase() || '';
                    break;
                case 'tentang':
                    aVal = a.tentang?.toLowerCase() || '';
                    bVal = b.tentang?.toLowerCase() || '';
                    break;
                case 'tahun':
                default:
                    aVal = a.tahun || 0;
                    bVal = b.tahun || 0;
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [allDasarHukum, search, status, jenis, sortField, sortDirection]);

    // Client-side pagination
    const totalPages = Math.ceil(filteredAndSortedDasarHukum.length / perPage);
    const paginatedDasarHukum = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedDasarHukum.slice(start, end);
    }, [filteredAndSortedDasarHukum, currentPage, perPage]);

    // Reset to page 1 when filters change
    useEffect(() => {
        const prevFilters = prevFiltersRef.current;
        if (
            prevFilters.search !== search ||
            prevFilters.status !== status ||
            prevFilters.jenis !== jenis
        ) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- Conditional reset based on filter change via ref
            setCurrentPage(1);
            prevFiltersRef.current = { search, status, jenis };
        }
    }, [search, status, jenis]);

    const stats = useMemo(() => {
        const total = allDasarHukum.length;
        const aktif = allDasarHukum.filter(
            (item) => item.status === 'aktif',
        ).length;
        const perubahan = allDasarHukum.filter(
            (item) => item.jenis === 'perubahan',
        ).length;
        const kategori = new Set(allDasarHukum.map((item) => item.kategori))
            .size;

        return { total, aktif, perubahan, kategori };
    }, [allDasarHukum]);

    const getKategoriLabel = (item: DasarHukum): string => {
        if (item.kategori === 'undang_undang') {
            return 'Undang-Undang';
        }
        if (item.kategori === 'peraturan_pemerintah') {
            return 'Peraturan Pemerintah';
        }
        if (item.kategori === 'peraturan_presiden') {
            return 'Peraturan Presiden';
        }
        if (item.kategori === 'peraturan_menteri_badan') {
            return item.instansi?.toLowerCase().startsWith('badan')
                ? `Peraturan ${item.instansi}`
                : `Peraturan Menteri ${item.instansi}`;
        }
        if (item.kategori === 'keputusan_menteri_kepala_badan') {
            return item.instansi?.toLowerCase().startsWith('badan')
                ? `Keputusan Kepala ${item.instansi}`
                : `Keputusan Menteri ${item.instansi}`;
        }
        if (item.kategori === 'peraturan_kepala_badan') {
            return 'Peraturan Kepala Badan Pusat Statistik';
        }

        return item.kategori;
    };

    const getKategoriBadgeClass = (kategori: string): string => {
        if (kategori === 'undang_undang') {
            return 'border-fuchsia-300 text-fuchsia-700 dark:border-fuchsia-700 dark:text-fuchsia-300';
        }
        if (kategori === 'peraturan_pemerintah') {
            return 'border-blue-300 text-blue-700 dark:border-blue-700 dark:text-blue-300';
        }
        if (kategori === 'peraturan_presiden') {
            return 'border-indigo-300 text-indigo-700 dark:border-indigo-700 dark:text-indigo-300';
        }
        if (kategori === 'peraturan_menteri_badan') {
            return 'border-teal-300 text-teal-700 dark:border-teal-700 dark:text-teal-300';
        }
        if (kategori === 'peraturan_kepala_badan') {
            return 'border-cyan-300 text-cyan-700 dark:border-cyan-700 dark:text-cyan-300';
        }
        if (kategori === 'keputusan_menteri_kepala_badan') {
            return 'border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300';
        }

        return 'border-neutral-300 text-neutral-700 dark:border-neutral-700 dark:text-neutral-300';
    };

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            },
        });
    };

    const handleSort = (field: 'nomor' | 'tahun' | 'tentang') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const renderSortIcon = (field: 'nomor' | 'tahun' | 'tentang') => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? (
            <ChevronUp className="h-4 w-4" />
        ) : (
            <ChevronDown className="h-4 w-4" />
        );
    };

    const handleDelete = (id: number, nomor: string) => {
        if (
            confirm(`Apakah Anda yakin ingin menghapus dasar hukum "${nomor}"?`)
        ) {
            router.delete(`/dasar-hukum/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasar Hukum SK" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Dasar Hukum SK"
                    description="Kelola dasar hukum yang digunakan pada SK KPA"
                >
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                        >
                            <RefreshCw
                                className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`}
                            />
                            Refresh
                        </Button>
                        {!isPJ && (
                            <Button size="sm" asChild className="gap-2">
                                <Link href="/dasar-hukum/create">
                                    <Plus className="h-4 w-4" />
                                    Tambah Dasar Hukum
                                </Link>
                            </Button>
                        )}
                    </div>
                </PageHeader>

                {/* Filters */}
                <ContentCard>
                    <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <button
                            type="button"
                            onClick={() => {
                                setStatus('all');
                                setJenis('all');
                            }}
                            className="rounded-xl border border-neutral-200 bg-neutral-50/70 p-3 text-left transition-all hover:border-neutral-300 hover:bg-white dark:border-neutral-800 dark:bg-neutral-900/40 dark:hover:border-neutral-700"
                        >
                            <p className="text-xs text-neutral-500">
                                Total Dasar Hukum
                            </p>
                            <p className="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">
                                {stats.total}
                            </p>
                        </button>
                        <button
                            type="button"
                            onClick={() => setStatus('aktif')}
                            className="rounded-xl border border-green-200 bg-green-50/70 p-3 text-left transition-all hover:bg-green-50 dark:border-green-900 dark:bg-green-950/30"
                        >
                            <p className="text-xs text-green-700/80 dark:text-green-400">
                                Status Aktif
                            </p>
                            <p className="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">
                                {stats.aktif}
                            </p>
                        </button>
                        <button
                            type="button"
                            onClick={() => setJenis('perubahan')}
                            className="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-left transition-all hover:bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30"
                        >
                            <p className="text-xs text-amber-700/80 dark:text-amber-400">
                                Peraturan Perubahan
                            </p>
                            <p className="mt-1 flex items-center gap-1 text-2xl font-bold text-amber-700 dark:text-amber-300">
                                {stats.perubahan}
                                <Sparkles className="h-4 w-4" />
                            </p>
                        </button>
                        <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-3 dark:border-blue-900 dark:bg-blue-950/30">
                            <p className="text-xs text-blue-700/80 dark:text-blue-400">
                                Kategori Tersedia
                            </p>
                            <p className="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {stats.kategori}
                            </p>
                        </div>
                    </div>

                    {/* Results Counter */}
                    <div className="mb-4 text-sm text-muted-foreground">
                        Menampilkan{' '}
                        <span className="font-semibold text-foreground">
                            {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAndSortedDasarHukum.length,
                            )}
                        </span>{' '}
                        dari{' '}
                        <span className="font-semibold text-foreground">
                            {filteredAndSortedDasarHukum.length}
                        </span>{' '}
                        dasar hukum{' '}
                        {search || status !== 'all'
                            ? `(difilter dari ${allDasarHukum.length} total data)`
                            : ''}
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div className="md:col-span-2">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                                <Input
                                    type="text"
                                    placeholder="Cari nomor atau tentang..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </div>
                        <div>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Semua Status
                                    </SelectItem>
                                    <SelectItem value="aktif">Aktif</SelectItem>
                                    <SelectItem value="nonaktif">
                                        Nonaktif
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Select
                                value={jenis}
                                onValueChange={(
                                    value: 'all' | 'pertama' | 'perubahan',
                                ) => setJenis(value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Semua Jenis
                                    </SelectItem>
                                    <SelectItem value="pertama">
                                        Peraturan Pertama
                                    </SelectItem>
                                    <SelectItem value="perubahan">
                                        Peraturan Perubahan
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2">
                        <Badge
                            variant={jenis === 'all' ? 'default' : 'outline'}
                            className="cursor-pointer"
                            onClick={() => setJenis('all')}
                        >
                            Semua Jenis
                        </Badge>
                        <Badge
                            variant={
                                jenis === 'pertama' ? 'default' : 'outline'
                            }
                            className="cursor-pointer"
                            onClick={() => setJenis('pertama')}
                        >
                            Pertama
                        </Badge>
                        <Badge
                            variant={
                                jenis === 'perubahan' ? 'default' : 'outline'
                            }
                            className="cursor-pointer"
                            onClick={() => setJenis('perubahan')}
                        >
                            Perubahan
                        </Badge>
                    </div>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="flex items-center justify-between px-6 pt-4 pb-3">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAndSortedDasarHukum.length,
                            )}{' '}
                            dari {filteredAndSortedDasarHukum.length} data
                            {(search || status !== 'all') &&
                                ` (difilter dari ${allDasarHukum.length} total)`}
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('nomor')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <FileText className="h-4 w-4" />
                                            Dasar Hukum
                                            {renderSortIcon('nomor')}
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('tahun')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <Calendar className="h-4 w-4" />
                                            Tahun
                                            {renderSortIcon('tahun')}
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Status
                                        </div>
                                    </th>
                                    {!isPJ && (
                                        <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                            Aksi
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {!paginatedDasarHukum ||
                                paginatedDasarHukum.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 3 : 4}
                                            className="px-6 py-12 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <FileText className="h-12 w-12 opacity-20" />
                                                <p className="font-medium">
                                                    Belum ada data dasar hukum
                                                </p>
                                                <p className="text-xs">
                                                    Coba ubah filter atau
                                                    kriteria pencarian
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedDasarHukum?.map((item) => {
                                        const kategoriLabel =
                                            getKategoriLabel(item);
                                        const fullLabel = `${kategoriLabel} Nomor ${item.nomor} Tahun ${item.tahun}`;

                                        return (
                                            <tr
                                                key={item.id}
                                                className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-3 py-3">
                                                    <div className="space-y-1">
                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                            <div className="flex max-w-2xl flex-wrap items-center gap-2">
                                                                <Badge
                                                                    variant="outline"
                                                                    className={getKategoriBadgeClass(
                                                                        item.kategori,
                                                                    )}
                                                                >
                                                                    {
                                                                        kategoriLabel
                                                                    }
                                                                </Badge>
                                                                <span className="font-semibold text-neutral-900 dark:text-white">
                                                                    No.{' '}
                                                                    {item.nomor}
                                                                </span>
                                                                <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                                                    Tahun{' '}
                                                                    {item.tahun}
                                                                </span>
                                                                {item.jenis ===
                                                                    'perubahan' && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="shrink-0 border-amber-300 text-xs text-amber-600 dark:border-amber-600 dark:text-amber-400"
                                                                    >
                                                                        <Sparkles className="mr-1 h-3 w-3" />
                                                                        Perubahan
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                            <div className="max-w-2xl">
                                                                tentang{' '}
                                                                {item.tentang}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                    <div className="font-medium">
                                                        {item.tahun}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3 whitespace-nowrap">
                                                    <StatusBadge
                                                        status={item.status}
                                                    />
                                                </td>
                                                {!isPJ && (
                                                    <td className="px-3 py-3 whitespace-nowrap">
                                                        <div className="flex items-center justify-center gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="gap-2"
                                                                onClick={() =>
                                                                    router.post(
                                                                        '/dasar-hukum/edit',
                                                                        {
                                                                            encrypted:
                                                                                encryptData(
                                                                                    {
                                                                                        id: item.id,
                                                                                    },
                                                                                ),
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                Edit
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                                                onClick={() => {
                                                                    handleDelete(
                                                                        item.id,
                                                                        fullLabel,
                                                                    );
                                                                }}
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                                Hapus
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="mt-4 flex flex-col gap-3 border-t border-neutral-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                Halaman{' '}
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {currentPage}
                                </span>{' '}
                                dari{' '}
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {totalPages}
                                </span>
                            </div>
                            <div className="flex flex-wrap items-center gap-1.5">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((prev) =>
                                            Math.max(1, prev - 1),
                                        )
                                    }
                                    disabled={currentPage === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>

                                {Array.from(
                                    { length: totalPages },
                                    (_, i) => i + 1,
                                )
                                    .filter((page) => {
                                        return (
                                            page === 1 ||
                                            page === totalPages ||
                                            (page >= currentPage - 1 &&
                                                page <= currentPage + 1)
                                        );
                                    })
                                    .map((page, index, array) => {
                                        const prevPage = array[index - 1];
                                        const showEllipsis =
                                            prevPage && page > prevPage + 1;

                                        return (
                                            <div
                                                key={page}
                                                className="flex items-center gap-1"
                                            >
                                                {showEllipsis && (
                                                    <span className="px-2 text-neutral-500">
                                                        ...
                                                    </span>
                                                )}
                                                <Button
                                                    variant={
                                                        currentPage === page
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    size="sm"
                                                    onClick={() =>
                                                        setCurrentPage(page)
                                                    }
                                                >
                                                    {page}
                                                </Button>
                                            </div>
                                        );
                                    })}

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((prev) =>
                                            Math.min(totalPages, prev + 1),
                                        )
                                    }
                                    disabled={currentPage === totalPages}
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
