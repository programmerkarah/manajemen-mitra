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
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import {
    CheckCircle,
    ChevronDown,
    ChevronRight,
    Clock,
    SendHorizontal,
    Users,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Monitoring', href: '#' },
    { title: 'Monitoring Pulsa', href: '/monitoring-pulsa' },
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
    status: 'dikirim' | 'diterima' | 'ditolak';
    petugas: { id: number; nama: string } | null;
    kegiatan: {
        id: number;
        kode_kegiatan: string;
        nama_kegiatan: string;
    } | null;
    submitted_by: { id: number; name: string } | null;
    reviewed_by: { id: number; name: string } | null;
}

interface PetugasGroup {
    petugasId: number;
    petugasNama: string;
    rows: PengajuanPulsaItem[];
    totalNominal: number;
    nominalDiajukan: number;
    nominalDisetujui: number;
    nominalDitolak: number;
}

interface Props {
    pengajuanList: { encrypted: string };
    filters: { bulan: string; tahun: string };
}

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

const STATUS_CLASSES: Record<PengajuanPulsaItem['status'], string> = {
    dikirim: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    diterima:
        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    ditolak: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
};

const STATUS_LABELS: Record<PengajuanPulsaItem['status'], string> = {
    dikirim: 'Menunggu',
    diterima: 'Diterima',
    ditolak: 'Ditolak',
};

type ActiveTab = 'semua' | 'disetujui';

