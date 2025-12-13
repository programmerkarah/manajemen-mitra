import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem, Sbml } from '@/types'
import { Head, Link, router } from '@inertiajs/react'
import { Pencil, Plus, Trash2 } from 'lucide-react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'SBML', href: '/sbml' },
]

interface Props {
    sbmls: {
        data: Sbml[]
        links: any[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
}

export default function Index({ sbmls }: Props) {
    // Group SBML data by year
    const groupedByYear = sbmls.data.reduce((acc, sbml) => {
        const year = sbml.tahun_anggaran
        
        // Ensure we have valid hashed_id
        if (!sbml.hashed_id) {
            console.warn('SBML entry missing hashed_id:', sbml)
            return acc
        }
        
        if (!acc[year]) {
            acc[year] = {
                tahun_anggaran: year,
                status: sbml.status,
                hashed_id: sbml.hashed_id, // Use first entry's ID for year group
                count: 0,
            }
        }
        acc[year].count++
        // If any entry is active, the year is considered active
        if (sbml.status === 'aktif') {
            acc[year].status = 'aktif'
        }
        return acc
    }, {} as Record<number, { tahun_anggaran: number; status: string; hashed_id: string; count: number }>)

    const yearGroups = Object.values(groupedByYear).sort((a, b) => b.tahun_anggaran - a.tahun_anggaran)

    const handleDelete = (tahun: number) => {
        if (confirm(`Apakah Anda yakin ingin menghapus semua SBML untuk tahun ${tahun}?`)) {
            router.delete(`/sbml/year/${tahun}`)
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SBML" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            SBML (Satuan Biaya Masukan Lainnya)
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola batas maksimal honor per bulan untuk semua kategori
                        </p>
                    </div>
                    <Link href="/sbml/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Tahun Anggaran
                        </Button>
                    </Link>
                </div>

                {/* Table */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Tahun Anggaran
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                {yearGroups.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            Belum ada data SBML
                                        </td>
                                    </tr>
                                ) : (
                                    yearGroups.map((group) => (
                                        <tr
                                            key={group.tahun_anggaran}
                                            className="hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                                {group.tahun_anggaran}
                                                <span className="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                    ({group.count} kategori)
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                                                        group.status === 'aktif'
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                                    }`}
                                                >
                                                    {group.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/sbml/${group.hashed_id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            Lihat
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/sbml/${group.hashed_id}/edit`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(group.tahun_anggaran)}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-600" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
