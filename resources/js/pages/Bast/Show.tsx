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
    openFastDownload,
    tryDirectDownload,
} from '@/utils/downloadUtils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    Download,
    FileText,
    FolderDown,
    PenLine,
    Upload,
} from 'lucide-react';
import { useState } from 'react';

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
    signed_file_path: string | null;
    lokasi_kegiatan: string | null;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    catatan: string | null;
    created_by: string;
    created_at: string;
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

interface BastPetugas {
    id: number;
    petugas_id: number;
    petugas_nama: string;
    nomor_spk: string;
    hasil_listing: number | null;
    satuan_listing: string | null;
    hasil_pendataan_lapangan: number | null;
    satuan_pendataan_lapangan: string | null;
    hasil_pengolahan: number | null;
    satuan_pengolahan: string | null;
    catatan: string | null;
}

interface BastHistory {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    tanggal_bast: string;
    tanggal_serah_terima: string;
    periode: string;
    status: string;
    file_path: string | null;
    signed_file_path: string | null;
    created_by: string;
    created_at: string;
    is_current: boolean;
}

interface BastListItem {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    petugas_nama: string;
    file_path: string | null;
    signed_file_path: string | null;
    is_current: boolean;
}

interface ShowProps {
    bast: Bast;
    spk: Spk | null;
    petugas: Petugas | null;
    kegiatan: Kegiatan;
    bast_petugas: BastPetugas[];
    bast_history: BastHistory[];
    bast_list: BastListItem[];
    bulan: number;
    tahun: number;
    bulan_label: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Detail BAST', href: '#' },
];

