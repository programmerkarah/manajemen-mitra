import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import {
    constructDownloadAllFilename,
    constructDownloadByKegiatanFilename,
    tryDirectDownload,
} from '@/utils/downloadUtils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, ArrowLeft, Download, Upload } from 'lucide-react';
import { useState } from 'react';

interface Spk {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    tanggal_spk: string;
    tanggal_mulai_kerja: string;
    tanggal_selesai_kerja: string;
    nilai_kontrak: number;
    nama_ppk: string;
    nip_ppk: string | null;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    file_path: string | null;
    signed_file_path: string | null;
    previous_file_path: string | null;
    created_by: string;
    created_at: string;
}

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    jenis_petugas: 'organik' | 'non_organik';
    alamat: string | null;
}

interface PeriodeAlokasi {
    id: number;
    hashed_id: string;
    bulan: number;
    tahun: number;
}

interface Bast {
    id: number;
    hashed_id: string;
    nomor_bast: string;
    tanggal_bast: string;
    file_path: string | null;
}

interface Addendum {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    tanggal_spk: string;
    tanggal_mulai_kerja: string;
    tanggal_selesai_kerja: string;
    nilai_kontrak: number;
    status: string;
    file_path: string | null;
    addendum_number: number;
    created_by: string;
    created_at: string;
}

interface MergedKegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: string;
    tahun_anggaran: number;
    peran: string;
    total_honor: number;
    original: {
        jumlah_satuan: number;
        jumlah_satuan_listing: number;
        total_honor: number;
        total_honor_listing: number;
        peran: string;
    };
    latest: {
        jumlah_satuan: number;
        jumlah_satuan_listing: number;
        total_honor: number;
        total_honor_listing: number;
        peran: string;
    };
    has_change: boolean;
}

interface UniqueKegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jumlah_spk: number;
    all_signed: boolean;
}

interface ShowProps {
    spk: Spk;
    petugas: Petugas;
    kegiatan_list: MergedKegiatan[];
    unique_kegiatan_list?: UniqueKegiatan[];
    addendums: Addendum[];
    periode: PeriodeAlokasi;
    bast: Bast | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
    { title: 'Detail Perjanjian Kerja', href: '#' },
];

const bulanLabels: Record<number, string> = {
    1: 'Januari',
    2: 'Februari',
    3: 'Maret',
    4: 'April',
    5: 'Mei',
    6: 'Juni',
    7: 'Juli',
    8: 'Agustus',
    9: 'September',
    10: 'Oktober',
    11: 'November',
    12: 'Desember',
};

