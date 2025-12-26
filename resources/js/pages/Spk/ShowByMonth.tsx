import React, { useState } from 'react';
import { usePage, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Download, Upload, FileText, Archive } from 'lucide-react';

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
    addendum_number: number;
    parent_spk_id: number | null;
    created_by: string;
    created_at: string;
    updated_at: string;
}

interface SpkDocument {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    tanggal_spk: string;
    addendum_number: number;
    file_path: string | null;
    status: string;
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

interface MergedKegiatan {
    id: number;
    hashed_id: string;
    nama_kegiatan: string;
    jenis_kegiatan: string;
    tahun_anggaran: number;
    petugas_nik: string;
    status: string;
    peran: string;
    total_honor: number;
    has_change: boolean;
    original: {
        total_honor: number;
    };
    latest: {
        total_honor: number;
    };
}

interface Addendum {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    tanggal_spk: string;
    nilai_kontrak: number;
    status: string;
    file_path: string | null;
}

interface PeriodeAlokasi {
    id: number;
    nama_periode: string;
    bulan: number;
    tahun: number;
}

interface Bast {
    id: number;
    nomor_bast: string;
    tanggal_bast: string;
    file_path: string | null;
}

interface PetugasListItem {
    id: number;
    hashed_id: string;
    petugas_nama: string;
    petugas_nik: string;
}

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface SharedData {
    auth: {
        activeRole?: {
            name: string;
        };
    };
    [key: string]: any;
}

interface ShowByMonthProps {
    spk: Spk;
    spk_documents: SpkDocument[];
    petugas: Petugas;
    kegiatan_list: MergedKegiatan[];
    addendums?: Addendum[];
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

export default function ShowByMonth({
    spk,
    spk_documents,
    petugas,
    kegiatan_list,
    addendums = [],
    periode,
    bast,
    petugas_list,
    bulan,
    tahun,
    bulan_label
}: ShowByMonthProps) {
    // The 'spk' prop contains the current SPK being viewed (could be original or addendum)
    // If it's an original (addendum_number === 0), show it in the main card
    // If it's an addendum (addendum_number > 0), show current SPK details in addendum card
    // and we would need to fetch/show the original elsewhere (currently we'll show current SPK as main)
    const isAddendum = spk.addendum_number > 0;
    const { auth } = usePage<SharedData>().props;
    const [uploadingDocId, setUploadingDocId] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SPK', href: '/spk' },
        { title: `Detail SPK ${bulan_label} ${tahun}`, href: '#' },
    ];

    const canEdit = auth.activeRole?.name === 'admin' || auth.activeRole?.name === 'approver' || auth.activeRole?.name === 'operator';

