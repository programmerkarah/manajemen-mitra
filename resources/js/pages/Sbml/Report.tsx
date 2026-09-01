import { ContentCard } from '@/components/content-card';
import { MultiSelectCheckbox } from '@/components/multi-select-checkbox';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

interface AlokasiDetail {
    peran: string;
    jumlah_satuan: number;
    jumlah_satuan_dibayarkan: number;
    jumlah_satuan_listing: number | null;
    jumlah_satuan_listing_dibayarkan: number | null;
    total_honor_listing: number;
    total_honor: number;
    status_kepegawaian: string;
    catatan: string | null;
}

interface KegiatanDetail {
    kegiatan_id: number;
    kegiatan_hashed_id: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    total_honor: number;
    alokasi: AlokasiDetail[];
}

interface PetugasData {
    petugas_id: number;
    petugas_hashed_id: string;
    nama: string;
    nik: string;
    jenis_petugas: 'organik' | 'non_organik';
    total_honor: number;
    max_allowed: number;
    exceeds: boolean;
    difference: number;
    percentage: number;
    kegiatan_count: number;
    kegiatan_details: KegiatanDetail[];
}

interface BulanOption {
    value: string;
    label: string;
}

interface Props {
    petugas: {
        encrypted: string;
    };
    filters: {
        encrypted?: string;
        decrypted?: {
            tahun: number;
            bulan: string;
        };
    };
    bulan_options: BulanOption[];
    tahun_options: number[];
}

