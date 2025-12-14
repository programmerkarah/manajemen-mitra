import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, Eye, Pencil, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kegiatan', href: '/kegiatan' },
];

interface User {
    id: number;
    name: string;
    email: string;
}

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tahun_anggaran: number;
    pagu_anggaran: number | null;
    status: string;
    ketua_tim: User;
}

interface KegiatanIndexProps {
    kegiatans: {
        data: Kegiatan[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        tahun?: string;
    };
}

export default function Index({ kegiatans, filters }: KegiatanIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [tahun, setTahun] = useState(filters.tahun || '');

    // Generate tahun options (5 tahun ke belakang dan 2 tahun ke depan)
    const currentYear = new Date().getFullYear();
    const tahunOptions = Array.from({ length: 8 }, (_, i) => currentYear - 5 + i);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/kegiatan',
            { search, status, tahun },
            { preserveState: true, replace: true }
        );
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setTahun('');
        router.get('/kegiatan', {}, { preserveState: true });
    };

    const getStatusBadge = (status: string) => {
        const badges: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400',
            divalidasi:
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            selesai: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            dibatalkan: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        };
        return badges[status] || badges.draft;
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            draft: 'Draft',
            divalidasi: 'Divalidasi',
            selesai: 'Selesai',
            dibatalkan: 'Dibatalkan',
        };
        return labels[status] || status;
    };

    const formatCurrency = (value: number | null | undefined) => {
        if (!value || isNaN(value)) return 'Rp 0'
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kegiatan" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Kegiatan"
                    description="Kelola kegiatan dan anggaran"
                >
                    <Button size="sm" asChild className="gap-2">
                        <Link href="/kegiatan/create">
                            <Plus className="h-4 w-4" />
                            Tambah Kegiatan
                        </Link>
                    </Button>
                </PageHeader>

                {/* Filters */}
                <ContentCard>
                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kegiatan..."
                                    className="h-10 pl-10"
                                />
                            </div>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="h-10 rounded-lg border border-neutral-300 px-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                            >
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="divalidasi">Divalidasi</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                            <select
                                value={tahun}
                                onChange={(e) => setTahun(e.target.value)}
                                className="h-10 rounded-lg border border-neutral-300 px-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                            >
                                    <option value="">Semua Tahun</option>
                                    {tahunOptions.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                                <div className="flex gap-2">
                                    <Button type="submit" className="flex-1 gap-2">
                                        <Search className="h-4 w-4" />
                                        Filter
                                    </Button>
                                    {(search || status || tahun) && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={handleReset}
                                            className="gap-2"
                                        >
                                            <X className="h-4 w-4" />
                                            Reset
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Kode
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Nama Kegiatan
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Tahun
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Pagu Anggaran
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Ketua Tim
                                    </th>
                                    <th className="px-6 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    <th className="px-6 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {kegiatans.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Search className="h-8 w-8 text-neutral-400" />
                                                <p>Tidak ada kegiatan yang ditemukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    kegiatans.data.map((kegiatan) => (
                                        <tr
                                            key={kegiatan.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {kegiatan.kode_kegiatan}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.nama_kegiatan}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {formatCurrency(kegiatan.pagu_anggaran)}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                                {kegiatan.ketua_tim.name}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${getStatusBadge(kegiatan.status)}`}
                                                >
                                                    {getStatusLabel(kegiatan.status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link href={`/kegiatan/${kegiatan.hashed_id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {kegiatans.links && (
                        <div className="flex items-center justify-center gap-1 border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                            {kegiatans.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.get(link.url)}
                                    disabled={!link.url}
                                    className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                        link.active
                                            ? 'bg-blue-600 text-white shadow-sm'
                                            : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                    } ${!link.url && 'cursor-not-allowed opacity-50'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </ContentCard>
                                                        Detail
            </div>
        </AppLayout>
    );
}
