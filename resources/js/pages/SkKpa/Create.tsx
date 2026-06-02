import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { DatePicker } from '@/components/ui/date-picker';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { previewFileFromPost } from '@/utils/downloadUtils';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    BookOpen,
    ChevronDown,
    ExternalLink,
    FileText,
    Loader2,
    Save,
    X,
} from 'lucide-react';
import { useState } from 'react';

interface DasarHukum {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    status: string;
}

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
    first_periode: { bulan: number; tahun: number } | null;
}

interface PeriodChange {
    bulan: number;
    bulan_nama: string;
    tahun: number;
    added_count: number;
    removed_count: number;
    total_petugas: number;
}

interface PersonnelChangeInfo {
    has_changes: boolean;
    sk_number: string;
    sk_date: string;
    sk_month: string;
    sk_year: number;
    reference_month: string;
    reference_year: number;
    first_change_month: string;
    last_change_month: string;
    change_year: number;
    estimated_sk_month: string;
    estimated_sk_year: number;
    total_changes: number;
    changes: PeriodChange[];
}

interface CreateProps {
    kegiatan: Kegiatan;
    dasarHukumList: DasarHukum[];
    personnelChangeInfo: PersonnelChangeInfo | null;
    oldInput?: {
        nomor_sk?: string;
        tanggal_sk?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SK KPA', href: '/sk-kpa' },
    { title: 'Buat SK', href: '#' },
];

export default function Create({
    kegiatan,
    dasarHukumList,
    personnelChangeInfo,
    oldInput,
}: CreateProps) {
    const { auth } = usePage<SharedData>().props;
    const activeRoleName = auth.activeRole?.name;
    const canManageDasarHukum = ['admin', 'operator'].includes(
        activeRoleName ?? '',
    );

    const [formData, setFormData] = useState({
        nomor_sk: oldInput?.nomor_sk || '',
        tanggal_sk: oldInput?.tanggal_sk || '',
    });
    const [processing, setProcessing] = useState(false);
    const [dasarHukumOpen, setDasarHukumOpen] = useState(true);
    const [modalAlert, setModalAlert] = useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });

    const isFormComplete =
        formData.nomor_sk.trim() !== '' && formData.tanggal_sk !== '';
    const isReady = isFormComplete && dasarHukumList.length > 0;

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({ open: true, title, message });
    };

    const handlePreview = async () => {
        if (!isFormComplete) {
            showModalAlert(
                'Data Belum Lengkap',
                'Lengkapi Nomor SK dan Tanggal SK terlebih dahulu.',
            );
            return;
        }

        const sanitizedKegiatanName = kegiatan.nama_kegiatan.replace(
            /[^A-Za-z0-9_-]/g,
            '_',
        );

        try {
            await previewFileFromPost(
                `/sk-kpa/kegiatan/${kegiatan.hashed_id}/preview`,
                {
                    nomor_sk: formData.nomor_sk,
                    tanggal_sk: formData.tanggal_sk,
                },
                `Preview_SK_${sanitizedKegiatanName}.pdf`,
                {
                    responseMode: 'url',
                },
            );
        } catch {
            showModalAlert(
                'Preview Gagal',
                'Gagal membuka preview SK. Silakan coba lagi.',
            );
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/sk-kpa/kegiatan/${kegiatan.hashed_id}/generate`;
        form.style.display = 'none';

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        const fields: Record<string, string> = {
            nomor_sk: formData.nomor_sk,
            tanggal_sk: formData.tanggal_sk,
        };

        Object.entries(fields).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => setProcessing(false), 2000);
    };

    const formatDasarHukumLabel = (dh: DasarHukum): string => {
        if (dh.kategori === 'undang_undang') {
            return 'Undang-Undang';
        }
        if (dh.kategori === 'peraturan_pemerintah') {
            return 'Peraturan Pemerintah';
        }
        if (dh.kategori === 'peraturan_presiden') {
            return 'Peraturan Presiden';
        }
        if (dh.kategori === 'peraturan_menteri_badan') {
            return dh.instansi?.toLowerCase().startsWith('badan')
                ? `Peraturan ${dh.instansi}`
                : `Peraturan Menteri ${dh.instansi}`;
        }
        if (dh.kategori === 'keputusan_menteri_kepala_badan') {
            return dh.instansi?.toLowerCase().startsWith('badan')
                ? `Keputusan Kepala ${dh.instansi}`
                : `Keputusan Menteri ${dh.instansi}`;
        }
        if (dh.kategori === 'peraturan_kepala_badan') {
            return 'Peraturan Kepala Badan Pusat Statistik';
        }
        return dh.kategori;
    };

    const getCategoryOrder = (kategori: string): number => {
        const order: Record<string, number> = {
            undang_undang: 1,
            peraturan_pemerintah: 2,
            peraturan_presiden: 3,
            peraturan_menteri_badan: 4,
            peraturan_kepala_badan: 5,
            keputusan_menteri_kepala_badan: 6,
        };
        return order[kategori] ?? 99;
    };

    const getCategoryLabel = (kategori: string): string => {
        const labels: Record<string, string> = {
            undang_undang: 'Undang-Undang',
            peraturan_pemerintah: 'Peraturan Pemerintah',
            peraturan_presiden: 'Peraturan Presiden',
            peraturan_menteri_badan: 'Peraturan Menteri / Badan',
            peraturan_kepala_badan: 'Peraturan Kepala Badan Pusat Statistik',
            keputusan_menteri_kepala_badan: 'Keputusan Menteri / Kepala Badan',
        };
        return labels[kategori] ?? kategori;
    };

    const getCategoryColor = (
        kategori: string,
    ): { badge: string; dot: string } => {
        const colors: Record<string, { badge: string; dot: string }> = {
            undang_undang: {
                badge: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                dot: 'bg-purple-500',
            },
            peraturan_pemerintah: {
                badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                dot: 'bg-blue-500',
            },
            peraturan_presiden: {
                badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                dot: 'bg-indigo-500',
            },
            peraturan_menteri_badan: {
                badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                dot: 'bg-teal-500',
            },
            peraturan_kepala_badan: {
                badge: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
                dot: 'bg-cyan-500',
            },
            keputusan_menteri_kepala_badan: {
                badge: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                dot: 'bg-orange-500',
            },
        };
        return (
            colors[kategori] ?? {
                badge: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                dot: 'bg-neutral-500',
            }
        );
    };

    // Group dasar hukum by category, sorted
    const groupedDasarHukum = dasarHukumList
        .slice()
        .sort(
            (a, b) =>
                getCategoryOrder(a.kategori) - getCategoryOrder(b.kategori) ||
                a.tahun - b.tahun,
        )
        .reduce<Record<string, DasarHukum[]>>((acc, dh) => {
            const key = dh.kategori;
            if (!acc[key]) {
                acc[key] = [];
            }
            acc[key].push(dh);
            return acc;
        }, {});

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Buat SK - ${kegiatan.nama_kegiatan}`} />

            <Dialog
                open={modalAlert.open}
                onOpenChange={(open) =>
                    setModalAlert((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{modalAlert.title}</DialogTitle>
                        <DialogDescription>
                            {modalAlert.message}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() =>
                                setModalAlert((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <PageHeader
                title="Generate SK Petugas"
                description={`Buat SK KPA untuk ${kegiatan.nama_kegiatan}`}
            >
                <Button variant="outline" asChild>
                    <Link href="/sk-kpa">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            <ContentCard>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Personnel Change Info */}
                    {personnelChangeInfo ? (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0 rounded-lg bg-blue-100 p-2 dark:bg-blue-900/40">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        className="h-5 w-5 text-blue-600 dark:text-blue-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="12" y1="16" x2="12" y2="12" />
                                        <line
                                            x1="12"
                                            y1="8"
                                            x2="12.01"
                                            y2="8"
                                        />
                                    </svg>
                                </div>
                                <div className="flex-1 space-y-3">
                                    <h4 className="font-semibold text-blue-900 dark:text-blue-100">
                                        Informasi SK Perubahan
                                    </h4>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-lg border border-blue-300 bg-white p-3 dark:border-blue-800 dark:bg-blue-950/50">
                                            <div className="mb-1 text-xs text-blue-600 dark:text-blue-400">
                                                SK Terakhir:
                                            </div>
                                            <div className="text-base font-bold text-blue-900 dark:text-blue-100">
                                                {personnelChangeInfo.sk_month}{' '}
                                                {personnelChangeInfo.sk_year}
                                            </div>
                                        </div>
                                        {personnelChangeInfo.estimated_sk_month && (
                                            <div className="rounded-lg border border-green-300 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/50">
                                                <div className="mb-1 text-xs text-green-600 dark:text-green-400">
                                                    Perkiraan SK Perubahan:
                                                </div>
                                                <div className="text-base font-bold text-green-900 dark:text-green-100">
                                                    {
                                                        personnelChangeInfo.estimated_sk_month
                                                    }{' '}
                                                    {
                                                        personnelChangeInfo.estimated_sk_year
                                                    }
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    {personnelChangeInfo.has_changes &&
                                        personnelChangeInfo.changes.length >
                                            0 && (
                                            <div className="rounded-md border border-blue-300 bg-white/70 p-3 dark:border-blue-800 dark:bg-blue-950/30">
                                                <p className="mb-2 text-xs font-medium text-blue-900 dark:text-blue-100">
                                                    Detail Perubahan:
                                                </p>
                                                <div className="space-y-1">
                                                    {personnelChangeInfo.changes.map(
                                                        (change, idx) => (
                                                            <div
                                                                key={idx}
                                                                className="flex items-center gap-2 text-xs text-blue-700 dark:text-blue-300"
                                                            >
                                                                <span className="font-medium">
                                                                    {
                                                                        personnelChangeInfo.estimated_sk_month
                                                                    }
                                                                    :
                                                                </span>
                                                                {change.added_count >
                                                                    0 && (
                                                                    <span className="text-green-600 dark:text-green-400">
                                                                        +
                                                                        {
                                                                            change.added_count
                                                                        }
                                                                    </span>
                                                                )}{' '}
                                                                petugas;
                                                                {change.removed_count >
                                                                    0 && (
                                                                    <span className="text-red-600 dark:text-red-400">
                                                                        -
                                                                        {
                                                                            change.removed_count
                                                                        }
                                                                    </span>
                                                                )}
                                                                <span>
                                                                    Total: (
                                                                    {
                                                                        change.total_petugas
                                                                    }{' '}
                                                                    petugas)
                                                                </span>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-900/20">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0 rounded-lg bg-green-100 p-2 dark:bg-green-900/40">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        className="h-5 w-5 text-green-600 dark:text-green-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                        <polyline points="22 4 12 14.01 9 11.01" />
                                    </svg>
                                </div>
                                <div className="flex-1">
                                    <h4 className="mb-1 font-semibold text-green-900 dark:text-green-100">
                                        SK Pertama untuk Kegiatan Ini
                                    </h4>
                                    <p className="text-sm text-green-800 dark:text-green-200">
                                        Ini adalah SK pertama yang akan dibuat
                                        untuk kegiatan{' '}
                                        <strong>
                                            {kegiatan.nama_kegiatan}
                                        </strong>
                                        . Pastikan semua data petugas sudah
                                        lengkap dan benar sebelum generate SK.
                                    </p>
                                    {kegiatan.first_periode && (
                                        <div className="mt-3 flex items-center gap-2 rounded-md border border-green-300 bg-green-100 px-3 py-2 dark:border-green-700 dark:bg-green-900/40">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                className="h-4 w-4 shrink-0 text-green-700 dark:text-green-300"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <rect
                                                    x="3"
                                                    y="4"
                                                    width="18"
                                                    height="18"
                                                    rx="2"
                                                    ry="2"
                                                />
                                                <line
                                                    x1="16"
                                                    y1="2"
                                                    x2="16"
                                                    y2="6"
                                                />
                                                <line
                                                    x1="8"
                                                    y1="2"
                                                    x2="8"
                                                    y2="6"
                                                />
                                                <line
                                                    x1="3"
                                                    y1="10"
                                                    x2="21"
                                                    y2="10"
                                                />
                                            </svg>
                                            <span className="text-sm text-green-800 dark:text-green-200">
                                                Periode alokasi:{' '}
                                                <span className="text-base font-bold text-green-900 dark:text-green-100">
                                                    {new Date(
                                                        kegiatan.first_periode
                                                            .tahun,
                                                        kegiatan.first_periode
                                                            .bulan - 1,
                                                    ).toLocaleString('id-ID', {
                                                        month: 'long',
                                                    })}{' '}
                                                    {
                                                        kegiatan.first_periode
                                                            .tahun
                                                    }
                                                </span>
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Kegiatan + Form Fields */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="rounded-lg bg-neutral-50 p-4 md:col-span-2 dark:bg-neutral-900">
                            <h3 className="mb-2 font-semibold text-neutral-900 dark:text-white">
                                Informasi Kegiatan
                            </h3>
                            <div className="space-y-1 text-sm">
                                <div>
                                    <span className="text-neutral-600 dark:text-neutral-400">
                                        Nama:{' '}
                                    </span>
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {kegiatan.nama_kegiatan}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-neutral-600 dark:text-neutral-400">
                                        Kode:{' '}
                                    </span>
                                    <span className="text-neutral-900 dark:text-white">
                                        {kegiatan.kode_kegiatan}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="nomor_sk">
                                Nomor SK <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="nomor_sk"
                                required
                                value={formData.nomor_sk}
                                onChange={(e) =>
                                    setFormData({
                                        ...formData,
                                        nomor_sk: e.target.value,
                                    })
                                }
                                placeholder="Contoh: 053"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tanggal_sk">
                                Tanggal SK{' '}
                                <span className="text-red-500">*</span>
                            </Label>
                            <DatePicker
                                id="tanggal_sk"
                                value={formData.tanggal_sk}
                                onChange={(v) =>
                                    setFormData({
                                        ...formData,
                                        tanggal_sk: v,
                                    })
                                }
                            />
                        </div>
                    </div>

                    {/* Dasar Hukum – read-only, auto from active list */}
                    <div className="rounded-lg border border-neutral-200 dark:border-neutral-800">
                        <Collapsible
                            open={dasarHukumOpen}
                            onOpenChange={setDasarHukumOpen}
                        >
                            <CollapsibleTrigger asChild>
                                <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/50"
                                >
                                    <div className="flex items-center gap-2">
                                        <BookOpen className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                        <span className="font-medium text-neutral-900 dark:text-white">
                                            Dasar Hukum yang Digunakan
                                        </span>
                                        <Badge
                                            variant="secondary"
                                            className="ml-1 text-xs"
                                        >
                                            {dasarHukumList.length} item
                                        </Badge>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {canManageDasarHukum && (
                                            <Link
                                                href="/dasar-hukum"
                                                onClick={(e) =>
                                                    e.stopPropagation()
                                                }
                                                className="flex items-center gap-1 rounded px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-300"
                                            >
                                                <ExternalLink className="h-3 w-3" />
                                                Kelola
                                            </Link>
                                        )}
                                        <ChevronDown
                                            className={`h-4 w-4 text-neutral-500 transition-transform duration-200 dark:text-neutral-400 ${dasarHukumOpen ? 'rotate-180' : ''}`}
                                        />
                                    </div>
                                </button>
                            </CollapsibleTrigger>

                            <CollapsibleContent>
                                <div className="border-t border-neutral-200 dark:border-neutral-800">
                                    {dasarHukumList.length === 0 ? (
                                        <div className="flex flex-col items-center gap-3 px-4 py-8 text-center">
                                            <div className="rounded-full bg-amber-100 p-3 dark:bg-amber-900/30">
                                                <AlertTriangle className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                    Belum ada dasar hukum aktif
                                                </p>
                                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    SK tidak dapat digenerate
                                                    tanpa dasar hukum.
                                                </p>
                                            </div>
                                            {canManageDasarHukum && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link href="/dasar-hukum">
                                                        <ExternalLink className="mr-2 h-4 w-4" />
                                                        Kelola Dasar Hukum
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                            {Object.entries(
                                                groupedDasarHukum,
                                            ).map(([kategori, items]) => {
                                                const color =
                                                    getCategoryColor(kategori);
                                                return (
                                                    <div
                                                        key={kategori}
                                                        className="px-4 py-3"
                                                    >
                                                        <div className="mb-2 flex items-center gap-2">
                                                            <span
                                                                className={`rounded px-2 py-0.5 text-xs font-semibold ${color.badge}`}
                                                            >
                                                                {getCategoryLabel(
                                                                    kategori,
                                                                )}
                                                            </span>
                                                            <span className="text-xs text-neutral-400 dark:text-neutral-500">
                                                                {items.length}{' '}
                                                                item
                                                            </span>
                                                        </div>
                                                        <ul className="space-y-2">
                                                            {items.map((dh) => (
                                                                <li
                                                                    key={dh.id}
                                                                    className="flex items-start gap-2"
                                                                >
                                                                    <div
                                                                        className={`mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full ${color.dot}`}
                                                                    />
                                                                    <span className="text-sm leading-relaxed text-neutral-700 dark:text-neutral-300">
                                                                        <span className="font-medium">
                                                                            {formatDasarHukumLabel(
                                                                                dh,
                                                                            )}
                                                                        </span>{' '}
                                                                        Nomor{' '}
                                                                        {
                                                                            dh.nomor
                                                                        }{' '}
                                                                        Tahun{' '}
                                                                        {
                                                                            dh.tahun
                                                                        }{' '}
                                                                        tentang{' '}
                                                                        {
                                                                            dh.tentang
                                                                        }
                                                                    </span>
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>

                    {/* No dasar hukum warning banner */}
                    {dasarHukumList.length === 0 && (
                        <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                            <div className="flex-1 text-sm">
                                <p className="font-medium text-amber-900 dark:text-amber-100">
                                    Tidak ada dasar hukum yang diaktifkan
                                </p>
                                <p className="mt-0.5 text-amber-700 dark:text-amber-300">
                                    SK tidak dapat digenerate. Hubungi admin
                                    untuk mengaktifkan dasar hukum di menu{' '}
                                    <strong>Master Data → Dasar Hukum</strong>.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Submit Buttons */}
                    <div className="flex flex-wrap items-center justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                        <Button
                            type="button"
                            variant="outline"
                            asChild
                            className="gap-2"
                        >
                            <Link href="/sk-kpa">
                                <X className="h-4 w-4" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handlePreview}
                            disabled={processing || !isReady}
                            className="cursor-pointer gap-2"
                        >
                            <FileText className="h-4 w-4" />
                            Preview SK
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || !isReady}
                            className="min-w-[160px] cursor-pointer gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Generating...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4" />
                                    Generate SK
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </ContentCard>
        </AppLayout>
    );
}
