import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import {
    constructBastDownloadFilename,
    downloadFileFromPost,
    openFastDownload,
    previewFileFromPost,
    tryDirectDownload,
} from '@/utils/downloadUtils';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
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
    generated_at: string | null;
    signed_uploaded_at: string | null;
    status: 'pending' | 'generated' | 'signed';
    can_download: boolean;
    can_generate: boolean;
    can_upload_signed: boolean;
    can_preview: boolean;
    ready_to_generate: boolean;
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
    bulan: number;
    tahun: number;
    bulan_label: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Detail BAST', href: '#' },
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
    bulan,
    tahun,
    bulan_label,
}: ShowProps) {
    usePage<SharedData>();
    const [uploadingTarget, setUploadingTarget] = useState<string | null>(null);
    const isPreviewOnlyMode = !bast.hashed_id;
    const listScrollRef = useRef<HTMLDivElement>(null);
    const SCROLL_KEY = `bast-list-scroll-${bulan}-${tahun}`;
    const currentDetailUrl =
        window.location.pathname +
        window.location.search +
        window.location.hash;

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

    const openDetailByPetugas = (petugasId?: number | null) => {
        handleListLinkClick();

        if (!petugasId) {
            return;
        }

        const state = encryptFilters({
            bulan,
            tahun,
            petugas_id: petugasId,
        });

        router.get(
            '/bast/open-detail',
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
    const canDownloadAll = bast.is_legacy_mode
        ? allSignedInList
        : allCompiledInList;

    const handleDownloadAll = async () => {
        const fallbackUrl = `/bast/download-all?bulan=${bulan}&tahun=${tahun}`;

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
            `/bast/${bast.hashed_id}/upload-signed`,
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

        await downloadFileFromPost(
            '/bast/lampiran-action/download',
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
            `LAMPIRAN_${item.kode_kegiatan}.pdf`,
        );

        reloadDetailData();
    };

    const handlePreviewLampiran = async (item: LampiranItem) => {
        if (!bast.hashed_id && !item.preview_spk_id) {
            return;
        }

        await previewFileFromPost(
            '/bast/lampiran-action/preview',
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

        router.post('/bast/lampiran-action/upload-signed', uploadPayload, {
            preserveScroll: true,
            onSuccess: reloadDetailData,
            onFinish: () => setUploadingTarget(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail BAST`} />

            <div className="space-y-6">
                <PageHeader
                    title={`Detail BAST ${bulan_label} ${tahun}`}
                    description={`Dokumen utama dan lampiran kegiatan untuk ${petugas?.nama ?? kegiatan.nama_kegiatan}`}
                >
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
                            <Link href={`/bast`}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Kembali
                            </Link>
                        </Button>
                    </div>
                </PageHeader>

                {!bast.is_legacy_mode && (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <ContentCard className="border border-blue-200/70 bg-gradient-to-br from-blue-50 to-white dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-blue-700 uppercase dark:text-blue-300">
                                    BAST [Generate]
                                </p>
                                <FileText className="h-4 w-4 text-blue-600 dark:text-blue-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-blue-900 dark:text-blue-100">
                                {bast.file_path ? '1' : '0'}
                            </p>
                            <p className="mt-2 text-xs text-blue-700 dark:text-blue-300">
                                {bast.file_path
                                    ? 'Dokumen utama sudah tersedia'
                                    : 'Dokumen utama belum tersedia'}
                            </p>
                        </ContentCard>

                        <ContentCard className="border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                                    BAST Bertanda Tangan
                                </p>
                                <PenLine className="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-emerald-900 dark:text-emerald-100">
                                {summary.main_signed_uploaded ? '1' : '0'}
                            </p>
                            <p className="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                                {summary.main_signed_uploaded
                                    ? 'File BAST bertanda tangan sudah diunggah'
                                    : 'Menunggu upload BAST bertanda tangan'}
                            </p>
                        </ContentCard>

                        <ContentCard className="border border-amber-200/70 bg-gradient-to-br from-amber-50 to-white dark:border-amber-900/40 dark:from-amber-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-amber-700 uppercase dark:text-amber-300">
                                    Lampiran [Generate]
                                </p>
                                <FileArchive className="h-4 w-4 text-amber-600 dark:text-amber-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-amber-900 dark:text-amber-100">
                                {summary.generated_lampiran}/
                                {summary.total_lampiran}
                            </p>
                            <p className="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                {summary.all_lampiran_generated
                                    ? 'Semua lampiran sudah siap'
                                    : 'Masih ada lampiran yang belum digenerate'}
                            </p>
                        </ContentCard>

                        <ContentCard className="border border-violet-200/70 bg-gradient-to-br from-violet-50 to-white dark:border-violet-900/40 dark:from-violet-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-violet-700 uppercase dark:text-violet-300">
                                    Lampiran Bertanda Tangan
                                </p>
                                <FileCheck2 className="h-4 w-4 text-violet-600 dark:text-violet-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-violet-900 dark:text-violet-100">
                                {summary.signed_lampiran}/
                                {summary.total_lampiran}
                            </p>
                            <p className="mt-2 text-xs text-violet-700 dark:text-violet-300">
                                {summary.final_signed_ready
                                    ? 'File BAST bertanda tangan siap diunduh'
                                    : 'Kompilasi BAST bertanda tangan belum lengkap'}
                            </p>
                        </ContentCard>
                    </div>
                )}

                {!bast.is_legacy_mode && (
                    <ContentCard>
                        <div className="space-y-4">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Progres Periode {bulan_label} {tahun}
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
                                        Dokumen periode {bulan_label} {tahun}
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
                                                            Signed
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
                                                        ? `/bast/${bast.hashed_id}/download-signed`
                                                        : `/bast/${bast.hashed_id}/download`,
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
                                                        `/bast/${bast.hashed_id}/download-compiled`,
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
                                                        `/bast/${bast.hashed_id}/download-signed`,
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
                                                        `/bast/${bast.hashed_id}/download-signed`,
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
                                                                <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                                    {
                                                                        item.kode_kegiatan
                                                                    }
                                                                    {item.peran
                                                                        ? ` • ${peranLabelMap[item.peran] ?? item.peran}`
                                                                        : ''}
                                                                </div>
                                                            </div>
                                                            <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                                                Berakhir:{' '}
                                                                {
                                                                    item.tanggal_selesai_formatted
                                                                }
                                                            </div>
                                                        </div>

                                                        <div className="mt-4 grid gap-4 md:grid-cols-3">
                                                            <div>
                                                                <Label>
                                                                    Jenis
                                                                    Kegiatan
                                                                </Label>
                                                                <p className="font-medium text-neutral-900 capitalize dark:text-white">
                                                                    {
                                                                        item.jenis_kegiatan
                                                                    }
                                                                </p>
                                                            </div>
                                                            <div>
                                                                <Label>
                                                                    Ketua Tim
                                                                </Label>
                                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                                    {item.ketua_tim_nama ??
                                                                        '-'}
                                                                </p>
                                                            </div>
                                                            <div>
                                                                <Label>
                                                                    Status
                                                                    Dokumen
                                                                </Label>
                                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                                    {item.status ===
                                                                    'signed'
                                                                        ? 'Signed'
                                                                        : item.status ===
                                                                            'generated'
                                                                          ? 'Draft tersedia'
                                                                          : 'Belum digenerate'}
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div className="mt-4 flex flex-wrap gap-3">
                                                            <Button
                                                                variant="outline"
                                                                disabled={
                                                                    !item.can_preview
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
                                                                    Lampiran
                                                                    baru dapat
                                                                    digenerate
                                                                    setelah
                                                                    kegiatan
                                                                    berakhir.
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
        </AppLayout>
    );
}
