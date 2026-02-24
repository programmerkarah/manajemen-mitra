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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Copy,
    Eye,
    Pencil,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    Send,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kegiatan', href: '/kegiatan' },
];

interface User {
    id: number;
    name: string;
    email: string;
}

interface Kegiatan {
    pj_lainnya: User | null;
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tahun_anggaran: number;
    pagu_pencacahan: number | null;
    pagu_listing: number | null;
    has_listing_updating: boolean;
    status: string;
    ketua_tim: User;
}

interface KegiatanIndexProps {
    kegiatans: {
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
        };
    };
}

export default function Index({ kegiatans }: KegiatanIndexProps) {
    const { auth } = usePage<SharedData>().props;

    // Check if user can create kegiatan based on active role
    const canCreate =
        auth.activeRole?.name &&
        ['admin', 'operator', 'ketua_tim'].includes(auth.activeRole.name);

    // Decrypt data once with memoization
    const allKegiatans = useDecryptedData<Kegiatan>(kegiatans.encrypted);

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [sortField, setSortField] = useState<
        'nama_kegiatan' | 'tahun_anggaran' | 'status'
    >('nama_kegiatan');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const prevFiltersRef = useRef({ search, status });
    const [showSubmitDialog, setShowSubmitDialog] = useState(false);
    const [showApproveDialog, setShowApproveDialog] = useState(false);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [rejectNotes, setRejectNotes] = useState('');
    const [selectedKegiatan, setSelectedKegiatan] = useState<Kegiatan | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    // Client-side filtering and sorting
    const filteredAndSortedKegiatans = useMemo(() => {
        let result: Kegiatan[] = [...allKegiatans];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (item: Kegiatan) =>
                    item.nama_kegiatan?.toLowerCase().includes(query) ||
                    item.kode_kegiatan?.toLowerCase().includes(query) ||
                    item.ketua_tim?.name?.toLowerCase().includes(query),
            );
        }

        // Filter by status
        if (status) {
            result = result.filter((item: Kegiatan) => item.status === status);
        }

        // Sort
        result.sort((a: Kegiatan, b: Kegiatan) => {
            let aVal: string | number = '';
            let bVal: string | number = '';
            switch (sortField) {
                case 'tahun_anggaran':
                    aVal = a.tahun_anggaran || 0;
                    bVal = b.tahun_anggaran || 0;
                    break;
                case 'status':
                    aVal = a.status?.toLowerCase() || '';
                    bVal = b.status?.toLowerCase() || '';
                    break;
                case 'nama_kegiatan':
                default:
                    aVal = a.nama_kegiatan?.toLowerCase() || '';
                    bVal = b.nama_kegiatan?.toLowerCase() || '';
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [allKegiatans, search, status, sortField, sortDirection]);

    // Client-side pagination
    const totalPages = Math.ceil(filteredAndSortedKegiatans.length / perPage);
    const paginatedKegiatans = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedKegiatans.slice(start, end);
    }, [filteredAndSortedKegiatans, currentPage, perPage]);

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

    const handleSort = (
        field: 'nama_kegiatan' | 'tahun_anggaran' | 'status',
    ) => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const renderSortIcon = (
        field: 'nama_kegiatan' | 'tahun_anggaran' | 'status',
    ) => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? (
            <ChevronUp className="h-4 w-4" />
        ) : (
            <ChevronDown className="h-4 w-4" />
        );
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setCurrentPage(1);
    };

    const formatCurrency = (value: number | null | undefined) => {
        if (!value || isNaN(value)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    const handleSubmitClick = (kegiatan: Kegiatan) => {
        setSelectedKegiatan(kegiatan);
        setShowSubmitDialog(true);
    };

    const handleSubmit = () => {
        if (!selectedKegiatan) return;

        setProcessing(true);
        router.post(
            `/kegiatan/${selectedKegiatan.hashed_id}/submit`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setShowSubmitDialog(false);
                    setSelectedKegiatan(null);
                },
            },
        );
    };

    const handleApproveClick = (kegiatan: Kegiatan) => {
        setSelectedKegiatan(kegiatan);
        setShowApproveDialog(true);
    };

    const handleApprove = () => {
        if (!selectedKegiatan) return;

        setProcessing(true);
        router.post(
            `/kegiatan/${selectedKegiatan.hashed_id}/approve`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setShowApproveDialog(false);
                    setSelectedKegiatan(null);
                },
            },
        );
    };

    const handleRejectClick = (kegiatan: Kegiatan) => {
        setSelectedKegiatan(kegiatan);
        setShowRejectDialog(true);
    };

    const handleReject = () => {
        if (!selectedKegiatan) return;

        setProcessing(true);
        router.post(
            `/kegiatan/${selectedKegiatan.hashed_id}/reject`,
            {
                catatan: rejectNotes,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setShowRejectDialog(false);
                    setRejectNotes('');
                    setSelectedKegiatan(null);
                },
            },
        );
    };

    const canEdit = (kegiatan: Kegiatan) => {
        if (!auth.user.active_role) return false;
        // Only allow editing draft or divalidasi status
        if (!['draft', 'divalidasi'].includes(kegiatan.status)) return false;

        // Admin and operator can edit draft or divalidasi
        if (
            auth.user.active_role === 'admin' ||
            auth.user.active_role === 'operator'
        ) {
            return true;
        }

        // Ketua tim can edit if they own the kegiatan (as ketua_tim or pj_lainnya)
        if (auth.user.active_role === 'ketua_tim') {
            return (
                kegiatan.ketua_tim.id === auth.user.id ||
                kegiatan.pj_lainnya?.id === auth.user.id
            );
        }

        // PJ role (pj_lainnya) can edit if they're assigned
        if (auth.user.active_role === 'pj') {
            return kegiatan.pj_lainnya?.id === auth.user.id;
        }

        return false;
    };

    const canSubmit = (kegiatan: Kegiatan) => {
        if (!auth.user.active_role) return false;
        if (kegiatan.status !== 'draft') return false;

        // Admin and operator can always submit
        if (
            auth.user.active_role === 'admin' ||
            auth.user.active_role === 'operator'
        ) {
            return true;
        }

        // Ketua tim can submit if they own the kegiatan (as ketua_tim, not as pj_lainnya)
        if (auth.user.active_role === 'ketua_tim') {
            return kegiatan.ketua_tim.id === auth.user.id;
        }

        return false;
    };

    const canApprove = (kegiatan: Kegiatan) => {
        if (!auth.user.active_role) return false;
        return (
            (auth.user.active_role === 'admin' ||
                auth.user.active_role === 'approver') &&
            (kegiatan.status === 'draft' || kegiatan.status === 'diajukan')
        );
    };

    const canReject = (kegiatan: Kegiatan) => {
        return canApprove(kegiatan);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kegiatan" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Kegiatan"
                    description="Kelola kegiatan dan anggaran"
                >
                    {canCreate && (
                        <Button size="sm" asChild className="gap-2">
                            <Link href="/kegiatan/create">
                                <Plus className="h-4 w-4" />
                                Tambah Kegiatan
                            </Link>
                        </Button>
                    )}
                </PageHeader>

                {/* Filters */}
                <ContentCard>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="space-y-2">
                                <Label
                                    htmlFor="search"
                                    className="text-base font-semibold"
                                >
                                    Cari Kegiatan
                                </Label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        type="text"
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                        placeholder="Cari nama atau kode kegiatan..."
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label
                                    htmlFor="status"
                                    className="text-base font-semibold"
                                >
                                    Status
                                </Label>
                                <Select
                                    value={status || 'all'}
                                    onValueChange={(v) =>
                                        setStatus(v === 'all' ? '' : v)
                                    }
                                >
                                    <SelectTrigger className="h-10 w-full">
                                        <SelectValue placeholder="Semua Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua Status
                                        </SelectItem>
                                        <SelectItem value="draft">
                                            Draft
                                        </SelectItem>
                                        <SelectItem value="divalidasi">
                                            Divalidasi
                                        </SelectItem>
                                        <SelectItem value="selesai">
                                            Selesai
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleReset}
                                    className="gap-2"
                                >
                                    <RotateCcw className="h-5 w-5" />
                                    Reset
                                </Button>
                            </div>
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
                                filteredAndSortedKegiatans.length,
                            )}{' '}
                            dari {filteredAndSortedKegiatans.length} data
                            {filteredAndSortedKegiatans.length !==
                                allKegiatans.length &&
                                ` (difilter dari ${allKegiatans.length} total)`}
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
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Kode
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('nama_kegiatan')
                                        }
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Nama Kegiatan
                                            {renderSortIcon('nama_kegiatan')}
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('tahun_anggaran')
                                        }
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Tahun
                                            {renderSortIcon('tahun_anggaran')}
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Pagu Anggaran
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Ketua Tim
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('status')}
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Status
                                            {renderSortIcon('status')}
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginatedKegiatans.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Search className="h-8 w-8 text-neutral-400" />
                                                <p>
                                                    {filteredAndSortedKegiatans.length ===
                                                        0 &&
                                                    allKegiatans.length > 0
                                                        ? 'Tidak ada data yang sesuai dengan filter'
                                                        : 'Tidak ada kegiatan yang ditemukan'}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedKegiatans.map((kegiatan) => (
                                        <tr
                                            key={kegiatan.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-3 text-sm font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                {kegiatan.kode_kegiatan}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                <div className="max-w-md">
                                                    {kegiatan.nama_kegiatan}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-center text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="px-3 py-3 text-right text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {formatCurrency(
                                                    (Number(
                                                        kegiatan.pagu_pencacahan,
                                                    ) || 0) +
                                                        (Number(
                                                            kegiatan.pagu_listing,
                                                        ) || 0),
                                                )}
                                            </td>
                                            <td className="px-3 py-3 text-center text-sm text-neutral-600 dark:text-neutral-400">
                                                <div
                                                    className="max-w-xs truncate"
                                                    title={
                                                        kegiatan.ketua_tim.name
                                                    }
                                                >
                                                    {kegiatan.ketua_tim.name}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-center">
                                                <StatusBadge
                                                    status={kegiatan.status}
                                                />
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex items-center justify-center gap-2">
                                                    {canSubmit(kegiatan) && (
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            className="gap-2 bg-blue-600 hover:bg-blue-700"
                                                            onClick={() =>
                                                                handleSubmitClick(
                                                                    kegiatan,
                                                                )
                                                            }
                                                        >
                                                            <Send className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">
                                                                Ajukan
                                                            </span>
                                                        </Button>
                                                    )}
                                                    {canApprove(kegiatan) && (
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            className="gap-2 bg-green-600 hover:bg-green-700"
                                                            onClick={() =>
                                                                handleApproveClick(
                                                                    kegiatan,
                                                                )
                                                            }
                                                        >
                                                            <Check className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">
                                                                Setujui
                                                            </span>
                                                        </Button>
                                                    )}
                                                    {canReject(kegiatan) && (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            className="gap-2"
                                                            onClick={() =>
                                                                handleRejectClick(
                                                                    kegiatan,
                                                                )
                                                            }
                                                        >
                                                            <X className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">
                                                                Tolak
                                                            </span>
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                        className="gap-2"
                                                    >
                                                        <Link
                                                            href={`/kegiatan/${kegiatan.hashed_id}`}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">
                                                                Detail
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                    {canEdit(kegiatan) && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                            className="gap-2"
                                                        >
                                                            <Link
                                                                href={`/kegiatan/${kegiatan.hashed_id}/edit`}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                                <span className="sr-only sm:not-sr-only">
                                                                    Edit
                                                                </span>
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {canCreate && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                            className="gap-2"
                                                        >
                                                            <Link
                                                                href={`/kegiatan/${kegiatan.hashed_id}/copy`}
                                                            >
                                                                <Copy className="h-4 w-4" />
                                                                <span className="sr-only sm:not-sr-only">
                                                                    Salin
                                                                </span>
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="flex items-center justify-between border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
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

                {/* Submit Dialog */}
                <Dialog
                    open={showSubmitDialog}
                    onOpenChange={setShowSubmitDialog}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Ajukan Kegiatan</DialogTitle>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Apakah Anda yakin ingin mengajukan kegiatan{' '}
                                <span className="font-semibold text-neutral-900 dark:text-white">
                                    {selectedKegiatan?.nama_kegiatan}
                                </span>{' '}
                                untuk persetujuan?
                            </p>
                            <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-500">
                                Kegiatan akan dikirim ke Admin/Approver untuk
                                ditinjau.
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowSubmitDialog(false);
                                    setSelectedKegiatan(null);
                                }}
                                disabled={processing}
                            >
                                Batal
                            </Button>
                            <Button
                                onClick={handleSubmit}
                                disabled={processing}
                                className="bg-blue-600 hover:bg-blue-700"
                            >
                                {processing
                                    ? 'Memproses...'
                                    : 'Ajukan Kegiatan'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Approve Dialog */}
                <Dialog
                    open={showApproveDialog}
                    onOpenChange={setShowApproveDialog}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Setujui Kegiatan</DialogTitle>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Apakah Anda yakin ingin menyetujui kegiatan{' '}
                                <span className="font-semibold text-neutral-900 dark:text-white">
                                    {selectedKegiatan?.nama_kegiatan}
                                </span>
                                ?
                            </p>
                            <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-500">
                                Kegiatan akan berstatus divalidasi dan dapat
                                dikelola rate honor serta alokasi petugas.
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowApproveDialog(false);
                                    setSelectedKegiatan(null);
                                }}
                                disabled={processing}
                            >
                                Batal
                            </Button>
                            <Button
                                onClick={handleApprove}
                                disabled={processing}
                                className="bg-green-600 hover:bg-green-700"
                            >
                                {processing
                                    ? 'Memproses...'
                                    : 'Setujui Kegiatan'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Reject Dialog */}
                <Dialog
                    open={showRejectDialog}
                    onOpenChange={setShowRejectDialog}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tolak Kegiatan</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="catatan">
                                    Catatan Penolakan
                                </Label>
                                <Textarea
                                    id="catatan"
                                    className="min-h-[100px]"
                                    value={rejectNotes}
                                    onChange={(e) =>
                                        setRejectNotes(e.target.value)
                                    }
                                    placeholder="Masukkan alasan penolakan..."
                                    disabled={processing}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowRejectDialog(false);
                                    setRejectNotes('');
                                    setSelectedKegiatan(null);
                                }}
                                disabled={processing}
                            >
                                Batal
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleReject}
                                disabled={!rejectNotes.trim() || processing}
                            >
                                {processing ? 'Memproses...' : 'Tolak Kegiatan'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
