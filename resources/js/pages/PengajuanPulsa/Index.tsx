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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Eye,
    FileText,
    Plus,
    XCircle,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengajuan Pulsa', href: '/pengajuan-pulsa' },
];

interface PengajuanPulsaItem {
    id: number;
    hashed_id: string;
    petugas_id: number;
    kegiatan_id: number;
    bulan: string;
    tahun: number;
    jenis_pulsa: 'pelatihan' | 'pendataan';
    nominal: number;
    nominal_disetujui: number | null;
    status: 'draft' | 'dikirim' | 'diterima' | 'ditolak';
    catatan: string | null;
    catatan_penolakan: string | null;
    submitted_at: string | null;
    petugas: { id: number; nama: string } | null;
    kegiatan: {
        id: number;
        kode_kegiatan: string;
        nama_kegiatan: string;
    } | null;
    submitted_by: { id: number; name: string } | null;
    reviewed_by: { id: number; name: string } | null;
}

interface KegiatanGroup {
    kegiatanId: number;
    kegiatanKode: string;
    kegiatanNama: string;
    items: PengajuanPulsaItem[];
    totalNominalDiajukan: number;
    totalNominalDisetujui: number;
    jumlahPengajuan: number;
    aggregatedStatus:
        | 'menunggu'
        | 'diterima'
        | 'ditolak'
        | 'sebagian'
        | 'draft';
}

interface Props {
    pengajuanList: { encrypted: string };
    filters: { bulan: string; tahun: string };
}

type SummaryModalType = 'all' | 'menunggu' | 'diterima' | 'ditolak';

/** Ordered array to avoid JS integer-key reordering in Object.entries */
const BULAN_LIST: Array<[string, string]> = [
    ['01', 'Januari'],
    ['02', 'Februari'],
    ['03', 'Maret'],
    ['04', 'April'],
    ['05', 'Mei'],
    ['06', 'Juni'],
    ['07', 'Juli'],
    ['08', 'Agustus'],
    ['09', 'September'],
    ['10', 'Oktober'],
    ['11', 'November'],
    ['12', 'Desember'],
];
const BULAN_LABELS: Record<string, string> = Object.fromEntries(BULAN_LIST);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

function getAggregatedStatus(
    items: PengajuanPulsaItem[],
): KegiatanGroup['aggregatedStatus'] {
    if (items.length === 0) {
        return 'draft';
    }
    const statuses = items.map((i) => i.status);
    if (statuses.every((s) => s === 'diterima')) {
        return 'diterima';
    }
    if (statuses.every((s) => s === 'ditolak')) {
        return 'ditolak';
    }
    if (statuses.some((s) => s === 'dikirim')) {
        return 'menunggu';
    }
    if (statuses.some((s) => s === 'ditolak')) {
        return 'sebagian';
    }
    return 'draft';
}

const AGGREGATED_STATUS_LABELS: Record<
    KegiatanGroup['aggregatedStatus'],
    string
> = {
    menunggu: 'Menunggu Review',
    diterima: 'Diterima',
    ditolak: 'Ditolak',
    sebagian: 'Sebagian Ditolak',
    draft: 'Draft',
};

const AGGREGATED_STATUS_CLASSES: Record<
    KegiatanGroup['aggregatedStatus'],
    string
> = {
    menunggu:
        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    diterima:
        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    ditolak: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    sebagian:
        'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    draft: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
};

