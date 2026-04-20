import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
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
import { KECAMATAN_LIST, getDesaByKecamatan } from '@/lib/wilayah-data';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';
import { useState } from 'react';

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    email: string;
    telepon: string;
    alamat: string;
    pendidikan: string;
    tahun_bergabung: number;
    jenis_petugas: 'organik' | 'non-organik';
    jabatan: string | null;
    golongan: string | null;
    npwp: string | null;
    bank: string | null;
    no_rekening: string | null;
    nama_rekening: string | null;
    status: string;
    jenis_kelamin: string | null;
    kecamatan: string | null;
    desa_kelurahan: string | null;
    tanggal_lahir: string | null;
    catatan: string | null;
}

interface EditProps {
    petugas: Petugas;
}

const pendidikanOptions = [
    'SD',
    'SMP',
    'SMA',
    'D1',
    'D2',
    'D3',
    'D4',
    'S1',
    'S2',
    'S3',
];

export default function Edit({ petugas }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Petugas', href: '/petugas' },
        { title: 'Edit', href: `/petugas/${petugas.hashed_id}/edit` },
    ];

    const [jenisPetugas, setJenisPetugas] = useState<'organik' | 'non-organik'>(
        petugas.jenis_petugas,
    );

    const { data, setData, put, processing, errors } = useForm({
        nama: petugas.nama,
        nik: petugas.nik,
        email: petugas.email,
        telepon: petugas.telepon,
        alamat: petugas.alamat,
        pendidikan: petugas.pendidikan,
        tahun_bergabung: petugas.tahun_bergabung,
        jenis_petugas: petugas.jenis_petugas,
        jabatan:
            petugas.jabatan ||
            (petugas.jenis_petugas === 'non-organik' ? 'Mitra Statistik' : ''),
        golongan:
            petugas.golongan ||
            (petugas.jenis_petugas === 'non-organik' ? 'Non PNS' : ''),
        npwp: petugas.npwp || '',
        bank: petugas.bank || '',
        no_rekening: petugas.no_rekening || '',
        nama_rekening: petugas.nama_rekening || '',
        status: petugas.status,
        jenis_kelamin: petugas.jenis_kelamin || '',
        kecamatan: petugas.kecamatan || '',
        desa_kelurahan: petugas.desa_kelurahan || '',
        tanggal_lahir: petugas.tanggal_lahir || '',
        catatan: petugas.catatan || '',
    });

    const handleJenisPetugasChange = (value: 'organik' | 'non-organik') => {
        setJenisPetugas(value);
        setData('jenis_petugas', value);

        // Auto-set jabatan and golongan for non-organik
        if (value === 'non-organik') {
            setData((prevData) => ({
                ...prevData,
                jenis_petugas: value,
                jabatan: 'Mitra Statistik',
                golongan: 'Non PNS',
            }));
        } else {
            // Clear for organik to allow user input
            setData((prevData) => ({
                ...prevData,
                jenis_petugas: value,
                jabatan: petugas.jabatan || '',
                golongan: petugas.golongan || '',
            }));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/petugas/${petugas.hashed_id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Petugas - ${petugas.nama}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Petugas"
                    description={`Perbarui informasi untuk ${petugas.nama}`}
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href="/petugas">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Nama */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Nama Lengkap{' '}
                                    <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.nama}
                                    onChange={(e) =>
                                        setData('nama', e.target.value)
                                    }
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.nama && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.nama}
                                    </p>
                                )}
                            </div>
                            {/* Jenis Kelamin */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Jenis Kelamin
                                </Label>
                                <Select
                                    value={data.jenis_kelamin}
                                    onValueChange={(value) =>
                                        setData('jenis_kelamin', value)
                                    }
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue placeholder="Pilih Jenis Kelamin" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="laki-laki">
                                            Laki-laki
                                        </SelectItem>
                                        <SelectItem value="perempuan">
                                            Perempuan
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.jenis_kelamin && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.jenis_kelamin}
                                    </p>
                                )}
                            </div>

                            {/* NIK */}
                            <div>
                                <label className="block text-sm font-medium">
                                    NIK/NIP{' '}
                                    <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.nik}
                                    onChange={(e) =>
                                        setData('nik', e.target.value)
                                    }
                                    maxLength={18}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.nik && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.nik}
                                    </p>
                                )}
                            </div>

                            {/* Tanggal Lahir */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Tanggal Lahir
                                </Label>
                                <DatePicker
                                    value={data.tanggal_lahir}
                                    onChange={(value) =>
                                        setData('tanggal_lahir', value)
                                    }
                                    max={new Date().toISOString().split('T')[0]}
                                    className="mt-1 h-10"
                                />
                                {errors.tanggal_lahir && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.tanggal_lahir}
                                    </p>
                                )}
                            </div>

                            {/* Email */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Email{' '}
                                    <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {/* Telepon */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Telepon{' '}
                                    <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.telepon}
                                    onChange={(e) =>
                                        setData('telepon', e.target.value)
                                    }
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.telepon && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.telepon}
                                    </p>
                                )}
                            </div>

                            {/* Pendidikan */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Pendidikan{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.pendidikan}
                                    onValueChange={(value) =>
                                        setData('pendidikan', value)
                                    }
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue placeholder="Pilih Pendidikan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {pendidikanOptions.map((option) => (
                                            <SelectItem
                                                key={option}
                                                value={option}
                                            >
                                                {option}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.pendidikan && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.pendidikan}
                                    </p>
                                )}
                            </div>

                            {/* Tahun Bergabung */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Tahun Bergabung{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.tahun_bergabung.toString()}
                                    onValueChange={(value) =>
                                        setData(
                                            'tahun_bergabung',
                                            parseInt(value),
                                        )
                                    }
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue placeholder="Pilih Tahun" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Array.from(
                                            {
                                                length:
                                                    new Date().getFullYear() -
                                                    1980 +
                                                    2,
                                            },
                                            (_, i) =>
                                                new Date().getFullYear() +
                                                1 -
                                                i,
                                        ).map((year) => (
                                            <SelectItem
                                                key={year}
                                                value={year.toString()}
                                            >
                                                {year}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.tahun_bergabung && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.tahun_bergabung}
                                    </p>
                                )}
                            </div>

                            {/* Status */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Status{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData('status', value)
                                    }
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue placeholder="Pilih Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="aktif">
                                            Aktif
                                        </SelectItem>
                                        <SelectItem value="nonaktif">
                                            Nonaktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.status && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.status}
                                    </p>
                                )}
                            </div>

                            {/* Jenis Petugas */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Jenis Petugas{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.jenis_petugas}
                                    onValueChange={(value) =>
                                        handleJenisPetugasChange(
                                            value as 'organik' | 'non-organik',
                                        )
                                    }
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue placeholder="Pilih Jenis Petugas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="organik">
                                            Organik
                                        </SelectItem>
                                        <SelectItem value="non-organik">
                                            Non-Organik
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.jenis_petugas && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.jenis_petugas}
                                    </p>
                                )}
                            </div>

                            {/* Jabatan */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Jabatan
                                </label>
                                <input
                                    type="text"
                                    value={data.jabatan}
                                    onChange={(e) =>
                                        setData('jabatan', e.target.value)
                                    }
                                    disabled={jenisPetugas === 'non-organik'}
                                    placeholder={
                                        jenisPetugas === 'non-organik'
                                            ? 'Mitra Statistik'
                                            : 'Contoh: Statistisi Ahli Pertama'
                                    }
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:disabled:bg-neutral-900"
                                />
                                {jenisPetugas === 'non-organik' && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Jabatan untuk non-organik otomatis:
                                        Mitra Statistik
                                    </p>
                                )}
                                {errors.jabatan && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.jabatan}
                                    </p>
                                )}
                            </div>

                            {/* Golongan */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Golongan
                                </label>
                                <input
                                    type="text"
                                    value={data.golongan}
                                    onChange={(e) =>
                                        setData('golongan', e.target.value)
                                    }
                                    disabled={jenisPetugas === 'non-organik'}
                                    placeholder={
                                        jenisPetugas === 'non-organik'
                                            ? 'Non PNS'
                                            : 'Contoh: III/b'
                                    }
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:disabled:bg-neutral-900"
                                />
                                {jenisPetugas === 'non-organik' && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Golongan untuk non-organik otomatis: Non
                                        PNS
                                    </p>
                                )}
                                {errors.golongan && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.golongan}
                                    </p>
                                )}
                            </div>

                            {/* Alamat - Full Width */}
                            <div className="md:col-span-2">
                                <Label className="block text-sm font-medium">
                                    Alamat{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Textarea
                                    value={data.alamat}
                                    onChange={(e) =>
                                        setData('alamat', e.target.value)
                                    }
                                    rows={3}
                                    className="mt-1"
                                />
                                {errors.alamat && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.alamat}
                                    </p>
                                )}
                            </div>

                            {/* Kecamatan */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Kecamatan
                                </Label>
                                <Select
                                    value={data.kecamatan}
                                    onValueChange={(value) => {
                                        setData((prev) => ({
                                            ...prev,
                                            kecamatan: value,
                                            desa_kelurahan: '',
                                        }));
                                    }}
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue placeholder="Pilih Kecamatan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {KECAMATAN_LIST.map((kec) => (
                                            <SelectItem
                                                key={kec.kode}
                                                value={kec.nama}
                                            >
                                                {kec.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.kecamatan && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.kecamatan}
                                    </p>
                                )}
                            </div>

                            {/* Desa / Kelurahan */}
                            <div>
                                <Label className="block text-sm font-medium">
                                    Desa / Kelurahan
                                </Label>
                                <Select
                                    value={data.desa_kelurahan}
                                    onValueChange={(value) =>
                                        setData('desa_kelurahan', value)
                                    }
                                    disabled={!data.kecamatan}
                                >
                                    <SelectTrigger className="mt-1 h-10">
                                        <SelectValue
                                            placeholder={
                                                data.kecamatan
                                                    ? 'Pilih Desa/Kelurahan'
                                                    : 'Pilih kecamatan dulu'
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {getDesaByKecamatan(data.kecamatan).map(
                                            (desa) => (
                                                <SelectItem
                                                    key={desa.kode}
                                                    value={desa.nama}
                                                >
                                                    {desa.nama}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.desa_kelurahan && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.desa_kelurahan}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Data Bank */}
                        <div className="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                            <h3 className="font-semibold">
                                Data Bank (Opsional)
                            </h3>

                            <div className="grid gap-6 md:grid-cols-2">
                                {/* NPWP */}
                                <div>
                                    <label className="block text-sm font-medium">
                                        NPWP
                                    </label>
                                    <input
                                        type="text"
                                        value={data.npwp}
                                        onChange={(e) =>
                                            setData('npwp', e.target.value)
                                        }
                                        maxLength={24}
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.npwp && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.npwp}
                                        </p>
                                    )}
                                </div>

                                {/* Bank */}
                                <div>
                                    <label className="block text-sm font-medium">
                                        Bank
                                    </label>
                                    <input
                                        type="text"
                                        value={data.bank}
                                        onChange={(e) =>
                                            setData('bank', e.target.value)
                                        }
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.bank && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.bank}
                                        </p>
                                    )}
                                </div>

                                {/* Nomor Rekening */}
                                <div>
                                    <label className="block text-sm font-medium">
                                        Nomor Rekening
                                    </label>
                                    <input
                                        type="text"
                                        value={data.no_rekening}
                                        onChange={(e) =>
                                            setData(
                                                'no_rekening',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.no_rekening && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.no_rekening}
                                        </p>
                                    )}
                                </div>

                                {/* Nama Rekening */}
                                <div>
                                    <label className="block text-sm font-medium">
                                        Nama Rekening
                                    </label>
                                    <input
                                        type="text"
                                        value={data.nama_rekening}
                                        onChange={(e) =>
                                            setData(
                                                'nama_rekening',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.nama_rekening && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.nama_rekening}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Catatan */}
                        <div className="rounded-lg border border-neutral-200 bg-neutral-50/50 p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
                            <h3 className="mb-3 text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                Catatan
                            </h3>
                            <div>
                                <Label className="block text-sm font-medium">
                                    Catatan
                                </Label>
                                <Textarea
                                    value={data.catatan}
                                    onChange={(e) =>
                                        setData('catatan', e.target.value)
                                    }
                                    rows={3}
                                    className="mt-1"
                                    placeholder="Catatan tambahan tentang petugas..."
                                />
                                {errors.catatan && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.catatan}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="mt-6 flex justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                            <Button
                                type="button"
                                variant="outline"
                                asChild
                                className="min-w-[180px] gap-2"
                            >
                                <Link href="/petugas">
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
                </ContentCard>
            </div>
        </AppLayout>
    );
}