export default function Report({
    petugas,
    filters,
    bulan_options,
    tahun_options,
}: Props) {
    const decryptedPetugas = useDecryptedData<PetugasData>(petugas.encrypted);
    const currentMonthValue = String(new Date().getMonth() + 1).padStart(
        2,
        '0',
    );
    const initialFilters = filters.decrypted || {
        tahun: new Date().getFullYear(),
        bulan: currentMonthValue,
    };
    const [selectedTahun, setSelectedTahun] = useState(
        initialFilters.tahun.toString(),
    );
    const [selectedBulan, setSelectedBulan] = useState(initialFilters.bulan);
    const [selectedPetugasIds, setSelectedPetugasIds] = useState<number[]>([]);
    const [currentPage, setCurrentPage] = useState(1);
    const pageSize = 10;
    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Monitoring', href: '#' },
        { title: 'Rekap Honor Petugas', href: '/rekap-honor' },
    ];

    const handleFilterChange = (tahun: string, bulan: string) => {
        router.post(
            '/rekap-honor',
            { tahun, bulan },
            { preserveState: true, replace: true },
        );
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const toggleRow = (petugasId: number) => {
        const newExpanded = new Set(expandedRows);
        if (newExpanded.has(petugasId)) {
            newExpanded.delete(petugasId);
        } else {
            newExpanded.add(petugasId);
        }
        setExpandedRows(newExpanded);
    };

    const getStatusForPercentage = (
        exceeds: boolean,
        percentage: number,
    ): string => {
        if (exceeds) return 'melebihi_batas';
        if (percentage >= 90) return 'mendekati_batas';
        return 'normal';
    };

    const currentMonth = bulan_options.find((b) => b.value === selectedBulan);

    const filteredPetugas = useMemo(() => {
        const source = decryptedPetugas ?? [];

        if (selectedPetugasIds.length === 0) {
            return source;
        }

        return source.filter((petugas) =>
            selectedPetugasIds.includes(petugas.petugas_id),
        );
    }, [decryptedPetugas, selectedPetugasIds]);

    const totalPages = Math.max(
        1,
        Math.ceil(filteredPetugas.length / pageSize),
    );
    const effectiveCurrentPage =
        totalPages > 0 ? Math.min(currentPage, totalPages) : 1;
    const pageRows = useMemo(() => {
        const start = (effectiveCurrentPage - 1) * pageSize;
        return filteredPetugas.slice(start, start + pageSize);
    }, [effectiveCurrentPage, filteredPetugas]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rekap Honor Petugas" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Rekap Honor Petugas
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Rekap total honor yang diterima masing-masing
                            petugas per bulan
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <ContentCard>
                    <div className="flex flex-col gap-4 md:flex-row md:items-end">
                        <div className="flex-1 space-y-2">
                            <label className="text-sm font-medium">Tahun</label>
                            <Select
                                value={selectedTahun}
                                onValueChange={(value) => {
                                    setSelectedTahun(value);
                                    handleFilterChange(value, selectedBulan);
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {tahun_options.map((year) => (
                                        <SelectItem
                                            key={year}
                                            value={year.toString()}
                                        >
                                            {year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex-1 space-y-2">
                            <label className="text-sm font-medium">Bulan</label>
                            <Select
                                value={selectedBulan}
                                onValueChange={(value) => {
                                    setSelectedBulan(value);
                                    handleFilterChange(selectedTahun, value);
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {bulan_options.map((bulan) => (
                                        <SelectItem
                                            key={bulan.value}
                                            value={bulan.value}
                                        >
                                            {bulan.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="min-w-[220px] flex-1 space-y-2 md:max-w-xs">
                            <label className="text-sm font-medium">
                                Filter Petugas
                            </label>
                            <MultiSelectCheckbox
                                options={(decryptedPetugas ?? []).map(
                                    (petugas) => ({
                                        value: petugas.petugas_id,
                                        label: petugas.nama,
                                    }),
                                )}
                                values={selectedPetugasIds}
                                onValuesChange={(values) => {
                                    setSelectedPetugasIds(values);
                                    setCurrentPage(1);
                                }}
                                placeholder="Pilih petugas..."
                            />
                        </div>
                    </div>
                </ContentCard>

                {/* Summary Info */}
                {filteredPetugas && filteredPetugas.length > 0 && (
                    <div className="grid gap-4 md:grid-cols-3">
                        <ContentCard>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Total Petugas
                                </p>
                                <p className="text-2xl font-bold">
                                    {filteredPetugas.length}
                                </p>
                            </div>
                        </ContentCard>
                        <ContentCard>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Total Honor
                                </p>
                                <p className="text-2xl font-bold">
                                    {formatCurrency(
                                        filteredPetugas.reduce(
                                            (sum, p) => sum + p.total_honor,
                                            0,
                                        ),
                                    )}
                                </p>
                            </div>
                        </ContentCard>
                        <ContentCard>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Petugas Melebihi Batas
                                </p>
                                <p className="text-2xl font-bold text-destructive">
                                    {
                                        filteredPetugas.filter((p) => p.exceeds)
                                            .length
                                    }
                                </p>
                            </div>
                        </ContentCard>
                    </div>
                )}

                {/* Table */}
                <ContentCard>
                    {!filteredPetugas || filteredPetugas.length === 0 ? (
                        <div className="py-12 text-center text-muted-foreground">
                            Tidak ada data honor petugas untuk{' '}
                            {currentMonth?.label} {selectedTahun}
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-md border">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50">
                                        <tr>
                                            <th className="h-10 w-12 px-4 text-left align-middle font-medium"></th>
                                            <th className="h-10 px-4 text-left align-middle font-medium">
                                                Nama Petugas
                                            </th>
                                            <th className="h-10 px-4 text-left align-middle font-medium">
                                                NIK
                                            </th>
                                            <th className="h-10 px-4 text-left align-middle font-medium">
                                                Status
                                            </th>
                                            <th className="h-10 px-4 text-right align-middle font-medium">
                                                Total Honor
                                            </th>
                                            <th className="h-10 px-4 text-right align-middle font-medium">
                                                Maks. SBML
                                            </th>
                                            <th className="h-10 px-4 text-left align-middle font-medium">
                                                Status Honor
                                            </th>
                                            <th className="h-10 px-4 text-center align-middle font-medium">
                                                Jml Kegiatan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pageRows.map((p) => (
                                            <>
                                                <tr
                                                    key={p.petugas_id}
                                                    className="border-b"
                                                >
                                                    <td className="p-4 align-middle">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                toggleRow(
                                                                    p.petugas_id,
                                                                )
                                                            }
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            {expandedRows.has(
                                                                p.petugas_id,
                                                            ) ? (
                                                                <ChevronDown className="h-4 w-4" />
                                                            ) : (
                                                                <ChevronRight className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    </td>
                                                    <td className="p-4 align-middle font-medium">
                                                        {p.nama}
                                                    </td>
                                                    <td className="p-4 align-middle text-muted-foreground">
                                                        {p.nik}
                                                    </td>
                                                    <td className="p-4 align-middle">
                                                        <Badge
                                                            variant={
                                                                p.jenis_petugas ===
                                                                'organik'
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {p.jenis_petugas ===
                                                            'organik'
                                                                ? 'Organik'
                                                                : 'Non-Organik'}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-4 text-right align-middle font-medium">
                                                        {formatCurrency(
                                                            p.total_honor,
                                                        )}
                                                    </td>
                                                    <td className="p-4 text-right align-middle text-muted-foreground">
                                                        {formatCurrency(
                                                            p.max_allowed,
                                                        )}
                                                    </td>
                                                    <td className="p-4 align-middle">
                                                        <StatusBadge
                                                            status={getStatusForPercentage(
                                                                p.exceeds,
                                                                p.percentage,
                                                            )}
                                                            label={`${p.percentage.toFixed(1)}%`}
                                                        />
                                                    </td>
                                                    <td className="p-4 text-center align-middle">
                                                        {p.kegiatan_count}
                                                    </td>
                                                </tr>

                                                {/* Expanded Details */}
                                                {expandedRows.has(
                                                    p.petugas_id,
                                                ) && (
                                                    <tr>
                                                        <td
                                                            colSpan={8}
                                                            className="bg-muted/50 p-0"
                                                        >
                                                            <div className="space-y-4 p-4">
                                                                <h4 className="text-sm font-semibold">
                                                                    Detail
                                                                    Kegiatan &
                                                                    Alokasi
                                                                </h4>
                                                                {p.kegiatan_details.map(
                                                                    (
                                                                        kegiatan,
                                                                        idx,
                                                                    ) => (
                                                                        <div
                                                                            key={
                                                                                idx
                                                                            }
                                                                            className="space-y-3 rounded-lg border bg-background p-4"
                                                                        >
                                                                            <div className="flex items-start justify-between">
                                                                                <div className="space-y-1">
                                                                                    <h5 className="font-medium">
                                                                                        {
                                                                                            kegiatan.nama_kegiatan
                                                                                        }
                                                                                    </h5>
                                                                                    <div className="flex gap-2">
                                                                                        <Badge
                                                                                            variant="outline"
                                                                                            className="text-xs"
                                                                                        >
                                                                                            Jenis
                                                                                            Kegiatan
                                                                                        </Badge>
                                                                                        <span className="text-sm text-muted-foreground">
                                                                                            {kegiatan.jenis_kegiatan ===
                                                                                            'sensus'
                                                                                                ? 'Sensus'
                                                                                                : 'Survei'}
                                                                                        </span>
                                                                                    </div>
                                                                                    <div className="flex gap-2">
                                                                                        <Badge
                                                                                            variant="outline"
                                                                                            className="text-xs"
                                                                                        >
                                                                                            Penugasan
                                                                                        </Badge>
                                                                                        {kegiatan.alokasi.map(
                                                                                            (
                                                                                                alokasi,
                                                                                                alokasiIdx,
                                                                                            ) => (
                                                                                                <span
                                                                                                    key={`peran-${idx}-${alokasiIdx}`}
                                                                                                    className="text-sm text-muted-foreground"
                                                                                                >
                                                                                                    {
                                                                                                        alokasi.peran
                                                                                                    }
                                                                                                </span>
                                                                                            ),
                                                                                        )}
                                                                                    </div>
                                                                                    <div className="flex gap-2">
                                                                                        <Badge
                                                                                            variant="outline"
                                                                                            className="text-xs"
                                                                                        >
                                                                                            Total
                                                                                            Alokasi
                                                                                            Honor
                                                                                        </Badge>
                                                                                        <span className="text-sm text-muted-foreground">
                                                                                            Total:{' '}
                                                                                            {formatCurrency(
                                                                                                kegiatan.total_honor,
                                                                                            )}
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            {/* Alokasi Table */}
                                                                            <div className="overflow-hidden rounded-md border">
                                                                                <table className="w-full text-sm">
                                                                                    <thead className="bg-muted/50">
                                                                                        <tr>
                                                                                            <th className="p-2 text-left font-medium">
                                                                                                Tahapan
                                                                                            </th>
                                                                                            <th className="p-2 text-center font-medium">
                                                                                                Jumlah
                                                                                                Satuan
                                                                                            </th>
                                                                                            <th className="p-2 text-center font-medium">
                                                                                                Jumlah
                                                                                                Satuan
                                                                                                Dibayarkan
                                                                                            </th>
                                                                                            <th className="p-2 text-right font-medium">
                                                                                                Total
                                                                                                Honor
                                                                                            </th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        {kegiatan.alokasi.map(
                                                                                            (
                                                                                                alokasi,
                                                                                                alokasiIdx,
                                                                                            ) =>
                                                                                                alokasi.jumlah_satuan_listing !==
                                                                                                null ? (
                                                                                                    <>
                                                                                                        {alokasi.jumlah_satuan_listing >
                                                                                                            0 && (
                                                                                                            <tr
                                                                                                                key={`${alokasiIdx}-listing`}
                                                                                                                className="border-t"
                                                                                                            >
                                                                                                                <td className="p-2">
                                                                                                                    Listing
                                                                                                                </td>
                                                                                                                <td className="p-2 text-center">
                                                                                                                    {
                                                                                                                        alokasi.jumlah_satuan_listing
                                                                                                                    }
                                                                                                                </td>
                                                                                                                <td className="p-2 text-center">
                                                                                                                    {alokasi.jumlah_satuan_listing_dibayarkan ??
                                                                                                                        alokasi.jumlah_satuan_listing ??
                                                                                                                        0}
                                                                                                                </td>
                                                                                                                <td className="p-2 text-right font-medium">
                                                                                                                    {formatCurrency(
                                                                                                                        alokasi.total_honor_listing,
                                                                                                                    )}
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        )}
                                                                                                        {alokasi.jumlah_satuan >
                                                                                                            0 && (
                                                                                                            <tr
                                                                                                                key={`${alokasiIdx}-pencacahan`}
                                                                                                                className="border-t"
                                                                                                            >
                                                                                                                <td className="p-2">
                                                                                                                    Pencacahan
                                                                                                                </td>
                                                                                                                <td className="p-2 text-center">
                                                                                                                    {
                                                                                                                        alokasi.jumlah_satuan
                                                                                                                    }
                                                                                                                </td>
                                                                                                                <td className="p-2 text-center">
                                                                                                                    {alokasi.jumlah_satuan_dibayarkan ??
                                                                                                                        alokasi.jumlah_satuan ??
                                                                                                                        0}
                                                                                                                </td>
                                                                                                                <td className="p-2 text-right font-medium">
                                                                                                                    {formatCurrency(
                                                                                                                        alokasi.total_honor,
                                                                                                                    )}
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        )}
                                                                                                    </>
                                                                                                ) : (
                                                                                                    <tr
                                                                                                        key={`${alokasiIdx}-pcah`}
                                                                                                        className="border-t"
                                                                                                    >
                                                                                                        <td className="p-2">
                                                                                                            Pencacahan
                                                                                                        </td>
                                                                                                        <td className="p-2 text-center">
                                                                                                            {
                                                                                                                alokasi.jumlah_satuan
                                                                                                            }
                                                                                                        </td>
                                                                                                        <td className="p-2 text-center">
                                                                                                            {alokasi.jumlah_satuan_dibayarkan ??
                                                                                                                alokasi.jumlah_satuan ??
                                                                                                                0}
                                                                                                        </td>
                                                                                                        <td className="p-2 text-right font-medium">
                                                                                                            {formatCurrency(
                                                                                                                alokasi.total_honor,
                                                                                                            )}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                ),
                                                                                        )}
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    ),
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {filteredPetugas.length > 0 && (
                                <div className="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Menampilkan{' '}
                                        {(effectiveCurrentPage - 1) * pageSize +
                                            1}
                                        -
                                        {Math.min(
                                            effectiveCurrentPage * pageSize,
                                            filteredPetugas.length,
                                        )}{' '}
                                        dari {filteredPetugas.length} petugas
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
                                            disabled={effectiveCurrentPage <= 1}
                                        >
                                            Sebelumnya
                                        </Button>
                                        <span className="text-sm text-muted-foreground">
                                            Halaman {effectiveCurrentPage} dari{' '}
                                            {totalPages}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setCurrentPage((page) =>
                                                    Math.min(
                                                        totalPages,
                                                        page + 1,
                                                    ),
                                                )
                                            }
                                            disabled={
                                                effectiveCurrentPage >=
                                                totalPages
                                            }
                                        >
                                            Berikutnya
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
