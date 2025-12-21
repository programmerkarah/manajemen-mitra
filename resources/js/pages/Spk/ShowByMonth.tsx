import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Archive, ArrowLeft, Download, FileText, Upload } from 'lucide-react';
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
    created_by: string;
    created_at: string;
    updated_at: string;
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

interface PetugasListItem {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    petugas_nama: string;
    petugas_nik: string;
    status: string;
}

interface ShowByMonthProps {
    spk: Spk;
    petugas: Petugas;
    kegiatan_list: Kegiatan[];
    periode: PeriodeAlokasi;
    bast: Bast | null;
    petugas_list: PetugasListItem[];
    bulan: number;
    tahun: number;
    bulan_label: string;
}

const bulanLabels: Record<number, string> = {
    1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
    5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
    9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember',
};

export default function ShowByMonth({ spk, petugas, kegiatan_list, periode, bast, petugas_list, bulan, tahun, bulan_label }: ShowByMonthProps) {
    const { auth } = usePage<SharedData>().props;
    const [showUploadModal, setShowUploadModal] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SPK', href: '/spk' },
        { title: `Detail SPK ${bulan_label} ${tahun}`, href: '#' },
    ];

    const canEdit = auth.activeRole?.name === 'admin' || auth.activeRole?.name === 'approver';

    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
    };

    const handleDownloadAll = () => {
        window.location.href = `/spk/download-all?bulan=${bulan}&tahun=${tahun}`;
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

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            'pcl_ppl': 'Petugas Pencacahan',
            'pml': 'Pemeriksa Lapangan',
            'pengolahan': 'Petugas Pengolahan',
            'pengawas_pengolahan': 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
    };

    const handleUploadSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.file) return;

        post(`/spk/${spk.hashed_id}/upload-signed`, {
            onSuccess: () => {
                setShowUploadModal(false);
                reset();
            },
        });
    };

    const handleSelectPetugas = (spkHashedId: string) => {
        router.post('/spk/month', {
            bulan: bulan,
            tahun: tahun,
            spk: spkHashedId
        });
    };

    const formatPeriodeKerja = (tanggalMulai: string, tanggalSelesai: string) => {
        const mulai = new Date(tanggalMulai);
        const selesai = new Date(tanggalSelesai);
        
        const tanggalMulaiNum = mulai.getDate();
        const tanggalSelesaiNum = selesai.getDate();
        const bulanIndex = mulai.getMonth() + 1;
        const tahunValue = mulai.getFullYear();
        
        return `${tanggalMulaiNum} - ${tanggalSelesaiNum} ${bulanLabels[bulanIndex]} ${tahunValue}`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail SPK ${bulan_label} ${tahun} - ${petugas.nama}`} />

            <PageHeader
                title={`Detail SPK ${bulan_label} ${tahun}`}
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

            <div className="grid gap-6 md:grid-cols-4">
                {/* Sidebar - Petugas List */}
                <div className="md:col-span-1">
                    <ContentCard>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Daftar Petugas ({petugas_list.length})
                                </h3>
                            </div>

                            <Button 
                                variant="outline" 
                                onClick={handleDownloadAll}
                                className="w-full"
                                size="sm"
                            >
                                <Archive className="mr-2 h-4 w-4" />
                                Download Semua SPK
                            </Button>

                            <div className="space-y-2 max-h-[600px] overflow-y-auto">
                                {petugas_list.map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() => handleSelectPetugas(item.hashed_id)}
                                        className={`w-full rounded-lg border p-3 text-left transition-colors ${
                                            item.id === spk.id
                                                ? 'border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800'
                                                : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                        }`}
                                    >
                                        <div className="font-medium text-sm text-neutral-900 dark:text-white">
                                            {item.petugas_nama}
                                        </div>
                                        <div className="text-xs text-neutral-600 dark:text-neutral-400 mt-0.5">
                                            {item.petugas_nik}
                                        </div>
                                        <div className="mt-1">
                                            {getStatusBadge(item.status)}
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </ContentCard>
                </div>

                {/* Main Content - SPK Details */}
                <div className="md:col-span-3 space-y-6">
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
                                        {formatPeriodeKerja(spk.tanggal_mulai_kerja, spk.tanggal_selesai_kerja)}
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

                    {/* Documents & Actions */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Dokumen SPK
                            </h3>

                            {/* History Section */}
                            {spk.file_path && (
                                <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                    <h4 className="text-sm font-semibold text-neutral-900 dark:text-white mb-2">
                                        History Dokumen
                                    </h4>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex items-start gap-2">
                                            <FileText className="h-4 w-4 text-neutral-600 dark:text-neutral-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-neutral-900 dark:text-white font-medium">
                                                    Dokumen SPK {spk.status === 'diterbitkan' ? 'Diterbitkan' : 'Diupload'}
                                                </p>
                                                <p className="text-neutral-600 dark:text-neutral-400 text-xs mt-0.5">
                                                    Dibuat oleh {spk.created_by} pada {spk.created_at} WIB
                                                </p>
                                                {spk.updated_at !== spk.created_at && (
                                                    <p className="text-neutral-600 dark:text-neutral-400 text-xs mt-0.5">
                                                        Terakhir diperbarui pada {spk.updated_at} WIB
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="space-y-3">
                                {spk.file_path ? (
                                    <>
                                        <Button
                                            variant="default"
                                            onClick={() => handleDownload(spk.file_path!)}
                                            className="w-full"
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Download SPK
                                        </Button>
                                        
                                        {canEdit && spk.status === 'draft' && (
                                            <Button
                                                variant="outline"
                                                onClick={() => setShowUploadModal(true)}
                                                className="w-full"
                                            >
                                                <Upload className="mr-2 h-4 w-4" />
                                                Upload Dokumen Bertanda Tangan
                                            </Button>
                                        )}
                                    </>
                                ) : (
                                    <div>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400 text-center py-4 mb-3">
                                            File SPK belum tersedia
                                        </p>
                                        {canEdit && (
                                            <Button
                                                variant="outline"
                                                onClick={() => setShowUploadModal(true)}
                                                className="w-full"
                                            >
                                                <Upload className="mr-2 h-4 w-4" />
                                                Upload Dokumen SPK
                                            </Button>
                                        )}
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
            </div>

            {/* Upload Modal */}
            {showUploadModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={() => setShowUploadModal(false)}>
                    <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800" onClick={(e) => e.stopPropagation()}>
                        <h3 className="mb-4 text-lg font-semibold text-neutral-900 dark:text-white">
                            Upload Dokumen SPK
                        </h3>
                        
                        <form onSubmit={handleUploadSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="file">Pilih File PDF</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".pdf"
                                    onChange={handleFileChange}
                                    required
                                />
                                {errors.file && (
                                    <p className="mt-1 text-sm text-red-600">{errors.file}</p>
                                )}
                                <p className="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                                    Format: PDF, Maksimal 10MB
                                </p>
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setShowUploadModal(false);
                                        reset();
                                    }}
                                >
                                    Batal
                                </Button>
                                <Button type="submit" disabled={processing || !data.file}>
                                    {processing ? 'Mengunggah...' : 'Upload'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
