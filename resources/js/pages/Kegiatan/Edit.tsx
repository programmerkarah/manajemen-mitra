import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Kegiatan, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';

const BULAN_OPTIONS = [
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
] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kegiatan', href: '/kegiatan' },
    { title: 'Edit Kegiatan', href: '#' },
];

interface User {
    id: number;
    name: string;
    email: string;
}

interface KegiatanEditProps {
    kegiatan: Kegiatan;
    ketuaTimUsers: User[];
    tahunOptions: number[];
    pjLainnyaUsers: User[];
}

export default function Edit({
    kegiatan,
    ketuaTimUsers,
    tahunOptions,
    pjLainnyaUsers,
}: KegiatanEditProps) {
    const { auth, errors: pageErrors } = usePage<
        SharedData & { errors?: Record<string, string> }
    >().props;
    const errors = pageErrors ?? {};
    const isKetuaTim = auth.activeRole?.name === 'ketua_tim';

    // Format tanggal dari Carbon ke Y-m-d format
    const formatDateForInput = (dateString: string | null): string => {
        if (!dateString) return '';
        // Laravel sudah mengirim dalam format Y-m-d, langsung return
        return dateString;
    };

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

    // Helper untuk konversi nominal float ke string tanpa desimal
    const nominalToString = (val: number | null | undefined): string => {
        if (val === null || val === undefined) return '';
        // Jika float, bulatkan ke integer (misal 4941000.00 -> '4941000')
        return Math.round(val).toString();
    };

    const { data, setData, processing } = useForm({
        kode_kegiatan: kegiatan.kode_kegiatan || '',
        nama_kegiatan: kegiatan.nama_kegiatan || '',
        jenis_kegiatan: kegiatan.jenis_kegiatan || 'survei',
        deskripsi: kegiatan.deskripsi || '',
        tahun_anggaran: kegiatan.tahun_anggaran || new Date().getFullYear(),
        pagu_pencacahan: nominalToString(kegiatan.pagu_pencacahan),
        pagu_listing: nominalToString(kegiatan.pagu_listing),
        has_listing_updating: kegiatan.has_listing_updating || false,
        metode_pendataan_pencacahan: (kegiatan.metode_pendataan_pencacahan ||
            '') as '' | 'PAPI' | 'CAPI',
        metode_pendataan_listing: (kegiatan.metode_pendataan_listing || '') as
            | ''
            | 'PAPI'
            | 'CAPI',
        metode_pelatihan: (kegiatan.metode_pelatihan || '') as
            | ''
            | 'daring'
            | 'luring'
            | 'hybrid'
            | 'tidak_ada_pelatihan',
        bulan_pelatihan: kegiatan.bulan_pelatihan
            ? kegiatan.bulan_pelatihan.toString()
            : '',
        ketua_tim_user_id: kegiatan.ketua_tim_user_id?.toString() || '',
        pj_lainnya_id: kegiatan.pj_lainnya_id
            ? kegiatan.pj_lainnya_id.toString()
            : '',
        tanggal_mulai: formatDateForInput(kegiatan.tanggal_mulai),
        tanggal_selesai: formatDateForInput(kegiatan.tanggal_selesai),
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
            metode_pendataan_pencacahan:
                data.metode_pendataan_pencacahan || null,
            metode_pendataan_listing: data.has_listing_updating
                ? data.metode_pendataan_listing || null
                : null,
            metode_pelatihan: data.metode_pelatihan || null,
            bulan_pelatihan:
                data.metode_pelatihan &&
                data.metode_pelatihan !== 'tidak_ada_pelatihan' &&
                data.bulan_pelatihan
                    ? Number(data.bulan_pelatihan)
                    : null,
            ketua_tim_user_id: data.ketua_tim_user_id || null,
            pj_lainnya_id: data.pj_lainnya_id || null,
            tanggal_mulai: data.tanggal_mulai,
            tanggal_selesai: data.tanggal_selesai,
        };

        router.put(`/kegiatan/${kegiatan.hashed_id}`, transformedData, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Kegiatan - ${kegiatan.nama_kegiatan}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Kegiatan"
                    description="Ubah informasi kegiatan"
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Kode Kegiatan - Read Only */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="kode_kegiatan"
                                    className="text-base font-semibold"
                                >
                                    Kode Kegiatan
                                </Label>
                                <Input
                                    id="kode_kegiatan"
                                    value={data.kode_kegiatan}
                                    disabled
                                    className="h-11 bg-neutral-100 text-base dark:bg-neutral-800/60"
                                />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    🔒 Kode kegiatan tidak dapat diubah
                                </p>
                            </div>

                            {/* Nama Kegiatan */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="nama_kegiatan"
                                    className="text-base font-semibold"
                                >
                                    Nama Kegiatan{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="nama_kegiatan"
                                    value={data.nama_kegiatan}
                                    onChange={(e) =>
                                        setData('nama_kegiatan', e.target.value)
                                    }
                                    placeholder="Masukkan nama kegiatan..."
                                    className="h-11 text-base"
                                />
                                <InputError
                                    message={errors.nama_kegiatan}
                                    className="mt-2"
                                />
                            </div>

                            {/* Jenis Kegiatan */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="jenis_kegiatan"
                                    className="text-base font-semibold"
                                >
                                    Jenis Kegiatan{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <SearchableSelect
                                    options={[
                                        { value: 'survei', label: 'Survei' },
                                        { value: 'sensus', label: 'Sensus' },
                                    ]}
                                    value={data.jenis_kegiatan}
                                    onValueChange={(value) =>
                                        setData(
                                            'jenis_kegiatan',
                                            value as 'sensus' | 'survei',
                                        )
                                    }
                                    placeholder="Pilih jenis kegiatan"
                                    searchPlaceholder="Cari jenis kegiatan..."
                                    className="mt-1"
                                />
                                <InputError
                                    message={errors.jenis_kegiatan}
                                    className="mt-2"
                                />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    💡 Jenis kegiatan akan menentukan rate honor
                                    yang tersedia
                                </p>
                            </div>

                            {/* Deskripsi */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="deskripsi"
                                    className="text-base font-semibold"
                                >
                                    Deskripsi
                                </Label>
                                <Textarea
                                    id="deskripsi"
                                    rows={4}
                                    value={data.deskripsi}
                                    onChange={(e) =>
                                        setData('deskripsi', e.target.value)
                                    }
                                    placeholder="Masukkan deskripsi kegiatan... (opsional)"
                                    className="text-base"
                                />
                                <InputError
                                    message={errors.deskripsi}
                                    className="mt-2"
                                />
                            </div>

                            {/* Grid untuk 2 kolom */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tahun Anggaran */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="tahun_anggaran"
                                        className="text-base font-semibold"
                                    >
                                        Tahun Anggaran{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <SearchableSelect
                                        options={tahunOptions.map((tahun) => ({
                                            value: tahun.toString(),
                                            label: tahun.toString(),
                                        }))}
                                        value={data.tahun_anggaran.toString()}
                                        onValueChange={(value) =>
                                            setData(
                                                'tahun_anggaran',
                                                parseInt(value, 10),
                                            )
                                        }
                                        placeholder="Pilih tahun anggaran"
                                        searchPlaceholder="Cari tahun..."
                                        className="mt-1"
                                    />
                                    <InputError
                                        message={errors.tahun_anggaran}
                                        className="mt-2"
                                    />
                                </div>

                                {/* Pagu Anggaran */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="pagu_pencacahan"
                                        className="text-base font-semibold"
                                    >
                                        Pagu Pencacahan (Rp)
                                    </Label>
                                    <Input
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
                                        placeholder="Masukkan nominal pagu..."
                                        className="h-11 text-base"
                                    />
                                    <InputError
                                        message={errors.pagu_pencacahan}
                                        className="mt-2"
                                    />
                                </div>
                            </div>

                            {/* Tahapan Listing/Updating */}
                            <div className="space-y-2">
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
                                        className="mt-1 h-5 w-5 rounded border-2 border-neutral-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-gray-700"
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
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="pagu_listing"
                                        className="text-base font-semibold"
                                    >
                                        Pagu Listing/Updating (Rp)
                                    </Label>
                                    <Input
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
                                        placeholder="Masukkan nominal pagu listing..."
                                        className="h-11 text-base"
                                    />
                                    <InputError
                                        message={errors.pagu_listing}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            {/* Metode Pendataan Pencacahan */}
                            <div className="space-y-2">
                                <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                    Metode Pendataan Pencacahan{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    CAPI = menggunakan aplikasi FASIH di
                                    smartphone. PAPI = menggunakan kertas.
                                </p>
                                <div className="flex gap-4">
                                    {['PAPI', 'CAPI'].map((metode) => (
                                        <label
                                            key={metode}
                                            className={`flex flex-1 cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-colors ${
                                                data.metode_pendataan_pencacahan ===
                                                metode
                                                    ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-300 dark:bg-neutral-800'
                                                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="metode_pendataan_pencacahan"
                                                value={metode}
                                                checked={
                                                    data.metode_pendataan_pencacahan ===
                                                    metode
                                                }
                                                onChange={() =>
                                                    setData(
                                                        'metode_pendataan_pencacahan',
                                                        metode as
                                                            | 'PAPI'
                                                            | 'CAPI',
                                                    )
                                                }
                                                className="h-4 w-4 text-neutral-900"
                                            />
                                            <div>
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {metode}
                                                </span>
                                                <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                    {metode === 'CAPI'
                                                        ? '(FASIH)'
                                                        : '(Kertas)'}
                                                </span>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                                <InputError
                                    message={errors.metode_pendataan_pencacahan}
                                    className="mt-2"
                                />
                            </div>

                            {/* Metode Pendataan Listing */}
                            {data.has_listing_updating && (
                                <div className="space-y-2">
                                    <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                        Metode Pendataan Listing{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Metode pendataan khusus untuk tahap
                                        listing/updating.
                                    </p>
                                    <div className="flex gap-4">
                                        {['PAPI', 'CAPI'].map((metode) => (
                                            <label
                                                key={metode}
                                                className={`flex flex-1 cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-colors ${
                                                    data.metode_pendataan_listing ===
                                                    metode
                                                        ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-300 dark:bg-neutral-800'
                                                        : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="metode_pendataan_listing"
                                                    value={metode}
                                                    checked={
                                                        data.metode_pendataan_listing ===
                                                        metode
                                                    }
                                                    onChange={() =>
                                                        setData(
                                                            'metode_pendataan_listing',
                                                            metode as
                                                                | 'PAPI'
                                                                | 'CAPI',
                                                        )
                                                    }
                                                    className="h-4 w-4 text-neutral-900"
                                                />
                                                <div>
                                                    <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                        {metode}
                                                    </span>
                                                    <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                        {metode === 'CAPI'
                                                            ? '(FASIH)'
                                                            : '(Kertas)'}
                                                    </span>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError
                                        message={
                                            errors.metode_pendataan_listing
                                        }
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            {/* Metode Pelatihan */}
                            <div className="space-y-2">
                                <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                    Metode Pelatihan Petugas{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Apakah pelatihan petugas dilaksanakan secara
                                    daring, luring, hybrid, atau tidak ada
                                    pelatihan?
                                </p>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {(
                                        [
                                            {
                                                value: 'daring',
                                                label: 'Daring',
                                                desc: '(Online)',
                                            },
                                            {
                                                value: 'luring',
                                                label: 'Luring',
                                                desc: '(Tatap Muka)',
                                            },
                                            {
                                                value: 'hybrid',
                                                label: 'Hybrid',
                                                desc: '(Campuran)',
                                            },
                                            {
                                                value: 'tidak_ada_pelatihan',
                                                label: 'Tidak Ada',
                                                desc: '(Tanpa Pelatihan)',
                                            },
                                        ] as const
                                    ).map((opt) => (
                                        <label
                                            key={opt.value}
                                            className={`flex cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-colors ${
                                                data.metode_pelatihan ===
                                                opt.value
                                                    ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-300 dark:bg-neutral-800'
                                                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="metode_pelatihan"
                                                value={opt.value}
                                                checked={
                                                    data.metode_pelatihan ===
                                                    opt.value
                                                }
                                                onChange={() =>
                                                    setData((previousData) => ({
                                                        ...previousData,
                                                        metode_pelatihan:
                                                            opt.value,
                                                        bulan_pelatihan:
                                                            opt.value ===
                                                            'tidak_ada_pelatihan'
                                                                ? ''
                                                                : previousData.bulan_pelatihan,
                                                    }))
                                                }
                                                className="h-4 w-4 text-neutral-900"
                                            />
                                            <div>
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {opt.label}
                                                </span>
                                                <span className="ml-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {opt.desc}
                                                </span>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                                <InputError
                                    message={errors.metode_pelatihan}
                                    className="mt-2"
                                />
                            </div>

                            {data.metode_pelatihan !== '' &&
                                data.metode_pelatihan !==
                                    'tidak_ada_pelatihan' && (
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="bulan_pelatihan"
                                            className="text-base font-semibold"
                                        >
                                            Bulan Pelatihan{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </Label>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            Pilih bulan pelaksanaan pelatihan
                                            untuk sinkronisasi pengajuan pulsa
                                            pelatihan.
                                        </p>
                                        <SearchableSelect
                                            options={BULAN_OPTIONS.map(
                                                (bulan) => ({
                                                    value: bulan.value,
                                                    label: bulan.label,
                                                }),
                                            )}
                                            value={data.bulan_pelatihan}
                                            onValueChange={(value) =>
                                                setData(
                                                    'bulan_pelatihan',
                                                    value,
                                                )
                                            }
                                            placeholder="Pilih Bulan Pelatihan"
                                            searchPlaceholder="Cari bulan..."
                                            className="mt-1"
                                        />
                                        <InputError
                                            message={errors.bulan_pelatihan}
                                            className="mt-2"
                                        />
                                    </div>
                                )}

                            {!isKetuaTim && (
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="ketua_tim_user_id"
                                        className="text-base font-semibold"
                                    >
                                        Ketua Tim{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <SearchableSelect
                                        options={[
                                            {
                                                value: '',
                                                label: 'Pilih Ketua Tim',
                                            },
                                            ...ketuaTimUsers.map((user) => ({
                                                value: user.id.toString(),
                                                label: `${user.name} (${user.email})`,
                                                searchKeywords: `${user.name} ${user.email}`,
                                            })),
                                        ]}
                                        value={data.ketua_tim_user_id}
                                        onValueChange={(value) =>
                                            setData('ketua_tim_user_id', value)
                                        }
                                        placeholder="Pilih Ketua Tim"
                                        searchPlaceholder="Cari ketua tim..."
                                        className="mt-1"
                                    />
                                    <InputError
                                        message={errors.ketua_tim_user_id}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            {/* PJ Lainnya - Optional */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="pj_lainnya_id"
                                    className="text-base font-semibold"
                                >
                                    Ketua Tim Lainnya (opsional)
                                </Label>
                                <SearchableSelect
                                    options={[
                                        {
                                            value: '',
                                            label: 'Pilih Ketua Tim Lainnya (opsional)',
                                        },
                                        ...pjLainnyaUsers.map((user) => ({
                                            value: user.id.toString(),
                                            label: `${user.name} (${user.email})`,
                                            searchKeywords: `${user.name} ${user.email}`,
                                        })),
                                    ]}
                                    value={data.pj_lainnya_id}
                                    onValueChange={(value) =>
                                        setData('pj_lainnya_id', value)
                                    }
                                    placeholder="Pilih Ketua Tim Lainnya (opsional)"
                                    searchPlaceholder="Cari ketua tim..."
                                    className="mt-1"
                                />
                                <InputError
                                    message={errors.pj_lainnya_id}
                                    className="mt-2"
                                />
                            </div>

                            {/* Grid untuk tanggal */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tanggal Mulai */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="tanggal_mulai"
                                        className="text-base font-semibold"
                                    >
                                        Tanggal Mulai{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="tanggal_mulai"
                                        type="date"
                                        value={data.tanggal_mulai}
                                        onChange={(e) =>
                                            setData(
                                                'tanggal_mulai',
                                                e.target.value,
                                            )
                                        }
                                        className="h-11 text-base"
                                    />
                                    <InputError
                                        message={errors.tanggal_mulai}
                                        className="mt-2"
                                    />
                                </div>

                                {/* Tanggal Selesai */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="tanggal_selesai"
                                        className="text-base font-semibold"
                                    >
                                        Tanggal Selesai{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="tanggal_selesai"
                                        type="date"
                                        value={data.tanggal_selesai}
                                        onChange={(e) =>
                                            setData(
                                                'tanggal_selesai',
                                                e.target.value,
                                            )
                                        }
                                        className="h-11 text-base"
                                    />
                                    <InputError
                                        message={errors.tanggal_selesai}
                                        className="mt-2"
                                    />
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            asChild
                            className="gap-2"
                            disabled={processing}
                        >
                            <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                <X className="h-5 w-5" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="min-w-[200px] gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <Save className="h-5 w-5" />
                                    Simpan Perubahan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
