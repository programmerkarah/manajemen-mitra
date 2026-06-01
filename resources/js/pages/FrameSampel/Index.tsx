import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Database, Search } from 'lucide-react';
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen Frame Sampel" />

            <PageHeader
                title="Manajemen Frame Sampel"
                description="Kelola daftar frame sampel untuk setiap kegiatan"
            />

            <ContentCard>
                <div className="mb-4 flex items-center gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Cari kegiatan..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                </div>

                {filtered.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 py-12 text-sm text-muted-foreground">
                        <Database className="h-10 w-10 opacity-30" />
                        <span>Tidak ada kegiatan ditemukan.</span>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="py-2 pr-4 font-medium">
                                        Nama Kegiatan
                                    </th>
                                    <th className="py-2 pr-4 font-medium">
                                        Tahun
                                    </th>
                                    <th className="py-2 pr-4 text-center font-medium">
                                        Jumlah Frame
                                    </th>
                                    <th className="py-2 font-medium" />
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.map((kegiatan) => (
                                    <tr
                                        key={kegiatan.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-2 pr-4">
                                            {kegiatan.nama_kegiatan}
                                        </td>
                                        <td className="py-2 pr-4">
                                            {kegiatan.tahun_anggaran}
                                        </td>
                                        <td className="py-2 pr-4 text-center">
                                            <span className="rounded bg-muted px-2 py-0.5 text-xs font-medium">
                                                {
                                                    kegiatan.kegiatan_frame_sampel_count
                                                }
                                            </span>
                                        </td>
                                        <td className="py-2 text-right">
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
                )}
            </ContentCard>
        </AppLayout>
    );
}
