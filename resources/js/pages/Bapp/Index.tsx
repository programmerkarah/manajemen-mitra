import AlertCircle from 'lucide-react/icons/alert-circle';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import FileUp from 'lucide-react/icons/file-up';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface TerminData {
    termin: number;
    termin_hashed: string;
    termin_roman: string;
    bulan: number;
    bulan_label: string;
    persentase: number;
    bapp_count: number;
    spk_count: number;
    is_complete: boolean;
}

interface IndexProps {
    tahun: number;
    termin_data: TerminData[];
    has_kegiatan: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'BAPP SE2026', href: '/bapp' }];

export default function Index({ tahun, termin_data, has_kegiatan }: IndexProps) {
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
                            <p className="text-sm text-muted-foreground">
                                Pastikan kegiatan Sensus Ekonomi sudah dikonfigurasi.
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
                    description={`Upload manual BAPP Sensus Ekonomi 2026 — Tahun ${tahun}. Dokumen tidak lagi digenerate oleh SIMANTIK.`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/dashboard" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                        BAPP Termin I dan Termin II sekarang dikelola sebagai
                        dokumen manual. Pilih termin, lalu unggah PDF final untuk
                        masing-masing petugas. File yang belum diunggah akan
                        tampil sebagai tidak tersedia pada halaman /mitra.
                    </div>
                </ContentCard>

                <div className="grid gap-4 lg:grid-cols-2">
                    {termin_data.map((termin) => {
                        const uploaded = Math.min(
                            termin.bapp_count,
                            termin.spk_count,
                        );
                        const complete =
                            termin.spk_count > 0 &&
                            uploaded >= termin.spk_count;

                        return (
                            <ContentCard key={termin.termin}>
                                <div className="flex h-full flex-col gap-5">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">
                                                Termin {termin.termin_roman} ·{' '}
                                                {termin.bulan_label}
                                            </p>
                                            <h3 className="mt-1 text-xl font-semibold">
                                                BAPP {termin.persentase}%
                                            </h3>
                                        </div>
                                        <Badge
                                            variant={
                                                complete
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {complete
                                                ? 'Lengkap'
                                                : `${uploaded}/${termin.spk_count} diunggah`}
                                        </Badge>
                                    </div>

                                    <p className="text-sm text-muted-foreground">
                                        Unggah PDF BAPP final secara manual
                                        untuk setiap Perjanjian Kerja pada
                                        termin ini.
                                    </p>

                                    <Button asChild className="mt-auto w-full">
                                        <Link
                                            href={`/bapp/create?termin=${termin.termin_hashed}`}
                                            prefetch
                                        >
                                            <FileUp className="mr-2 h-4 w-4" />
                                            Kelola Upload Manual
                                        </Link>
                                    </Button>
                                </div>
                            </ContentCard>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
