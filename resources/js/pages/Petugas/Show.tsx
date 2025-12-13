import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik_masked: string;
    email: string;
    telepon: string;
    alamat: string;
    pendidikan: string;
    tahun_bergabung: number;
    jenis_petugas: 'organik' | 'non-organik';
    npwp_masked: string | null;
    bank: string | null;
    no_rekening_masked: string | null;
    nama_rekening: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    alokasi: Array<{
        id: number;
        hashed_id: string;
        kegiatan: {
            nama_kegiatan: string;
            kode_kegiatan: string;
        };
        rate_honor: {
            posisi: string;
            rate: number;
            satuan: {
                nama: string;
            };
        };
        bulan: number;
        tahun: number;
        jumlah_satuan: number;
        total_honor: number;
        status: string;
    }>;
}

interface ShowProps {
    petugas: Petugas;
}

const bulanNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function Show({ petugas }: ShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Petugas', href: '/petugas' },
        { title: petugas.nama, href: `/petugas/${petugas.hashed_id}` },
    ];

    const formatRupiah = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
        }).format(amount);
    };

    const handleDelete = () => {
        if (confirm('Apakah Anda yakin ingin menghapus Petugas ini?')) {
            router.delete(`/petugas/${petugas.hashed_id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Petugas - ${petugas.nama}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">{petugas.nama}</h1>
                    <div className="flex gap-2">
                        <Link
                            href={`/petugas/${petugas.hashed_id}/edit`}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                        >
                            Edit
                        </Link>
                        <button
                            onClick={handleDelete}
                            className="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                        >
                            Hapus
                        </button>
                    </div>
                </div>

                {/* Informasi Dasar */}
                <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                    <h2 className="mb-4 text-lg font-semibold">Informasi Dasar</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">NIK</p>
                            <p className="font-medium">{petugas.nik_masked}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Email</p>
                            <p className="font-medium">{petugas.email}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Telepon</p>
                            <p className="font-medium">{petugas.telepon}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Pendidikan</p>
                            <p className="font-medium">{petugas.pendidikan}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Tahun Bergabung</p>
                            <p className="font-medium">{petugas.tahun_bergabung}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Status</p>
                            <span
                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                    petugas.status === 'aktif'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                }`}
                            >
                                {petugas.status}
                            </span>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Jenis Petugas</p>
                            <span
                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                    petugas.jenis_petugas === 'organik'
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                        : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                }`}
                            >
                                {petugas.jenis_petugas === 'organik' ? 'Pegawai Organik' : 'Non-Organik (Mitra)'}
                            </span>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Alamat</p>
                            <p className="font-medium">{petugas.alamat}</p>
                        </div>
                    </div>
                </div>

                {/* Data Bank */}
                {(petugas.npwp_masked || petugas.bank || petugas.no_rekening_masked) && (
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                        <h2 className="mb-4 text-lg font-semibold">Data Bank</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {petugas.npwp_masked && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">NPWP</p>
                                    <p className="font-medium">{petugas.npwp_masked}</p>
                                </div>
                            )}
                            {petugas.bank && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">Bank</p>
                                    <p className="font-medium">{petugas.bank}</p>
                                </div>
                            )}
                            {petugas.no_rekening_masked && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">Nomor Rekening</p>
                                    <p className="font-medium">{petugas.no_rekening_masked}</p>
                                </div>
                            )}
                            {petugas.nama_rekening && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">Nama Rekening</p>
                                    <p className="font-medium">{petugas.nama_rekening}</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Riwayat Alokasi */}
                <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-900">
                    <h2 className="mb-4 text-lg font-semibold">Riwayat Alokasi</h2>
                    {petugas.alokasi && petugas.alokasi.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Kegiatan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Posisi
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Periode
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Jumlah
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                    {petugas.alokasi.map((alokasi) => (
                                        <tr key={alokasi.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                            <td className="px-6 py-4 text-sm">
                                                <div className="font-medium">{alokasi.kegiatan.nama_kegiatan}</div>
                                                <div className="text-neutral-600 dark:text-neutral-400">
                                                    {alokasi.kegiatan.kode_kegiatan}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm">{alokasi.rate_honor.posisi}</td>
                                            <td className="px-6 py-4 text-sm">
                                                {bulanNames[alokasi.bulan - 1]} {alokasi.tahun}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {alokasi.jumlah_satuan} {alokasi.rate_honor.satuan.nama}
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium">
                                                {formatRupiah(alokasi.total_honor)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                        alokasi.status === 'disetujui'
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                            : alokasi.status === 'diajukan'
                                                              ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                                                              : alokasi.status === 'ditolak'
                                                                ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                                                : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200'
                                                    }`}
                                                >
                                                    {alokasi.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-neutral-600 dark:text-neutral-400">Belum ada riwayat alokasi.</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}


