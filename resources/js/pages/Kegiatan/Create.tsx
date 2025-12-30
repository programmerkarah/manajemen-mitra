import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Copy, Loader2, Save, X } from 'lucide-react';

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
    pjLainnyaUsers: User[];
    copyData?: {
        nama_kegiatan: string;
        jenis_kegiatan: 'sensus' | 'survei';
        deskripsi: string | null;
        tahun_anggaran: number;
        has_listing_updating: boolean;
        ketua_tim_user_id: number;
        pj_lainnya_id: number | null;
    };
    isCopyMode?: boolean;
}

export default function Create({
    ketuaTimUsers,
    tahunOptions,
    pjLainnyaUsers,
    copyData,
    isCopyMode = false,
}: KegiatanCreateProps) {
    const { auth } = usePage<SharedData>().props;
    const isKetuaTim = auth.activeRole?.name === 'ketua_tim';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/kegiatan' },
        {
            title: isCopyMode ? 'Salin Kegiatan' : 'Tambah Kegiatan',
            href: '/kegiatan/create',
        },
    ];

    const pageTitle = isCopyMode ? 'Salin Kegiatan' : 'Tambah Kegiatan';
    const pageDescription = isCopyMode
        ? 'Buat kegiatan baru dari kegiatan yang disalin'
        : 'Buat kegiatan baru dengan informasi lengkap';

    // Format currency untuk display
    const formatCurrency = (value: string | number | null): string => {
        if (value === null || value === undefined) return '';
        const str = String(value);
        const number = str.replace(/\D/g, '');
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    // Parse currency untuk submit
    const parseCurrency = (value: string): string => {
        return value.replace(/\./g, '');
    };

    const { data, setData, post, processing, errors } = useForm({
        nama_kegiatan: copyData?.nama_kegiatan || '',
        jenis_kegiatan:
            copyData?.jenis_kegiatan || ('survei' as 'sensus' | 'survei'),
        deskripsi: copyData?.deskripsi || '',
        tahun_anggaran: copyData?.tahun_anggaran || new Date().getFullYear(),
        pagu_pencacahan: '',
        pagu_listing: '',
        has_listing_updating: copyData?.has_listing_updating || false,
        ketua_tim_user_id: copyData?.ketua_tim_user_id?.toString() || '',
        pj_lainnya_id: copyData?.pj_lainnya_id?.toString() || '',
        tanggal_mulai: '',
        tanggal_selesai: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Transform data: convert string currency values to numbers before submitting
        const transformedData = {
            nama_kegiatan: data.nama_kegiatan,
            jenis_kegiatan: data.jenis_kegiatan,
            deskripsi: data.deskripsi,
            tahun_anggaran: data.tahun_anggaran,
            pagu_pencacahan: data.pagu_pencacahan
                ? Number(data.pagu_pencacahan)
                : null,
            pagu_listing: data.pagu_listing ? Number(data.pagu_listing) : null,
            has_listing_updating: data.has_listing_updating,
            ketua_tim_user_id: data.ketua_tim_user_id || null,
            pj_lainnya_id: data.pj_lainnya_id || null,
            tanggal_mulai: data.tanggal_mulai,
            tanggal_selesai: data.tanggal_selesai,
        };

        post('/kegiatan/store', transformedData, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                <PageHeader title={pageTitle} description={pageDescription}>
                    {isCopyMode && (
                        <div className="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                            <Copy className="h-4 w-4" />
                            <span>Mode Salin Kegiatan</span>
                        </div>
                    )}
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
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
                            <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <div className="flex items-start space-x-3">
                                    <svg
                                        className="mt-0.5 size-5 flex-shrink-0 text-neutral-600 dark:text-neutral-400"
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
                                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            Kode Kegiatan Otomatis
                                        </h3>
                                        <p className="mt-1 text-sm text-neutral-700 dark:text-neutral-300">
                                            Kode kegiatan akan dibuat otomatis
                                            oleh sistem dengan format: KEG-
                                            {data.tahun_anggaran}-XXX
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Nama Kegiatan */}
                            <div>
                                <label
                                    htmlFor="nama_kegiatan"
                                    className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Nama Kegiatan{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_kegiatan"
                                    value={data.nama_kegiatan}
                                    onChange={(e) =>
                                        setData('nama_kegiatan', e.target.value)
                                    }
                                    className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                                    placeholder="Masukkan nama kegiatan..."
                                />
                                <InputError
                                    message={errors.nama_kegiatan}
                                    className="mt-2"
                                />
                            </div>

                            {/* Jenis Kegiatan */}
                            <div>
                                <label
                                    htmlFor="jenis_kegiatan"
                                    className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Jenis Kegiatan{' '}
                                    <span className="text-red-500">*</span>
                                    {isCopyMode && (
                                        <span className="ml-2 text-sm font-normal text-gray-500">
                                            (dari kegiatan yang disalin)
                                        </span>
                                    )}
                                </label>
                                <select
                                    id="jenis_kegiatan"
                                    value={data.jenis_kegiatan}
                                    onChange={(e) =>
                                        setData(
                                            'jenis_kegiatan',
                                            e.target.value as
                                                | 'sensus'
                                                | 'survei',
                                        )
                                    }
                                    disabled={isCopyMode}
                                    className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="survei">Survei</option>
                                    <option value="sensus">Sensus</option>
                                </select>
                                <InputError
                                    message={errors.jenis_kegiatan}
                                    className="mt-2"
                                />
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    💡 Jenis kegiatan akan menentukan rate honor
                                    yang tersedia
                                </p>
                            </div>

                            {/* Deskripsi */}
                            <div>
                                <label
                                    htmlFor="deskripsi"
                                    className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Deskripsi
                                </label>
                                <Textarea
                                    id="deskripsi"
                                    rows={4}
                                    value={data.deskripsi}
                                    onChange={(e) =>
                                        setData('deskripsi', e.target.value)
                                    }
                                    placeholder="Masukkan deskripsi kegiatan... (opsional)"
                                    className="mt-2 text-base"
                                />
                                <InputError
                                    message={errors.deskripsi}
                                    className="mt-2"
                                />
                            </div>

                            {/* Tahun Anggaran */}
                            <div>
                                <label
                                    htmlFor="tahun_anggaran"
                                    className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Tahun Anggaran{' '}
                                    <span className="text-red-500">*</span>
                                    {isCopyMode && (
                                        <span className="ml-2 text-sm font-normal text-gray-500">
                                            (dari kegiatan yang disalin)
                                        </span>
                                    )}
                                </label>
                                <select
                                    id="tahun_anggaran"
                                    value={data.tahun_anggaran}
                                    onChange={(e) =>
                                        setData(
                                            'tahun_anggaran',
                                            parseInt(e.target.value),
                                        )
                                    }
                                    disabled={isCopyMode}
                                    className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                                    {tahunOptions.map((tahun) => (
                                        <option key={tahun} value={tahun}>
                                            {tahun}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.tahun_anggaran}
                                    className="mt-2"
                                />
                            </div>

                            {/* Tahapan Listing/Updating */}
                            <div>
                                <label
                                    htmlFor="has_listing_updating"
                                    className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Apakah kegiatan ini memiliki tahapan
                                    Listing/Updating?
                                </label>
                                <div className="mt-3 flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="has_listing_updating"
                                        checked={data.has_listing_updating}
                                        onChange={(e) =>
                                            setData(
                                                'has_listing_updating',
                                                e.target.checked,
                                            )
                                        }
                                        className="mt-1 h-5 w-5 rounded border-2 border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                    />
                                    <span className="text-base text-gray-700 dark:text-gray-300">
                                        Aktifkan jika ada tahapan
                                        listing/updating sebelum
                                        pencacahan/pendataan lapangan.
                                    </span>
                                </div>
                            </div>

                            {/* Pagu Listing */}
                            {data.has_listing_updating && (
                                <div>
                                    <label
                                        htmlFor="pagu_listing"
                                        className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Pagu Listing/Updating (Rp)
                                    </label>
                                    <input
                                        type="text"
                                        id="pagu_listing"
                                        value={
                                            data.pagu_listing
                                                ? formatCurrency(
                                                      data.pagu_listing,
                                                  )
                                                : ''
                                        }
                                        onChange={(e) => {
                                            const raw = parseCurrency(
                                                e.target.value,
                                            );
                                            setData('pagu_listing', raw);
                                        }}
                                        className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                                        placeholder="Masukkan nominal pagu listing..."
                                    />
                                    <InputError
                                        message={errors.pagu_listing}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-1">
                                {/* Pagu Pencacahan */}
                                <div>
                                    <label
                                        htmlFor="pagu_pencacahan"
                                        className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Pagu Pencacahan (Rp)
                                    </label>
                                    <input
                                        type="text"
                                        id="pagu_pencacahan"
                                        value={
                                            data.pagu_pencacahan
                                                ? formatCurrency(
                                                      data.pagu_pencacahan,
                                                  )
                                                : ''
                                        }
                                        onChange={(e) => {
                                            const raw = parseCurrency(
                                                e.target.value,
                                            );
                                            setData('pagu_pencacahan', raw);
                                        }}
                                        className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"
                                        placeholder="Masukkan nominal pagu pencacahan..."
                                    />
                                    <InputError
                                        message={errors.pagu_pencacahan}
                                        className="mt-2"
                                    />
                                </div>
                            </div>

                            {/* Ketua Tim - Hidden for ketua_tim role */}
                            {!isKetuaTim && (
                                <div>
                                    <label
                                        htmlFor="ketua_tim_user_id"
                                        className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Ketua Tim{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="ketua_tim_user_id"
                                        value={data.ketua_tim_user_id}
                                        onChange={(e) =>
                                            setData(
                                                'ketua_tim_user_id',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    >
                                        <option value="">
                                            Pilih Ketua Tim
                                        </option>
                                        {ketuaTimUsers.map((user) => (
                                            <option
                                                key={user.id}
                                                value={user.id}
                                            >
                                                {user.name} - {user.email}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.ketua_tim_user_id}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            {/* PJ Lainnya - Optional */}
                            <div>
                                <label
                                    htmlFor="pj_lainnya_id"
                                    className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Ketua Tim Lainnya (opsional)
                                </label>
                                <select
                                    id="pj_lainnya_id"
                                    value={data.pj_lainnya_id}
                                    onChange={(e) =>
                                        setData('pj_lainnya_id', e.target.value)
                                    }
                                    className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">
                                        Pilih Ketua Tim Lainnya (opsional)
                                    </option>
                                    {pjLainnyaUsers.map((user: User) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name} - {user.email}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.pj_lainnya_id}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tanggal Mulai */}
                                <div>
                                    <label
                                        htmlFor="tanggal_mulai"
                                        className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Tanggal Mulai{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="tanggal_mulai"
                                        value={data.tanggal_mulai}
                                        onChange={(e) =>
                                            setData(
                                                'tanggal_mulai',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    />
                                    <InputError
                                        message={errors.tanggal_mulai}
                                        className="mt-2"
                                    />
                                </div>

                                {/* Tanggal Selesai */}
                                <div>
                                    <label
                                        htmlFor="tanggal_selesai"
                                        className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Tanggal Selesai{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="tanggal_selesai"
                                        value={data.tanggal_selesai}
                                        onChange={(e) =>
                                            setData(
                                                'tanggal_selesai',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-2 block h-11 w-full rounded-lg border-2 border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    />
                                    <InputError
                                        message={errors.tanggal_selesai}
                                        className="mt-2"
                                    />
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="mt-6 flex justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                                <Button
                                    type="button"
                                    variant="outline"
                                    asChild
                                    className="gap-2"
                                    disabled={processing}
                                >
                                    <Link href="/kegiatan">
                                        <X className="h-5 w-5" />
                                        Batal
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-w-[180px] gap-2"
                                >
                                    {processing ? (
                                        <>
                                            <Loader2 className="h-5 w-5 animate-spin" />
                                            Menyimpan...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="h-5 w-5" />
                                            Simpan Kegiatan
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </ContentCard>
                </form>
            </div>
        </AppLayout>
    );
}
