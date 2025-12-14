import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, Eye, Pencil, X, Check, Send } from 'lucide-react';
import { useState } from 'react';
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
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tahun_anggaran: number;
    pagu_anggaran: number | null;
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
    const isPJ = auth.activeRole?.name === 'pj';
    
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [showSubmitDialog, setShowSubmitDialog] = useState(false);
    const [showApproveDialog, setShowApproveDialog] = useState(false);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [rejectNotes, setRejectNotes] = useState('');
    const [selectedKegiatan, setSelectedKegiatan] = useState<Kegiatan | null>(null);
    const [processing, setProcessing] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/kegiatan',
            { search, status },
            { preserveState: true, replace: true }
        );
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        router.get('/kegiatan', {}, { preserveState: true });
    };

    const getStatusBadge = (status: string) => {
        const badges: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400',
            diajukan: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            divalidasi: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            aktif: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            selesai: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            dibatalkan: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        };
        return badges[status] || badges.draft;
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            draft: 'Draft',
            diajukan: 'Diajukan',
            divalidasi: 'Divalidasi',
            aktif: 'Aktif',
            selesai: 'Selesai',
            dibatalkan: 'Dibatalkan',
        };
        return labels[status] || status;
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
               (auth.user.active_role === 'ketua_tim' && kegiatan.ketua_tim.id === auth.user.id && kegiatan.status === 'draft');
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
                    {!isPJ && (
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
                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kegiatan..."
                                    className="h-10 pl-10"
                                />
                            </div>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="h-10 rounded-lg border border-neutral-300 px-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                            >
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="divalidasi">Divalidasi</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                            <div className="flex gap-2">
                                <Button type="submit" className="flex-1 gap-2">
                                    <Search className="h-4 w-4" />
                                    Filter
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleReset}
                                    className="flex-1 gap-2"
                                >
                                    <X className="h-4 w-4" />
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </form>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Kode
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Nama Kegiatan
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Tahun
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Pagu Anggaran
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Ketua Tim
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    <th className="px-6 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
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
                                            <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {kegiatan.kode_kegiatan}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.nama_kegiatan}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {formatCurrency(kegiatan.pagu_anggaran)}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.ketua_tim.name}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${getStatusBadge(kegiatan.status)}`}
                                                >
                                                    {getStatusLabel(kegiatan.status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-center gap-2">
                                                    {!isPJ && canSubmit(kegiatan) && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleSubmitClick(kegiatan)}
                                                            className="h-8 gap-1.5 border-blue-200 text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-blue-950 dark:hover:text-blue-300"
                                                        >
                                                            <Send className="h-3.5 w-3.5" />
                                                            <span className="sr-only sm:not-sr-only">Ajukan</span>
                                                        </Button>
                                                    )}
                                                    {!isPJ && canApprove(kegiatan) && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleApproveClick(kegiatan)}
                                                            className="h-8 gap-1.5 border-green-200 text-green-600 hover:bg-green-50 hover:text-green-700 dark:border-green-800 dark:text-green-400 dark:hover:bg-green-950 dark:hover:text-green-300"
                                                        >
                                                            <Check className="h-3.5 w-3.5" />
                                                            <span className="sr-only sm:not-sr-only">Setujui</span>
                                                        </Button>
                                                    )}
                                                    {!isPJ && canReject(kegiatan) && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleRejectClick(kegiatan)}
                                                            className="h-8 gap-1.5 border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                                                        >
                                                            <X className="h-3.5 w-3.5" />
                                                            <span className="sr-only sm:not-sr-only">Tolak</span>
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {!isPJ && canEdit(kegiatan) && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <Link href={`/kegiatan/${kegiatan.hashed_id}/edit`}>
                                                                <Pencil className="h-4 w-4" />
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
                            {kegiatans.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.get(link.url)}
                                    disabled={!link.url}
                                    className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                        link.active
                                            ? 'bg-blue-600 text-white shadow-sm'
                                            : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                    } ${!link.url && 'cursor-not-allowed opacity-50'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
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
                                <textarea
                                    id="catatan"
                                    className="min-h-[100px] w-full rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-neutral-800 dark:bg-neutral-950"
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
