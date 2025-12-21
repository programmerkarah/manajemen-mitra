import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Download, FileText, Pencil } from 'lucide-react';

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

interface Kegiatan {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
    peran: string;
    total_honor: number;
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

interface ShowProps {
    spk: Spk;
    petugas: Petugas;
    kegiatan_list: Kegiatan[];
    periode: PeriodeAlokasi;
    bast: Bast | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SPK', href: '/spk' },
    { title: 'Detail SPK', href: '#' },
];

const bulanLabels: Record<number, string> = {
    1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
    5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
    9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember',
};

export default function Show({ spk, petugas, kegiatan_list, periode, bast }: ShowProps) {
    const { auth } = usePage<SharedData>().props;
    const canEdit = auth.activeRole?.name === 'admin' || auth.activeRole?.name === 'approver';

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            'pcl_ppl': 'Petugas Pencacahan',
            'pml': 'Pemeriksa Lapangan',
            'pengolahan': 'Petugas Pengolahan',
            'pengawas_pengolahan': 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
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
            <Head title={`Detail SPK - ${spk.nomor_spk}`} />

            <PageHeader
                title="Detail SPK"
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
                <div className="md:col-span-2 space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Informasi SPK
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Detail surat perjanjian kerja petugas
                                    </p>
                                </div>
                                {getStatusBadge(spk.status)}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">Nomor SPK</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">{spk.nomor_spk}</p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">Tanggal SPK</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">{spk.tanggal_spk}</p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">Periode Kerja</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">
                                        {bulanLabels[periode.bulan]} {periode.tahun}
                                    </p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        {spk.tanggal_mulai_kerja} s/d {spk.tanggal_selesai_kerja}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">Nilai Kontrak</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">
                                        Rp {parseFloat(spk.nilai_kontrak.toString()).toLocaleString('id-ID')}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">PPK</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">{spk.nama_ppk}</p>
                                    {spk.nip_ppk && (
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            NIP: {spk.nip_ppk}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">Dibuat Oleh</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">{spk.created_by}</p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">{spk.created_at}</p>
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    {/* Kegiatan List */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Kegiatan ({kegiatan_list.length})
                            </h3>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                    <thead className="bg-neutral-50 dark:bg-neutral-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                                Kode Kegiatan
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                                Nama Kegiatan
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                                Peran
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                                Honor
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                        {kegiatan_list.map((kegiatan) => (
                                            <tr key={kegiatan.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                                <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-white">
                                                    {kegiatan.kode_kegiatan}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    {kegiatan.nama_kegiatan}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    {getPeranLabel(kegiatan.peran)}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    Rp {kegiatan.total_honor.toLocaleString('id-ID')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-neutral-50 dark:bg-neutral-800">
                                        <tr>
                                            <td colSpan={3} className="px-6 py-3 text-right text-sm font-semibold text-neutral-900 dark:text-white">
                                                Total Honor:
                                            </td>
                                            <td className="px-6 py-3 text-sm font-semibold text-neutral-900 dark:text-white">
                                                Rp {kegiatan_list.reduce((sum, k) => sum + k.total_honor, 0).toLocaleString('id-ID')}
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
                                    <Label className="text-neutral-600 dark:text-neutral-400">Nama Petugas</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">{petugas.nama}</p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">NIK/NIP</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium">{petugas.nik}</p>
                                </div>
                                <div>
                                    <Label className="text-neutral-600 dark:text-neutral-400">Jenis Petugas</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium capitalize">
                                        {petugas.jenis_petugas === 'organik' ? 'Organik' : 'Non Organik'}
                                    </p>
                                </div>
                                {petugas.alamat && (
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">Alamat</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium">{petugas.alamat}</p>
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
                                        <Label className="text-neutral-600 dark:text-neutral-400">Nomor BAST</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium">{bast.nomor_bast}</p>
                                    </div>
                                    <div>
                                        <Label className="text-neutral-600 dark:text-neutral-400">Tanggal BAST</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium">{bast.tanggal_bast}</p>
                                    </div>
                                </div>

                                {bast.file_path && (
                                    <Button
                                        variant="outline"
                                        onClick={() => handleDownload(bast.file_path!)}
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
                                Dokumen
                            </h3>

                            <div className="space-y-3">
                                {spk.file_path && (
                                    <Button
                                        variant="default"
                                        onClick={() => handleDownload(spk.file_path!)}
                                        className="w-full"
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Download SPK
                                    </Button>
                                )}

                                {!spk.file_path && (
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400 text-center py-4">
                                        File SPK belum tersedia
                                    </p>
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
                                    <span className="text-neutral-600 dark:text-neutral-400">Status</span>
                                    <span className="font-medium text-neutral-900 dark:text-white capitalize">
                                        {spk.status}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-neutral-600 dark:text-neutral-400">Dibuat</span>
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {new Date(spk.created_at).toLocaleDateString('id-ID')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </ContentCard>
                </div>
            </div>
        </AppLayout>
    );
}
