import AppLayout from '@/layouts/app-layout'
import { ContentCard } from '@/components/content-card'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem, Sbml, SharedData } from '@/types'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { Pencil, Plus, Trash2, Eye } from 'lucide-react'
import { StatusBadge } from '@/components/status-badge'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'SBML', href: '/sbml' },
]

interface YearGroup {
    tahun_anggaran: number
    status: string
    count: number
}

interface Props {
    year_groups: YearGroup[]
}

export default function Index({ year_groups }: Props) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';

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
                    {!isPJ && (
                        <Button size="sm" asChild className="gap-2">
                            <Link href="/sbml/create">
                                <Plus className="h-4 w-4" />
                                Tambah Tahun Anggaran
                            </Link>
                        </Button>
                    )}
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
                                {year_groups.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            Belum ada data SBML
                                        </td>
                                    </tr>
                                ) : (
                                    year_groups.map((group) => (
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
                                                <StatusBadge status={group.status} />
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
                                                    {!isPJ && (
                                                        <>
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
                </ContentCard>
            </div>
        </AppLayout>
    )
}
