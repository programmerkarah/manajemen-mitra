import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
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
    spk_list: SpkItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Generate BAST', href: '#' },
];

export default function CreateForMonth({
    bulan,
    tahun,
    bulan_label,
    spk_list,
}: CreateForMonthProps) {
    const [selectedSpks, setSelectedSpks] = useState<number[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);

    const handleSelectAll = () => {
        if (selectedSpks.length === spk_list.length) {
            setSelectedSpks([]);
        } else {
            setSelectedSpks(spk_list.map((spk) => spk.spk_id));
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
            alert('Pilih minimal 1 SPK untuk generate BAST');
            return;
        }

        setIsGenerating(true);
        router.post(
            '/bast/generate-batch',
            {
                spk_ids: selectedSpks,
                bulan,
                tahun,
            },
            {
                onFinish: () => setIsGenerating(false),
            },
        );
    };

    const handlePreviewSpk = (spkId: number) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/bast/preview-spk';
        form.target = '_blank';

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        const spkInput = document.createElement('input');
        spkInput.type = 'hidden';
        spkInput.name = 'spk_id';
        spkInput.value = spkId.toString();
        form.appendChild(spkInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Generate BAST - ${bulan_label} ${tahun}`} />

            <PageHeader
                title={`Generate BAST - ${bulan_label} ${tahun}`}
                description="Pilih SPK yang akan dibuatkan BAST"
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
                                {spk_list.length} Perjanjian Kerja belum memiliki BAST di
                                periode ini
                            </p>
                        </div>
                        <Button variant="outline" onClick={handleSelectAll}>
                            {selectedSpks.length === spk_list.length
                                ? 'Batal Pilih Semua'
                                : 'Pilih Semua'}
                        </Button>
                    </div>

                    <div className="space-y-4">
                        {spk_list.map((spk) => (
                            <div
                                key={spk.spk_id}
                                className={`rounded-lg border p-4 transition-colors ${
                                    selectedSpks.includes(spk.spk_id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-neutral-200 dark:border-neutral-800'
                                }`}
                            >
                                <div className="flex items-start gap-4">
                                    <input
                                        type="checkbox"
                                        checked={selectedSpks.includes(
                                            spk.spk_id,
                                        )}
                                        onChange={() =>
                                            handleSelectSpk(spk.spk_id)
                                        }
                                        className="mt-1 h-4 w-4 rounded border-neutral-300"
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
                                                    SPK: {spk.nomor_spk}
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
                                                                        keg.kode_kegiatan
                                                                    }
                                                                </span>
                                                                <span className="text-neutral-600 dark:text-neutral-400">
                                                                    {' '}
                                                                    •{' '}
                                                                    {
                                                                        keg.nama_kegiatan
                                                                    }
                                                                </span>
                                                                <span className="ml-2 text-xs text-neutral-500">
                                                                    (
                                                                    {
                                                                        keg.peran
                                                                    }
                                                                    )
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
                                            <span>
                                                PPK: {spk.nama_ppk}
                                                {spk.nip_ppk &&
                                                    ` (${spk.nip_ppk})`}
                                            </span>
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
                                                onClick={() =>
                                                    handlePreviewSpk(
                                                        spk.spk_id,
                                                    )
                                                }
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

                    {spk_list.length === 0 && (
                        <div className="py-12 text-center text-neutral-500">
                            Tidak ada Perjanjian Kerja yang perlu dibuatkan BAST
                        </div>
                    )}
                </div>
            </ContentCard>
        </AppLayout>
    );
}
