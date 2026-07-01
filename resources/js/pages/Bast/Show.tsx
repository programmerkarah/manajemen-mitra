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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import {
    constructBastDownloadFilename,
    openFastDownload,
    previewFileFromPost,
    tryDirectDownload,
} from '@/utils/downloadUtils';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Clock3,
    Download,
    Eye,
    FileArchive,
    FileCheck2,
    FileText,
    FolderDown,
    PenLine,
    Upload,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Bast {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    tanggal_bast: string;
    tanggal_serah_terima: string;
    menggunakan_fasih: boolean;
    uraian_pekerjaan: string;
    nama_ketua_tim: string;
    nip_ketua_tim: string | null;
    nama_ppk: string;
    nip_ppk: string | null;
    hasil_pekerjaan: string | null;
    file_path: string | null;
    compiled_file_path: string | null;
    main_signed_file_path: string | null;
    signed_file_path: string | null;
    lokasi_kegiatan: string | null;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    catatan: string | null;
    is_sensus_ekonomi: boolean;
    muatan_input: number | null;
    muatan_prelist: number | null;
    realisasi_unit_sampel: Record<string, number> | null;
    fasih_screenshot_path: string | null;
    created_by: string;
    created_at: string;
    is_legacy_mode: boolean;
}

interface Spk {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    tanggal_spk: string;
    nilai_kontrak: number;
}

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    alamat: string | null;
    no_hp: string | null;
}

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
}

interface LampiranItem {
    id: number;
    kegiatan_id: number;
    periode_alokasi_id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    peran: string | null;
    tanggal_selesai: string | null;
    tanggal_selesai_formatted: string;
    ketua_tim_nama: string | null;
    file_path: string | null;
    signed_file_path: string | null;
    fasih_screenshot_path: string | null;
    generated_at: string | null;
    signed_uploaded_at: string | null;
    fasih_screenshot_uploaded_at?: string | null;
    status: 'pending' | 'generated' | 'signed';
    can_download: boolean;
    can_generate: boolean;
    can_upload_signed: boolean;
    can_upload_fasih_screenshot?: boolean;
    can_preview: boolean;
    ready_to_generate: boolean;
    uses_fasih_screenshot?: boolean;
    preview_spk_id?: number | null;
}

interface BastListItem {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    petugas_nama: string;
    petugas_id?: number | null;
    file_path: string | null;
    compiled_file_path: string | null;
    main_signed_file_path: string | null;
    signed_file_path: string | null;
    is_current: boolean;
}

interface Permissions {
    can_manage_main: boolean;
    is_ketua_tim: boolean;
    can_upload_main: boolean;
}

interface Summary {
    total_lampiran: number;
    generated_lampiran: number;
    signed_lampiran: number;
    all_lampiran_generated: boolean;
    all_lampiran_signed: boolean;
    main_signed_uploaded: boolean;
    final_signed_ready: boolean;
}

interface SensusReference {
    spk_id: number;
    bulan: number;
    tahun: number;
    unit_sampel_pencacahan_items: { id: number; nama: string }[];
    realisasi_unit_sampel: Record<string, number>;
    muatan_input: number | null;
    muatan_prelist: number | null;
    muatan_prelist_unit_sampel?: Record<string, number | null>;
    target_sls?: number | null;
    fasih_screenshot_path: string | null;
    fasih_screenshot_uploaded_at?: string | null;
    bapp_termin_ii_complete?: boolean;
}

interface EligibleWithoutBast {
    petugas_nama: string;
    petugas_id?: number | null;
}

interface ShowProps {
    bast: Bast;
    spk: Spk | null;
    petugas: Petugas | null;
    kegiatan: Kegiatan;
    lampiran: LampiranItem[];
    bast_list: BastListItem[];
    eligible_without_bast: EligibleWithoutBast[];
    permissions: Permissions;
    summary: Summary;
    sensus_reference?: SensusReference | null;
    mode?: string;
    bulan: number;
    tahun: number;
    bulan_label: string;
}

interface ModalAlertState {
    open: boolean;
    title: string;
    message: string;
}

interface ImagePreviewState {
    open: boolean;
    title: string;
    src: string;
    alt: string;
}

const getUnitKey = (unit: { id: number; nama: string }): string =>
    unit.nama.trim().toLowerCase().replace(/\s+/g, '_');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Berita Acara', href: '/berita-acara' },
    { title: 'Detail Berita Acara', href: '#' },
];

const peranLabelMap: Record<string, string> = {
    pcl_ppl: 'Petugas Lapangan',
    pml: 'Petugas Pemeriksa Lapangan',
    pcl: 'PCL',
    ppl: 'PPL',
    lapangan: 'Petugas Lapangan',
    pengolahan: 'Petugas Pengolahan',
    pengawas_pengolahan: 'Pemeriksa Pengolahan',
    pemeriksa_pengolahan: 'Pemeriksa Pengolahan',
};

function getLampiranBadge(status: LampiranItem['status']) {
    if (status === 'signed') {
        return <Badge variant="default">Signed</Badge>;
    }

    if (status === 'generated') {
        return <Badge variant="secondary">Draft</Badge>;
    }

    return <Badge variant="outline">Pending</Badge>;
}

