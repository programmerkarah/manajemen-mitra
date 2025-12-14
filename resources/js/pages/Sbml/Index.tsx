import AppLayout from '@/layouts/app-layout'
import { ContentCard } from '@/components/content-card'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem, Sbml } from '@/types'
import { Head, Link, router } from '@inertiajs/react'
import { Pencil, Plus, Trash2, Eye } from 'lucide-react'

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
        
        if (!acc[year]) {
            acc[year] = {
                tahun_anggaran: year,
                status: sbml.status,
                count: 0,
            }
        }
        acc[year].count++
        // If any entry is active, the year is considered active
        if (sbml.status === 'aktif') {
            acc[year].status = 'aktif'
        }
        return acc
    }, {} as Record<number, { tahun_anggaran: number; status: string; count: number }>)

    const yearGroups = Object.values(groupedByYear).sort((a, b) => b.tahun_anggaran - a.tahun_anggaran)

    const handleDelete = (tahun: number) => {
        if (confirm(`Apakah Anda yakin ingin menghapus semua SBML untuk tahun ${tahun}?`)) {
            router.delete(`/sbml/year/${tahun}`)
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SBML" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="SBML (Satuan Biaya Masukan Lainnya)"
                    description="Kelola batas maksimal honor per bulan untuk semua kategori"
                >
                    <Button size="sm" asChild className="gap-2">
                        <Link href="/sbml/create">
                            <Plus className="h-4 w-4" />
                            Tambah Tahun Anggaran
                        </Link>
                    </Button>
                </PageHeader>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Tahun Anggaran
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
                                {yearGroups.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            Belum ada data SBML
                                        </td>
                                    </tr>
                                ) : (
                                    yearGroups.map((group) => (
                                        <tr
                                            key={group.tahun_anggaran}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {group.tahun_anggaran}
                                                <span className="ml-2 text-xs text-neutral-500 dark:text-neutral-400">
                                                    ({group.count} kategori)
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                        group.status === 'aktif'
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                    }`}
                                                >
                                                    {group.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link href={`/sbml/${group.tahun_anggaran}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link href={`/sbml/${group.tahun_anggaran}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(group.tahun_anggaran)}
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-600 dark:text-red-400" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    )
}
