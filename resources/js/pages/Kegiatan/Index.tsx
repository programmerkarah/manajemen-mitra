import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
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

            <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Kegiatan
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Kelola kegiatan dan anggaran
                                </p>
                            </div>
                            <Link
                                href="/kegiatan/create"
                                className="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                            >
                                Tambah Kegiatan
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="p-6">
                        <form onSubmit={handleSearch}>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kegiatan..."
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                />
                                <select
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
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
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                                    <option value="">Semua Tahun</option>
                                    {tahunOptions.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                                <div className="flex gap-2">
                                    <button
                                        type="submit"
                                        className="flex-1 rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                    >
                                        Filter
                                    </button>
                                    {(search || status || tahun) && (
                                        <button
                                            type="button"
                                            onClick={handleReset}
                                            className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                                        >
                                            Reset
                                        </button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Table */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Kode
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Nama Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Tahun
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Pagu Anggaran
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Ketua Tim
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                {kegiatans.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            Tidak ada kegiatan yang ditemukan
                                        </td>
                                    </tr>
                                ) : (
                                    kegiatans.data.map((kegiatan) => (
                                        <tr
                                            key={kegiatan.id}
                                            className="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                        >
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                                {kegiatan.kode_kegiatan}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {kegiatan.nama_kegiatan}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {kegiatan.tahun_anggaran}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {formatCurrency(kegiatan.pagu_anggaran)}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {kegiatan.ketua_tim.name}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5 ${getStatusBadge(kegiatan.status)}`}
                                                >
                                                    {getStatusLabel(kegiatan.status)}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                                <div className="flex gap-2">
                                                    <Link
                                                        href={`/kegiatan/${kegiatan.hashed_id}`}
                                                        className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        Detail
                                                    </Link>
                                                    <Link
                                                        href={`/kegiatan/${kegiatan.hashed_id}/edit`}
                                                        className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    >
                                                        Edit
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {kegiatans?.meta?.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                            <div className="flex flex-1 justify-between sm:hidden">
                                {kegiatans?.meta?.current_page > 1 && (
                                    <Link
                                        href={
                                            kegiatans.links[kegiatans.meta.current_page - 1]?.url ||
                                            '#'
                                        }
                                        className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {kegiatans?.meta?.current_page < kegiatans?.meta?.last_page && (
                                    <Link
                                        href={
                                            kegiatans.links[kegiatans.meta.current_page + 1]?.url ||
                                            '#'
                                        }
                                        className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-gray-700 dark:text-gray-300">
                                        Showing{' '}
                                        <span className="font-medium">{kegiatans?.meta?.from}</span> to{' '}
                                        <span className="font-medium">{kegiatans?.meta?.to}</span> of{' '}
                                        <span className="font-medium">{kegiatans?.meta?.total}</span>{' '}
                                        results
                                    </p>
                                </div>
                                <div>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                        {kegiatans.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-medium ${
                                                    link.active
                                                        ? 'z-10 border-blue-500 bg-blue-50 text-blue-600 dark:border-blue-400 dark:bg-blue-900/30 dark:text-blue-400'
                                                        : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600'
                                                } ${
                                                    index === 0
                                                        ? 'rounded-l-md'
                                                        : index === kegiatans.links.length - 1
                                                          ? 'rounded-r-md'
                                                          : ''
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
