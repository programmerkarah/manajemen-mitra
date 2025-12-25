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
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kegiatan', href: '/kegiatan' },
    { title: 'Detail Kegiatan', href: '#' },
];

interface Alokasi {
    id: string;
    hashed_id: string;
    bulan: number;
    tahun: number;
    petugas: Petugas;
    rate_honor: RateHonor & {
        satuan: Satuan;
    };
    volume: number;
    total_honor: number;
    status: string;
    tanggal_pengajuan: string;
}

interface Props {
    kegiatan: Kegiatan & {
        ketua_tim: {
            id: number;
            name: string;
            email: string;
        };
        rate_honor?: RateHonor[] & {
            satuan: Satuan;
        };
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
    };

    const alokasiStatusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        diajukan:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        disetujui_pj:
            'bg-blue-100 text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300',
        disetujui:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
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
        (sum: number, alokasi: Alokasi) => sum + alokasi.total_honor,
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
                    description="Informasi lengkap kegiatan dan alokasi petugas"
                >
                    <div className="flex gap-3">
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

                        {/* Rate Honor Management - hidden for PJ role */}
                        {auth.activeRole?.name !== 'pj' &&
                            auth.activeRole?.name !== 'approver' && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild={canManageFeatures}
                                    className="gap-2"
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

                        {canEdit && (
                            <Button size="sm" asChild className="gap-2">
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
                    <div className="border-b border-neutral-200/70 pb-4 dark:border-neutral-800">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Informasi Kegiatan
                        </h2>
                    </div>
                    <div className="pt-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Kode Kegiatan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.kode_kegiatan}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>
                                <div className="mt-1">
                                    <span
                                        className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusColors[kegiatan.status as keyof typeof statusColors]}`}
                                    >
                                        {kegiatan.status === 'draft' && 'Draft'}
                                        {kegiatan.status === 'diajukan' &&
                                            'Diajukan'}
                                        {kegiatan.status === 'divalidasi' &&
                                            'Divalidasi'}
                                        {kegiatan.status === 'aktif' && 'Aktif'}
                                        {kegiatan.status === 'selesai' &&
                                            'Selesai'}
                                        {kegiatan.status === 'dibatalkan' &&
                                            'Dibatalkan'}
                                    </span>
                                </div>
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nama Kegiatan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.nama_kegiatan}
                                </p>
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Deskripsi
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.deskripsi || '-'}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tahun Anggaran
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.tahun_anggaran}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Pagu Pencacahan
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatCurrency(kegiatan.pagu_pencacahan)}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tahapan Listing/Updating
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.has_listing_updating
                                        ? 'Ya'
                                        : 'Tidak'}
                                </p>
                            </div>
                                    
                            {kegiatan.has_listing_updating && (
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Pagu Listing/Updating
                                    </label>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        {formatCurrency(kegiatan.pagu_listing)}
                                    </p>
                                </div>
                            )}

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Ketua Tim
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {kegiatan.ketua_tim?.name || '-'}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {kegiatan.ketua_tim?.email || ''}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Rate Honor
                                </label>
                                {Array.isArray(kegiatan.rate_honors) &&
                                kegiatan.rate_honors.length > 0 ? (
                                    <>
                                        <p className="mt-1 text-gray-900 dark:text-white">
                                            Sudah ditentukan
                                        </p>
                                    </>
                                ) : (
                                    <p className="mt-1 text-gray-500 dark:text-gray-400">
                                        Belum ditentukan
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Total Alokasi
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatCurrency(totalAlokasi)}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Sisa:{' '}
                                    {formatCurrency(
                                        (kegiatan.pagu_pencacahan || 0) -
                                            totalAlokasi,
                                    )}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tanggal Mulai
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatDate(kegiatan.tanggal_mulai)}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tanggal Selesai
                                </label>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {formatDate(kegiatan.tanggal_selesai)}
                                </p>
                            </div>
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
                                <thead className="bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md">
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
                                <tbody className="divide-y divide-white/10 bg-white/30 dark:divide-neutral-700/20 dark:bg-neutral-800/30 backdrop-blur-sm">
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
