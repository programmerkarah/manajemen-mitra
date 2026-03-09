import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { openFastDownload } from '@/utils/downloadUtils';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    Download,
    FileText,
    Upload,
    User,
} from 'lucide-react';
import { useState } from 'react';

interface SkKpa {
    id: number;
    hashed_id: string;
    nomor_sk: string;
    tanggal_sk: string;
    nama_kpa: string;
    perihal: string;
    bulan: number;
    tahun: number;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    file_path: string;
    signed_file_path: string | null;
    is_signed: boolean;
    signed_at: string | null;
    signed_by: string | null;
    created_by: string;
    created_at: string;
    dasar_hukum: string[];
}

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
}

interface SkHistory {
    id: number;
    hashed_id: string;
    nomor_sk: string;
    tanggal_sk: string;
    nama_kpa: string;
    perihal: string;
    status: string;
    file_path: string;
    signed_file_path: string | null;
    is_signed: boolean;
    signed_at: string | null;
    signed_by: string | null;
    created_by: string;
    created_at: string;
    is_current: boolean;
}

interface ShowProps {
    skKpa: SkKpa;
    kegiatan: Kegiatan;
    sk_history: SkHistory[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SK KPA', href: '/sk-kpa' },
    { title: 'Detail SK', href: '#' },
];

export default function Show({ skKpa, kegiatan, sk_history }: ShowProps) {
    const [isUploading, setIsUploading] = useState(false);
    const { data, setData, post, errors } = useForm({
        signed_file: null as File | null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('signed_file', e.target.files[0]);
        }
    };

    const handleUpload = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.signed_file) {
            alert('Pilih file PDF yang sudah ditandatangani');
            return;
        }

