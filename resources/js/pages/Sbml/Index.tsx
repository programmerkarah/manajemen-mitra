import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
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
import { useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'SBML', href: '/sbml' }];

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

    const summary = useMemo(() => {
        const active = year_groups.filter(
            (group) => group.status === 'aktif',
        ).length;
        return {
            totalYears: year_groups.length,
            active,
            categories: year_groups.reduce(
                (total, group) => total + group.count,
                0,
            ),
            latestYear:
                [...year_groups].sort(
                    (left, right) => right.tahun_anggaran - left.tahun_anggaran,
                )[0]?.tahun_anggaran ?? null,
        };
    }, [year_groups]);

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

            <div className="space-y-5">
                <PageHeader
                    title="SBML"
                    description="Kelola Satuan Biaya Masukan Lainnya dan batas honor per tahun anggaran."
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

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <ContentCard>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Tahun tersedia
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.totalYears}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Tahun aktif
                        </p>
                        <div className="mt-1 flex items-center gap-2">
                            <p className="text-2xl font-semibold">
                                {summary.active}
                            </p>
                            <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Total konfigurasi
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.categories}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Tahun terbaru
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.latestYear ?? '—'}
                        </p>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 className="font-semibold">
                                Konfigurasi per Tahun Anggaran
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Buka detail untuk melihat kombinasi jenis
                                kegiatan, status kepegawaian, penugasan, dan
                                batas honor.
                            </p>
                        </div>
                        <Badge variant="outline">
                            {year_groups.length} tahun
                        </Badge>
                    </div>

                    {year_groups.length === 0 ? (
                        <div className="flex min-h-64 flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 text-center dark:border-neutral-700">
                            <DollarSign className="h-10 w-10 text-muted-foreground/30" />
                            <p className="mt-3 font-medium">
                                Belum ada konfigurasi SBML
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Tambahkan tahun anggaran untuk mulai mengatur
                                batas honor.
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {[...year_groups]
                                .sort(
                                    (left, right) =>
                                        right.tahun_anggaran -
                                        left.tahun_anggaran,
                                )
                                .map((group) => (
                                    <div
                                        key={group.tahun_anggaran}
                                        className="rounded-2xl border border-neutral-200 p-4 transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="flex items-start gap-3">
                                                <div className="rounded-xl bg-neutral-100 p-2.5 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                                    <Calendar className="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p className="text-lg font-semibold">
                                                        {group.tahun_anggaran}
                                                    </p>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {group.count}{' '}
                                                        konfigurasi biaya
                                                    </p>
                                                </div>
                                            </div>
                                            <StatusBadge
                                                status={group.status}
                                            />
                                        </div>

                                        <div className="mt-4 flex items-center gap-2 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                                className="flex-1 gap-2"
                                            >
                                                <Link
                                                    href={`/sbml/${group.tahun_anggaran}`}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    Detail
                                                </Link>
                                            </Button>
                                            {!isPJ && (
                                                <>
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        asChild
                                                        className="h-9 w-9"
                                                    >
                                                        <Link
                                                            href={`/sbml/${group.tahun_anggaran}/edit`}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-9 w-9 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                        onClick={() =>
                                                            handleDelete(
                                                                group.tahun_anggaran,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                ))}
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
