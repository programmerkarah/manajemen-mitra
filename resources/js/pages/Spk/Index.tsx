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
import { Download, Eye, Plus, ChevronLeft, ChevronRight, FileEdit } from 'lucide-react';
import { useState } from 'react';

interface KegiatanItem {
    periode_id: number;
    periode_hashed_id: string;
    kegiatan_hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    jumlah_petugas_non_organik: number;
}

interface MonthlyPeriodeItem {
    tahun: number;
    bulan: number;
    bulan_label: string;
    total_petugas_non_organik: number;
    total_spk: number;
    spk_status: string;
    spk_status_type: 'not_created' | 'created';
    has_revision: boolean;
    kegiatan_list: KegiatanItem[];
}

interface IndexProps {
    periodeList: {
        data: MonthlyPeriodeItem[];
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
        bulan?: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SPK', href: '/spk' },
];

const bulanOptions = [
    { value: 'all', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

export default function Index({ periodeList, filters }: IndexProps) {
    const { auth } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [bulan, setBulan] = useState(filters.bulan?.toString() || 'all');

    // Check if user can create SPK (only admin and pj)
    const canCreateSpk = auth.activeRole?.name === 'admin' || auth.activeRole?.name === 'approver';

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/spk',
            {
                search: search || undefined,
                bulan: bulan && bulan !== 'all' ? bulan : undefined,
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
        setBulan('all');
        router.get(
            '/spk',
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SPK" />

            <div className="space-y-6">
                <PageHeader
                    title="Surat Perjanjian Kerja (SPK)"
                    description="Kelola Surat Perjanjian Kerja untuk petugas per bulan"
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
                                <label htmlFor="bulan" className="mb-2 block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    Bulan
                                </label>
                                <Select
                                    value={bulan}
                                    onValueChange={(value) => setBulan(value)}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Semua Bulan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bulanOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
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
                                        Periode
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Jumlah Petugas Non Organik
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Total SPK
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Status SPK
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {periodeList.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                            Tidak ada periode yang memerlukan SPK
                                        </td>
                                    </tr>
                                ) : (
                                    periodeList.data.map((monthData, index) => (
                                        <tr key={`${monthData.tahun}-${monthData.bulan}`} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                            <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-white">
                                                {monthData.bulan_label} {monthData.tahun}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="space-y-1">
                                                    {monthData.kegiatan_list.map((kegiatan, kegIndex) => (
                                                        <div key={kegiatan.periode_id} className="text-sm">
                                                            <div className="font-medium text-neutral-900 dark:text-white">
                                                                {kegiatan.nama_kegiatan}
                                                            </div>
                                                            <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                {kegiatan.kode_kegiatan} • {kegiatan.jumlah_petugas_non_organik} petugas non-organik
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                {monthData.total_petugas_non_organik} petugas
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                {monthData.total_spk} SPK
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                                                    monthData.spk_status_type === 'created' 
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                        : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200'
                                                }`}>
                                                    {monthData.spk_status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <div className="flex items-center justify-end gap-2">
                                                    {/* Generate SPK for all kegiatan in this month - Only if none exist yet */}
                                                    {canCreateSpk && monthData.total_spk === 0 && monthData.kegiatan_list.length > 0 && (
                                                        <Button
                                                            size="sm"
                                                            asChild
                                                            className="gap-1"
                                                        >
                                                            <Link href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/generate`}>
                                                                <Plus className="h-3.5 w-3.5" />
                                                                Generate SPK
                                                            </Link>
                                                        </Button>
                                                    )}

                                                    {/* View SPK Details - Show list of generated SPK */}
                                                    {monthData.total_spk > 0 && (
                                                        <>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => router.post('/spk/month', {
                                                                    bulan: monthData.bulan,
                                                                    tahun: monthData.tahun
                                                                })}
                                                                className="gap-1"
                                                            >
                                                                <Eye className="h-3.5 w-3.5" />
                                                                Lihat Detail SPK
                                                            </Button>

                                                            {/* Addendum SPK - Show if there are revisions */}
                                                            {canCreateSpk && monthData.has_revision && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="default"
                                                                    asChild
                                                                    className="gap-1"
                                                                >
                                                                    <Link href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/addendum?bulan=${monthData.bulan}&tahun=${monthData.tahun}`}>
                                                                        <FileEdit className="h-3.5 w-3.5" />
                                                                        Addendum SPK
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                        </>
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
                    {periodeList.data.length > 0 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 px-6 py-3 dark:border-neutral-700">
                            <div className="text-sm text-neutral-700 dark:text-neutral-300">
                                Menampilkan {periodeList.from} hingga {periodeList.to} dari {periodeList.total} bulan
                            </div>
                            <div className="flex gap-2">
                                {periodeList.links.map((link, index) => {
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
