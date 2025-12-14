import { Head, Link, router } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { BreadcrumbItem, Kegiatan } from '@/types'
import { useState } from 'react'
import { Search, Plus } from 'lucide-react'

interface KegiatanWithCount extends Kegiatan {
    penanggung_jawab: {
        id: number
        name: string
        email: string
    }
    alokasi_count: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '#' },
];


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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alokasi petugas" />

            <PageHeader
                title="Alokasi Petugas"
                description="Kelola alokasi petugas untuk setiap kegiatan"
            >
                <Button size="sm" asChild className="gap-2">
                    <Link href="/alokasi/create">
                        <Plus className="h-4 w-4" />
                        Tambah Periode Kegiatan
                    </Link>
                </Button>
            </PageHeader>

            {/* Filters */}
            <ContentCard>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="search">Cari Kegiatan</Label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                            <Input
                                id="search"
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                placeholder="Nama atau kode kegiatan..."
                                className="pl-9"
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                        >
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="divalidasi">Divalidasi</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="tahun">Tahun</Label>
                        <select
                            id="tahun"
                            value={tahun}
                            onChange={(e) => setTahun(e.target.value)}
                            className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
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
            </ContentCard>

            {/* Table */}
            <ContentCard padding="none">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-neutral-200/70 dark:divide-neutral-800">
                        <thead className="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Kegiatan
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Penanggung Jawab
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Tahun
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Pagu Anggaran
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Jumlah Petugas
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-200/70 bg-white dark:divide-neutral-800 dark:bg-neutral-950">
                            {kegiatans.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400"
                                    >
                                        Tidak ada data kegiatan
                                    </td>
                                </tr>
                            ) : (
                                kegiatans.data.map((kegiatan) => (
                                    <tr
                                        key={kegiatan.id}
                                        className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                    >
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <div>
                                                <div className="font-medium text-neutral-900 dark:text-white">
                                                    {kegiatan.nama_kegiatan}
                                                </div>
                                                <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {kegiatan.kode_kegiatan}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <div className="text-sm text-neutral-900 dark:text-white">
                                                {kegiatan.penanggung_jawab?.name || '-'}
                                            </div>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                            {kegiatan.tahun_anggaran}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                            {kegiatan.pagu_anggaran ? formatCurrency(kegiatan.pagu_anggaran) : '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                {kegiatan.alokasi_count} petugas
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
                                                <Button size="sm">Kelola Petugas</Button>
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
                    <div className="flex items-center justify-between border-t border-neutral-200/70 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950 sm:px-6">
                        <div className="flex flex-1 justify-between sm:hidden">
                            {kegiatans.links[0].url && (
                                <Link
                                    href={kegiatans.links[0].url}
                                    className="relative inline-flex items-center rounded-lg border border-neutral-200/70 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300"
                                >
                                    Previous
                                </Link>
                            )}
                            {kegiatans.links[kegiatans.links.length - 1].url && (
                                <Link
                                    href={
                                        kegiatans.links[kegiatans.links.length - 1].url || '#'
                                    }
                                    className="relative ml-3 inline-flex items-center rounded-lg border border-neutral-200/70 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                        <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p className="text-sm text-neutral-700 dark:text-neutral-400">
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
                                <nav className="isolate inline-flex -space-x-px rounded-lg shadow-sm">
                                    {kegiatans.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`relative inline-flex items-center px-4 py-2 text-sm font-medium ${
                                                link.active
                                                    ? 'z-10 bg-blue-600 text-white'
                                                    : 'bg-white text-neutral-700 hover:bg-neutral-50 dark:bg-neutral-950 dark:text-neutral-400 dark:hover:bg-neutral-900'
                                            } ${
                                                index === 0
                                                    ? 'rounded-l-lg'
                                                    : index === kegiatans.links.length - 1
                                                      ? 'rounded-r-lg'
                                                      : ''
                                            } border border-neutral-200/70 dark:border-neutral-800`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </nav>
                            </div>
                        </div>
                    </div>
                )}
            </ContentCard>
        </AppLayout>
    )
}

