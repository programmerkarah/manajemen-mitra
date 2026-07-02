import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    Kegiatan,
    Petugas,
    RateHonor,
    Satuan,
} from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil, Settings } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kegiatan', href: '/kegiatan' },
    { title: 'Detail Kegiatan', href: '#' },
];

interface Alokasi {
    id: string;
    hashed_id: string;
    bulan: number;
    tahun: number;
    petugas: Petugas;
    jumlah_satuan: number;
    jumlah_satuan_listing: number | null;
    total_honor: number;
    total_honor_listing: number | null;
    peran: string;
    status_kepegawaian: string;
    jenis_kegiatan: string | null;
    status: string;
}

interface Props {
    kegiatan: Kegiatan & {
        ketua_tim: {
            id: number;
            name: string;
            email: string;
        };
        pj_lainnya?: {
            id: number;
            name: string;
            email: string;
        } | null;
        rate_honors?: Array<
            RateHonor & {
                satuan?: Satuan;
                satuan_listing?: Satuan;
            }
        >;
        frame_sampel_listing?: {
            id: number;
            nama: string;
            kode: string;
        } | null;
        frame_sampel_pencacahan?: {
            id: number;
            nama: string;
            kode: string;
        } | null;
        unit_sampel_listing?: Array<{
            id: number;
            nama: string;
            kode: string;
        }> | null;
        unit_sampel_pencacahan?: Array<{
            id: number;
            nama: string;
            kode: string;
        }> | null;
        alokasi: Alokasi[];
    };
    auth: {
        user: {
            id: number;
            role: string;
        };
        activeRole?: {
            name: string;
        };
    };
    can: {
        update: boolean;
        approve: boolean;
        reject: boolean;
        delete: boolean;
    };
}

