import { Head, Link, router } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import type { Kegiatan } from '@/types'
import { useState } from 'react'

interface KegiatanWithCount extends Kegiatan {
    penanggung_jawab: {
        id: number
        name: string
        email: string
    }
    alokasi_count: number
}

interface Props {
    kegiatans: {
        data: KegiatanWithCount[]
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
        tahun?: number
    }
}

export default function Index({ kegiatans, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '')
    const [status, setStatus] = useState(filters.status || '')
    const [tahun, setTahun] = useState(filters.tahun?.toString() || '')

    // Generate tahun options (5 tahun ke belakang dan 2 tahun ke depan)
    const currentYear = new Date().getFullYear()
    const tahunOptions = Array.from({ length: 8 }, (_, i) => currentYear - 5 + i)

    const handleFilter = () => {
        router.get(
            '/alokasi',
            {
                search: search || undefined,
                status: status || undefined,
                tahun: tahun || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        )
    }

    const handleReset = () => {
        setSearch('')
        setStatus('')
        setTahun('')
        router.get('/alokasi')
    }

    const statusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        divalidasi: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        selesai: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        dibatalkan: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
    }

    return (
        <AppLayout>
            <Head title="Alokasi Mitra" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Alokasi Mitra
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola alokasi mitra untuk setiap kegiatan
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Cari Kegiatan
                            </label>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                placeholder="Nama atau kode kegiatan..."
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Status
                            </label>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            >
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="divalidasi">Divalidasi</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tahun
                            </label>
                            <select
                                value={tahun}
                                onChange={(e) => setTahun(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            >
                                <option value="">Semua Tahun</option>
                                {tahunOptions.map((year) => (
                                    <option key={year} value={year}>
                                        {year}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex items-end gap-2">
                            <Button onClick={handleFilter} className="flex-1">
                                Filter
                            </Button>
                            <Button onClick={handleReset} variant="outline">
                                Reset
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Penanggung Jawab
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Tahun
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Pagu Anggaran
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Jumlah Mitra
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
                                {kegiatans.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-12 text-center text-gray-500 dark:text-gray-400"
                                        >
                                            Tidak ada data kegiatan
                                        </td>
                                    </tr>
                                ) : (
                                    kegiatans.data.map((kegiatan) => (
                                        <tr
                                            key={kegiatan.id}
                                            className="hover:bg-gray-50 dark:hover:bg-gray-900"
                                        >
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div>
                                                    <div className="font-medium text-gray-900 dark:text-white">
                                                        {kegiatan.nama_kegiatan}
                                                    </div>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        {kegiatan.kode_kegiatan}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div className="text-sm text-gray-900 dark:text-white">
                                                    {kegiatan.penanggung_jawab?.name || '-'}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {kegiatan.pagu_anggaran ? formatCurrency(kegiatan.pagu_anggaran) : '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <span className="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                    {kegiatan.alokasi_count} Mitra
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[kegiatan.status as keyof typeof statusColors]}`}
                                                >
                                                    {kegiatan.status === 'draft' && 'Draft'}
                                                    {kegiatan.status === 'divalidasi' && 'Divalidasi'}
                                                    {kegiatan.status === 'selesai' && 'Selesai'}
                                                    {kegiatan.status === 'dibatalkan' &&
                                                        'Dibatalkan'}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <Link href={`/alokasi/kegiatan/${kegiatan.hashed_id}/manage`}>
                                                    <Button size="sm">Kelola Mitra</Button>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {kegiatans.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                            <div className="flex flex-1 justify-between sm:hidden">
                                {kegiatans.links[0].url && (
                                    <Link
                                        href={kegiatans.links[0].url}
                                        className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {kegiatans.links[kegiatans.links.length - 1].url && (
                                    <Link
                                        href={
                                            kegiatans.links[kegiatans.links.length - 1].url || '#'
                                        }
                                        className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-gray-700 dark:text-gray-400">
                                        Menampilkan{' '}
                                        <span className="font-medium">
                                            {(kegiatans.current_page - 1) * kegiatans.per_page + 1}
                                        </span>{' '}
                                        sampai{' '}
                                        <span className="font-medium">
                                            {Math.min(
                                                kegiatans.current_page * kegiatans.per_page,
                                                kegiatans.total
                                            )}
                                        </span>{' '}
                                        dari <span className="font-medium">{kegiatans.total}</span>{' '}
                                        hasil
                                    </p>
                                </div>
                                <div>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                        {kegiatans.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-medium ${
                                                    link.active
                                                        ? 'z-10 bg-blue-600 text-white'
                                                        : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
                                                } ${
                                                    index === 0
                                                        ? 'rounded-l-md'
                                                        : index === kegiatans.links.length - 1
                                                          ? 'rounded-r-md'
                                                          : ''
                                                } border border-gray-300 dark:border-gray-600`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    )
}
