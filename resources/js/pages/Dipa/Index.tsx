import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, Pencil, Trash2, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useState, useEffect } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { StatusBadge } from '@/components/status-badge';
import { encryptFilters } from '@/utils/encryption';
import { useDecryptedData } from '@/hooks/useDecryptedData';

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
        links: any[];
    };
    tahunOptions: number[];
    filters: {
        encrypted?: string
        decrypted?: {
            search?: string
            status?: string
            tahun?: string
        }
    };
}

export default function Index({ dipaList, tahunOptions, filters }: DipaIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';
    const initialFilters = filters.decrypted || {};
    
    const decryptedDipa = useDecryptedData<Dipa>(dipaList.encrypted);
    
    const [search, setSearch] = useState(initialFilters.search || '');
    const [status, setStatus] = useState(initialFilters.status || '');
    const [tahun, setTahun] = useState(initialFilters.tahun || '');
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedDipa, setSelectedDipa] = useState<Dipa | null>(null);
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
    }, [status, tahun]);

    const applyFilter = () => {
        const filterParams = { search, status, tahun };
        const encryptedFilters = encryptFilters(filterParams);

        router.post(
            '/dipa',
            { encrypted_filters: encryptedFilters },
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    // Fungsi untuk reset filter
    const handleReset = () => {
        setSearch('');
        setStatus('');
        setTahun('');
        router.post('/dipa', { encrypted_filters: encryptFilters({}) }, {
            preserveState: true,
            preserveScroll: true,
        });
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
            year: 'numeric'
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
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Cari nomor DIPA atau tahun..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 pl-10"
                                />
                            </div>
                        </div>

                        <Select value={tahun || 'all'} onValueChange={(value) => setTahun(value === 'all' ? '' : value)}>
                            <SelectTrigger className="h-10 w-full sm:w-[150px]">
                                <SelectValue placeholder="Semua Tahun" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Tahun</SelectItem>
                                {tahunOptions.map((year) => (
                                    <SelectItem key={year} value={year.toString()}>
                                        {year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select value={status || 'all'} onValueChange={(value) => setStatus(value === 'all' ? '' : value)}>
                            <SelectTrigger className="h-10 w-full sm:w-[150px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">Non-Aktif</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button onClick={handleReset} variant="outline" className="h-10">
                            <X className="mr-2 h-4 w-4" />
                            Reset
                        </Button>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="whitespace-nowrap px-3 py-3 text-left text-sm font-medium">Nomor DIPA</th>
                                    <th className="whitespace-nowrap px-3 py-3 text-center text-sm font-medium">Tahun</th>
                                    <th className="whitespace-nowrap px-3 py-3 text-left text-sm font-medium">Tanggal DIPA</th>
                                    <th className="whitespace-nowrap px-3 py-3 text-center text-sm font-medium">Status</th>
                                    {!isPJ && (
                                        <th className="whitespace-nowrap px-3 py-3 text-center text-sm font-medium">Aksi</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {decryptedDipa.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 4 : 5}
                                            className="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Tidak ada data
                                        </td>
                                    </tr>
                                ) : (
                                    decryptedDipa.map((dipa) => (
                                        <tr key={dipa.id} className="hover:bg-muted/50">
                                            <td className="whitespace-nowrap px-3 py-3 text-sm font-medium">
                                                {dipa.nomor_dipa}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-center text-sm font-semibold">
                                                {dipa.tahun}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-sm">
                                                {formatDate(dipa.tanggal_dipa)}
                                            </td>
                                            <td className="px-3 py-3 text-center">
                                                <StatusBadge status={dipa.is_active ? 'aktif' : 'nonaktif'} />
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
                                                            <Link href={`/dipa/${dipa.id}/edit`}>
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                <span className="sr-only sm:not-sr-only">
                                                                    Edit
                                                                </span>
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDeleteClick(dipa)}
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
                    {dipaList.links.length > 3 && (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            {dipaList.links.map((link: any, index: number) => {
                                const isFirst = link.label.includes('Previous');
                                const isLast = link.label.includes('Next');
                                
                                return (
                                    <Button
                                        key={index}
                                        variant={link.active ? 'default' : 'outline'}
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
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                );
                            })}
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
                        <span className="font-semibold">{selectedDipa?.nomor_dipa}</span> tahun{' '}
                        <span className="font-semibold">{selectedDipa?.tahun}</span>?
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