export default function MonitoringPulsaIndex({
    pengajuanList,
    filters,
}: Props) {
    const items = useDecryptedData<PengajuanPulsaItem>(pengajuanList.encrypted);

    const [bulan, setBulan] = useState(filters.bulan);
    const tahun = filters.tahun;
    const [activeTab, setActiveTab] = useState<ActiveTab>('semua');

    const handleFilterChange = (newBulan: string) => {
        setBulan(newBulan);
        router.get(
            '/monitoring-pulsa',
            { bulan: newBulan },
            { preserveState: true },
        );
    };

    const approvedItems = useMemo(
        () => items.filter((i) => i.status === 'diterima'),
        [items],
    );
    const pendingItems = useMemo(
        () => items.filter((i) => i.status === 'dikirim'),
        [items],
    );

    const totalNominalDiajukan = useMemo(
        () => items.reduce((sum, i) => sum + i.nominal, 0),
        [items],
    );
    const totalNominalDisetujui = useMemo(
        () =>
            approvedItems.reduce(
                (sum, i) => sum + (i.nominal_disetujui ?? i.nominal),
                0,
            ),
        [approvedItems],
    );
    const totalPetugas = useMemo(
        () => new Set(items.map((i) => i.petugas_id)).size,
        [items],
    );
    const totalMenunggu = pendingItems.length;

    const displayItems = activeTab === 'disetujui' ? approvedItems : items;

    const petugasGroups = useMemo<PetugasGroup[]>(() => {
        const map = new Map<number, PetugasGroup>();
        for (const item of displayItems) {
            if (!map.has(item.petugas_id)) {
                map.set(item.petugas_id, {
                    petugasId: item.petugas_id,
                    petugasNama:
                        item.petugas?.nama ?? `Petugas #${item.petugas_id}`,
                    rows: [],
                    totalNominal: 0,
                    nominalDiajukan: 0,
                    nominalDisetujui: 0,
                    nominalDitolak: 0,
                });
            }
            const group = map.get(item.petugas_id)!;
            group.rows.push(item);
            group.totalNominal += item.nominal;
            group.nominalDiajukan += item.nominal;
            if (item.status === 'diterima') {
                group.nominalDisetujui +=
                    item.nominal_disetujui ?? item.nominal;
            }
            if (item.status === 'ditolak') {
                group.nominalDitolak += item.nominal;
            }
        }
        return Array.from(map.values()).sort((a, b) =>
            a.petugasNama.localeCompare(b.petugasNama, 'id'),
        );
    }, [displayItems]);

    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

    const toggleRow = (petugasId: number) => {
        setExpandedRows((prev) => {
            const next = new Set(prev);
            if (next.has(petugasId)) {
                next.delete(petugasId);
            } else {
                next.add(petugasId);
            }
            return next;
        });
    };

    const colSpan = activeTab === 'semua' ? 6 : 4;

    const statCards = [
        {
            label: 'Total Nominal Diajukan',
            value: formatCurrency(totalNominalDiajukan),
            icon: SendHorizontal,
            colorClass: 'text-blue-600 dark:text-blue-400',
            bgClass: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            label: 'Total Nominal Disetujui',
            value: formatCurrency(totalNominalDisetujui),
            icon: CheckCircle,
            colorClass: 'text-green-600 dark:text-green-400',
            bgClass: 'bg-green-50 dark:bg-green-900/20',
        },
        {
            label: 'Total Petugas',
            value: `${totalPetugas} petugas`,
            icon: Users,
            colorClass: 'text-purple-600 dark:text-purple-400',
            bgClass: 'bg-purple-50 dark:bg-purple-900/20',
        },
        {
            label: 'Menunggu Review',
            value: `${totalMenunggu} pengajuan`,
            icon: Clock,
            colorClass: 'text-orange-600 dark:text-orange-400',
            bgClass: 'bg-orange-50 dark:bg-orange-900/20',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rekap Pengadaan Pulsa Petugas" />
            <div className="space-y-4">
                <PageHeader
                    title="Rekap Pengadaan Pulsa Petugas"
                    description="Pantau status pengajuan dan persetujuan pulsa petugas"
                />

                {/* Filter */}
                <ContentCard>
                    <div className="flex flex-wrap gap-4">
                        <div className="space-y-1.5">
                            <Label>Bulan</Label>
                            <Select
                                value={bulan}
                                onValueChange={handleFilterChange}
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

                {/* Summary Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {statCards.map((card) => {
                        const Icon = card.icon;
                        return (
                            <ContentCard key={card.label}>
                                <div className="flex items-center gap-4">
                                    <div
                                        className={`rounded-xl p-3 ${card.bgClass}`}
                                    >
                                        <Icon
                                            className={`h-5 w-5 ${card.colorClass}`}
                                        />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                            {card.label}
                                        </p>
                                        <p className="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            {card.value}
                                        </p>
                                    </div>
                                </div>
                            </ContentCard>
                        );
                    })}
                </div>

                {/* Tabs + Table */}
                <ContentCard padding="none">
                    {/* Tab buttons */}
                    <div className="flex gap-1 border-b border-neutral-200 px-4 pt-3 dark:border-neutral-800">
                        {(
                            [
                                {
                                    key: 'semua' as ActiveTab,
                                    label: 'Semua Pengajuan',
                                    count: items.length,
                                },
                                {
                                    key: 'disetujui' as ActiveTab,
                                    label: 'Disetujui',
                                    count: approvedItems.length,
                                },
                            ] as const
                        ).map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => {
                                    setActiveTab(tab.key);
                                    setExpandedRows(new Set());
                                }}
                                className={`relative flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none ${
                                    activeTab === tab.key
                                        ? 'text-neutral-900 dark:text-neutral-100'
                                        : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200'
                                }`}
                            >
                                {tab.label}
                                <span
                                    className={`rounded-full px-1.5 py-0.5 text-xs font-semibold ${
                                        activeTab === tab.key
                                            ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900'
                                            : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                                    }`}
                                >
                                    {tab.count}
                                </span>
                                {activeTab === tab.key && (
                                    <span className="absolute right-0 bottom-0 left-0 h-0.5 rounded-t-full bg-neutral-900 dark:bg-neutral-100" />
                                )}
                            </button>
                        ))}
                    </div>

                    {/* Summary line */}
                    <div className="px-6 pt-4 pb-2">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {petugasGroups.length} petugas untuk{' '}
                            {BULAN_LABELS[bulan]} {tahun}
                        </p>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="w-12 px-4 py-3.5" />
                                    <th className="px-4 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Petugas
                                    </th>
                                    <th className="px-4 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Jml Pengajuan
                                    </th>
                                    {activeTab === 'semua' ? (
                                        <>
                                            <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-blue-700 dark:text-blue-400">
                                                Total Diajukan
                                            </th>
                                            <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-green-700 dark:text-green-400">
                                                Total Disetujui
                                            </th>
                                            <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-red-700 dark:text-red-400">
                                                Total Ditolak
                                            </th>
                                        </>
                                    ) : (
                                        <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                            Total Nominal
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {petugasGroups.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={colSpan}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            {activeTab === 'disetujui'
                                                ? `Tidak ada pengajuan yang disetujui untuk ${BULAN_LABELS[bulan]} ${tahun}`
                                                : `Tidak ada pengajuan pulsa untuk ${BULAN_LABELS[bulan]} ${tahun}`}
                                        </td>
                                    </tr>
                                ) : (
                                    petugasGroups.map((group) => {
                                        const isExpanded = expandedRows.has(
                                            group.petugasId,
                                        );

                                        return (
                                            <>
                                                {/* Petugas main row */}
                                                <tr
                                                    key={`main-${group.petugasId}`}
                                                    className="cursor-pointer transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                                    onClick={() =>
                                                        toggleRow(
                                                            group.petugasId,
                                                        )
                                                    }
                                                >
                                                    <td className="px-4 py-3 text-center">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 w-7 p-0"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                toggleRow(
                                                                    group.petugasId,
                                                                );
                                                            }}
                                                        >
                                                            {isExpanded ? (
                                                                <ChevronDown className="h-4 w-4" />
                                                            ) : (
                                                                <ChevronRight className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                        {group.petugasNama}
                                                    </td>
                                                    <td className="px-4 py-3 text-center text-sm text-neutral-700 dark:text-neutral-300">
                                                        {group.rows.length}
                                                    </td>
                                                    {activeTab === 'semua' ? (
                                                        <>
                                                            <td className="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-blue-700 dark:text-blue-400">
                                                                {group.nominalDiajukan >
                                                                0 ? (
                                                                    formatCurrency(
                                                                        group.nominalDiajukan,
                                                                    )
                                                                ) : (
                                                                    <span className="text-neutral-400 dark:text-neutral-600">
                                                                        —
                                                                    </span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-green-700 dark:text-green-400">
                                                                {group.nominalDisetujui >
                                                                0 ? (
                                                                    formatCurrency(
                                                                        group.nominalDisetujui,
                                                                    )
                                                                ) : (
                                                                    <span className="text-neutral-400 dark:text-neutral-600">
                                                                        —
                                                                    </span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-red-700 dark:text-red-400">
                                                                {group.nominalDitolak >
                                                                0 ? (
                                                                    formatCurrency(
                                                                        group.nominalDitolak,
                                                                    )
                                                                ) : (
                                                                    <span className="text-neutral-400 dark:text-neutral-600">
                                                                        —
                                                                    </span>
                                                                )}
                                                            </td>
                                                        </>
                                                    ) : (
                                                        <td className="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                            {formatCurrency(
                                                                group.totalNominal,
                                                            )}
                                                        </td>
                                                    )}
                                                </tr>

                                                {/* Expanded detail row */}
                                                {isExpanded && (
                                                    <tr
                                                        key={`expanded-${group.petugasId}`}
                                                    >
                                                        <td
                                                            colSpan={colSpan}
                                                            className="bg-neutral-50/70 p-0 dark:bg-neutral-900/40"
                                                        >
                                                            <div className="px-12 py-3">
                                                                <div className="overflow-hidden rounded-md border border-neutral-200 dark:border-neutral-800">
                                                                    <table className="w-full text-sm">
                                                                        <thead className="border-b border-neutral-200 bg-neutral-100/70 dark:border-neutral-700 dark:bg-neutral-800/60">
                                                                            <tr>
                                                                                <th className="px-4 py-2.5 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                                                    Kegiatan
                                                                                </th>
                                                                                <th className="px-4 py-2.5 text-center text-xs font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                                                    Jenis
                                                                                    Pulsa
                                                                                </th>
                                                                                <th className="px-4 py-2.5 text-right text-xs font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                                                    Nominal
                                                                                </th>
                                                                                {activeTab ===
                                                                                    'semua' && (
                                                                                    <th className="px-4 py-2.5 text-center text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                                                        Status
                                                                                    </th>
                                                                                )}
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                                                            {group.rows.map(
                                                                                (
                                                                                    item,
                                                                                ) => (
                                                                                    <tr
                                                                                        key={
                                                                                            item.id
                                                                                        }
                                                                                        className="bg-white transition-colors hover:bg-neutral-50 dark:bg-neutral-900/20 dark:hover:bg-neutral-800/40"
                                                                                    >
                                                                                        <td className="px-4 py-2.5">
                                                                                            <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                                                                {item
                                                                                                    .kegiatan
                                                                                                    ?.nama_kegiatan ??
                                                                                                    '-'}
                                                                                            </span>
                                                                                        </td>
                                                                                        <td className="px-4 py-2.5 text-center text-xs text-neutral-700 capitalize dark:text-neutral-300">
                                                                                            {
                                                                                                item.jenis_pulsa
                                                                                            }
                                                                                        </td>
                                                                                        <td className="px-4 py-2.5 text-right text-xs font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                                                            {item.status ===
                                                                                            'diterima' ? (
                                                                                                <>
                                                                                                    <div className="text-green-700 dark:text-green-400">
                                                                                                        {formatCurrency(
                                                                                                            item.nominal_disetujui ??
                                                                                                                item.nominal,
                                                                                                        )}
                                                                                                    </div>
                                                                                                    {item.nominal_disetujui !==
                                                                                                        null &&
                                                                                                        item.nominal_disetujui !==
                                                                                                            item.nominal && (
                                                                                                            <div className="text-xs text-neutral-400 line-through dark:text-neutral-500">
                                                                                                                {formatCurrency(
                                                                                                                    item.nominal,
                                                                                                                )}
                                                                                                            </div>
                                                                                                        )}
                                                                                                </>
                                                                                            ) : (
                                                                                                formatCurrency(
                                                                                                    item.nominal,
                                                                                                )
                                                                                            )}
                                                                                        </td>
                                                                                        {activeTab ===
                                                                                            'semua' && (
                                                                                            <td className="px-4 py-2.5 text-center">
                                                                                                <span
                                                                                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_CLASSES[item.status]}`}
                                                                                                >
                                                                                                    {
                                                                                                        STATUS_LABELS[
                                                                                                            item
                                                                                                                .status
                                                                                                        ]
                                                                                                    }
                                                                                                </span>
                                                                                            </td>
                                                                                        )}
                                                                                    </tr>
                                                                                ),
                                                                            )}
                                                                        </tbody>
                                                                        <tfoot className="border-t border-neutral-200 bg-neutral-100/70 dark:border-neutral-700 dark:bg-neutral-800/60">
                                                                            <tr>
                                                                                <td
                                                                                    colSpan={
                                                                                        2
                                                                                    }
                                                                                    className="px-4 py-2 text-right text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                                                                                >
                                                                                    Total
                                                                                </td>
                                                                                <td className="px-4 py-2 text-right text-xs font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                                                    {formatCurrency(
                                                                                        activeTab ===
                                                                                            'disetujui'
                                                                                            ? group.nominalDisetujui
                                                                                            : group.totalNominal,
                                                                                    )}
                                                                                </td>
                                                                                {activeTab ===
                                                                                    'semua' && (
                                                                                    <td />
                                                                                )}
                                                                            </tr>
                                                                        </tfoot>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}

MonitoringPulsaIndex.layout = (page: React.ReactNode) => page;
