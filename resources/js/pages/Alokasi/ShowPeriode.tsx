import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Kegiatan, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Edit,
    FileText,
    History,
    Save,
    Users,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface Petugas {
    id: number;
    nama: string;
    jenis_petugas: string;
}

interface FrameSampelOption {
    id: number;
    kegiatan_frame_sampel_id?: number;
    tahapan: 'listing' | 'pencacahan';
    nama_target: string | null;
    sample_role: string | null;
    is_active: boolean;
    nama_frame: string | null;
    kode_kecamatan: string | null;
    kode_desa: string | null;
    kode_sls: string | null;
    kode_sub_sls: string | null;
    kode_segmen: string | null;
    nks?: string | null;
    nama_usaha_penggilingan?: string | null;
    target_unit_total?: number;
    target_unit_sampel?: Record<string, number | string> | null;
    identitas_tambahan?: Record<string, string | number | null> | null;
    metadata_items?: MetadataItem[];
}

interface RateHonorEntry {
    status_kepegawaian: string;
    jenis_penugasan: string;
    rate: number;
    rate_listing: number;
}

interface FrameMetadataColumn {
    code: string;
    label: string;
}

interface MetadataItem {
    code: string;
    label: string;
    codeValue: string;
    labelValue: string;
    displayMode: 'code_name' | 'code_only' | 'name_only';
}

interface FrameSampelDetail extends FrameSampelOption {
    frame_allocation_id: number;
    kegiatan_frame_sampel_id: number;
    is_non_response?: boolean;
}

interface AlokasiPetugas {
    id: number;
    petugas: Petugas;
    peran: string;
    jumlah_satuan: number;
    jumlah_satuan_listing?: number;
    jumlah_satuan_dibayarkan?: number;
    jumlah_satuan_listing_dibayarkan?: number;
    total_honor: number;
    total_honor_listing?: number;
    rate_pencacahan?: number;
    rate_listing?: number;
    catatan: string | null;
    non_response?: number | null;
    non_response_listing?: number | null;
    frame_sampel_details?: FrameSampelDetail[];
}

interface PeriodeAlokasi {
    id: number;
    kegiatan_id: number;
    bulan: string;
    tahun: number;
    jenis_kegiatan: 'sensus' | 'survei';
    tahapan?: 'both' | 'listing_only' | 'pencacahan_only' | null;
    tanggal_mulai?: string | null;
    tanggal_selesai?: string | null;
    tanggal_mulai_listing?: string | null;
    tanggal_selesai_listing?: string | null;
    status: 'draft' | 'dikirim' | 'perubahan' | 'disetujui';
    revision_number: number;
    parent_periode_id: number | null;
    submitted_at: string | null;
    submitted_by_name: string | null;
    kegiatan: Kegiatan & {
        has_listing_updating?: boolean;
        metode_sampling?: string | null;
        kegiatan_frame_sampel?: FrameSampelOption[];
        frame_metadata_columns?: FrameMetadataColumn[];
        rate_honors?: RateHonorEntry[];
    };
    alokasi_petugas: AlokasiPetugas[];
    total_estimasi: number;
    total_estimasi_pencacahan?: number;
    total_estimasi_listing?: number;
    jumlah_petugas: number;
}

interface PeriodeRevision {
    id: number;
    revision_number: number;
    status: string;
    submitted_at: string | null;
    submitted_by_name: string | null;
    alokasi_petugas: AlokasiPetugas[];
    total_estimasi: number;
    total_estimasi_pencacahan?: number;
    total_estimasi_listing?: number;
    jumlah_petugas: number;
}

interface FlattenedFrameRow {
    alokasi: AlokasiPetugas;
    frame: FrameSampelDetail | null;
}

interface GroupedFrameRow {
    key: string;
    frame: FrameSampelDetail | null;
    rows: FlattenedFrameRow[];
}

interface PetugasHonorSummaryRow {
    key: string;
    nama: string;
    peran: string;
    jenisPetugas: string;
    totalHonor: number;
}

interface ReplacementFrameOption {
    frame: FrameSampelOption;
    alreadySelected: boolean;
}

interface Props {
    periode: PeriodeAlokasi;
    revisions: PeriodeRevision[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '/alokasi' },
    { title: 'Detail Periode', href: '#' },
];

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

const peranLabels: Record<string, string> = {
    pcl_ppl: 'PCL',
    pml: 'PML',
    pengolahan: 'Petugas Pengolahan',
    pengawas_pengolahan: 'Pengawas Pengolahan',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    dikirim: 'Dikirim',
    perubahan: 'Revisi',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    dikirim:
        'bg-blue-100 text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300',
    perubahan:
        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function getDisplayedBebanTugas(alokasi: AlokasiPetugas): number {
    const paidPencacahan =
        alokasi.jumlah_satuan_dibayarkan ?? alokasi.jumlah_satuan ?? 0;
    const paidListing =
        alokasi.jumlah_satuan_listing_dibayarkan ??
        alokasi.jumlah_satuan_listing ??
        0;

    return paidPencacahan + paidListing;
}

function getDisplayedHonor(alokasi: AlokasiPetugas): number {
    return (alokasi.total_honor ?? 0) + (alokasi.total_honor_listing ?? 0);
}

function resolveRateHonorForAlokasi(
    alokasi: AlokasiPetugas,
    rateHonors: RateHonorEntry[] | undefined,
): RateHonorEntry | null {
    const petugasType =
        alokasi.petugas.jenis_petugas === 'organik' ? 'organik' : 'non_organik';
    const normalizedPeran = alokasi.peran.trim().toLowerCase();

    const matchedRate = rateHonors?.find(
        (rateHonor) =>
            rateHonor.status_kepegawaian === petugasType &&
            rateHonor.jenis_penugasan.trim().toLowerCase() === normalizedPeran,
    );

    if (matchedRate) {
        return matchedRate;
    }

    return (
        rateHonors?.find((rateHonor) => {
            const ratePetugasType = rateHonor.status_kepegawaian
                .trim()
                .toLowerCase();
            const ratePeran = rateHonor.jenis_penugasan.trim().toLowerCase();

            return (
                ratePetugasType === petugasType &&
                (ratePeran === normalizedPeran ||
                    ratePeran.replace(/[_\s]+/g, '') ===
                        normalizedPeran.replace(/[_\s]+/g, ''))
            );
        }) || null
    );
}

function formatFrameText(value: string | number | null | undefined): string {
    if (value === null || value === undefined || String(value).trim() === '') {
        return '-';
    }

    return String(value).trim();
}

function formatFrameTarget(frame: FrameSampelDetail): number {
    return Math.max(0, Number(frame.target_unit_total || 0));
}

function formatSampleRoleLabel(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (match) => match.toUpperCase());
}

function getPeranPriority(peran: string): number {
    const normalizedPeran = peran.trim().toLowerCase();

    if (normalizedPeran === 'pml') {
        return 0;
    }

    if (normalizedPeran === 'pcl_ppl' || normalizedPeran === 'ppl') {
        return 1;
    }

    return 2;
}

function getJenisPetugasLabel(jenisPetugas: string): string {
    return jenisPetugas === 'organik' ? 'Organik' : 'Mitra';
}

function getFrameMetadataSortValues(frame: FrameSampelDetail | null): string[] {
    if (!frame) {
        return [];
    }

    const metadataItems = frame.metadata_items || [];

    if (metadataItems.length > 0) {
        return metadataItems
            .map((item) => formatReplacementMetadataItem(item))
            .filter((value) => value !== '-');
    }

    return [
        resolveCodeLabelValue(
            frame,
            ['kdkec', 'kode_kecamatan'],
            ['kdkec_label', 'kode_kecamatan_label'],
        ),
        resolveCodeLabelValue(
            frame,
            ['kddes', 'kode_desa'],
            ['kddes_label', 'kode_desa_label'],
        ),
        resolveCodeLabelValue(frame, ['kode_sls'], ['kode_sls_label']),
        resolveCodeLabelValue(frame, ['kode_sub_sls'], ['kode_sub_sls_label']),
        resolveCodeLabelValue(frame, ['kode_segmen'], ['kode_segmen_label']),
    ].filter((value) => value !== '-');
}

