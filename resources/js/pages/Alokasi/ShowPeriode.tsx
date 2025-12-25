import { Head, Link } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem, Kegiatan } from '@/types'
import { ArrowLeft, Calendar, Users, DollarSign, FileText, History } from 'lucide-react'

interface Petugas {
    id: number
    nama: string
    jenis_petugas: string
}

interface AlokasiPetugas {
    id: number
    petugas: Petugas
    peran: string
    jumlah_satuan: number
    jumlah_satuan_listing?: number
    total_honor: number
    total_honor_listing?: number
    rate_pencacahan?: number
    rate_listing?: number
    catatan: string | null
}

interface PeriodeAlokasi {
    id: number
    kegiatan_id: number
    bulan: string
    tahun: number
    jenis_kegiatan: 'sensus' | 'survei'
    status: 'draft' | 'dikirim' | 'perubahan' | 'disetujui'
    revision_number: number
    parent_periode_id: number | null
    submitted_at: string | null
    submitted_by_name: string | null
    kegiatan: Kegiatan & { has_listing_updating?: boolean }
    alokasi_petugas: AlokasiPetugas[]
    total_estimasi: number
    total_estimasi_pencacahan?: number
    total_estimasi_listing?: number
    jumlah_petugas: number
}

interface PeriodeRevision {
    id: number
    revision_number: number
    status: string
    submitted_at: string | null
    submitted_by_name: string | null
    alokasi_petugas: AlokasiPetugas[]
    total_estimasi: number
    total_estimasi_pencacahan?: number
    total_estimasi_listing?: number
    jumlah_petugas: number
}

interface Props {
    periode: PeriodeAlokasi
    revisions: PeriodeRevision[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '/alokasi' },
    { title: 'Detail Periode', href: '#' },
]

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]

const peranLabels: Record<string, string> = {
    'pcl_ppl': 'PCL',
    'pml': 'PML',
    'pengolahan': 'Petugas Pengolahan',
    'pengawas_pengolahan': 'Pengawas Pengolahan',
}

const statusLabels: Record<string, string> = {
    'draft': 'Draft',
    'dikirim': 'Dikirim',
    'perubahan': 'Revisi',
}

const statusColors: Record<string, string> = {
    'draft': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    'dikirim': 'bg-blue-100 text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300',
    'perubahan': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount)
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}

