import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CheckCheck,
    ClipboardCheck,
    Loader2,
    RotateCcw,
    X,
} from 'lucide-react';
import { useState } from 'react';

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
    submitted_by: { id: number; name: string } | null;
    reviewed_by: { id: number; name: string } | null;
}

interface BatchItem {
    id: number;
    hashed_id: string;
    action: 'diterima' | 'ditolak';
    nominal_disetujui: number;
}

interface AllPulsaItem {
    id: number;
    kegiatan_id: number;
    kegiatan_kode: string | null;
    kegiatan_nama: string | null;
    jenis_pulsa: 'pelatihan' | 'pendataan';
    nominal: number;
    nominal_disetujui: number | null;
    status: 'draft' | 'dikirim' | 'diterima' | 'ditolak';
    is_current_kegiatan: boolean;
}

interface KegiatanInfo {
    id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    metode_pendataan_pencacahan: 'PAPI' | 'CAPI' | null;
}

interface Props {
    kegiatan: KegiatanInfo;
    pengajuanList: { encrypted: string };
    allPulsaPerPetugas: Record<number, AllPulsaItem[]>;
    filters: { bulan: string; tahun: string };
    canReview: boolean;
    canResubmit: boolean;
}

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    dikirim: 'Diajukan',
    diterima: 'Diterima',
    ditolak: 'Ditolak',
};

const STATUS_CLASSES: Record<string, string> = {
    draft: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    dikirim: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    diterima:
        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    ditolak: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
};

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

const formatNumber = (value: number) =>
    new Intl.NumberFormat('id-ID').format(value);

const parseFormattedNumber = (str: string): number => {
    const cleaned = str.replace(/\D/g, '');
    return cleaned === '' ? 0 : parseInt(cleaned, 10);
};