        setIsUploading(true);
        post(`/sk-kpa/${skKpa.hashed_id}/upload-signed`, {
            onSuccess: () => {
                setData('signed_file', null);
                setIsUploading(false);
                // Reset file input
                const fileInput = document.getElementById(
                    'signed_file',
                ) as HTMLInputElement;
                if (fileInput) fileInput.value = '';
            },
            onError: () => {
                setIsUploading(false);
            },
        });
    };

    const handleDownload = (filePath: string) => {
        openFastDownload(filePath);
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

    const getBulanName = (bulan: number) => {
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
        return months[bulan - 1] || bulan;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail SK - ${kegiatan.nama_kegiatan}`} />

            <PageHeader
                title="Detail SK KPA"
                description={`SK untuk ${kegiatan.nama_kegiatan}`}
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/sk-kpa">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            </PageHeader>

            <div className="w-full" style={{ maxWidth: '84vw' }}>
                <div className="max-w-full overflow-hidden">
                    <div className="grid gap-6 md:grid-cols-3">
                        {/* Main Content - SK Details */}
                        <div className="min-w-0 space-y-6 md:col-span-2">
                            <ContentCard>
                                <div className="space-y-6">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                                Informasi SK
                                            </h3>
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                Detail surat keputusan petugas
                                                kegiatan
                                            </p>
                                        </div>
                                        {getStatusBadge(skKpa.status)}
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Nomor SK
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {skKpa.nomor_sk}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Tanggal SK
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {skKpa.tanggal_sk}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Periode
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {getBulanName(skKpa.bulan)}{' '}
                                                {skKpa.tahun}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Nama KPA
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {skKpa.nama_kpa}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Perihal
                                            </Label>
                                            <p className="font-medium break-words text-neutral-900 dark:text-white">
                                                {skKpa.perihal}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Kegiatan
                                            </Label>
                                            <p className="font-medium break-words text-neutral-900 dark:text-white">
                                                {kegiatan.nama_kegiatan} (
                                                {kegiatan.kode_kegiatan})
                                            </p>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-neutral-600 dark:text-neutral-400">
                                            Dasar Hukum
                                        </Label>
                                        <ul className="mt-2 space-y-2">
                                            {skKpa.dasar_hukum.map(
                                                (dh, index) => (
                                                    <li
                                                        key={index}
                                                        className="grid gap-2 text-sm text-neutral-900 dark:text-white"
                                                        style={{
                                                            gridTemplateColumns:
                                                                'auto 1fr',
                                                        }}
                                                    >
                                                        <span className="text-neutral-600 dark:text-neutral-400">
                                                            {index + 1}.
                                                        </span>
                                                        <span
                                                            style={{
                                                                wordBreak:
                                                                    'break-word',
                                                                overflowWrap:
                                                                    'break-word',
                                                            }}
                                                        >
                                                            {dh}
                                                        </span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>

                                    <div className="border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                            <User className="h-4 w-4 flex-shrink-0" />
                                            <span className="break-words">
                                                Dibuat oleh {skKpa.created_by}{' '}
                                                pada {skKpa.created_at}
                                            </span>
                                        </div>
                                        {skKpa.is_signed && (
                                            <div className="mt-2 flex flex-wrap items-center gap-2 text-sm text-green-600 dark:text-green-400">
                                                <Check className="h-4 w-4 flex-shrink-0" />
                                                <span className="break-words">
                                                    Ditandatangani oleh{' '}
                                                    {skKpa.signed_by} pada{' '}
                                                    {skKpa.signed_at}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </ContentCard>

                            {/* Download SK */}
                            <ContentCard>
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Download SK
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            Unduh dokumen SK dalam format PDF
                                        </p>
                                    </div>

                                    <div className="space-y-3">
                                        {/* SK yang sudah ditandatangani (prioritas) */}
                                        {skKpa.is_signed &&
                                            skKpa.signed_file_path && (
                                                <Button
                                                    onClick={() =>
                                                        handleDownload(
                                                            skKpa.signed_file_path!,
                                                        )
                                                    }
                                                    className="w-full justify-start"
                                                    variant="complete"
                                                >
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Unduh SK Bertanda Tangan
                                                </Button>
                                            )}

                                        {/* SK asli (tanpa tanda tangan) */}
                                        <Button
                                            onClick={() =>
                                                handleDownload(skKpa.file_path)
                                            }
                                            className="w-full justify-start"
                                            variant={
                                                skKpa.is_signed
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Unduh SK Hasil <em>Generate</em>{' '}
                                            (Belum Bertanda Tangan)
                                        </Button>
                                    </div>
                                </div>
                            </ContentCard>

                            {/* Upload SK Bertanda Tangan */}
                            <ContentCard>
                                <form
                                    onSubmit={handleUpload}
                                    className="space-y-4"
                                >
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Upload SK Bertanda Tangan
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            Upload SK yang sudah ditandatangani
                                            (format PDF, max 10MB)
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="signed_file">
                                            File SK Bertanda Tangan
                                        </Label>
                                        <Input
                                            id="signed_file"
                                            type="file"
                                            accept=".pdf"
                                            onChange={handleFileChange}
                                            disabled={isUploading}
                                        />
                                        {errors.signed_file && (
                                            <p className="text-sm text-red-500">
                                                {errors.signed_file}
                                            </p>
                                        )}
                                        {data.signed_file && (
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                File: {data.signed_file.name} (
                                                {Math.round(
                                                    data.signed_file.size /
                                                        1024,
                                                )}{' '}
                                                KB)
                                            </p>
                                        )}
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={
                                            !data.signed_file || isUploading
                                        }
                                        className="w-full"
                                    >
                                        <Upload className="mr-2 h-4 w-4" />
                                        {isUploading
                                            ? 'Uploading...'
                                            : 'Upload SK'}
                                    </Button>
                                </form>
                            </ContentCard>
                        </div>

                        {/* Sidebar - History */}
                        <div className="min-w-0 space-y-6">
                            <ContentCard>
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Riwayat SK
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            {sk_history.length} SK untuk
                                            kegiatan ini
                                        </p>
                                    </div>

                                    <div className="space-y-3">
                                        {sk_history.map((sk, index) => (
                                            <div
                                                key={sk.id}
                                                className={`rounded-lg border p-3 ${
                                                    sk.is_current
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-neutral-200 dark:border-neutral-800'
                                                }`}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center gap-2">
                                                            {sk.is_current && (
                                                                <Badge
                                                                    variant="default"
                                                                    className="text-xs"
                                                                >
                                                                    Saat ini
                                                                </Badge>
                                                            )}
                                                            {index === 0 &&
                                                                !sk.is_current && (
                                                                    <Badge
                                                                        variant="secondary"
                                                                        className="text-xs"
                                                                    >
                                                                        Terbaru
                                                                    </Badge>
                                                                )}
                                                        </div>
                                                        <p className="mt-1 truncate text-sm font-medium text-neutral-900 dark:text-white">
                                                            {sk.nomor_sk}
                                                        </p>
                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                            {sk.tanggal_sk}
                                                        </p>
                                                        {sk.is_signed && (
                                                            <div className="mt-1 flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                                <Check className="h-3 w-3" />
                                                                <span>
                                                                    Bertanda
                                                                    tangan
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    {!sk.is_current && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/sk-kpa/${sk.hashed_id}`}
                                                            >
                                                                <FileText className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </ContentCard>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
