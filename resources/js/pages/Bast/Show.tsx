import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Check, Download, FileText, User } from 'lucide-react';

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
    status: 'draft' | 'final' | 'dibatalkan';
    catatan: string | null;
    created_by: string;
    created_at: string;
}

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
}

interface Periode {
    id: number;
    hashed_id: string;
    bulan: number;
    tahun: number;
    bulan_label: string;
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
    created_by: string;
    created_at: string;
    is_current: boolean;
}

interface ShowProps {
    bast: Bast;
    kegiatan: Kegiatan;
    periode: Periode;
    bast_petugas: BastPetugas[];
    bast_history: BastHistory[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Detail BAST', href: '#' },
];

export default function Show({
    bast,
    kegiatan,
    periode,
    bast_petugas,
    bast_history,
}: ShowProps) {
    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'final':
                return <Badge variant="default">Final</Badge>;
            case 'draft':
                return <Badge variant="secondary">Draft</Badge>;
            case 'dibatalkan':
                return <Badge variant="destructive">Dibatalkan</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    };

    // Check if there's data in specific columns
    const hasListingData = bast_petugas.some((p) => p.hasil_listing !== null);
    const hasPendataanData = bast_petugas.some(
        (p) => p.hasil_pendataan_lapangan !== null,
    );
    const hasPengolahanData = bast_petugas.some(
        (p) => p.hasil_pengolahan !== null,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail BAST - ${bast.nomor_bast}`} />

            <PageHeader
                title="Detail BAST"
                description={`Berita Acara Serah Terima - ${kegiatan.nama_kegiatan}`}
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/bast">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            </PageHeader>

            <div className="w-full" style={{ maxWidth: '84vw' }}>
                <div className="max-w-full overflow-hidden">
                    <div className="grid gap-6 md:grid-cols-3">
                        {/* Main Content - BAST Details */}
                        <div className="min-w-0 space-y-6 md:col-span-2">
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

                                    <div className="space-y-4">
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Nomor BAST
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {bast.nomor_bast}
                                            </p>
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <Label className="text-neutral-600 dark:text-neutral-400">
                                                    Tanggal BAST
                                                </Label>
                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                    {formatDate(
                                                        bast.tanggal_bast,
                                                    )}
                                                </p>
                                            </div>
                                            <div>
                                                <Label className="text-neutral-600 dark:text-neutral-400">
                                                    Tanggal Serah Terima
                                                </Label>
                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                    {formatDate(
                                                        bast.tanggal_serah_terima,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Periode
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {periode.bulan_label}{' '}
                                                {periode.tahun}
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
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Uraian Pekerjaan
                                            </Label>
                                            <p className="font-medium break-words text-neutral-900 dark:text-white">
                                                {bast.uraian_pekerjaan}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Menggunakan FASIH
                                            </Label>
                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                {bast.menggunakan_fasih
                                                    ? 'Ya'
                                                    : 'Tidak'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="space-y-4 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                        <h4 className="font-semibold text-neutral-900 dark:text-white">
                                            Pihak Terkait
                                        </h4>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <Label className="text-neutral-600 dark:text-neutral-400">
                                                    Ketua Tim
                                                </Label>
                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                    {bast.nama_ketua_tim}
                                                </p>
                                                {bast.nip_ketua_tim && (
                                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                        NIP: {bast.nip_ketua_tim}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <Label className="text-neutral-600 dark:text-neutral-400">
                                                    PPK
                                                </Label>
                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                    {bast.nama_ppk}
                                                </p>
                                                {bast.nip_ppk && (
                                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                        NIP: {bast.nip_ppk}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {bast.catatan && (
                                        <div>
                                            <Label className="text-neutral-600 dark:text-neutral-400">
                                                Catatan
                                            </Label>
                                            <p className="font-medium break-words text-neutral-900 dark:text-white">
                                                {bast.catatan}
                                            </p>
                                        </div>
                                    )}

                                    <div className="border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                            <User className="h-4 w-4 flex-shrink-0" />
                                            <span className="break-words">
                                                Dibuat oleh {bast.created_by}{' '}
                                                pada {bast.created_at}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </ContentCard>

                            {/* Daftar Petugas */}
                            <ContentCard>
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Daftar Petugas
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            {bast_petugas.length} petugas yang
                                            menyerahkan pekerjaan
                                        </p>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead className="border-b border-neutral-200 dark:border-neutral-800">
                                                <tr>
                                                    <th className="p-2 text-left text-neutral-900 dark:text-white">
                                                        No
                                                    </th>
                                                    <th className="p-2 text-left text-neutral-900 dark:text-white">
                                                        Nama Petugas
                                                    </th>
                                                    <th className="p-2 text-left text-neutral-900 dark:text-white">
                                                        Nomor SPK
                                                    </th>
                                                    {hasListingData && (
                                                        <th className="p-2 text-left text-neutral-900 dark:text-white">
                                                            Listing
                                                        </th>
                                                    )}
                                                    {hasPendataanData && (
                                                        <th className="p-2 text-left text-neutral-900 dark:text-white">
                                                            Pendataan Lapangan
                                                        </th>
                                                    )}
                                                    {hasPengolahanData && (
                                                        <th className="p-2 text-left text-neutral-900 dark:text-white">
                                                            Pengolahan
                                                        </th>
                                                    )}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {bast_petugas.map((p, index) => (
                                                    <tr
                                                        key={p.id}
                                                        className="border-b border-neutral-200 dark:border-neutral-800"
                                                    >
                                                        <td className="p-2 text-neutral-900 dark:text-white">
                                                            {index + 1}
                                                        </td>
                                                        <td className="p-2 text-neutral-900 dark:text-white">
                                                            {p.petugas_nama}
                                                        </td>
                                                        <td className="p-2 text-neutral-900 dark:text-white">
                                                            {p.nomor_spk}
                                                        </td>
                                                        {hasListingData && (
                                                            <td className="p-2 text-neutral-900 dark:text-white">
                                                                {p.hasil_listing ??
                                                                    '-'}{' '}
                                                                {p.satuan_listing &&
                                                                    p.hasil_listing &&
                                                                    p.satuan_listing}
                                                            </td>
                                                        )}
                                                        {hasPendataanData && (
                                                            <td className="p-2 text-neutral-900 dark:text-white">
                                                                {p.hasil_pendataan_lapangan ??
                                                                    '-'}{' '}
                                                                {p.satuan_pendataan_lapangan &&
                                                                    p.hasil_pendataan_lapangan &&
                                                                    p.satuan_pendataan_lapangan}
                                                            </td>
                                                        )}
                                                        {hasPengolahanData && (
                                                            <td className="p-2 text-neutral-900 dark:text-white">
                                                                {p.hasil_pengolahan ??
                                                                    '-'}{' '}
                                                                {p.satuan_pengolahan &&
                                                                    p.hasil_pengolahan &&
                                                                    p.satuan_pengolahan}
                                                            </td>
                                                        )}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </ContentCard>

                            {/* Download BAST */}
                            {bast.file_path && (
                                <ContentCard>
                                    <div className="space-y-4">
                                        <div>
                                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                                Download BAST
                                            </h3>
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                Unduh dokumen BAST dalam format
                                                PDF
                                            </p>
                                        </div>

                                        <Button
                                            onClick={() =>
                                                handleDownload(bast.file_path!)
                                            }
                                            className="w-full justify-start"
                                            variant="default"
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Download BAST
                                        </Button>
                                    </div>
                                </ContentCard>
                            )}
                        </div>

                        {/* Sidebar - History */}
                        <div className="min-w-0 space-y-6">
                            <ContentCard>
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                            Riwayat BAST
                                        </h3>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            {bast_history.length} BAST untuk
                                            kegiatan ini
                                        </p>
                                    </div>

                                    <div className="space-y-3">
                                        {bast_history.map((b, index) => (
                                            <div
                                                key={b.id}
                                                className={`rounded-lg border p-3 ${
                                                    b.is_current
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-neutral-200 dark:border-neutral-800'
                                                }`}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center gap-2">
                                                            {b.is_current && (
                                                                <Badge
                                                                    variant="default"
                                                                    className="text-xs"
                                                                >
                                                                    Saat ini
                                                                </Badge>
                                                            )}
                                                            {index === 0 &&
                                                                !b.is_current && (
                                                                    <Badge
                                                                        variant="secondary"
                                                                        className="text-xs"
                                                                    >
                                                                        Terbaru
                                                                    </Badge>
                                                                )}
                                                        </div>
                                                        <p className="mt-1 truncate text-sm font-medium text-neutral-900 dark:text-white">
                                                            {b.nomor_bast}
                                                        </p>
                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                            {b.periode}
                                                        </p>
                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                            {formatDate(
                                                                b.tanggal_bast,
                                                            )}
                                                        </p>
                                                    </div>
                                                    {!b.is_current && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/bast/${b.hashed_id}`}
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
