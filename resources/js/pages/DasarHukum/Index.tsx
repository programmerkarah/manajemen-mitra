import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
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
    Trash2,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master', href: '#' },
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
    const [sortField, setSortField] = useState<'nomor' | 'tahun' | 'tentang'>(
        'tahun',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const prevFiltersRef = useRef({ search, status });

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
    }, [allDasarHukum, search, status, sortField, sortDirection]);

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
        if (prevFilters.search !== search || prevFilters.status !== status) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- Conditional reset based on filter change via ref
            setCurrentPage(1);
            prevFiltersRef.current = { search, status };
        }
    }, [search, status]);

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

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                    </div>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="flex items-center justify-between px-6 pt-4 pb-2">
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
                                        // Format dengan atau tanpa instansi
                                        const formatNamaLengkap = () => {
                                            let kategoriLabel = '';

                                            if (
                                                item.kategori ===
                                                'undang_undang'
                                            ) {
                                                kategoriLabel = 'Undang-Undang';
                                            } else if (
                                                item.kategori ===
                                                'peraturan_pemerintah'
                                            ) {
                                                kategoriLabel =
                                                    'Peraturan Pemerintah';
                                            } else if (
                                                item.kategori ===
                                                'peraturan_presiden'
                                            ) {
                                                kategoriLabel =
                                                    'Peraturan Presiden';
                                            } else if (
                                                item.kategori ===
                                                'peraturan_menteri_badan'
                                            ) {
                                                // Deteksi apakah instansi adalah Badan atau Menteri
                                                if (
                                                    item.instansi &&
                                                    item.instansi
                                                        .toLowerCase()
                                                        .startsWith('badan')
                                                ) {
                                                    kategoriLabel = `Peraturan ${item.instansi}`;
                                                } else {
                                                    kategoriLabel = `Peraturan Menteri ${item.instansi}`;
                                                }
                                            } else if (
                                                item.kategori ===
                                                'keputusan_menteri_kepala_badan'
                                            ) {
                                                // Deteksi apakah instansi adalah Badan atau Menteri
                                                if (
                                                    item.instansi &&
                                                    item.instansi
                                                        .toLowerCase()
                                                        .startsWith('badan')
                                                ) {
                                                    kategoriLabel = `Keputusan Kepala ${item.instansi}`;
                                                } else {
                                                    kategoriLabel = `Keputusan Menteri ${item.instansi}`;
                                                }
                                            }

                                            return `${kategoriLabel} Nomor ${item.nomor} Tahun ${item.tahun}`;
                                        };

                                        return (
                                            <tr
                                                key={item.id}
                                                className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-3 py-3">
                                                    <div className="space-y-1">
                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                            <div className="max-w-2xl">
                                                                {formatNamaLengkap()}
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
                                                            <Link
                                                                href={`/dasar-hukum/${item.id}/edit`}
                                                            >
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="gap-2"
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                    Edit
                                                                </Button>
                                                            </Link>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                                                onClick={() => {
                                                                    handleDelete(
                                                                        item.id,
                                                                        formatNamaLengkap(),
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
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                Halaman{' '}
                                <span className="font-medium">
                                    {currentPage}
                                </span>{' '}
                                dari{' '}
                                <span className="font-medium">
                                    {totalPages}
                                </span>
                            </div>
                            <div className="flex items-center gap-1">
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
