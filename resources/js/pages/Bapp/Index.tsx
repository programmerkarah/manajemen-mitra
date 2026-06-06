import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle,
    Clock,
    FileText,
    Plus,
} from 'lucide-react';

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
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'BAPP SE2026', href: '/bapp' }];

export default function Index({
    tahun,
    termin_data,
    has_kegiatan,
    unit_sampel_items,
}: IndexProps) {
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

                <div className="grid gap-4 md:grid-cols-2">
                    {termin_data.map((termin) => (
                        <ContentCard key={termin.termin}>
                            <div className="flex flex-col gap-4">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-xl font-bold">
                                            Termin {termin.termin_roman}
                                        </h2>
                                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                            Generate bulan {termin.bulan_label}{' '}
                                            &bull; Target {termin.persentase}%
                                            pekerjaan
                                        </p>
                                    </div>
                                    {termin.is_complete ? (
                                        <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            <CheckCircle className="mr-1 h-3 w-3" />
                                            Selesai
                                        </Badge>
                                    ) : termin.bapp_count > 0 ? (
                                        <Badge variant="secondary">
                                            <Clock className="mr-1 h-3 w-3" />
                                            Sebagian
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline">
                                            Belum Ada
                                        </Badge>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-3 rounded-lg bg-neutral-50 p-3 dark:bg-neutral-800/50">
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            {termin.bapp_count}
                                        </div>
                                        <div className="text-xs text-neutral-500">
                                            BAPP Dibuat
                                        </div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-neutral-700 dark:text-neutral-200">
                                            {termin.spk_count}
                                        </div>
                                        <div className="text-xs text-neutral-500">
                                            Total SPK
                                        </div>
                                    </div>
                                </div>

                                {!termin.can_generate && (
                                    <div className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800 dark:border-yellow-800/40 dark:bg-yellow-900/20 dark:text-yellow-300">
                                        <AlertCircle className="h-4 w-4 shrink-0" />
                                        <span>
                                            Generate BAPP Termin{' '}
                                            {termin.termin_roman} baru tersedia
                                            mulai bulan {termin.bulan_label}.
                                        </span>
                                    </div>
                                )}

                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        asChild
                                        className="flex-1"
                                    >
                                        <Link
                                            href={`/bapp/termin/${termin.termin_hashed}`}
                                        >
                                            <FileText className="mr-2 h-4 w-4" />
                                            Lihat Detail
                                        </Link>
                                    </Button>
                                    <Button
                                        asChild
                                        className="flex-1"
                                        disabled={!termin.can_generate}
                                    >
                                        <Link
                                            href={`/bapp/create?termin=${termin.termin_hashed}`}
                                        >
                                            <Plus className="mr-2 h-4 w-4" />
                                            Input Realisasi & Generate
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </ContentCard>
                    ))}
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