    // Helper functions (must be inside component for hooks/props access)
    const getPeranLabel = (peran: string): string => {
        const labels: Record<string, string> = {
            'pcl_ppl': 'Petugas Pencacahan',
            'pml': 'Pemeriksa Lapangan',
            'pengolahan': 'Petugas Pengolahan',
            'pengawas_pengolahan': 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const formatIndonesianDate = (isoDate: string): string => {
        if (!isoDate) return '-';
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const date = new Date(isoDate);
        if (isNaN(date.getTime())) return isoDate;
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
    };

    const formatPeriodeKerja = (tanggalMulai: string, tanggalSelesai: string) => {
        if (!tanggalMulai || !tanggalSelesai) return '-';
        const mulai = new Date(tanggalMulai);
        const selesai = new Date(tanggalSelesai);
        if (isNaN(mulai.getTime()) || isNaN(selesai.getTime())) return '-';
        const tanggalMulaiNum = mulai.getDate();
        const tanggalSelesaiNum = selesai.getDate();
        const bulanIndex = mulai.getMonth() + 1;
        const tahunValue = mulai.getFullYear();
        return `${tanggalMulaiNum} - ${tanggalSelesaiNum} ${bulanLabels[bulanIndex]} ${tahunValue}`;
    };

    const getDocumentLabel = (addendumNumber: number) => {
        if (addendumNumber === 0) return 'SPK Asli';
        if (addendumNumber === 1) return 'SPK Addendum';
        if (addendumNumber === 2) return 'SPK Addendum Kedua';
        if (addendumNumber === 3) return 'SPK Addendum Ketiga';
        return `SPK Addendum Ke-${addendumNumber}`;
    };

    const handleDownload = (filePath: string) => {
        window.open(`/${filePath}`, '_blank');
    };

    const handleDownloadAll = () => {
        window.location.href = `/spk/download-all?bulan=${bulan}&tahun=${tahun}`;
    };

    const handleSelectPetugas = (spkHashedId: string) => {
        router.post('/spk/month', {
            bulan: bulan,
            tahun: tahun,
            spk: spkHashedId
        });
    };

    const handleUploadSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.file || !uploadingDocId) return;
        const doc = spk_documents.find((d: SpkDocument) => d.hashed_id === uploadingDocId);
        if (!doc) return;
        post(`/spk/${doc.hashed_id}/upload-signed`, {
            onSuccess: () => {
                setUploadingDocId(null);
                reset();
            },
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
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
            <PageHeader
                title={`Detail SPK ${bulan_label} ${tahun}`}
            >
            </PageHeader>

            <div className="grid gap-6 md:grid-cols-3 overflow-x-hidden max-w-full">
                {/* Sidebar - Petugas List */}
                <div className="md:col-span-1 min-w-0 w-full">
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
                                {Array.from(
                                    petugas_list.reduce((map, item) => {
                                        // Gabungkan berdasarkan NIK jika ada, jika tidak pakai ID
                                        const key = item.petugas_nik ? item.petugas_nik : item.id;
                                        if (!map.has(key)) {
                                            map.set(key, item);
                                        }
                                        return map;
                                    }, new Map())
                                ).map(([key, item]) => (
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
                                    </button>
                                ))}
                            </div>
                        </div>
                    </ContentCard>
                </div>

                {/* Main Content - SPK Details */}
                <div className="md:col-span-2 space-y-6 min-w-0 w-full">
                    {/* Always show original SPK info */}
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
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Nomor SPK</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">{spk.nomor_spk}</p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Tanggal SPK</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">{formatIndonesianDate(spk.tanggal_spk)}</p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Periode Kerja</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">
                                        {formatPeriodeKerja(spk.tanggal_mulai_kerja, spk.tanggal_selesai_kerja)}
                                    </p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Nilai Kontrak</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">
                                        Rp {parseFloat(spk.nilai_kontrak?.toString() || '0').toLocaleString('id-ID')}
                                    </p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">PPK</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">{spk.nama_ppk}</p>
                                    {spk.nip_ppk && (
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400 break-words">
                                            NIP: {spk.nip_ppk}
                                        </p>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Dibuat Oleh</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">{spk.created_by}</p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400 break-words">{spk.created_at}</p>
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    {/* If current SPK is addendum, show its info as a separate card */}
                    {isAddendum && (
                        <ContentCard>
                            <div className="space-y-6">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <h3 className="text-lg font-semibold text-blue-700 dark:text-blue-300">
                                            Informasi Addendum SPK
                                        </h3>
                                        <p className="text-sm text-blue-700 dark:text-blue-300">
                                            Perubahan/addendum terhadap SPK asli
                                        </p>
                                    </div>
                                    {getStatusBadge(spk.status)}
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Nomor Addendum</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{spk.nomor_spk}</p>
                                    </div>
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Tanggal Addendum</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{formatIndonesianDate(spk.tanggal_spk)}</p>
                                    </div>
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Periode Kerja</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">
                                            {formatPeriodeKerja(spk.tanggal_mulai_kerja, spk.tanggal_selesai_kerja)}
                                        </p>
                                    </div>
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Nilai Kontrak</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">
                                            Rp {parseFloat(spk.nilai_kontrak.toString()).toLocaleString('id-ID')}
                                        </p>
                                    </div>
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">PPK</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{spk.nama_ppk}</p>
                                        {spk.nip_ppk && (
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400 break-words">
                                                NIP: {spk.nip_ppk}
                                            </p>
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Dibuat Oleh</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{spk.created_by}</p>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400 break-words">{spk.created_at}</p>
                                    </div>
                                </div>
                            </div>
                        </ContentCard>
                    )}

                    {/* Petugas Information */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Informasi Petugas
                            </h3>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Nama Petugas</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">{petugas.nama}</p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">NIK/NIP</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium break-words">{petugas.nik}</p>
                                </div>
                                <div className="min-w-0">
                                    <Label className="text-neutral-600 dark:text-neutral-400">Jenis Petugas</Label>
                                    <p className="text-neutral-900 dark:text-white font-medium capitalize break-words">
                                        {petugas.jenis_petugas === 'organik' ? 'Organik' : 'Non Organik'}
                                    </p>
                                </div>
                                {petugas.alamat && (
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Alamat</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{petugas.alamat}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </ContentCard>

                    {/* Kegiatan List - Merged with Change Highlight */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Kegiatan ({kegiatan_list.length})
                            </h3>

                            <div className="w-full overflow-x-auto">
                                <table className="w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                    <thead className="bg-neutral-50 dark:bg-neutral-800">
                                        <tr>
                                            <th className="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300 max-w-xs">
                                                Nama Kegiatan
                                            </th>
                                            <th className="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300 whitespace-nowrap">
                                                Peran
                                            </th>
                                            <th className="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300 whitespace-nowrap">
                                                Honor
                                            </th>
                                            <th className="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300 whitespace-nowrap">
                                                Perubahan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                        {kegiatan_list.map((kegiatan, idx) => {
                                            const changed = kegiatan.has_change;
                                            return (
                                                <tr key={kegiatan.id + '-' + kegiatan.peran} className={changed ? "bg-yellow-50 dark:bg-yellow-900" : "hover:bg-neutral-50 dark:hover:bg-neutral-800"}>
                
                                                    <td className="px-3 py-3 text-sm text-neutral-900 dark:text-white max-w-xs">
                                                        <div className="break-words">{kegiatan.nama_kegiatan}</div>
                                                    </td>
                                                    <td className="px-3 py-3 text-sm text-neutral-900 dark:text-white whitespace-nowrap">
                                                        {getPeranLabel(kegiatan.peran)}
                                                    </td>
                                                    <td className="px-3 py-3 text-right text-neutral-900 dark:text-white whitespace-nowrap">
                                                        Rp {kegiatan.total_honor.toLocaleString('id-ID')}
                                                    </td>
                                                    <td className="px-3 py-3 text-sm whitespace-nowrap">
                                                        {changed ? (
                                                            <span className="inline-flex items-start gap-1 text-yellow-700 dark:text-yellow-200" title={`Perubahan: dari Rp ${kegiatan.original.total_honor?.toLocaleString('id-ID')} ke Rp ${kegiatan.latest.total_honor?.toLocaleString('id-ID')}`}>
                                                                <svg className="w-4 h-4 text-yellow-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" /></svg>
                                                                <span className="break-words">Ada perubahan sebesar Rp {Math.abs(kegiatan.latest.total_honor - kegiatan.original.total_honor).toLocaleString('id-ID')}</span>
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-neutral-400">-</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot className="bg-neutral-50 dark:bg-neutral-800">
                                        <tr>
                                            <td colSpan={2} className="px-3 py-3 text-right text-sm font-semibold text-neutral-900 dark:text-white">
                                                Total Honor:
                                            </td>
                                            <td colSpan={1} className="px-3 py-3 text-right font-semibold text-neutral-900 dark:text-white break-words">
                                                Rp {kegiatan_list.reduce((sum, k) => sum + k.total_honor, 0).toLocaleString('id-ID')}
                                            </td>
                                            <td colSpan={1}></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </ContentCard>

                    {/* Documents History */}
                    <ContentCard>
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                History Dokumen SPK
                            </h3>

                            <div className="space-y-3">
                                {spk_documents.map((doc) => (
                                    <div key={doc.id} className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                        <div className="flex flex-col md:flex-row items-start justify-between gap-4">
                                            <div className="flex items-start gap-3 flex-1 min-w-0">
                                                <FileText className="h-5 w-5 text-neutral-600 dark:text-neutral-400 mt-0.5 flex-shrink-0" />
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <p className="text-neutral-900 dark:text-white font-semibold">
                                                            {getDocumentLabel(doc.addendum_number)}
                                                        </p>
                                                        {getStatusBadge(doc.status)}
                                                    </div>
                                                    <p className="text-sm text-neutral-600 dark:text-neutral-400 mt-1 break-words">
                                                        {doc.nomor_spk}
                                                    </p>
                                                    <p className="text-xs text-neutral-600 dark:text-neutral-400 mt-1 break-words">
                                                        Dibuat oleh {doc.created_by} pada {doc.created_at}
                                                    </p>
                                                    {doc.updated_at !== doc.created_at && (
                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400 break-words">
                                                            Diperbarui pada {doc.updated_at}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex flex-col gap-2 flex-shrink-0">
                                                {doc.file_path ? (
                                                    <>
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            onClick={() => handleDownload(doc.file_path!)}
                                                        >
                                                            <Download className="mr-2 h-3.5 w-3.5" />
                                                            Download
                                                        </Button>
                                                        {canEdit && doc.status === 'draft' && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => setUploadingDocId(doc.hashed_id)}
                                                            >
                                                                <Upload className="mr-2 h-3.5 w-3.5" />
                                                                Upload Signed
                                                            </Button>
                                                        )}
                                                    </>
                                                ) : (
                                                    <>
                                                        <p className="text-xs text-neutral-500 dark:text-neutral-400 mb-1">File belum tersedia</p>
                                                        {canEdit && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => setUploadingDocId(doc.hashed_id)}
                                                            >
                                                                <Upload className="mr-2 h-3.5 w-3.5" />
                                                                Upload
                                                            </Button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
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
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Nomor BAST</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{bast.nomor_bast}</p>
                                    </div>
                                    <div className="min-w-0">
                                        <Label className="text-neutral-600 dark:text-neutral-400">Tanggal BAST</Label>
                                        <p className="text-neutral-900 dark:text-white font-medium break-words">{bast.tanggal_bast}</p>
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
            {uploadingDocId && (() => {
                const doc = spk_documents.find((d: SpkDocument) => d.hashed_id === uploadingDocId);
                if (!doc) return null;
                return (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={() => setUploadingDocId(null)}>
                        <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800" onClick={(e) => e.stopPropagation()}>
                            <h3 className="mb-4 text-lg font-semibold text-neutral-900 dark:text-white">
                                Upload {getDocumentLabel(doc.addendum_number)}
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
                                            setUploadingDocId(null);
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
                );
            })()}
        </AppLayout>
    );
}