export default function ShowPeriode({ periode, revisions }: Props) {
    const bulanLabel = months[parseInt(periode.bulan) - 1]

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Periode ${bulanLabel} ${periode.tahun}`} />

            <PageHeader
                title={`Detail Periode ${bulanLabel} ${periode.tahun}`}
                description={`Informasi alokasi petugas untuk ${periode.kegiatan.nama_kegiatan}`}
            >
                <Button variant="outline" asChild>
                    <Link href="/alokasi">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            {/* Ringkasan Periode */}
            <ContentCard>
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                        Ringkasan Periode
                    </h3>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {/* Kegiatan */}
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <FileText className="h-4 w-4" />
                                <span>Kegiatan</span>
                            </div>
                            <div>
                                <div className="font-semibold text-neutral-900 dark:text-white">
                                    {periode.kegiatan.nama_kegiatan}
                                </div>
                                <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                    {periode.kegiatan.kode_kegiatan}
                                </div>
                            </div>
                        </div>

                        {/* Periode */}
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <Calendar className="h-4 w-4" />
                                <span>Periode</span>
                            </div>
                            <div>
                                <div className="font-semibold text-neutral-900 dark:text-white">
                                    {bulanLabel} {periode.tahun}
                                </div>
                                <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                    Jenis: {periode.jenis_kegiatan === 'sensus' ? 'Sensus' : 'Survei'}
                                </div>
                            </div>
                        </div>

                        {/* Jumlah Petugas */}
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <Users className="h-4 w-4" />
                                <span>Jumlah Petugas</span>
                            </div>
                            <div>
                                <div className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    {periode.jumlah_petugas}
                                </div>
                                <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                    Petugas dialokasikan
                                </div>
                            </div>
                        </div>

                        {/* Total Estimasi */}
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <DollarSign className="h-4 w-4" />
                                <span>Total Estimasi Honor</span>
                            </div>
                            <div>
                                <div className="text-xl font-bold text-green-600 dark:text-green-400">
                                    {formatCurrency(periode.total_estimasi)}
                                </div>
                                {periode.kegiatan.has_listing_updating && (
                                    <div className="mt-1 space-y-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                        <div>Listing: {formatCurrency(periode.total_estimasi_listing || 0)}</div>
                                        <div>Pencacahan: {formatCurrency(periode.total_estimasi_pencacahan || 0)}</div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Status and Submission Info */}
                    <div className="flex flex-wrap items-center gap-4 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-neutral-600 dark:text-neutral-400">Status:</span>
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${statusColors[periode.status]}`}>
                                {statusLabels[periode.status]}
                            </span>
                        </div>
                        {periode.revision_number > 0 && (
                            <div className="flex items-center gap-2">
                                <History className="h-4 w-4 text-neutral-600 dark:text-neutral-400" />
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Revisi ke-{periode.revision_number}
                                </span>
                            </div>
                        )}
                        {periode.submitted_at && (
                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                Dikirim oleh <strong>{periode.submitted_by_name}</strong> pada {formatDateTime(periode.submitted_at)}
                            </div>
                        )}
                    </div>
                </div>
            </ContentCard>

            {/* Tabel Alokasi Petugas */}
            <ContentCard>
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                        Daftar Alokasi Petugas
                    </h3>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-neutral-100 dark:bg-neutral-900">
                                <tr>
                                    <th className="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-400">No</th>
                                    <th className="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-400">Nama Petugas</th>
                                    <th className="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-400">Jenis</th>
                                    <th className="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-400">Peran</th>
                                    {periode.kegiatan.has_listing_updating && (
                                        <th className="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-400">Tahapan</th>
                                    )}
                                    <th className="px-4 py-3 text-right font-medium text-neutral-600 dark:text-neutral-400">Beban Tugas</th>
                                    <th className="px-4 py-3 text-right font-medium text-neutral-600 dark:text-neutral-400">Harga Satuan</th>
                                    <th className="px-4 py-3 text-right font-medium text-neutral-600 dark:text-neutral-400">Estimasi Honor</th>
                                    <th className="px-4 py-3 font-medium text-neutral-600 dark:text-neutral-400">Catatan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {periode.alokasi_petugas.map((alokasi, index) => (
                                    <>
                                        {/* Listing Row - only if has_listing_updating and has listing data */}
                                        {periode.kegiatan.has_listing_updating && (alokasi.jumlah_satuan_listing ?? 0) > 0 && (
                                            <tr key={`${alokasi.id}-listing`} className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50">
                                                <td className="px-4 py-3 text-neutral-900 dark:text-white" rowSpan={2}>
                                                    {index + 1}
                                                </td>
                                                <td className="px-4 py-3" rowSpan={2}>
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {alokasi.petugas.nama}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3" rowSpan={2}>
                                                    <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300">
                                                        {alokasi.petugas.jenis_petugas === 'organik' ? 'Organik' : 'Mitra'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-neutral-900 dark:text-white" rowSpan={2}>
                                                    {peranLabels[alokasi.peran] || alokasi.peran}
                                                </td>
                                                <td className="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                                    Listing
                                                </td>
                                                <td className="px-4 py-3 text-right text-neutral-900 dark:text-white">
                                                    {alokasi.jumlah_satuan_listing}
                                                </td>
                                                <td className="px-4 py-3 text-right text-neutral-900 dark:text-white">
                                                    {formatCurrency(alokasi.rate_listing || 0)}
                                                </td>
                                                <td className="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400">
                                                    {formatCurrency(alokasi.total_honor_listing || 0)}
                                                </td>
                                                <td className="px-4 py-3 text-neutral-600 dark:text-neutral-400" rowSpan={2}>
                                                    {alokasi.catatan || '-'}
                                                </td>
                                            </tr>
                                        )}
                                        {/* Pencacahan Row */}
                                        <tr key={`${alokasi.id}-pencacahan`} className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50">
                                            {!periode.kegiatan.has_listing_updating || (alokasi.jumlah_satuan_listing ?? 0) === 0 ? (
                                                <>
                                                    <td className="px-4 py-3 text-neutral-900 dark:text-white">
                                                        {index + 1}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                            {alokasi.petugas.nama}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300">
                                                            {alokasi.petugas.jenis_petugas === 'organik' ? 'Organik' : 'Mitra'}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-neutral-900 dark:text-white">
                                                        {peranLabels[alokasi.peran] || alokasi.peran}
                                                    </td>
                                                </>
                                            ) : null}
                                            {periode.kegiatan.has_listing_updating && (
                                                <td className="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                                    Pencacahan
                                                </td>
                                            )}
                                            <td className="px-4 py-3 text-right text-neutral-900 dark:text-white">
                                                {alokasi.jumlah_satuan}
                                            </td>
                                            <td className="px-4 py-3 text-right text-neutral-900 dark:text-white">
                                                {formatCurrency(alokasi.rate_pencacahan || 0)}
                                            </td>
                                            <td className="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400">
                                                {formatCurrency(alokasi.total_honor)}
                                            </td>
                                            {!periode.kegiatan.has_listing_updating || (alokasi.jumlah_satuan_listing ?? 0) === 0 ? (
                                                <td className="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                                    {alokasi.catatan || '-'}
                                                </td>
                                            ) : null}
                                        </tr>
                                    </>
                                ))}
                            </tbody>
                            <tfoot className="bg-neutral-100 dark:bg-neutral-900">
                                {periode.kegiatan.has_listing_updating && (
                                    <>
                                        <tr className="border-b border-neutral-200 dark:border-neutral-800">
                                            <td colSpan={7} className="px-4 py-2 text-right text-sm font-semibold text-neutral-600 dark:text-neutral-400">
                                                Total Listing:
                                            </td>
                                            <td className="px-4 py-2 text-right text-lg font-bold text-blue-600 dark:text-blue-400">
                                                {formatCurrency(periode.total_estimasi_listing || 0)}
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr className="border-b border-neutral-200 dark:border-neutral-800">
                                            <td colSpan={7} className="px-4 py-2 text-right text-sm font-semibold text-neutral-600 dark:text-neutral-400">
                                                Total Pencacahan:
                                            </td>
                                            <td className="px-4 py-2 text-right text-lg font-bold text-blue-600 dark:text-blue-400">
                                                {formatCurrency(periode.total_estimasi_pencacahan || 0)}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </>
                                )}
                                <tr>
                                    <td colSpan={periode.kegiatan.has_listing_updating ? 7 : 6} className="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-white">
                                        Total Keseluruhan:
                                    </td>
                                    <td className="px-4 py-3 text-right text-xl font-bold text-green-600 dark:text-green-400">
                                        {formatCurrency(periode.total_estimasi)}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </ContentCard>

            {/* Histori Revisi */}
            {revisions && revisions.length > 0 && (
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <History className="h-5 w-5 text-neutral-600 dark:text-neutral-400" />
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Histori Revisi
                            </h3>
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                ({revisions.length} revisi sebelumnya)
                            </span>
                        </div>

                        <div className="space-y-6">
                            {revisions.map((revision, revIdx) => {
                                // For comparison, get the previous revision (or current if this is the oldest)
                                const comparedWith = revIdx < revisions.length - 1 ? revisions[revIdx + 1] : null
                                const isOldest = !comparedWith

                                return (
                                    <div
                                        key={revision.id}
                                        className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50"
                                    >
                                        <div className="mb-4 flex items-center justify-between border-b border-neutral-200 pb-3 dark:border-neutral-800">
                                            <div className="flex items-center gap-3">
                                                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${
                                                    revision.status === 'direvisi' 
                                                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                                        : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
                                                }`}>
                                                    {revision.status === 'direvisi' ? 'Sudah Direvisi' : `Revisi ke-${revision.revision_number}`}
                                                </span>
                                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                                    {revision.jumlah_petugas} petugas
                                                </span>
                                            </div>
                                            <div className="text-right text-sm text-neutral-600 dark:text-neutral-400">
                                                {revision.submitted_at && (
                                                    <div>
                                                        {formatDateTime(revision.submitted_at)}
                                                        {revision.submitted_by_name && (
                                                            <div className="font-medium">oleh {revision.submitted_by_name}</div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="bg-neutral-200 dark:bg-neutral-800">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">No</th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">Petugas</th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">Peran</th>
                                                        <th className="px-3 py-2 text-right text-xs font-medium text-neutral-600 dark:text-neutral-400">Beban Tugas</th>
                                                        <th className="px-3 py-2 text-right text-xs font-medium text-neutral-600 dark:text-neutral-400">Honor</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                                    {revision.alokasi_petugas.map((alokasi, idx) => {
                                                        // Check if this petugas was changed in next revision
                                                        const nextRevisionAlokasi = revIdx > 0 
                                                            ? revisions[revIdx - 1].alokasi_petugas.find(a => a.petugas.id === alokasi.petugas.id)
                                                            : periode.alokasi_petugas.find(a => a.petugas.id === alokasi.petugas.id)
                                                        
                                                        const bebanChanged = nextRevisionAlokasi && nextRevisionAlokasi.jumlah_satuan !== alokasi.jumlah_satuan
                                                        const honorChanged = nextRevisionAlokasi && nextRevisionAlokasi.total_honor !== alokasi.total_honor

                                                        return (
                                                            <tr key={alokasi.id}>
                                                                <td className="px-3 py-2 text-neutral-900 dark:text-white">{idx + 1}</td>
                                                                <td className="px-3 py-2 text-neutral-900 dark:text-white">
                                                                    {alokasi.petugas.nama}
                                                                </td>
                                                                <td className="px-3 py-2 text-neutral-900 dark:text-white">
                                                                    {peranLabels[alokasi.peran] || alokasi.peran}
                                                                </td>
                                                                <td className="px-3 py-2 text-right">
                                                                    <div className={bebanChanged ? 'font-semibold text-orange-600 dark:text-orange-400' : 'text-neutral-900 dark:text-white'}>
                                                                        {alokasi.jumlah_satuan}
                                                                        {bebanChanged && nextRevisionAlokasi && (
                                                                            <span className="ml-1 text-xs">
                                                                                → {nextRevisionAlokasi.jumlah_satuan}
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                </td>
                                                                <td className="px-3 py-2 text-right">
                                                                    <div className={honorChanged ? 'font-semibold text-orange-600 dark:text-orange-400' : 'text-neutral-900 dark:text-white'}>
                                                                        {formatCurrency(alokasi.total_honor)}
                                                                        {honorChanged && nextRevisionAlokasi && (
                                                                            <div className="text-xs">
                                                                                → {formatCurrency(nextRevisionAlokasi.total_honor)}
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        )
                                                    })}
                                                </tbody>
                                                <tfoot className="bg-neutral-200 dark:bg-neutral-800">
                                                    <tr>
                                                        <td colSpan={4} className="px-3 py-2 text-right text-xs font-semibold text-neutral-900 dark:text-white">
                                                            Total:
                                                        </td>
                                                        <td className="px-3 py-2 text-right font-bold text-neutral-900 dark:text-white">
                                                            {formatCurrency(revision.total_estimasi)}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                )
                            })}
                        </div>
                    </div>
                </ContentCard>
            )}
        </AppLayout>
    )
}
