import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { type BreadcrumbItem, type RateHonor, type Satuan } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kegiatan', href: '/kegiatan' },
    { title: 'Tambah Kegiatan', href: '/kegiatan/create' },
];

interface User {
    id: number;
    name: string;
    email: string;
}

interface KegiatanCreateProps {
    ketuaTimUsers: User[];
    rateHonors: Array<RateHonor & { satuan: Satuan }>;
    tahunOptions: number[];
}

export default function Create({ ketuaTimUsers, rateHonors, tahunOptions }: KegiatanCreateProps) {
    // Format currency untuk display
    const formatCurrency = (value: string): string => {
        const number = value.replace(/\D/g, '')
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    }

    // Parse currency untuk submit
    const parseCurrency = (value: string): string => {
        return value.replace(/\./g, '')
    }

    const { data, setData, post, processing, errors } = useForm({
        nama_kegiatan: '',
        jenis_kegiatan: 'survei' as 'sensus' | 'survei',
        deskripsi: '',
        tahun_anggaran: new Date().getFullYear(),
        pagu_anggaran: '',
        ketua_tim_user_id: '',
        rate_honor_id: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/kegiatan');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Kegiatan" />

            <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Tambah Kegiatan
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Buat kegiatan baru dengan informasi lengkap
                                </p>
                            </div>
                            <Link
                                href="/kegiatan"
                                className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6 space-y-6">
                            {/* Info: Kode Kegiatan Otomatis */}
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950">
                                <div className="flex items-start space-x-3">
                                    <svg
                                        className="mt-0.5 size-5 flex-shrink-0 text-blue-600 dark:text-blue-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <div>
                                        <h3 className="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                            Kode Kegiatan Otomatis
                                        </h3>
                                        <p className="mt-1 text-sm text-blue-800 dark:text-blue-200">
                                            Kode kegiatan akan dibuat otomatis oleh sistem dengan format: KEG-{data.tahun_anggaran}-XXX
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Nama Kegiatan */}
                            <div>
                                <label
                                    htmlFor="nama_kegiatan"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Nama Kegiatan <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_kegiatan"
                                    value={data.nama_kegiatan}
                                    onChange={(e) => setData('nama_kegiatan', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                    placeholder="Nama kegiatan"
                                />
                                <InputError message={errors.nama_kegiatan} className="mt-2" />
                            </div>

                            {/* Jenis Kegiatan */}
                            <div>
                                <label
                                    htmlFor="jenis_kegiatan"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Jenis Kegiatan <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="jenis_kegiatan"
                                    value={data.jenis_kegiatan}
                                    onChange={(e) => setData('jenis_kegiatan', e.target.value as 'sensus' | 'survei')}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                                    <option value="survei">Survei</option>
                                    <option value="sensus">Sensus</option>
                                </select>
                                <InputError message={errors.jenis_kegiatan} className="mt-2" />
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Jenis kegiatan akan menentukan rate honor yang tersedia
                                </p>
                            </div>

                            {/* Deskripsi */}
                            <div>
                                <label
                                    htmlFor="deskripsi"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Deskripsi
                                </label>
                                <textarea
                                    id="deskripsi"
                                    rows={4}
                                    value={data.deskripsi}
                                    onChange={(e) => setData('deskripsi', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                    placeholder="Deskripsi kegiatan (opsional)"
                                />
                                <InputError message={errors.deskripsi} className="mt-2" />
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tahun Anggaran */}
                                <div>
                                    <label
                                        htmlFor="tahun_anggaran"
                                        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Tahun Anggaran <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="tahun_anggaran"
                                        value={data.tahun_anggaran}
                                        onChange={(e) =>
                                            setData('tahun_anggaran', parseInt(e.target.value))
                                        }
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                    >
                                        {tahunOptions.map((tahun) => (
                                            <option key={tahun} value={tahun}>
                                                {tahun}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.tahun_anggaran} className="mt-2" />
                                </div>

                                {/* Pagu Anggaran */}
                                <div>
                                    <label
                                        htmlFor="pagu_anggaran"
                                        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Pagu Anggaran (Rp)
                                    </label>
                                    <input
                                        type="text"
                                        id="pagu_anggaran"
                                        value={data.pagu_anggaran ? formatCurrency(data.pagu_anggaran) : ''}
                                        onChange={(e) => {
                                            const raw = parseCurrency(e.target.value)
                                            setData('pagu_anggaran', raw)
                                        }}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                        placeholder="0"
                                    />
                                    <InputError message={errors.pagu_anggaran} className="mt-2" />
                                </div>
                            </div>

                            {/* Ketua Tim */}
                            <div>
                                <label
                                    htmlFor="ketua_tim_user_id"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Ketua Tim <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="ketua_tim_user_id"
                                    value={data.ketua_tim_user_id}
                                    onChange={(e) => setData('ketua_tim_user_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                                    <option value="">Pilih Ketua Tim</option>
                                    {ketuaTimUsers.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name} - {user.email}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.ketua_tim_user_id} className="mt-2" />
                            </div>

                            {/* Rate Honor */}
                            <div>
                                <label
                                    htmlFor="rate_honor_id"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Rate Honor <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="rate_honor_id"
                                    value={data.rate_honor_id}
                                    onChange={(e) => setData('rate_honor_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                                    <option value="">Pilih Rate Honor</option>
                                    {rateHonors.map((rate) => (
                                        <option key={rate.id} value={rate.id}>
                                            {rate.posisi} - Rp {rate.rate.toLocaleString('id-ID')}/
                                            {rate.satuan.nama}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.rate_honor_id} className="mt-2" />
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Rate honor ini akan berlaku untuk semua mitra dalam kegiatan ini
                                </p>
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tanggal Mulai */}
                                <div>
                                    <label
                                        htmlFor="tanggal_mulai"
                                        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Tanggal Mulai <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="tanggal_mulai"
                                        value={data.tanggal_mulai}
                                        onChange={(e) => setData('tanggal_mulai', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                    />
                                    <InputError message={errors.tanggal_mulai} className="mt-2" />
                                </div>

                                {/* Tanggal Selesai */}
                                <div>
                                    <label
                                        htmlFor="tanggal_selesai"
                                        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Tanggal Selesai <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="tanggal_selesai"
                                        value={data.tanggal_selesai}
                                        onChange={(e) => setData('tanggal_selesai', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                    />
                                    <InputError message={errors.tanggal_selesai} className="mt-2" />
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                                <Link
                                    href="/kegiatan"
                                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                                >
                                    Batal
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan Kegiatan'}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