function resolveCodeLabelValue(
    frame: FrameSampelDetail | null,
    codeKeys: string[],
    labelKeys: string[],
): string {
    if (!frame) {
        return '-';
    }

    const metadata = frame.identitas_tambahan || {};

    const directFrame = frame as unknown as Record<string, unknown>;

    const codeValue = codeKeys
        .map((key) => {
            const directEntry = Object.entries(directFrame).find(
                ([actualKey, value]) =>
                    actualKey.trim().toLowerCase() ===
                        key.trim().toLowerCase() &&
                    value !== null &&
                    value !== undefined &&
                    String(value).trim() !== '',
            );

            if (directEntry) {
                return String(directEntry[1]).trim();
            }

            const matchedEntry = Object.entries(metadata).find(
                ([actualKey, value]) =>
                    actualKey.trim().toLowerCase() ===
                        key.trim().toLowerCase() &&
                    value !== null &&
                    value !== undefined &&
                    String(value).trim() !== '',
            );

            return matchedEntry ? String(matchedEntry[1]).trim() : '';
        })
        .find((value) => value !== '');

    const labelValue = labelKeys
        .map((key) => {
            const directEntry = Object.entries(directFrame).find(
                ([actualKey, value]) =>
                    actualKey.trim().toLowerCase() ===
                        key.trim().toLowerCase() &&
                    value !== null &&
                    value !== undefined &&
                    String(value).trim() !== '',
            );

            if (directEntry) {
                return String(directEntry[1]).trim();
            }

            const matchedEntry = Object.entries(metadata).find(
                ([actualKey, value]) =>
                    actualKey.trim().toLowerCase() ===
                        key.trim().toLowerCase() &&
                    value !== null &&
                    value !== undefined &&
                    String(value).trim() !== '',
            );

            return matchedEntry ? String(matchedEntry[1]).trim() : '';
        })
        .find((value) => value !== '');

    const fallbackValue = resolveFrameTextFromKnownFields(frame, codeKeys);

    if (codeValue && labelValue) {
        return `[${codeValue}] ${labelValue}`;
    }

    if (codeValue) {
        return `{${codeValue}}`;
    }

    if (labelValue) {
        return labelValue;
    }

    return fallbackValue;
}

function resolveFrameTextFromKnownFields(
    frame: FrameSampelDetail,
    keys: string[],
): string {
    for (const key of keys) {
        const normalized = key.toLowerCase();

        if (normalized.includes('kdkec') || normalized.includes('kecamatan')) {
            return formatFrameText(frame.kode_kecamatan);
        }

        if (normalized.includes('kddes') || normalized.includes('desa')) {
            return formatFrameText(frame.kode_desa);
        }

        if (normalized.includes('nks')) {
            return formatFrameText(frame.nks);
        }

        if (normalized.includes('kode_sls') || normalized === 'sls') {
            return formatFrameText(frame.kode_sls);
        }

        if (normalized.includes('kode_segmen')) {
            return formatFrameText(frame.kode_segmen);
        }

        if (
            normalized.includes('nama_target') ||
            normalized.includes('nama_frame')
        ) {
            return formatFrameText(
                frame.nama_usaha_penggilingan ||
                    frame.nama_target ||
                    frame.nama_frame,
            );
        }
    }

    return '-';
}

function formatReplacementMetadataItem(item: MetadataItem): string {
    const codeValue = formatFrameText(item.codeValue);
    const labelValue = formatFrameText(item.labelValue);

    switch (item.displayMode) {
        case 'code_name':
            if (codeValue === '-' && labelValue === '-') {
                return '-';
            }

            if (codeValue === '-') {
                return labelValue;
            }

            if (labelValue === '-') {
                return codeValue;
            }

            return `[${codeValue}] ${labelValue}`;
        case 'code_only':
            return codeValue;
        case 'name_only':
            return labelValue;
        default:
            return '-';
    }
}

function resolveFrameMetadataItemDisplay(
    frame: FrameSampelDetail | null,
    code: string,
): string {
    if (!frame || !Array.isArray(frame.metadata_items)) {
        return '-';
    }

    const normalizedCode = code.trim().toLowerCase();
    const matchedItem = frame.metadata_items.find(
        (item) => item.code.trim().toLowerCase() === normalizedCode,
    );

    return matchedItem ? formatReplacementMetadataItem(matchedItem) : '-';
}

interface FrameDetailColumn {
    key: string;
    header: string;
    getValue: (frame: FrameSampelDetail | null) => string;
}

