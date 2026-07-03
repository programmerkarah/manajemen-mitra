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
import { type BreadcrumbItem } from '@/types';
import { openFastDownload } from '@/utils/downloadUtils';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Download, FileText, Upload } from 'lucide-react';
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
    const [modalAlert, setModalAlert] = useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });
    const { data, setData, post, errors } = useForm({
        signed_file: null as File | null,
    });

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('signed_file', e.target.files[0]);
        }
    };

    const handleUpload = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.signed_file) {
            showModalAlert(
                'File Belum Dipilih',
                'Pilih file PDF yang sudah ditandatangani.',
            );
            return;
        }

        setIsUploading(true);
        post(`/sk-kpa/${skKpa.hashed_id}/upload-signed`, {
            onSuccess: () => {
                setData('signed_file', null);
                setIsUploading(false);
                const fileInput = document.getElementById(
                    'signed_file',
                ) as HTMLInputElement;
                if (fileInput) {
                    fileInput.value = '';
                }
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

    const documentStatusBadge = skKpa.is_signed ? (
        <Badge variant="default">Sudah Bertanda Tangan</Badge>
    ) : (
        getStatusBadge(skKpa.status)
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail SK - ${kegiatan.nama_kegiatan}`} />

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

            <div className="w-full space-y-4">
                <ContentCard>
                    <div className="space-y-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Informasi SK
                                </h3>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Detail surat keputusan petugas kegiatan
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                {documentStatusBadge}
                                <Badge variant="outline">
                                    {getBulanName(skKpa.bulan)} {skKpa.tahun}
                                </Badge>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <Label className="text-neutral-600 dark:text-neutral-400">
                                    Nomor SK
                                </Label>
                                <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                    {skKpa.nomor_sk}
                                </p>
                            </div>
                            <div>
                                <Label className="text-neutral-600 dark:text-neutral-400">
                                    Tanggal SK
                                </Label>
                                <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                    {skKpa.tanggal_sk}
                                </p>
                            </div>
                            <div>
                                <Label className="text-neutral-600 dark:text-neutral-400">
                                    Nama KPA
                                </Label>
                                <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                    {skKpa.nama_kpa}
                                </p>
                            </div>
                            <div>
                                <Label className="text-neutral-600 dark:text-neutral-400">
                                    Kegiatan
                                </Label>
                                <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                    {kegiatan.nama_kegiatan}
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-[minmax(0,1.45fr)_minmax(260px,0.8fr)]">
                            <div>
                                <Label className="text-neutral-600 dark:text-neutral-400">
                                    Perihal
                                </Label>
                                <p className="mt-1 leading-6 break-words text-neutral-900 dark:text-white">
                                    {skKpa.perihal}
                                </p>
                            </div>

                            <div>
                                <Label className="text-neutral-600 dark:text-neutral-400">
                                    Status Dokumen
                                </Label>
                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                    {documentStatusBadge}
                                    <Badge variant="outline">
                                        {sk_history.length} riwayat
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                            {skKpa.is_signed && skKpa.signed_file_path && (
                                <Button
                                    onClick={() =>
                                        handleDownload(skKpa.signed_file_path!)
                                    }
                                    className="justify-start gap-2"
                                    variant="complete"
                                >
                                    <Check className="h-4 w-4" />
                                    Unduh SK Bertanda Tangan
                                </Button>
                            )}
                            <Button
                                onClick={() => handleDownload(skKpa.file_path)}
                                className="justify-start gap-2"
                                variant={
                                    skKpa.is_signed ? 'outline' : 'default'
                                }
                            >
                                <Download className="h-4 w-4" />
                                Unduh SK Hasil Generate
                            </Button>
                        </div>
                    </div>
                </ContentCard>

                <div className="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,1fr)]">
                    <ContentCard>
                        <div className="space-y-3">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Dasar Hukum
                                </h3>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Daftar dasar hukum disajikan tanpa banyak
                                    kotak tambahan agar lebih mudah dibaca.
                                </p>
                            </div>

                            <ul className="space-y-2">
                                {skKpa.dasar_hukum.map((dh, index) => (
                                    <li
                                        key={index}
                                        className="flex gap-3 rounded-2xl border border-neutral-200 bg-neutral-50/60 px-4 py-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900/30 dark:text-white"
                                    >
                                        <span className="shrink-0 text-neutral-500 dark:text-neutral-400">
                                            {index + 1}.
                                        </span>
                                        <span className="min-w-0 flex-1 leading-6 break-words">
                                            {dh}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </ContentCard>

                    <div className="space-y-4">
                        <ContentCard>
                            <form onSubmit={handleUpload} className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Upload SK Bertanda Tangan
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Upload PDF yang sudah ditandatangani.
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
                                            {data.signed_file.name} ·{' '}
                                            {Math.round(
                                                data.signed_file.size / 1024,
                                            )}{' '}
                                            KB
                                        </p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    disabled={!data.signed_file || isUploading}
                                    className="w-full gap-2"
                                >
                                    <Upload className="h-4 w-4" />
                                    {isUploading ? 'Uploading...' : 'Upload'}
                                </Button>
                            </form>
                        </ContentCard>

                        <ContentCard>
                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Riwayat SK
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {sk_history.length} SK untuk kegiatan
                                        ini
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
                                                                Bertanda tangan
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
        </AppLayout>
    );
}
