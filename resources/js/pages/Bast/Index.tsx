import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { StatusBadge } from '@/components/status-badge';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, Plus, Search } from 'lucide-react';
import { useState } from 'react';

interface BastItem {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    tanggal_bast: string;
    status: string;
    file_path: string | null;
    jumlah_petugas: number;
}

interface KegiatanItem {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    ketua_tim: string | null;
}

interface PeriodeItem {
    bulan: number;
    tahun: number;
    bulan_label: string;
}

interface DataItem {
    kegiatan: KegiatanItem;
    periode: PeriodeItem;
    bast: BastItem | null;
    has_bast: boolean;
}

interface IndexProps {
    data: DataItem[];
    filters: {
        search?: string;
    };
    active_year: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
];

export default function Index({ data, filters, active_year }: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/bast', { search }, { preserveState: true });
    };

    const getStatusBadge = (status: string) => {
        // StatusBadge expects a `status` string and handles label/styling internally
        return <StatusBadge status={status} />;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="BAST" />

            <div className="space-y-6">
                <PageHeader
                    title="Berita Acara Serah Terima (BAST)"
                    description="Kelola Berita Acara Serah Terima hasil pekerjaan petugas"
                />

                {/* Search */}
                <ContentCard>
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                            <Input
                                type="text"
                                placeholder="Cari kegiatan..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit">Cari</Button>
                        {filters.search && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setSearch('');
                                    router.get('/bast', {}, { preserveState: true });
                                }}
                            >
                                Reset
                            </Button>
                        )}
                    </form>
                </ContentCard>

                {/* Table */}
                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Periode
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Nama Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Status BAST
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {data.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-12 text-center text-neutral-500">
                                            Tidak ada data BAST untuk tahun {active_year}
                                        </td>
                                    </tr>
                                ) : (
                                    data.map((item, index) => (
                                        <tr
                                            key={`${item.kegiatan.id}-${item.periode.bulan}-${item.periode.tahun}`}
                                            className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                        >
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                                                <div className="font-medium">
                                                    {item.periode.bulan_label} {item.periode.tahun}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                                                <div className="font-medium">{item.kegiatan.nama_kegiatan}</div>
                                                <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {item.kegiatan.kode_kegiatan}
                                                    {item.kegiatan.ketua_tim && ` • Ketua Tim: ${item.kegiatan.ketua_tim}`}
                                                </div>
                                                {item.has_bast && item.bast && (
                                                    <div className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                        {item.bast.nomor_bast} • {item.bast.jumlah_petugas} petugas
                                                    </div>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-center text-sm">
                                                {item.has_bast && item.bast ? (
                                                    getStatusBadge(item.bast.status)
                                                ) : (
                                                    <StatusBadge status="not_created" />
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-center text-sm">
                                                <div className="flex items-center justify-center gap-2">
                                                    {item.has_bast && item.bast ? (
                                                        <>
                                                            <Link href={`/bast/${item.bast.hashed_id}`}>
                                                                <Button size="sm" variant="outline">
                                                                    <FileText className="mr-1 h-4 w-4" />
                                                                    Lihat BAST
                                                                </Button>
                                                            </Link>
                                                        </>
                                                    ) : (
                                                        <Link href={`/bast/kegiatan/${item.kegiatan.hashed_id}/create?bulan=${item.periode.bulan}&tahun=${item.periode.tahun}`}>
                                                            <Button size="sm">
                                                                <Plus className="mr-1 h-4 w-4" />
                                                                Buat BAST
                                                            </Button>
                                                        </Link>
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