export default function Show({
    bast,
    spk,
    petugas,
    kegiatan,
    bast_list,
    bulan,
    tahun,
    bulan_label,
}: ShowProps) {
    const { auth } = usePage<SharedData>().props;
    const [isUploading, setIsUploading] = useState(false);

    // Urutkan bast_list berdasarkan nama petugas (A-Z)
    const sortedBastList = [...bast_list].sort((a, b) =>
        a.petugas_nama.localeCompare(b.petugas_nama),
    );

    const bastSigned = sortedBastList.filter((item) => item.signed_file_path);
    const bastUnsigned = sortedBastList.filter(
        (item) => !item.signed_file_path,
    );

    const generatedFileCount = sortedBastList.filter(
        (item) => item.file_path,
    ).length;
    const signedFileCount = sortedBastList.filter(
        (item) => item.signed_file_path,
    ).length;
    const unsignedFileCount = Math.max(generatedFileCount - signedFileCount, 0);

    const signedProgress =
        generatedFileCount > 0
            ? Math.round((signedFileCount / generatedFileCount) * 100)
            : 0;

    const canEdit =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'approver';

    const handleDownload = (filePath: string) => {
        openFastDownload(filePath);
    };

    const handleDownloadAll = async () => {
        // Construct deterministic filename
        const filename = constructBastDownloadFilename(bulan, tahun);
        const fallbackUrl = `/bast/download-all?bulan=${bulan}&tahun=${tahun}`;

        // Try direct download first, fallback to Laravel route if not exists
        await tryDirectDownload(filename, fallbackUrl);
    };

    const handleUploadSigned = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploading(true);

        router.post(
            `/bast/${bast.hashed_id}/upload-signed`,
            { file },
            {
                onFinish: () => setIsUploading(false),
                onError: () => setIsUploading(false),
            },
        );
    };

    const handleSelectBast = (bastHashedId: string) => {
        router.get(
            `/bast/${bastHashedId}`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: [
                    'bast',
                    'spk',
                    'petugas',
                    'bast_petugas',
                    'bast_history',
                    'bast_list',
                    'bulan',
                    'tahun',
                    'bulan_label',
                ],
            },
        );
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'diterbitkan':
                return <Badge variant="default">Diterbitkan</Badge>;
            case 'draft':
                return <Badge variant="secondary">Draft</Badge>;
            case 'dibatalkan':
                return <Badge variant="destructive">Dibatalkan</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail BAST - ${bast.nomor_bast}`} />

            <PageHeader
                title={`Detail BAST ${bulan_label} ${tahun}`}
                description={`Berita Acara Serah Terima - ${kegiatan.nama_kegiatan}`}
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={handleDownloadAll}>
                        <FolderDown className="mr-2 h-4 w-4" />
                        Download Semua
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/bast/list?bulan=${bulan}&tahun=${tahun}`}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            </PageHeader>

            <ContentCard>
                <div className="space-y-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Ringkasan Dokumen BAST
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Progres generate dan tanda tangan periode{' '}
                                {bulan_label} {tahun}
                            </p>
                        </div>
                        <Badge variant="outline" className="text-xs">
                            {signedProgress}% signed
                        </Badge>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="group rounded-xl border border-blue-200/80 bg-linear-to-br from-blue-50 to-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-blue-900/60 dark:from-blue-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-blue-700 uppercase dark:text-blue-300">
                                    File Digenerate
                                </p>
                                <FileText className="h-4 w-4 text-blue-600 dark:text-blue-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-blue-900 dark:text-blue-100">
                                {generatedFileCount}
                            </p>
                        </div>

                        <div className="group rounded-xl border border-green-200/80 bg-linear-to-br from-green-50 to-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-green-900/60 dark:from-green-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-green-700 uppercase dark:text-green-300">
                                    Sudah Ditandatangani
                                </p>
                                <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-green-900 dark:text-green-100">
                                {signedFileCount}
                            </p>
                        </div>

                        <div className="group rounded-xl border border-amber-200/80 bg-linear-to-br from-amber-50 to-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-amber-900/60 dark:from-amber-950/30 dark:to-neutral-900">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-medium tracking-wide text-amber-700 uppercase dark:text-amber-300">
                                    Belum Ditandatangani
                                </p>
                                <PenLine className="h-4 w-4 text-amber-600 dark:text-amber-300" />
                            </div>
                            <p className="mt-3 text-3xl font-bold text-amber-900 dark:text-amber-100">
                                {unsignedFileCount}
                            </p>
                        </div>
                    </div>

                    <div>
                        <div className="mb-1 flex items-center justify-between text-xs text-neutral-600 dark:text-neutral-400">
                            <span>Progres tanda tangan</span>
                            <span>
                                {signedFileCount}/{generatedFileCount || 0}
                            </span>
                        </div>
                        <div className="h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                            <div
                                className="h-2 rounded-full bg-green-500 transition-all duration-500"
                                style={{ width: `${signedProgress}%` }}
                            />
                        </div>
                    </div>
                </div>
            </ContentCard>

            <div className="grid max-w-full gap-6 overflow-x-hidden md:grid-cols-3">
                {/* Sidebar - Daftar BAST */}
                <div className="w-full min-w-0 md:col-span-1">
                    <ContentCard>
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Daftar BAST ({sortedBastList.length})
                                </h3>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    BAST periode {bulan_label} {tahun}
                                </p>
                            </div>

                            <div className="space-y-3">
                                <h4 className="text-sm font-semibold text-green-700 dark:text-green-400">
                                    BAST Sudah Ditandatangani (
                                    {bastSigned.length})
                                </h4>
                                <div className="max-h-[250px] space-y-2 overflow-y-auto">
                                    {bastSigned.map((item) => (
                                        <Button
                                            key={item.id}
                                            type="button"
                                            variant="ghost"
                                            onClick={() =>
                                                handleSelectBast(item.hashed_id)
                                            }
                                            className={`w-full cursor-pointer rounded-lg border p-3 text-left transition-colors ${
                                                item.is_current
                                                    ? 'border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800'
                                                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            <div className="text-sm font-medium text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </div>
                                            <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                                {item.nomor_bast}
                                            </div>
                                        </Button>
                                    ))}
                                </div>

                                <h4 className="text-sm font-semibold text-amber-700 dark:text-amber-400">
                                    BAST Belum Ditandatangani (
                                    {bastUnsigned.length})
                                </h4>
                                <div className="max-h-[250px] space-y-2 overflow-y-auto">
                                    {bastUnsigned.map((item) => (
                                        <Button
                                            key={item.id}
                                            type="button"
                                            variant="ghost"
                                            onClick={() =>
                                                handleSelectBast(item.hashed_id)
                                            }
                                            className={`w-full cursor-pointer rounded-lg border p-3 text-left transition-colors ${
                                                item.is_current
                                                    ? 'border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800'
                                                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            <div className="text-sm font-medium text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </div>
                                            <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                                {item.nomor_bast}
                                            </div>
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                {/* Main Content */}
                <div className="w-full min-w-0 space-y-6 md:col-span-2">
                    {/* Informasi Petugas Card */}
                    {petugas && (
                        <ContentCard>
                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Informasi Petugas
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Detail petugas penerima pekerjaan
                                    </p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">
                                            Nama Lengkap
                                        </Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {petugas.nama}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">
                                            NIK
                                        </Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {petugas.nik}
                                        </p>
                                    </div>
                                    {petugas.alamat && (
                                        <div className="md:col-span-2">
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Alamat
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {petugas.alamat}
                                            </p>
                                        </div>
                                    )}
                                    {petugas.no_hp && (
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                No. HP
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {petugas.no_hp}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </ContentCard>
                    )}
                    {/* Informasi BAST Card */}
                    <ContentCard>
                        <div className="space-y-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Informasi BAST
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Detail berita acara serah terima
                                    </p>
                                </div>
                                {getStatusBadge(bast.status)}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Nomor BAST
                                    </Label>
                                    <p className="font-medium break-words text-neutral-900 dark:text-white">
                                        {bast.nomor_bast}
                                    </p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Tanggal BAST
                                    </Label>
                                    <p className="font-medium break-words text-neutral-900 dark:text-white">
                                        {bast.tanggal_bast}
                                    </p>
                                </div>
                            </div>

                            {spk && (
                                <div className="border-t border-neutral-200 pt-4 dark:border-neutral-700">
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Perjanjian Kerja Terkait
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {spk.nomor_spk}
                                    </p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {spk.tanggal_spk} • Rp{' '}
                                        {parseFloat(
                                            spk.nilai_kontrak.toString(),
                                        ).toLocaleString('id-ID')}
                                    </p>
                                </div>
                            )}
                            <div className="flex items-center gap-4 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                                {bast.file_path && (
                                    <Button
                                        onClick={() =>
                                            handleDownload(bast.file_path!)
                                        }
                                        size="sm"
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Download BAST (belum tanda tangan)
                                    </Button>
                                )}
                                {bast.signed_file_path && (
                                    <Button
                                        onClick={() =>
                                            handleDownload(
                                                bast.signed_file_path!,
                                            )
                                        }
                                        size="sm"
                                        variant="outline"
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Download BAST (bertanda tangan)
                                    </Button>
                                )}
                            </div>
                        </div>
                    </ContentCard>

                    {/* Upload BAST Signed Card */}
                    {canEdit && (
                        <ContentCard>
                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Upload BAST Bertanda Tangan
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Upload file BAST yang sudah
                                        ditandatangani (PDF, max 10MB)
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Label
                                        htmlFor="signed-file"
                                        className="flex cursor-pointer items-center gap-2 rounded-lg border border-neutral-300 px-4 py-2 transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:hover:bg-neutral-800"
                                    >
                                        <Upload className="h-4 w-4" />
                                        <span className="text-sm">
                                            {isUploading
                                                ? 'Mengunggah...'
                                                : bast.signed_file_path
                                                  ? 'Ganti File'
                                                  : 'Pilih File'}
                                        </span>
                                    </Label>
                                    <Input
                                        id="signed-file"
                                        type="file"
                                        accept="application/pdf"
                                        onChange={handleUploadSigned}
                                        disabled={isUploading}
                                        className="hidden"
                                    />
                                    {bast.signed_file_path && (
                                        <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                                            <Check className="h-4 w-4" />
                                            <span>File tersimpan</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </ContentCard>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
