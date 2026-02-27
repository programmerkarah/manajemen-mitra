import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
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
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';

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
    totalNominal: number;
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
                    totalNominal: groupItems.reduce(
                        (sum, i) => sum + i.nominal,
                        0,
                    ),
                    jumlahPengajuan: groupItems.length,
                    aggregatedStatus: getAggregatedStatus(groupItems),
                };
            })
            .sort((a, b) => a.kegiatanKode.localeCompare(b.kegiatanKode));
    }, [items]);

    const handleFilterChange = (newBulan: string) => {
        router.get(
            '/pengajuan-pulsa',
            { bulan: newBulan },
            { preserveState: true },
        );
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
                                        Total Nominal
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
                                {kegiatanGroups.length === 0 ? (
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
                                    kegiatanGroups.map((group) => (
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
                                                    group.totalNominal,
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
                                                    asChild
                                                    className="gap-1.5"
                                                >
                                                    <Link
                                                        href={`/pengajuan-pulsa/detail?kegiatan_id=${group.kegiatanId}&bulan=${bulan}`}
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        Lihat Detail
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}

PengajuanPulsaIndex.layout = (page: React.ReactNode) => page;
