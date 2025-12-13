import { Head, Link } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import type { Kegiatan, Mitra, RateHonor, Satuan } from '@/types'

interface Alokasi {
    id: string
    hashed_id: string
    mitra: Mitra
    rate_honor: RateHonor & {
        satuan: Satuan
    }
    volume: number
    total_honor: number
    status: string
    tanggal_pengajuan: string
}

interface Props {
    kegiatan: Kegiatan & {
        penanggung_jawab: {
            id: number
            name: string
            email: string
        }
        alokasi: Alokasi[]
    }
}

export default function Show({ kegiatan }: Props) {
    const statusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        divalidasi: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        selesai: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        dibatalkan: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }

    const alokasiStatusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        diajukan: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        disetujui_pj: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        disetujui: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ditolak: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }

    const formatCurrency = (amount: number | null | undefined) => {
        if (!amount || isNaN(amount)) return 'Rp 0'
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
    }

    const formatDate = (date: string) => {
        // Laravel mengirim format Y-m-d, parse manual untuk menghindari timezone shift
        const [year, month, day] = date.split('-')
        const localDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day))
        return localDate.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        })
    }

    const totalAlokasi = kegiatan.alokasi.reduce(
        (sum: number, alokasi: Alokasi) => sum + alokasi.total_honor,
        0
    )

    return (
        <AppLayout>
            <Head title={`Detail Kegiatan - ${kegiatan.nama_kegiatan}`} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Detail Kegiatan
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Informasi lengkap kegiatan dan alokasi mitra
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link href="/kegiatan">
                            <Button variant="outline">Kembali</Button>
                        </Link>
                        <Link href={`/kegiatan/${kegiatan.hashed_id}/edit`}>
                            <Button>Edit Kegiatan</Button>
                        </Link>
                    </div>
                </div>

                {/* Kegiatan Info Card */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Informasi Kegiatan
                        </h2>
                    </div>
                    <div className="p-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Kode Kegiatan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.kode_kegiatan}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>
                                <div className="mt-1">
                                    <span
                                        className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusColors[kegiatan.status as keyof typeof statusColors]}`}
                                    >
                                        {kegiatan.status === 'draft' && 'Draft'}
                                        {kegiatan.status === 'divalidasi' && 'Divalidasi'}
                                        {kegiatan.status === 'selesai' && 'Selesai'}
                                        {kegiatan.status === 'dibatalkan' && 'Dibatalkan'}
                                    </span>
                                </div>
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nama Kegiatan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.nama_kegiatan}
                                </p>
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Deskripsi
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.deskripsi || '-'}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tahun Anggaran
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.tahun_anggaran}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Pagu Anggaran
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatCurrency(kegiatan.pagu_anggaran)}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Penanggung Jawab
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.penanggung_jawab?.name || '-'}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {kegiatan.penanggung_jawab?.email || ''}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Total Alokasi
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatCurrency(totalAlokasi)}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Sisa: {formatCurrency((kegiatan.pagu_anggaran || 0) - totalAlokasi)}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tanggal Mulai
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatDate(kegiatan.tanggal_mulai)}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tanggal Selesai
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatDate(kegiatan.tanggal_selesai)}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Alokasi Mitra */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Alokasi Mitra ({kegiatan.alokasi.length})
                        </h2>
                        <Link href={`/alokasi/create?kegiatan_id=${kegiatan.id}`}>
                            <Button size="sm">Tambah Alokasi</Button>
                        </Link>
                    </div>

                    {kegiatan.alokasi.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <p className="text-gray-500 dark:text-gray-400">
                                Belum ada alokasi mitra untuk kegiatan ini
                            </p>
                            <Link
                                href={`/alokasi/create?kegiatan_id=${kegiatan.id}`}
                                className="mt-4 inline-block"
                            >
                                <Button variant="outline" size="sm">
                                    Tambah Alokasi Pertama
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Mitra
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Rate Honor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Volume
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    {kegiatan.alokasi.map((alokasi: Alokasi) => (
                                        <tr key={alokasi.id}>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div>
                                                    <div className="font-medium text-gray-900 dark:text-white">
                                                        {alokasi.mitra.nama}
                                                    </div>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        NIK: {alokasi.mitra.nik}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div>
                                                    <div className="text-gray-900 dark:text-white">
                                                        {alokasi.rate_honor.nama}
                                                    </div>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        {formatCurrency(
                                                            alokasi.rate_honor.honor_satuan
                                                        )}
                                                        /{alokasi.rate_honor.satuan.nama}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-gray-900 dark:text-white">
                                                {alokasi.volume}{' '}
                                                {alokasi.rate_honor.satuan.nama}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                                {formatCurrency(alokasi.total_honor)}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${alokasiStatusColors[alokasi.status as keyof typeof alokasiStatusColors]}`}
                                                >
                                                    {alokasi.status === 'draft' && 'Draft'}
                                                    {alokasi.status === 'diajukan' && 'Diajukan'}
                                                    {alokasi.status === 'disetujui_pj' &&
                                                        'Disetujui PJ'}
                                                    {alokasi.status === 'disetujui' && 'Disetujui'}
                                                    {alokasi.status === 'ditolak' && 'Ditolak'}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <Link href={`/alokasi/${alokasi.hashed_id}`}>
                                                    <Button variant="outline" size="sm">
                                                        Detail
                                                    </Button>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    )
}
