import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Kepala BPS', href: '/kepala-bps' },
];

interface KepalaBps {
    id: number;
    nama: string;
    nip: string | null;
    jabatan: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface KepalaBpsIndexProps {
    kepalaBpsList: {
        data: KepalaBps[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

export default function Index({ kepalaBpsList, filters }: KepalaBpsIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedKepalaBps, setSelectedKepalaBps] = useState<KepalaBps | null>(null);
    const [processing, setProcessing] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/kepala-bps',
            { search, status },
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const handleDeleteClick = (kepalaBps: KepalaBps) => {
        setSelectedKepalaBps(kepalaBps);
        setShowDeleteDialog(true);
    };

    const handleDelete = () => {
        if (!selectedKepalaBps) return;

        setProcessing(true);
        router.delete(`/kepala-bps/${selectedKepalaBps.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setShowDeleteDialog(false);
                setSelectedKepalaBps(null);
            },
        });
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kepala BPS" />

            <div className="space-y-6">
                <PageHeader
                    title="Kepala BPS"
                    description="Kelola data Kepala BPS untuk dokumen SK"
                >
                    {!isPJ && (
                        <Button asChild size="sm" className="gap-2">
                            <Link href="/kepala-bps/create">
                                <Plus className="h-4 w-4" />
                                Tambah Kepala BPS
                            </Link>
                        </Button>
                    )}
                </PageHeader>

                <ContentCard>
                    {/* Search and Filter */}
                    <form onSubmit={handleSearch} className="mb-6 flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Cari nama, NIP, atau jabatan..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 pl-10"
                                />
                            </div>
                        </div>

                        <Select value={status || 'all'} onValueChange={(value) => setStatus(value === 'all' ? '' : value)}>
                            <SelectTrigger className="h-10 w-full sm:w-[200px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">Non-Aktif</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button type="submit" className="h-10">
                            <Search className="mr-2 h-4 w-4" />
                            Cari
                        </Button>
                    </form>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-sm font-medium">Nama</th>
                                    <th className="px-4 py-3 text-left text-sm font-medium">NIP</th>
                                    <th className="px-4 py-3 text-left text-sm font-medium">Jabatan</th>
                                    <th className="px-4 py-3 text-left text-sm font-medium">Periode Mulai</th>
                                    <th className="px-4 py-3 text-left text-sm font-medium">Periode Selesai</th>
                                    <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                                    {!isPJ && (
                                        <th className="px-4 py-3 text-center text-sm font-medium">Aksi</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {kepalaBpsList.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 6 : 7}
                                            className="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Tidak ada data
                                        </td>
                                    </tr>
                                ) : (
                                    kepalaBpsList.data.map((kepalaBps) => (
                                        <tr key={kepalaBps.id} className="hover:bg-muted/50">
                                            <td className="px-4 py-3 text-sm font-medium">
                                                {kepalaBps.nama}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {kepalaBps.nip || '-'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {kepalaBps.jabatan}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {formatDate(kepalaBps.periode_mulai)}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {formatDate(kepalaBps.periode_selesai)}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                                                        kepalaBps.is_active
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                                    }`}
                                                >
                                                    {kepalaBps.is_active ? 'Aktif' : 'Non-Aktif'}
                                                </span>
                                            </td>
                                            {!isPJ && (
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                            className="h-8 gap-1.5"
                                                        >
                                                            <Link href={`/kepala-bps/${kepalaBps.id}/edit`}>
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                <span className="sr-only sm:not-sr-only">
                                                                    Edit
                                                                </span>
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDeleteClick(kepalaBps)}
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
                    {kepalaBpsList.links.length > 3 && (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            {kepalaBpsList.links.map((link: any, index: number) => {
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
                        Apakah Anda yakin ingin menghapus data Kepala BPS{' '}
                        <span className="font-semibold">{selectedKepalaBps?.nama}</span>?
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
