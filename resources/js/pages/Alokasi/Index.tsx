import { Head, Link, router, usePage } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { StatusBadge } from '@/components/status-badge'
import type { BreadcrumbItem, Kegiatan, SharedData } from '@/types'
import { useState } from 'react'
import { Search, Plus, Send, Edit2, X, RefreshCw, AlertCircle, Copy, Eye, ChevronLeft, ChevronRight, Filter, RotateCcw, Users } from 'lucide-react'

interface AlokasiPeriod {
    kegiatan_id: number
    bulan: string
    tahun: number
    jenis_kegiatan: 'sensus' | 'survei'
    status: 'draft' | 'dikirim' | 'direvisi' | 'dihapus' | 'perubahan'
    jumlah_petugas: number
    total_honor: number
    estimasi_honor: number
    sisa_pagu: number
    pagu_anggaran: number
    latest_created_at: string
    is_latest_periode: boolean
    kegiatan: Kegiatan
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '#' },
];

interface Props {
    alokasi: {
        data: AlokasiPeriod[]
        current_page: number
        last_page: number
        per_page: number
        total: number
        links: Array<{
            url: string | null
            label: string
            active: boolean
        }>
    }
    filters: {
        search?: string
        status?: string
        bulan?: string
    }
    hasKegiatans: boolean
}

