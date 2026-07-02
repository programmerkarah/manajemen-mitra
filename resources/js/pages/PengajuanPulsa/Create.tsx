import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Check,
    ChevronLeft,
    ChevronRight,
    Download,
    FileUp,
    Info,
    Loader2,
    Send,
    Upload,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengajuan Pulsa', href: '/pengajuan-pulsa' },
    { title: 'Ajukan', href: '/pengajuan-pulsa/create' },
];

interface KegiatanItem {
    id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    metode_pendataan_pencacahan: 'PAPI' | 'CAPI' | null;
    metode_pendataan_listing: 'PAPI' | 'CAPI' | null;
    metode_pelatihan:
        | 'daring'
        | 'luring'
        | 'hybrid'
        | 'tidak_ada_pelatihan'
        | null;
    bulan_pelatihan: number | null;
    has_listing_updating: boolean;
}

interface PetugasItem {
    id: number;
    nama: string;
    peran: string;
}

interface Props {
    eligibleKegiatan: KegiatanItem[];
    /** Petugas eligible for pendataan pulsa: sourced from current bulan allocations */
    petugasPerKegiatan: Record<number, PetugasItem[]>;
    /** Petugas eligible for pelatihan pulsa: sourced from bulan_pelatihan+1 allocations (or bulan_pelatihan if kegiatan starts that month) */
    petugasPerKegiatanPelatihan: Record<number, PetugasItem[]>;
    /** Total existing submissions from kegiatan managed by this ketua tim */
    existingTotals: Record<number, number>;
    /** Total existing submissions across ALL kegiatan (global) */
    allExistingTotals: Record<number, number>;
    /** key = "${kegiatan_id}_${petugas_id}_${jenis_pulsa}" → nominal already submitted */
    existingPerKegiatan: Record<string, number>;
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

const MAX_PULSA_PER_PETUGAS = 100000;

/** Format raw number to Indonesian thousand-separated string, e.g. 35000 → "35.000" */
const formatThousands = (value: number): string =>
    value > 0 ? value.toLocaleString('id-ID') : '';

/** Parse Indonesian thousand-separated string back to raw number */
const parseRaw = (str: string): number => {
    const num = parseInt(str.replace(/\./g, '').replace(/[^\d]/g, ''), 10);
    return isNaN(num) ? 0 : num;
};

const PERAN_LABELS: Record<string, string> = {
    pml: 'Petugas Pemeriksaan',
    pcl_ppl: 'Petugas Pendataan',
    pcl: 'Petugas Pendataan',
    ppl: 'Petugas Pendataan',
    lapangan: 'Petugas Pendataan',
};

const peranLabel = (peran: string): string =>
    PERAN_LABELS[peran] ?? 'Petugas Pendataan';

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

/** State for nominal inputs: key = `${kegiatanId}__${petugasId}__${jenisPulsa}` */
type NominalMap = Record<string, number>;

interface PengajuanPulsaImportRow {
    bulan: string;
    tahun: number;
    kegiatan_id: number;
    petugas_id: number;
    jenis_pulsa: 'pelatihan' | 'pendataan';
    nominal: number;
    kegiatan_kode?: string | null;
    kegiatan_nama?: string | null;
    petugas_nama?: string | null;
}

interface PengajuanPulsaImportSummary {
    total_rows: number;
    preview_rows: number;
    skipped_rows: number;
    error_count: number;
}

export default function PengajuanPulsaCreate({
    eligibleKegiatan,
    petugasPerKegiatan,
    petugasPerKegiatanPelatihan,
    existingTotals,
    allExistingTotals,
    existingPerKegiatan,
    filters,
}: Props) {
    const [bulan, setBulan] = useState(filters.bulan);
    const tahun = filters.tahun;
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 6;
    const [catatan, setCatatan] = useState('');
    const [nominals, setNominals] = useState<NominalMap>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [isImportProcessing, setIsImportProcessing] = useState(false);
    const [importPreviewRows, setImportPreviewRows] = useState<
        PengajuanPulsaImportRow[]
    >([]);
    const [importPreviewSummary, setImportPreviewSummary] =
        useState<PengajuanPulsaImportSummary | null>(null);
    const [importPreviewErrors, setImportPreviewErrors] = useState<string[]>(
        [],
    );

    const getNominal = useCallback(
        (
            kegiatanId: number,
            petugasId: number,
            jenisPulsa: 'pelatihan' | 'pendataan',
        ) => nominals[`${kegiatanId}__${petugasId}__${jenisPulsa}`] ?? 0,
        [nominals],
    );

    const setNominal = useCallback(
        (
            kegiatanId: number,
            petugasId: number,
            jenisPulsa: 'pelatihan' | 'pendataan',
            value: number,
        ) => {
            setNominals((prev) => ({
                ...prev,
                [`${kegiatanId}__${petugasId}__${jenisPulsa}`]: value,
            }));
        },
        [],
    );

    /** Compute new total per petugas from the current form state */
    const newTotalPerPetugas = useMemo<Record<number, number>>(() => {
        const totals: Record<number, number> = {};
        for (const key of Object.keys(nominals)) {
            const [, petugasIdStr] = key.split('__');
            const petugasId = Number(petugasIdStr);
            totals[petugasId] = (totals[petugasId] ?? 0) + (nominals[key] ?? 0);
        }
        return totals;
    }, [nominals]);

    const totalForPetugas = useCallback(
        (petugasId: number) => {
            const existing = allExistingTotals[petugasId] ?? 0;
            const newTotal = newTotalPerPetugas[petugasId] ?? 0;
            return existing + newTotal;
        },
        [allExistingTotals, newTotalPerPetugas],
    );

    const overLimitPetugasIds = useMemo(() => {
        return Object.keys(newTotalPerPetugas).filter(
            (petugasId) =>
                totalForPetugas(Number(petugasId)) > MAX_PULSA_PER_PETUGAS,
        );
    }, [newTotalPerPetugas, totalForPetugas]);

    const hasNominalFormatErrors = useMemo(
        () =>
            Object.values(nominals).some(
                (value) => value > 0 && value % 1000 !== 0,
            ),
        [nominals],
    );

    const isOverLimit = useCallback(
        (petugasId: number) =>
            totalForPetugas(petugasId) > MAX_PULSA_PER_PETUGAS,
        [totalForPetugas],
    );

    /**
     * Invert petugasPerKegiatan + petugasPerKegiatanPelatihan → list of unique petugas
     * each with their kegiatan list, sorted by petugas name.
     * A petugas card appears if they are eligible for either pendataan or pelatihan pulsa.
     */
    const petugasWithKegiatan = useMemo(() => {
        const map = new Map<
            number,
            { petugas: PetugasItem; kegiatan: KegiatanItem[] }
        >();

        const addPetugas = (p: PetugasItem, kegiatan: KegiatanItem) => {
            if (!map.has(p.id)) {
                map.set(p.id, { petugas: p, kegiatan: [] });
            }
            if (!map.get(p.id)!.kegiatan.some((k) => k.id === kegiatan.id)) {
                map.get(p.id)!.kegiatan.push(kegiatan);
            }
        };

        for (const kegiatan of eligibleKegiatan) {
            for (const p of petugasPerKegiatan[kegiatan.id] ?? []) {
                addPetugas(p, kegiatan);
            }
            for (const p of petugasPerKegiatanPelatihan[kegiatan.id] ?? []) {
                addPetugas(p, kegiatan);
            }
        }
        return Array.from(map.values()).sort((a, b) =>
            a.petugas.nama.localeCompare(b.petugas.nama, 'id'),
        );
    }, [eligibleKegiatan, petugasPerKegiatan, petugasPerKegiatanPelatihan]);

    const totalPages = Math.max(
        1,
        Math.ceil(petugasWithKegiatan.length / perPage),
    );

    const paginatedPetugasWithKegiatan = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return petugasWithKegiatan.slice(startIndex, startIndex + perPage);
    }, [currentPage, petugasWithKegiatan]);

    const pageStart =
        petugasWithKegiatan.length === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const pageEnd = Math.min(currentPage * perPage, petugasWithKegiatan.length);

    useEffect(() => {
        setCurrentPage(1);
    }, [bulan]);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(totalPages);
        }
    }, [currentPage, totalPages]);

    const kegiatanById = useMemo(
        () =>
            new Map(
                eligibleKegiatan.map((kegiatan) => [kegiatan.id, kegiatan]),
            ),
        [eligibleKegiatan],
    );

    const petugasById = useMemo(() => {
        const map = new Map<number, PetugasItem>();

        for (const { petugas } of petugasWithKegiatan) {
            map.set(petugas.id, petugas);
        }

        return map;
    }, [petugasWithKegiatan]);

    const handleFilterChange = (newBulan: string) => {
        router.get(
            '/pengajuan-pulsa/create',
            { bulan: newBulan },
            { preserveState: false },
        );
    };

    const handleDownloadTemplate = () => {
        window.location.href = `/pengajuan-pulsa/template?bulan=${bulan}&tahun=${tahun}`;
    };

    const handlePreviewImport = async () => {
        if (!importFile) {
            setErrors({
                general:
                    'Pilih file Excel terlebih dahulu sebelum melakukan preview import.',
            });

            return;
        }

        setIsImportProcessing(true);
        setErrors({});
        setImportPreviewErrors([]);

        try {
            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';

            const formData = new FormData();
            formData.append('file', importFile);
            formData.append('bulan', bulan);
            formData.append('tahun', tahun);

            const response = await fetch('/pengajuan-pulsa/import-preview', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const payload = await response.json();

            if (!response.ok) {
                setErrors({
                    general:
                        payload?.message ??
                        'Gagal memproses file import pengajuan pulsa.',
                });

                return;
            }

            setImportPreviewRows(payload.rows ?? []);
            setImportPreviewSummary(payload.summary ?? null);
            setImportPreviewErrors(payload.errors ?? []);
            setIsImportDialogOpen(true);
        } catch (error) {
            setErrors({
                general:
                    error instanceof Error
                        ? error.message
                        : 'Gagal memproses file import pengajuan pulsa.',
            });
        } finally {
            setIsImportProcessing(false);
        }
    };

    const applyImportedRows = () => {
        if (importPreviewRows.length === 0) {
            return;
        }

        setNominals((previousNominals) => {
            const nextNominals = { ...previousNominals };

            for (const row of importPreviewRows) {
                if (
                    !kegiatanById.has(row.kegiatan_id) ||
                    !petugasById.has(row.petugas_id)
                ) {
                    continue;
                }

                nextNominals[
                    `${row.kegiatan_id}__${row.petugas_id}__${row.jenis_pulsa}`
                ] = row.nominal;
            }

            return nextNominals;
        });

        setIsImportDialogOpen(false);
    };

    const handleSubmit = () => {
        if (hasNominalFormatErrors) {
            setErrors({
                general:
                    'Semua nominal pulsa harus merupakan kelipatan Rp1.000.',
            });
            return;
        }

        if (overLimitPetugasIds.length > 0) {
            setErrors({
                general: `Beberapa petugas melebihi batas pulsa Rp100.000 per bulan. Sesuaikan nominal sebelum mengirim.`,
            });
            return;
        }

        const items: Array<{
            kegiatan_id: number;
            petugas_id: number;
            jenis_pulsa: string;
            nominal: number;
        }> = [];

        for (const key of Object.keys(nominals)) {
            const nominal = nominals[key] ?? 0;
            if (nominal <= 0) continue;
            const [kegiatanIdStr, petugasIdStr, jenisPulsa] = key.split('__');
            items.push({
                kegiatan_id: Number(kegiatanIdStr),
                petugas_id: Number(petugasIdStr),
                jenis_pulsa: jenisPulsa,
                nominal,
            });
        }

        if (items.length === 0) {
            setErrors({
                general: 'Isi minimal satu nominal pulsa sebelum mengirim.',
            });
            return;
        }

        setErrors({});
        setIsSubmitting(true);

        router.post(
            '/pengajuan-pulsa',
            { bulan, tahun, catatan, items },
            {
                onError: (errs) => {
                    setErrors(errs as Record<string, string>);
                    setIsSubmitting(false);
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const hasEligibleKegiatan = petugasWithKegiatan.length > 0;
    const isSubmitDisabled =
        isSubmitting ||
        hasNominalFormatErrors ||
        overLimitPetugasIds.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajukan Pulsa" />
            <div className="space-y-4">
                <PageHeader
                    title="Ajukan Pulsa"
                    description="Pengajuan pulsa petugas untuk pelatihan dan/atau pendataan. Pastikan nominal yang diajukan sudah benar sebelum mengirim."
                >
                    <Button variant="outline" asChild className="gap-2">
                        <Link href="/pengajuan-pulsa">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                Periode
                            </h3>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Filter bulan aktif, lalu gunakan template import
                                untuk mengisi nominal dengan lebih cepat.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-end gap-3">
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
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="gap-2"
                                    onClick={handleDownloadTemplate}
                                >
                                    <Download className="h-4 w-4" />
                                    Template
                                </Button>
                                <Button
                                    type="button"
                                    className="gap-2"
                                    onClick={() => setIsImportDialogOpen(true)}
                                >
                                    <FileUp className="h-4 w-4" />
                                    Import Excel
                                </Button>
                            </div>
                        </div>
                    </div>
                </ContentCard>

                {!hasEligibleKegiatan ? (
                    <ContentCard>
                        <div className="flex flex-col items-center gap-2 py-8 text-center">
                            <AlertTriangle className="h-10 w-10 text-amber-500" />
                            <p className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                Tidak ada kegiatan dengan alokasi petugas untuk
                                periode ini
                            </p>
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                Belum ada petugas non-organik yang dialokasikan
                                pada {BULAN_LABELS[bulan]} {tahun}.
                            </p>
                        </div>
                    </ContentCard>
                ) : (
                    <>
                        {errors.general && (
                            <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                                {errors.general}
                            </div>
                        )}

                        <div className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-neutral-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:bg-neutral-900/40">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Menampilkan {pageStart}-{pageEnd} dari{' '}
                                {petugasWithKegiatan.length} petugas
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
                                    className="gap-1.5"
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
                                    className="gap-1.5"
                                >
                                    Berikutnya
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        {/* Per-petugas cards */}
                        {paginatedPetugasWithKegiatan.map(
                            ({ petugas, kegiatan: kegiatanList }) => {
                                const total = totalForPetugas(petugas.id);
                                const over = isOverLimit(petugas.id);
                                const petugasError =
                                    errors[`petugas_${petugas.id}`];
                                const currentActivityTotal =
                                    existingTotals[petugas.id] ?? 0;
                                const globalExistingTotal =
                                    allExistingTotals[petugas.id] ?? 0;
                                const externalTotal =
                                    globalExistingTotal - currentActivityTotal;

                                return (
                                    <ContentCard
                                        key={petugas.id}
                                        padding="none"
                                    >
                                        {/* Card header */}
                                        <div
                                            className={`flex items-center justify-between px-6 py-4 ${over ? 'bg-red-50 dark:bg-red-900/10' : ''}`}
                                        >
                                            <div>
                                                <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                                    {petugas.nama}
                                                </h3>
                                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {peranLabel(petugas.peran)}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <div
                                                    className={`text-sm font-semibold ${over ? 'text-red-600 dark:text-red-400' : 'text-neutral-900 dark:text-neutral-100'}`}
                                                >
                                                    Total:{' '}
                                                    {formatCurrency(total)}
                                                </div>
                                                {over && (
                                                    <div className="flex items-center justify-end gap-1 text-xs text-red-600 dark:text-red-400">
                                                        <AlertTriangle className="h-3 w-3" />
                                                        Melebihi batas Rp100.000
                                                    </div>
                                                )}
                                                {currentActivityTotal > 0 && (
                                                    <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                        Sudah diajukan (kegiatan
                                                        ini):{' '}
                                                        {formatCurrency(
                                                            currentActivityTotal,
                                                        )}
                                                    </div>
                                                )}
                                                {externalTotal > 0 && (
                                                    <div className="mt-0.5 flex items-center justify-end gap-1 text-xs text-amber-600 dark:text-amber-400">
                                                        <Info className="h-3 w-3 shrink-0" />
                                                        +
                                                        {formatCurrency(
                                                            externalTotal,
                                                        )}{' '}
                                                        dari kegiatan lain
                                                        (total:{' '}
                                                        {formatCurrency(
                                                            globalExistingTotal,
                                                        )}
                                                        )
                                                    </div>
                                                )}
                                                {petugasError && (
                                                    <div className="mt-1 text-xs text-red-600 dark:text-red-400">
                                                        {petugasError}
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Kegiatan table */}
                                        <div className="overflow-x-auto border-t border-neutral-200 dark:border-neutral-800">
                                            <table className="w-full table-fixed">
                                                <colgroup>
                                                    <col className="w-[44%]" />
                                                    <col className="w-[28%]" />
                                                    <col className="w-[28%]" />
                                                </colgroup>
                                                <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                                    <tr>
                                                        <th className="px-4 py-3 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                            Kegiatan
                                                        </th>
                                                        <th className="px-4 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                            Pulsa Pelatihan (Rp)
                                                        </th>
                                                        <th className="px-4 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                            Pulsa Pendataan (Rp)
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                                    {kegiatanList.map(
                                                        (kegiatan) => {
                                                            const canPendataan =
                                                                kegiatan.metode_pendataan_pencacahan ===
                                                                    'CAPI' &&
                                                                (
                                                                    petugasPerKegiatan[
                                                                        kegiatan
                                                                            .id
                                                                    ] ?? []
                                                                ).some(
                                                                    (p) =>
                                                                        p.id ===
                                                                        petugas.id,
                                                                );
                                                            const canPelatihan =
                                                                (
                                                                    petugasPerKegiatanPelatihan[
                                                                        kegiatan
                                                                            .id
                                                                    ] ?? []
                                                                ).some(
                                                                    (p) =>
                                                                        p.id ===
                                                                        petugas.id,
                                                                );
                                                            const submittedPelatihan =
                                                                (existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pelatihan`
                                                                ] ?? 0) > 0;
                                                            const submittedPendataan =
                                                                (existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pendataan`
                                                                ] ?? 0) > 0;
                                                            const existingPelatihan =
                                                                existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pelatihan`
                                                                ] ?? 0;
                                                            const existingPendataanVal =
                                                                existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pendataan`
                                                                ] ?? 0;
                                                            const isPapi =
                                                                kegiatan.metode_pendataan_pencacahan ===
                                                                'PAPI';

                                                            return (
                                                                <tr
                                                                    key={
                                                                        kegiatan.id
                                                                    }
                                                                    className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                                                >
                                                                    <td className="px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100">
                                                                        <div className="flex items-center gap-2">
                                                                            <span
                                                                                className={`inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-xs font-medium ${
                                                                                    isPapi
                                                                                        ? 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                                                                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                                                }`}
                                                                            >
                                                                                {isPapi
                                                                                    ? 'PAPI'
                                                                                    : 'CAPI'}
                                                                            </span>

                                                                            <span className="font-medium">
                                                                                {
                                                                                    kegiatan.nama_kegiatan
                                                                                }
                                                                            </span>
                                                                        </div>
                                                                    </td>

                                                                    {/* Pulsa Pelatihan — only on configured bulan_pelatihan */}
                                                                    <td className="px-4 py-3">
                                                                        {submittedPelatihan ? (
                                                                            <div className="flex h-9 w-full items-center justify-end rounded-md border border-neutral-200 bg-neutral-100 px-3 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400">
                                                                                {formatCurrency(
                                                                                    existingPelatihan,
                                                                                )}
                                                                            </div>
                                                                        ) : !canPelatihan ? (
                                                                            <div className="flex h-9 w-full items-center justify-center rounded-md border border-neutral-200/50 bg-neutral-50 text-xs text-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-600">
                                                                                —
                                                                            </div>
                                                                        ) : (
                                                                            (() => {
                                                                                const raw =
                                                                                    getNominal(
                                                                                        kegiatan.id,
                                                                                        petugas.id,
                                                                                        'pelatihan',
                                                                                    );
                                                                                const invalid =
                                                                                    raw >
                                                                                        0 &&
                                                                                    (raw %
                                                                                        1000 !==
                                                                                        0 ||
                                                                                        totalForPetugas(
                                                                                            petugas.id,
                                                                                        ) >
                                                                                            MAX_PULSA_PER_PETUGAS);
                                                                                return (
                                                                                    <div className="space-y-1">
                                                                                        <Input
                                                                                            type="text"
                                                                                            inputMode="numeric"
                                                                                            value={formatThousands(
                                                                                                raw,
                                                                                            )}
                                                                                            onChange={(
                                                                                                e,
                                                                                            ) =>
                                                                                                setNominal(
                                                                                                    kegiatan.id,
                                                                                                    petugas.id,
                                                                                                    'pelatihan',
                                                                                                    parseRaw(
                                                                                                        e
                                                                                                            .target
                                                                                                            .value,
                                                                                                    ),
                                                                                                )
                                                                                            }
                                                                                            className={`w-full text-right ${invalid ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                                                                            placeholder="0"
                                                                                        />
                                                                                        {invalid && (
                                                                                            <p className="text-xs text-red-500">
                                                                                                Kelipatan
                                                                                                1.000
                                                                                                dan
                                                                                                total
                                                                                                per
                                                                                                petugas
                                                                                                maks
                                                                                                Rp100.000
                                                                                            </p>
                                                                                        )}
                                                                                    </div>
                                                                                );
                                                                            })()
                                                                        )}
                                                                    </td>

                                                                    {/* Pulsa Pendataan — locked for PAPI */}
                                                                    <td className="px-4 py-3">
                                                                        {submittedPendataan ? (
                                                                            <div className="flex h-9 w-full items-center justify-end rounded-md border border-neutral-200 bg-neutral-100 px-3 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400">
                                                                                {formatCurrency(
                                                                                    existingPendataanVal,
                                                                                )}
                                                                            </div>
                                                                        ) : !canPendataan ? (
                                                                            <div className="flex h-9 w-full items-center justify-center rounded-md border border-neutral-200/50 bg-neutral-50 text-xs text-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-600">
                                                                                —
                                                                            </div>
                                                                        ) : (
                                                                            (() => {
                                                                                const raw =
                                                                                    getNominal(
                                                                                        kegiatan.id,
                                                                                        petugas.id,
                                                                                        'pendataan',
                                                                                    );
                                                                                const invalid =
                                                                                    raw >
                                                                                        0 &&
                                                                                    (raw %
                                                                                        1000 !==
                                                                                        0 ||
                                                                                        totalForPetugas(
                                                                                            petugas.id,
                                                                                        ) >
                                                                                            MAX_PULSA_PER_PETUGAS);
                                                                                return (
                                                                                    <div className="space-y-1">
                                                                                        <Input
                                                                                            type="text"
                                                                                            inputMode="numeric"
                                                                                            value={formatThousands(
                                                                                                raw,
                                                                                            )}
                                                                                            onChange={(
                                                                                                e,
                                                                                            ) =>
                                                                                                setNominal(
                                                                                                    kegiatan.id,
                                                                                                    petugas.id,
                                                                                                    'pendataan',
                                                                                                    parseRaw(
                                                                                                        e
                                                                                                            .target
                                                                                                            .value,
                                                                                                    ),
                                                                                                )
                                                                                            }
                                                                                            className={`w-full text-right ${invalid ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                                                                            placeholder="0"
                                                                                        />
                                                                                        {invalid && (
                                                                                            <p className="text-xs text-red-500">
                                                                                                Kelipatan
                                                                                                1.000
                                                                                                dan
                                                                                                total
                                                                                                per
                                                                                                petugas
                                                                                                maks
                                                                                                Rp100.000
                                                                                            </p>
                                                                                        )}
                                                                                    </div>
                                                                                );
                                                                            })()
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            );
                                                        },
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </ContentCard>
                                );
                            },
                        )}

                        {/* Catatan */}
                        <ContentCard>
                            <h3 className="mb-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                Catatan (Opsional)
                            </h3>
                            <Textarea
                                value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                                placeholder="Catatan tambahan untuk pengajuan pulsa ini..."
                                rows={3}
                                className="max-w-xl"
                            />
                        </ContentCard>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3">
                            <Button variant="outline" asChild>
                                <Link href="/pengajuan-pulsa">Batal</Link>
                            </Button>
                            <Button
                                onClick={handleSubmit}
                                disabled={isSubmitDisabled}
                                className="gap-2"
                            >
                                <Send className="h-4 w-4" />
                                {isSubmitting
                                    ? 'Mengirim...'
                                    : 'Kirim Pengajuan'}
                            </Button>
                        </div>
                    </>
                )}

                <Dialog
                    open={isImportDialogOpen}
                    onOpenChange={setIsImportDialogOpen}
                >
                    <DialogContent className="max-h-[90vh] overflow-hidden sm:max-w-5xl">
                        <DialogHeader>
                            <DialogTitle>
                                Import Excel Pengajuan Pulsa
                            </DialogTitle>
                            <DialogDescription>
                                Unggah template Excel untuk melihat preview data
                                sebelum nominal diterapkan ke form.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/40">
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            Pilih file Excel
                                        </p>
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Format yang didukung: `.xlsx`,
                                            `.xls`, atau `.csv`.
                                        </p>
                                    </div>
                                    <Input
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={(event) =>
                                            setImportFile(
                                                event.target.files?.[0] ?? null,
                                            )
                                        }
                                        className="max-w-md"
                                    />
                                </div>
                            </div>

                            {importPreviewSummary && (
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                                    <div className="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Total baris
                                        </p>
                                        <p className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                            {importPreviewSummary.total_rows}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Baris preview
                                        </p>
                                        <p className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                            {importPreviewSummary.preview_rows}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Dilewati
                                        </p>
                                        <p className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                            {importPreviewSummary.skipped_rows}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Error
                                        </p>
                                        <p className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                            {importPreviewSummary.error_count}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {importPreviewErrors.length > 0 && (
                                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                                    <p className="mb-2 font-semibold">
                                        Catatan import
                                    </p>
                                    <ul className="space-y-1">
                                        {importPreviewErrors.map(
                                            (message, index) => (
                                                <li key={`${message}-${index}`}>
                                                    {message}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}

                            <div className="max-h-[38vh] overflow-auto rounded-xl border border-neutral-200 dark:border-neutral-800">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-neutral-50 dark:bg-neutral-900">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-neutral-900 dark:text-neutral-100">
                                                Kegiatan
                                            </th>
                                            <th className="px-4 py-3 text-left font-semibold text-neutral-900 dark:text-neutral-100">
                                                Petugas
                                            </th>
                                            <th className="px-4 py-3 text-left font-semibold text-neutral-900 dark:text-neutral-100">
                                                Jenis
                                            </th>
                                            <th className="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">
                                                Nominal
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                        {importPreviewRows.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={4}
                                                    className="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400"
                                                >
                                                    Belum ada data preview. Klik
                                                    Preview Import setelah
                                                    memilih file.
                                                </td>
                                            </tr>
                                        ) : (
                                            importPreviewRows.map((row) => {
                                                const kegiatan =
                                                    kegiatanById.get(
                                                        row.kegiatan_id,
                                                    );
                                                const petugas = petugasById.get(
                                                    row.petugas_id,
                                                );

                                                return (
                                                    <tr
                                                        key={`${row.kegiatan_id}_${row.petugas_id}_${row.jenis_pulsa}`}
                                                    >
                                                        <td className="px-4 py-3 text-neutral-900 dark:text-neutral-100">
                                                            <div className="font-medium">
                                                                {kegiatan?.nama_kegiatan ??
                                                                    row.kegiatan_nama ??
                                                                    `Kegiatan #${row.kegiatan_id}`}
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3 text-neutral-900 dark:text-neutral-100">
                                                            {petugas?.nama ??
                                                                row.petugas_nama ??
                                                                `Petugas #${row.petugas_id}`}
                                                        </td>
                                                        <td className="px-4 py-3 text-neutral-700 dark:text-neutral-300">
                                                            {row.jenis_pulsa ===
                                                            'pelatihan'
                                                                ? 'Pelatihan'
                                                                : 'Pendataan'}
                                                        </td>
                                                        <td className="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">
                                                            {formatCurrency(
                                                                row.nominal,
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsImportDialogOpen(false)}
                            >
                                Tutup
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="gap-2"
                                onClick={handlePreviewImport}
                                disabled={isImportProcessing}
                            >
                                {isImportProcessing ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <Upload className="h-4 w-4" />
                                )}
                                Preview Import
                            </Button>
                            <Button
                                type="button"
                                className="gap-2"
                                onClick={applyImportedRows}
                                disabled={importPreviewRows.length === 0}
                            >
                                <Check className="h-4 w-4" />
                                Terapkan ke Form
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}

PengajuanPulsaCreate.layout = (page: React.ReactNode) => page;