export default function Show({
    bast,
    petugas,
    kegiatan,
    lampiran,
    bast_list,
    eligible_without_bast,
    permissions,
    summary,
    sensus_reference,
    mode = 'regular',
    bulan,
    tahun,
    bulan_label,
}: ShowProps) {
    usePage<SharedData>();
    const [uploadingTarget, setUploadingTarget] = useState<string | null>(null);
    const [modalAlert, setModalAlert] = useState<ModalAlertState>({
        open: false,
        title: '',
        message: '',
    });
    const [imagePreview, setImagePreview] = useState<ImagePreviewState>({
        open: false,
        title: '',
        src: '',
        alt: '',
    });
    const [sharedScreenshotPath, setSharedScreenshotPath] = useState<
        string | null
    >(sensus_reference?.fasih_screenshot_path ?? bast.fasih_screenshot_path);
    const isPreviewOnlyMode = !bast.hashed_id;
    const listScrollRef = useRef<HTMLDivElement>(null);
    const SCROLL_KEY = `bast-list-scroll-${bulan}-${tahun}`;
    const currentDetailUrl =
        window.location.pathname +
        window.location.search +
        window.location.hash;

    useEffect(() => {
        setSharedScreenshotPath(
            sensus_reference?.fasih_screenshot_path ??
                bast.fasih_screenshot_path,
        );
    }, [sensus_reference, bast.fasih_screenshot_path]);

    useEffect(() => {
        const saved = sessionStorage.getItem(SCROLL_KEY);
        if (saved && listScrollRef.current) {
            listScrollRef.current.scrollTop = parseInt(saved, 10);
        }
    }, [SCROLL_KEY]);

    const handleListLinkClick = () => {
        if (listScrollRef.current) {
            sessionStorage.setItem(
                SCROLL_KEY,
                String(listScrollRef.current.scrollTop),
            );
        }
    };

    const getCsrfToken = () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };

    const openImagePreview = (title: string, src: string, alt: string) => {
        setImagePreview({
            open: true,
            title,
            src,
            alt,
        });
    };

    const extractErrorMessage = async (response: Response): Promise<string> => {
        const fallbackMessage = `Server mengembalikan status ${response.status}.`;
        const contentType = response.headers.get('content-type') ?? '';

        if (contentType.includes('application/json')) {
            const parsed = (await response.json().catch(() => null)) as {
                message?: string;
                errors?: Record<string, string[]>;
            } | null;

            if (parsed?.message && parsed.message.trim() !== '') {
                return parsed.message;
            }

            if (parsed?.errors) {
                const firstFieldError = Object.values(parsed.errors)
                    .flat()
                    .find(
                        (value) =>
                            typeof value === 'string' && value.trim() !== '',
                    );

                if (firstFieldError) {
                    return firstFieldError;
                }
            }

            return fallbackMessage;
        }

        const rawText = await response.text().catch(() => '');
        if (rawText.trim() !== '') {
            try {
                const parsed = JSON.parse(rawText) as {
                    message?: string;
                    errors?: Record<string, string[]>;
                };

                if (parsed.message && parsed.message.trim() !== '') {
                    return parsed.message;
                }

                if (parsed.errors) {
                    const firstFieldError = Object.values(parsed.errors)
                        .flat()
                        .find(
                            (value) =>
                                typeof value === 'string' &&
                                value.trim() !== '',
                        );

                    if (firstFieldError) {
                        return firstFieldError;
                    }
                }
            } catch {
                // Keep fallback for non-JSON response body.
            }
        }

        return fallbackMessage;
    };

    const openDetailByPetugas = (petugasId?: number | null) => {
        handleListLinkClick();

        if (!petugasId) {
            return;
        }

        const state = encryptFilters({
            bulan,
            tahun,
            petugas_id: petugasId,
            mode,
        });

        router.get(
            '/berita-acara/open-detail',
            {
                state,
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const reloadDetailData = () => {
        router.get(
            window.location.pathname + window.location.search,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: [
                    'bast',
                    'lampiran',
                    'summary',
                    'bast_list',
                    'eligible_without_bast',
                    'sensus_reference',
                ],
            },
        );
    };

    const sortedBastList = [...bast_list].sort((left, right) =>
        left.petugas_nama.localeCompare(right.petugas_nama),
    );

    const finalSignedCount = sortedBastList.filter(
        (item) => item.signed_file_path,
    ).length;
    const overallProgress =
        sortedBastList.length > 0
            ? Math.round((finalSignedCount / sortedBastList.length) * 100)
            : 0;

    const allSignedInList =
        sortedBastList.length > 0 &&
        sortedBastList.every((item) => item.signed_file_path);
    const allCompiledInList =
        sortedBastList.length > 0 &&
        sortedBastList.every((item) => item.compiled_file_path);
    const activePetugasId = petugas?.id ?? null;
    const totalPetugasPeriod =
        sortedBastList.length + eligible_without_bast.length;
    const bastBelumGenerateCount = eligible_without_bast.length;
    const bastSudahGenerateCount = sortedBastList.filter((item) =>
        bast.is_legacy_mode
            ? Boolean(item.file_path || item.signed_file_path)
            : Boolean(item.file_path),
    ).length;
    const bastSudahTtdCount = sortedBastList.filter((item) =>
        bast.is_legacy_mode
            ? Boolean(item.signed_file_path)
            : Boolean(item.main_signed_file_path),
    ).length;
    const lampiranSudahLengkapCount = sortedBastList.filter((item) =>
        bast.is_legacy_mode
            ? Boolean(item.file_path)
            : Boolean(item.compiled_file_path),
    ).length;
    const lampiranBelumLengkapCount = Math.max(
        totalPetugasPeriod - lampiranSudahLengkapCount,
        0,
    );
    const canDownloadAll = bast.is_legacy_mode
        ? allSignedInList
        : allCompiledInList;
    const isSensusEkonomi = Boolean(bast.is_sensus_ekonomi);
    const sensusTitle = `Sensus Ekonomi ${kegiatan.tahun_anggaran}`;
    const pageTitle = isSensusEkonomi
        ? `Detail BAST ${sensusTitle}`
        : `Detail BAST ${bulan_label} ${tahun}`;
    const pageDescription = isSensusEkonomi
        ? `Dokumen utama dan lampiran BAST Sensus Ekonomi untuk ${petugas?.nama ?? kegiatan.nama_kegiatan}`
        : `Dokumen utama dan lampiran BAST kegiatan untuk ${petugas?.nama ?? kegiatan.nama_kegiatan}`;
    const mainBastGenerated = Boolean(bast.file_path);
    const mainBastSignedUploaded = Boolean(summary.main_signed_uploaded);

    const showPreviewError = (error: unknown) => {
        const fallback =
            'Preview lampiran gagal dibuka. Pastikan screenshot Fasih sudah diunggah dan kegiatan sudah berakhir.';
        const message =
            error instanceof Error && error.message.trim() !== ''
                ? error.message
                : fallback;

        showModalAlert('Preview Lampiran Gagal', message);
    };

    const handleDownloadAll = async () => {
        const fallbackUrl = `/berita-acara/download-all?bulan=${bulan}&tahun=${tahun}`;

        if (permissions.is_ketua_tim) {
            // Ketua tim: always hit backend (user-specific filtered ZIP, no static cache)
            window.location.href = fallbackUrl;
            return;
        }

        const filename = constructBastDownloadFilename(
            bulan,
            tahun,
            bast.is_legacy_mode,
        );
        await tryDirectDownload(filename, fallbackUrl);
    };

    const handleUploadMainSigned = (
        event: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        setUploadingTarget('main');

        router.post(
            `/berita-acara/${bast.hashed_id}/upload-signed`,
            { file },
            {
                preserveScroll: true,
                onSuccess: reloadDetailData,
                onFinish: () => setUploadingTarget(null),
            },
        );
    };

    const handleGenerateDownloadLampiran = async (item: LampiranItem) => {
        if (!item.ready_to_generate && !item.file_path) {
            return;
        }

        if (!bast.hashed_id && !item.preview_spk_id) {
            return;
        }

        try {
            const payload = bast.hashed_id
                ? {
                      bast_hashed_id: bast.hashed_id,
                      bast_kegiatan_id: item.id,
                  }
                : {
                      encrypted_filters: encryptFilters({
                          spk_id: item.preview_spk_id,
                          kegiatan_id: item.kegiatan_id,
                          periode_alokasi_id: item.periode_alokasi_id,
                      }),
                  };

            const formData = new FormData();
            const csrfToken = getCsrfToken();
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }

            Object.entries(payload).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    formData.append(key, String(value));
                }
            });

            const response = await fetch(
                '/berita-acara/lampiran-action/download',
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/pdf,application/json',
                    },
                    credentials: 'include',
                    body: formData,
                },
            );

            if (!response.ok) {
                const message = await extractErrorMessage(response);
                throw new Error(message);
            }

            const blob = await response.blob();
            const blobUrl = window.URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = blobUrl;
            anchor.download = `LAMPIRAN_${item.kode_kegiatan}.pdf`;
            anchor.rel = 'noopener noreferrer';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            window.setTimeout(() => window.URL.revokeObjectURL(blobUrl), 2000);

            reloadDetailData();
        } catch (error) {
            const message =
                error instanceof Error && error.message.trim() !== ''
                    ? error.message
                    : 'Unduh lampiran gagal. Silakan coba lagi.';

            showModalAlert('Unduh Lampiran Gagal', message);
        }
    };

    const handlePreviewLampiran = async (item: LampiranItem) => {
        if (
            item.uses_fasih_screenshot &&
            !(item.fasih_screenshot_path ?? sharedScreenshotPath)
        ) {
            showModalAlert(
                'Preview Lampiran Dinonaktifkan',
                'Preview dinonaktifkan karena screenshot Fasih belum diunggah.',
            );

            return;
        }

        if (!bast.hashed_id && !item.preview_spk_id) {
            return;
        }

        try {
            await previewFileFromPost(
                '/berita-acara/lampiran-action/preview',
                bast.hashed_id
                    ? {
                          bast_hashed_id: bast.hashed_id,
                          bast_kegiatan_id: item.id,
                      }
                    : {
                          encrypted_filters: encryptFilters({
                              spk_id: item.preview_spk_id,
                              kegiatan_id: item.kegiatan_id,
                              periode_alokasi_id: item.periode_alokasi_id,
                          }),
                      },
                `Preview_Lampiran_${item.kode_kegiatan}.pdf`,
            );
        } catch (error) {
            showPreviewError(error);
        }
    };

    const handlePreviewSharedScreenshot = () => {
        if (!sharedScreenshotPath) {
            return;
        }

        openImagePreview(
            'Screenshot Fasih',
            `../storage/${sharedScreenshotPath}`,
            'Screenshot Fasih BAST',
        );
    };

    const handleUploadLampiranSigned = (
        item: LampiranItem,
        event: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        setUploadingTarget(`lampiran-${item.id}`);

        if (!bast.hashed_id && !item.preview_spk_id) {
            setUploadingTarget(null);

            return;
        }

        const uploadPayload = bast.hashed_id
            ? {
                  bast_hashed_id: bast.hashed_id,
                  bast_kegiatan_id: item.id,
                  redirect_url: currentDetailUrl,
                  file,
              }
            : {
                  spk_id: item.preview_spk_id,
                  kegiatan_id: item.kegiatan_id,
                  periode_alokasi_id: item.periode_alokasi_id,
                  kode_kegiatan: item.kode_kegiatan,
                  redirect_url: currentDetailUrl,
                  file,
              };

        router.post(
            '/berita-acara/lampiran-action/upload-signed',
            uploadPayload,
            {
                preserveScroll: true,
                onSuccess: reloadDetailData,
                onFinish: () => setUploadingTarget(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                <PageHeader title={pageTitle} description={pageDescription}>
                    <div className="flex items-center gap-2">
                        {(permissions.can_manage_main ||
                            permissions.is_ketua_tim) && (
                            <Button
                                variant="outline"
                                onClick={handleDownloadAll}
                                disabled={!canDownloadAll}
                                title={
                                    !canDownloadAll
                                        ? bast.is_legacy_mode
                                            ? 'Semua BAST di daftar harus sudah bertanda tangan'
                                            : 'Semua BAST di daftar harus sudah dikompilasi'
                                        : undefined
                                }
                            >
                                <FolderDown className="mr-2 h-4 w-4" />
                                Download Semua
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={`/berita-acara`}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Kembali
                            </Link>
                        </Button>
                    </div>
                </PageHeader>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <ContentCard className="border border-blue-200/70 bg-gradient-to-br from-blue-50 to-white dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                        <div className="flex items-center justify-between">
                            <p className="text-xs font-medium tracking-wide text-blue-700 uppercase dark:text-blue-300">
                                BAST [generate]
                            </p>
                            <FileText className="h-4 w-4 text-blue-600 dark:text-blue-300" />
                        </div>
                        <p className="mt-3 text-3xl font-bold text-blue-900 dark:text-blue-100">
                            {bastSudahGenerateCount}/{totalPetugasPeriod}
                        </p>
                        <p className="mt-2 text-xs text-blue-700 dark:text-blue-300">
                            Jumlah petugas yang dokumen utama BAST-nya sudah
                            digenerate.
                        </p>
                    </ContentCard>

                    <ContentCard className="border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                        <div className="flex items-center justify-between">
                            <p className="text-xs font-medium tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                                BAST [bertanda tangan]
                            </p>
                            <PenLine className="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                        </div>
                        <p className="mt-3 text-3xl font-bold text-emerald-900 dark:text-emerald-100">
                            {bastSudahTtdCount}/{totalPetugasPeriod}
                        </p>
                        <p className="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                            Jumlah petugas yang file utama BAST bertanda
                            tangannya sudah diunggah.
                        </p>
                    </ContentCard>

                    <ContentCard className="border border-slate-200/70 bg-gradient-to-br from-slate-50 to-white dark:border-slate-800 dark:from-slate-900/60 dark:to-neutral-900">
                        <div className="flex items-center justify-between">
                            <p className="text-xs font-medium tracking-wide text-slate-700 uppercase dark:text-slate-300">
                                BAST [belum generate]
                            </p>
                            <FileText className="h-4 w-4 text-slate-600 dark:text-slate-300" />
                        </div>
                        <p className="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">
                            {bastBelumGenerateCount}/{totalPetugasPeriod}
                        </p>
                        <p className="mt-2 text-xs text-slate-700 dark:text-slate-300">
                            Jumlah petugas yang belum memiliki dokumen BAST pada
                            periode ini.
                        </p>
                    </ContentCard>

                    <ContentCard className="border border-amber-200/70 bg-gradient-to-br from-amber-50 to-white dark:border-amber-900/40 dark:from-amber-950/30 dark:to-neutral-900">
                        <div className="flex items-center justify-between">
                            <p className="text-xs font-medium tracking-wide text-amber-700 uppercase dark:text-amber-300">
                                Lampiran [belum lengkap]
                            </p>
                            <Clock3 className="h-4 w-4 text-amber-600 dark:text-amber-300" />
                        </div>
                        <p className="mt-3 text-3xl font-bold text-amber-900 dark:text-amber-100">
                            {lampiranBelumLengkapCount}/{totalPetugasPeriod}
                        </p>
                        <p className="mt-2 text-xs text-amber-700 dark:text-amber-300">
                            Jumlah petugas yang lampirannya belum tergenerate
                            lengkap.
                        </p>
                    </ContentCard>

                    <ContentCard className="border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                        <div className="flex items-center justify-between">
                            <p className="text-xs font-medium tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                                Lampiran [sudah lengkap]
                            </p>
                            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                        </div>
                        <p className="mt-3 text-3xl font-bold text-emerald-900 dark:text-emerald-100">
                            {lampiranSudahLengkapCount}/{totalPetugasPeriod}
                        </p>
                        <p className="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                            Jumlah petugas yang seluruh lampirannya sudah
                            tergenerate.
                        </p>
                    </ContentCard>
                </div>

                {!bast.is_legacy_mode && (
                    <ContentCard>
                        <div className="space-y-4">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        {isSensusEkonomi
                                            ? `Progress BAST ${sensusTitle}`
                                            : `Progres BAST Periode ${bulan_label} ${tahun}`}
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {finalSignedCount} dari{' '}
                                        {sortedBastList.length} BAST sudah
                                        memiliki file BAST bertanda tangan.
                                    </p>
                                </div>
                                <Badge variant="outline">
                                    {overallProgress}% selesai
                                </Badge>
                            </div>
                            <div className="h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                                <div
                                    className="h-2 rounded-full bg-emerald-500 transition-all"
                                    style={{ width: `${overallProgress}%` }}
                                />
                            </div>
                        </div>
                    </ContentCard>
                )}

                <div className="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <div className="space-y-6 lg:sticky lg:top-6 lg:self-start">
                        <ContentCard>
                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Daftar BAST
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {isSensusEkonomi
                                            ? `Daftar Petugas ${sensusTitle}`
                                            : `Daftar Petugas Survei periode ${bulan_label} ${tahun}`}
                                    </p>
                                </div>
                                <div
                                    ref={listScrollRef}
                                    className="max-h-[calc(100vh-16rem)] space-y-2 overflow-y-auto pr-1"
                                >
                                    {sortedBastList.map((item) => (
                                        <button
                                            type="button"
                                            key={item.id}
                                            onClick={() =>
                                                openDetailByPetugas(
                                                    item.petugas_id,
                                                )
                                            }
                                            className={`block h-auto w-full cursor-pointer rounded-xl border p-4 text-left transition-colors ${
                                                item.is_current ||
                                                (activePetugasId !== null &&
                                                    item.petugas_id ===
                                                        activePetugasId)
                                                    ? 'border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800'
                                                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            <div className="space-y-1">
                                                <div className="font-medium text-neutral-900 dark:text-white">
                                                    {item.petugas_nama}
                                                </div>
                                                <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {item.nomor_bast}
                                                </div>
                                                <div className="flex flex-wrap gap-2 pt-1">
                                                    {item.signed_file_path ? (
                                                        <Badge variant="default">
                                                            Dokumen Lengkap
                                                        </Badge>
                                                    ) : item.main_signed_file_path ? (
                                                        <Badge variant="secondary">
                                                            BAST Bertanda Tangan
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline">
                                                            Draft
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </button>
                                    ))}

                                    {eligible_without_bast.map((item, idx) => {
                                        const isPendingCurrent =
                                            activePetugasId !== null &&
                                            item.petugas_id === activePetugasId;

                                        const content = (
                                            <div className="space-y-1">
                                                <div className="font-medium text-neutral-900 dark:text-white">
                                                    {item.petugas_nama}
                                                </div>
                                                <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                    -
                                                </div>
                                                <div className="flex pt-1">
                                                    <Badge variant="outline">
                                                        Belum ada BAST
                                                    </Badge>
                                                </div>
                                            </div>
                                        );

                                        if (item.petugas_id) {
                                            return (
                                                <button
                                                    type="button"
                                                    key={`pending-${idx}`}
                                                    onClick={() =>
                                                        openDetailByPetugas(
                                                            item.petugas_id,
                                                        )
                                                    }
                                                    className={`block h-auto w-full cursor-pointer rounded-xl border p-4 text-left transition-colors ${
                                                        isPendingCurrent
                                                            ? 'border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800'
                                                            : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                                    }`}
                                                >
                                                    {content}
                                                </button>
                                            );
                                        }

                                        return (
                                            <div
                                                key={`pending-${idx}`}
                                                className="h-auto w-full rounded-xl border border-neutral-200 p-4 text-left dark:border-neutral-700"
                                            >
                                                {content}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </ContentCard>
                    </div>

                    <div className="space-y-6">
                        {!bast.is_legacy_mode && (
                            <ContentCard>
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                            Status Petugas Terpilih
                                        </h3>
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Ringkasan progres dokumen untuk
                                            petugas yang sedang dipilih.
                                        </p>
                                    </div>

                                    <div className="grid gap-3 md:grid-cols-2">
                                        <div className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                                                    BAST [Generate]
                                                </p>
                                                <FileText className="h-4 w-4 text-neutral-500" />
                                            </div>
                                            <div className="mt-2">
                                                <Badge
                                                    variant={
                                                        mainBastGenerated
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {mainBastGenerated
                                                        ? 'Sudah Digenerate'
                                                        : 'Belum Digenerate'}
                                                </Badge>
                                            </div>
                                        </div>

                                        <div className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                                                    BAST Bertanda Tangan
                                                </p>
                                                <PenLine className="h-4 w-4 text-neutral-500" />
                                            </div>
                                            <div className="mt-2">
                                                <Badge
                                                    variant={
                                                        mainBastSignedUploaded
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {mainBastSignedUploaded
                                                        ? 'Sudah Diunggah'
                                                        : 'Menunggu Upload'}
                                                </Badge>
                                            </div>
                                        </div>

                                        <div className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                                                    Lampiran [Generate]
                                                </p>
                                                <FileArchive className="h-4 w-4 text-neutral-500" />
                                            </div>
                                            <p className="mt-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                                {summary.generated_lampiran}/
                                                {summary.total_lampiran}
                                            </p>
                                        </div>

                                        <div className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                                                    Lampiran Bertanda Tangan
                                                </p>
                                                <FileCheck2 className="h-4 w-4 text-neutral-500" />
                                            </div>
                                            <p className="mt-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                                {summary.signed_lampiran}/
                                                {summary.total_lampiran}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </ContentCard>
                        )}

                        <ContentCard>
                            <div className="space-y-6">
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Dokumen Utama BAST
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            Nomor {bast.nomor_bast}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label>Nama Petugas</Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {petugas?.nama ?? '-'}
                                        </p>
                                    </div>
                                    {petugas?.nik && (
                                        <div>
                                            <Label>NIK</Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {petugas.nik}
                                            </p>
                                        </div>
                                    )}
                                    {petugas?.alamat && (
                                        <div>
                                            <Label>Alamat</Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {petugas.alamat}
                                            </p>
                                        </div>
                                    )}
                                    <div>
                                        <Label>Tanggal Serah Terima</Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {bast.tanggal_serah_terima}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                                    {(bast.file_path ||
                                        (bast.is_legacy_mode &&
                                            bast.signed_file_path)) && (
                                        <Button
                                            onClick={() =>
                                                openFastDownload(
                                                    bast.is_legacy_mode &&
                                                        bast.signed_file_path
                                                        ? `/berita-acara/${bast.hashed_id}/download-signed`
                                                        : `/berita-acara/${bast.hashed_id}/download`,
                                                )
                                            }
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Download BAST
                                        </Button>
                                    )}

                                    {!bast.is_legacy_mode &&
                                        bast.compiled_file_path &&
                                        summary.all_lampiran_generated && (
                                            <Button
                                                variant="outline"
                                                onClick={() =>
                                                    openFastDownload(
                                                        `/berita-acara/${bast.hashed_id}/download-compiled`,
                                                    )
                                                }
                                            >
                                                <FileArchive className="mr-2 h-4 w-4" />
                                                Download Gabungan
                                            </Button>
                                        )}

                                    {summary.final_signed_ready &&
                                        bast.signed_file_path &&
                                        !bast.is_legacy_mode && (
                                            <Button
                                                variant="outline"
                                                onClick={() =>
                                                    openFastDownload(
                                                        `/berita-acara/${bast.hashed_id}/download-signed`,
                                                    )
                                                }
                                            >
                                                <Download className="mr-2 h-4 w-4" />
                                                Download Gabungan Bertanda
                                                Tangan
                                            </Button>
                                        )}

                                    {bast.is_legacy_mode &&
                                        summary.final_signed_ready &&
                                        bast.signed_file_path && (
                                            <Button
                                                variant="outline"
                                                onClick={() =>
                                                    openFastDownload(
                                                        `/berita-acara/${bast.hashed_id}/download-signed`,
                                                    )
                                                }
                                            >
                                                <Download className="mr-2 h-4 w-4" />
                                                Download Gabungan Bertanda
                                                Tangan
                                            </Button>
                                        )}
                                </div>

                                {!summary.final_signed_ready && (
                                    <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900/50 dark:text-neutral-400">
                                        Download BAST + Lampiran Bertanda Tangan
                                        baru muncul setelah semua berkas
                                        bertanda tangan selesai diunggah.
                                    </div>
                                )}
                            </div>
                        </ContentCard>

                        {permissions.can_upload_main && bast.file_path && (
                            <ContentCard>
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Upload BAST Bertanda Tangan
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            Unggah PDF BAST yang sudah
                                            ditandatangani. File final bertanda
                                            tangan akan disusun otomatis setelah
                                            semua lampiran bertanda tangan
                                            tersedia.
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-3">
                                        <Label
                                            htmlFor="main-signed-file"
                                            className="inline-flex h-11 cursor-pointer items-center justify-center gap-2.5 rounded-xl border-2 border-input bg-white/50 px-5 text-base font-semibold shadow-lg backdrop-blur-sm transition-[color,box-shadow,transform] hover:border-accent-foreground/20 hover:bg-accent hover:text-accent-foreground hover:shadow-xl active:scale-[0.98] dark:bg-neutral-800/60"
                                        >
                                            <Upload className="size-5 shrink-0" />
                                            {uploadingTarget === 'main'
                                                ? 'Mengunggah...'
                                                : bast.main_signed_file_path
                                                  ? 'Ganti File BAST Bertanda Tangan'
                                                  : 'Pilih File BAST Bertanda Tangan'}
                                        </Label>
                                        <Input
                                            id="main-signed-file"
                                            type="file"
                                            accept="application/pdf"
                                            onChange={handleUploadMainSigned}
                                            className="hidden"
                                        />
                                        {bast.main_signed_file_path && (
                                            <div className="inline-flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                                                <CheckCircle2 className="h-4 w-4" />
                                                File BAST bertanda tangan
                                                tersimpan
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </ContentCard>
                        )}

                        {permissions.can_upload_main &&
                            bast.is_sensus_ekonomi &&
                            petugas &&
                            sensus_reference && (
                                <ContentCard>
                                    <div className="space-y-4">
                                        <div>
                                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                                Data Realisasi & Screenshot
                                                Fasih (dari BAPP SE2026)
                                            </h3>
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                Data realisasi diambil dari
                                                penjumlahan BAPP Termin I dan
                                                II. BAST hanya dapat di-generate
                                                setelah BAPP Termin II
                                                diselesaikan.
                                            </p>
                                        </div>

                                        {sensus_reference.bapp_termin_ii_complete ===
                                            false && (
                                            <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                                                <AlertCircle className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                                                <p className="text-sm text-amber-800 dark:text-amber-300">
                                                    BAST Sensus Ekonomi hanya
                                                    bisa di-generate setelah
                                                    BAPP Termin II diselesaikan
                                                    (realisasi + screenshot
                                                    Fasih lengkap).
                                                </p>
                                            </div>
                                        )}

                                        <div className="grid gap-4 md:grid-cols-2">
                                            {sensus_reference.target_sls !=
                                                null && (
                                                <div>
                                                    <Label>
                                                        Realisasi SLS/sub-SLS
                                                    </Label>
                                                    <Input
                                                        value={
                                                            sensus_reference.target_sls
                                                        }
                                                        readOnly
                                                        disabled
                                                    />
                                                </div>
                                            )}
                                            {sensus_reference.unit_sampel_pencacahan_items.map(
                                                (unit) => {
                                                    const unitKey =
                                                        getUnitKey(unit);
                                                    const realisasi =
                                                        sensus_reference
                                                            .realisasi_unit_sampel?.[
                                                            unitKey
                                                        ] ?? '-';

                                                    return (
                                                        <div key={unit.id}>
                                                            <Label>
                                                                Realisasi (
                                                                {unit.nama})
                                                            </Label>
                                                            <Input
                                                                value={
                                                                    realisasi
                                                                }
                                                                readOnly
                                                                disabled
                                                            />
                                                        </div>
                                                    );
                                                },
                                            )}
                                        </div>

                                        {sharedScreenshotPath && (
                                            <div className="flex flex-wrap items-center gap-3">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    className="inline-flex items-center gap-2 px-0 text-sm font-medium text-blue-600 hover:bg-transparent hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                                    onClick={
                                                        handlePreviewSharedScreenshot
                                                    }
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    Lihat Screenshot Fasih
                                                    (Termin II)
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </ContentCard>
                            )}

                        {!bast.is_legacy_mode && (
                            <ContentCard>
                                <div className="space-y-5">
                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                                Daftar Lampiran per Kegiatan
                                            </h3>
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                {permissions.is_ketua_tim
                                                    ? 'Daftar ini hanya menampilkan kegiatan yang Anda kelola sebagai ketua tim.'
                                                    : 'Ketua tim hanya dapat generate dan upload signed untuk kegiatan yang menjadi tanggung jawabnya.'}
                                            </p>
                                        </div>
                                        <Badge variant="outline">
                                            {summary.generated_lampiran}/
                                            {summary.total_lampiran} generated
                                        </Badge>
                                    </div>

                                    {lampiran.length === 0 ? (
                                        <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900/40 dark:text-neutral-400">
                                            BAST ini belum memiliki data
                                            lampiran per kegiatan.
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {lampiran.map((item) => {
                                                const uploadId = `lampiran-signed-${item.id}`;
                                                const isUploadingThis =
                                                    uploadingTarget ===
                                                    `lampiran-${item.id}`;
                                                const requiresFasihScreenshot =
                                                    Boolean(
                                                        item.uses_fasih_screenshot,
                                                    );
                                                const hasFasihScreenshot =
                                                    Boolean(
                                                        item.fasih_screenshot_path ??
                                                        sharedScreenshotPath,
                                                    );
                                                const canPreviewLampiran =
                                                    item.can_preview &&
                                                    (!requiresFasihScreenshot ||
                                                        hasFasihScreenshot);
                                                const pendingLampiranMessage =
                                                    requiresFasihScreenshot &&
                                                    !hasFasihScreenshot
                                                        ? 'Lampiran baru dapat digenerate setelah screenshot Fasih utama diunggah dan kegiatan berakhir.'
                                                        : 'Lampiran baru dapat digenerate setelah kegiatan berakhir.';
                                                const previewDisabledReason =
                                                    requiresFasihScreenshot &&
                                                    !hasFasihScreenshot
                                                        ? 'Preview dinonaktifkan karena screenshot Fasih belum diunggah.'
                                                        : undefined;

                                                return (
                                                    <div
                                                        key={item.id}
                                                        className="rounded-2xl border border-neutral-200 p-5 dark:border-neutral-700"
                                                    >
                                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                                            <div className="space-y-1">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <h4 className="text-base font-semibold text-neutral-900 dark:text-white">
                                                                        {
                                                                            item.nama_kegiatan
                                                                        }
                                                                    </h4>
                                                                    {getLampiranBadge(
                                                                        item.status,
                                                                    )}
                                                                </div>
                                                                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                                                    {' '}
                                                                    {peranLabelMap[
                                                                        item.peran ??
                                                                            ''
                                                                    ] ??
                                                                        item.peran ??
                                                                        'Tanpa peran'}{' '}
                                                                    •{' '}
                                                                    {
                                                                        item.tanggal_selesai_formatted
                                                                    }
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div className="mt-4 flex flex-wrap gap-3">
                                                            <Button
                                                                variant="outline"
                                                                disabled={
                                                                    !canPreviewLampiran
                                                                }
                                                                title={
                                                                    previewDisabledReason
                                                                }
                                                                onClick={() =>
                                                                    void handlePreviewLampiran(
                                                                        item,
                                                                    )
                                                                }
                                                            >
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                Preview Lampiran
                                                            </Button>

                                                            <Button
                                                                variant="outline"
                                                                disabled={
                                                                    !item.can_download
                                                                }
                                                                onClick={() =>
                                                                    void handleGenerateDownloadLampiran(
                                                                        item,
                                                                    )
                                                                }
                                                            >
                                                                <Download className="mr-2 h-4 w-4" />
                                                                Unduh Lampiran
                                                            </Button>

                                                            {item.can_upload_signed && (
                                                                <>
                                                                    <Label
                                                                        htmlFor={
                                                                            uploadId
                                                                        }
                                                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2.5 rounded-xl border-2 border-input bg-white/50 px-5 text-base font-semibold shadow-lg backdrop-blur-sm transition-[color,box-shadow,transform] hover:border-accent-foreground/20 hover:bg-accent hover:text-accent-foreground hover:shadow-xl active:scale-[0.98] dark:bg-neutral-800/60"
                                                                    >
                                                                        <Upload className="size-5 shrink-0" />
                                                                        {isUploadingThis
                                                                            ? 'Mengunggah...'
                                                                            : item.signed_file_path
                                                                              ? 'Ganti Lampiran Bertanda Tangan'
                                                                              : 'Unggah Lampiran Bertanda Tangan'}
                                                                    </Label>
                                                                    <Input
                                                                        id={
                                                                            uploadId
                                                                        }
                                                                        type="file"
                                                                        accept="application/pdf"
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            handleUploadLampiranSigned(
                                                                                item,
                                                                                event,
                                                                            )
                                                                        }
                                                                        className="hidden"
                                                                    />
                                                                </>
                                                            )}

                                                            {!item.can_upload_signed &&
                                                                isPreviewOnlyMode && (
                                                                    <Button
                                                                        variant="outline"
                                                                        disabled
                                                                    >
                                                                        <Upload className="mr-2 h-4 w-4" />
                                                                        Unggah
                                                                        Lampiran
                                                                    </Button>
                                                                )}
                                                        </div>

                                                        {!item.ready_to_generate &&
                                                            item.status ===
                                                                'pending' && (
                                                                <div className="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                                    <Clock3 className="h-4 w-4" />
                                                                    {
                                                                        pendingLampiranMessage
                                                                    }
                                                                </div>
                                                            )}

                                                        {(item.generated_at ||
                                                            item.signed_uploaded_at) && (
                                                            <div className="mt-4 flex flex-wrap gap-4 text-xs text-neutral-500 dark:text-neutral-400">
                                                                {item.generated_at && (
                                                                    <span>
                                                                        Digenerate:{' '}
                                                                        {
                                                                            item.generated_at
                                                                        }
                                                                    </span>
                                                                )}
                                                                {item.signed_uploaded_at && (
                                                                    <span>
                                                                        Signed
                                                                        diunggah:{' '}
                                                                        {
                                                                            item.signed_uploaded_at
                                                                        }
                                                                    </span>
                                                                )}
                                                                {item.fasih_screenshot_uploaded_at && (
                                                                    <span>
                                                                        Screenshot
                                                                        diunggah:{' '}
                                                                        {
                                                                            item.fasih_screenshot_uploaded_at
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </ContentCard>
                        )}
                    </div>
                </div>
            </div>

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
                open={imagePreview.open}
                onOpenChange={(open) =>
                    setImagePreview((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>{imagePreview.title}</DialogTitle>
                        <DialogDescription>
                            Pratinjau screenshot Fasih.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="max-h-[70vh] overflow-auto rounded-lg border border-neutral-200 bg-neutral-50 p-2 dark:border-neutral-700 dark:bg-neutral-900">
                        {imagePreview.src && (
                            <img
                                src={imagePreview.src}
                                alt={imagePreview.alt}
                                className="mx-auto max-h-[65vh] w-full object-contain"
                            />
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                setImagePreview((prev) => ({
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
        </AppLayout>
    );
}
