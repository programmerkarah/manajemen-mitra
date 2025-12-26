import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { StatusBadge } from '@/components/status-badge';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, Eye, Pencil, X, Check, Send, ChevronLeft, ChevronRight, Filter, RotateCcw } from 'lucide-react';
import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kegiatan', href: '/kegiatan' },
];

interface User {
    id: number;
    name: string;
    email: string;
}

interface Kegiatan {
    pj_lainnya: any;
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tahun_anggaran: number;
    pagu_pencacahan: number | null;
    status: string;
    ketua_tim: User;
}

interface KegiatanIndexProps {
    kegiatans: {
        data: Kegiatan[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

export default function Index({ kegiatans, filters }: KegiatanIndexProps) {
    const { auth } = usePage<SharedData>().props;
    // isPJ: true if user is pj (pj_lainnya), false otherwise
    const isPJ = auth.activeRole?.name === 'pj';
    // isKetuaTimLainnya: true if user is pj_lainnya (ketua tim lainnya)
    const isKetuaTimLainnya = auth.user.active_role === 'pj';
    
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [showSubmitDialog, setShowSubmitDialog] = useState(false);
    const [showApproveDialog, setShowApproveDialog] = useState(false);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [rejectNotes, setRejectNotes] = useState('');
    const [selectedKegiatan, setSelectedKegiatan] = useState<Kegiatan | null>(null);
    const [processing, setProcessing] = useState(false);

    // Auto-filter with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            router.get(
                '/kegiatan',
                { search, status },
                { 
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search, status]);

    const handleReset = () => {
        setSearch('');
        setStatus('');
        router.get(
            '/kegiatan',
            {},
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const formatCurrency = (value: number | null | undefined) => {
        if (!value || isNaN(value)) return 'Rp 0'
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value)
    }

    const handleSubmitClick = (kegiatan: Kegiatan) => {
        setSelectedKegiatan(kegiatan)
        setShowSubmitDialog(true)
    }

    const handleSubmit = () => {
        if (!selectedKegiatan) return
        
        setProcessing(true)
        router.post(`/kegiatan/${selectedKegiatan.hashed_id}/submit`, {}, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false)
                setShowSubmitDialog(false)
                setSelectedKegiatan(null)
            }
        })
    }

    const handleApproveClick = (kegiatan: Kegiatan) => {
        setSelectedKegiatan(kegiatan)
        setShowApproveDialog(true)
    }

