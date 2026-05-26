import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Briefcase,
    CalendarDays,
    Pencil,
    Trash2,
    Wallet,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    Tooltip as ChartTooltip,
    Legend,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';

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
        jumlah_satuan: number | null;
        jumlah_satuan_listing: number | null;
        total_honor: number | null;
        total_honor_listing: number | null;
        status: string;
    }>;
}

interface TrenAlokasi {
    bulan: string;
    jumlah_kegiatan: number;
    total_honor: number;
}

interface RiwayatAlokasiRingkasItem {
    kegiatan: {
        nama_kegiatan: string;
        kode_kegiatan: string;
    };
    jumlah_periode: number;
    total_honor: number;
    details: Array<{
        id: number;
        posisi: string;
        periode: {
            bulan: number;
            tahun: number;
        };
        tahapan: string[];
        jumlah_satuan: number;
        jumlah_satuan_listing: number;
        satuan: string;
        total_honor: number;
        status: string;
    }>;
}

interface ShowProps {
    petugas: Petugas;
    tren_alokasi: TrenAlokasi[];
    active_year: number;
    riwayat_alokasi_ringkas: RiwayatAlokasiRingkasItem[];
}

const bulanNames = [
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

export default function Show({
    petugas,
    tren_alokasi,
    active_year,
    riwayat_alokasi_ringkas,
}: ShowProps) {
    const { auth } = usePage<SharedData>().props;

    const canEdit =
        auth.activeRole?.name !== 'pj' &&
        auth.activeRole?.name !== 'administrator' &&
        auth.activeRole?.name !== 'ketua_tim';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Petugas', href: '/petugas' },
        { title: petugas.nama, href: `/petugas/${petugas.hashed_id}` },
    ];

    const formatRupiah = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const handleDelete = () => {
        if (confirm('Apakah Anda yakin ingin menghapus Petugas ini?')) {
            router.delete(`/petugas/${petugas.hashed_id}`);
        }
    };

    const alokasi = petugas.alokasi ?? [];

    const jumlahKegiatan = new Set(alokasi.map((a) => a.kegiatan.nama_kegiatan))
        .size;

    const jumlahBulan = new Set(alokasi.map((a) => `${a.tahun}-${a.bulan}`))
        .size;

    const totalHonor = alokasi.reduce(
        (sum, a) =>
            sum +
            (Number(a.total_honor) || 0) +
            (Number(a.total_honor_listing) || 0),
        0,
    );

    const [riwayatPage, setRiwayatPage] = useState(1);
    const [selectedRiwayat, setSelectedRiwayat] =
        useState<RiwayatAlokasiRingkasItem | null>(null);

    const riwayatPerPage = 10;
    const totalRiwayatPages = Math.max(
        1,
        Math.ceil((riwayat_alokasi_ringkas?.length ?? 0) / riwayatPerPage),
    );
    const safeRiwayatPage = Math.min(riwayatPage, totalRiwayatPages);

    const paginatedRiwayat = useMemo(() => {
        const start = (safeRiwayatPage - 1) * riwayatPerPage;
        return (riwayat_alokasi_ringkas ?? []).slice(
            start,
            start + riwayatPerPage,
        );
    }, [riwayat_alokasi_ringkas, safeRiwayatPage]);

    const getStatusBadgeClass = (status: string) => {
        if (status === 'disetujui') {
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        }
        if (status === 'dikirim' || status === 'diajukan') {
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
        }
        if (status === 'ditolak') {
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        }
        if (status === 'perubahan') {
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        }
        if (status === 'direvisi') {
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        }
        return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Petugas - ${petugas.nama}`} />

            <div className="space-y-6">
                <PageHeader
                    title={petugas.nama}
                    description={`NIK/nip: ${petugas.nik_masked} • ${petugas.pendidikan} • ${petugas.jenis_petugas === 'organik' ? 'Organik' : 'Non-Organik'}`}
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href="/petugas">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    {canEdit && (
                        <>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="gap-2"
                            >
                                <Link
                                    href={`/petugas/${petugas.hashed_id}/edit`}
                                >
                                    <Pencil className="h-4 w-4" />
                                    Edit
                                </Link>
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={handleDelete}
                                className="gap-2"
                            >
                                <Trash2 className="h-4 w-4" />
                                Hapus
                            </Button>
                        </>
                    )}
                </PageHeader>

                <ContentCard>
                    <h2 className="mb-4 text-lg font-semibold">
                        Informasi Dasar
                    </h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                NIK
                            </p>
                            <p className="font-medium">{petugas.nik_masked}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Email
                            </p>
                            <p className="font-medium">{petugas.email}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Telepon
                            </p>
                            <p className="font-medium">{petugas.telepon}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Pendidikan
                            </p>
                            <p className="font-medium">{petugas.pendidikan}</p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Tahun Bergabung
                            </p>
                            <p className="font-medium">
                                {petugas.tahun_bergabung}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Status
                            </p>
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
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Jenis Petugas
                            </p>
                            <span
                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                    petugas.jenis_petugas === 'organik'
                                        ? 'bg-blue-100 text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300'
                                        : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                }`}
                            >
                                {petugas.jenis_petugas === 'organik'
                                    ? 'Pegawai Organik'
                                    : 'Non-Organik (Mitra)'}
                            </span>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Alamat
                            </p>
                            <p className="font-medium">{petugas.alamat}</p>
                        </div>
                    </div>
                </ContentCard>

                {(petugas.npwp_masked ||
                    petugas.bank ||
                    petugas.no_rekening_masked ||
                    petugas.nama_rekening) && (
                    <ContentCard>
                        <h2 className="mb-4 text-lg font-semibold">
                            Data Bank
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {petugas.npwp_masked && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        NPWP
                                    </p>
                                    <p className="font-medium">
                                        {petugas.npwp_masked}
                                    </p>
                                </div>
                            )}
                            {petugas.bank && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Bank
                                    </p>
                                    <p className="font-medium">
                                        {petugas.bank}
                                    </p>
                                </div>
                            )}
                            {petugas.no_rekening_masked && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        No Rekening
                                    </p>
                                    <p className="font-medium">
                                        {petugas.no_rekening_masked}
                                    </p>
                                </div>
                            )}
                            {petugas.nama_rekening && (
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Nama Rekening
                                    </p>
                                    <p className="font-medium">
                                        {petugas.nama_rekening}
                                    </p>
                                </div>
                            )}
                        </div>
                    </ContentCard>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <ContentCard>
                        <div className="flex items-center gap-3">
                            <div className="shrink-0 rounded-xl bg-blue-100 p-3 dark:bg-blue-900/30">
                                <Briefcase className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Kegiatan Dialokasikan
                                </p>
                                <p className="text-xl font-bold">
                                    {jumlahKegiatan}
                                </p>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="flex items-center gap-3">
                            <div className="shrink-0 rounded-xl bg-purple-100 p-3 dark:bg-purple-900/30">
                                <CalendarDays className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Bulan Dialokasikan
                                </p>
                                <p className="text-xl font-bold">
                                    {jumlahBulan}
                                </p>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="flex items-center gap-3">
                            <div className="shrink-0 rounded-xl bg-emerald-100 p-3 dark:bg-emerald-900/30">
                                <Wallet className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Total Honor
                                </p>
                                <p className="text-xl font-bold">
                                    {formatRupiah(totalHonor)}
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                <ContentCard>
                    <h2 className="mb-4 text-lg font-semibold">
                        Tren Alokasi {active_year}
                    </h2>
                    <ResponsiveContainer width="100%" height={300}>
                        <AreaChart
                            data={tren_alokasi.map((d) => ({
                                ...d,
                                bulan_label: bulanNames[Number(d.bulan) - 1],
                            }))}
                        >
                            <defs>
                                <linearGradient
                                    id="colorKegiatan"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor="#6366f1"
                                        stopOpacity={0.3}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor="#6366f1"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                                <linearGradient
                                    id="colorHonor"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor="#10b981"
                                        stopOpacity={0.3}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor="#10b981"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="currentColor"
                                className="text-neutral-200 dark:text-neutral-700"
                                opacity={0.4}
                            />
                            <XAxis
                                dataKey="bulan_label"
                                tick={{ fontSize: 12 }}
                                axisLine={false}
                                tickLine={false}
                            />
                            <YAxis
                                yAxisId="kegiatan"
                                orientation="left"
                                tick={{ fontSize: 12 }}
                                axisLine={false}
                                tickLine={false}
                                allowDecimals={false}
                            />
                            <YAxis
                                yAxisId="honor"
                                orientation="right"
                                tick={{ fontSize: 12 }}
                                axisLine={false}
                                tickLine={false}
                                tickFormatter={(value) =>
                                    `${Math.round(value / 1000000)} jt`
                                }
                            />
                            <ChartTooltip
                                formatter={(value, name) => {
                                    if (name === 'Honor (rupiah)') {
                                        return [
                                            formatRupiah(Number(value) || 0),
                                            String(name),
                                        ];
                                    }
                                    return [Number(value) || 0, String(name)];
                                }}
                            />
                            <Legend
                                iconType="circle"
                                iconSize={8}
                                wrapperStyle={{
                                    fontSize: '12px',
                                    paddingTop: '12px',
                                }}
                            />
                            <Area
                                yAxisId="kegiatan"
                                type="monotone"
                                dataKey="jumlah_kegiatan"
                                name="Kegiatan"
                                stroke="#6366f1"
                                strokeWidth={2}
                                fill="url(#colorKegiatan)"
                                dot={{ r: 3, fill: '#6366f1' }}
                                activeDot={{ r: 5 }}
                            />
                            <Area
                                yAxisId="honor"
                                type="monotone"
                                dataKey="total_honor"
                                name="Honor (rupiah)"
                                stroke="#10b981"
                                strokeWidth={2}
                                fill="url(#colorHonor)"
                                dot={{ r: 3, fill: '#10b981' }}
                                activeDot={{ r: 5 }}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </ContentCard>

                <ContentCard>
                    <h2 className="mb-4 text-lg font-semibold">
                        Riwayat Alokasi
                    </h2>
                    {riwayat_alokasi_ringkas &&
                    riwayat_alokasi_ringkas.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Kegiatan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Jumlah Periode
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                    {paginatedRiwayat.map((riwayat) => (
                                        <tr
                                            key={`${riwayat.kegiatan.kode_kegiatan}-${riwayat.kegiatan.nama_kegiatan}`}
                                            className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                        >
                                            <td className="px-6 py-4 text-sm">
                                                <div className="font-medium">
                                                    {
                                                        riwayat.kegiatan
                                                            .nama_kegiatan
                                                    }
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {riwayat.jumlah_periode}
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium">
                                                {formatRupiah(
                                                    riwayat.total_honor,
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setSelectedRiwayat(
                                                            riwayat,
                                                        )
                                                    }
                                                >
                                                    Lihat Detail
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="mt-4 flex items-center justify-between gap-3">
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Halaman {safeRiwayatPage} dari{' '}
                                    {totalRiwayatPages}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setRiwayatPage((prev) =>
                                                Math.max(1, prev - 1),
                                            )
                                        }
                                        disabled={safeRiwayatPage <= 1}
                                    >
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setRiwayatPage((prev) =>
                                                Math.min(
                                                    totalRiwayatPages,
                                                    prev + 1,
                                                ),
                                            )
                                        }
                                        disabled={
                                            safeRiwayatPage >= totalRiwayatPages
                                        }
                                    >
                                        Berikutnya
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <p className="text-neutral-600 dark:text-neutral-400">
                            Belum ada riwayat alokasi.
                        </p>
                    )}
                </ContentCard>

                <Dialog
                    open={Boolean(selectedRiwayat)}
                    onOpenChange={(open) => {
                        if (!open) {
                            setSelectedRiwayat(null);
                        }
                    }}
                >
                    <DialogContent className="sm:max-w-4xl">
                        <DialogHeader>
                            <DialogTitle>
                                Detail Riwayat Alokasi Kegiatan
                            </DialogTitle>
                            <DialogDescription>
                                {selectedRiwayat
                                    ? `${selectedRiwayat.kegiatan.nama_kegiatan}`
                                    : ''}
                            </DialogDescription>
                        </DialogHeader>

                        {selectedRiwayat && (
                            <div className="max-h-[60vh] overflow-auto">
                                <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                    <thead>
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider uppercase">
                                                Periode
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider uppercase">
                                                Posisi
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider uppercase">
                                                Tahapan
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider uppercase">
                                                Jumlah Penugasan
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider uppercase">
                                                Total Honor
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider uppercase">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                        {selectedRiwayat.details.map(
                                            (detail) => (
                                                <tr
                                                    key={detail.id}
                                                    className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                                >
                                                    <td className="px-4 py-3 text-sm">
                                                        {
                                                            bulanNames[
                                                                detail.periode
                                                                    .bulan - 1
                                                            ]
                                                        }{' '}
                                                        {detail.periode.tahun}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        {detail.posisi}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <div className="flex flex-wrap gap-1">
                                                            {detail.tahapan
                                                                .length > 0 ? (
                                                                detail.tahapan.map(
                                                                    (
                                                                        tahapan,
                                                                    ) => (
                                                                        <span
                                                                            key={`${detail.id}-${tahapan}`}
                                                                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                                                tahapan ===
                                                                                'Pendataan'
                                                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'
                                                                                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'
                                                                            }`}
                                                                        >
                                                                            {
                                                                                tahapan
                                                                            }
                                                                        </span>
                                                                    ),
                                                                )
                                                            ) : (
                                                                <span className="text-neutral-400">
                                                                    -
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <div className="space-y-1">
                                                            {detail.jumlah_satuan >
                                                                0 && (
                                                                <div>
                                                                    {
                                                                        detail.jumlah_satuan
                                                                    }{' '}
                                                                    {
                                                                        detail.satuan
                                                                    }
                                                                </div>
                                                            )}
                                                            {detail.jumlah_satuan_listing >
                                                                0 && (
                                                                <div>
                                                                    {
                                                                        detail.jumlah_satuan_listing
                                                                    }{' '}
                                                                    {
                                                                        detail.satuan
                                                                    }
                                                                </div>
                                                            )}
                                                            {detail.jumlah_satuan <=
                                                                0 &&
                                                                detail.jumlah_satuan_listing <=
                                                                    0 &&
                                                                '-'}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm font-medium">
                                                        {formatRupiah(
                                                            detail.total_honor,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <span
                                                            className={`rounded-full px-2 py-1 text-xs font-medium ${getStatusBadgeClass(detail.status)}`}
                                                        >
                                                            {detail.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
