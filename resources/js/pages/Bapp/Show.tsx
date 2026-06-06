import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    FileText,
    PenLine,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';

interface SpkItem {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    petugas: {
        id: number | null;
        nama: string | null;
        nik: string | null;
    };
    peran: string;
    nomor_bapp_auto: string;
    has_bapp: boolean;
    bapp_hashed_id: string | null;
    nomor_bapp: string | null;
    tanggal_bapp: string | null;
    file_path: string | null;
    signed_file_path: string | null;
    signed_uploaded_at: string | null;
    fasih_screenshot_path: string | null;
    realisasi_sls: number | null;
    realisasi_unit_sampel: Record<string, number>;
}

interface ShowProps {
    tahun: number;
    termin: number;
    termin_hashed: string;
    termin_roman: string;
    bulan_label: string;
    persentase: number;
    can_generate: boolean;
    can_input_realisasi: boolean;
    tanggal_min: string;
    spk_list: SpkItem[];
    summary: {
        total: number;
        generated: number;
        signed: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAPP SE2026', href: '/bapp' },
    { title: 'Detail Termin', href: '#' },
];

const peranLabelMap: Record<string, string> = {
    pcl_ppl: 'PCL/PPL',
    pml: 'PML',
    pcl: 'PCL',
    ppl: 'PPL',
    lapangan: 'Petugas Lapangan',
    pengolahan: 'Petugas Pengolahan',
};

function getStatusBadge(item: SpkItem) {
    if (item.signed_file_path) {
        return (
            <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                <CheckCircle2 className="mr-1 h-3 w-3" />
                Bertanda Tangan
            </Badge>
        );
    }

    if (item.has_bapp && item.file_path) {
        return (
            <Badge variant="secondary">
                <FileText className="mr-1 h-3 w-3" />
                PDF Tersedia
            </Badge>
        );
    }

    if (item.has_bapp) {
        return (
            <Badge variant="secondary">
                <Clock className="mr-1 h-3 w-3" />
                Draft
            </Badge>
        );
    }

    return <Badge variant="outline">Belum Ada</Badge>;
}

export default function Show({
    tahun,
    termin,
    termin_hashed,
    termin_roman,
    bulan_label,
    persentase,
    can_input_realisasi,
    spk_list,
    summary,
}: ShowProps) {
    const [uploadingId, setUploadingId] = useState<number | null>(null);
    const fileInputRefs = useRef<Record<number, HTMLInputElement | null>>({});

    const handleUploadSignedBapp = (spkId: number, hashedId: string) => {
        const input = fileInputRefs.current[spkId];
        if (!input?.files?.[0]) {
            return;
        }

        setUploadingId(spkId);

        const formData = new FormData();
        formData.append('file', input.files[0]);

        router.post(`/bapp/${hashedId}/upload-signed`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setUploadingId(null);
                if (input) {
                    input.value = '';
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail BAPP Termin ${termin_roman} — ${tahun}`} />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title={`Detail BAPP Termin ${termin_roman}`}
                    description={`Sensus Ekonomi 2026 — ${bulan_label} ${tahun} — Target ${persentase}% pekerjaan`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/bapp">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Summary Stats */}
                <div className="grid grid-cols-3 gap-4">
                    <ContentCard>
                        <div className="text-center">
                            <div className="text-3xl font-bold text-neutral-700 dark:text-neutral-200">
                                {summary.total}
                            </div>
                            <div className="mt-1 text-sm text-neutral-500">
                                Total SPK
                            </div>
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="text-center">
                            <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {summary.generated}
                            </div>
                            <div className="mt-1 text-sm text-neutral-500">
                                PDF Dibuat
                            </div>
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="text-center">
                            <div className="text-3xl font-bold text-green-600 dark:text-green-400">
                                {summary.signed}
                            </div>
                            <div className="mt-1 text-sm text-neutral-500">
                                Bertanda Tangan
                            </div>
                        </div>
                    </ContentCard>
                </div>

                {!can_input_realisasi && (
                    <div className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800 dark:border-yellow-800/40 dark:bg-yellow-900/20 dark:text-yellow-300">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>
                            Upload BAPP bertanda tangan belum tersedia. Fitur
                            ini aktif setelah tanggal minimum berlaku.
                        </span>
                    </div>
                )}

                {/* Per-Petugas List */}
                <ContentCard>
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="font-semibold">
                            Daftar Petugas — Termin {termin_roman}
                        </h3>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/bapp/create?termin=${termin_hashed}`}>
                                <FileText className="mr-2 h-4 w-4" />
                                Input Realisasi
                            </Link>
                        </Button>
                    </div>

                    <div className="flex flex-col gap-4">
                        {spk_list.map((item) => (
                            <div
                                key={item.spk_id}
                                className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700"
                            >
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    {/* Left: Petugas Info */}
                                    <div className="flex flex-col gap-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">
                                                {item.petugas.nama ??
                                                    '(Tanpa Nama)'}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="text-xs"
                                            >
                                                {peranLabelMap[item.peran] ??
                                                    item.peran}
                                            </Badge>
                                        </div>
                                        <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                            SPK: {item.nomor_spk}
                                            {item.petugas.nik && (
                                                <span>
                                                    {' '}
                                                    &bull; NIK:{' '}
                                                    {item.petugas.nik}
                                                </span>
                                            )}
                                        </div>
                                        {item.nomor_bapp && (
                                            <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                                No. BAPP: {item.nomor_bapp}
                                                {item.tanggal_bapp && (
                                                    <span>
                                                        {' '}
                                                        &bull;{' '}
                                                        {item.tanggal_bapp}
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                        {item.signed_uploaded_at && (
                                            <div className="text-xs text-green-600 dark:text-green-400">
                                                <PenLine className="mr-1 inline h-3 w-3" />
                                                Diunggah:{' '}
                                                {item.signed_uploaded_at}
                                            </div>
                                        )}
                                    </div>

                                    {/* Right: Status + Actions */}
                                    <div className="flex flex-col items-start gap-2 sm:items-end">
                                        {getStatusBadge(item)}

                                        <div className="flex flex-wrap gap-2">
                                            {/* Preview BAPP (draft) */}
                                            {item.has_bapp &&
                                            item.bapp_hashed_id &&
                                            item.file_path ? (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/bapp/${item.bapp_hashed_id}/preview`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        <Eye className="mr-1 h-4 w-4" />
                                                        Preview
                                                    </a>
                                                </Button>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled
                                                >
                                                    <Eye className="mr-1 h-4 w-4" />
                                                    Preview
                                                </Button>
                                            )}

                                            {/* Download BAPP (draft) */}
                                            {item.has_bapp &&
                                            item.bapp_hashed_id &&
                                            item.file_path ? (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/bapp/${item.bapp_hashed_id}/download`}
                                                    >
                                                        <Download className="mr-1 h-4 w-4" />
                                                        Unduh
                                                    </a>
                                                </Button>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled
                                                >
                                                    <Download className="mr-1 h-4 w-4" />
                                                    Unduh
                                                </Button>
                                            )}

                                            {/* Preview Signed */}
                                            {item.signed_file_path &&
                                            item.bapp_hashed_id ? (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/bapp/${item.bapp_hashed_id}/download-signed`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        <PenLine className="mr-1 h-4 w-4" />
                                                        Lihat Signed
                                                    </a>
                                                </Button>
                                            ) : null}
                                        </div>

                                        {/* Upload Signed section */}
                                        {can_input_realisasi &&
                                            item.has_bapp &&
                                            item.file_path &&
                                            item.bapp_hashed_id && (
                                                <div className="flex items-end gap-2">
                                                    <div className="flex flex-col gap-1">
                                                        <Label
                                                            htmlFor={`signed-upload-${item.spk_id}`}
                                                            className="text-xs text-neutral-500"
                                                        >
                                                            BAPP Bertanda Tangan
                                                            (PDF)
                                                        </Label>
                                                        <Input
                                                            id={`signed-upload-${item.spk_id}`}
                                                            type="file"
                                                            accept=".pdf"
                                                            className="h-8 w-48 text-xs"
                                                            ref={(el) => {
                                                                fileInputRefs.current[
                                                                    item.spk_id
                                                                ] = el;
                                                            }}
                                                        />
                                                    </div>
                                                    <Button
                                                        size="sm"
                                                        disabled={
                                                            uploadingId ===
                                                            item.spk_id
                                                        }
                                                        onClick={() =>
                                                            handleUploadSignedBapp(
                                                                item.spk_id,
                                                                item.bapp_hashed_id!,
                                                            )
                                                        }
                                                    >
                                                        <Upload className="mr-1 h-4 w-4" />
                                                        {uploadingId ===
                                                        item.spk_id
                                                            ? 'Mengunggah...'
                                                            : 'Unggah'}
                                                    </Button>
                                                </div>
                                            )}
                                    </div>
                                </div>
                            </div>
                        ))}

                        {spk_list.length === 0 && (
                            <div className="flex flex-col items-center gap-3 py-8 text-center text-neutral-500">
                                <FileText className="h-10 w-10 opacity-40" />
                                <p>Tidak ada SPK Sensus Ekonomi ditemukan.</p>
                            </div>
                        )}
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
