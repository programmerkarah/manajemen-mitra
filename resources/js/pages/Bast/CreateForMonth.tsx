import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { previewFileFromPost } from '@/utils/downloadUtils';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Eye, FileText, User } from 'lucide-react';
import { useState } from 'react';

interface Petugas {
    id: number;
    nama: string;
    nik: string;
    tempat_lahir: string | null;
    tanggal_lahir: string | null;
    alamat: string | null;
    no_rekening: string | null;
    nama_bank: string | null;
    npwp: string | null;
}

interface KetuaTim {
    nama: string | null;
    nip: string | null;
}

interface KegiatanDetail {
    kegiatan_id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tanggal_selesai: string;
    tanggal_selesai_label: string;
    peran: string;
    hasil_listing: number | null;
    hasil_pendataan_lapangan: number | null;
    hasil_pengolahan: number | null;
    spk_id: number;
    nomor_spk: string;
}

interface SpkItem {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    nomor_bast_preview: string | null;
    tanggal_spk: string;
    tanggal_mulai_kerja: string;
    tanggal_selesai_kerja_asli: string;
    tanggal_berakhir_paling_akhir: string;
    nama_ppk: string;
    nip_ppk: string | null;
    petugas: Petugas;
    ketua_tim: KetuaTim;
    kegiatan_list: KegiatanDetail[];
    jumlah_kegiatan: number;
}

