import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type AlokasiPetugas, type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alokasi petugas', href: '/alokasi' },
    { title: 'Edit Alokasi', href: '#' },
];

interface Kegiatan {
    id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    rate_honor?: {
        posisi: string;
        rate: number;
        satuan: {
            nama: string;
        };
    } | null;
}

interface petugas {
    id: string;
    nama: string;
    nik: string;
    email: string;
    jenis_petugas: 'organik' | 'non-organik';
    jabatan?: string | null;
}

interface RateHonor {
    id: string;
    nama: string;
    honor_satuan: number;
    satuan: {
        id: string;
        nama: string;
    };
}

interface AlokasiEditProps {
    alokasi: AlokasiPetugas;
    kegiatans: Kegiatan[];
    petugas: petugas[];
    rateHonors: RateHonor[];
}

export default function Edit({
    alokasi,
    kegiatans,
    petugas,
    rateHonors,
}: AlokasiEditProps) {
    // Debug: log petugas data
    const { data, setData, put, processing, errors } = useForm({
        kegiatan_id: alokasi.kegiatan_id || '',
        petugas_id: alokasi.petugas_id || '',
        bulan: alokasi.bulan || new Date().getMonth() + 1,
        tahun: alokasi.tahun || new Date().getFullYear(),
        jumlah_satuan: alokasi.jumlah_satuan.toString() || '',
        catatan: alokasi.catatan || '',
    });

    const [selectedRateHonor, setSelectedRateHonor] =
        useState<RateHonor | null>(null);
    const [estimatedTotal, setEstimatedTotal] = useState(0);

    // Calculate total based on kegiatan's rate honor
    useEffect(() => {
        const selectedKegiatan = kegiatans.find(
            (k) => k.id === data.kegiatan_id,
        );
        
        // Parse jumlah_satuan safely, defaulting to 0 if invalid
        const jumlahSatuan = parseFloat(data.jumlah_satuan) || 0;
        
        if (selectedKegiatan?.rate_honor && jumlahSatuan > 0) {
            setEstimatedTotal(
                selectedKegiatan.rate_honor.rate * jumlahSatuan,
            );
        } else {
            setEstimatedTotal(0);
        }
    }, [data.kegiatan_id, data.jumlah_satuan, kegiatans]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/alokasi/${alokasi.hashed_id}`);
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const months = [
        { value: 1, label: 'Januari' },
        { value: 2, label: 'Februari' },
        { value: 3, label: 'Maret' },
        { value: 4, label: 'April' },
        { value: 5, label: 'Mei' },
        { value: 6, label: 'Juni' },
        { value: 7, label: 'Juli' },
        { value: 8, label: 'Agustus' },
        { value: 9, label: 'September' },
        { value: 10, label: 'Oktober' },
        { value: 11, label: 'November' },
        { value: 12, label: 'Desember' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Alokasi Petugas" />

            <PageHeader
                title="Edit Alokasi Petugas"
                description="Ubah informasi alokasi petugas"
            >
                <Button variant="outline" asChild>
                    <Link href={`/alokasi/${alokasi.hashed_id}`}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            {/* Form */}
            <form onSubmit={handleSubmit}>
                <ContentCard>
                    <div className="space-y-6">
                        {/* Kegiatan */}
                        <div className="space-y-2">
                            <Label
                                htmlFor="kegiatan_id"
                                className="text-base font-semibold"
                            >
                                Kegiatan <span className="text-red-500">*</span>
                            </Label>
                            <Select
                                value={data.kegiatan_id || undefined}
                                onValueChange={(value) =>
                                    setData('kegiatan_id', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Kegiatan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {kegiatans.map((kegiatan) => (
                                        <SelectItem
                                            key={kegiatan.id}
                                            value={kegiatan.id}
                                        >
                                            {kegiatan.kode_kegiatan} -{' '}
                                            {kegiatan.nama_kegiatan}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.kegiatan_id}
                                className="mt-2"
                            />
                        </div>

                        {/* Petugas */}
                        <div className="space-y-2">
                            <Label
                                htmlFor="petugas_id"
                                className="text-base font-semibold"
                            >
                                Petugas <span className="text-red-500">*</span>
                            </Label>
                            <SearchableSelect
                                options={petugas.map((p) => {
                                    const jenisPetugasLabel =
                                        p.jenis_petugas === 'organik'
                                            ? 'Organik'
                                            : 'Non-Organik';
                                    const jabatanLabel = p.jabatan || '-';

                                    return {
                                        value: String(p.id),
                                        label: `${p.nama} - ${jenisPetugasLabel} - ${jabatanLabel}`,
                                        displayLabel: p.nama,
                                    };
                                })}
                                value={data.petugas_id}
                                onValueChange={(value) =>
                                    setData('petugas_id', value)
                                }
                                placeholder="Pilih Petugas"
                                searchPlaceholder="Cari petugas..."
                            />
                            <InputError
                                message={errors.petugas_id}
                                className="mt-2"
                            />
                        </div>

                        {/* Info Rate Honor dari Kegiatan */}
                        {kegiatans.find((k) => k.id === data.kegiatan_id)
                            ?.rate_honor && (
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                <p className="text-sm text-blue-700 dark:text-blue-400">
                                    <strong>Rate Honor:</strong>{' '}
                                    {
                                        kegiatans.find(
                                            (k) => k.id === data.kegiatan_id,
                                        )?.rate_honor?.posisi
                                    }{' '}
                                    -{' '}
                                    {formatCurrency(
                                        kegiatans.find(
                                            (k) => k.id === data.kegiatan_id,
                                        )?.rate_honor?.rate || 0,
                                    )}
                                    /
                                    {
                                        kegiatans.find(
                                            (k) => k.id === data.kegiatan_id,
                                        )?.rate_honor?.satuan.nama
                                    }
                                </p>
                                <p className="mt-1 text-xs text-blue-600 dark:text-blue-500">
                                    Rate honor ditentukan oleh kegiatan yang
                                    dipilih
                                </p>
                            </div>
                        )}

                        {/* Grid untuk periode */}
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            {/* Bulan */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="bulan"
                                    className="text-base font-semibold"
                                >
                                    Bulan{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.bulan.toString()}
                                    onValueChange={(value) =>
                                        setData('bulan', parseInt(value))
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {months.map((month) => (
                                            <SelectItem
                                                key={month.value}
                                                value={month.value.toString()}
                                            >
                                                {month.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.bulan}
                                    className="mt-2"
                                />
                            </div>

                            {/* Tahun */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="tahun"
                                    className="text-base font-semibold"
                                >
                                    Tahun{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    type="number"
                                    id="tahun"
                                    value={data.tahun}
                                    onChange={(e) =>
                                        setData(
                                            'tahun',
                                            parseInt(e.target.value),
                                        )
                                    }
                                    min="2020"
                                    max="2099"
                                    className="h-11 text-base"
                                />
                                <InputError
                                    message={errors.tahun}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        {/* Jumlah Satuan */}
                        <div className="space-y-2">
                            <Label
                                htmlFor="jumlah_satuan"
                                className="text-base font-semibold"
                            >
                                Jumlah{' '}
                                {selectedRateHonor?.satuan.nama || 'Satuan'}{' '}
                                <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                type="number"
                                id="jumlah_satuan"
                                value={data.jumlah_satuan}
                                onChange={(e) =>
                                    setData('jumlah_satuan', e.target.value)
                                }
                                placeholder="Masukkan jumlah..."
                                className="h-11 text-base"
                                min="1"
                            />
                            <InputError
                                message={errors.jumlah_satuan}
                                className="mt-2"
                            />
                        </div>

                        {/* Estimasi Total */}
                        {estimatedTotal > 0 && (
                            <div className="rounded-lg border border-blue-400/30 bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-blue-300/10 p-4 shadow-lg backdrop-blur-xl dark:border-blue-400/20 dark:from-blue-500/10 dark:via-neutral-800/20 dark:to-neutral-800/10">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium text-blue-900 dark:text-blue-300">
                                        Estimasi Honor:
                                    </span>
                                    <span className="text-lg font-bold text-blue-900 dark:text-blue-300">
                                        {formatCurrency(estimatedTotal)}
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* Catatan */}
                        <div className="space-y-2">
                            <Label
                                htmlFor="catatan"
                                className="text-base font-semibold"
                            >
                                Catatan
                            </Label>
                            <Textarea
                                id="catatan"
                                rows={3}
                                value={data.catatan}
                                onChange={(e) =>
                                    setData('catatan', e.target.value)
                                }
                                placeholder="Catatan tambahan (opsional)"
                                className="text-base"
                            />
                            <InputError
                                message={errors.catatan}
                                className="mt-2"
                            />
                        </div>
                    </div>
                </ContentCard>

                {/* Footer Buttons */}
                <div className="flex items-center justify-end gap-3">
                    <Button
                        variant="outline"
                        asChild
                        className="gap-2"
                        disabled={processing}
                    >
                        <Link href={`/alokasi/${alokasi.hashed_id}`}>
                            <X className="h-5 w-5" />
                            Batal
                        </Link>
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="min-w-[200px] gap-2"
                    >
                        {processing ? (
                            <>
                                <Loader2 className="h-5 w-5 animate-spin" />
                                Menyimpan...
                            </>
                        ) : (
                            <>
                                <Save className="h-5 w-5" />
                                Simpan Perubahan
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