export default function Index({ alokasi, filters, hasKegiatans }: Props) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';
    
    const [search, setSearch] = useState(filters.search || '')
    const [status, setStatus] = useState(filters.status || 'all')
    const [bulan, setBulan] = useState(filters.bulan || 'all')

    // Modal states
    const [showKirimModal, setShowKirimModal] = useState(false)
    const [showBatalkanModal, setShowBatalkanModal] = useState(false)
    const [showRevisiModal, setShowRevisiModal] = useState(false)
    const [selectedPeriode, setSelectedPeriode] = useState<{
        kegiatanId: number
        kegiatanHashedId?: string
        bulan: string
        tahun: number
        namaKegiatan?: string
    } | null>(null)

    const bulanOptions = [
        { value: '01', label: 'Januari' },
        { value: '02', label: 'Februari' },
        { value: '03', label: 'Maret' },
        { value: '04', label: 'April' },
        { value: '05', label: 'Mei' },
        { value: '06', label: 'Juni' },
        { value: '07', label: 'Juli' },
        { value: '08', label: 'Agustus' },
        { value: '09', label: 'September' },
        { value: '10', label: 'Oktober' },
        { value: '11', label: 'November' },
        { value: '12', label: 'Desember' },
    ]

    const handleFilter = () => {
        router.get(
            '/alokasi',
            {
                search: search || undefined,
                status: status && status !== 'all' ? status : undefined,
                bulan: bulan && bulan !== 'all' ? bulan : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        )
    }

    const handleReset = () => {
        setSearch('')
        setStatus('all')
        setBulan('all')
        router.get(
            '/alokasi',
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        )
    }

    const handleKirim = (kegiatanHashedId: string, bulan: string, tahun: number, namaKegiatan: string) => {
        setSelectedPeriode({ kegiatanId: 0, bulan, tahun, namaKegiatan, kegiatanHashedId })
        setShowKirimModal(true)
    }

    const confirmKirim = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.post(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}/submit`,
                {},
                {
                    onSuccess: () => {
                        setShowKirimModal(false)
                        setSelectedPeriode(null)
                    },
                }
            )
        }
    }

    const handleBatalkan = (kegiatanHashedId: string, bulan: string, tahun: number, namaKegiatan: string) => {
        setSelectedPeriode({ kegiatanId: 0, bulan, tahun, namaKegiatan, kegiatanHashedId })
        setShowBatalkanModal(true)
    }

    const confirmBatalkan = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.delete(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}`,
                {
                    onSuccess: () => {
                        setShowBatalkanModal(false)
                        setSelectedPeriode(null)
                    },
                }
            )
        }
    }

    const handleRevisi = (kegiatanHashedId: string, bulan: string, tahun: number, namaKegiatan: string) => {
        setSelectedPeriode({ kegiatanId: 0, bulan, tahun, namaKegiatan, kegiatanHashedId })
        setShowRevisiModal(true)
    }

    const confirmRevisi = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.post(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}/revisi`,
                {},
                {
                    onSuccess: () => {
                        setShowRevisiModal(false)
                        setSelectedPeriode(null)
                    },
                }
            )
        }
    }



    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
    }

    const getBulanLabel = (bulan: string | number) => {
        // Convert to string and ensure bulan has leading zero
        const bulanStr = String(bulan).padStart(2, '0')
        const bulanObj = bulanOptions.find(b => b.value === bulanStr)
        return bulanObj?.label || bulanStr
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alokasi petugas" />

            <PageHeader
                title="Alokasi Petugas"
                description="Kelola alokasi petugas untuk setiap kegiatan"
            >
                {(!isPJ && hasKegiatans) && (
                    <Button size="sm" asChild className="gap-2">
                        <Link href="/alokasi/create">
                            <Plus className="h-4 w-4" />
                            Tambah Periode Kegiatan
                        </Link>
                    </Button>
                )}
            </PageHeader>

            {/* Filters */}
            <ContentCard>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="search" className="text-base font-semibold">Cari Kegiatan</Label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-500" />
                            <Input
                                id="search"
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                placeholder="Nama atau kode kegiatan..."
                                className="pl-10"
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="bulan" className="text-base font-semibold">Bulan</Label>
                        <Select
                            value={bulan}
                            onValueChange={(value) => setBulan(value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Bulan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Bulan</SelectItem>
                                {bulanOptions.map((b) => (
                                    <SelectItem key={b.value} value={b.value}>
                                        {b.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="status" className="text-base font-semibold">Status</Label>
                        <Select
                            value={status}
                            onValueChange={(value) => setStatus(value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="dikirim">Terkirim</SelectItem>
                                <SelectItem value="perubahan">Perubahan</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-end gap-2">
                        <Button onClick={handleFilter} className="flex-1 gap-2">
                            <Filter className="h-5 w-5" />
                            Filter
                        </Button>
                        <Button onClick={handleReset} variant="outline" className="gap-2">
                            <RotateCcw className="h-5 w-5" />
                            Reset
                        </Button>
                    </div>
                </div>
            </ContentCard>

            {/* Table */}
            <ContentCard padding="none">
                <div className="overflow-x-auto">
                    <div className="overflow-hidden rounded-2xl">
                        <table className="min-w-full divide-y divide-white/20 dark:divide-neutral-700/30">
                        <thead className="bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Kegiatan
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Bulan
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Estimasi Honor
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Sisa Pagu
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Jumlah Petugas
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-white/10 bg-white/30 dark:divide-neutral-700/20 dark:bg-neutral-800/30 backdrop-blur-sm">
                            {alokasi.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400"
                                    >
                                        Tidak ada data alokasi
                                    </td>
                                </tr>
                            ) : (
                                alokasi.data.map((periode, index) => (
                                    <tr
                                        key={`${periode.kegiatan_id}-${periode.bulan}-${periode.tahun}-${index}`}
                                        className="transition-colors hover:bg-white/50 dark:hover:bg-neutral-800/50"
                                    >
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <div>
                                                <div className="font-medium text-neutral-900 dark:text-white">
                                                    {periode.kegiatan.nama_kegiatan}
                                                </div>
                                                <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {periode.kegiatan.kode_kegiatan}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                            {getBulanLabel(periode.bulan)} {periode.tahun}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-neutral-900 dark:text-white">
                                            {formatCurrency(periode.estimasi_honor)}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <span className={`font-semibold ${
                                                periode.sisa_pagu >= 0 
                                                    ? 'text-green-600 dark:text-green-400' 
                                                    : 'text-red-600 dark:text-red-400'
                                            }`}>
                                                {formatCurrency(periode.sisa_pagu)}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-blue-300/10 backdrop-blur-md px-4 py-2 text-base font-semibold text-blue-900 dark:text-blue-200 shadow-lg">
                                                <Users className="h-5 w-5 shrink-0" strokeWidth={2.5} />
                                                {periode.jumlah_petugas} petugas
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <StatusBadge status={periode.status} />
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <div className="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                    className="gap-2"
                                                    title="Lihat Detail"
                                                >
                                                    <Link href={`/alokasi/periode/${periode.kegiatan.hashed_id}/${periode.tahun}/${periode.bulan}`}>
                                                        <Eye className="h-4 w-4" />
                                                        Detail
                                                    </Link>
                                                </Button>
                                                {!isPJ && periode.status === 'draft' && (
                                                    <>
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            className="gap-2 bg-blue-600 hover:bg-blue-700"
                                                            onClick={() => handleKirim(periode.kegiatan.hashed_id, periode.bulan, periode.tahun, periode.kegiatan.nama_kegiatan)}
                                                        >
                                                            <Send className="h-4 w-4" />
                                                            Kirim
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            asChild
                                                            className="gap-2"
                                                        >
                                                            <Link href={`/alokasi/periode/${periode.kegiatan.hashed_id}/${periode.tahun}/${periode.bulan}/edit`}>
                                                                <Edit2 className="h-4 w-4" />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            asChild
                                                            className="gap-2"
                                                        >
                                                            <Link href={`/alokasi/create?kegiatan_id=${periode.kegiatan.hashed_id}&copy_from_bulan=${periode.bulan}&copy_from_tahun=${periode.tahun}`}>
                                                                <Copy className="h-4 w-4" />
                                                                Salin
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            className="gap-2"
                                                            onClick={() => handleBatalkan(periode.kegiatan.hashed_id, periode.bulan, periode.tahun, periode.kegiatan.nama_kegiatan)}
                                                        >
                                                            <X className="h-4 w-4" />
                                                            Batalkan
                                                        </Button>
                                                    </>
                                                )}
                                                {!isPJ && (periode.status === 'dikirim' || periode.status === 'perubahan') && (
                                                    <>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            asChild
                                                            className="gap-2"
                                                        >
                                                            <Link href={`/alokasi/create?kegiatan_id=${periode.kegiatan.hashed_id}&copy_from_bulan=${periode.bulan}&copy_from_tahun=${periode.tahun}`}>
                                                                <Copy className="h-4 w-4" />
                                                                Salin
                                                            </Link>
                                                        </Button>
                                                        {periode.is_latest_periode && (
                                                            <Button
                                                                size="sm"
                                                                variant="default"
                                                                className="gap-2 bg-purple-600 hover:bg-purple-700"
                                                                onClick={() => handleRevisi(periode.kegiatan.hashed_id, periode.bulan, periode.tahun, periode.kegiatan.nama_kegiatan)}
                                                            >
                                                                <RefreshCw className="h-4 w-4" />
                                                                Revisi
                                                            </Button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                </div>

                {/* Pagination */}
                {alokasi.links && (
                    <div className="flex items-center justify-between border-t border-white/20 px-6 py-4 dark:border-neutral-700/30">
                        <div className="text-sm text-neutral-700 dark:text-neutral-300">
                            Showing {alokasi.data.length} of {alokasi.total} results
                        </div>
                        <div className="flex gap-1">
                            {alokasi.links.map((link, index) => {
                                const isFirst = link.label.includes('Previous');
                                const isLast = link.label.includes('Next');
                                
                                return (
                                    <Button
                                        key={index}
                                        size="sm"
                                        variant={link.active ? 'default' : 'outline'}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.visit(link.url)}
                                    >
                                        {isFirst ? (
                                            <ChevronLeft className="h-4 w-4" />
                                        ) : isLast ? (
                                            <ChevronRight className="h-4 w-4" />
                                        ) : (
                                            link.label
                                        )}
                                    </Button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </ContentCard>

            {/* Modal Kirim */}
            <Dialog open={showKirimModal} onOpenChange={setShowKirimModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-neutral-700/50">
                                <Send className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span>Kirim Alokasi Periode</span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            Alokasi yang dikirim akan digunakan sebagai dasar pembuatan <strong>SK KPA</strong> dan <strong>SPK</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4">
                            <div className="space-y-3 border-y border-white/20 py-4 dark:border-neutral-700/30">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-white/50 dark:bg-neutral-800/60 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">📋</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-600 dark:text-neutral-400">Kegiatan</p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">{selectedPeriode.namaKegiatan}</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-white/50 dark:bg-neutral-800/60 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">📅</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-600 dark:text-neutral-400">Periode</p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(selectedPeriode.bulan)} {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-blue-400/30 bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-blue-300/10 backdrop-blur-xl p-3 shadow-lg">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                                    <div className="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                                        <p className="font-medium">Pastikan data sudah benar:</p>
                                        <ul className="ml-4 list-disc space-y-1">
                                            <li>Data akan digunakan untuk SK KPA</li>
                                            <li>Data akan digunakan untuk SPK</li>
                                            <li>Dapat direvisi jika diperlukan</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowKirimModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Batal
                        </Button>
                        <Button onClick={confirmKirim} className="w-full sm:w-auto">
                            <Send className="mr-2 h-4 w-4" />
                            Ya, Kirim Sekarang
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Batalkan */}
            <Dialog open={showBatalkanModal} onOpenChange={setShowBatalkanModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                            <span className="text-red-600 dark:text-red-400">Batalkan Alokasi Periode</span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            <strong className="text-red-600 dark:text-red-400">Perhatian:</strong> Data alokasi akan dihapus secara permanen dan tidak dapat dikembalikan.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4 border-y border-white/20 py-4 dark:border-neutral-700/30">
                            <div className="space-y-3">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-red-500/30 via-red-400/20 to-red-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-red-600 dark:text-red-400">📋</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-600 dark:text-neutral-400">Kegiatan</p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">{selectedPeriode.namaKegiatan}</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-red-500/30 via-red-400/20 to-red-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-red-600 dark:text-red-400">📅</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-600 dark:text-neutral-400">Periode</p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(selectedPeriode.bulan)} {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-red-400/30 bg-gradient-to-br from-red-500/20 via-red-400/10 to-red-300/10 backdrop-blur-xl p-3 shadow-lg">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                                    <p className="text-sm text-red-800 dark:text-red-200">
                                        Semua data petugas pada periode ini akan dihapus. Tindakan ini tidak dapat dibatalkan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowBatalkanModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Tidak, Kembali
                        </Button>
                        <Button variant="destructive" onClick={confirmBatalkan} className="w-full sm:w-auto">
                            <X className="mr-2 h-4 w-4" />
                            Ya, Batalkan Alokasi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Revisi */}
            <Dialog open={showRevisiModal} onOpenChange={setShowRevisiModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                                <RefreshCw className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <span>Revisi Alokasi Periode</span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            Revisi akan membuat draft baru yang dapat diedit. Revisi ini akan menghasilkan <strong>SK Perubahan</strong> dan <strong>Addendum SPK</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4">
                            <div className="space-y-3 border-y border-white/20 py-4 dark:border-neutral-700/30">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-orange-500/30 via-orange-400/20 to-orange-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-orange-600 dark:text-orange-400">📋</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-600 dark:text-neutral-400">Kegiatan</p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">{selectedPeriode.namaKegiatan}</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-orange-500/30 via-orange-400/20 to-orange-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-orange-600 dark:text-orange-400">📅</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-600 dark:text-neutral-400">Periode</p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(selectedPeriode.bulan)} {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-orange-400/30 bg-gradient-to-br from-orange-500/20 via-orange-400/10 to-orange-300/10 backdrop-blur-xl p-3 shadow-lg">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-orange-600 dark:text-orange-400" />
                                    <div className="space-y-1 text-sm text-orange-800 dark:text-orange-200">
                                        <p className="font-medium">Proses revisi:</p>
                                        <ul className="ml-4 list-disc space-y-1">
                                            <li>Data yang terkirim akan diarsipkan</li>
                                            <li>Salinan data akan dibuat sebagai draft baru</li>
                                            <li>Setelah dikirim ulang akan dibuat SK Perubahan</li>
                                            <li>SPK akan ditambahkan Addendum</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowRevisiModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Batal
                        </Button>
                        <Button onClick={confirmRevisi} className="w-full sm:w-auto">
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Ya, Lanjutkan Revisi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}
