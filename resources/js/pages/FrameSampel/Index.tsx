import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { encryptFilters } from '@/utils/encryption';
import { Head, router } from '@inertiajs/react';
import ArrowRight from 'lucide-react/icons/arrow-right';
import ChevronLeft from 'lucide-react/icons/chevron-left';
import ChevronRight from 'lucide-react/icons/chevron-right';
import Database from 'lucide-react/icons/database';
import Search from 'lucide-react/icons/search';
import SlidersHorizontal from 'lucide-react/icons/sliders-horizontal';

import { useMemo, useState } from 'react';

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tahun_anggaran: number;
    kegiatan_frame_sampel_count: number;
}

interface Props {
    kegiatans: Kegiatan[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Frame Sampel', href: '/frame-sampel' },
];

export default function FrameSampelIndex({ kegiatans }: Props) {
    const [search, setSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(10);

    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return kegiatans;

        return kegiatans.filter(
            (kegiatan) =>
                kegiatan.nama_kegiatan.toLowerCase().includes(query) ||
                kegiatan.kode_kegiatan.toLowerCase().includes(query) ||
                String(kegiatan.tahun_anggaran).includes(query),
        );
    }, [kegiatans, search]);

    const configuredCount = kegiatans.filter(
        (item) => item.kegiatan_frame_sampel_count > 0,
    ).length;
    const totalFrameCount = kegiatans.reduce(
        (sum, item) => sum + item.kegiatan_frame_sampel_count,
        0,
    );

    const totalPages = Math.ceil(filtered.length / perPage);
    const effectiveCurrentPage =
        totalPages > 0 ? Math.min(currentPage, totalPages) : 1;

    const paginated = useMemo(() => {
        const start = (effectiveCurrentPage - 1) * perPage;
        return filtered.slice(start, start + perPage);
    }, [effectiveCurrentPage, filtered, perPage]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Frame Sampel" />

            <PageHeader
                title="Frame Sampel"
                description="Kelola konfigurasi sampel per kegiatan menggunakan alur yang sama dengan form tambah/edit kegiatan."
            />

            <div className="grid gap-4 md:grid-cols-3">
                <ContentCard>
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Total kegiatan
                            </p>
                            <p className="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">
                                {kegiatans.length}
                            </p>
                        </div>
                        <div className="rounded-xl bg-neutral-100 p-2.5 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                            <Database className="h-5 w-5" />
                        </div>
                    </div>
                </ContentCard>

                <ContentCard>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Sudah dikonfigurasi
                        </p>
                        <p className="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">
                            {configuredCount}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {kegiatans.length - configuredCount} kegiatan belum
                            memiliki detail frame
                        </p>
                    </div>
                </ContentCard>

                <ContentCard>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Total detail frame
                        </p>
                        <p className="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">
                            {totalFrameCount}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Akumulasi seluruh kegiatan
                        </p>
                    </div>
                </ContentCard>
            </div>

            <ContentCard>
                <div className="space-y-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <SlidersHorizontal className="h-5 w-5 text-muted-foreground" />
                                <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Pilih Kegiatan
                                </h2>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Buka konfigurasi Manajemen Lapangan untuk
                                mengatur metode sampling, frame, unit sampel,
                                metadata, dan detail frame.
                            </p>
                        </div>

                        <div className="relative w-full lg:w-96">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari kegiatan, kode, atau tahun..."
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setCurrentPage(1);
                                }}
                                className="pl-9"
                            />
                        </div>
                    </div>

                    {filtered.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 py-12 text-center dark:border-neutral-700">
                            <Database className="mb-3 h-9 w-9 text-muted-foreground/40" />
                            <p className="font-medium text-neutral-700 dark:text-neutral-200">
                                Kegiatan tidak ditemukan
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Coba gunakan nama, kode, atau tahun yang lain.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800">
                            <div className="hidden grid-cols-[minmax(0,1.5fr)_9rem_11rem_9rem] gap-4 bg-neutral-50 px-4 py-2.5 text-xs font-medium tracking-wide text-muted-foreground uppercase md:grid dark:bg-neutral-900/70">
                                <span>Kegiatan</span>
                                <span>Tahun</span>
                                <span>Konfigurasi Frame</span>
                                <span className="text-right">Aksi</span>
                            </div>

                            <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginated.map((kegiatan) => {
                                    const configured =
                                        kegiatan.kegiatan_frame_sampel_count >
                                        0;

                                    return (
                                        <div
                                            key={kegiatan.id}
                                            className="grid gap-3 px-4 py-4 transition-colors hover:bg-neutral-50 md:grid-cols-[minmax(0,1.5fr)_9rem_11rem_9rem] md:items-center md:gap-4 dark:hover:bg-neutral-900/50"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate font-medium text-neutral-900 dark:text-white">
                                                    {kegiatan.nama_kegiatan}
                                                </p>
                                                <p className="mt-1 font-mono text-xs text-muted-foreground">
                                                    {kegiatan.kode_kegiatan}
                                                </p>
                                            </div>

                                            <span className="text-sm text-neutral-600 dark:text-neutral-300">
                                                {kegiatan.tahun_anggaran}
                                            </span>

                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge
                                                    variant={
                                                        configured
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {configured
                                                        ? 'Terkonfigurasi'
                                                        : 'Belum lengkap'}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {
                                                        kegiatan.kegiatan_frame_sampel_count
                                                    }{' '}
                                                    detail
                                                </span>
                                            </div>

                                            <div className="flex justify-end">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-2"
                                                    onClick={() =>
                                                        router.post(
                                                            '/kegiatan/edit',
                                                            {
                                                                state: encryptFilters({
                                                                    kegiatan:
                                                                        kegiatan.hashed_id,
                                                                    step: 'lapangan',
                                                                }),
                                                            },
                                                        )
                                                    }
                                                >
                                                    Kelola
                                                    <ArrowRight className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    <div className="flex flex-col gap-3 border-t border-neutral-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                        <p className="text-sm text-muted-foreground">
                            Menampilkan{' '}
                            {filtered.length === 0
                                ? 0
                                : (effectiveCurrentPage - 1) * perPage + 1}
                            –
                            {Math.min(
                                effectiveCurrentPage * perPage,
                                filtered.length,
                            )}{' '}
                            dari {filtered.length} kegiatan
                        </p>

                        {totalPages > 1 && (
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((prev) =>
                                            Math.max(prev - 1, 1),
                                        )
                                    }
                                    disabled={effectiveCurrentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Sebelumnya
                                </Button>
                                <span className="min-w-20 text-center text-sm text-muted-foreground">
                                    {effectiveCurrentPage} / {totalPages}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((prev) =>
                                            Math.min(prev + 1, totalPages),
                                        )
                                    }
                                    disabled={
                                        effectiveCurrentPage >= totalPages
                                    }
                                >
                                    Berikutnya
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </ContentCard>
        </AppLayout>
    );
}
