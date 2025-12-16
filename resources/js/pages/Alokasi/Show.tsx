import { Head, Link, router, useForm, usePage } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import type { AlokasiPetugas, BreadcrumbItem, Kegiatan, Petugas, RateHonor, Satuan, SharedData } from '@/types'
import { useState } from 'react'
import InputError from '@/components/input-error'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '/alokasi' },
    { title: 'Detail Alokasi Petugas', href: '#' },
];

interface Props {
    alokasi: AlokasiPetugas & {
        kegiatan: Kegiatan & {
            penanggung_jawab: {
                id: number
                name: string
                email: string
            }
            rate_honor: RateHonor & {
                satuan: Satuan
            }
        }
        petugas: Petugas
        submitted_by?: {
            id: number
            name: string
            email: string
        }
        approved_by?: {
            id: number
            name: string
            email: string
        }
    }
}

export default function Show({ alokasi }: Props) {
    const { auth } = usePage<SharedData>().props
    const [showApprovalModal, setShowApprovalModal] = useState(false)
    const [approvalAction, setApprovalAction] = useState<'approve' | 'approve-pj' | 'reject'>(
        'approve'
    )

    const { data, setData, post, processing, errors, reset } = useForm({
        catatan_approval: '',
    })

    const statusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        diajukan: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        disetujui_pj: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        disetujui: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ditolak: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
    }

    const formatDate = (date: string | null) => {
        if (!date) return '-'
        
        // Jika ada waktu (timestamp dengan T), handle terpisah
        if (date.includes('T')) {
            // Parse tanggal dan waktu
            const dateObj = new Date(date)
            return dateObj.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })
        }
        
        // Jika hanya tanggal (Y-m-d), parse manual untuk menghindari timezone shift
        const [year, month, day] = date.split('-')
        const localDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day))
        return localDate.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        })
    }

    const monthNames = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ]

    const handleSubmit = () => {
        router.post(`/alokasi/${alokasi.hashed_id}/submit`)
    }

    const handleApproval = (e: React.FormEvent) => {
        e.preventDefault()
        let endpoint = `/alokasi/${alokasi.hashed_id}/approve`
        
        if (approvalAction === 'approve-pj') {
            endpoint = `/alokasi/${alokasi.hashed_id}/approve-pj`
        } else if (approvalAction === 'reject') {
            endpoint = `/alokasi/${alokasi.hashed_id}/reject`
        }

        post(endpoint, {
            onSuccess: () => {
                setShowApprovalModal(false)
                reset()
            },
        })
    }

    const openApprovalModal = (action: 'approve' | 'approve-pj' | 'reject') => {
        setApprovalAction(action)
        setShowApprovalModal(true)
    }

    // Check permissions based on active role
    const canEditDraft = alokasi.status === 'draft' && auth.activeRole?.name !== 'guest'
    const canSubmitDraft = alokasi.status === 'draft' && auth.activeRole?.name !== 'guest'
    
    const canApprovePj =
        alokasi.status === 'diajukan' &&
        auth.activeRole?.name === 'pj' &&
        auth.user.id === alokasi.kegiatan.penanggung_jawab?.id
    
    const canApprove =
        (alokasi.status === 'diajukan' || alokasi.status === 'disetujui_pj') &&
        auth.activeRole?.name === 'approver'

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Alokasi - ${alokasi.petugas.nama}`} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Detail Alokasi Petugas
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Informasi lengkap alokasi petugas ke kegiatan
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link href="/alokasi">
                            <Button variant="outline">Kembali</Button>
                        </Link>
                        
                        {canEditDraft && (
                            <Link href={`/alokasi/${alokasi.hashed_id}/edit`}>
                                <Button variant="outline">Edit</Button>
                            </Link>
                        )}
                        
                        {canSubmitDraft && (
                            <Button onClick={handleSubmit}>Ajukan Persetujuan</Button>
                        )}
                        
                        {canApprovePj && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => openApprovalModal('reject')}
                                    className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                >
                                    Tolak
                                </Button>
                                <Button onClick={() => openApprovalModal('approve-pj')}>
                                    Setujui (PJ)
                                </Button>
                            </>
                        )}
                        
                        {canApprove && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => openApprovalModal('reject')}
                                    className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                >
                                    Tolak
                                </Button>
                                <Button onClick={() => openApprovalModal('approve')}>
                                    Setujui Final
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Status Badge */}
                <div className="flex items-center gap-2">
                    <span
                        className={`inline-flex rounded-full px-4 py-2 text-sm font-semibold ${statusColors[alokasi.status as keyof typeof statusColors]}`}
                    >
                        {alokasi.status === 'draft' && 'Draft'}
                        {alokasi.status === 'diajukan' && 'Menunggu Persetujuan'}
                        {alokasi.status === 'disetujui_pj' && 'Disetujui PJ'}
                        {alokasi.status === 'disetujui' && 'Disetujui'}
                        {alokasi.status === 'ditolak' && 'Ditolak'}
                    </span>
                </div>

                {/* Alokasi Info Card */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Informasi Alokasi
                        </h2>
                    </div>
                    <div className="p-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Kegiatan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {alokasi.kegiatan.nama_kegiatan}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {alokasi.kegiatan.kode_kegiatan}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Petugas
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {alokasi.petugas.nama}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    NIK: {alokasi.petugas.nik}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {alokasi.petugas.email}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Penanggung Jawab Kegiatan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {alokasi.kegiatan.penanggung_jawab?.name || '-'}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {alokasi.kegiatan.penanggung_jawab?.email || ''}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Rate Honor
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {alokasi.kegiatan.rate_honor.posisi}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {formatCurrency(alokasi.kegiatan.rate_honor.rate)}/
                                    {alokasi.kegiatan.rate_honor.satuan.nama}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Periode
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {monthNames[alokasi.bulan - 1]} {alokasi.tahun}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Volume
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {alokasi.jumlah_satuan} {alokasi.kegiatan.rate_honor.satuan.nama}
                                </p>
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Total Honor
                                </label>
                                <p className="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {formatCurrency(alokasi.total_honor)}
                                </p>
                            </div>

                            {alokasi.catatan && (
                                <div className="md:col-span-2">
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Catatan
                                    </label>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        {alokasi.catatan}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Timeline Card */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Timeline Persetujuan
                        </h2>
                    </div>
                    <div className="p-6">
                        <div className="space-y-4">
                            {alokasi.submitted_by && (
                                <div className="flex gap-4">
                                    <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                        <svg
                                            className="h-5 w-5 text-blue-600 dark:text-blue-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">
                                            Diajukan
                                        </p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {formatDate(alokasi.submitted_at)}
                                        </p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            oleh {alokasi.submitted_by.name}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {alokasi.approved_by && (
                                <div className="flex gap-4">
                                    <div
                                        className={`flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full ${
                                            alokasi.status === 'ditolak'
                                                ? 'bg-red-100 dark:bg-red-900'
                                                : 'bg-green-100 dark:bg-green-900'
                                        }`}
                                    >
                                        <svg
                                            className={`h-5 w-5 ${
                                                alokasi.status === 'ditolak'
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : 'text-green-600 dark:text-green-400'
                                            }`}
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            {alokasi.status === 'ditolak' ? (
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            ) : (
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M5 13l4 4L19 7"
                                                />
                                            )}
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">
                                            {alokasi.status === 'ditolak'
                                                ? 'Ditolak'
                                                : 'Disetujui'}
                                        </p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {formatDate(alokasi.approved_at)}
                                        </p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            oleh {alokasi.approved_by.name}
                                        </p>
                                        {alokasi.catatan_approval && (
                                            <p className="mt-2 text-sm text-gray-900 dark:text-white">
                                                Catatan: {alokasi.catatan_approval}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Approval Modal */}
            {showApprovalModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 dark:bg-gray-800">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {approvalAction === 'approve' && 'Setujui Final Alokasi'}
                            {approvalAction === 'approve-pj' && 'Setujui Alokasi (PJ)'}
                            {approvalAction === 'reject' && 'Tolak Alokasi'}
                        </h3>
                        <form onSubmit={handleApproval} className="mt-4">
                            <div>
                                <label
                                    htmlFor="catatan_approval"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Catatan {approvalAction === 'reject' && '(Wajib diisi)'}
                                </label>
                                <Textarea
                                    id="catatan_approval"
                                    rows={4}
                                    value={data.catatan_approval}
                                    onChange={(e) => setData('catatan_approval', e.target.value)}
                                    placeholder="Masukkan catatan..."
                                />
                                <InputError message={errors.catatan_approval} className="mt-2" />
                            </div>
                            <div className="mt-6 flex justify-end gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setShowApprovalModal(false)
                                        reset()
                                    }}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className={
                                        approvalAction === 'reject'
                                            ? 'bg-red-600 hover:bg-red-700'
                                            : ''
                                    }
                                >
                                    {processing
                                        ? 'Memproses...'
                                        : approvalAction === 'approve'
                                          ? 'Setujui Final'
                                          : approvalAction === 'approve-pj'
                                            ? 'Setujui (PJ)'
                                            : 'Tolak'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    )
}