export default function Show({
    spk,
    petugas,
    kegiatan_list,
    unique_kegiatan_list,
    addendums,
    periode,
    bast,
}: ShowProps) {
    const { auth } = usePage<SharedData>().props;
    const canEdit =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'approver';
    const [isUploading, setIsUploading] = useState(false);

    // Ensure unique_kegiatan_list is always an array
    const kegiatanListForDownload = unique_kegiatan_list || [];

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'Petugas Pencacahan',
            pml: 'Pemeriksa Lapangan',
            pengolahan: 'Petugas Pengolahan',
            pengawas_pengolahan: 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
    };

    const handleDownloadAllByPeriode = async () => {
        // Construct deterministic filename
        const filename = constructDownloadAllFilename(periode.bulan, periode.tahun);
        const fallbackUrl = `/spk/periode/${periode.hashed_id}/download-all`;

        // Try direct download first, fallback to Laravel route if not exists
        await tryDirectDownload(filename, fallbackUrl);
    };

    const handleDownloadAllByKegiatan = async (
        kegiatanHashedId: string,
        kegiatanName: string,
    ) => {
        // Construct deterministic filename
        const filename = constructDownloadByKegiatanFilename(
            kegiatanName,
            periode.bulan,
            periode.tahun,
        );
        const fallbackUrl = `/spk/periode/${periode.hashed_id}/kegiatan/${kegiatanHashedId}/download-all`;

        // Try direct download first, fallback to Laravel route if not exists
        await tryDirectDownload(filename, fallbackUrl);
    };

    const handleUploadSigned = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploading(true);

        router.post(
            `/spk/${spk.hashed_id}/upload-signed`,
            { file },
            {
                onFinish: () => setIsUploading(false),
                onError: () => setIsUploading(false),
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
            <Head title={`Detail Perjanjian Kerja - ${spk.nomor_spk}`} />

            <PageHeader
                title="Detail Perjanjian Kerja"
                description={`Surat Perjanjian Kerja untuk ${petugas.nama}`}
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/spk">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            </PageHeader>

            <div className="grid gap-6 md:grid-cols-3">
                {/* Main Content - SPK Details */}
                <div className="space-y-6 md:col-span-2">
                    <ContentCard>
                        <div className="space-y-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Informasi Perjanjian Kerja
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Detail surat perjanjian kerja petugas
                                    </p>
                                </div>
                                {getStatusBadge(spk.status)}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Nomor Perjanjian Kerja
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {spk.nomor_spk}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Tanggal Perjanjian Kerja
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {spk.tanggal_spk}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Periode Kerja
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {bulanLabels[periode.bulan]}{' '}
                                        {periode.tahun}
                                    </p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {spk.tanggal_mulai_kerja} s/d{' '}
                                        {spk.tanggal_selesai_kerja}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Nilai Kontrak
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        Rp{' '}
                                        {parseFloat(
                                            spk.nilai_kontrak.toString(),
                                        ).toLocaleString('id-ID')}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        PPK
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {spk.nama_ppk}
                                    </p>
                                    {spk.nip_ppk && (
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            NIP: {spk.nip_ppk}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Dibuat Oleh
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {spk.created_by}
                                    </p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {spk.created_at}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    {/* Addendums Section */}
                    {addendums && addendums.length > 0 && (
                        <ContentCard>
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-blue-700 dark:text-blue-300">
                                    Addendum Perjanjian Kerja
                                </h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-blue-200 dark:divide-blue-700">
                                        <thead className="bg-white/60 backdrop-blur-md dark:bg-neutral-800/60">
                                            <tr>
                                                <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-blue-700 uppercase dark:text-blue-200">
                                                    No.
                                                </th>
                                                <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-blue-700 uppercase dark:text-blue-200">
                                                    Nomor Addendum
                                                </th>
                                                <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-blue-700 uppercase dark:text-blue-200">
                                                    Tanggal
                                                </th>
                                                <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-blue-700 uppercase dark:text-blue-200">
                                                    Nilai Kontrak
                                                </th>
                                                <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-blue-700 uppercase dark:text-blue-200">
                                                    Status
                                                </th>
                                                <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-blue-700 uppercase dark:text-blue-200">
                                                    File
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-white/10 bg-white/30 backdrop-blur-sm dark:divide-neutral-700/20 dark:bg-neutral-800/30">
                                            {addendums.map((add, idx) => (
                                                <tr
                                                    key={add.id}
                                                    className="hover:bg-blue-50 dark:hover:bg-blue-900"
                                                >
                                                    <td className="px-4 py-2 text-sm text-blue-900 dark:text-blue-100">
                                                        {idx + 1}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-blue-900 dark:text-blue-100">
                                                        {add.nomor_spk}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-blue-900 dark:text-blue-100">
                                                        {add.tanggal_spk}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-blue-900 dark:text-blue-100">
                                                        Rp{' '}
                                                        {add.nilai_kontrak.toLocaleString(
                                                            'id-ID',
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-blue-900 dark:text-blue-100">
                                                        {getStatusBadge(
                                                            add.status,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm">
                                                        {add.file_path ? (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    handleDownload(
                                                                        add.file_path!,
                                                                    )
                                                                }
                                                            >
                                                                <Download className="mr-1 h-4 w-4" />
                                                                Download
                                                            </Button>
                                                        ) : (
                                                            <span className="text-xs text-blue-400">
                                                                Belum ada file
                                                            </span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </ContentCard>
                    )}

                    {/* Kegiatan List - Merged with Change Highlight */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Kegiatan ({kegiatan_list.length})
                            </h3>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                    <thead className="bg-neutral-50 dark:bg-neutral-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                Kode Kegiatan
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                Nama Kegiatan
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                Peran
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                Honor
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                Perubahan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                        {kegiatan_list.map((kegiatan) => {
                                            const changed = kegiatan.has_change;
                                            return (
                                                <tr
                                                    key={
                                                        kegiatan.id +
                                                        '-' +
                                                        kegiatan.peran
                                                    }
                                                    className={
                                                        changed
                                                            ? 'bg-yellow-50 dark:bg-yellow-900'
                                                            : 'hover:bg-neutral-50 dark:hover:bg-neutral-800'
                                                    }
                                                >
                                                    <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-white">
                                                        {kegiatan.kode_kegiatan}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                        {kegiatan.nama_kegiatan}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                        {getPeranLabel(
                                                            kegiatan.peran,
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                        Rp{' '}
                                                        {kegiatan.total_honor.toLocaleString(
                                                            'id-ID',
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm">
                                                        {changed ? (
                                                            <span
                                                                className="inline-flex items-center gap-1 text-yellow-700 dark:text-yellow-200"
                                                                title={`Perubahan: dari Rp ${kegiatan.original.total_honor?.toLocaleString('id-ID')} ke Rp ${kegiatan.latest.total_honor?.toLocaleString('id-ID')}`}
                                                            >
                                                                <svg
                                                                    className="h-4 w-4 text-yellow-500"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    strokeWidth="2"
                                                                    viewBox="0 0 24 24"
                                                                >
                                                                    <path
                                                                        strokeLinecap="round"
                                                                        strokeLinejoin="round"
                                                                        d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                                                                    />
                                                                </svg>
                                                                Ada perubahan
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-neutral-400">
                                                                -
                                                            </span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot className="bg-neutral-50 dark:bg-neutral-800">
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-3 text-right text-sm font-semibold text-neutral-900 dark:text-white"
                                            >
                                                Total Honor:
                                            </td>
                                            <td className="px-6 py-3 text-sm font-semibold text-neutral-900 dark:text-white">
                                                Rp{' '}
                                                {kegiatan_list
                                                    .reduce(
                                                        (sum, k) =>
                                                            sum + k.total_honor,
                                                        0,
                                                    )
                                                    .toLocaleString('id-ID')}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </ContentCard>
                    {/* Petugas Information */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Informasi Petugas
                            </h3>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Nama Petugas
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {petugas.nama}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        NIK/NIP
                                    </Label>
                                    <p className="font-medium text-neutral-900 dark:text-white">
                                        {petugas.nik}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">
                                        Jenis Petugas
                                    </Label>
                                    <p className="font-medium text-neutral-900 capitalize dark:text-white">
                                        {petugas.jenis_petugas === 'organik'
                                            ? 'Organik'
                                            : 'Non Organik'}
                                    </p>
                                </div>
                                {petugas.alamat && (
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">
                                            Alamat
                                        </Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {petugas.alamat}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </ContentCard>

                    {/* BAST Information */}
                    {bast && (
                        <ContentCard>
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Berita Acara Serah Terima (BAST)
                                </h3>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">
                                            Nomor BAST
                                        </Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {bast.nomor_bast}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">
                                            Tanggal BAST
                                        </Label>
                                        <p className="font-medium text-neutral-900 dark:text-white">
                                            {bast.tanggal_bast}
                                        </p>
                                    </div>
                                </div>

                                {bast.file_path && (
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            handleDownload(bast.file_path!)
                                        }
                                        className="w-full"
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Download BAST
                                    </Button>
                                )}
                            </div>
                        </ContentCard>
                    )}
                </div>

                {/* Sidebar - Actions */}
                <div className="space-y-6">
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Dokumen Perjanjian Kerja
                            </h3>

                            <div className="space-y-3">
                                {/* SPK - Download signed version if available, otherwise latest */}
                                {spk.file_path && (
                                    <div className="space-y-2">
                                        <Label className="text-xs text-neutral-600 dark:text-neutral-400">
                                            {spk.signed_file_path
                                                ? 'Perjanjian Kerja Bertandatangan'
                                                : 'Perjanjian Kerja Terbaru'}
                                        </Label>
                                        <Button
                                            variant="default"
                                            onClick={() =>
                                                handleDownload(
                                                    spk.signed_file_path ||
                                                        spk.file_path!,
                                                )
                                            }
                                            className="w-full"
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Download Perjanjian Kerja
                                        </Button>
                                    </div>
                                )}

                                {/* SPK Terbaru (Belum Ditandatangani) - shown only if signed version exists */}
                                {spk.signed_file_path && spk.file_path && (
                                    <div className="space-y-2">
                                        <Label className="text-xs text-neutral-600 dark:text-neutral-400">
                                            Perjanjian Kerja Terbaru (Belum
                                            Ditandatangani)
                                        </Label>
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                handleDownload(spk.file_path!)
                                            }
                                            className="w-full"
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Download Perjanjian Kerja Belum
                                            Bertanda Tangan
                                        </Button>
                                    </div>
                                )}

                                {/* Previous Signed SPK (if available after regenerate) */}
                                {spk.previous_file_path && (
                                    <div className="space-y-2">
                                        <Label className="text-xs text-neutral-600 dark:text-neutral-400">
                                            Perjanjian Kerja Bertanda Tangan
                                            Sebelumnya
                                        </Label>
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                handleDownload(
                                                    spk.previous_file_path!,
                                                )
                                            }
                                            className="w-full"
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Download Perjanjian Kerja Lama
                                        </Button>
                                    </div>
                                )}

                                {!spk.file_path && (
                                    <p className="py-4 text-center text-sm text-neutral-600 dark:text-neutral-400">
                                        File Perjanjian Kerja belum tersedia
                                    </p>
                                )}

                                {/* Upload Signed SPK */}
                                {canEdit && spk.file_path && (
                                    <div className="border-t border-neutral-200 pt-3 dark:border-neutral-700">
                                        <Label className="mb-2 block text-xs text-neutral-600 dark:text-neutral-400">
                                            Upload Perjanjian Kerja yang Sudah
                                            Ditandatangani
                                        </Label>
                                        <label
                                            htmlFor="upload-signed"
                                            className="cursor-pointer"
                                        >
                                            <Button
                                                variant="secondary"
                                                className="w-full"
                                                disabled={isUploading}
                                                asChild
                                            >
                                                <span>
                                                    <Upload className="mr-2 h-4 w-4" />
                                                    {isUploading
                                                        ? 'Mengupload...'
                                                        : 'Upload Perjanjian Kerja Bertanda Tangan'}
                                                </span>
                                            </Button>
                                        </label>
                                        <input
                                            id="upload-signed"
                                            type="file"
                                            accept="application/pdf"
                                            onChange={handleUploadSigned}
                                            className="hidden"
                                        />
                                    </div>
                                )}
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Informasi Tambahan
                            </h3>

                            <div className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-neutral-600 dark:text-neutral-400">
                                        Status
                                    </span>
                                    <span className="font-medium text-neutral-900 capitalize dark:text-white">
                                        {spk.status}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-neutral-600 dark:text-neutral-400">
                                        Dibuat
                                    </span>
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {new Date(
                                            spk.created_at,
                                        ).toLocaleDateString('id-ID')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Download Semua Perjanjian Kerja
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Download semua Perjanjian Kerja untuk semua
                                petugas di periode {bulanLabels[periode.bulan]}{' '}
                                {periode.tahun}
                            </p>
                            <Button
                                variant="outline"
                                onClick={handleDownloadAllByPeriode}
                                className="w-full"
                            >
                                <Archive className="mr-2 h-4 w-4" />
                                Download Semua Perjanjian Kerja
                            </Button>
                        </div>
                    </ContentCard>

                    {kegiatanListForDownload.length > 0 && (
                        <ContentCard>
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Download Perjanjian Kerja per Kegiatan
                                </h3>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Download Perjanjian Kerja yang sudah
                                    ditandatangani untuk petugas pada kegiatan
                                    tertentu
                                </p>
                                <div className="space-y-2">
                                    {kegiatanListForDownload.map((kegiatan) => (
                                        <div key={kegiatan.id}>
                                            {kegiatan.all_signed ? (
                                                <Button
                                                    variant="outline"
                                                    onClick={() =>
                                                        handleDownloadAllByKegiatan(
                                                            kegiatan.hashed_id,
                                                            kegiatan.nama_kegiatan,
                                                        )
                                                    }
                                                    className="w-full justify-start"
                                                    size="sm"
                                                >
                                                    <Download className="mr-2 h-4 w-4" />
                                                    <div className="flex-1 text-left">
                                                        <div className="font-medium">
                                                            {
                                                                kegiatan.nama_kegiatan
                                                            }
                                                        </div>
                                                        <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                            {
                                                                kegiatan.kode_kegiatan
                                                            }{' '}
                                                            •{' '}
                                                            {
                                                                kegiatan.jumlah_spk
                                                            }{' '}
                                                            Perjanjian Kerja
                                                        </div>
                                                    </div>
                                                </Button>
                                            ) : (
                                                <div className="flex w-full items-center justify-between rounded-md border border-neutral-200 p-3 text-sm dark:border-neutral-700">
                                                    <div className="flex-1">
                                                        <div className="font-medium text-neutral-700 dark:text-neutral-300">
                                                            {
                                                                kegiatan.nama_kegiatan
                                                            }
                                                        </div>
                                                        <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                            {
                                                                kegiatan.kode_kegiatan
                                                            }{' '}
                                                            •{' '}
                                                            {
                                                                kegiatan.jumlah_spk
                                                            }{' '}
                                                            Perjanjian Kerja
                                                        </div>
                                                    </div>
                                                    <span className="text-xs text-orange-600 dark:text-orange-400">
                                                        Belum semua bertanda
                                                        tangan
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </ContentCard>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