interface CreateForMonthProps {
    bulan: number;
    tahun: number;
    bulan_label: string;
    spk_list: {
        encrypted: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Generate BAST', href: '#' },
];

const getPeranLabel = (peran: string): string => {
    const labels: Record<string, string> = {
        pcl_ppl: 'Petugas Lapangan',
        pml: 'Petugas Pemeriksa Lapangan',
        pengolahan: 'Petugas Pengolahan',
        pengawas_pengolahan: 'Pemeriksa Pengolahan',
    };
    return labels[peran] || peran;
};

export default function CreateForMonth({
    bulan,
    tahun,
    bulan_label,
    spk_list,
}: CreateForMonthProps) {
    const decryptedSpkList = useDecryptedData<SpkItem>(spk_list.encrypted);

    // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir (ASC) kemudian nama petugas (A-Z)
    const sortedSpkList = [...decryptedSpkList].sort((a, b) => {
        // Compare by tanggal_berakhir_paling_akhir first
        const dateCompare = a.tanggal_berakhir_paling_akhir.localeCompare(
            b.tanggal_berakhir_paling_akhir,
        );
        if (dateCompare !== 0) return dateCompare;

        // If dates are equal, compare by nama petugas (A-Z)
        return a.petugas.nama.localeCompare(b.petugas.nama);
    });

    const [selectedSpks, setSelectedSpks] = useState<number[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);

    const handleSelectAll = () => {
        if (selectedSpks.length === sortedSpkList.length) {
            setSelectedSpks([]);
        } else {
            setSelectedSpks(sortedSpkList.map((spk) => spk.spk_id));
        }
    };

    const handleSelectSpk = (spkId: number) => {
        if (selectedSpks.includes(spkId)) {
            setSelectedSpks(selectedSpks.filter((id) => id !== spkId));
        } else {
            setSelectedSpks([...selectedSpks, spkId]);
        }
    };

    const handleGenerateBast = () => {
        if (selectedSpks.length === 0) {
            alert('Pilih minimal 1 Perjanjian Kerja untuk generate BAST');
            return;
        }

        const payload = {
            spk_ids: selectedSpks,
            bulan,
            tahun,
        };

        const encryptedPayload = encryptFilters(payload);

        setIsGenerating(true);
        router.post(
            '/bast/generate-batch',
            {
                encrypted_filters: encryptedPayload,
            },
            {
                onFinish: () => setIsGenerating(false),
            },
        );
    };

    const handlePreviewSpk = async (spkId: number) => {
        const payload: Record<string, number> = {
            spk_id: spkId,
        };

        // Don't send nomor_bast - let backend generate the latest number
        // if (nomorBast) {
        //     payload.nomor_bast = nomorBast;
        // }

        const encryptedPayload = encryptFilters(payload);

        try {
            await previewFileFromPost(
                '/bast/preview-bast',
                {
                    encrypted_filters: encryptedPayload,
                },
                'Preview_BAST.pdf',
            );
        } catch {
            alert('Gagal membuka preview BAST. Silakan coba lagi.');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Generate BAST - ${bulan_label} ${tahun}`} />

            <PageHeader
                title={`Generate BAST - ${bulan_label} ${tahun}`}
                description="Pilih Perjanjian Kerja yang akan dibuatkan BAST"
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/bast">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    {selectedSpks.length > 0 && (
                        <Button
                            onClick={handleGenerateBast}
                            disabled={isGenerating}
                        >
                            <FileText className="mr-2 h-4 w-4" />
                            Generate {selectedSpks.length} BAST
                        </Button>
                    )}
                </div>
            </PageHeader>

            <ContentCard>
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Perjanjian Kerja
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                {sortedSpkList.length} Perjanjian Kerja belum
                                memiliki BAST di periode ini
                            </p>
                        </div>
                        <Button variant="outline" onClick={handleSelectAll}>
                            {selectedSpks.length === sortedSpkList.length
                                ? 'Batal Pilih Semua'
                                : 'Pilih Semua'}
                        </Button>
                    </div>

                    <div className="space-y-4">
                        {sortedSpkList.map((spk) => (
                            <div
                                key={spk.spk_id}
                                onClick={() => handleSelectSpk(spk.spk_id)}
                                className={`cursor-pointer rounded-lg border p-4 transition-colors ${
                                    selectedSpks.includes(spk.spk_id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700'
                                }`}
                            >
                                <div className="flex items-start gap-4">
                                    <input
                                        type="checkbox"
                                        checked={selectedSpks.includes(
                                            spk.spk_id,
                                        )}
                                        onChange={() => {}}
                                        onClick={(e) => e.stopPropagation()}
                                        className="pointer-events-none mt-1 h-4 w-4 rounded border-neutral-300"
                                    />
                                    <div className="flex-1 space-y-3">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <User className="h-4 w-4 text-neutral-500" />
                                                    <span className="font-semibold text-neutral-900 dark:text-white">
                                                        {spk.petugas.nama}
                                                    </span>
                                                    <Badge variant="secondary">
                                                        {spk.petugas.nik}
                                                    </Badge>
                                                </div>
                                                <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                                    Perjanjian Kerja:{' '}
                                                    {spk.nomor_spk}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <Badge variant="outline">
                                                    <Calendar className="mr-1 h-3 w-3" />
                                                    BAST:{' '}
                                                    {new Date(
                                                        spk.tanggal_berakhir_paling_akhir,
                                                    ).toLocaleDateString(
                                                        'id-ID',
                                                    )}
                                                </Badge>
                                            </div>
                                        </div>

                                        <div className="rounded-md bg-neutral-50 p-3 dark:bg-neutral-800">
                                            <p className="mb-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                                Kegiatan yang Diikuti (
                                                {spk.jumlah_kegiatan}):
                                            </p>
                                            <div className="space-y-2">
                                                {spk.kegiatan_list.map(
                                                    (keg, idx) => (
                                                        <div
                                                            key={idx}
                                                            className="flex items-center justify-between text-sm"
                                                        >
                                                            <div className="flex-1">
                                                                <span className="font-medium text-neutral-900 dark:text-white">
                                                                    {
                                                                        keg.nama_kegiatan
                                                                    }
                                                                </span>
                                                                <span className="text-neutral-600 dark:text-neutral-400">
                                                                    {' '}
                                                                    •{' '}
                                                                    {getPeranLabel(
                                                                        keg.peran,
                                                                    )}
                                                                </span>
                                                            </div>
                                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                Berakhir:{' '}
                                                                {
                                                                    keg.tanggal_selesai_label
                                                                }
                                                            </span>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-4 text-xs text-neutral-600 dark:text-neutral-400">
                                            {spk.ketua_tim.nama && (
                                                <span>
                                                    Ketua Tim:{' '}
                                                    {spk.ketua_tim.nama}
                                                    {spk.ketua_tim.nip &&
                                                        ` (${spk.ketua_tim.nip})`}
                                                </span>
                                            )}
                                        </div>

                                        <div className="mt-3 flex justify-end">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handlePreviewSpk(
                                                        spk.spk_id,
                                                    );
                                                }}
                                            >
                                                <Eye className="mr-1 h-3 w-3" />
                                                Preview BAST
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {decryptedSpkList.length === 0 && (
                        <div className="py-12 text-center text-neutral-500">
                            Tidak ada Perjanjian Kerja yang perlu dibuatkan BAST
                        </div>
                    )}
                </div>
            </ContentCard>
        </AppLayout>
    );
}
