import AppLayout from '@/layouts/app-layout';
import { PageHeader } from '@/components/page-header';
import { ContentCard } from '@/components/content-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Info } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
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
    tahunOptions: number[];
}

export default function Create({ ketuaTimUsers, tahunOptions }: KegiatanCreateProps) {
    const { auth } = usePage<SharedData>().props;
    const isKetuaTim = auth.activeRole?.name === 'ketua_tim';
    
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

            <div className="space-y-6">
                <PageHeader
                    title="Tambah Kegiatan"
                    description="Buat kegiatan baru dengan informasi lengkap"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/kegiatan">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <ContentCard>
                        <div className="space-y-6">
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

                            {/* Ketua Tim - Hidden for ketua_tim role */}
                            {!isKetuaTim && (
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
                            )}

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
                            <div className="mt-6 flex justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                                <Button
                                    type="button"
                                    variant="outline"
                                    asChild
                                >
                                    <Link href="/kegiatan">Batal</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan Kegiatan'}
                                </Button>
                            </div>
                        </div>
                    </ContentCard>
                </form>
            </div>
        </AppLayout>
    );
}

