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
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Penandatangan', href: '/penandatangan' },
];

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
        links: any[];
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

export default function Index({
    PenandatanganList,
    filters,
}: PenandatanganIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';
    const initialFilters = filters.decrypted || {};

    const decryptedPenandatangan = useDecryptedData<Penandatangan>(
        PenandatanganList.encrypted,
    );

    const [search, setSearch] = useState(initialFilters.search || '');
    const [status, setStatus] = useState(initialFilters.status || '');
    const [jenis, setJenis] = useState(initialFilters.jenis || '');
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedPenandatangan, setSelectedPenandatangan] =
        useState<Penandatangan | null>(null);
    const [processing, setProcessing] = useState(false);

    // Auto-filter with debounce for search input
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            applyFilter();
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [search]);

    // Auto-filter immediately for dropdowns
    useEffect(() => {
        applyFilter();
    }, [status, jenis]);

    const applyFilter = () => {
        const filterParams = { search, status, jenis };
        const encryptedFilters = encryptFilters(filterParams);

        router.post(
            '/penandatangan',
            { encrypted_filters: encryptedFilters },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
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
        router.post(
            '/penandatangan',
            { encrypted_filters: encryptFilters({}) },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
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
                    {!isPJ && (
                        <Button asChild size="sm" className="gap-2">
                            <Link href="/penandatangan/create">
                                <Plus className="h-4 w-4" />
                                Tambah Penandatangan
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
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-3 py-3 text-center text-sm font-medium">
                                        Nama
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-medium whitespace-nowrap">
                                        NIP
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-medium whitespace-nowrap">
                                        Jenis
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-medium">
                                        Jabatan
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-medium whitespace-nowrap">
                                        Periode Mulai
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-medium whitespace-nowrap">
                                        Periode Selesai
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-medium whitespace-nowrap">
                                        Status
                                    </th>
                                    {!isPJ && (
                                        <th className="px-3 py-3 text-center text-sm font-medium whitespace-nowrap">
                                            Aksi
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {decryptedPenandatangan.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 7 : 8}
                                            className="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Tidak ada data
                                        </td>
                                    </tr>
                                ) : (
                                    decryptedPenandatangan.map(
                                        (Penandatangan) => (
                                            <tr
                                                key={Penandatangan.id}
                                                className="hover:bg-muted/50"
                                            >
                                                <td className="px-3 py-3 text-sm font-medium">
                                                    <div
                                                        className="max-w-xs truncate"
                                                        title={
                                                            Penandatangan.nama
                                                        }
                                                    >
                                                        {Penandatangan.nama}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3 text-sm whitespace-nowrap">
                                                    {Penandatangan.nip || '-'}
                                                </td>
                                                <td className="px-3 py-3 text-center text-sm whitespace-nowrap">
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
                                                            : 'PPK (SPK/BAST)'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-3 text-sm">
                                                    <div
                                                        className="max-w-xs"
                                                        title={
                                                            Penandatangan.jabatan
                                                        }
                                                    >
                                                        {Penandatangan.jabatan}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3 text-center text-sm whitespace-nowrap">
                                                    {formatDate(
                                                        Penandatangan.periode_mulai,
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-center text-sm whitespace-nowrap">
                                                    {formatDate(
                                                        Penandatangan.periode_selesai,
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-center">
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
                    {PenandatanganList.links.length > 3 && (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            {PenandatanganList.links.map(
                                (link: any, index: number) => {
                                    const isFirst =
                                        link.label.includes('Previous');
                                    const isLast = link.label.includes('Next');

                                    return (
                                        <Button
                                            key={index}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            disabled={!link.url || processing}
                                            onClick={() => {
                                                if (link.url) {
                                                    router.visit(link.url);
                                                }
                                            }}
                                        >
                                            {isFirst ? (
                                                <ChevronLeft className="h-4 w-4" />
                                            ) : isLast ? (
                                                <ChevronRight className="h-4 w-4" />
                                            ) : (
                                                <span
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                />
                                            )}
                                        </Button>
                                    );
                                },
                            )}
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
