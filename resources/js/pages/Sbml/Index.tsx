import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    DollarSign,
    Eye,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'SBML', href: '/sbml' },
];

interface YearGroup {
    tahun_anggaran: number;
    status: string;
    count: number;
}

interface Props {
    year_groups: YearGroup[];
}

export default function Index({ year_groups }: Props) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';

    const handleDelete = (tahun: number) => {
        if (
            confirm(
                `Apakah Anda yakin ingin menghapus semua SBML untuk tahun ${tahun}?`,
            )
        ) {
            router.delete(`/sbml/year/${tahun}`);
        }
    };

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
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap">
                                        <div className="flex items-center gap-1.5">
                                            <Calendar className="h-4 w-4" />
                                            Tahun Anggaran
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap">
                                        <div className="flex items-center gap-1.5">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Status
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {year_groups.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-6 py-12 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <DollarSign className="h-12 w-12 opacity-20" />
                                                <p className="font-medium">
                                                    Belum ada data SBML
                                                </p>
                                                <p className="text-xs">
                                                    Mulai dengan menambahkan
                                                    tahun anggaran baru
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    year_groups.map((group, index) => (
                                        <tr
                                            key={group.tahun_anggaran}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-3 text-sm">
                                                <div className="flex items-center gap-2">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                                        {String(
                                                            group.tahun_anggaran,
                                                        ).slice(-2)}
                                                    </div>
                                                    <div>
                                                        <div className="font-medium">
                                                            {
                                                                group.tahun_anggaran
                                                            }
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {group.count}{' '}
                                                            kategori
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge
                                                    status={group.status}
                                                />
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link
                                                            href={`/sbml/${group.tahun_anggaran}`}
                                                        >
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
                                                                <Link
                                                                    href={`/sbml/${group.tahun_anggaran}/edit`}
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    handleDelete(
                                                                        group.tahun_anggaran,
                                                                    )
                                                                }
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
    );
}
