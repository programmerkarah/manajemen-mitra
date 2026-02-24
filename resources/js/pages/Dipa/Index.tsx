import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
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
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'DIPA', href: '/dipa' },
];

interface Dipa {
    id: number;
    nomor_dipa: string;
    tahun: number;
    tanggal_dipa: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface DipaIndexProps {
    dipaList: {
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
    tahunOptions: number[];
    filters: {
        encrypted?: string;
        decrypted?: {
            search?: string;
            status?: string;
            tahun?: string;
        };
    };
}

export default function Index({
    dipaList,
    tahunOptions,
    filters,
}: DipaIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';

    const allDipa = useDecryptedData<Dipa>(dipaList.encrypted);

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [tahun, setTahun] = useState('');
    const [sortField, setSortField] = useState<
        'nomor_dipa' | 'tahun' | 'tanggal_dipa'
    >('tahun');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedDipa, setSelectedDipa] = useState<Dipa | null>(null);
    const [processing, setProcessing] = useState(false);

    // Client-side filtering and sorting
    const filteredAndSortedDipa = useMemo(() => {
        let result: Dipa[] = [...allDipa];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter((item: Dipa) =>
                item.nomor_dipa?.toLowerCase().includes(query),
            );
        }

        // Filter by status
        if (status) {
            const isActive = status === 'active';
            result = result.filter((item: Dipa) => item.is_active === isActive);
        }

        // Filter by tahun
        if (tahun) {
            const year = parseInt(tahun);
            result = result.filter((item: Dipa) => item.tahun === year);
        }

        // Sort
        result.sort((a: Dipa, b: Dipa) => {
            let aVal: any = '',
                bVal: any = '';
            switch (sortField) {
                case 'tahun':
                    aVal = a.tahun || 0;
                    bVal = b.tahun || 0;
                    break;
                case 'tanggal_dipa':
                    aVal = new Date(a.tanggal_dipa).getTime();
                    bVal = new Date(b.tanggal_dipa).getTime();
                    break;
                case 'nomor_dipa':
                default:
                    aVal = a.nomor_dipa?.toLowerCase() || '';
                    bVal = b.nomor_dipa?.toLowerCase() || '';
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [allDipa, search, status, tahun, sortField, sortDirection]);

    // Client-side pagination
    const totalPages = Math.ceil(filteredAndSortedDipa.length / perPage);
    const paginatedDipa = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedDipa.slice(start, end);
    }, [filteredAndSortedDipa, currentPage, perPage]);

    // Reset to page 1 when filters change
    useEffect(() => {
        setCurrentPage(1);
    }, [search, status, tahun]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            },
        });
    };

    const handleSort = (field: 'nomor_dipa' | 'tahun' | 'tanggal_dipa') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const SortIcon = ({
        field,
    }: {
        field: 'nomor_dipa' | 'tahun' | 'tanggal_dipa';
    }) => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? (
            <ChevronUp className="h-4 w-4" />
        ) : (
            <ChevronDown className="h-4 w-4" />
        );
    };

    // Fungsi untuk reset filter
    const handleReset = () => {
        setSearch('');
        setStatus('');
        setTahun('');
        setCurrentPage(1);
    };

    const handleDeleteClick = (dipa: Dipa) => {
        setSelectedDipa(dipa);
        setShowDeleteDialog(true);
    };

    const handleDelete = () => {
        if (!selectedDipa) return;

        setProcessing(true);
        router.delete(`/dipa/${selectedDipa.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setShowDeleteDialog(false);
                setSelectedDipa(null);
            },
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="DIPA" />

            <div className="space-y-6">
                <PageHeader
                    title="DIPA"
                    description="Kelola data DIPA (Daftar Isian Pelaksanaan Anggaran)"
                >
                    {!isPJ && (
                        <Button asChild size="sm" className="gap-2">
                            <Link href="/dipa/create">
                                <Plus className="h-4 w-4" />
                                Tambah DIPA
                            </Link>
                        </Button>
                    )}
                </PageHeader>

                <ContentCard>
                    {/* Search and Filter */}
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Cari nomor DIPA atau tahun..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 pl-10"
                                />
                            </div>
                        </div>

                        <Select
                            value={tahun || 'all'}
                            onValueChange={(value) =>
                                setTahun(value === 'all' ? '' : value)
                            }
                        >
                            <SelectTrigger className="h-10 w-full sm:w-[150px]">
                                <SelectValue placeholder="Semua Tahun" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Tahun</SelectItem>
                                {tahunOptions.map((year) => (
                                    <SelectItem
                                        key={year}
                                        value={year.toString()}
                                    >
                                        {year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={status || 'all'}
                            onValueChange={(value) =>
                                setStatus(value === 'all' ? '' : value)
                            }
                        >
                            <SelectTrigger className="h-10 w-full sm:w-[150px]">
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
                                filteredAndSortedDipa.length,
                            )}{' '}
                            dari {filteredAndSortedDipa.length} data
                            {filteredAndSortedDipa.length !== allDipa.length &&
                                ` (difilter dari ${allDipa.length} total)`}
                        </p>
                        <Button
                            type="button"
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
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('nomor_dipa')}
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Nomor DIPA
                                            <SortIcon field="nomor_dipa" />
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('tahun')}
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Tahun
                                            <SortIcon field="tahun" />
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('tanggal_dipa')
                                        }
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Tanggal DIPA
                                            <SortIcon field="tanggal_dipa" />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                        Status
                                    </th>
                                    {!isPJ && (
                                        <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                            Aksi
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginatedDipa.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 4 : 5}
                                            className="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            {filteredAndSortedDipa.length ===
                                                0 && allDipa.length > 0
                                                ? 'Tidak ada data yang sesuai dengan filter'
                                                : 'Tidak ada data'}
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedDipa.map((dipa) => (
                                        <tr
                                            key={dipa.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-3 text-sm font-medium whitespace-nowrap">
                                                {dipa.nomor_dipa}
                                            </td>
                                            <td className="px-3 py-3 text-center text-sm font-semibold whitespace-nowrap">
                                                {dipa.tahun}
                                            </td>
                                            <td className="px-3 py-3 text-sm whitespace-nowrap">
                                                {formatDate(dipa.tanggal_dipa)}
                                            </td>
                                            <td className="px-3 py-3 text-center">
                                                <StatusBadge
                                                    status={
                                                        dipa.is_active
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
                                                                href={`/dipa/${dipa.id}/edit`}
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
                                                                    dipa,
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
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-neutral-700 dark:text-neutral-300">
                                Halaman {currentPage} dari {totalPages}
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage(currentPage - 1)
                                    }
                                    disabled={currentPage === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                {Array.from(
                                    { length: totalPages },
                                    (_, i) => i + 1,
                                ).map((page) => (
                                    <Button
                                        key={page}
                                        type="button"
                                        variant={
                                            currentPage === page
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() => setCurrentPage(page)}
                                    >
                                        {page}
                                    </Button>
                                ))}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage(currentPage + 1)
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
                        Apakah Anda yakin ingin menghapus DIPA{' '}
                        <span className="font-semibold">
                            {selectedDipa?.nomor_dipa}
                        </span>{' '}
                        tahun{' '}
                        <span className="font-semibold">
                            {selectedDipa?.tahun}
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
