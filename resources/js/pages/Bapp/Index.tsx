import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, Eye, Plus } from 'lucide-react';

interface TerminData {
    termin: number;
    termin_hashed: string;
    termin_roman: string;
    bulan: number;
    bulan_label: string;
    persentase: number;
    can_generate: boolean;
    bapp_count: number;
    spk_count: number;
    is_complete: boolean;
}

interface IndexProps {
    tahun: number;
    termin_data: TerminData[];
    has_kegiatan: boolean;
    unit_sampel_items: { id: number; nama: string }[];
    document_type?: 'regular' | 'stopped_petugas' | 'replacement_pkpp';
    replacement_termin_count?: number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'BAPP SE2026', href: '/bapp' }];

export default function Index({
    tahun,
    termin_data,
    has_kegiatan,
    unit_sampel_items,
}: IndexProps) {
    const defaultTermin = termin_data[0] ?? null;
    const createHref = '/bapp/create';
    const showHref = defaultTermin
        ? `/bapp/termin/${defaultTermin.termin_hashed}`
        : '/bapp';

    const buildCreateQuery = (
        nextDocumentType: 'regular' | 'stopped_petugas' | 'replacement_pkpp',
        nextReplacementTerminCount: number = 2,
    ) => {
        const query = new URLSearchParams();

        if (defaultTermin) {
            query.set('termin', defaultTermin.termin_hashed);
        }

        query.set('document_type', nextDocumentType);

        if (nextDocumentType === 'replacement_pkpp') {
            query.set(
                'replacement_termin_count',
                nextReplacementTerminCount === 1 ? '1' : '2',
            );
        }

        return query.toString();
    };

    const buildDocumentQuery = (
        nextDocumentType: 'regular' | 'stopped_petugas' | 'replacement_pkpp',
        nextReplacementTerminCount: number = 2,
    ) => {
        const query = new URLSearchParams();
        query.set('document_type', nextDocumentType);

        if (nextDocumentType === 'replacement_pkpp') {
            query.set(
                'replacement_termin_count',
                nextReplacementTerminCount === 1 ? '1' : '2',
            );
        }

        return query.toString();
    };

    if (!has_kegiatan) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="BAPP SE2026" />
                <div className="flex flex-col gap-6 p-6">
                    <PageHeader
                        title="BAPP SE2026"
                        description="Berita Acara Pemeriksaan Pekerjaan Sensus Ekonomi 2026"
                    />
                    <ContentCard>
                        <div className="flex flex-col items-center gap-3 py-12 text-center">
                            <AlertCircle className="h-12 w-12 text-yellow-500" />
                            <p className="text-lg font-medium">
                                Kegiatan Sensus Ekonomi tidak ditemukan
                            </p>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Pastikan kegiatan Sensus Ekonomi sudah
                                dikonfigurasi terlebih dahulu.
                            </p>
                        </div>
                    </ContentCard>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="BAPP SE2026" />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title="BAPP SE2026"
                    description={`Berita Acara Pemeriksaan Pekerjaan Sensus Ekonomi 2026 — Tahun ${tahun}`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/dashboard">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <div className="grid gap-4 xl:grid-cols-3">
                    <ContentCard className="border-neutral-200 bg-gradient-to-br from-neutral-50 to-white dark:border-neutral-800 dark:from-neutral-900 dark:to-neutral-950">
                        <div className="flex h-full flex-col gap-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium text-neutral-600 dark:text-neutral-300">
                                        Dokumen Operasional
                                    </p>
                                    <h3 className="text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        BAPP Petugas Berhenti
                                    </h3>
                                </div>
                                <Badge variant="secondary">Single</Badge>
                            </div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Untuk petugas yang berhenti di tengah jalan.
                                Tidak memakai termin tab di detail.
                            </p>
                            <div className="mt-auto flex flex-wrap gap-2">
                                <Button
                                    asChild
                                    className="flex-1"
                                    disabled={!defaultTermin}
                                >
                                    <Link
                                        href={`${createHref}?${buildCreateQuery('stopped_petugas')}`}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Input & Generate
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="flex-1"
                                    disabled={!defaultTermin}
                                >
                                    <Link
                                        href={`${showHref}?${buildDocumentQuery('stopped_petugas')}`}
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Lihat Detail
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard className="border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                        <div className="flex h-full flex-col gap-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                        Dokumen Awal
                                    </p>
                                    <h3 className="text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        BAPP Petugas Reguler
                                    </h3>
                                </div>
                                <Badge className="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                                    Termin I / II
                                </Badge>
                            </div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Untuk alur reguler sesuai termin awal. Detail
                                tetap memisah per termin.
                            </p>
                            <div className="mt-auto flex flex-wrap gap-2">
                                <Button
                                    asChild
                                    className="flex-1"
                                    disabled={!defaultTermin}
                                >
                                    <Link
                                        href={`${createHref}?${buildCreateQuery('regular')}`}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Input & Generate
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="flex-1"
                                    disabled={!defaultTermin}
                                >
                                    <Link
                                        href={`${showHref}?${buildDocumentQuery('regular')}`}
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Lihat Detail
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard className="border-sky-200/70 bg-gradient-to-br from-sky-50 to-white dark:border-sky-900/40 dark:from-sky-950/30 dark:to-neutral-900">
                        <div className="flex h-full flex-col gap-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium text-sky-700 dark:text-sky-300">
                                        Dokumen Pengganti
                                    </p>
                                    <h3 className="text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        BAPP Petugas Pengganti
                                    </h3>
                                </div>
                                <Badge className="bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200">
                                    Termin I / II
                                </Badge>
                            </div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Untuk dokumen BAPP setelah replacement
                                disetujui. Proses penggantian petugas sekarang
                                dilakukan di PK Petugas Pengganti.
                            </p>
                            <div className="mt-auto flex flex-wrap gap-2">
                                <Button
                                    asChild
                                    className="flex-1"
                                    disabled={!defaultTermin}
                                >
                                    <Link
                                        href={`${createHref}?${buildCreateQuery('replacement_pkpp')}`}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Input & Generate
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="flex-1"
                                    disabled={!defaultTermin}
                                >
                                    <Link
                                        href={`${showHref}?${buildDocumentQuery('replacement_pkpp')}`}
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Lihat Detail
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                {unit_sampel_items.length > 0 && (
                    <ContentCard>
                        <h3 className="mb-2 font-semibold">
                            Unit Sampel Pencacahan
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {unit_sampel_items.map((unit) => (
                                <Badge key={unit.id} variant="secondary">
                                    {unit.nama}
                                </Badge>
                            ))}
                        </div>
                    </ContentCard>
                )}
            </div>
        </AppLayout>
    );
}