export default function ShowPeriode({ periode, revisions }: Props) {
    const bulanLabel = months[parseInt(periode.bulan) - 1];
    const exportMonitoringUrl = `/alokasi/periode/${periode.kegiatan.hashed_id}/${periode.tahun}/${String(periode.bulan).padStart(2, '0')}/export-monitoring-skgb`;
    const effectiveRevisionNumber = useMemo(() => {
        if (periode.revision_number > 0) {
            return periode.revision_number;
        }

        if (periode.parent_periode_id) {
            return Math.max(revisions.length, 1);
        }

        return 0;
    }, [periode.parent_periode_id, periode.revision_number, revisions.length]);

    const effectiveRevisionCount = useMemo(() => {
        if (effectiveRevisionNumber > 0) {
            return effectiveRevisionNumber;
        }

        return revisions.length;
    }, [effectiveRevisionNumber, revisions.length]);

    const hasListingValues = periode.alokasi_petugas.some((alokasi) => {
        const listingBeban =
            alokasi.jumlah_satuan_listing_dibayarkan ??
            alokasi.jumlah_satuan_listing ??
            0;

        return listingBeban > 0 || (alokasi.total_honor_listing ?? 0) > 0;
    });

    const hasPencacahanValues = periode.alokasi_petugas.some((alokasi) => {
        const pencacahanBeban =
            alokasi.jumlah_satuan_dibayarkan ?? alokasi.jumlah_satuan ?? 0;

        return pencacahanBeban > 0 || (alokasi.total_honor ?? 0) > 0;
    });

    const isListingOnly =
        periode.tahapan === 'listing_only' ||
        (periode.tahapan !== 'pencacahan_only' &&
            hasListingValues &&
            !hasPencacahanValues);
    const isPencacahanOnly =
        periode.tahapan === 'pencacahan_only' ||
        (periode.tahapan !== 'listing_only' &&
            hasPencacahanValues &&
            !hasListingValues);
    const showListingSection =
        !!periode.kegiatan.has_listing_updating && !isPencacahanOnly;
    const showPencacahanSection = !isListingOnly;
    const pelaksanaanRangeLabel = isListingOnly
        ? `${formatDate(periode.tanggal_mulai_listing)} - ${formatDate(periode.tanggal_selesai_listing)}`
        : `${formatDate(periode.tanggal_mulai)} - ${formatDate(periode.tanggal_selesai)}`;
    const { auth } = usePage<SharedData>().props;
    const isKetuaTim =
        auth.activeRole?.name === 'ketua_tim' ||
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'operator';
    const normalizedActivityName = (periode.kegiatan.nama_kegiatan || '')
        .trim()
        .toLowerCase();
    const isMineralActivity = normalizedActivityName.includes('mineral');
    const isPurpossiveSampling = ['purpossive', 'purposive'].includes(
        (periode.kegiatan.metode_sampling || '').trim().toLowerCase(),
    );
    const canEditNonResponse = ['dikirim', 'perubahan'].includes(
        periode.status,
    );
    const canReplaceSamples = isPurpossiveSampling;

    // State untuk edit mode
    const [isEditMode, setIsEditMode] = useState(false);
    const [editedData, setEditedData] = useState<
        Record<number, { non_response?: number; non_response_listing?: number }>
    >({});
    const [nonResponseSelections, setNonResponseSelections] = useState<
        Record<number, number[]>
    >({});
    const [replacementState, setReplacementState] = useState<{
        frameAllocationId: number;
        alokasiId: number;
        currentFrameId: number;
        currentFrameLabel: string;
        currentTahapan: 'listing' | 'pencacahan';
        currentPeran: string;
    } | null>(null);

    // Check if ada alokasi dengan peran pendataan
    const hasPendataanRole = periode.alokasi_petugas.some((alokasi) =>
        ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'].includes(alokasi.peran),
    );
    const summaryColSpan = isKetuaTim && hasPendataanRole ? 8 : 7;
    const totalListing = periode.total_estimasi_listing ?? 0;
    const totalPencacahan = periode.total_estimasi_pencacahan ?? 0;
    const hasFrameSampleDetails = periode.alokasi_petugas.some(
        (alokasi) => (alokasi.frame_sampel_details || []).length > 0,
    );
    const rateHonors = useMemo(
        () => periode.kegiatan.rate_honors || [],
        [periode.kegiatan.rate_honors],
    );

    const flattenFrameRows = useMemo<FlattenedFrameRow[]>(() => {
        return periode.alokasi_petugas.reduce<FlattenedFrameRow[]>(
            (rows, alokasi) => {
                const selectedFrames = alokasi.frame_sampel_details || [];

                if (selectedFrames.length === 0) {
                    rows.push({
                        alokasi,
                        frame: null,
                    });

                    return rows;
                }

                selectedFrames.forEach((frame) => {
                    rows.push({
                        alokasi,
                        frame,
                    });
                });

                return rows;
            },
            [],
        );
    }, [periode.alokasi_petugas]);

    const frameRowTotals = useMemo(() => {
        return flattenFrameRows.reduce(
            (totals, row) => {
                const rateHonor = resolveRateHonorForAlokasi(
                    row.alokasi,
                    rateHonors,
                );
                const target = row.frame
                    ? formatFrameTarget(row.frame) || 1
                    : Number(row.alokasi.jumlah_satuan || 0);
                const hargaSatuan =
                    row.frame?.tahapan === 'listing'
                        ? rateHonor?.rate_listing ||
                          rateHonor?.rate ||
                          row.alokasi.rate_listing ||
                          row.alokasi.rate_pencacahan ||
                          0
                        : rateHonor?.rate ||
                          rateHonor?.rate_listing ||
                          row.alokasi.rate_pencacahan ||
                          row.alokasi.rate_listing ||
                          0;
                const honor = row.frame
                    ? hargaSatuan * target
                    : rateHonor?.rate || row.alokasi.total_honor || 0;

                if (row.frame?.tahapan === 'listing') {
                    totals.listing += honor;
                } else {
                    totals.pencacahan += honor;
                }

                totals.total += honor;

                return totals;
            },
            { total: 0, pencacahan: 0, listing: 0 },
        );
    }, [flattenFrameRows, rateHonors]);

    const summarizeGroupPetugas = (
        group: GroupedFrameRow,
    ): Array<{
        key: string;
        label: string;
        peran: string;
        jenisPetugas: string;
    }> => {
        return [...group.rows]
            .sort((left, right) => {
                const leftPriority = getPeranPriority(left.alokasi.peran);
                const rightPriority = getPeranPriority(right.alokasi.peran);

                if (leftPriority !== rightPriority) {
                    return leftPriority - rightPriority;
                }

                return left.alokasi.petugas.nama.localeCompare(
                    right.alokasi.petugas.nama,
                    'id',
                    { sensitivity: 'base' },
                );
            })
            .map((row) => ({
                key: `${row.alokasi.id}-${row.frame?.frame_allocation_id ?? 'summary'}`,
                label: row.alokasi.petugas.nama,
                peran: peranLabels[row.alokasi.peran] || row.alokasi.peran,
                jenisPetugas: getJenisPetugasLabel(
                    row.alokasi.petugas.jenis_petugas,
                ),
            }));
    };

    const hasNonResponseColumn = isKetuaTim && hasPendataanRole;

    const petugasHonorSummaryRows = useMemo<PetugasHonorSummaryRow[]>(() => {
        const resolveHonorForAlokasiFrame = (
            alokasi: AlokasiPetugas,
            frame: FrameSampelDetail | null,
        ): { hargaSatuan: number; honor: number } => {
            const rateHonor = resolveRateHonorForAlokasi(alokasi, rateHonors);
            const hargaSatuan =
                frame?.tahapan === 'listing'
                    ? rateHonor?.rate_listing ||
                      rateHonor?.rate ||
                      alokasi.rate_listing ||
                      alokasi.rate_pencacahan ||
                      0
                    : rateHonor?.rate ||
                      rateHonor?.rate_listing ||
                      alokasi.rate_pencacahan ||
                      alokasi.rate_listing ||
                      0;
            const honor = frame
                ? hargaSatuan * (formatFrameTarget(frame) || 1)
                : rateHonor?.rate || Number(alokasi.total_honor || 0);

            return {
                hargaSatuan,
                honor,
            };
        };

        return periode.alokasi_petugas
            .map((alokasi) => {
                const frameDetails = alokasi.frame_sampel_details || [];
                const totalHonor =
                    frameDetails.length > 0
                        ? frameDetails.reduce((sum, frame) => {
                              const honor = resolveHonorForAlokasiFrame(
                                  alokasi,
                                  frame,
                              );

                              return sum + honor.honor;
                          }, 0)
                        : resolveHonorForAlokasiFrame(alokasi, null).honor;

                return {
                    key: `alokasi-${alokasi.id}`,
                    nama: alokasi.petugas.nama,
                    peran: peranLabels[alokasi.peran] || alokasi.peran,
                    jenisPetugas: getJenisPetugasLabel(
                        alokasi.petugas.jenis_petugas,
                    ),
                    totalHonor,
                };
            })
            .sort((left, right) => {
                const leftPriority = getPeranPriority(left.peran);
                const rightPriority = getPeranPriority(right.peran);

                if (leftPriority !== rightPriority) {
                    return leftPriority - rightPriority;
                }

                return left.nama.localeCompare(right.nama, 'id', {
                    sensitivity: 'base',
                });
            });
    }, [periode.alokasi_petugas, rateHonors]);

    const displayTotalEstimasi = hasFrameSampleDetails
        ? frameRowTotals.total
        : periode.total_estimasi;
    const displayTotalEstimasiPencacahan = hasFrameSampleDetails
        ? frameRowTotals.pencacahan
        : totalPencacahan;
    const displayTotalEstimasiListing = hasFrameSampleDetails
        ? frameRowTotals.listing
        : totalListing;

    const availableReplacementFrames = useMemo<ReplacementFrameOption[]>(() => {
        if (!replacementState) {
            return [];
        }

        const currentPeran = (replacementState.currentPeran || '')
            .trim()
            .toLowerCase();

        const usedFrameIdsInRole = new Set<number>();

        periode.alokasi_petugas.forEach((alokasi) => {
            const alokasiPeran = (alokasi.peran || '').trim().toLowerCase();

            if (currentPeran && alokasiPeran !== currentPeran) {
                return;
            }

            (alokasi.frame_sampel_details || []).forEach((selectedFrame) => {
                usedFrameIdsInRole.add(selectedFrame.kegiatan_frame_sampel_id);
            });
        });

        const allFrames = periode.kegiatan.kegiatan_frame_sampel || [];

        return allFrames
            .filter(
                (frame) => frame.tahapan === replacementState.currentTahapan,
            )
            .filter((frame) => frame.is_active)
            .filter((frame) => frame.id !== replacementState.currentFrameId)
            .filter((frame) => !usedFrameIdsInRole.has(frame.id))
            .sort((left, right) => {
                const leftName = formatFrameText(
                    left.nama_target || left.nama_frame,
                );
                const rightName = formatFrameText(
                    right.nama_target || right.nama_frame,
                );

                return leftName.localeCompare(rightName);
            })
            .map((frame) => ({
                frame,
                alreadySelected: false,
            }));
    }, [
        periode.alokasi_petugas,
        periode.kegiatan.kegiatan_frame_sampel,
        replacementState,
    ]);

    const frameDetailColumns = useMemo<FrameDetailColumn[]>(() => {
        const framePool = flattenFrameRows
            .map((row) => row.frame)
            .filter((frame): frame is FrameSampelDetail => frame !== null);
        const columns: FrameDetailColumn[] = [];
        const seenKeys = new Set<string>();
        const hasMetadataItems = framePool.some(
            (frame) => (frame.metadata_items || []).length > 0,
        );

        const addColumn = (
            key: string,
            header: string,
            getValue: (frame: FrameSampelDetail | null) => string,
        ) => {
            const normalizedKey = key.trim().toLowerCase();

            if (normalizedKey === '' || seenKeys.has(normalizedKey)) {
                return;
            }

            const hasValue = framePool.some((frame) => getValue(frame) !== '-');

            if (!hasValue) {
                return;
            }

            seenKeys.add(normalizedKey);
            columns.push({
                key,
                header,
                getValue,
            });
        };

        const addDynamicMetadataColumns = (): void => {
            framePool.forEach((frame) => {
                (frame.metadata_items || []).forEach((item) => {
                    const normalizedKey = item.code.trim().toLowerCase();

                    if (
                        normalizedKey === '' ||
                        [
                            'nama_target',
                            'nama_frame',
                            'nama_usaha_penggilingan',
                        ].includes(normalizedKey) ||
                        seenKeys.has(normalizedKey)
                    ) {
                        return;
                    }

                    const hasValue = framePool.some((candidateFrame) =>
                        (candidateFrame.metadata_items || []).some(
                            (candidateItem) => {
                                const candidateKey = candidateItem.code
                                    .trim()
                                    .toLowerCase();

                                return (
                                    candidateKey === normalizedKey &&
                                    formatReplacementMetadataItem(
                                        candidateItem,
                                    ) !== '-'
                                );
                            },
                        ),
                    );

                    if (!hasValue) {
                        return;
                    }

                    seenKeys.add(normalizedKey);
                    columns.push({
                        key: item.code,
                        header: item.label,
                        getValue: (candidateFrame: FrameSampelDetail | null) =>
                            resolveFrameMetadataItemDisplay(
                                candidateFrame,
                                item.code,
                            ),
                    });
                });
            });
        };

        if (hasMetadataItems) {
            addDynamicMetadataColumns();

            if (isPurpossiveSampling) {
                addColumn('nama_target', 'Nama target', (frame) =>
                    formatFrameText(
                        frame?.nama_target ||
                            frame?.nama_frame ||
                            frame?.nama_usaha_penggilingan,
                    ),
                );
            }

            return columns;
        }

        if (isMineralActivity) {
            addColumn('kecamatan', 'Kecamatan', (frame) =>
                resolveCodeLabelValue(
                    frame,
                    ['kdkec', 'kode_kecamatan'],
                    ['kdkec_label', 'kode_kecamatan_label'],
                ),
            );

            addColumn('desa_kelurahan', 'Desa/Kelurahan', (frame) =>
                resolveCodeLabelValue(
                    frame,
                    ['kddes', 'kode_desa'],
                    ['kddes_label', 'kode_desa_label'],
                ),
            );

            return columns;
        }

        if (isPurpossiveSampling) {
            addColumn('nama_target', 'Nama target', (frame) =>
                formatFrameText(
                    frame?.nama_target ||
                        frame?.nama_frame ||
                        frame?.nama_usaha_penggilingan,
                ),
            );

            return columns;
        }

        addColumn('kecamatan', 'Kecamatan', (frame) =>
            resolveCodeLabelValue(
                frame,
                ['kdkec', 'kode_kecamatan'],
                ['kdkec_label', 'kode_kecamatan_label'],
            ),
        );

        addColumn('desa_kelurahan', 'Desa/Kelurahan', (frame) =>
            resolveCodeLabelValue(
                frame,
                ['kddes', 'kode_desa'],
                ['kddes_label', 'kode_desa_label'],
            ),
        );

        addColumn('nks', 'NKS', (frame) =>
            resolveCodeLabelValue(frame, ['nks'], ['nks_label']),
        );

        addColumn('sls', 'SLS', (frame) =>
            resolveCodeLabelValue(frame, ['kode_sls'], ['kode_sls_label']),
        );

        if (isPurpossiveSampling) {
            addColumn('nama_target', 'Nama target', (frame) =>
                formatFrameText(
                    frame?.nama_target ||
                        frame?.nama_frame ||
                        frame?.nama_usaha_penggilingan,
                ),
            );
        }

        return columns;
    }, [flattenFrameRows, isMineralActivity, isPurpossiveSampling]);

    const groupedFrameRows = useMemo<GroupedFrameRow[]>(() => {
        const groups = new Map<string, GroupedFrameRow>();

        flattenFrameRows.forEach((row) => {
            const groupKey = row.frame
                ? `${row.frame.kegiatan_frame_sampel_id}-${row.frame.tahapan}`
                : `alokasi-${row.alokasi.id}`;

            const existingGroup = groups.get(groupKey);

            if (existingGroup) {
                existingGroup.rows.push(row);

                return;
            }

            groups.set(groupKey, {
                key: groupKey,
                frame: row.frame,
                rows: [row],
            });
        });

        return Array.from(groups.values())
            .map((group) => ({
                ...group,
                rows: [...group.rows].sort((left, right) => {
                    const leftPriority = getPeranPriority(left.alokasi.peran);
                    const rightPriority = getPeranPriority(right.alokasi.peran);

                    if (leftPriority !== rightPriority) {
                        return leftPriority - rightPriority;
                    }

                    return left.alokasi.petugas.nama.localeCompare(
                        right.alokasi.petugas.nama,
                        'id',
                        { sensitivity: 'base' },
                    );
                }),
            }))
            .sort((left, right) => {
                if (!left.frame && !right.frame) {
                    return left.rows[0].alokasi.petugas.nama.localeCompare(
                        right.rows[0].alokasi.petugas.nama,
                        'id',
                        { sensitivity: 'base' },
                    );
                }

                if (!left.frame) {
                    return 1;
                }

                if (!right.frame) {
                    return -1;
                }

                const leftSortValues = getFrameMetadataSortValues(left.frame);
                const rightSortValues = getFrameMetadataSortValues(right.frame);

                const maxLength = Math.max(
                    leftSortValues.length,
                    rightSortValues.length,
                );

                for (let index = 0; index < maxLength; index += 1) {
                    const comparison = (
                        leftSortValues[index] || ''
                    ).localeCompare(rightSortValues[index] || '', 'id', {
                        sensitivity: 'base',
                    });

                    if (comparison !== 0) {
                        return comparison;
                    }
                }

                const leftName = left.rows
                    .map((row) => row.alokasi.petugas.nama)
                    .join(' ');
                const rightName = right.rows
                    .map((row) => row.alokasi.petugas.nama)
                    .join(' ');

                return leftName.localeCompare(rightName, 'id', {
                    sensitivity: 'base',
                });
            });
    }, [flattenFrameRows]);

    const handleEditToggle = () => {
        if (!canEditNonResponse) {
            return;
        }

        if (!isEditMode) {
            // Masuk edit mode - inisialisasi data
            const initialData: Record<
                number,
                { non_response?: number; non_response_listing?: number }
            > = {};
            const initialSelections: Record<number, number[]> = {};

            periode.alokasi_petugas.forEach((alokasi) => {
                const frameAllocationIds = (alokasi.frame_sampel_details || [])
                    .filter((frame) => frame.is_non_response)
                    .map((frame) => frame.frame_allocation_id);

                initialData[alokasi.id] = {
                    non_response: alokasi.non_response || 0,
                    non_response_listing: alokasi.non_response_listing || 0,
                };
                initialSelections[alokasi.id] = frameAllocationIds;
            });
            setEditedData(initialData);
            setNonResponseSelections(initialSelections);
        }
        setIsEditMode(!isEditMode);
    };

    const handleSave = () => {
        const payload = periode.alokasi_petugas.map((alokasi) => ({
            id: alokasi.id,
            non_response: hasFrameSampleDetails
                ? nonResponseSelections[alokasi.id]?.length || 0
                : editedData[alokasi.id]?.non_response || 0,
            non_response_listing:
                editedData[alokasi.id]?.non_response_listing || 0,
            frame_allocation_ids: nonResponseSelections[alokasi.id] || [],
        }));

        router.post(
            '/alokasi/update-non-response',
            {
                alokasi_petugas: payload,
            },
            {
                onSuccess: () => {
                    setIsEditMode(false);
                },
            },
        );
    };

    const handleInputChange = (
        alokasiId: number,
        field: 'non_response' | 'non_response_listing',
        value: string,
    ) => {
        const numValue = value === '' ? 0 : parseInt(value, 10);
        setEditedData((prev) => ({
            ...prev,
            [alokasiId]: {
                ...prev[alokasiId],
                [field]: isNaN(numValue) ? 0 : numValue,
            },
        }));
    };

    const toggleNonResponseSelection = (
        alokasiId: number,
        frameAllocationId: number,
        checked: boolean,
    ) => {
        setNonResponseSelections((prev) => {
            const currentSelections = prev[alokasiId] || [];
            const nextSelections = checked
                ? [...currentSelections, frameAllocationId]
                : currentSelections.filter((id) => id !== frameAllocationId);

            return {
                ...prev,
                [alokasiId]: nextSelections,
            };
        });
    };

    const openReplacementDialog = (
        alokasiId: number,
        alokasiPeran: string,
        frameAllocationId: number,
        currentFrame: FrameSampelDetail,
    ) => {
        setReplacementState({
            frameAllocationId,
            alokasiId,
            currentFrameId: currentFrame.kegiatan_frame_sampel_id,
            currentFrameLabel: formatFrameText(
                currentFrame.nama_target || currentFrame.nama_frame,
            ),
            currentTahapan: currentFrame.tahapan,
            currentPeran: alokasiPeran,
        });
    };

    const handleReplaceFrameSample = (newFrameId: number) => {
        if (!replacementState) {
            return;
        }

        if (!newFrameId) {
            return;
        }

        router.patch(
            `/alokasi/frame-sampel/${replacementState.frameAllocationId}/replace`,
            {
                kegiatan_frame_sampel_id: newFrameId,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReplacementState(null);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Periode ${bulanLabel} ${periode.tahun}`} />

            <PageHeader
                title={`Detail Periode ${bulanLabel} ${periode.tahun}`}
                description={`Informasi alokasi petugas untuk ${periode.kegiatan.nama_kegiatan}`}
            >
                {hasFrameSampleDetails && (
                    <Button variant="outline" asChild>
                        <a href={exportMonitoringUrl}>
                            <FileText className="mr-2 h-4 w-4" />
                            Export PDF
                        </a>
                    </Button>
                )}
                <Button variant="outline" asChild>
                    <Link href="/alokasi">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            <ContentCard>
                <div className="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
                    <div className="rounded-3xl border border-indigo-200/70 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/30 dark:via-neutral-950 dark:to-cyan-950/20">
                        <div className="flex flex-wrap gap-2">
                            <span className="rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">
                                {bulanLabel} {periode.tahun}
                            </span>
                            <span className="rounded-full border border-indigo-200 bg-white/80 px-3 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-neutral-900/70 dark:text-indigo-300">
                                {periode.jenis_kegiatan === 'sensus'
                                    ? 'Sensus'
                                    : 'Survei'}
                            </span>
                            <span className="rounded-full border border-neutral-200 bg-white/80 px-3 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-800 dark:bg-neutral-900/70 dark:text-neutral-300">
                                {periode.jumlah_petugas} petugas
                            </span>
                            <span className="rounded-full border border-neutral-200 bg-white/80 px-3 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-800 dark:bg-neutral-900/70 dark:text-neutral-300">
                                Rev. {effectiveRevisionNumber}
                            </span>
                        </div>

                        <h3 className="mt-4 text-2xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {periode.kegiatan.nama_kegiatan}
                        </h3>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                            Detail periode ini menampilkan komposisi petugas,
                            rentang pelaksanaan, dan total estimasi honor dalam
                            satu tampilan yang lebih ringkas.
                        </p>

                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div className="rounded-2xl border border-neutral-200 bg-white/90 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Total estimasi
                                </p>
                                <p className="mt-2 text-lg font-semibold text-green-700 dark:text-green-400">
                                    {formatCurrency(displayTotalEstimasi)}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-neutral-200 bg-white/90 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Listing
                                </p>
                                <p className="mt-2 text-lg font-semibold text-blue-700 dark:text-blue-400">
                                    {formatCurrency(
                                        displayTotalEstimasiListing,
                                    )}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-neutral-200 bg-white/90 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Pencacahan
                                </p>
                                <p className="mt-2 text-lg font-semibold text-amber-700 dark:text-amber-400">
                                    {formatCurrency(
                                        displayTotalEstimasiPencacahan,
                                    )}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-neutral-200 bg-white/90 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Status
                                </p>
                                <p
                                    className={`mt-2 inline-flex rounded-full px-3 py-1 text-sm font-medium ${statusColors[periode.status]}`}
                                >
                                    {statusLabels[periode.status]}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/70">
                            <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <FileText className="h-4 w-4" />
                                <span>Pelaksanaan & Status</span>
                            </div>
                            <div className="mt-3 space-y-2">
                                <div>
                                    <p className="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                        Pelaksanaan
                                    </p>
                                    <p className="text-sm text-neutral-700 dark:text-neutral-300">
                                        {pelaksanaanRangeLabel}
                                    </p>
                                </div>
                                {periode.revision_number > 0 && (
                                    <div className="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                        <History className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                        <span>
                                            Revisi ke-{periode.revision_number}
                                        </span>
                                    </div>
                                )}
                                {periode.submitted_at && (
                                    <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Dikirim oleh{' '}
                                        <strong>
                                            {periode.submitted_by_name}
                                        </strong>{' '}
                                        pada{' '}
                                        {formatDateTime(periode.submitted_at)}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/70">
                            <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <Users className="h-4 w-4" />
                                <span>Komposisi alokasi</span>
                            </div>
                            <div className="mt-3 grid grid-cols-2 gap-3">
                                <div className="rounded-xl bg-neutral-50 p-3 dark:bg-neutral-900/60">
                                    <p className="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                        Petugas
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {periode.jumlah_petugas}
                                    </p>
                                </div>
                                <div className="rounded-xl bg-neutral-50 p-3 dark:bg-neutral-900/60">
                                    <p className="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                        Revisi
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {effectiveRevisionCount}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ContentCard>

            {/* Tabel Alokasi Petugas */}
            {!hasFrameSampleDetails && (
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Alokasi Petugas
                            </h3>
                            {isKetuaTim && hasPendataanRole && (
                                <div className="flex flex-wrap justify-end gap-2">
                                    {isEditMode ? (
                                        <>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={handleEditToggle}
                                            >
                                                <X className="mr-2 h-4 w-4" />
                                                Batal
                                            </Button>
                                            <Button
                                                size="sm"
                                                onClick={handleSave}
                                            >
                                                <Save className="mr-2 h-4 w-4" />
                                                Simpan Non Response
                                            </Button>
                                        </>
                                    ) : (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={handleEditToggle}
                                            disabled={!canEditNonResponse}
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Edit Non Response
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="w-full overflow-x-auto">
                            <table className="w-full min-w-[980px] text-left text-sm">
                                <thead className="bg-neutral-100 dark:bg-neutral-900">
                                    <tr>
                                        <th className="px-3 py-3 font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            No
                                        </th>
                                        <th className="px-3 py-3 font-medium text-neutral-600 dark:text-neutral-400">
                                            Nama Petugas
                                        </th>
                                        <th className="px-3 py-3 font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Jenis
                                        </th>
                                        <th className="px-3 py-3 font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Peran
                                        </th>
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Target
                                        </th>
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Realisasi
                                        </th>
                                        {isKetuaTim && hasPendataanRole && (
                                            <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                Non Response
                                            </th>
                                        )}
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Harga Satuan
                                        </th>
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Estimasi Honor
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    {periode.alokasi_petugas.map(
                                        (alokasi, index) => (
                                            <>
                                                {/* Listing Row - only if has_listing_updating and has listing data */}
                                                {showListingSection &&
                                                    (alokasi.jumlah_satuan_listing ??
                                                        0) > 0 && (
                                                        <tr
                                                            key={`${alokasi.id}-listing`}
                                                            className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                                        >
                                                            <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {index + 1}
                                                            </td>
                                                            <td className="px-3 py-3">
                                                                <div className="font-medium break-words text-neutral-900 dark:text-white">
                                                                    {
                                                                        alokasi
                                                                            .petugas
                                                                            .nama
                                                                    }
                                                                </div>
                                                            </td>
                                                            <td className="px-3 py-3 whitespace-nowrap">
                                                                <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300">
                                                                    {alokasi
                                                                        .petugas
                                                                        .jenis_petugas ===
                                                                    'organik'
                                                                        ? 'Organik'
                                                                        : 'Mitra'}
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {peranLabels[
                                                                    alokasi
                                                                        .peran
                                                                ] ||
                                                                    alokasi.peran}{' '}
                                                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                    (Listing)
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {
                                                                    alokasi.jumlah_satuan_listing
                                                                }
                                                            </td>
                                                            <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {alokasi.jumlah_satuan_listing_dibayarkan ??
                                                                    alokasi.jumlah_satuan_listing ??
                                                                    0}
                                                            </td>
                                                            {isKetuaTim &&
                                                                hasPendataanRole && (
                                                                    <td className="px-3 py-3 text-right whitespace-nowrap">
                                                                        {isEditMode ? (
                                                                            <input
                                                                                type="number"
                                                                                min="0"
                                                                                value={
                                                                                    editedData[
                                                                                        alokasi
                                                                                            .id
                                                                                    ]
                                                                                        ?.non_response_listing ||
                                                                                    0
                                                                                }
                                                                                onChange={(
                                                                                    e,
                                                                                ) =>
                                                                                    handleInputChange(
                                                                                        alokasi.id,
                                                                                        'non_response_listing',
                                                                                        e
                                                                                            .target
                                                                                            .value,
                                                                                    )
                                                                                }
                                                                                disabled={
                                                                                    alokasi.peran ===
                                                                                        'pengolahan' ||
                                                                                    alokasi.peran ===
                                                                                        'pengawas_pengolahan'
                                                                                }
                                                                                className="w-20 rounded border border-neutral-300 px-2 py-1 text-right text-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:disabled:bg-neutral-700"
                                                                            />
                                                                        ) : (
                                                                            <span className="text-neutral-900 dark:text-white">
                                                                                {alokasi.non_response_listing ||
                                                                                    0}
                                                                            </span>
                                                                        )}
                                                                    </td>
                                                                )}
                                                            <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {formatCurrency(
                                                                    alokasi.rate_listing ||
                                                                        0,
                                                                )}
                                                            </td>
                                                            <td className="px-3 py-3 text-right font-semibold whitespace-nowrap text-green-600 dark:text-green-400">
                                                                {formatCurrency(
                                                                    alokasi.total_honor_listing ||
                                                                        0,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    )}
                                                {/* Pencacahan Row */}
                                                {showPencacahanSection && (
                                                    <tr
                                                        key={`${alokasi.id}-pencacahan`}
                                                        className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                                    >
                                                        <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-white">
                                                            {index + 1}
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            <div className="font-medium break-words text-neutral-900 dark:text-white">
                                                                {
                                                                    alokasi
                                                                        .petugas
                                                                        .nama
                                                                }
                                                            </div>
                                                        </td>
                                                        <td className="px-3 py-3 whitespace-nowrap">
                                                            <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-neutral-700/60 dark:text-blue-300">
                                                                {alokasi.petugas
                                                                    .jenis_petugas ===
                                                                'organik'
                                                                    ? 'Organik'
                                                                    : 'Mitra'}
                                                            </span>
                                                        </td>
                                                        <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-white">
                                                            {peranLabels[
                                                                alokasi.peran
                                                            ] ||
                                                                alokasi.peran}{' '}
                                                            {showListingSection && (
                                                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                    (Pencacahan)
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                            {
                                                                alokasi.jumlah_satuan
                                                            }
                                                        </td>
                                                        <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                            {alokasi.jumlah_satuan_dibayarkan ??
                                                                alokasi.jumlah_satuan ??
                                                                0}
                                                        </td>
                                                        {isKetuaTim &&
                                                            hasPendataanRole && (
                                                                <td className="px-3 py-3 text-right whitespace-nowrap">
                                                                    {isEditMode ? (
                                                                        <input
                                                                            type="number"
                                                                            min="0"
                                                                            value={
                                                                                editedData[
                                                                                    alokasi
                                                                                        .id
                                                                                ]
                                                                                    ?.non_response ||
                                                                                0
                                                                            }
                                                                            onChange={(
                                                                                e,
                                                                            ) =>
                                                                                handleInputChange(
                                                                                    alokasi.id,
                                                                                    'non_response',
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                            disabled={
                                                                                alokasi.peran ===
                                                                                    'pengolahan' ||
                                                                                alokasi.peran ===
                                                                                    'pengawas_pengolahan'
                                                                            }
                                                                            className="w-20 rounded border border-neutral-300 px-2 py-1 text-right text-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:disabled:bg-neutral-700"
                                                                        />
                                                                    ) : (
                                                                        <span className="text-neutral-900 dark:text-white">
                                                                            {alokasi.non_response ||
                                                                                0}
                                                                        </span>
                                                                    )}
                                                                </td>
                                                            )}
                                                        <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                            {formatCurrency(
                                                                alokasi.rate_pencacahan ||
                                                                    0,
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3 text-right font-semibold whitespace-nowrap text-green-600 dark:text-green-400">
                                                            {formatCurrency(
                                                                alokasi.total_honor,
                                                            )}
                                                        </td>
                                                    </tr>
                                                )}
                                            </>
                                        ),
                                    )}
                                </tbody>
                                <tfoot className="bg-neutral-100 dark:bg-neutral-900">
                                    {showListingSection && (
                                        <>
                                            <tr className="border-b border-neutral-200 dark:border-neutral-800">
                                                <td
                                                    colSpan={summaryColSpan}
                                                    className="px-3 py-2 text-right text-sm font-semibold whitespace-nowrap text-neutral-600 dark:text-neutral-400"
                                                >
                                                    Total Listing:
                                                </td>
                                                <td className="px-3 py-2 text-right text-lg font-bold whitespace-nowrap text-blue-600 dark:text-blue-400">
                                                    {formatCurrency(
                                                        displayTotalEstimasiListing ||
                                                            0,
                                                    )}
                                                </td>
                                            </tr>
                                            {showPencacahanSection && (
                                                <tr className="border-b border-neutral-200 dark:border-neutral-800">
                                                    <td
                                                        colSpan={summaryColSpan}
                                                        className="px-3 py-2 text-right text-sm font-semibold whitespace-nowrap text-neutral-600 dark:text-neutral-400"
                                                    >
                                                        Total Pencacahan:
                                                    </td>
                                                    <td className="px-3 py-2 text-right text-lg font-bold whitespace-nowrap text-blue-600 dark:text-blue-400">
                                                        {formatCurrency(
                                                            displayTotalEstimasiPencacahan ||
                                                                0,
                                                        )}
                                                    </td>
                                                </tr>
                                            )}
                                        </>
                                    )}
                                    <tr>
                                        <td
                                            colSpan={summaryColSpan}
                                            className="px-3 py-3 text-right font-semibold whitespace-nowrap text-neutral-900 dark:text-white"
                                        >
                                            Total Keseluruhan:
                                        </td>
                                        <td className="px-3 py-3 text-right text-xl font-bold whitespace-nowrap text-green-600 dark:text-green-400">
                                            {formatCurrency(
                                                displayTotalEstimasi,
                                            )}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </ContentCard>
            )}

            {hasFrameSampleDetails && (
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Detail Alokasi
                                </h3>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                    Menampilkan baris gabungan per sampel yang
                                    dipakai bersama oleh PPL dan PML.
                                </p>
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Harga Satuan dan Estimasi Honor dihitung
                                    sebagai total grup sampel yang dipakai
                                    bersama.
                                </p>
                            </div>
                            {isKetuaTim && hasPendataanRole && (
                                <div className="flex flex-wrap justify-end gap-2">
                                    {isEditMode ? (
                                        <>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={handleEditToggle}
                                            >
                                                <X className="mr-2 h-4 w-4" />
                                                Batal
                                            </Button>
                                            <Button
                                                size="sm"
                                                onClick={handleSave}
                                            >
                                                <Save className="mr-2 h-4 w-4" />
                                                Simpan Non Response
                                            </Button>
                                        </>
                                    ) : (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={handleEditToggle}
                                            disabled={!canEditNonResponse}
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Edit Non Response
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="w-full overflow-x-auto">
                            <table className="w-full min-w-[1280px] text-left text-sm">
                                <thead className="bg-neutral-100 dark:bg-neutral-900">
                                    <tr>
                                        <th className="px-3 py-3 font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            No
                                        </th>
                                        <th className="px-3 py-3 font-medium text-neutral-600 dark:text-neutral-400">
                                            Nama Petugas
                                        </th>
                                        {frameDetailColumns.map((column) => (
                                            <th
                                                key={column.key}
                                                className="px-3 py-3 font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400"
                                            >
                                                {column.header}
                                            </th>
                                        ))}
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Target
                                        </th>
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Realisasi
                                        </th>
                                        {hasNonResponseColumn && (
                                            <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                Non Response
                                            </th>
                                        )}
                                        <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    {groupedFrameRows.map((group, index) => {
                                        const primaryRow = group.rows[0];
                                        const frame = group.frame;
                                        const target = frame
                                            ? formatFrameTarget(frame) || 1
                                            : Number(
                                                  primaryRow.alokasi
                                                      .jumlah_satuan || 0,
                                              );
                                        const realisasi = frame
                                            ? target
                                            : group.rows.reduce(
                                                  (sum, row) =>
                                                      sum +
                                                      Number(
                                                          row.alokasi
                                                              .jumlah_satuan_dibayarkan ??
                                                              row.alokasi
                                                                  .jumlah_satuan ??
                                                              0,
                                                      ),
                                                  0,
                                              );
                                        const petugasList =
                                            summarizeGroupPetugas(group);
                                        const selectedNonResponseCount =
                                            group.rows.reduce((sum, row) => {
                                                if (!row.frame) {
                                                    return sum;
                                                }

                                                const isSelected = (
                                                    nonResponseSelections[
                                                        row.alokasi.id
                                                    ] || []
                                                ).includes(
                                                    row.frame
                                                        .frame_allocation_id,
                                                );

                                                return (
                                                    sum + (isSelected ? 1 : 0)
                                                );
                                            }, 0);

                                        return (
                                            <tr
                                                key={group.key}
                                                className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-white">
                                                    {index + 1}
                                                </td>
                                                <td className="px-3 py-3 align-top">
                                                    <div className="flex flex-col gap-1.5">
                                                        {petugasList.map(
                                                            (petugas) => (
                                                                <div
                                                                    key={
                                                                        petugas.key
                                                                    }
                                                                    className="rounded-md border border-neutral-200/60 bg-white/50 px-2.5 py-1.5 dark:border-neutral-800/60 dark:bg-neutral-950/35"
                                                                >
                                                                    <div className="flex flex-wrap items-center gap-1.5">
                                                                        <span className="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                                            {
                                                                                petugas.peran
                                                                            }
                                                                        </span>
                                                                        <span className="text-[10px] font-medium text-neutral-500 dark:text-neutral-400">
                                                                            {
                                                                                petugas.jenisPetugas
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                    <div className="mt-0.5 text-sm leading-tight font-medium break-words text-neutral-900 dark:text-white">
                                                                        {
                                                                            petugas.label
                                                                        }
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </td>
                                                {frameDetailColumns.map(
                                                    (column) => (
                                                        <td
                                                            key={`${group.key}-${column.key}`}
                                                            className="px-3 py-3 align-top whitespace-nowrap text-neutral-900 dark:text-white"
                                                        >
                                                            {column.getValue(
                                                                frame,
                                                            )}
                                                        </td>
                                                    ),
                                                )}
                                                <td className="px-3 py-3 text-right align-top whitespace-nowrap text-neutral-900 dark:text-white">
                                                    {target}
                                                </td>
                                                <td className="px-3 py-3 text-right align-top whitespace-nowrap text-neutral-900 dark:text-white">
                                                    {realisasi}
                                                </td>
                                                {hasNonResponseColumn && (
                                                    <td className="px-3 py-3 text-right align-top whitespace-nowrap">
                                                        {isEditMode && frame ? (
                                                            <input
                                                                type="checkbox"
                                                                checked={
                                                                    selectedNonResponseCount >
                                                                        0 &&
                                                                    selectedNonResponseCount ===
                                                                        group.rows.filter(
                                                                            (
                                                                                row,
                                                                            ) =>
                                                                                row.frame,
                                                                        ).length
                                                                }
                                                                onChange={(
                                                                    e,
                                                                ) => {
                                                                    group.rows.forEach(
                                                                        (
                                                                            row,
                                                                        ) => {
                                                                            if (
                                                                                !row.frame
                                                                            ) {
                                                                                return;
                                                                            }

                                                                            toggleNonResponseSelection(
                                                                                row
                                                                                    .alokasi
                                                                                    .id,
                                                                                row
                                                                                    .frame
                                                                                    .frame_allocation_id,
                                                                                e
                                                                                    .target
                                                                                    .checked,
                                                                            );
                                                                        },
                                                                    );
                                                                }}
                                                                disabled={group.rows.every(
                                                                    (row) =>
                                                                        row
                                                                            .alokasi
                                                                            .peran ===
                                                                            'pengolahan' ||
                                                                        row
                                                                            .alokasi
                                                                            .peran ===
                                                                            'pengawas_pengolahan',
                                                                )}
                                                                className="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed"
                                                            />
                                                        ) : (
                                                            <span className="text-neutral-900 dark:text-white">
                                                                {frame
                                                                    ? selectedNonResponseCount
                                                                    : '-'}
                                                            </span>
                                                        )}
                                                    </td>
                                                )}
                                                <td className="px-3 py-3 text-right align-top whitespace-nowrap">
                                                    {frame &&
                                                    canReplaceSamples ? (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                openReplacementDialog(
                                                                    primaryRow
                                                                        .alokasi
                                                                        .id,
                                                                    primaryRow
                                                                        .alokasi
                                                                        .peran,
                                                                    frame.frame_allocation_id,
                                                                    frame,
                                                                )
                                                            }
                                                        >
                                                            Ganti Sampel
                                                        </Button>
                                                    ) : (
                                                        <span className="text-xs text-neutral-400">
                                                            -
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        <div className="border-t border-neutral-200 pt-4 dark:border-neutral-800">
                            <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                        Perkiraan honor per petugas
                                    </h4>
                                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        Ringkasan honor ditampilkan terpisah
                                        dari tabel sampel supaya detail alokasi
                                        tetap ringkas.
                                    </p>
                                </div>
                                <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-right dark:border-emerald-900/40 dark:bg-emerald-950/30">
                                    <div className="text-[11px] font-medium tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                                        Total estimasi honor
                                    </div>
                                    <div className="text-lg font-bold text-emerald-700 dark:text-emerald-300">
                                        {formatCurrency(displayTotalEstimasi)}
                                    </div>
                                </div>
                            </div>

                            <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                                <table className="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                                    <thead className="bg-neutral-50 dark:bg-neutral-900/70">
                                        <tr>
                                            <th className="px-3 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Petugas
                                            </th>
                                            <th className="px-3 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Peran
                                            </th>
                                            <th className="px-3 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Jenis
                                            </th>
                                            <th className="px-3 py-3 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                                Total honor
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                        {petugasHonorSummaryRows.map((row) => (
                                            <tr
                                                key={row.key}
                                                className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-3 py-3 font-medium text-neutral-900 dark:text-white">
                                                    {row.nama}
                                                </td>
                                                <td className="px-3 py-3 text-neutral-600 dark:text-neutral-300">
                                                    {row.peran}
                                                </td>
                                                <td className="px-3 py-3 text-neutral-600 dark:text-neutral-300">
                                                    {row.jenisPetugas}
                                                </td>
                                                <td className="px-3 py-3 text-right font-semibold text-green-600 dark:text-green-400">
                                                    {formatCurrency(
                                                        row.totalHonor,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </ContentCard>
            )}

            {/* Histori Revisi */}
            {revisions && revisions.length > 0 && (
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <History className="h-5 w-5 text-neutral-600 dark:text-neutral-400" />
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Histori Revisi
                            </h3>
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                ({revisions.length} revisi sebelumnya)
                            </span>
                        </div>

                        <div className="space-y-6">
                            {revisions.map((revision, revIdx) => {
                                return (
                                    <div
                                        key={revision.id}
                                        className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50"
                                    >
                                        <div className="mb-4 flex items-center justify-between border-b border-neutral-200 pb-3 dark:border-neutral-800">
                                            <div className="flex items-center gap-3">
                                                <span
                                                    className={`rounded-full px-3 py-1 text-sm font-semibold ${
                                                        revision.status ===
                                                        'direvisi'
                                                            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                                            : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
                                                    }`}
                                                >
                                                    {revision.status ===
                                                    'direvisi'
                                                        ? 'Sudah Direvisi'
                                                        : `Revisi ke-${revision.revision_number}`}
                                                </span>
                                                <span className="text-sm text-neutral-600 dark:text-neutral-400">
                                                    {revision.jumlah_petugas}{' '}
                                                    petugas
                                                </span>
                                            </div>
                                            <div className="text-right text-sm text-neutral-600 dark:text-neutral-400">
                                                {revision.submitted_at && (
                                                    <div>
                                                        {formatDateTime(
                                                            revision.submitted_at,
                                                        )}
                                                        {revision.submitted_by_name && (
                                                            <div className="font-medium">
                                                                oleh{' '}
                                                                {
                                                                    revision.submitted_by_name
                                                                }
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="w-full">
                                            <table className="w-full text-sm">
                                                <thead className="bg-neutral-200 dark:bg-neutral-800">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                            No
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                            Petugas
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                            Peran
                                                        </th>
                                                        <th className="px-3 py-2 text-right text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                            Beban Tugas
                                                        </th>
                                                        <th className="px-3 py-2 text-right text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                                            Honor
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                                    {revision.alokasi_petugas.map(
                                                        (alokasi, idx) => {
                                                            // Check if this petugas was changed in next revision
                                                            const nextRevisionAlokasi =
                                                                revIdx > 0
                                                                    ? revisions[
                                                                          revIdx -
                                                                              1
                                                                      ].alokasi_petugas.find(
                                                                          (a) =>
                                                                              a
                                                                                  .petugas
                                                                                  .id ===
                                                                              alokasi
                                                                                  .petugas
                                                                                  .id,
                                                                      )
                                                                    : periode.alokasi_petugas.find(
                                                                          (a) =>
                                                                              a
                                                                                  .petugas
                                                                                  .id ===
                                                                              alokasi
                                                                                  .petugas
                                                                                  .id,
                                                                      );

                                                            const bebanChanged =
                                                                nextRevisionAlokasi &&
                                                                getDisplayedBebanTugas(
                                                                    nextRevisionAlokasi,
                                                                ) !==
                                                                    getDisplayedBebanTugas(
                                                                        alokasi,
                                                                    );
                                                            const honorChanged =
                                                                nextRevisionAlokasi &&
                                                                getDisplayedHonor(
                                                                    nextRevisionAlokasi,
                                                                ) !==
                                                                    getDisplayedHonor(
                                                                        alokasi,
                                                                    );

                                                            return (
                                                                <tr
                                                                    key={
                                                                        alokasi.id
                                                                    }
                                                                >
                                                                    <td className="px-3 py-2 text-neutral-900 dark:text-white">
                                                                        {idx +
                                                                            1}
                                                                    </td>
                                                                    <td className="px-3 py-2 text-neutral-900 dark:text-white">
                                                                        {
                                                                            alokasi
                                                                                .petugas
                                                                                .nama
                                                                        }
                                                                    </td>
                                                                    <td className="px-3 py-2 text-neutral-900 dark:text-white">
                                                                        {peranLabels[
                                                                            alokasi
                                                                                .peran
                                                                        ] ||
                                                                            alokasi.peran}
                                                                    </td>
                                                                    <td className="px-3 py-2 text-right">
                                                                        <div
                                                                            className={
                                                                                bebanChanged
                                                                                    ? 'font-semibold text-orange-600 dark:text-orange-400'
                                                                                    : 'text-neutral-900 dark:text-white'
                                                                            }
                                                                        >
                                                                            {getDisplayedBebanTugas(
                                                                                alokasi,
                                                                            )}
                                                                            {bebanChanged &&
                                                                                nextRevisionAlokasi && (
                                                                                    <span className="ml-1 text-xs">
                                                                                        →{' '}
                                                                                        {getDisplayedBebanTugas(
                                                                                            nextRevisionAlokasi,
                                                                                        )}
                                                                                    </span>
                                                                                )}
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-3 py-2 text-right">
                                                                        <div
                                                                            className={
                                                                                honorChanged
                                                                                    ? 'font-semibold text-orange-600 dark:text-orange-400'
                                                                                    : 'text-neutral-900 dark:text-white'
                                                                            }
                                                                        >
                                                                            {formatCurrency(
                                                                                getDisplayedHonor(
                                                                                    alokasi,
                                                                                ),
                                                                            )}
                                                                            {honorChanged &&
                                                                                nextRevisionAlokasi && (
                                                                                    <div className="text-xs">
                                                                                        →{' '}
                                                                                        {formatCurrency(
                                                                                            getDisplayedHonor(
                                                                                                nextRevisionAlokasi,
                                                                                            ),
                                                                                        )}
                                                                                    </div>
                                                                                )}
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            );
                                                        },
                                                    )}
                                                </tbody>
                                                <tfoot className="bg-neutral-200 dark:bg-neutral-800">
                                                    <tr>
                                                        <td
                                                            colSpan={4}
                                                            className="px-3 py-2 text-right text-xs font-semibold text-neutral-900 dark:text-white"
                                                        >
                                                            Total:
                                                        </td>
                                                        <td className="px-3 py-2 text-right font-bold text-neutral-900 dark:text-white">
                                                            {formatCurrency(
                                                                revision.total_estimasi,
                                                            )}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </ContentCard>
            )}

            {replacementState && (
                <div className="bg-opacity-60 fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6">
                    <div className="w-full max-w-4xl rounded-2xl bg-white p-6 shadow-xl dark:bg-neutral-900">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Ganti Sampel
                                </h3>
                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    Sampel saat ini:{' '}
                                    {replacementState.currentFrameLabel}
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setReplacementState(null)}
                            >
                                <X className="mr-2 h-4 w-4" />
                                Tutup
                            </Button>
                        </div>

                        <div className="mt-5 max-h-[60vh] overflow-y-auto rounded-2xl border border-neutral-200 dark:border-neutral-800">
                            {availableReplacementFrames.length === 0 ? (
                                <div className="p-6 text-sm text-neutral-500 dark:text-neutral-400">
                                    Tidak ada sampel pengganti yang tersedia
                                    untuk tahapan ini.
                                </div>
                            ) : (
                                <div className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {availableReplacementFrames.map(
                                        ({ frame, alreadySelected }) =>
                                            (() => {
                                                const replacementFrameId =
                                                    frame.id ??
                                                    frame.kegiatan_frame_sampel_id ??
                                                    0;

                                                return (
                                                    <button
                                                        key={frame.id}
                                                        type="button"
                                                        onClick={() =>
                                                            handleReplaceFrameSample(
                                                                replacementFrameId,
                                                            )
                                                        }
                                                        disabled={
                                                            alreadySelected
                                                        }
                                                        className={`rounded-2xl border p-4 text-left transition ${
                                                            alreadySelected
                                                                ? 'cursor-not-allowed border-neutral-200 bg-neutral-100 opacity-60 dark:border-neutral-800 dark:bg-neutral-900/40'
                                                                : 'border-neutral-200 bg-neutral-50 hover:border-indigo-300 hover:bg-indigo-50 dark:border-neutral-800 dark:bg-neutral-950/70 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30'
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between gap-2">
                                                            <div className="font-semibold text-neutral-900 dark:text-white">
                                                                {formatFrameText(
                                                                    frame.nama_target ||
                                                                        frame.nama_frame ||
                                                                        frame.nama_usaha_penggilingan,
                                                                )}
                                                            </div>
                                                            {isPurpossiveSampling &&
                                                                frame.sample_role && (
                                                                    <span className="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                                                                        {formatSampleRoleLabel(
                                                                            frame.sample_role,
                                                                        )}
                                                                    </span>
                                                                )}
                                                        </div>
                                                        <div className="mt-2 grid gap-1 text-sm text-neutral-600 dark:text-neutral-300">
                                                            {(
                                                                frame.metadata_items ||
                                                                []
                                                            ).map((item) => (
                                                                <p
                                                                    key={`${frame.id}-${item.code}`}
                                                                >
                                                                    {item.label}
                                                                    :{' '}
                                                                    {formatReplacementMetadataItem(
                                                                        item,
                                                                    )}
                                                                </p>
                                                            ))}
                                                        </div>
                                                        {alreadySelected && (
                                                            <div className="mt-2 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                                Sudah dipakai
                                                                pada alokasi ini
                                                            </div>
                                                        )}
                                                        <div className="mt-3 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                                            {alreadySelected
                                                                ? 'Tidak bisa dipilih'
                                                                : 'Klik untuk mengganti sampel ini.'}
                                                        </div>
                                                    </button>
                                                );
                                            })(),
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
