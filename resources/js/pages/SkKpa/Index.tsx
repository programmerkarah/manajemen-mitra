import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Eye, Plus, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { StatusBadge } from '@/components/status-badge';

interface LatestSk {
    id: number;
    hashed_id: string;
    nomor_sk: string;
    tanggal_sk: string;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    file_path: string | null;
    signed_file_path: string | null;
}

interface KegiatanItem {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
    ketua_tim: string;
    sk_status: string;
    sk_status_type: 'not_created' | 'created' | 'revision';
    sk_count: number;
    has_personnel_changes: boolean;
    latest_sk: LatestSk | null;
}

interface IndexProps {
    kegiatan: {
        data: KegiatanItem[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        jenis_kegiatan?: 'sensus' | 'survei';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SK KPA', href: '/sk-kpa' },
];

export default function Index({ kegiatan, filters }: IndexProps) {
    const { auth } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [jenisKegiatan, setJenisKegiatan] = useState(filters.jenis_kegiatan || 'all');

    // Check if user can create SK (only admin and pj)
    const canCreateSk = auth.activeRole?.name === 'admin' || auth.activeRole?.name === 'pj';

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/sk-kpa',
            {
                search: search || undefined,
                jenis_kegiatan: jenisKegiatan && jenisKegiatan !== 'all' ? jenisKegiatan : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const handleReset = () => {
        setSearch('');
        setJenisKegiatan('all');
        router.get(
            '/sk-kpa',
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const getStatusBadgeColor = (type: string) => {
        switch (type) {
            case 'not_created':
                return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200';
            case 'created':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'revision':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            default:
                return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200';
        }
    };

    const handleDownload = (keg: KegiatanItem) => {
        const latestSk = keg.latest_sk;
        if (!latestSk) {
            alert('SK tidak tersedia');
            return;
        }

        // Prioritaskan signed file jika ada
        const filePath = latestSk.signed_file_path || latestSk.file_path;
        if (!filePath) {
            alert('File SK tidak tersedia');
            return;
        }
        
        window.open(`/${filePath}`, '_blank');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SK KPA" />

            <div className="space-y-6">
                <PageHeader
                    title="SK KPA"
                    description="Kelola Surat Keputusan Kuasa Pengguna Anggaran untuk setiap kegiatan"
                />

                {/* Filter & Search */}
                <ContentCard>
                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <label htmlFor="search" className="mb-2 block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    Cari Kegiatan
                                </label>
                                <Input
                                    id="search"
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Nama atau kode kegiatan..."
                                    className="w-full"
                                />
                            </div>

                            <div>
                                <label htmlFor="jenis_kegiatan" className="mb-2 block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    Jenis Kegiatan
                                </label>
                                <Select
                                    value={jenisKegiatan}
                                    onValueChange={(value) => setJenisKegiatan(value)}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Semua Jenis" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua</SelectItem>
                                        <SelectItem value="sensus">Sensus</SelectItem>
                                        <SelectItem value="survei">Survei</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end gap-2">
                                <Button type="submit" className="flex-1">
                                    Cari
                                </Button>
                                <Button type="button" variant="outline" onClick={handleReset}>
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </form>
                </ContentCard>

                {/* Table */}
                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Jenis
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Tahun
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Ketua Tim
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Status SK
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {kegiatan.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                            Tidak ada kegiatan yang memerlukan SK KPA
                                        </td>
                                    </tr>
                                ) : (
                                    kegiatan.data.map((keg) => (
                                        <tr key={keg.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                            <td className="px-6 py-4">
                                                <div>
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {keg.nama_kegiatan}
                                                    </div>
                                                    <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                        {keg.kode_kegiatan}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                <span className="capitalize">{keg.jenis_kegiatan}</span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                {keg.tahun_anggaran}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                {keg.ketua_tim}
                                            </td>
                                            <td className="px-6 py-4">
                                                <StatusBadge status={keg.sk_status_type} />
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <div className="flex items-center justify-end gap-2">
                                                    {/* View Latest SK Details - All roles can view */}
                                                    {keg.latest_sk && (
                                                        <Button
                                                            size="sm"
                                                            variant="secondary"
                                                            asChild
                                                            className="gap-1"
                                                        >
                                                            <Link href={`/sk-kpa/${keg.latest_sk.hashed_id}`}>
                                                                <Eye className="h-3.5 w-3.5" />
                                                                Detail
                                                            </Link>
                                                        </Button>
                                                    )}

                                                    {/* Buat SK / Buat SK Perubahan - Only admin and pj */}
                                                    {canCreateSk && (
                                                        <>
                                                            {/* Buat SK - Show only if no SK exists yet */}
                                                            {keg.sk_count === 0 && (
                                                                <Button
                                                                    size="sm"
                                                                    asChild
                                                                    className="gap-1"
                                                                >
                                                                    <Link href={`/sk-kpa/kegiatan/${keg.hashed_id}/create`}>
                                                                        <Plus className="h-3.5 w-3.5" />
                                                                        Buat SK
                                                                    </Link>
                                                                </Button>
                                                            )}

                                                            {/* SK Perubahan - Show only if SK exists AND there are personnel changes */}
                                                            {keg.sk_count > 0 && keg.has_personnel_changes && (
                                                                <Button
                                                                    size="sm"
                                                                    asChild
                                                                    className="gap-1"
                                                                >
                                                                    <Link href={`/sk-kpa/kegiatan/${keg.hashed_id}/create`}>
                                                                        <Plus className="h-3.5 w-3.5" />
                                                                        SK Perubahan
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                        </>
                                                    )}

                                                    {/* Download SK Terakhir - All roles can download */}
                                                    {keg.latest_sk?.file_path && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handleDownload(keg)}
                                                            className="gap-1"
                                                        >
                                                            <Download className="h-3.5 w-3.5" />
                                                            Download
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {kegiatan.data.length > 0 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 px-6 py-3 dark:border-neutral-700">
                            <div className="text-sm text-neutral-700 dark:text-neutral-300">
                                Menampilkan {kegiatan.from} hingga {kegiatan.to} dari {kegiatan.total} kegiatan
                            </div>
                            <div className="flex gap-2">
                                {kegiatan.links.map((link, index) => {
                                    const isFirst = link.label.includes('Previous');
                                    const isLast = link.label.includes('Next');
                                    
                                    return (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                                                    : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700'
                                            } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                                        >
                                            {isFirst ? (
                                                <ChevronLeft className="h-4 w-4" />
                                            ) : isLast ? (
                                                <ChevronRight className="h-4 w-4" />
                                            ) : (
                                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                            )}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