export default function PengajuanPulsaDetail({
    kegiatan,
    pengajuanList,
    allPulsaPerPetugas,
    filters,
    canReview,
    canResubmit,
}: Props) {
    const items = useDecryptedData<PengajuanPulsaItem>(pengajuanList.encrypted);

    const { bulan, tahun } = filters;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pengajuan Pulsa', href: '/pengajuan-pulsa' },
        {
            title: `Detail — ${kegiatan.nama_kegiatan} — ${BULAN_LABELS[bulan]} ${tahun}`,
            href: '#',
        },
    ];

    const [reviewItem, setReviewItem] = useState<PengajuanPulsaItem | null>(
        null,
    );
    const [reviewAction, setReviewAction] = useState<'diterima' | 'ditolak'>(
        'diterima',
    );
    const [nominalDisetujui, setNominalDisetujui] = useState<number>(0);
    const [catatanPenolakan, setCatatanPenolakan] = useState('');
    const [isReviewing, setIsReviewing] = useState(false);

    // Batch review state
    const [showBatchDialog, setShowBatchDialog] = useState(false);
    const [batchItems, setBatchItems] = useState<BatchItem[]>([]);
    const [batchCatatan, setBatchCatatan] = useState('');
    const [isReviewingAll, setIsReviewingAll] = useState(false);
    const [resubmitItem, setResubmitItem] = useState<PengajuanPulsaItem | null>(
        null,
    );
    const [resubmitNominal, setResubmitNominal] = useState<number>(0);
    const [resubmitCatatan, setResubmitCatatan] = useState('');
    const [isResubmitting, setIsResubmitting] = useState(false);

    const dikirimItems = items.filter((i) => i.status === 'dikirim');

    const openBatchDialog = () => {
        setBatchItems(
            dikirimItems.map((i) => ({
                id: i.id,
                hashed_id: i.hashed_id,
                action: 'diterima',
                nominal_disetujui: i.nominal,
            })),
        );
        setBatchCatatan('');
        setShowBatchDialog(true);
    };

    const setBatchAction = (id: number, action: 'diterima' | 'ditolak') => {
        setBatchItems((prev) =>
            prev.map((b) => (b.id === id ? { ...b, action } : b)),
        );
    };

    const setBatchNominal = (id: number, value: number) => {
        setBatchItems((prev) =>
            prev.map((b) =>
                b.id === id ? { ...b, nominal_disetujui: value } : b,
            ),
        );
    };

    const setAllBatchAction = (action: 'diterima' | 'ditolak') => {
        setBatchItems((prev) => prev.map((b) => ({ ...b, action })));
    };

    const handleReviewSubmit = () => {
        if (!reviewItem) {
            return;
        }
        setIsReviewing(true);
        router.post(
            `/pengajuan-pulsa/${reviewItem.hashed_id}/review`,
            {
                action: reviewAction,
                nominal_disetujui:
                    reviewAction === 'diterima' ? nominalDisetujui : undefined,
                catatan_penolakan: catatanPenolakan,
            },
            {
                onSuccess: () => {
                    setReviewItem(null);
                    setCatatanPenolakan('');
                    setIsReviewing(false);
                },
                onError: () => setIsReviewing(false),
            },
        );
    };

    const handleBatchSubmit = () => {
        setIsReviewingAll(true);
        router.post(
            '/pengajuan-pulsa/review-all',
            {
                kegiatan_id: kegiatan.id,
                bulan,
                tahun,
                catatan_penolakan: batchCatatan,
                items: batchItems.map((b) => ({
                    id: b.id,
                    action: b.action,
                    nominal_disetujui:
                        b.action === 'diterima'
                            ? b.nominal_disetujui
                            : undefined,
                })),
            },
            {
                onSuccess: () => {
                    setShowBatchDialog(false);
                    setBatchCatatan('');
                    setIsReviewingAll(false);
                },
                onError: () => setIsReviewingAll(false),
            },
        );
    };

    const totalNominal = items.reduce((sum, i) => sum + i.nominal, 0);
    const showActionColumn = canReview || canResubmit;

    const handleResubmitSubmit = () => {
        if (!resubmitItem) {
            return;
        }

        setIsResubmitting(true);
        router.post(
            `/pengajuan-pulsa/${resubmitItem.hashed_id}/resubmit`,
            {
                nominal: resubmitNominal,
                catatan: resubmitCatatan,
            },
            {
                onSuccess: () => {
                    setResubmitItem(null);
                    setResubmitNominal(0);
                    setResubmitCatatan('');
                    setIsResubmitting(false);
                },
                onError: () => setIsResubmitting(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Pengajuan — ${kegiatan.kode_kegiatan}`} />
            <div className="space-y-4">
                <PageHeader
                    title="Detail Pengajuan Pulsa"
                    description={`${kegiatan.kode_kegiatan} — ${kegiatan.nama_kegiatan}`}
                >
                    <Button variant="outline" asChild className="gap-2">
                        <Link href={`/pengajuan-pulsa?bulan=${bulan}`}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Summary card */}
                <ContentCard>
                    <div className="flex flex-wrap gap-6">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                Kegiatan
                            </p>
                            <p className="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {kegiatan.nama_kegiatan}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                Periode
                            </p>
                            <p className="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {BULAN_LABELS[bulan]} {tahun}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                Jumlah Pengajuan
                            </p>
                            <p className="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {items.length} pengajuan
                            </p>
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                Total Nominal
                            </p>
                            <p className="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {formatCurrency(totalNominal)}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                Metode Pendataan
                            </p>
                            <p
                                className={`mt-1 inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ${
                                    kegiatan.metode_pendataan_pencacahan ===
                                    'CAPI'
                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                        : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                                }`}
                            >
                                {kegiatan.metode_pendataan_pencacahan ?? '-'}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                {/* Pengajuan table */}
                <ContentCard padding="none">
                    <div className="flex flex-wrap items-center justify-between gap-3 px-6 pt-4 pb-2">
                        <div>
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                Daftar Petugas yang Diajukan
                            </h3>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Petugas non-organik yang diajukan pulsa pada{' '}
                                {BULAN_LABELS[bulan]} {tahun}
                            </p>
                        </div>
                        {canReview && dikirimItems.length > 0 && (
                            <Button
                                size="sm"
                                variant="outline"
                                className="gap-1.5 border-blue-200 text-blue-700 hover:bg-blue-50 hover:text-blue-800 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
                                onClick={openBatchDialog}
                            >
                                <CheckCheck className="h-3.5 w-3.5" />
                                Proses Review ({dikirimItems.length})
                            </Button>
                        )}
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-4 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Petugas
                                    </th>
                                    <th className="px-4 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Jenis Pulsa
                                    </th>
                                    <th className="px-4 py-3.5 text-right text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Nominal
                                    </th>
                                    <th className="px-4 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    <th className="px-4 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Diajukan Oleh
                                    </th>
                                    {showActionColumn && (
                                        <th className="px-4 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            Aksi
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={showActionColumn ? 6 : 5}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            Tidak ada pengajuan pulsa untuk
                                            kegiatan ini pada{' '}
                                            {BULAN_LABELS[bulan]} {tahun}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr
                                            key={item.id}
                                            className={`transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50 ${
                                                item.status === 'dikirim'
                                                    ? 'bg-blue-50/40 dark:bg-blue-950/20'
                                                    : ''
                                            }`}
                                        >
                                            <td className="px-4 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {item.petugas?.nama ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                        item.jenis_pulsa ===
                                                        'pendataan'
                                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                                            : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                                    }`}
                                                >
                                                    {item.jenis_pulsa ===
                                                    'pendataan'
                                                        ? 'Pendataan'
                                                        : 'Pelatihan'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm whitespace-nowrap">
                                                <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                                    {formatCurrency(
                                                        item.nominal,
                                                    )}
                                                </div>
                                                {item.status === 'diterima' &&
                                                    item.nominal_disetujui !==
                                                        null &&
                                                    item.nominal_disetujui !==
                                                        item.nominal && (
                                                        <div className="text-xs text-green-600 dark:text-green-400">
                                                            Disetujui:{' '}
                                                            {formatCurrency(
                                                                item.nominal_disetujui,
                                                            )}
                                                        </div>
                                                    )}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_CLASSES[item.status]}`}
                                                >
                                                    {STATUS_LABELS[item.status]}
                                                </span>
                                                {item.catatan_penolakan && (
                                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400">
                                                        {item.catatan_penolakan}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                {item.submitted_by?.name ?? '-'}
                                            </td>
                                            {showActionColumn && (
                                                <td className="px-4 py-3">
                                                    {canReview &&
                                                        item.status ===
                                                            'dikirim' && (
                                                            <div className="flex items-center justify-center">
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="gap-1.5 border-blue-200 text-blue-700 hover:bg-blue-50 hover:text-blue-800 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
                                                                    onClick={() => {
                                                                        setReviewItem(
                                                                            item,
                                                                        );
                                                                        setReviewAction(
                                                                            'diterima',
                                                                        );
                                                                        setNominalDisetujui(
                                                                            item.nominal,
                                                                        );
                                                                        setCatatanPenolakan(
                                                                            '',
                                                                        );
                                                                    }}
                                                                >
                                                                    <ClipboardCheck className="h-3.5 w-3.5" />
                                                                    Review
                                                                </Button>
                                                            </div>
                                                        )}
                                                    {item.status ===
                                                        'ditolak' &&
                                                        canResubmit && (
                                                            <div className="flex items-center justify-center">
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="gap-1.5 border-amber-200 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-800 dark:text-amber-400 dark:hover:bg-amber-950"
                                                                    onClick={() => {
                                                                        setResubmitItem(
                                                                            item,
                                                                        );
                                                                        setResubmitNominal(
                                                                            item.nominal,
                                                                        );
                                                                        setResubmitCatatan(
                                                                            item.catatan ??
                                                                                '',
                                                                        );
                                                                    }}
                                                                >
                                                                    <RotateCcw className="h-3.5 w-3.5" />
                                                                    Perbaiki
                                                                </Button>
                                                            </div>
                                                        )}
                                                    {item.status ===
                                                        'diterima' && (
                                                        <div className="flex items-center justify-center">
                                                            <span className="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                                <Check className="h-3.5 w-3.5" />
                                                                {item
                                                                    .reviewed_by
                                                                    ?.name ??
                                                                    'Diterima'}
                                                            </span>
                                                        </div>
                                                    )}
                                                    {item.status ===
                                                        'ditolak' && (
                                                        <div className="flex items-center justify-center">
                                                            <span className="inline-flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                                                <X className="h-3.5 w-3.5" />
                                                                {item
                                                                    .reviewed_by
                                                                    ?.name ??
                                                                    'Ditolak'}
                                                            </span>
                                                        </div>
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>

            {/* Review Dialog */}
            <Dialog
                open={reviewItem !== null}
                onOpenChange={(open) => {
                    if (!open && !isReviewing) {
                        setReviewItem(null);
                    }
                }}
            >
                <DialogContent className="flex max-h-[90vh] flex-col sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ClipboardCheck className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            Review Pengajuan Pulsa
                        </DialogTitle>
                        {reviewItem && (
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                {reviewItem.jenis_pulsa === 'pendataan'
                                    ? 'Pulsa Pendataan'
                                    : 'Pulsa Pelatihan'}{' '}
                                —{' '}
                                <span className="font-medium text-neutral-700 dark:text-neutral-300">
                                    {formatCurrency(reviewItem.nominal)}
                                </span>{' '}
                                · {BULAN_LABELS[reviewItem.bulan]}{' '}
                                {reviewItem.tahun}
                            </p>
                        )}
                    </DialogHeader>

                    {reviewItem && (
                        <div className="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto py-1">
                            {/* All pulsa context table for this petugas */}
                            {(() => {
                                const petugasAllPulsa =
                                    allPulsaPerPetugas[reviewItem.petugas_id] ??
                                    [];
                                const grandTotal = petugasAllPulsa.reduce(
                                    (sum, p) => sum + p.nominal,
                                    0,
                                );
                                const hasOtherKegiatan = petugasAllPulsa.some(
                                    (p) => !p.is_current_kegiatan,
                                );
                                return (
                                    <div className="rounded-lg border border-neutral-200 dark:border-neutral-700">
                                        <div className="flex items-center justify-between border-b border-neutral-200 px-4 py-2.5 dark:border-neutral-700">
                                            <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                Rekap Pengajuan Pulsa —{' '}
                                                {reviewItem.petugas?.nama}
                                            </h4>
                                            {hasOtherKegiatan && (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                                    Lintas kegiatan
                                                </span>
                                            )}
                                        </div>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="bg-neutral-50 dark:bg-neutral-900/50">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                                            No
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                                            Kegiatan
                                                        </th>
                                                        <th className="px-3 py-2 text-center text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                                            Jenis
                                                        </th>
                                                        <th className="px-3 py-2 text-right text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                                            Nominal
                                                        </th>
                                                        <th className="px-3 py-2 text-center text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                                            Status
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                                    {petugasAllPulsa.map(
                                                        (p, idx) => {
                                                            const isThisItem =
                                                                p.id ===
                                                                reviewItem.id;
                                                            return (
                                                                <tr
                                                                    key={p.id}
                                                                    className={
                                                                        isThisItem
                                                                            ? 'bg-blue-50 dark:bg-blue-950/30'
                                                                            : 'hover:bg-neutral-50 dark:hover:bg-neutral-900/40'
                                                                    }
                                                                >
                                                                    <td className="px-3 py-2 text-xs text-neutral-500 dark:text-neutral-400">
                                                                        {idx +
                                                                            1}
                                                                    </td>
                                                                    <td className="px-3 py-2">
                                                                        <div className="flex items-center gap-1.5">
                                                                            <span className="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                                                                                {p.kegiatan_kode ??
                                                                                    '-'}
                                                                            </span>
                                                                            {isThisItem && (
                                                                                <span className="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                                                    sedang
                                                                                    direview
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                        {p.kegiatan_nama && (
                                                                            <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                                                                {
                                                                                    p.kegiatan_nama
                                                                                }
                                                                            </p>
                                                                        )}
                                                                    </td>
                                                                    <td className="px-3 py-2 text-center">
                                                                        <span
                                                                            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                                                                p.jenis_pulsa ===
                                                                                'pendataan'
                                                                                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                                                                    : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                                                            }`}
                                                                        >
                                                                            {p.jenis_pulsa ===
                                                                            'pendataan'
                                                                                ? 'Pendataan'
                                                                                : 'Pelatihan'}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-3 py-2 text-right text-xs font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                                        {formatCurrency(
                                                                            p.nominal,
                                                                        )}
                                                                        {p.status ===
                                                                            'diterima' &&
                                                                            p.nominal_disetujui !==
                                                                                null &&
                                                                            p.nominal_disetujui !==
                                                                                p.nominal && (
                                                                                <div className="text-xs text-green-600 dark:text-green-400">
                                                                                    disetujui:{' '}
                                                                                    {formatCurrency(
                                                                                        p.nominal_disetujui,
                                                                                    )}
                                                                                </div>
                                                                            )}
                                                                    </td>
                                                                    <td className="px-3 py-2 text-center">
                                                                        {isThisItem ? (
                                                                            <span className="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                                                Direview
                                                                            </span>
                                                                        ) : (
                                                                            <span
                                                                                className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_CLASSES[p.status]}`}
                                                                            >
                                                                                {
                                                                                    STATUS_LABELS[
                                                                                        p
                                                                                            .status
                                                                                    ]
                                                                                }
                                                                            </span>
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            );
                                                        },
                                                    )}
                                                </tbody>
                                                <tfoot className="border-t-2 border-neutral-300 bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-900/50">
                                                    <tr>
                                                        <td
                                                            colSpan={3}
                                                            className="px-3 py-2 text-right text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                                                        >
                                                            Total pengajuan:
                                                        </td>
                                                        <td className="px-3 py-2 text-right text-sm font-bold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                            {formatCurrency(
                                                                grandTotal,
                                                            )}
                                                        </td>
                                                        <td />
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                );
                            })()}

                            {/* Accept / Reject toggle */}
                            <div>
                                <Label className="mb-2 block text-sm font-medium">
                                    Keputusan
                                </Label>
                                <div className="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setReviewAction('diterima')
                                        }
                                        className={`flex items-center justify-center gap-2 rounded-lg border-2 px-4 py-3 text-sm font-medium transition-all ${
                                            reviewAction === 'diterima'
                                                ? 'border-green-500 bg-green-50 text-green-700 dark:border-green-600 dark:bg-green-950 dark:text-green-300'
                                                : 'border-neutral-200 bg-white text-neutral-600 hover:border-green-300 hover:bg-green-50/50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400'
                                        }`}
                                    >
                                        <Check className="h-4 w-4" />
                                        Terima
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setReviewAction('ditolak')
                                        }
                                        className={`flex items-center justify-center gap-2 rounded-lg border-2 px-4 py-3 text-sm font-medium transition-all ${
                                            reviewAction === 'ditolak'
                                                ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-600 dark:bg-red-950 dark:text-red-300'
                                                : 'border-neutral-200 bg-white text-neutral-600 hover:border-red-300 hover:bg-red-50/50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400'
                                        }`}
                                    >
                                        <X className="h-4 w-4" />
                                        Tolak
                                    </button>
                                </div>
                            </div>

                            {/* Nominal disetujui (only when diterima) */}
                            {reviewAction === 'diterima' && reviewItem && (
                                <div className="space-y-1.5">
                                    <Label
                                        htmlFor="nominal_disetujui"
                                        className="text-sm font-medium"
                                    >
                                        Nominal Disetujui
                                    </Label>
                                    <Input
                                        id="nominal_disetujui"
                                        type="text"
                                        inputMode="numeric"
                                        value={formatNumber(nominalDisetujui)}
                                        onChange={(e) =>
                                            setNominalDisetujui(
                                                Math.min(
                                                    parseFormattedNumber(
                                                        e.target.value,
                                                    ),
                                                    reviewItem.nominal,
                                                ),
                                            )
                                        }
                                        className="text-right"
                                    />
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        Maksimal{' '}
                                        {formatCurrency(reviewItem.nominal)}
                                    </p>
                                </div>
                            )}

                            {/* Rejection reason (only shown when tolak is selected) */}
                            {reviewAction === 'ditolak' && (
                                <div className="space-y-1.5">
                                    <Label
                                        htmlFor="catatan_penolakan"
                                        className="text-sm font-medium"
                                    >
                                        Alasan Penolakan{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Textarea
                                        id="catatan_penolakan"
                                        value={catatanPenolakan}
                                        onChange={(e) =>
                                            setCatatanPenolakan(e.target.value)
                                        }
                                        placeholder="Jelaskan alasan penolakan kepada pengaju..."
                                        rows={3}
                                        className="resize-none"
                                    />
                                    {!catatanPenolakan.trim() && (
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Alasan wajib diisi agar petugas
                                            dapat mengajukan ulang.
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setReviewItem(null)}
                            disabled={isReviewing}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={handleReviewSubmit}
                            disabled={
                                isReviewing ||
                                (reviewAction === 'diterima' &&
                                    nominalDisetujui <= 0) ||
                                (reviewAction === 'ditolak' &&
                                    !catatanPenolakan.trim())
                            }
                            className={
                                reviewAction === 'diterima'
                                    ? 'bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600'
                                    : 'bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600'
                            }
                        >
                            {isReviewing ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Memproses...
                                </>
                            ) : reviewAction === 'diterima' ? (
                                <>
                                    <Check className="mr-2 h-4 w-4" />
                                    Konfirmasi Terima
                                </>
                            ) : (
                                <>
                                    <X className="mr-2 h-4 w-4" />
                                    Konfirmasi Tolak
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Resubmit Dialog */}
            <Dialog
                open={resubmitItem !== null}
                onOpenChange={(open) => {
                    if (!open && !isResubmitting) {
                        setResubmitItem(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <RotateCcw className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            Perbaiki Pengajuan Ditolak
                        </DialogTitle>
                    </DialogHeader>

                    {resubmitItem && (
                        <div className="space-y-4 py-1">
                            <div className="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900/50">
                                <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                    {resubmitItem.petugas?.nama ?? '-'}
                                </p>
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {resubmitItem.jenis_pulsa === 'pendataan'
                                        ? 'Pulsa Pendataan'
                                        : 'Pulsa Pelatihan'}
                                    {' · '}
                                    {BULAN_LABELS[resubmitItem.bulan]}{' '}
                                    {resubmitItem.tahun}
                                </p>
                                {resubmitItem.catatan_penolakan && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400">
                                        Alasan ditolak:{' '}
                                        {resubmitItem.catatan_penolakan}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="resubmit_nominal">
                                    Nominal Baru
                                </Label>
                                <Input
                                    id="resubmit_nominal"
                                    type="text"
                                    inputMode="numeric"
                                    value={formatNumber(resubmitNominal)}
                                    onChange={(e) =>
                                        setResubmitNominal(
                                            parseFormattedNumber(
                                                e.target.value,
                                            ),
                                        )
                                    }
                                    className="text-right"
                                />
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Maksimal Rp100.000 dan harus kelipatan
                                    Rp1.000.
                                </p>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="resubmit_catatan">
                                    Catatan (Opsional)
                                </Label>
                                <Textarea
                                    id="resubmit_catatan"
                                    value={resubmitCatatan}
                                    onChange={(e) =>
                                        setResubmitCatatan(e.target.value)
                                    }
                                    rows={3}
                                    placeholder="Catatan tambahan saat kirim ulang..."
                                />
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setResubmitItem(null)}
                            disabled={isResubmitting}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={handleResubmitSubmit}
                            disabled={isResubmitting || resubmitNominal <= 0}
                            className="bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600"
                        >
                            {isResubmitting ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Mengirim Ulang...
                                </>
                            ) : (
                                <>
                                    <RotateCcw className="mr-2 h-4 w-4" />
                                    Kirim Ulang
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            {/* Batch Review Dialog */}
            <Dialog
                open={showBatchDialog}
                onOpenChange={(open) => {
                    if (!open && !isReviewingAll) {
                        setShowBatchDialog(false);
                    }
                }}
            >
                <DialogContent className="sm:max-w-9xl flex max-h-[120vh] flex-col">
                    <DialogHeader className="shrink-0">
                        <DialogTitle className="flex items-center gap-2">
                            <ClipboardCheck className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            Proses Review Pengajuan ({dikirimItems.length})
                        </DialogTitle>
                    </DialogHeader>

                    <div className="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto py-1">
                        {/* Quick-fill buttons */}
                        <div className="flex shrink-0 items-center gap-2">
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                Nama Kegiatan:
                            </span>
                            <span>{kegiatan.nama_kegiatan}</span>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                Jumlah Petugas:
                            </span>
                            <span>{items.length} petugas</span>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                Isi cepat:
                            </span>
                            <button
                                type="button"
                                onClick={() => setAllBatchAction('diterima')}
                                className="flex items-center gap-1.5 rounded-md border border-green-200 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-50 dark:border-green-800 dark:text-green-400 dark:hover:bg-green-950"
                            >
                                <Check className="h-3 w-3" />
                                Setuju Semua
                            </button>
                            <button
                                type="button"
                                onClick={() => setAllBatchAction('ditolak')}
                                className="flex items-center gap-1.5 rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950"
                            >
                                <X className="h-3 w-3" />
                                Tolak Semua
                            </button>
                        </div>

                        {/* Per-item table */}
                        <div className="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <table className="w-full text-sm">
                                <thead className="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900">
                                    <tr>
                                        <th className="px-3 py-2.5 text-left font-semibold text-neutral-700 dark:text-neutral-300">
                                            Petugas
                                        </th>
                                        <th className="px-3 py-2.5 text-left font-semibold text-neutral-700 dark:text-neutral-300">
                                            Jenis
                                        </th>
                                        <th className="px-3 py-2.5 text-right font-semibold text-neutral-700 dark:text-neutral-300">
                                            Diajukan
                                        </th>
                                        <th className="px-3 py-2.5 text-center font-semibold text-neutral-700 dark:text-neutral-300">
                                            Keputusan
                                        </th>
                                        <th className="px-3 py-2.5 text-left font-semibold text-neutral-700 dark:text-neutral-300">
                                            Disetujui
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                    {batchItems.map((batchItem) => {
                                        const sourceItem = dikirimItems.find(
                                            (i) => i.id === batchItem.id,
                                        );
                                        if (!sourceItem) {
                                            return null;
                                        }
                                        return (
                                            <tr
                                                key={batchItem.id}
                                                className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                            >
                                                <td className="px-3 py-2.5 font-medium text-neutral-900 dark:text-neutral-100">
                                                    {sourceItem.petugas?.nama ??
                                                        '-'}
                                                </td>
                                                <td className="px-3 py-2.5">
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                                            sourceItem.jenis_pulsa ===
                                                            'pendataan'
                                                                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                                                : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                                        }`}
                                                    >
                                                        {sourceItem.jenis_pulsa ===
                                                        'pendataan'
                                                            ? 'Pendataan'
                                                            : 'Pelatihan'}
                                                    </span>
                                                </td>
                                                <td className="px-3 py-2.5 text-right whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                    {formatCurrency(
                                                        sourceItem.nominal,
                                                    )}
                                                </td>
                                                <td className="px-3 py-2.5">
                                                    <div className="flex justify-center gap-1.5">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setBatchAction(
                                                                    batchItem.id,
                                                                    'diterima',
                                                                )
                                                            }
                                                            className={`flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium transition-all ${
                                                                batchItem.action ===
                                                                'diterima'
                                                                    ? 'border-green-500 bg-green-50 text-green-700 dark:border-green-600 dark:bg-green-950 dark:text-green-300'
                                                                    : 'border-neutral-200 text-neutral-500 hover:border-green-300 hover:bg-green-50/50 dark:border-neutral-700 dark:text-neutral-400'
                                                            }`}
                                                        >
                                                            <Check className="h-3 w-3" />
                                                            Terima
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setBatchAction(
                                                                    batchItem.id,
                                                                    'ditolak',
                                                                )
                                                            }
                                                            className={`flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium transition-all ${
                                                                batchItem.action ===
                                                                'ditolak'
                                                                    ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-600 dark:bg-red-950 dark:text-red-300'
                                                                    : 'border-neutral-200 text-neutral-500 hover:border-red-300 hover:bg-red-50/50 dark:border-neutral-700 dark:text-neutral-400'
                                                            }`}
                                                        >
                                                            <X className="h-3 w-3" />
                                                            Tolak
                                                        </button>
                                                    </div>
                                                </td>
                                                <td className="px-3 py-2.5">
                                                    {batchItem.action ===
                                                    'diterima' ? (
                                                        <Input
                                                            type="text"
                                                            inputMode="numeric"
                                                            value={formatNumber(
                                                                batchItem.nominal_disetujui,
                                                            )}
                                                            onChange={(e) =>
                                                                setBatchNominal(
                                                                    batchItem.id,
                                                                    Math.min(
                                                                        parseFormattedNumber(
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        ),
                                                                        sourceItem.nominal,
                                                                    ),
                                                                )
                                                            }
                                                            className="h-7 w-32 text-right text-xs"
                                                        />
                                                    ) : (
                                                        <span className="block text-right text-xs text-neutral-400 dark:text-neutral-500">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        {/* Global catatan penolakan */}
                        {batchItems.some((b) => b.action === 'ditolak') && (
                            <div className="shrink-0 space-y-1.5">
                                <Label
                                    htmlFor="batch_catatan"
                                    className="text-sm font-medium"
                                >
                                    Alasan Penolakan{' '}
                                    <span className="text-xs font-normal text-neutral-500">
                                        (berlaku untuk semua yang ditolak)
                                    </span>
                                </Label>
                                <Textarea
                                    id="batch_catatan"
                                    value={batchCatatan}
                                    onChange={(e) =>
                                        setBatchCatatan(e.target.value)
                                    }
                                    placeholder="Jelaskan alasan penolakan..."
                                    rows={2}
                                    className="resize-none"
                                />
                            </div>
                        )}
                    </div>

                    <DialogFooter className="shrink-0 gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowBatchDialog(false)}
                            disabled={isReviewingAll}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={handleBatchSubmit}
                            disabled={isReviewingAll}
                            className="bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600"
                        >
                            {isReviewingAll ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Memproses...
                                </>
                            ) : (
                                <>
                                    <CheckCheck className="mr-2 h-4 w-4" />
                                    Proses Review ({batchItems.length})
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

PengajuanPulsaDetail.layout = (page: React.ReactNode) => page;
