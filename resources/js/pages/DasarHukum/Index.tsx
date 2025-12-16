import AppLayout from '@/layouts/app-layout'
import { ContentCard } from '@/components/content-card'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import type { BreadcrumbItem, SharedData } from '@/types'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { Pencil, Plus, Trash2, Search } from 'lucide-react'
import { useEffect, useState } from 'react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
]

interface DasarHukum {
    id: number
    kategori: string
    instansi: string | null
    nomor: string
    tentang: string
    tahun: number
    status: 'aktif' | 'nonaktif'
    created_at: string
    updated_at: string
}

interface Props {
    dasarHukum: {
        data: DasarHukum[]
        links: any[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    filters: {
        search: string
        status: string
    }
}

export default function Index({ dasarHukum, filters }: Props) {
    const { auth } = usePage<SharedData>().props
    const isPJ = auth.activeRole?.name === 'pj'

    const [search, setSearch] = useState(filters.search || '')
    const [status, setStatus] = useState(filters.status || 'all')

    useEffect(() => {
        const timer = setTimeout(() => {
            handleFilter()
        }, 300)
        return () => clearTimeout(timer)
    }, [search, status])

    const handleFilter = () => {
        router.get(
            '/dasar-hukum',
            {
                search: search || undefined,
                status: status !== 'all' ? status : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const handleDelete = (id: number, nomor: string) => {
        if (
            confirm(
                `Apakah Anda yakin ingin menghapus dasar hukum "${nomor}"?`,
            )
        ) {
            router.delete(`/dasar-hukum/${id}`)
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasar Hukum SK" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Dasar Hukum SK"
                    description="Kelola dasar hukum yang digunakan pada SK KPA"
                >
                    {!isPJ && (
                        <Button size="sm" asChild className="gap-2">
                            <Link href="/dasar-hukum/create">
                                <Plus className="h-4 w-4" />
                                Tambah Dasar Hukum
                            </Link>
                        </Button>
                    )}
                </PageHeader>

                {/* Filters */}
                <ContentCard>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div className="md:col-span-2">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                                <Input
                                    type="text"
                                    placeholder="Cari nomor atau tentang..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </div>
                        <div>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Semua Status
                                    </SelectItem>
                                    <SelectItem value="aktif">Aktif</SelectItem>
                                    <SelectItem value="nonaktif">
                                        Nonaktif
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Dasar Hukum
                                    </th>
                                    <th className="px-6 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Tahun
                                    </th>
                                    <th className="px-6 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    {!isPJ && (
                                        <th className="px-6 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            Aksi
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {dasarHukum.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 3 : 4}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            Belum ada data dasar hukum
                                        </td>
                                    </tr>
                                ) : (
                                    dasarHukum.data.map((item) => {
                                        // Format dengan atau tanpa instansi
                                        const formatNamaLengkap = () => {
                                            let kategoriLabel = ''
                                            
                                            if (item.kategori === 'undang_undang') {
                                                kategoriLabel = 'Undang-Undang'
                                            } else if (item.kategori === 'peraturan_pemerintah') {
                                                kategoriLabel = 'Peraturan Pemerintah'
                                            } else if (item.kategori === 'peraturan_presiden') {
                                                kategoriLabel = 'Peraturan Presiden'
                                            } else if (item.kategori === 'peraturan_menteri_badan') {
                                                // Deteksi apakah instansi adalah Badan atau Menteri
                                                if (item.instansi && item.instansi.toLowerCase().startsWith('badan')) {
                                                    kategoriLabel = `Peraturan ${item.instansi}`
                                                } else {
                                                    kategoriLabel = `Peraturan Menteri ${item.instansi}`
                                                }
                                            } else if (item.kategori === 'keputusan_menteri_kepala_badan') {
                                                // Deteksi apakah instansi adalah Badan atau Menteri
                                                if (item.instansi && item.instansi.toLowerCase().startsWith('badan')) {
                                                    kategoriLabel = `Keputusan Kepala ${item.instansi}`
                                                } else {
                                                    kategoriLabel = `Keputusan Menteri ${item.instansi}`
                                                }
                                            }
                                            
                                            return `${kategoriLabel} Nomor ${item.nomor} Tahun ${item.tahun}`
                                        }

                                        return (
                                            <tr
                                                key={item.id}
                                                className="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                            {formatNamaLengkap()}
                                                        </div>
                                                        <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                            tentang {item.tentang}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-center">
                                                    <div className="text-sm text-neutral-900 dark:text-white">
                                                        {item.tahun}
                                                    </div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-center">
                                                    <span
                                                        className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
                                                            item.status === 'aktif'
                                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                                : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300'
                                                        }`}
                                                    >
                                                        {item.status === 'aktif'
                                                            ? 'Aktif'
                                                            : 'Nonaktif'}
                                                    </span>
                                                </td>
                                                {!isPJ && (
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="flex items-center justify-center gap-2">
                                                            <Link
                                                                href={`/dasar-hukum/${item.id}/edit`}
                                                            >
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="gap-2"
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                    Edit
                                                                </Button>
                                                            </Link>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                                                onClick={() => {
                                                                    handleDelete(item.id, formatNamaLengkap())
                                                                }}
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                                Hapus
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        )
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {dasarHukum.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-neutral-200 px-6 py-3 dark:border-neutral-800">
                            <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                Menampilkan {dasarHukum.data.length} dari{' '}
                                {dasarHukum.total} data
                            </div>
                            <div className="flex gap-2">
                                {dasarHukum.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700'
                                        } ${!link.url && 'cursor-not-allowed opacity-50'}`}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    )
}