export default function Show({ kegiatan, auth, can }: Props) {
    const statusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        diajukan:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        divalidasi:
            'bg-blue-100 text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300',
        aktif: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        selesai:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        dibatalkan: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        ditolak: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    };

    const formatCurrency = (amount: number | null | undefined) => {
        if (!amount || isNaN(amount)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (date: string) => {
        // Laravel mengirim format Y-m-d, parse manual untuk menghindari timezone shift
        const [year, month, day] = date.split('-');
        const localDate = new Date(
            parseInt(year),
            parseInt(month) - 1,
            parseInt(day),
        );
        return localDate.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const totalAlokasi = kegiatan.alokasi.reduce(
        (sum: number, alokasi: Alokasi) =>
            sum +
            Number(alokasi.total_honor || 0) +
            Number(alokasi.total_honor_listing || 0),
        0,
    );

    // Group alokasi by bulan-tahun
    const groupedAlokasi = kegiatan.alokasi.reduce(
        (
            acc: Record<
                string,
                {
                    bulan: number;
                    tahun: number;
                    jumlah_petugas: number;
                    alokasi: Alokasi[];
                }
            >,
            alokasi,
        ) => {
            const key = `${alokasi.tahun}-${alokasi.bulan}`;
            if (!acc[key]) {
                acc[key] = {
                    bulan: alokasi.bulan,
                    tahun: alokasi.tahun,
                    jumlah_petugas: 0,
                    alokasi: [],
                };
            }
            acc[key].jumlah_petugas++;
            acc[key].alokasi.push(alokasi);
            return acc;
        },
        {},
    );

    const periodeAlokasi = Object.values(groupedAlokasi).sort((a, b) => {
        if (a.tahun !== b.tahun) return b.tahun - a.tahun;
        return b.bulan - a.bulan;
    });

    const getBulanName = (bulan: number) => {
        const months = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        return months[bulan - 1] || '';
    };

    const formatMetodePendataan = (metode: string | null) => {
        if (!metode) {
            return null;
        }

        if (metode === 'CAPI_FASIH' || metode === 'CAPI') {
            return 'CAPI (FASIH)';
        }

        if (metode === 'CAPI_KSA_PRO') {
            return 'CAPI (KSA Pro/Aplikasi Lainnya)';
        }

        if (metode === 'PAPI') {
            return 'PAPI (Kertas)';
        }

        return metode;
    };

    const statusLabel =
        kegiatan.status === 'draft'
            ? 'Draft'
            : kegiatan.status === 'diajukan'
              ? 'Diajukan'
              : kegiatan.status === 'divalidasi'
                ? 'Divalidasi'
                : kegiatan.status === 'aktif'
                  ? 'Aktif'
                  : kegiatan.status === 'selesai'
                    ? 'Selesai'
                    : kegiatan.status === 'dibatalkan'
                      ? 'Dibatalkan'
                      : kegiatan.status === 'ditolak'
                        ? 'Ditolak'
                        : kegiatan.status;

    const pendataanPencacahanLabel =
        formatMetodePendataan(kegiatan.metode_pendataan_pencacahan) ??
        'Belum diisi';

    const pendataanListingLabel = kegiatan.has_listing_updating
        ? (formatMetodePendataan(kegiatan.metode_pendataan_listing) ??
          'Belum diisi')
        : 'Tidak ada tahapan listing';

    const metodePelatihanLabel =
        kegiatan.metode_pelatihan === 'daring'
            ? 'Daring (Online)'
            : kegiatan.metode_pelatihan === 'luring'
              ? 'Luring (Tatap Muka)'
              : kegiatan.metode_pelatihan === 'hybrid'
                ? 'Hybrid (Campuran)'
                : kegiatan.metode_pelatihan === 'tidak_ada_pelatihan'
                  ? 'Tidak Ada Pelatihan'
                  : 'Belum dipilih';

    const sisaPagu =
        (Number(kegiatan.pagu_pencacahan) || 0) +
        (Number(kegiatan.pagu_listing) || 0) -
        totalAlokasi;

    // Can edit only if draft
    const canEdit = kegiatan.status === 'draft' && can.update;

    // Can manage rate honor and alokasi only if divalidasi or aktif, and not PJ role
    const canManageFeatures =
        (kegiatan.status === 'divalidasi' || kegiatan.status === 'aktif') &&
        auth.activeRole?.name !== 'pj' &&
        auth.activeRole?.name !== 'approver';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Kegiatan - ${kegiatan.nama_kegiatan}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Detail Kegiatan"
                    description="Ikhtisar kegiatan, pengaturan lapangan, dan alokasi petugas"
                >
                    <div className="flex w-full flex-wrap gap-2 sm:w-auto sm:justify-end">
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="w-full gap-2 sm:w-auto"
                        >
                            <Link href="/kegiatan">
                                <ArrowLeft className="h-4 w-4" />
                                Kembali
                            </Link>
                        </Button>

                        {/* Rate Honor Management - hidden for PJ role */}
                        {auth.activeRole?.name !== 'pj' &&
                            auth.activeRole?.name !== 'approver' && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild={canManageFeatures}
                                    className="w-full gap-2 sm:w-auto"
                                    disabled={!canManageFeatures}
                                    title={
                                        !canManageFeatures
                                            ? 'Kegiatan harus divalidasi terlebih dahulu'
                                            : undefined
                                    }
                                >
                                    {canManageFeatures ? (
                                        <Link
                                            href={`/kegiatan/${kegiatan.hashed_id}/rate-honor/manage`}
                                        >
                                            <Settings className="h-4 w-4" />
                                            Kelola Rate Honor
                                        </Link>
                                    ) : (
                                        ''
                                    )}
                                </Button>
                            )}

                        {canManageFeatures && (
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="w-full gap-2 sm:w-auto"
                            >
                                <Link
                                    href={`/kegiatan/${kegiatan.hashed_id}/frame-sampel`}
                                >
                                    <Settings className="h-4 w-4" />
                                    Kelola Frame Sampel
                                </Link>
                            </Button>
                        )}

                        {canEdit && (
                            <Button
                                size="sm"
                                asChild
                                className="w-full gap-2 sm:w-auto"
                            >
                                <Link
                                    href={`/kegiatan/${kegiatan.hashed_id}/edit`}
                                >
                                    <Pencil className="h-4 w-4" />
                                    Edit
                                </Link>
                            </Button>
                        )}
                    </div>
                </PageHeader>

                <ContentCard>
                    <div className="space-y-6 p-6">
                        <section className="overflow-hidden rounded-3xl border border-neutral-200/70 bg-gradient-to-br from-neutral-50 via-white to-neutral-100 p-6 shadow-sm dark:border-neutral-800 dark:from-neutral-900 dark:via-neutral-900 dark:to-neutral-800/80">
                            <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div className="space-y-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span
                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusColors[kegiatan.status as keyof typeof statusColors]}`}
                                        >
                                            {statusLabel}
                                        </span>
                                        <span className="inline-flex rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-medium text-neutral-700 shadow-sm dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                                            Tahun {kegiatan.tahun_anggaran}
                                        </span>
                                        <span className="inline-flex rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-medium text-neutral-700 shadow-sm dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                                            {kegiatan.has_listing_updating
                                                ? 'Dengan listing/updating'
                                                : 'Tanpa listing/updating'}
                                        </span>
                                    </div>

                                    <div className="space-y-2">
                                        <h2 className="text-2xl font-semibold tracking-tight text-gray-900 lg:text-3xl dark:text-white">
                                            {kegiatan.nama_kegiatan}
                                        </h2>
                                        <p className="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                                            {kegiatan.deskripsi ||
                                                'Tidak ada deskripsi kegiatan.'}
                                        </p>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        <div className="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-neutral-700 dark:bg-neutral-900/80">
                                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                                Periode
                                            </p>
                                            <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                                {formatDate(
                                                    kegiatan.tanggal_mulai,
                                                )}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                                s.d.{' '}
                                                {formatDate(
                                                    kegiatan.tanggal_selesai,
                                                )}
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-neutral-700 dark:bg-neutral-900/80">
                                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                                Total Alokasi
                                            </p>
                                            <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                                {formatCurrency(totalAlokasi)}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                                Sisa {formatCurrency(sisaPagu)}
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-neutral-700 dark:bg-neutral-900/80">
                                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                                Ketua Tim
                                            </p>
                                            <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                                {kegiatan.ketua_tim?.name ||
                                                    '-'}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                                {kegiatan.ketua_tim?.email ||
                                                    ''}
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-neutral-700 dark:bg-neutral-900/80">
                                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                                Pelatihan
                                            </p>
                                            <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                                {metodePelatihanLabel}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                                {kegiatan.metode_pendataan_pencacahan
                                                    ? 'Pendataan siap dikonfigurasi'
                                                    : 'Pendataan belum lengkap'}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2 lg:w-[340px] lg:grid-cols-1">
                                    <div className="rounded-2xl border border-neutral-200/70 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/60">
                                        <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                            Pendataan Pencacahan
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                            {pendataanPencacahanLabel}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-neutral-200/70 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/60">
                                        <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                            Pendataan Listing
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                            {pendataanListingLabel}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-neutral-200/70 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/60">
                                        <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                            Frame
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                            {kegiatan.frame_sampel_pencacahan
                                                ? `${kegiatan.frame_sampel_pencacahan.nama} (${kegiatan.frame_sampel_pencacahan.kode})`
                                                : '-'}
                                        </p>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Listing:{' '}
                                            {kegiatan.frame_sampel_listing
                                                ? `${kegiatan.frame_sampel_listing.nama} (${kegiatan.frame_sampel_listing.kode})`
                                                : '-'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div className="grid gap-6 xl:grid-cols-2">
                            <section className="rounded-3xl border border-neutral-200/70 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                                <div className="flex items-center justify-between gap-4 border-b border-neutral-200/70 pb-4 dark:border-neutral-800">
                                    <div>
                                        <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                                            Informasi Utama
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Detail dasar yang paling sering
                                            dibaca.
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-6 grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Tahun Anggaran
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.tahun_anggaran}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Pagu Pencacahan
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {formatCurrency(
                                                kegiatan.pagu_pencacahan,
                                            )}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Pagu Listing/Updating
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.has_listing_updating
                                                ? formatCurrency(
                                                      kegiatan.pagu_listing,
                                                  )
                                                : 'Tidak ada'}
                                        </p>
                                    </div>
                                    <div className="md:col-span-2">
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Deskripsi
                                        </label>
                                        <p className="mt-1 leading-6 text-gray-900 dark:text-white">
                                            {kegiatan.deskripsi || '-'}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-3xl border border-neutral-200/70 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                                <div className="flex items-center justify-between gap-4 border-b border-neutral-200/70 pb-4 dark:border-neutral-800">
                                    <div>
                                        <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                                            Pengaturan Lapangan
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Frame, unit sampel, dan metode
                                            pendataan.
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-6 space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Frame Sampel Pencacahan
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.frame_sampel_pencacahan
                                                ? `${kegiatan.frame_sampel_pencacahan.nama} (${kegiatan.frame_sampel_pencacahan.kode})`
                                                : '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Unit Sampel Pencacahan
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.unit_sampel_pencacahan
                                                ?.length
                                                ? kegiatan.unit_sampel_pencacahan
                                                      .map(
                                                          (u) =>
                                                              `${u.nama} (${u.kode})`,
                                                      )
                                                      .join(', ')
                                                : '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Frame Sampel Listing
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.frame_sampel_listing
                                                ? `${kegiatan.frame_sampel_listing.nama} (${kegiatan.frame_sampel_listing.kode})`
                                                : '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Unit Sampel Listing
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.unit_sampel_listing
                                                ?.length
                                                ? kegiatan.unit_sampel_listing
                                                      .map(
                                                          (u) =>
                                                              `${u.nama} (${u.kode})`,
                                                      )
                                                      .join(', ')
                                                : '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Tahapan Listing/Updating
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.has_listing_updating
                                                ? 'Ya'
                                                : 'Tidak'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Status
                                        </label>
                                        <div className="mt-1">
                                            <span
                                                className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusColors[kegiatan.status as keyof typeof statusColors]}`}
                                            >
                                                {statusLabel}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-3xl border border-neutral-200/70 bg-white p-6 shadow-sm xl:col-span-2 dark:border-neutral-800 dark:bg-neutral-950/50">
                                <div className="flex items-center justify-between gap-4 border-b border-neutral-200/70 pb-4 dark:border-neutral-800">
                                    <div>
                                        <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                                            Penugasan dan Rate Honor
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Ketua tim, metode pelatihan, dan
                                            ringkasan honor.
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Metode Pendataan Pencacahan
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {pendataanPencacahanLabel}
                                        </p>
                                    </div>
                                    {kegiatan.has_listing_updating && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Metode Pendataan Listing
                                            </label>
                                            <p className="mt-1 text-gray-900 dark:text-white">
                                                {pendataanListingLabel}
                                            </p>
                                        </div>
                                    )}
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Metode Pelatihan
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {metodePelatihanLabel}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Ketua Tim
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {kegiatan.ketua_tim?.name || '-'}
                                        </p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {kegiatan.ketua_tim?.email || ''}
                                        </p>
                                    </div>
                                    {kegiatan.pj_lainnya && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Ketua Tim Lainnya (PJ)
                                            </label>
                                            <p className="mt-1 text-gray-900 dark:text-white">
                                                {kegiatan.pj_lainnya.name}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {kegiatan.pj_lainnya.email}
                                            </p>
                                        </div>
                                    )}
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Periode
                                        </label>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            {formatDate(kegiatan.tanggal_mulai)}{' '}
                                            -{' '}
                                            {formatDate(
                                                kegiatan.tanggal_selesai,
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-8">
                                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Rate Honor
                                    </label>
                                    {Array.isArray(kegiatan.rate_honors) &&
                                    kegiatan.rate_honors.length > 0 ? (
                                        <div className="mt-3 overflow-x-auto rounded-2xl border border-neutral-200/70 dark:border-neutral-800">
                                            <table className="min-w-full text-sm">
                                                <thead className="bg-neutral-50/80 dark:bg-neutral-900/60">
                                                    <tr className="border-b border-gray-200 dark:border-gray-700">
                                                        <th className="py-3 pr-4 pl-4 text-left font-medium text-gray-600 dark:text-gray-400">
                                                            Status Kepegawaian
                                                        </th>
                                                        <th className="py-3 pr-4 text-left font-medium text-gray-600 dark:text-gray-400">
                                                            Penugasan
                                                        </th>
                                                        <th className="py-3 pr-4 text-right font-medium text-gray-600 dark:text-gray-400">
                                                            Rate Honor
                                                        </th>
                                                        {kegiatan.has_listing_updating && (
                                                            <th className="py-3 pr-4 text-right font-medium text-gray-600 dark:text-gray-400">
                                                                Rate Listing
                                                            </th>
                                                        )}
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                                    {kegiatan.rate_honors.map(
                                                        (rh, idx) => (
                                                            <tr
                                                                key={idx}
                                                                className="bg-white/60 dark:bg-neutral-950/20"
                                                            >
                                                                <td className="py-3 pr-4 pl-4 text-gray-700 dark:text-gray-300">
                                                                    {rh.status_kepegawaian ===
                                                                    'organik'
                                                                        ? 'Organik'
                                                                        : 'Non-Organik'}
                                                                </td>
                                                                <td className="py-3 pr-4 text-gray-700 dark:text-gray-300">
                                                                    {rh.jenis_penugasan ===
                                                                        'pcl_ppl' &&
                                                                        'PCL/PPL'}
                                                                    {rh.jenis_penugasan ===
                                                                        'pml' &&
                                                                        'PML'}
                                                                    {rh.jenis_penugasan ===
                                                                        'koseka' &&
                                                                        'Koseka (Koordinator Sensus Kecamatan)'}
                                                                    {rh.jenis_penugasan ===
                                                                        'pengolahan' &&
                                                                        'Pengolahan'}
                                                                    {rh.jenis_penugasan ===
                                                                        'pengawas_pengolahan' &&
                                                                        'Pengawas Pengolahan'}
                                                                </td>
                                                                <td className="py-3 pr-4 text-right text-gray-900 dark:text-white">
                                                                    {formatCurrency(
                                                                        rh.rate,
                                                                    )}
                                                                    {rh.satuan
                                                                        ?.nama && (
                                                                        <span className="ml-1 text-xs text-gray-500">
                                                                            /
                                                                            {
                                                                                rh
                                                                                    .satuan
                                                                                    .nama
                                                                            }
                                                                        </span>
                                                                    )}
                                                                </td>
                                                                {kegiatan.has_listing_updating && (
                                                                    <td className="py-3 pr-4 text-right text-gray-900 dark:text-white">
                                                                        {rh.rate_listing
                                                                            ? formatCurrency(
                                                                                  rh.rate_listing,
                                                                              )
                                                                            : '-'}
                                                                        {rh.rate_listing &&
                                                                            rh
                                                                                .satuan_listing
                                                                                ?.nama && (
                                                                                <span className="ml-1 text-xs text-gray-500">
                                                                                    /
                                                                                    {
                                                                                        rh
                                                                                            .satuan_listing
                                                                                            .nama
                                                                                    }
                                                                                </span>
                                                                            )}
                                                                    </td>
                                                                )}
                                                            </tr>
                                                        ),
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <p className="mt-3 text-gray-500 dark:text-gray-400">
                                            Belum ditentukan
                                        </p>
                                    )}
                                </div>

                                <div className="mt-6 rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/70 p-4 dark:border-neutral-700 dark:bg-neutral-900/40">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        Total Alokasi:{' '}
                                        {formatCurrency(totalAlokasi)}
                                    </p>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Sisa pagu: {formatCurrency(sisaPagu)}
                                    </p>
                                </div>
                            </section>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard padding="none">
                    <div className="border-b border-neutral-200/70 px-6 py-4 dark:border-neutral-800">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Alokasi Petugas ({periodeAlokasi.length}{' '}
                                Periode)
                            </h2>
                            {canManageFeatures ? (
                                <Button
                                    size="sm"
                                    asChild={canManageFeatures}
                                    disabled={!canManageFeatures}
                                    title={
                                        !canManageFeatures
                                            ? 'Kegiatan harus divalidasi terlebih dahulu'
                                            : undefined
                                    }
                                >
                                    <Link
                                        href={`/alokasi/create?kegiatan_id=${kegiatan.hashed_id}`}
                                    >
                                        Tambah Periode Kegiatan
                                    </Link>
                                </Button>
                            ) : (
                                ''
                            )}
                        </div>
                    </div>

                    {periodeAlokasi.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <p className="text-gray-500 dark:text-gray-400">
                                Belum ada alokasi petugas untuk kegiatan ini
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-white/60 backdrop-blur-md dark:bg-neutral-800/60">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            No
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            Bulan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            Jumlah Petugas
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/10 bg-white/30 backdrop-blur-sm dark:divide-neutral-700/20 dark:bg-neutral-800/30">
                                    {periodeAlokasi.map((periode, index) => (
                                        <tr
                                            key={`${periode.tahun}-${periode.bulan}`}
                                        >
                                            <td className="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                                {index + 1}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="font-medium text-gray-900 dark:text-white">
                                                    {getBulanName(
                                                        periode.bulan,
                                                    )}{' '}
                                                    {periode.tahun}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                                {periode.jumlah_petugas} petugas
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap">
                                                <div className="flex gap-2">
                                                    <Link
                                                        href={`/alokasi/periode/${kegiatan.hashed_id}/${periode.tahun}/${String(periode.bulan).padStart(2, '0')}`}
                                                    >
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            Lihat
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
