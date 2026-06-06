import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { previewFileFromPost } from '@/utils/downloadUtils';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    Eye,
    FileText,
    User,
} from 'lucide-react';
import { useState } from 'react';

interface Petugas {
    id: number;
    nama: string;
    nik: string;
    tempat_lahir: string | null;
    tanggal_lahir: string | null;
    alamat: string | null;
    no_rekening: string | null;
    nama_bank: string | null;
    npwp: string | null;
}

interface KetuaTim {
    nama: string | null;
    nip: string | null;
}

interface KegiatanDetail {
    kegiatan_id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tanggal_selesai: string;
    tanggal_selesai_label: string;
    peran: string;
    hasil_listing: number | null;
    hasil_pendataan_lapangan: number | null;
    hasil_pengolahan: number | null;
    spk_id: number;
    nomor_spk: string;
}

interface SpkItem {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    nomor_bast_preview: string | null;
    tanggal_spk: string;
    tanggal_mulai_kerja: string;
    tanggal_selesai_kerja_asli: string;
    tanggal_berakhir_paling_akhir: string;
    nama_ppk: string;
    nip_ppk: string | null;
    petugas: Petugas;
    ketua_tim: KetuaTim;
    kegiatan_list: KegiatanDetail[];
    jumlah_kegiatan: number;
    has_bast?: boolean;
    existing_bast_hashed_id?: string | null;
    existing_bast_nomor?: string | null;
    lampiran_total?: number;
    lampiran_generated?: number;
    lampiran_signed?: number;
    is_sensus_ekonomi?: boolean;
    bapp_termin_ii_complete?: boolean | null;
    muatan_prelist_default?: number;
    muatan_prelist_keluarga_default?: number;
    muatan_prelist_usaha_default?: number;
    unit_sampel_pencacahan_items?: { id: number; nama: string }[];
}

interface CreateForMonthProps {
    bulan: number;
    tahun: number;
    bulan_label: string;
    spk_list: {
        encrypted: string;
    };
    mode?: 'create' | 'detail';
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Berita Acara', href: '/berita-acara' },
    { title: 'Generate Berita Acara', href: '#' },
];

const getPeranLabel = (peran: string): string => {
    const labels: Record<string, string> = {
        pcl_ppl: 'Petugas Lapangan',
        pml: 'Petugas Pemeriksa Lapangan',
        pengolahan: 'Petugas Pengolahan',
        pengawas_pengolahan: 'Pemeriksa Pengolahan',
    };
    return labels[peran] || peran;
};

