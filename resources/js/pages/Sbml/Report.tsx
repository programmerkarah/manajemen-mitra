import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Sbml } from '@/types';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { AlertCircle, ChevronDown, ChevronRight } from 'lucide-react';

interface MonthlyData {
    bulan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    status_kepegawaian: 'organik' | 'non_organik';
    total_honor: number;
    highest_peran: string;
    max_allowed: number;
    exceeds: boolean;
    difference: number;
    details: Array<{
        kegiatan: string;
        peran: string;
        honor: number;
    }>;
}

interface MitraReport {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    monthly_data: MonthlyData[];
    total_honor_tahun: number;
    has_violations: boolean;
}

interface Props {
    mitras: MitraReport[];
    sbml: Sbml | null;
    tahun: number;
    bulan: string | null;
    filters: {
        tahun: number;
        bulan: string | null;
        jenis_kegiatan: string | null;
        status_kepegawaian: string | null;
    };
}

export default function Report({ mitras, sbml, tahun, bulan, filters }: Props) {
    const currentYear = new Date().getFullYear();
    const tahunOptions = Array.from({ length: 8 }, (_, i) => currentYear - 5 + i);
    const bulanOptions = [
        { value: '', label: 'Semua Bulan' },
        { value: 'Januari', label: 'Januari' },
        { value: 'Februari', label: 'Februari' },
        { value: 'Maret', label: 'Maret' },
        { value: 'April', label: 'April' },
        { value: 'Mei', label: 'Mei' },
        { value: 'Juni', label: 'Juni' },
        { value: 'Juli', label: 'Juli' },
        { value: 'Agustus', label: 'Agustus' },
        { value: 'September', label: 'September' },
        { value: 'Oktober', label: 'Oktober' },
        { value: 'November', label: 'November' },
        { value: 'Desember', label: 'Desember' },
    ];

    const [expandedMitras, setExpandedMitras] = useState<Set<number>>(new Set());

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'PCL/PPL',
            pml: 'PML',
            pengolahan: 'Pengolahan',
        };
        return labels[peran] || peran;
    };

    const getJenisKegiatanLabel = (jenis: 'sensus' | 'survei') => {
        return jenis === 'sensus' ? 'Sensus' : 'Survei';
    };

    const getStatusKepegawaianLabel = (status: 'organik' | 'non_organik') => {
        return status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik';
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/sbml-report', {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const toggleMitraExpand = (mitraId: number) => {
        const newExpanded = new Set(expandedMitras);
        if (newExpanded.has(mitraId)) {
            newExpanded.delete(mitraId);
        } else {
            newExpanded.add(mitraId);
        }
        setExpandedMitras(newExpanded);
    };

    const violatingMitras = mitras.filter(m => m.has_violations);

    return (
        <>
            <Head title="Laporan SBML" />

            <div className="flex flex-col gap-6">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link href="/dashboard">Dashboard</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>Laporan SBML</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div className="flex flex-col space-y-1.5 p-6">
                        <h3 className="text-2xl font-semibold leading-none tracking-tight">
                            Laporan Monitoring SBML
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            Pantau batas honor maksimal mitra per bulan
                        </p>
                    </div>

                    <div className="p-6 pt-0">
                        {/* Summary Alert */}
                        {!sbml && (
                            <div className="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                                <div className="flex items-start">
                                    <AlertCircle className="mr-2 mt-0.5 h-5 w-5 text-yellow-600 dark:text-yellow-500" />
                                    <div>
                                        <h4 className="font-semibold text-yellow-800 dark:text-yellow-500">
                                            SBML Tidak Tersedia
                                        </h4>
                                        <p className="text-sm text-yellow-700 dark:text-yellow-400">
                                            Tidak ada data SBML aktif untuk tahun {tahun}. Silakan tambahkan data SBML terlebih dahulu.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {violatingMitras.length > 0 && (
                            <div className="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                                <div className="flex items-start">
                                    <AlertCircle className="mr-2 mt-0.5 h-5 w-5 text-red-600 dark:text-red-500" />
                                    <div>
                                        <h4 className="font-semibold text-red-800 dark:text-red-500">
                                            Peringatan Pelanggaran Batas Honor
                                        </h4>
                                        <p className="text-sm text-red-700 dark:text-red-400">
                                            Terdapat {violatingMitras.length} mitra yang melebihi batas honor maksimal per bulan.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Filters */}
                        <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-2">
                                <label htmlFor="tahun" className="text-sm font-medium">
                                    Tahun
                                </label>
                                <select
                                    id="tahun"
                                    value={tahun}
                                    onChange={(e) => handleFilterChange('tahun', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    {tahunOptions.map((t) => (
                                        <option key={t} value={t}>
                                            {t}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="bulan" className="text-sm font-medium">
                                    Bulan
                                </label>
                                <select
                                    id="bulan"
                                    value={bulan || ''}
                                    onChange={(e) => handleFilterChange('bulan', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    {bulanOptions.map((b) => (
                                        <option key={b.value} value={b.value}>
                                            {b.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="jenis_kegiatan" className="text-sm font-medium">
                                    Jenis Kegiatan
                                </label>
                                <select
                                    id="jenis_kegiatan"
                                    value={filters.jenis_kegiatan || ''}
                                    onChange={(e) => handleFilterChange('jenis_kegiatan', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option value="">Semua Kegiatan</option>
                                    <option value="sensus">Sensus</option>
                                    <option value="survei">Survei</option>
                                </select>
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="status_kepegawaian" className="text-sm font-medium">
                                    Status Kepegawaian
                                </label>
                                <select
                                    id="status_kepegawaian"
                                    value={filters.status_kepegawaian || ''}
                                    onChange={(e) => handleFilterChange('status_kepegawaian', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="organik">Organik (PNS/PPPK)</option>
                                    <option value="non_organik">Non-Organik</option>
                                </select>
                            </div>
                        </div>

                        {/* Mitra List */}
                        {mitras.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <p className="text-muted-foreground">
                                    Tidak ada data alokasi mitra untuk periode yang dipilih.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {mitras.map((mitra) => {
                                    const isExpanded = expandedMitras.has(mitra.id);

                                    return (
                                        <div
                                            key={mitra.id}
                                            className={`rounded-lg border ${
                                                mitra.has_violations
                                                    ? 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/10'
                                                    : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'
                                            }`}
                                        >
                                            <div
                                                className="flex cursor-pointer items-center justify-between p-4"
                                                onClick={() => toggleMitraExpand(mitra.id)}
                                            >
                                                <div className="flex items-center gap-3">
                                                    {isExpanded ? (
                                                        <ChevronDown className="h-5 w-5 text-gray-500" />
                                                    ) : (
                                                        <ChevronRight className="h-5 w-5 text-gray-500" />
                                                    )}
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <h4 className="font-semibold">{mitra.nama}</h4>
                                                            {mitra.has_violations && (
                                                                <span className="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                    Melebihi Batas
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="text-sm text-muted-foreground">
                                                            NIK: {mitra.nik}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm text-muted-foreground">Total Honor</p>
                                                    <p className="font-semibold">
                                                        {formatCurrency(mitra.total_honor_tahun)}
                                                    </p>
                                                </div>
                                            </div>

                                            {isExpanded && (
                                                <div className="border-t p-4">
                                                    <div className="space-y-4">
                                                        {mitra.monthly_data.map((month) => (
                                                            <div
                                                                key={month.bulan}
                                                                className={`rounded-lg border p-4 ${
                                                                    month.exceeds
                                                                        ? 'border-red-200 bg-red-50/50 dark:border-red-800 dark:bg-red-900/5'
                                                                        : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50'
                                                                }`}
                                                            >
                                                                <div className="mb-3 flex items-center justify-between">
                                                                    <div>
                                                                        <h5 className="font-semibold">{month.bulan}</h5>
                                                                        <div className="mt-1 flex flex-wrap gap-2">
                                                                            <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                                                {getJenisKegiatanLabel(month.jenis_kegiatan)}
                                                                            </span>
                                                                            <span className="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                                                {getStatusKepegawaianLabel(month.status_kepegawaian)}
                                                                            </span>
                                                                            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                                                {getPeranLabel(month.highest_peran)}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div className="text-right">
                                                                        <p className="text-sm text-muted-foreground">
                                                                            Batas Maksimal
                                                                        </p>
                                                                        <p className="font-semibold">
                                                                            {formatCurrency(month.max_allowed)}
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <div className="mb-3 space-y-2">
                                                                    {month.details.map((detail, idx) => (
                                                                        <div
                                                                            key={idx}
                                                                            className="flex justify-between rounded bg-white/50 p-2 text-sm dark:bg-gray-900/50"
                                                                        >
                                                                            <div>
                                                                                <span className="font-medium">
                                                                                    {detail.kegiatan}
                                                                                </span>
                                                                                <span className="ml-2 text-muted-foreground">
                                                                                    ({getPeranLabel(detail.peran)})
                                                                                </span>
                                                                            </div>
                                                                            <span className="font-medium">
                                                                                {formatCurrency(detail.honor)}
                                                                            </span>
                                                                        </div>
                                                                    ))}
                                                                </div>

                                                                <div
                                                                    className={`flex items-center justify-between border-t pt-3 ${
                                                                        month.exceeds
                                                                            ? 'border-red-200 dark:border-red-800'
                                                                            : 'border-gray-200 dark:border-gray-700'
                                                                    }`}
                                                                >
                                                                    <span className="font-semibold">Total Honor Bulan Ini:</span>
                                                                    <div className="text-right">
                                                                        <p className="font-bold">
                                                                            {formatCurrency(month.total_honor)}
                                                                        </p>
                                                                        {month.exceeds && (
                                                                            <p className="text-sm font-semibold text-red-600 dark:text-red-400">
                                                                                Melebihi {formatCurrency(Math.abs(month.difference))}
                                                                            </p>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Report.layout = (page: React.ReactNode) => <AppLayout children={page} />;