export default function PengajuanPulsaIndex({ pengajuanList, filters }: Props) {
    const { auth } = usePage<SharedData>().props;
    const activeRole = auth.activeRole?.name ?? '';

    const items = useDecryptedData<PengajuanPulsaItem>(pengajuanList.encrypted);

    const [bulan, setBulan] = useState(filters.bulan);
    const tahun = filters.tahun;
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 8;
    const [summaryModalOpen, setSummaryModalOpen] = useState(false);
    const [summaryModalType, setSummaryModalType] =
        useState<SummaryModalType>('all');

    const kegiatanGroups = useMemo<KegiatanGroup[]>(() => {
        const map = new Map<number, PengajuanPulsaItem[]>();
        for (const item of items) {
            if (!map.has(item.kegiatan_id)) {
                map.set(item.kegiatan_id, []);
            }
            map.get(item.kegiatan_id)!.push(item);
        }
        return Array.from(map.entries())
            .map(([kegiatanId, groupItems]) => {
                const first = groupItems[0];
                return {
                    kegiatanId,
                    kegiatanKode: first.kegiatan?.kode_kegiatan ?? '-',
                    kegiatanNama: first.kegiatan?.nama_kegiatan ?? '-',
                    items: groupItems,
                    totalNominalDiajukan: groupItems.reduce(
                        (sum, i) => sum + i.nominal,
                        0,
                    ),
                    totalNominalDisetujui: groupItems.reduce((sum, i) => {
                        if (i.status !== 'diterima') {
                            return sum;
                        }

                        return sum + (i.nominal_disetujui ?? i.nominal);
                    }, 0),
                    jumlahPengajuan: groupItems.length,
                    aggregatedStatus: getAggregatedStatus(groupItems),
                };
            })
            .sort((a, b) => a.kegiatanKode.localeCompare(b.kegiatanKode));
    }, [items]);

    const totalPages = Math.max(1, Math.ceil(kegiatanGroups.length / perPage));

    const paginatedKegiatanGroups = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;

        return kegiatanGroups.slice(startIndex, startIndex + perPage);
    }, [currentPage, kegiatanGroups]);

    const pageStart =
        kegiatanGroups.length === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const pageEnd = Math.min(currentPage * perPage, kegiatanGroups.length);

    useEffect(() => {
        setCurrentPage(1);
    }, [bulan]);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(totalPages);
        }
    }, [currentPage, totalPages]);

    const summaryGroups = useMemo(() => {
        const all = kegiatanGroups;
        const menunggu = all.filter(
            (item) => item.aggregatedStatus === 'menunggu',
        );
        const diterima = all.filter(
            (item) => item.aggregatedStatus === 'diterima',
        );
        const ditolak = all.filter(
            (item) => item.aggregatedStatus === 'ditolak',
        );

        return { all, menunggu, diterima, ditolak };
    }, [kegiatanGroups]);

    const summaryModalItems = useMemo(() => {
        switch (summaryModalType) {
            case 'menunggu':
                return summaryGroups.menunggu;
            case 'diterima':
                return summaryGroups.diterima;
            case 'ditolak':
                return summaryGroups.ditolak;
            case 'all':
            default:
                return summaryGroups.all;
        }
    }, [summaryGroups, summaryModalType]);

    const summaryModalTitle = useMemo(() => {
        switch (summaryModalType) {
            case 'menunggu':
                return 'Pengajuan Menunggu Review';
            case 'diterima':
                return 'Pengajuan Diterima';
            case 'ditolak':
                return 'Pengajuan Ditolak';
            case 'all':
            default:
                return 'Semua Pengajuan';
        }
    }, [summaryModalType]);

    const openSummaryModal = (type: SummaryModalType) => {
        setSummaryModalType(type);
        setSummaryModalOpen(true);
    };

    const summaryTotals = useMemo(() => {
        return kegiatanGroups.reduce(
            (acc, item) => {
                acc.diajukan += item.totalNominalDiajukan;
                acc.disetujui += item.totalNominalDisetujui;

                return acc;
            },
            { diajukan: 0, disetujui: 0 },
        );
    }, [kegiatanGroups]);

    const handleFilterChange = (newBulan: string) => {
        router.get('/pengajuan-pulsa', {
            state: encryptFilters({ bulan: newBulan }),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengajuan Pulsa" />
            <div className="space-y-4">
                <PageHeader
                    title="Pengajuan Pulsa"
                    description="Kelola pengajuan pulsa petugas untuk kegiatan CAPI"
                >
                    {(activeRole === 'ketua_tim' ||
                        activeRole === 'admin' ||
                        activeRole === 'operator') && (
                        <Button asChild className="gap-2">
                            <Link
                                href={`/pengajuan-pulsa/create?bulan=${bulan}`}
                            >
                                <Plus className="h-4 w-4" />
                                Ajukan Pulsa
                            </Link>
                        </Button>
                    )}
                </PageHeader>

                {/* Filter */}
                <ContentCard>
                    <div className="flex flex-wrap gap-4">
                        <div className="space-y-1.5">
                            <Label>Bulan</Label>
                            <Select
                                value={bulan}
                                onValueChange={(v) => {
                                    setBulan(v);
                                    handleFilterChange(v);
                                }}
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {BULAN_LIST.map(([val, label]) => (
                                        <SelectItem key={val} value={val}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </ContentCard>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <button
                        type="button"
                        onClick={() => openSummaryModal('all')}
                        className="cursor-pointer text-left"
                    >
                        <ContentCard className="border border-blue-200/60 bg-gradient-to-br from-blue-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-blue-700 dark:text-blue-300">
                                        Nominal Diajukan
                                    </p>
                                    <p className="mt-2 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                        {formatCurrency(summaryTotals.diajukan)}
                                    </p>
                                </div>
                                <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    <FileText className="h-5 w-5" />
                                </span>
                            </div>
                        </ContentCard>
                    </button>
                    <button
                        type="button"
                        onClick={() => openSummaryModal('menunggu')}
                        className="cursor-pointer text-left"
                    >
                        <ContentCard className="border border-amber-200/60 bg-gradient-to-br from-amber-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-amber-900/40 dark:from-amber-950/30 dark:to-neutral-900">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-amber-700 dark:text-amber-300">
                                        Menunggu Review
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {summaryGroups.menunggu.length}
                                    </p>
                                </div>
                                <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                    <Clock3 className="h-5 w-5" />
                                </span>
                            </div>
                        </ContentCard>
                    </button>
                    <button
                        type="button"
                        onClick={() => openSummaryModal('diterima')}
                        className="cursor-pointer text-left"
                    >
                        <ContentCard className="border border-emerald-200/60 bg-gradient-to-br from-emerald-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                        Nominal Disetujui
                                    </p>
                                    <p className="mt-2 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                        {formatCurrency(
                                            summaryTotals.disetujui,
                                        )}
                                    </p>
                                </div>
                                <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                    <CheckCircle className="h-5 w-5" />
                                </span>
                            </div>
                        </ContentCard>
                    </button>
                    <button
                        type="button"
                        onClick={() => openSummaryModal('ditolak')}
                        className="cursor-pointer text-left"
                    >
                        <ContentCard className="border border-rose-200/60 bg-gradient-to-br from-rose-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-rose-900/40 dark:from-rose-950/30 dark:to-neutral-900">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-rose-700 dark:text-rose-300">
                                        Ditolak
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {summaryGroups.ditolak.length}
                                    </p>
                                </div>
                                <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300">
                                    <XCircle className="h-5 w-5" />
                                </span>
                            </div>
                        </ContentCard>
                    </button>
                </div>

                {/* Per-kegiatan table */}
                <ContentCard padding="none">
                    <div className="px-6 pt-4 pb-2">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {kegiatanGroups.length} kegiatan untuk{' '}
                            {BULAN_LABELS[bulan]} {tahun}
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-4 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Kegiatan
                                    </th>
                                    <th className="px-4 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Jumlah Petugas
                                    </th>
                                    <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Nominal Diajukan
                                    </th>
                                    <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Nominal Disetujui
                                    </th>
                                    <th className="px-4 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    <th className="px-4 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginatedKegiatanGroups.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            Tidak ada pengajuan pulsa untuk{' '}
                                            {BULAN_LABELS[bulan]} {tahun}
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedKegiatanGroups.map((group) => (
                                        <tr
                                            key={group.kegiatanId}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-4 py-3 text-sm">
                                                <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                    {group.kegiatanNama}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {group.jumlahPengajuan} petugas
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                {formatCurrency(
                                                    group.totalNominalDiajukan,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                {formatCurrency(
                                                    group.totalNominalDisetujui,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${AGGREGATED_STATUS_CLASSES[group.aggregatedStatus]}`}
                                                >
                                                    {
                                                        AGGREGATED_STATUS_LABELS[
                                                            group
                                                                .aggregatedStatus
                                                        ]
                                                    }
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-1.5"
                                                    onClick={() =>
                                                        router.get(
                                                            '/pengajuan-pulsa/detail',
                                                            {
                                                                state: encryptFilters(
                                                                    {
                                                                        kegiatan_id:
                                                                            group.kegiatanId,
                                                                        bulan,
                                                                    },
                                                                ),
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Eye className="h-3.5 w-3.5" />
                                                    Lihat Detail
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {kegiatanGroups.length > 0 && (
                        <div className="flex flex-col gap-3 border-t border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Menampilkan {pageStart}-{pageEnd} dari{' '}
                                {kegiatanGroups.length} kegiatan
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((page) =>
                                            Math.max(1, page - 1),
                                        )
                                    }
                                    disabled={currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Sebelumnya
                                </Button>
                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Halaman {currentPage} dari {totalPages}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((page) =>
                                            Math.min(totalPages, page + 1),
                                        )
                                    }
                                    disabled={currentPage >= totalPages}
                                >
                                    Berikutnya
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </ContentCard>

                <Dialog
                    open={summaryModalOpen}
                    onOpenChange={setSummaryModalOpen}
                >
                    <DialogContent className="sm:max-w-7xl">
                        <DialogHeader>
                            <DialogTitle>{summaryModalTitle}</DialogTitle>
                            <DialogDescription>
                                Klik detail untuk membuka rincian pengajuan per
                                kegiatan.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                            {summaryModalItems.length === 0 ? (
                                <div className="rounded-md border border-dashed border-neutral-300 px-4 py-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                    Tidak ada data pada kategori ini.
                                </div>
                            ) : (
                                summaryModalItems.map((group) => (
                                    <div
                                        key={`summary-${group.kegiatanId}`}
                                        className="flex items-center justify-between gap-3 rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-800"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {group.kegiatanNama}
                                            </p>
                                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {group.jumlahPengajuan} petugas
                                            </p>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    router.get(
                                                        '/pengajuan-pulsa/detail',
                                                        {
                                                            state: encryptFilters(
                                                                {
                                                                    kegiatan_id:
                                                                        group.kegiatanId,
                                                                    bulan,
                                                                },
                                                            ),
                                                        },
                                                    )
                                                }
                                            >
                                                <Eye className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}

PengajuanPulsaIndex.layout = (page: React.ReactNode) => page;
