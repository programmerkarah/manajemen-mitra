import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mitra', href: '/mitra' },
];

interface Mitra {
    id: number;
    hashed_id: string;
    nama: string;
    nik_masked: string;
    email: string;
    telepon: string;
    pendidikan: string;
    tahun_bergabung: number;
    status: string;
}

interface MitraIndexProps {
    mitras: {
        data: Mitra[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        tahun?: string;
    };
}

export default function Index({ mitras, filters }: MitraIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/mitra',
            { search, status },
            { preserveState: true, replace: true }
        );
    };

    const handleImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.file) return;

        post('/mitra/import', {
            preserveScroll: true,
            onSuccess: () => {
                setShowImportModal(false);
                reset();
            },
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Mitra" />
            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Data Mitra</h1>
                    <div className="flex gap-2">
                        <a
                            href="/mitra/template/download"
                            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Download Template
                        </a>
                        <button
                            onClick={() => setShowImportModal(true)}
                            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Import Excel
                        </button>
                        <Link
                            href="/mitra/create"
                            className="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                        >
                            Tambah Mitra
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <form
                    onSubmit={handleSearch}
                    className="mb-6 flex gap-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900"
                >
                    <input
                        type="text"
                        placeholder="Cari nama, NIK, atau email..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="flex-1 rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                    <button
                        type="submit"
                        className="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700"
                    >
                        Filter
                    </button>
                </form>

                {/* Table */}
                <div className="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                    <table className="w-full">
                        <thead className="bg-neutral-50 dark:bg-neutral-800">
                            <tr>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    Nama
                                </th>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    NIK
                                </th>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    Email
                                </th>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    Telepon
                                </th>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    Pendidikan
                                </th>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-sm font-semibold">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                            {mitras.data.map((mitra) => (
                                <tr key={mitra.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                    <td className="px-6 py-4 text-sm">
                                        {mitra.nama}
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        {mitra.nik_masked}
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        {mitra.email}
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        {mitra.telepon}
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        {mitra.pendidikan}
                                    </td>
                                    <td className="px-6 py-4">
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                mitra.status === 'aktif'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                            }`}
                                        >
                                            {mitra.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        <div className="flex gap-2">
                                            <Link
                                                href={`/mitra/${mitra.hashed_id}`}
                                                className="text-blue-600 hover:text-blue-800"
                                            >
                                                Detail
                                            </Link>
                                            <Link
                                                href={`/mitra/${mitra.hashed_id}/edit`}
                                                className="text-amber-600 hover:text-amber-800"
                                            >
                                                Edit
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {mitras.links && (
                    <div className="mt-4 flex justify-center gap-2">
                        {mitras.links.map((link, index) => (
                            <button
                                key={index}
                                onClick={() =>
                                    link.url && router.get(link.url)
                                }
                                disabled={!link.url}
                                className={`rounded px-3 py-1 ${
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white text-neutral-700 hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-300'
                                } ${!link.url && 'cursor-not-allowed opacity-50'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                            Import Mitra dari Excel
                        </h3>
                        <form onSubmit={handleImport}>
                            <div className="mb-4">
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Pilih File Excel
                                </label>
                                <input
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={handleFileChange}
                                    className="block w-full rounded-md border border-gray-300 p-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                                {errors.file && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.file}
                                    </p>
                                )}
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Format: .xlsx, .xls, .csv (Max: 2MB)
                                </p>
                            </div>
                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowImportModal(false);
                                        reset();
                                    }}
                                    className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing || !data.file}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processing ? 'Mengimport...' : 'Import'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
