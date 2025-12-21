import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, Download, Upload, FileUp } from 'lucide-react';
import { useState } from 'react';

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
}

interface KegiatanItem {
    kode_kegiatan: string;
    nama_kegiatan: string;
    peran: string;
}

interface SpkItem {
    id: number;
    hashed_id: string;
    nomor_spk: string;
    tanggal_spk: string;
    nilai_kontrak: number;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    file_path: string | null;
    petugas: Petugas;
    jumlah_kegiatan: number;
    kegiatan_list: KegiatanItem[];
}

interface ListProps {
    spk_list: SpkItem[];
    bulan: number;
    tahun: number;
    bulan_label: string;
}

const bulanLabels: Record<number, string> = {
    1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
    5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
    9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember',
};

export default function List({ spk_list, bulan, tahun, bulan_label }: ListProps) {
    const [selectedSpkId, setSelectedSpkId] = useState<number | null>(spk_list[0]?.id || null);
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [uploadingSpkId, setUploadingSpkId] = useState<number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    const selectedSpk = spk_list.find(spk => spk.id === selectedSpkId);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SPK', href: '/spk' },
        { title: `Daftar SPK ${bulan_label} ${tahun}`, href: '#' },
    ];

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

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            'pcl_ppl': 'Petugas Pencacahan',
            'pml': 'Pemeriksa Lapangan',
            'pengolahan': 'Petugas Pengolahan',
            'pengawas_pengolahan': 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const handleUploadClick = (spkId: number) => {
        setUploadingSpkId(spkId);
        setShowUploadModal(true);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
    };

    const handleUploadSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!uploadingSpkId || !data.file) return;

        const formData = new FormData();
        formData.append('file', data.file);

        post(`/spk/${uploadingSpkId}/upload-signed`, {
            onSuccess: () => {
                setShowUploadModal(false);
                setUploadingSpkId(null);
                reset();
            },
        });
    };

    if (!selectedSpk && spk_list.length > 0) {
        return null;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Daftar SPK ${bulan_label} ${tahun}`} />

            <div className="space-y-6">
                <PageHeader
                    title={`Daftar SPK ${bulan_label} ${tahun}`}
                    description={`Menampilkan ${spk_list.length} SPK yang telah dibuat untuk periode ini`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Nomor SPK
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Petugas
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Kegiatan & Peran
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Tanggal SPK
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Nilai Kontrak
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {spk_list.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                            Tidak ada SPK untuk periode ini
                                        </td>
                                    </tr>
                                ) : (
                                    spk_list.map((spk) => (
                                        <tr key={spk.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                            <td className="px-6 py-4 text-sm font-medium text-neutral-900 dark:text-white">
                                                {spk.nomor_spk}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {spk.petugas.nama}
                                                    </div>
                                                    <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                        {spk.petugas.nik}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="space-y-1">
                                                    {spk.kegiatan_list.map((kegiatan, idx) => (
                                                        <div key={idx} className="text-sm">
                                                            <div className="font-medium text-neutral-900 dark:text-white">
                                                                {kegiatan.kode_kegiatan}
                                                            </div>
                                                            <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                {getPeranLabel(kegiatan.peran)}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                {spk.tanggal_spk}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                Rp {parseFloat(spk.nilai_kontrak.toString()).toLocaleString('id-ID')}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getStatusBadge(spk.status)}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                        className="gap-1"
                                                    >
                                                        <Link href={`/spk/${spk.hashed_id}`}>
                                                            <Eye className="h-3.5 w-3.5" />
                                                            Detail
                                                        </Link>
                                                    </Button>
                                                    {spk.file_path && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handleDownload(spk.file_path!)}
                                                            className="gap-1"
                                                        >
                                                            <Download className="h-3.5 w-3.5" />
                                                            Download
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