export default function CreateForMonth({
    bulan,
    tahun,
    bulan_label,
    spk_list,
    mode = 'create',
}: CreateForMonthProps) {
    const { auth } = usePage<SharedData>().props;
    const decryptedSpkList = useDecryptedData<SpkItem>(spk_list.encrypted);
    const isAdminOrOperator =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'operator';
    const isDetailMode = mode === 'detail';

    // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir (ASC) kemudian nama petugas (A-Z)
    const sortedSpkList = [...decryptedSpkList].sort((a, b) => {
        // Compare by tanggal_berakhir_paling_akhir first
        const dateCompare = a.tanggal_berakhir_paling_akhir.localeCompare(
            b.tanggal_berakhir_paling_akhir,
        );
        if (dateCompare !== 0) return dateCompare;

        // If dates are equal, compare by nama petugas (A-Z)
        return a.petugas.nama.localeCompare(b.petugas.nama);
    });

    const [selectedSpks, setSelectedSpks] = useState<number[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);
    const [modalAlert, setModalAlert] = useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });
    const [lampiranSelectDialog, setLampiranSelectDialog] = useState<{
        open: boolean;
        spk: SpkItem | null;
    }>({ open: false, spk: null });

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };

    const isSpkSelectable = (spk: SpkItem) =>
        !(spk.is_sensus_ekonomi && !spk.bapp_termin_ii_complete);

    const selectableSpks = sortedSpkList.filter(isSpkSelectable);

    const handleSelectAll = () => {
        if (selectedSpks.length === selectableSpks.length) {
            setSelectedSpks([]);
        } else {
            setSelectedSpks(selectableSpks.map((spk) => spk.spk_id));
        }
    };

    const handleSelectSpk = (spkId: number) => {
        if (selectedSpks.includes(spkId)) {
            setSelectedSpks(selectedSpks.filter((id) => id !== spkId));
        } else {
            setSelectedSpks([...selectedSpks, spkId]);
        }
    };

    const handleGenerateBast = () => {
        if (selectedSpks.length === 0) {
            showModalAlert(
                'Data Belum Lengkap',
                'Pilih minimal 1 Perjanjian Kerja untuk generate BAST main.',
            );
            return;
        }

        const payload = {
            spk_ids: selectedSpks,
            bulan,
            tahun,
        };

        const encryptedPayload = encryptFilters(payload);

        setIsGenerating(true);
        router.post(
            '/berita-acara/generate-batch',
            {
                encrypted_filters: encryptedPayload,
            },
            {
                onFinish: () => setIsGenerating(false),
            },
        );
    };

    const handlePreviewSpk = async (spk: SpkItem) => {
        const payload: Record<string, unknown> = {
            spk_id: spk.spk_id,
        };

        const encryptedPayload = encryptFilters(payload);

        try {
            await previewFileFromPost(
                '/berita-acara/preview-bast',
                {
                    encrypted_filters: encryptedPayload,
                },
                'Preview_BAST.pdf',
            );
        } catch {
            showModalAlert(
                'Preview Gagal',
                'Gagal membuka preview BAST. Silakan coba lagi.',
            );
        }
    };

    const handlePreviewLampiran = async (spk: SpkItem, kegiatanId?: number) => {
        const payload: Record<string, unknown> = { spk_id: spk.spk_id };

        if (kegiatanId) {
            payload.kegiatan_id = kegiatanId;
        }

        const encryptedPayload = encryptFilters(payload);

        try {
            await previewFileFromPost(
                '/berita-acara/lampiran-action/preview',
                { encrypted_filters: encryptedPayload },
                'Preview_Lampiran.pdf',
            );
        } catch (error) {
            showModalAlert(
                'Preview Gagal',
                error instanceof Error && error.message.trim() !== ''
                    ? error.message
                    : 'Gagal membuka preview lampiran. Silakan coba lagi.',
            );
        }
    };

    const handlePreviewLampiranClick = (spk: SpkItem) => {
        if (isAdminOrOperator) {
            handlePreviewLampiran(spk);
        } else {
            setLampiranSelectDialog({ open: true, spk });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Generate BAST - ${bulan_label} ${tahun}`} />

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

            <Dialog
                open={lampiranSelectDialog.open}
                onOpenChange={(open) =>
                    setLampiranSelectDialog((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Pilih Kegiatan</DialogTitle>
                        <DialogDescription>
                            Pilih kegiatan yang akan dipreview lampirannya.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        {lampiranSelectDialog.spk?.kegiatan_list.map((keg) => (
                            <Button
                                key={keg.kegiatan_id}
                                variant="outline"
                                className="w-full justify-start"
                                onClick={() => {
                                    setLampiranSelectDialog((prev) => ({
                                        ...prev,
                                        open: false,
                                    }));
                                    handlePreviewLampiran(
                                        lampiranSelectDialog.spk!,
                                        keg.kegiatan_id,
                                    );
                                }}
                            >
                                {keg.nama_kegiatan}
                            </Button>
                        ))}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() =>
                                setLampiranSelectDialog((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Batal
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <PageHeader
                title={`${isDetailMode ? 'Detail BAST' : 'Generate BAST'} - ${bulan_label} ${tahun}`}
                description={
                    isDetailMode
                        ? 'Daftar petugas, status BAST, dan lampiran periode ini (termasuk yang belum digenerate).'
                        : 'Pilih perjanjian kerja yang akan dibuatkan dokumen BAST'
                }
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/berita-acara">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    {!isDetailMode &&
                        isAdminOrOperator &&
                        selectedSpks.length > 0 && (
                            <Button
                                onClick={handleGenerateBast}
                                disabled={isGenerating}
                            >
                                <FileText className="mr-2 h-4 w-4" />
                                Generate {selectedSpks.length} BAST
                            </Button>
                        )}
                </div>
            </PageHeader>

            {!isDetailMode && !isAdminOrOperator && (
                <ContentCard>
                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                        Halaman ini dipakai admin atau operator untuk membuat
                        BAST main. Lampiran per kegiatan dilanjutkan dari
                        halaman detail BAST oleh ketua tim terkait.
                    </p>
                </ContentCard>
            )}

            <ContentCard>
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Perjanjian Kerja
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                {isDetailMode
                                    ? `${sortedSpkList.length} Perjanjian Kerja pada periode ini`
                                    : `${sortedSpkList.length} Perjanjian Kerja belum memiliki BAST di periode ini`}
                            </p>
                        </div>
                        {!isDetailMode && isAdminOrOperator && (
                            <Button variant="outline" onClick={handleSelectAll}>
                                {selectedSpks.length ===
                                    selectableSpks.length &&
                                selectableSpks.length > 0
                                    ? 'Batal Pilih Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        )}
                    </div>

                    <div className="space-y-4">
                        {sortedSpkList.map((spk) => (
                            <div
                                key={spk.spk_id}
                                onClick={() =>
                                    !isDetailMode &&
                                    isAdminOrOperator &&
                                    isSpkSelectable(spk) &&
                                    handleSelectSpk(spk.spk_id)
                                }
                                className={`rounded-lg border p-4 transition-colors ${
                                    !isDetailMode &&
                                    isAdminOrOperator &&
                                    isSpkSelectable(spk)
                                        ? 'cursor-pointer'
                                        : 'cursor-default'
                                } ${
                                    !isDetailMode &&
                                    isAdminOrOperator &&
                                    selectedSpks.includes(spk.spk_id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700'
                                }`}
                            >
                                <div className="flex items-start gap-4">
                                    {!isDetailMode && isAdminOrOperator && (
                                        <input
                                            type="checkbox"
                                            checked={selectedSpks.includes(
                                                spk.spk_id,
                                            )}
                                            disabled={!isSpkSelectable(spk)}
                                            onChange={() => {}}
                                            onClick={(e) => e.stopPropagation()}
                                            className={`mt-1 h-4 w-4 rounded border-neutral-300 ${
                                                isSpkSelectable(spk)
                                                    ? 'pointer-events-none'
                                                    : 'cursor-not-allowed opacity-40'
                                            }`}
                                        />
                                    )}
                                    <div className="flex-1 space-y-3">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <User className="h-4 w-4 text-neutral-500" />
                                                    <span className="font-semibold text-neutral-900 dark:text-white">
                                                        {spk.petugas.nama}
                                                    </span>
                                                    <Badge variant="secondary">
                                                        {spk.petugas.nik}
                                                    </Badge>
                                                </div>
                                                <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                                    Perjanjian Kerja:{' '}
                                                    {spk.nomor_spk}
                                                </p>
                                                {isDetailMode && (
                                                    <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                                        BAST:{' '}
                                                        {spk.existing_bast_nomor ??
                                                            'Belum digenerate'}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="text-right">
                                                <Badge variant="outline">
                                                    <Calendar className="mr-1 h-3 w-3" />
                                                    BAST:{' '}
                                                    {new Date(
                                                        spk.tanggal_berakhir_paling_akhir,
                                                    ).toLocaleDateString(
                                                        'id-ID',
                                                    )}
                                                </Badge>
                                                {isDetailMode && (
                                                    <div className="mt-2">
                                                        <Badge
                                                            variant={
                                                                spk.has_bast
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {spk.has_bast
                                                                ? `Lampiran ${spk.lampiran_generated ?? 0}/${spk.lampiran_total ?? 0}`
                                                                : 'BAST belum digenerate'}
                                                        </Badge>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="rounded-md bg-neutral-50 p-3 dark:bg-neutral-800">
                                            <p className="mb-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                                Kegiatan yang Diikuti (
                                                {spk.jumlah_kegiatan}):
                                            </p>
                                            <div className="space-y-2">
                                                {spk.kegiatan_list.map(
                                                    (keg, idx) => (
                                                        <div
                                                            key={idx}
                                                            className="flex items-center justify-between text-sm"
                                                        >
                                                            <div className="flex-1">
                                                                <span className="font-medium text-neutral-900 dark:text-white">
                                                                    {
                                                                        keg.nama_kegiatan
                                                                    }
                                                                </span>
                                                                <span className="text-neutral-600 dark:text-neutral-400">
                                                                    {' '}
                                                                    •{' '}
                                                                    {getPeranLabel(
                                                                        keg.peran,
                                                                    )}
                                                                </span>
                                                            </div>
                                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                Berakhir:{' '}
                                                                {
                                                                    keg.tanggal_selesai_label
                                                                }
                                                            </span>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-4 text-xs text-neutral-600 dark:text-neutral-400">
                                            {spk.ketua_tim.nama && (
                                                <span>
                                                    Ketua Tim:{' '}
                                                    {spk.ketua_tim.nama}
                                                    {spk.ketua_tim.nip &&
                                                        ` (${spk.ketua_tim.nip})`}
                                                </span>
                                            )}
                                        </div>

                                        {spk.is_sensus_ekonomi && (
                                            <div
                                                className={`flex items-start gap-2 rounded-md border p-3 ${spk.bapp_termin_ii_complete ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30'}`}
                                                onClick={(e) =>
                                                    e.stopPropagation()
                                                }
                                            >
                                                <AlertCircle
                                                    className={`mt-0.5 h-4 w-4 shrink-0 ${spk.bapp_termin_ii_complete ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'}`}
                                                />
                                                <p
                                                    className={`text-sm ${spk.bapp_termin_ii_complete ? 'text-blue-800 dark:text-blue-300' : 'text-amber-800 dark:text-amber-300'}`}
                                                >
                                                    {spk.bapp_termin_ii_complete
                                                        ? 'Data realisasi dan screenshot untuk BAST Sensus Ekonomi diambil otomatis dari BAPP SE2026 Termin I dan II.'
                                                        : 'BAST belum dapat di-preview — BAPP SE2026 Termin I dan II belum lengkap (realisasi + screenshot Fasih).'}
                                                </p>
                                            </div>
                                        )}

                                        <div className="mt-3 flex justify-end gap-2">
                                            {!isDetailMode &&
                                                isAdminOrOperator && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            spk.is_sensus_ekonomi &&
                                                            !spk.bapp_termin_ii_complete
                                                        }
                                                        title={
                                                            spk.is_sensus_ekonomi &&
                                                            !spk.bapp_termin_ii_complete
                                                                ? 'Preview BAST belum tersedia — BAPP Termin I dan II belum lengkap'
                                                                : undefined
                                                        }
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            handlePreviewSpk(
                                                                spk,
                                                            );
                                                        }}
                                                    >
                                                        <Eye className="mr-1 h-3 w-3" />
                                                        Preview BAST
                                                    </Button>
                                                )}
                                            {!isDetailMode && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={
                                                        spk.is_sensus_ekonomi &&
                                                        !spk.bapp_termin_ii_complete
                                                    }
                                                    title={
                                                        spk.is_sensus_ekonomi &&
                                                        !spk.bapp_termin_ii_complete
                                                            ? 'Preview lampiran belum tersedia — BAPP Termin I dan II belum lengkap'
                                                            : undefined
                                                    }
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handlePreviewLampiranClick(
                                                            spk,
                                                        );
                                                    }}
                                                >
                                                    <FileText className="mr-1 h-3 w-3" />
                                                    Preview Lampiran
                                                </Button>
                                            )}
                                            {isDetailMode &&
                                                spk.existing_bast_hashed_id && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/berita-acara/${spk.existing_bast_hashed_id}`}
                                                        >
                                                            <FileText className="mr-1 h-3 w-3" />
                                                            Buka Detail BAST
                                                        </Link>
                                                    </Button>
                                                )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {decryptedSpkList.length === 0 && (
                        <div className="py-12 text-center text-neutral-500">
                            Tidak ada Perjanjian Kerja yang perlu dibuatkan BAST
                        </div>
                    )}
                </div>
            </ContentCard>
        </AppLayout>
    );
}
