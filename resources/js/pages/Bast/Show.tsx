import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Check, Download, FileText, FolderDown, Upload, User } from 'lucide-react';
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
    bast_petugas,
    bast_history,
    bast_list,
    bulan,
    tahun,
    bulan_label,
}: ShowProps) {
    const { auth } = usePage<SharedData>().props;
    const [isUploading, setIsUploading] = useState(false);
    
    const canEdit =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'approver';

    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
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
        router.get(`/bast/${bastHashedId}`);
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
                    <Button
                        variant="outline"
                        onClick={() => {
                            window.location.href = `/bast/download-all?bulan=${bulan}&tahun=${tahun}`;
                        }}
                    >
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

            <div className="grid max-w-full gap-6 overflow-x-hidden md:grid-cols-3">
                {/* Sidebar - Daftar BAST */}
                <div className="w-full min-w-0 md:col-span-1">
                    <ContentCard>
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Daftar BAST ({bast_list.length})
                                </h3>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    BAST periode {bulan_label} {tahun}
                                </p>
                            </div>

                            <div className="max-h-[600px] space-y-2 overflow-y-auto">
                                {bast_list.map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() =>
                                            handleSelectBast(item.hashed_id)
                                        }
                                        className={`w-full rounded-lg border p-3 text-left transition-colors ${
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
                                    </button>
                                ))}
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
                                        Download BAST
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
                                        Download BAST Signed
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
                                        Upload file BAST yang sudah ditandatangani
                                        (PDF, max 10MB)
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
   