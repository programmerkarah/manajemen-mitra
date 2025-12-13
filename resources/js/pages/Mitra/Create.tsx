import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Form } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mitra', href: '/mitra' },
    { title: 'Tambah Mitra', href: '/mitra/create' },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Mitra" />
            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold">Tambah Mitra Baru</h1>
                </div>

                <Form
                    action="/mitra"
                    method="post"
                    className="rounded-lg border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-6 md:grid-cols-2">
                                {/* Nama */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Nama Lengkap{' '}
                                        <span className="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="nama"
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.nama && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.nama}
                                        </p>
                                    )}
                                </div>

                                {/* NIK */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        NIK <span className="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="nik"
                                        maxLength={16}
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.nik && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.nik}
                                        </p>
                                    )}
                                </div>

                                {/* Email */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Email <span className="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.email && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                {/* Telepon */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Telepon <span className="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="telepon"
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.telepon && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.telepon}
                                        </p>
                                    )}
                                </div>

                                {/* Pendidikan */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Pendidikan{' '}
                                        <span className="text-red-600">*</span>
                                    </label>
                                    <select
                                        name="pendidikan"
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    >
                                        <option value="">Pilih Pendidikan</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                        <option value="D3">D3</option>
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                    </select>
                                    {errors.pendidikan && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.pendidikan}
                                        </p>
                                    )}
                                </div>

                                {/* Tahun Bergabung */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Tahun Bergabung{' '}
                                        <span className="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="tahun_bergabung"
                                        min="2000"
                                        max="2100"
                                        defaultValue={new Date().getFullYear()}
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.tahun_bergabung && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.tahun_bergabung}
                                        </p>
                                    )}
                                </div>

                                {/* Status */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Status <span className="text-red-600">*</span>
                                    </label>
                                    <select
                                        name="status"
                                        required
                                        defaultValue="aktif"
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    >
                                        <option value="aktif">Aktif</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                    {errors.status && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.status}
                                        </p>
                                    )}
                                </div>

                                {/* NPWP */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        NPWP
                                    </label>
                                    <input
                                        type="text"
                                        name="npwp"
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.npwp && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.npwp}
                                        </p>
                                    )}
                                </div>

                                {/* Bank */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Bank
                                    </label>
                                    <input
                                        type="text"
                                        name="bank"
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.bank && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.bank}
                                        </p>
                                    )}
                                </div>

                                {/* No Rekening */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        No. Rekening
                                    </label>
                                    <input
                                        type="text"
                                        name="no_rekening"
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.no_rekening && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.no_rekening}
                                        </p>
                                    )}
                                </div>

                                {/* Nama Rekening */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Nama Rekening
                                    </label>
                                    <input
                                        type="text"
                                        name="nama_rekening"
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.nama_rekening && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.nama_rekening}
                                        </p>
                                    )}
                                </div>

                                {/* Alamat */}
                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium">
                                        Alamat <span className="text-red-600">*</span>
                                    </label>
                                    <textarea
                                        name="alamat"
                                        rows={3}
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.alamat && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.alamat}
                                        </p>
                                    )}
                                </div>

                                {/* Catatan */}
                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium">
                                        Catatan
                                    </label>
                                    <textarea
                                        name="catatan"
                                        rows={3}
                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-600 dark:bg-neutral-800"
                                    />
                                    {errors.catatan && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.catatan}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="mt-6 flex gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </button>
                                <a
                                    href="/mitra"
                                    className="rounded-lg border border-neutral-300 px-6 py-2 hover:bg-neutral-50 dark:border-neutral-600 dark:hover:bg-neutral-800"
                                >
                                    Batal
                                </a>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
