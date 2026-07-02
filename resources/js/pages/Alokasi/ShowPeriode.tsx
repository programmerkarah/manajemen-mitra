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
import { useState } from 'react';

interface Petugas {
    id: number;
    nama: string;
    jenis_petugas: string;
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
    kegiatan: Kegiatan & { has_listing_updating?: boolean };
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

export default function ShowPeriode({ periode, revisions }: Props) {
    const bulanLabel = months[parseInt(periode.bulan) - 1];
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

    // State untuk edit mode
    const [isEditMode, setIsEditMode] = useState(false);
    const [editedData, setEditedData] = useState<
        Record<number, { non_response?: number; non_response_listing?: number }>
    >({});

    // Check if ada alokasi dengan peran pendataan
    const hasPendataanRole = periode.alokasi_petugas.some((alokasi) =>
        ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'].includes(alokasi.peran),
    );
    const canEditNonResponse = ['dikirim', 'perubahan'].includes(
        periode.status,
    );
    const summaryColSpan = isKetuaTim && hasPendataanRole ? 8 : 7;
    const totalListing = periode.total_estimasi_listing ?? 0;
    const totalPencacahan = periode.total_estimasi_pencacahan ?? 0;

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
            periode.alokasi_petugas.forEach((alokasi) => {
                initialData[alokasi.id] = {
                    non_response: alokasi.non_response || 0,
                    non_response_listing: alokasi.non_response_listing || 0,
                };
            });
            setEditedData(initialData);
        }
        setIsEditMode(!isEditMode);
    };

    const handleSave = () => {
        const payload = Object.keys(editedData).map((id) => ({
            id: Number(id),
            non_response: editedData[Number(id)].non_response || 0,
            non_response_listing:
                editedData[Number(id)].non_response_listing || 0,
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Periode ${bulanLabel} ${periode.tahun}`} />

            <PageHeader
                title={`Detail Periode ${bulanLabel} ${periode.tahun}`}
                description={`Informasi alokasi petugas untuk ${periode.kegiatan.nama_kegiatan}`}
            >
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
                                    {formatCurrency(periode.total_estimasi)}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-neutral-200 bg-white/90 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Listing
                                </p>
                                <p className="mt-2 text-lg font-semibold text-blue-700 dark:text-blue-400">
                                    {formatCurrency(totalListing)}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-neutral-200 bg-white/90 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Pencacahan
                                </p>
                                <p className="mt-2 text-lg font-semibold text-amber-700 dark:text-amber-400">
                                    {formatCurrency(totalPencacahan)}
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
                                        <Button size="sm" onClick={handleSave}>
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
                                        Beban Tugas
                                    </th>
                                    <th className="px-3 py-3 text-right font-medium whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                        Beban Tugas dibayarkan
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
                                                                alokasi.petugas
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
                                                        ] || alokasi.peran}{' '}
                                                        {showListingSection && (
                                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                (Pencacahan)
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-3 text-right whitespace-nowrap text-neutral-900 dark:text-white">
                                                        {alokasi.jumlah_satuan}
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
                                                    periode.total_estimasi_listing ||
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
                                                        periode.total_estimasi_pencacahan ||
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
                                        {formatCurrency(periode.total_estimasi)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </ContentCard>

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
        </AppLayout>
    );
}
