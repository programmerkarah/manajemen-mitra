import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText, Plus } from 'lucide-react';

interface ReplacementItem {
    id: number;
    hashed_id: string;
    petugas_berhenti_nama: string | null;
    petugas_pengganti_nama: string | null;
    pml_cover_nama: string | null;
    tanggal_berhenti: string | null;
    tanggal_mulai_pkpp: string | null;
    status: string;
    has_pkpp_contract: boolean;
}

interface IndexProps {
    replacements: ReplacementItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
    { title: 'PK Petugas Pengganti', href: '#' },
];

function formatStatus(status: string): string {
    switch (status) {
        case 'pengganti_ditetapkan':
            return 'Pengganti Ditetapkan';
        case 'pml_cover':
            return 'PML Cover';
        case 'selesai':
            return 'Selesai';
        case 'dibatalkan':
            return 'Dibatalkan';
        default:
            return 'Draft';
    }
}

export default function Index({ replacements }: IndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="PK Petugas Pengganti" />

            <div className="space-y-6 p-6">
                <PageHeader
                    title="PK Petugas Pengganti"
                    description="Pintu masuk resmi untuk generate PK baru petugas pengganti berdasarkan replacement yang sudah ditetapkan."
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali ke Perjanjian Kerja
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard className="border-blue-200/70 bg-gradient-to-br from-blue-50 to-white dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-1">
                            <div className="text-sm font-medium text-blue-700 dark:text-blue-300">
                                Alur pengantian petugas Sensus Ekonomi
                            </div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400"></p>
                        </div>
                        <Badge variant="secondary" className="w-fit">
                            {replacements.length} Replacement
                        </Badge>
                    </div>
                </ContentCard>

                <div className="grid gap-4 lg:grid-cols-2">
                    {replacements.length === 0 ? (
                        <ContentCard className="lg:col-span-2">
                            <div className="flex flex-col items-center gap-3 py-12 text-center">
                                <FileText className="h-12 w-12 text-neutral-400" />
                                <p className="text-lg font-medium text-neutral-900 dark:text-neutral-100">
                                    Belum ada data replacement
                                </p>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                    Replacement petugas akan muncul di sini
                                    setelah petugas berhenti ditetapkan.
                                </p>
                            </div>
                        </ContentCard>
                    ) : (
                        replacements.map((replacement) => (
                            <ContentCard key={replacement.id}>
                                <div className="flex h-full flex-col gap-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                                {replacement.petugas_pengganti_nama ??
                                                    'Petugas Pengganti belum ditetapkan'}
                                            </h3>
                                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                                Berhenti:{' '}
                                                {replacement.petugas_berhenti_nama ??
                                                    '-'}
                                            </p>
                                        </div>
                                        <Badge variant="outline">
                                            {formatStatus(replacement.status)}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-3 rounded-lg bg-neutral-50 p-4 text-sm dark:bg-neutral-800/40">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-neutral-500">
                                                PML Cover
                                            </span>
                                            <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                {replacement.pml_cover_nama ??
                                                    '-'}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-neutral-500">
                                                Tanggal Berhenti
                                            </span>
                                            <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                {replacement.tanggal_berhenti ??
                                                    '-'}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-neutral-500">
                                                Mulai PKPP
                                            </span>
                                            <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                {replacement.tanggal_mulai_pkpp ??
                                                    '-'}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2 pt-1">
                                        <Button asChild className="flex-1">
                                            <Link
                                                href={`/spk/petugas-pengganti/${replacement.hashed_id}/pkpp-contracts/create`}
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                Generate PK Petugas Pengganti
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            asChild
                                            className="flex-1"
                                            disabled={
                                                !replacement.has_pkpp_contract
                                            }
                                        >
                                            <Link
                                                href={`/spk/petugas-pengganti/${replacement.hashed_id}/pkpp-contracts/create`}
                                            >
                                                <FileText className="mr-2 h-4 w-4" />
                                                Lihat / Lanjutkan
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </ContentCard>
                        ))
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
