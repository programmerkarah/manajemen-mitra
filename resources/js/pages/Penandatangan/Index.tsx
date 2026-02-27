import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    CreditCard,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Trash2,
    User as UserIcon,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Penandatangan', href: '/penandatangan' },
];

// SortIcon component declared outside to avoid recreation on each render
const SortIcon = ({
    field,
    sortField,
    sortDirection,
}: {
    field: string;
    sortField: string;
    sortDirection: 'asc' | 'desc';
}) => {
    if (sortField !== field) return null;
    return sortDirection === 'asc' ? (
        <ChevronUp className="h-4 w-4" />
    ) : (
        <ChevronDown className="h-4 w-4" />
    );
};

interface Penandatangan {
    id: number;
    nama: string;
    nip: string | null;
    jenis_penandatangan: 'kepala' | 'ppk';
    jabatan: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface PenandatanganIndexProps {
    PenandatanganList: {
        encrypted: string;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        encrypted?: string;
        decrypted?: {
            search?: string;
            status?: string;
            jenis?: string;
        };
    };
}

export default function Index({ PenandatanganList }: PenandatanganIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';

    const allPenandatangan = useDecryptedData<Penandatangan>(
        PenandatanganList.encrypted,
    );

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [jenis, setJenis] = useState('');
    const [sortField, setSortField] = useState<'nama' | 'nip' | 'jabatan'>(
        'nama',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const prevFiltersRef = useRef({ search, jenis, status });
    const [selectedPenandatangan, setSelectedPenandatangan] =
        useState<Penandatangan | null>(null);
    const [processing, setProcessing] = useState(false);

    // Client-side filtering and sorting
    const filteredAndSortedPenandatangan = useMemo(() => {
        let result: Penandatangan[] = [...allPenandatangan];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (item: Penandatangan) =>
                    item.nama?.toLowerCase().includes(query) ||
                    item.nip?.toLowerCase().includes(query) ||
                    item.jabatan?.toLowerCase().includes(query),
            );
        }

        // Filter by jenis
        if (jenis) {
            result = result.filter(
                (item: Penandatangan) => item.jenis_penandatangan === jenis,
            );
        }

        // Filter by status
        if (status) {
            const isActive = status === 'aktif';
            result = result.filter(
                (item: Penandatangan) => item.is_active === isActive,
            );
        }

        // Sort
        result.sort((a: Penandatangan, b: Penandatangan) => {
            let aVal = '',
                bVal = '';
            switch (sortField) {
                case 'nip':
                    aVal = a.nip?.toLowerCase() || '';
                    bVal = b.nip?.toLowerCase() || '';
                    break;
                case 'jabatan':
                    aVal = a.jabatan?.toLowerCase() || '';
                    bVal = b.jabatan?.toLowerCase() || '';
                    break;
                case 'nama':
                default:
                    aVal = a.nama?.toLowerCase() || '';
                    bVal = b.nama?.toLowerCase() || '';
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [allPenandatangan, search, jenis, status, sortField, sortDirection]);

    // Client-side pagination
    const totalPages = Math.ceil(
        filteredAndSortedPenandatangan.length / perPage,
    );
    const paginatedPenandatangan = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedPenandatangan.slice(start, end);
    }, [filteredAndSortedPenandatangan, currentPage, perPage]);

    // Reset to page 1 when filters change
    useEffect(() => {
        const prevFilters = prevFiltersRef.current;
        if (
            prevFilters.search !== search ||
            prevFilters.jenis !== jenis ||
            prevFilters.status !== status
        ) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- Conditional reset based on filter change via ref
            setCurrentPage(1);
            prevFiltersRef.current = { search, jenis, status };
        }
    }, [search, jenis, status]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            },
        });
    };

    const handleSort = (field: 'nama' | 'nip' | 'jabatan') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const handleDeleteClick = (Penandatangan: Penandatangan) => {
        setSelectedPenandatangan(Penandatangan);
        setShowDeleteDialog(true);
    };

    const handleDelete = () => {
        if (!selectedPenandatangan) return;

        setProcessing(true);
        router.delete(`/penandatangan/${selectedPenandatangan.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setShowDeleteDialog(false);
                setSelectedPenandatangan(null);
            },
        });
    };

    // Fungsi untuk reset filter
    const handleReset = () => {
        setSearch('');
        setStatus('');
        setJenis('');
    };
    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penandatangan" />

            <div className="space-y-6">
                <PageHeader
                    title="Penandatangan"
                    description="Kelola data Penandatangan untuk dokumen SK"
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
                            <Button asChild size="sm" className="gap-2">
                                <Link href="/penandatangan/create">
                                    <Plus className="h-4 w-4" />
                                    Tambah Penandatangan
                                </Link>
                            </Button>
                        )}
                    </div>
                </PageHeader>

                <ContentCard>
                    {/* Search and Filter */}
                    <div className="mb-4 flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Cari nama, NIP, atau jabatan..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 pl-10"
                                />
                            </div>
                        </div>

                        <Select
                            value={jenis || 'all'}
                            onValueChange={(value) =>
                                setJenis(value === 'all' ? '' : value)
                            }
                        >
                            <SelectTrigger className="h-10 w-full sm:w-[180px]">
                                <SelectValue placeholder="Semua Jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Jenis</SelectItem>
                                <SelectItem value="kepala">Kepala</SelectItem>
                                <SelectItem value="ppk">PPK</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            value={status || 'all'}
                            onValueChange={(value) =>
                                setStatus(value === 'all' ? '' : value)
                            }
                        >
                            <SelectTrigger className="h-10 w-full sm:w-[180px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua Status
                                </SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">
                                    Non-Aktif
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            onClick={handleReset}
                            variant="outline"
                            className="h-10"
                        >
                            <X className="mr-2 h-4 w-4" />
                            Reset
                        </Button>
                    </div>

                    {/* Table */}
                    <div className="mb-4 flex items-center justify-between">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAndSortedPenandatangan.length,
                            )}{' '}
                            dari {filteredAndSortedPenandatangan.length} data
                            {(search || jenis || status) &&
                                ` (difilter dari ${allPenandatangan.length} total)`}
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('nama')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <UserIcon className="h-4 w-4" />
                                            Nama
                                            <SortIcon
                                                field="nama"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('nip')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <CreditCard className="h-4 w-4" />
                                            NIP
                                            <SortIcon
                                                field="nip"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <Briefcase className="h-4 w-4" />
                                            Jenis
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('jabatan')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <Briefcase className="h-4 w-4" />
                                            Jabatan
                                            <SortIcon
                                                field="jabatan"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <Calendar className="h-4 w-4" />
                                            Periode Mulai
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <Calendar className="h-4 w-4" />
                                            Periode Selesai
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
                                {paginatedPenandatangan.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 7 : 8}
                                            className="px-4 py-12 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <UserIcon className="h-12 w-12 opacity-20" />
                                                <p className="font-medium">
                                                    Tidak ada data penandatangan
                                                </p>
                                                <p className="text-xs">
                                                    Coba ubah filter atau
                                                    kriteria pencarian
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedPenandatangan.map(
                                        (Penandatangan) => (
                                            <tr
                                                key={Penandatangan.id}
                                                className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-3 py-3 text-sm">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                                            {Penandatangan.nama
                                                                ?.charAt(0)
                                                                .toUpperCase() ||
                                                                'P'}
                                                        </div>
                                                        <div
                                                            className="max-w-xs truncate font-medium"
                                                            title={
                                                                Penandatangan.nama
                                                            }
                                                        >
                                                            {Penandatangan.nama}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                    {Penandatangan.nip || '-'}
                                                </td>
                                                <td className="px-3 py-3 text-sm whitespace-nowrap">
                                                    <Badge
                                                        variant={
                                                            Penandatangan.jenis_penandatangan ===
                                                            'kepala'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {Penandatangan.jenis_penandatangan ===
                                                        'kepala'
                                                            ? 'Kepala (SK)'
                                                            : 'PPK (Perjanjian Kerja/BAST)'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                    <div
                                                        className="max-w-xs"
                                                        title={
                                                            Penandatangan.jabatan
                                                        }
                                                    >
                                                        {Penandatangan.jabatan}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                    {formatDate(
                                                        Penandatangan.periode_mulai,
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                    {formatDate(
                                                        Penandatangan.periode_selesai,
                                                    )}
                                                </td>
                                                <td className="px-3 py-3">
                                                    <StatusBadge
                                                        status={
                                                            Penandatangan.is_active
                                                                ? 'aktif'
                                                                : 'nonaktif'
                                                        }
                                                    />
                                                </td>
                                                {!isPJ && (
                                                    <td className="px-3 py-3">
                                                        <div className="flex items-center justify-center gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                                className="h-8 gap-1.5"
                                                            >
                                                                <Link
                                                                    href={`/penandatangan/${Penandatangan.id}/edit`}
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                    <span className="sr-only sm:not-sr-only">
                                                                        Edit
                                                                    </span>
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    handleDeleteClick(
                                                                        Penandatangan,
                                                                    )
                                                                }
                                                                className="h-8 gap-1.5 border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                                <span className="sr-only sm:not-sr-only">
                                                                    Hapus
                                                                </span>
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ),
                                    )
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
                                {/* Previous Button */}
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

                                {/* Page Numbers */}
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

                                {/* Next Button */}
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

            {/* Delete Confirmation Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Konfirmasi Hapus</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus data Penandatangan{' '}
                        <span className="font-semibold">
                            {selectedPenandatangan?.nama}
                        </span>
                        ?
                    </p>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDeleteDialog(false)}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={processing}
                        >
                            {processing ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
