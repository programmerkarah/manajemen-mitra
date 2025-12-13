import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import type { BreadcrumbItem, Kegiatan, RateHonor, Satuan } from '@/types'
import { Head, router, usePage } from '@inertiajs/react'
import { CheckCircle2, Clock, XCircle } from 'lucide-react'
import { useState } from 'react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kelola Rate Honor', href: '/kegiatan/rate-honor' },
]

interface User {
    id: number
    name: string
    email: string
}

interface KegiatanItem {
    id: string
    hashed_id: string
    kode_kegiatan: string
    nama_kegiatan: string
    tahun_anggaran: number
    rate_honor_id: string | null
    rate_honor_status: 'pending' | 'approved' | 'rejected' | null
    rate_honor_notes: string | null
    penanggung_jawab: User
    rate_honor: (RateHonor & { satuan: Satuan }) | null
    rate_honor_approved_by: User | null
}

interface Props {
    kegiatan: {
        data: KegiatanItem[]
        current_page: number
        from: number
        last_page: number
        per_page: number
        to: number
        total: number
        links: Array<{
            url: string | null
            label: string
            active: boolean
        }>
    }
    satuan: Satuan[]
}

type ModalAction = 'update' | 'approve' | 'reject'

export default function RateHonorManagement({ kegiatan, satuan }: Props) {
    const { auth } = usePage<any>().props
    const [modalOpen, setModalOpen] = useState(false)
    const [modalAction, setModalAction] = useState<ModalAction>('update')
    const [selectedKegiatan, setSelectedKegiatan] = useState<KegiatanItem | null>(null)
    const [satuanId, setSatuanId] = useState<string>('')
    const [rate, setRate] = useState<string>('')
    const [jenisPenugasan, setJenisPenugasan] = useState<'pcl_ppl' | 'pml' | 'pengolahan'>('pcl_ppl')
    const [statusKepegawaian, setStatusKepegawaian] = useState<'organik' | 'non_organik'>('non_organik')
    const [notes, setNotes] = useState('')
    const [processing, setProcessing] = useState(false)

    // Debug: Log auth info
    console.log('Auth User:', auth.user)
    console.log('Active Role:', auth.activeRole)

    const openModal = (action: ModalAction, item: KegiatanItem) => {
        setModalAction(action)
        setSelectedKegiatan(item)
        setSatuanId(item.rate_honor?.satuan_id || '')
        setRate(item.rate_honor?.rate.toString() || '')
        setJenisPenugasan(item.rate_honor?.jenis_penugasan || 'pcl_ppl')
        setStatusKepegawaian(item.rate_honor?.status_kepegawaian || 'non_organik')
        setNotes(item.rate_honor_notes || '')
        setModalOpen(true)
    }

    const closeModal = () => {
        setModalOpen(false)
        setSelectedKegiatan(null)
        setSatuanId('')
        setRate('')
        setJenisPenugasan('pcl_ppl')
        setStatusKepegawaian('non_organik')
        setNotes('')
    }

    const handleSubmit = () => {
        if (!selectedKegiatan) return

        setProcessing(true)

        if (modalAction === 'update') {
            router.post(
                `/kegiatan/${selectedKegiatan.hashed_id}/rate-honor`,
                {
                    satuan_id: satuanId,
                    rate: parseFloat(rate),
                    jenis_penugasan: jenisPenugasan,
                    status_kepegawaian: statusKepegawaian,
                    notes: notes,
                },
                {
                    onFinish: () => {
                        setProcessing(false)
                        closeModal()
                    },
                }
            )
        } else if (modalAction === 'approve') {
            router.post(
                `/kegiatan/${selectedKegiatan.hashed_id}/rate-honor/approve`,
                {
                    notes: notes,
                },
                {
                    onFinish: () => {
                        setProcessing(false)
                        closeModal()
                    },
                }
            )
        } else if (modalAction === 'reject') {
            router.post(
                `/kegiatan/${selectedKegiatan.hashed_id}/rate-honor/reject`,
                {
                    notes: notes,
                },
                {
                    onFinish: () => {
                        setProcessing(false)
                        closeModal()
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

    const getJenisPenugasanLabel = (jenis: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'PCL/PPL',
            pml: 'PML',
            pengolahan: 'Pengolahan',
        }
        return labels[jenis] || jenis
    }

    const getStatusKepegawaianLabel = (status: string) => {
        return status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik'
    }

    const getStatusBadge = (status: string | null) => {
        if (!status || status === 'pending') {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-500">
                    <Clock className="h-3 w-3" />
                    Pending
                </span>
            )
        }
        if (status === 'approved') {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-500">
                    <CheckCircle2 className="h-3 w-3" />
                    Disetujui
                </span>
            )
        }
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-500">
                <XCircle className="h-3 w-3" />
                Ditolak
            </span>
        )
    }

    // Count status
    const pendingCount = kegiatan.data.filter((k) => !k.rate_honor_status || k.rate_honor_status === 'pending').length
    const approvedCount = kegiatan.data.filter((k) => k.rate_honor_status === 'approved').length
    const rejectedCount = kegiatan.data.filter((k) => k.rate_honor_status === 'rejected').length

    // Check user permissions based on activeRole
    const activeRoleName = auth.activeRole?.name
    const canEdit = activeRoleName === 'operator' || activeRoleName === 'admin'
    const canApprove = activeRoleName === 'approver'

    // Debug: Log permissions
    console.log('Active Role Name:', activeRoleName)
    console.log('Can Edit:', canEdit)
    console.log('Can Approve:', canApprove)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kelola Rate Honor" />
            <div className="space-y-6">
                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">{pendingCount}</div>
                            <p className="text-xs text-muted-foreground">Pending</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">{approvedCount}</div>
                            <p className="text-xs text-muted-foreground">Disetujui</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">{rejectedCount}</div>
                            <p className="text-xs text-muted-foreground">Ditolak</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-4 py-2 text-left text-sm font-medium">
                                            Kegiatan
                                        </th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">
                                            Penanggung Jawab
                                        </th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">
                                            Rate Honor
                                        </th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">
                                            Jenis Penugasan
                                        </th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">
                                            Status Kepegawaian
                                        </th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">Status</th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">
                                            Disetujui Oleh
                                        </th>
                                        <th className="px-4 py-2 text-right text-sm font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {kegiatan.data.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="px-4 py-3">
                                                <div>
                                                    <div className="font-medium">{item.nama_kegiatan}</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {item.kode_kegiatan} - TA {item.tahun_anggaran}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="text-sm">{item.penanggung_jawab.name}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.rate_honor ? (
                                                    <div>
                                                        <div className="font-medium">
                                                            {item.rate_honor.posisi}
                                                        </div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {formatCurrency(item.rate_honor.rate)}/
                                                            {item.rate_honor.satuan.nama}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        Belum diset
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.rate_honor ? (
                                                    <span className="text-sm">
                                                        {getJenisPenugasanLabel(item.rate_honor.jenis_penugasan)}
                                                    </span>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.rate_honor ? (
                                                    <span className="text-sm">
                                                        {getStatusKepegawaianLabel(item.rate_honor.status_kepegawaian)}
                                                    </span>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {getStatusBadge(item.rate_honor_status)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.rate_honor_approved_by ? (
                                                    <div className="text-sm">
                                                        {item.rate_honor_approved_by.name}
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    {canEdit && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => openModal('update', item)}
                                                        >
                                                            {item.rate_honor ? 'Ubah Rate' : 'Set Rate'}
                                                        </Button>
                                                    )}
                                                    {canApprove &&
                                                        item.rate_honor_status === 'pending' && (
                                                            <>
                                                                <Button
                                                                    size="sm"
                                                                    variant="default"
                                                                    onClick={() =>
                                                                        openModal('approve', item)
                                                                    }
                                                                >
                                                                    Setujui
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="destructive"
                                                                    onClick={() =>
                                                                        openModal('reject', item)
                                                                    }
                                                                >
                                                                    Tolak
                                                                </Button>
                                                            </>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {kegiatan.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <div className="text-sm text-muted-foreground">
                                    Menampilkan {kegiatan.from} - {kegiatan.to} dari {kegiatan.total}{' '}
                                    data
                                </div>
                                <div className="flex gap-2">
                                    {kegiatan.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            size="sm"
                                            variant={link.active ? 'default' : 'outline'}
                                            disabled={!link.url}
                                            onClick={() => link.url && router.visit(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Modal */}
            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {modalAction === 'update' && 'Update Rate Honor'}
                            {modalAction === 'approve' && 'Setujui Rate Honor'}
                            {modalAction === 'reject' && 'Tolak Rate Honor'}
                        </DialogTitle>
                        {selectedKegiatan && (
                            <DialogDescription asChild>
                                <span className="block">
                                    <span className="font-medium block">{selectedKegiatan.nama_kegiatan}</span>
                                    <span className="text-sm block">
                                        {selectedKegiatan.kode_kegiatan} - TA {selectedKegiatan.tahun_anggaran}
                                    </span>
                                </span>
                            </DialogDescription>
                        )}
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {modalAction === 'update' && (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="satuan">
                                        Satuan <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={satuanId} onValueChange={setSatuanId}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih Satuan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {satuan.map((s) => (
                                                <SelectItem key={s.id} value={s.id.toString()}>
                                                    {s.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="jenis_penugasan">
                                        Jenis Penugasan <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={jenisPenugasan}
                                        onValueChange={(value) => setJenisPenugasan(value as 'pcl_ppl' | 'pml' | 'pengolahan')}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih Jenis Penugasan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pcl_ppl">
                                                <div>
                                                    <div className="font-medium">PCL/PPL</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Petugas Pencacahan/Pendataan Lapangan
                                                    </div>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="pml">
                                                <div>
                                                    <div className="font-medium">PML</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Petugas Pemeriksaan Lapangan
                                                    </div>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="pengolahan">
                                                <div>
                                                    <div className="font-medium">Petugas Pengolahan</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Petugas Pengolahan Data
                                                    </div>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status_kepegawaian">
                                        Status Kepegawaian <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={statusKepegawaian}
                                        onValueChange={(value) => setStatusKepegawaian(value as 'organik' | 'non_organik')}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih Status Kepegawaian" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="organik">Organik (PNS/PPPK)</SelectItem>
                                            <SelectItem value="non_organik">Non-Organik</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="rate">
                                        Rate Honor (Rp) <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="rate"
                                        type="number"
                                        placeholder="Masukkan rate honor"
                                        value={rate}
                                        onChange={(e) => setRate(e.target.value)}
                                        min="0"
                                        step="1000"
                                    />
                                    {rate && (
                                        <p className="text-sm text-muted-foreground">
                                            {formatCurrency(parseFloat(rate || '0'))}
                                        </p>
                                    )}
                                </div>
                            </>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="notes">
                                Catatan {modalAction === 'reject' && <span className="text-red-500">*</span>}
                            </Label>
                            <Input
                                id="notes"
                                value={notes}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setNotes(e.target.value)}
                                placeholder="Tambahkan catatan (opsional)"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={closeModal} disabled={processing}>
                            Batal
                        </Button>
                        <Button
                            onClick={handleSubmit}
                            disabled={
                                processing ||
                                (modalAction === 'update' && (!satuanId || !rate)) ||
                                (modalAction === 'reject' && !notes)
                            }
                            variant={modalAction === 'reject' ? 'destructive' : 'default'}
                        >
                            {processing ? 'Memproses...' : modalAction === 'approve' ? 'Setujui' : modalAction === 'reject' ? 'Tolak' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}

