import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
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

interface ShowProps {
    petugas: Petugas;
    tren_alokasi: TrenAlokasi[];
    active_year: number;
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
}: ShowProps) {
    const { auth } = usePage<SharedData>().props;

    // Check if user can edit (not pj, administrator, or ketua_tim)
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

                {/* Informasi Dasar */}
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

                {/* Data Bank */}
                {(petugas.npwp_masked ||
                    petugas.bank ||
                    petugas.no_rekening_masked) && (
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
                                        Nomor Rekening
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

                {/* Ringkasan Alokasi */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <ContentCard>
                        <div className="flex items-center gap-4">
                            <div className="shrink-0 rounded-xl bg-blue-100 p-3 dark:bg-blue-900/30">
                                <Briefcase className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    Kegiatan Dialokasikan
                                </p>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    {jumlahKegiatan}
                                </p>
                                <p className="text-xs text-neutral-400 dark:text-neutral-500">
                                    kegiatan
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center gap-4">
                            <div className="shrink-0 rounded-xl bg-purple-100 p-3 dark:bg-purple-900/30">
                                <CalendarDays className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    Bulan Dialokasikan
                                </p>
                                <p className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    {jumlahBulan}
                                </p>
                                <p className="text-xs text-neutral-400 dark:text-neutral-500">
                                    periode
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center gap-4">
                            <div className="shrink-0 rounded-xl bg-emerald-100 p-3 dark:bg-emerald-900/30">
                                <Wallet className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    Total Honor Diterima
                                </p>
                                <p className="text-xl font-bold text-neutral-900 dark:text-white">
                                    {formatRupiah(totalHonor)}
                                </p>
                                <p className="text-xs text-neutral-400 dark:text-neutral-500">
                                    dari seluruh alokasi
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                {/* Grafik Tren Alokasi */}
                <ContentCard>
                    <h2 className="mb-1 text-lg font-semibold">
                        Tren Alokasi {active_year}
                    </h2>
                    <p className="mb-5 text-xs text-neutral-500 dark:text-neutral-400">
                        Jumlah kegiatan dan total honor per bulan sepanjang
                        tahun {active_year}
                    </p>
                    <ResponsiveContainer width="100%" height={260}>
                        <AreaChart
                            data={tren_alokasi.map((d) => ({
                                ...d,
                                total_honor_juta: +d.total_honor.toFixed(2),
                            }))}
                            margin={{ top: 4, right: 16, left: 0, bottom: 0 }}
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
                                dataKey="bulan"
                                tickFormatter={(v) =>
                                    [
                                        'Jan',
                                        'Feb',
                                        'Mar',
                                        'Apr',
                                        'Mei',
                                        'Jun',
                                        'Jul',
                                        'Agu',
                                        'Sep',
                                        'Okt',
                                        'Nov',
                                        'Des',
                                    ][parseInt(v) - 1]
                                }
                                tick={{ fontSize: 12 }}
                                tickLine={false}
                                axisLine={false}
                                stroke="currentColor"
                                className="text-neutral-500 dark:text-neutral-400"
                            />
                            <YAxis
                                yAxisId="kegiatan"
                                orientation="left"
                                allowDecimals={false}
                                tick={{ fontSize: 12 }}
                                tickLine={false}
                                axisLine={false}
                                stroke="currentColor"
                                className="text-neutral-500 dark:text-neutral-400"
                                width={28}
                            />
                            <YAxis
                                yAxisId="honor"
                                orientation="right"
                                tick={{ fontSize: 12 }}
                                tickLine={false}
                                axisLine={false}
                                stroke="currentColor"
                                className="text-neutral-500 dark:text-neutral-400"
                                tickFormatter={(v) =>
                                    `Rp ${v.toLocaleString('id-ID')}`
                                }
                                width={44}
                            />
                            <ChartTooltip
                                contentStyle={{
                                    borderRadius: '8px',
                                    fontSize: '13px',
                                    border: '1px solid rgba(0,0,0,0.08)',
                                }}
                                formatter={(value, name) =>
                                    name === 'Honor (rupiah)'
                                        ? [
                                              `Rp ${Number(value).toLocaleString('id-ID')}`,
                                              name,
                                          ]
                                        : [value, name]
                                }
                                labelFormatter={(label) =>
                                    [
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
                                    ][parseInt(label) - 1]
                                }
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

                {/* Riwayat Alokasi */}
                <ContentCard>
                    <h2 className="mb-4 text-lg font-semibold">
                        Riwayat Alokasi
                    </h2>
                    {petugas.alokasi && petugas.alokasi.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Kegiatan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Posisi
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Tahapan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Periode
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Jumlah Penugasan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                    {petugas.alokasi.map((alokasi) => (
                                        <tr
                                            key={alokasi.id}
                                            className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                        >
                                            <td className="px-6 py-4 text-sm">
                                                <div className="font-medium">
                                                    {
                                                        alokasi.kegiatan
                                                            .nama_kegiatan
                                                    }
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {alokasi.rate_honor.posisi}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <div className="flex flex-wrap gap-1">
                                                    {(alokasi.jumlah_satuan ??
                                                        0) > 0 && (
                                                        <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                            Pendataan
                                                        </span>
                                                    )}
                                                    {(alokasi.jumlah_satuan_listing ??
                                                        0) > 0 && (
                                                        <span className="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                            Listing
                                                        </span>
                                                    )}
                                                    {!(
                                                        (alokasi.jumlah_satuan ??
                                                            0) > 0
                                                    ) &&
                                                        !(
                                                            (alokasi.jumlah_satuan_listing ??
                                                                0) > 0
                                                        ) && (
                                                            <span className="text-neutral-400">
                                                                -
                                                            </span>
                                                        )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {bulanNames[alokasi.bulan - 1]}{' '}
                                                {alokasi.tahun}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <div className="space-y-1">
                                                    {(alokasi.jumlah_satuan ??
                                                        0) > 0 && (
                                                        <div>
                                                            {
                                                                alokasi.jumlah_satuan
                                                            }{' '}
                                                            {
                                                                alokasi
                                                                    .rate_honor
                                                                    .satuan.nama
                                                            }
                                                        </div>
                                                    )}
                                                    {(alokasi.jumlah_satuan_listing ??
                                                        0) > 0 && (
                                                        <div>
                                                            {
                                                                alokasi.jumlah_satuan_listing
                                                            }{' '}
                                                            {
                                                                alokasi
                                                                    .rate_honor
                                                                    .satuan.nama
                                                            }
                                                        </div>
                                                    )}
                                                    {!(
                                                        (alokasi.jumlah_satuan ??
                                                            0) > 0
                                                    ) &&
                                                        !(
                                                            (alokasi.jumlah_satuan_listing ??
                                                                0) > 0
                                                        ) &&
                                                        '-'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium">
                                                {formatRupiah(
                                                    (Number(
                                                        alokasi.total_honor,
                                                    ) || 0) +
                                                        (Number(
                                                            alokasi.total_honor_listing,
                                                        ) || 0),
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                        alokasi.status ===
                                                        'disetujui'
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                            : alokasi.status ===
                                                                'diajukan'
                                                              ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                                                              : alokasi.status ===
                                                                  'ditolak'
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
                        <p className="text-neutral-600 dark:text-neutral-400">
                            Belum ada riwayat alokasi.
                        </p>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