    const handleApprove = () => {
        if (!selectedKegiatan) return
        
        setProcessing(true)
        router.post(`/kegiatan/${selectedKegiatan.hashed_id}/approve`, {}, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false)
                setShowApproveDialog(false)
                setSelectedKegiatan(null)
            }
        })
    }

    const handleRejectClick = (kegiatan: Kegiatan) => {
        setSelectedKegiatan(kegiatan)
        setShowRejectDialog(true)
    }

    const handleReject = () => {
        if (!selectedKegiatan) return
        
        setProcessing(true)
        router.post(`/kegiatan/${selectedKegiatan.hashed_id}/reject`, {
            catatan: rejectNotes
        }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false)
                setShowRejectDialog(false)
                setRejectNotes('')
                setSelectedKegiatan(null)
            }
        })
    }

    const canEdit = (kegiatan: Kegiatan) => {
        if (!auth.user.active_role) return false;
        // Only allow editing draft or divalidasi status
        if (!['draft', 'divalidasi'].includes(kegiatan.status)) return false;
        
        return auth.user.active_role === 'admin' || 
               auth.user.active_role === 'operator' || 
               (auth.user.active_role === 'ketua_tim' && kegiatan.ketua_tim.id === auth.user.id && kegiatan.status === 'draft' || kegiatan.status === 'divalidasi') ||
               (auth.user.active_role === 'pj' && kegiatan.pj_lainnya?.id === auth.user.id && kegiatan.status === 'draft' || kegiatan.status === 'divalidasi'); 
    }

    const canSubmit = (kegiatan: Kegiatan) => {
        if (!auth.user.active_role) return false;
        return kegiatan.status === 'draft' && (
            auth.user.active_role === 'admin' || 
            auth.user.active_role === 'operator' || 
            kegiatan.ketua_tim.id === auth.user.id
        )
    }

    const canApprove = (kegiatan: Kegiatan) => {
        if (!auth.user.active_role) return false;
        return (auth.user.active_role === 'admin' || auth.user.active_role === 'approver') &&
               (kegiatan.status === 'draft' || kegiatan.status === 'diajukan')
    }

    const canReject = (kegiatan: Kegiatan) => {
        return canApprove(kegiatan)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kegiatan" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Kegiatan"
                    description="Kelola kegiatan dan anggaran"
                >
                    {(!isPJ || isKetuaTimLainnya) && (
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
                                <Label htmlFor="search" className="text-base font-semibold">Cari Kegiatan</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-400" />
                                    <Input
                                        id="search"
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Cari nama atau kode kegiatan..."
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="status" className="text-base font-semibold">Status</Label>
                                <select
                                    id="status"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    className="h-11 w-full rounded-lg border-2 border-neutral-300 px-4 text-base font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="divalidasi">Divalidasi</option>
                                    <option value="selesai">Selesai</option>
                                </select>
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
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Kode
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Nama Kegiatan
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Tahun
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Pagu Anggaran
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Ketua Tim
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {kegiatans.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Search className="h-8 w-8 text-neutral-400" />
                                                <p>Tidak ada kegiatan yang ditemukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    kegiatans.data.map((kegiatan) => (
                                        <tr
                                            key={kegiatan.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="whitespace-nowrap px-3 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {kegiatan.kode_kegiatan}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                <div className="max-w-md">
                                                    {kegiatan.nama_kegiatan}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                {formatCurrency(kegiatan.pagu_pencacahan)}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                <div className="max-w-xs truncate" title={kegiatan.ketua_tim.name}>
                                                    {kegiatan.ketua_tim.name}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge status={kegiatan.status} />
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex items-center justify-center gap-2">
                                                    {!isPJ && canSubmit(kegiatan) && (
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            className="gap-2 bg-blue-600 hover:bg-blue-700"
                                                            onClick={() => handleSubmitClick(kegiatan)}
                                                        >
                                                            <Send className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">Ajukan</span>
                                                        </Button>
                                                    )}
                                                    {!isPJ && canApprove(kegiatan) && (
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            className="gap-2 bg-green-600 hover:bg-green-700"
                                                            onClick={() => handleApproveClick(kegiatan)}
                                                        >
                                                            <Check className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">Setujui</span>
                                                        </Button>
                                                    )}
                                                    {!isPJ && canReject(kegiatan) && (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            className="gap-2"
                                                            onClick={() => handleRejectClick(kegiatan)}
                                                        >
                                                            <X className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">Tolak</span>
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                        className="gap-2"
                                                    >
                                                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                                            <Eye className="h-4 w-4" />
                                                            <span className="sr-only sm:not-sr-only">Detail</span>
                                                        </Link>
                                                    </Button>
                                                    {!isPJ && canEdit(kegiatan) && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                            className="gap-2"
                                                        >
                                                            <Link href={`/kegiatan/${kegiatan.hashed_id}/edit`}>
                                                                <Pencil className="h-4 w-4" />
                                                                <span className="sr-only sm:not-sr-only">Edit</span>
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
                    {kegiatans.links && (
                        <div className="flex items-center justify-center gap-1 border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                            {kegiatans.links.map((link, index) => {
                                const isFirst = link.label.includes('Previous');
                                const isLast = link.label.includes('Next');
                                
                                return (
                                    <button
                                        key={index}
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                            link.active
                                                ? 'bg-blue-600 text-white shadow-sm'
                                                : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                        } ${!link.url && 'cursor-not-allowed opacity-50'}`}
                                    >
                                        {isFirst ? (
                                            <ChevronLeft className="h-4 w-4" />
                                        ) : isLast ? (
                                            <ChevronRight className="h-4 w-4" />
                                        ) : (
                                            link.label
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </ContentCard>

                {/* Submit Dialog */}
                <Dialog open={showSubmitDialog} onOpenChange={setShowSubmitDialog}>
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
                                Kegiatan akan dikirim ke Admin/Approver untuk ditinjau.
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowSubmitDialog(false)
                                    setSelectedKegiatan(null)
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
                                {processing ? 'Memproses...' : 'Ajukan Kegiatan'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Approve Dialog */}
                <Dialog open={showApproveDialog} onOpenChange={setShowApproveDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Setujui Kegiatan</DialogTitle>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Apakah Anda yakin ingin menyetujui kegiatan{' '}
                                <span className="font-semibold text-neutral-900 dark:text-white">
                                    {selectedKegiatan?.nama_kegiatan}
                                </span>?
                            </p>
                            <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-500">
                                Kegiatan akan berstatus divalidasi dan dapat dikelola rate honor serta alokasi petugas.
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowApproveDialog(false)
                                    setSelectedKegiatan(null)
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
                                {processing ? 'Memproses...' : 'Setujui Kegiatan'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Reject Dialog */}
                <Dialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tolak Kegiatan</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="catatan">Catatan Penolakan</Label>
                                <Textarea
                                    id="catatan"
                                    className="min-h-[100px]"
                                    value={rejectNotes}
                                    onChange={(e) => setRejectNotes(e.target.value)}
                                    placeholder="Masukkan alasan penolakan..."
                                    disabled={processing}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowRejectDialog(false)
                                    setRejectNotes('')
                                    setSelectedKegiatan(null)
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
