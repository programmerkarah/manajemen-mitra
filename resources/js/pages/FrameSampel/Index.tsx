import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Database, Search } from 'lucide-react';
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
    const [perPage] = useState(12);

    const filtered = useMemo(
        () =>
            search.trim()
                ? kegiatans.filter(
                      (k) =>
                          k.nama_kegiatan
                              .toLowerCase()
                              .includes(search.toLowerCase()) ||
                          k.kode_kegiatan
                              .toLowerCase()
                              .includes(search.toLowerCase()),
                  )
                : kegiatans,
        [kegiatans, search],
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
            <Head title="Manajemen Frame Sampel" />

            <PageHeader
                title="Manajemen Frame Sampel"
                description="Kelola daftar frame sampel untuk setiap kegiatan dengan pencarian dan pagination yang lebih nyaman dibaca"
            />

            <ContentCard>
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                        <Input
                            placeholder="Cari nama kegiatan atau kode kegiatan..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setCurrentPage(1);
                            }}
                            className="pl-9"
                        />
                    </div>
                    <div className="rounded-2xl border border-neutral-200 bg-neutral-50/80 px-4 py-2 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900/40 dark:text-neutral-300">
                        Menampilkan{' '}
                        {filtered.length === 0
                            ? 0
                            : (effectiveCurrentPage - 1) * perPage + 1}
                        -
                        {Math.min(
                            effectiveCurrentPage * perPage,
                            filtered.length,
                        )}{' '}
                        dari {filtered.length} kegiatan
                    </div>
                </div>

                {filtered.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 py-12 text-sm text-muted-foreground">
                        <Database className="h-10 w-10 opacity-30" />
                        <span>Tidak ada kegiatan ditemukan.</span>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="overflow-x-auto rounded-2xl border border-neutral-200 dark:border-neutral-800">
                            <table className="w-full text-sm">
                                <thead className="bg-neutral-50/70 text-neutral-700 dark:bg-neutral-900/50 dark:text-neutral-300">
                                    <tr>
                                        <th className="py-3 pr-4 pl-4 text-left font-medium">
                                            Nama Kegiatan
                                        </th>
                                        <th className="py-3 pr-4 text-left font-medium">
                                            Tahun
                                        </th>
                                        <th className="py-3 pr-4 text-center font-medium">
                                            Jumlah Frame
                                        </th>
                                        <th className="py-3 pr-4 text-right font-medium" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-800 dark:bg-neutral-950/40">
                                    {paginated.map((kegiatan) => (
                                        <tr
                                            key={kegiatan.id}
                                            className="transition-colors hover:bg-neutral-50/80 dark:hover:bg-neutral-900/70"
                                        >
                                            <td className="py-3 pr-4 pl-4">
                                                <div className="font-medium text-neutral-900 dark:text-white">
                                                    {kegiatan.nama_kegiatan}
                                                </div>
                                            </td>
                                            <td className="py-3 pr-4 text-neutral-600 dark:text-neutral-300">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="py-3 pr-4 text-center">
                                                <span className="inline-flex rounded-full border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                                    {
                                                        kegiatan.kegiatan_frame_sampel_count
                                                    }
                                                </span>
                                            </td>
                                            <td className="py-3 pr-4 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/kegiatan/${kegiatan.hashed_id}/frame-sampel`}
                                                    >
                                                        Kelola
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {totalPages > 1 && (
                            <div className="flex flex-col gap-3 border-t border-neutral-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Halaman {effectiveCurrentPage} dari{' '}
                                    {totalPages}
                                </p>
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
                            </div>
                        )}
                    </div>
                )}
            </ContentCard>
        </AppLayout>
    );
}
