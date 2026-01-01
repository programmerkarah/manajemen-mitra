import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, Eye } from 'lucide-react';

interface BastItem {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    tanggal_bast: string;
    tanggal_serah_terima: string;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    petugas_nama: string;
    kegiatan_nama: string;
    kegiatan_kode: string;
    periode: string;
    file_path: string | null;
    created_by: string;
    created_at: string;
}

interface ListByMonthProps {
    bastList: BastItem[];
    filters: {
        bulan?: number;
        tahun?: number;
    };
    bulan_label: string;
    active_year: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Daftar BAST', href: '#' },
];

export default function ListByMonth({
    bastList,
    filters,
    bulan_label,
    active_year,
}: ListByMonthProps) {
    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'diterbitkan':
                return <Badge variant="default">Diterbitkan</Badge>;
            case 'draft':
                return <Badge variant="secondary">Draft</Badge>;
            case 'dibatalkan':
                return <Badge variant="destructive">Dibatalkan</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Daftar BAST - ${bulan_label} ${filters.tahun || active_year}`}
            />

            <PageHeader
                title={`Daftar BAST - ${bulan_label} ${filters.tahun || active_year}`}
                description="Daftar Berita Acara Serah Terima yang telah dibuat"
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/bast">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            </PageHeader>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {bastList.length === 0 ? (
                    <div className="col-span-full">
                        <ContentCard>
                            <div className="py-12 text-center text-neutral-500 dark:text-neutral-400">
                                Tidak ada BAST yang ditemukan untuk periode ini
                            </div>
                        </ContentCard>
                    </div>
                ) : (
                    bastList.map((bast) => (
                        <ContentCard key={bast.id}>
                            <div className="space-y-4">
                                {/* Header */}
                                <div className="flex items-start justify-between">
                                    <div className="min-w-0 flex-1">
                                        <h3 className="truncate text-lg font-semibold text-neutral-900 dark:text-white">
                                            {bast.nomor_bast}
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            {bast.petugas_nama}
                                        </p>
                                    </div>
                                    {getStatusBadge(bast.status)}
                                </div>

                                {/* Kegiatan Info */}
                                <div className="space-y-2">
                                    <div>
                                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                            Kegiatan
                                        </p>
                                        <p className="text-sm font-medium text-neutral-900 dark:text-white">
                                            {bast.kegiatan_nama}
                                        </p>
                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                            {bast.kegiatan_kode}
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                Tanggal BAST
                                            </p>
                                            <p className="text-sm text-neutral-900 dark:text-white">
                                                {bast.tanggal_bast}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                Serah Terima
                                            </p>
                                            <p className="text-sm text-neutral-900 dark:text-white">
                                                {bast.tanggal_serah_terima}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                            Dibuat: {bast.created_at}
                                        </p>
                                    </div>
                                </div>

                                {/* Actions */}
                                <div className="flex gap-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="flex-1"
                                        asChild
                                    >
                                        <Link href={`/bast/${bast.hashed_id}`}>
                                            <Eye className="mr-1 h-4 w-4" />
                                            Detail
                                        </Link>
                                    </Button>
                                    {bast.file_path && (
                                        <Button
                                            size="sm"
                                            className="flex-1"
                                            onClick={() =>
                                                handleDownload(bast.file_path!)
                                            }
                                        >
                                            <Download className="mr-1 h-4 w-4" />
                                            PDF
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </ContentCard>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
