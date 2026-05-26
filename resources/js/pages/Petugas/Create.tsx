import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
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
import { KECAMATAN_LIST, getDesaByKecamatan } from '@/lib/wilayah-data';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, Loader2, Save, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Petugas', href: '/petugas' },
    { title: 'Tambah Petugas', href: '/petugas/create' },
];

export default function Create() {
    const [jenisPetugas, setJenisPetugas] = useState('non-organik');
    const [showError, setShowError] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        nama: '',
        nik: '',
        email: '',
        telepon: '',
        alamat: '',
        pendidikan: '',
        tahun_bergabung: new Date().getFullYear().toString(),
        status: 'aktif',
        jenis_petugas: 'non-organik',
        jabatan: 'Mitra Statistik',
        golongan: 'Non PNS',
        npwp: '',
        bank: '',
        no_rekening: '',
        nama_rekening: '',
        catatan: '',
        jenis_kelamin: '',
        kecamatan: '',
        desa_kelurahan: '',
        tanggal_lahir: '',
    });

    const handleJenisPetugasChange = (value: string) => {
        setJenisPetugas(value);
        setData({
            ...data,
            jenis_petugas: value,
            jabatan: value === 'non-organik' ? 'Mitra Statistik' : '',
            golongan: value === 'non-organik' ? 'Non PNS' : '',
        });
    };
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        e.stopPropagation();

        console.log('🚀 handleSubmit called');
        console.log('📦 Form data:', data);
        console.log('⚙️ Processing:', processing);

        setShowError(false);

        post('/petugas', {
            preserveScroll: true,
            onStart: () => {
                console.log('⏳ Request starting...');
            },
            onSuccess: (page) => {
                console.log('✅ Form submitted successfully', page);
            },
            onError: (errors) => {
                console.log('❌ Form submission error:', errors);
                setShowError(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            onFinish: () => {
                console.log('🏁 Request finished');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Petugas" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah Petugas Baru"
                    description="Masukkan informasi lengkap petugas mitra"
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

                {/* Success Alert */}
                {showError && Object.keys(errors).length > 0 && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-5 w-5" />
                        <AlertTitle>Terjadi Kesalahan</AlertTitle>
                        <AlertDescription>
                            Mohon periksa kembali form Anda. Ada beberapa field
                            yang perlu diperbaiki:
                            <ul className="mt-2 list-inside list-disc space-y-1">
                                {Object.entries(errors).map(
                                    ([field, message]) => (
                                        <li key={field} className="text-sm">
                                            <span className="font-medium capitalize">
                                                {field.replace(/_/g, ' ')}:
                                            </span>{' '}
                                            {message}
                                        </li>
                                    ),
                                )}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <ContentCard>
                    <form onSubmit={handleSubmit}>
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Nama */}
                            <div className="space-y-2">
                                <Label htmlFor="nama">
                                    Nama Lengkap{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="nama"
                                    type="text"
                                    value={data.nama}
                                    onChange={(e) =>
                                        setData('nama', e.target.value)
                                    }
                                    required
                                    className="h-10"
                                />
                                <InputError message={errors.nama} />
                            </div>

                            {/* Jenis Kelamin */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_kelamin">
                                    Jenis Kelamin
                                </Label>
                                <Select
                                    value={data.jenis_kelamin}
                                    onValueChange={(value) =>
                                        setData('jenis_kelamin', value)
                                    }
                                >
                                    <SelectTrigger className="h-10">
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
                                <InputError message={errors.jenis_kelamin} />
                            </div>

                            {/* NIK */}
                            <div className="space-y-2">
                                <Label htmlFor="nik">
                                    NIK/NIP{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="nik"
                                    type="text"
                                    value={data.nik}
                                    onChange={(e) =>
                                        setData('nik', e.target.value)
                                    }
                                    maxLength={18}
                                    required
                                    className="h-10"
                                />
                                <InputError message={errors.nik} />
                            </div>

                            {/* Tanggal Lahir */}
                            <div className="space-y-2">
                                <Label htmlFor="tanggal_lahir">
                                    Tanggal Lahir
                                </Label>
                                <DatePicker
                                    id="tanggal_lahir"
                                    value={data.tanggal_lahir}
                                    onChange={(value) =>
                                        setData('tanggal_lahir', value)
                                    }
                                    max={new Date().toISOString().split('T')[0]}
                                    className="h-10"
                                />
                                <InputError message={errors.tanggal_lahir} />
                            </div>

                            {/* Email */}
                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    Email{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    required
                                    className="h-10"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {/* Telepon */}
                            <div className="space-y-2">
                                <Label htmlFor="telepon">
                                    Telepon{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="telepon"
                                    type="text"
                                    value={data.telepon}
                                    onChange={(e) =>
                                        setData('telepon', e.target.value)
                                    }
                                    required
                                    className="h-10"
                                />
                                <InputError message={errors.telepon} />
                            </div>

                            {/* Pendidikan */}
                            <div className="space-y-2">
                                <Label htmlFor="pendidikan">
                                    Pendidikan{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.pendidikan}
                                    onValueChange={(value) =>
                                        setData('pendidikan', value)
                                    }
                                    required
                                >
                                    <SelectTrigger className="h-10">
                                        <SelectValue placeholder="Pilih Pendidikan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="SD">SD</SelectItem>
                                        <SelectItem value="SMP">SMP</SelectItem>
                                        <SelectItem value="SMA">SMA</SelectItem>
                                        <SelectItem value="D1">D1</SelectItem>
                                        <SelectItem value="D2">D2</SelectItem>
                                        <SelectItem value="D3">D3</SelectItem>
                                        <SelectItem value="D4">D4</SelectItem>
                                        <SelectItem value="S1">S1</SelectItem>
                                        <SelectItem value="S2">S2</SelectItem>
                                        <SelectItem value="S3">S3</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.pendidikan} />
                            </div>

                            {/* Tanggal Lahir */}
                            <div className="space-y-2">
                                <Label htmlFor="tanggal_lahir">
                                    Tanggal Lahir
                                </Label>
                                <DatePicker
                                    id="tanggal_lahir"
                                    value={data.tanggal_lahir}
                                    onChange={(value) =>
                                        setData('tanggal_lahir', value)
                                    }
                                    max={new Date().toISOString().split('T')[0]}
                                    className="h-10"
                                />
                                <InputError message={errors.tanggal_lahir} />
                            </div>

                            {/* Tahun Bergabung */}
                            <div className="space-y-2">
                                <Label htmlFor="tahun_bergabung">
                                    Tahun Bergabung{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.tahun_bergabung}
                                    onValueChange={(value) =>
                                        setData('tahun_bergabung', value)
                                    }
                                    required
                                >
                                    <SelectTrigger className="h-10">
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
                                <InputError message={errors.tahun_bergabung} />
                            </div>

                            {/* Status */}
                            <div className="space-y-2">
                                <Label htmlFor="status">
                                    Status{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData('status', value)
                                    }
                                    required
                                >
                                    <SelectTrigger className="h-10">
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
                                <InputError message={errors.status} />
                            </div>

                            {/* Jenis Petugas */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_petugas">
                                    Jenis Petugas{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.jenis_petugas}
                                    onValueChange={(value) =>
                                        handleJenisPetugasChange(value)
                                    }
                                    required
                                >
                                    <SelectTrigger className="h-10">
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
                                <InputError message={errors.jenis_petugas} />
                            </div>

                            {/* Jabatan */}
                            <div className="space-y-2">
                                <Label htmlFor="jabatan">Jabatan</Label>
                                <Input
                                    id="jabatan"
                                    type="text"
                                    value={data.jabatan}
                                    onChange={(e) =>
                                        setData('jabatan', e.target.value)
                                    }
                                    disabled={jenisPetugas === 'non-organik'}
                                    className="h-10"
                                    placeholder={
                                        jenisPetugas === 'non-organik'
                                            ? 'Mitra Statistik'
                                            : 'Contoh: Statistisi Ahli Pertama'
                                    }
                                />
                                {jenisPetugas === 'non-organik' && (
                                    <p className="text-xs text-muted-foreground">
                                        Jabatan untuk non-organik otomatis:
                                        Mitra Statistik
                                    </p>
                                )}
                                <InputError message={errors.jabatan} />
                            </div>

                            {/* Golongan */}
                            <div className="space-y-2">
                                <Label htmlFor="golongan">Golongan</Label>
                                <Input
                                    id="golongan"
                                    type="text"
                                    value={data.golongan}
                                    onChange={(e) =>
                                        setData('golongan', e.target.value)
                                    }
                                    disabled={jenisPetugas === 'non-organik'}
                                    className="h-10"
                                    placeholder={
                                        jenisPetugas === 'non-organik'
                                            ? 'Non PNS'
                                            : 'Contoh: III/b'
                                    }
                                />
                                {jenisPetugas === 'non-organik' && (
                                    <p className="text-xs text-muted-foreground">
                                        Golongan untuk non-organik otomatis: Non
                                        PNS
                                    </p>
                                )}
                                <InputError message={errors.golongan} />
                            </div>

                            {/* NPWP */}
                            <div className="space-y-2">
                                <Label htmlFor="npwp">NPWP</Label>
                                <Input
                                    id="npwp"
                                    type="text"
                                    value={data.npwp}
                                    onChange={(e) =>
                                        setData('npwp', e.target.value)
                                    }
                                    maxLength={24}
                                    className="h-10"
                                />
                                <InputError message={errors.npwp} />
                            </div>

                            {/* Bank */}
                            <div className="space-y-2">
                                <Label htmlFor="bank">Bank</Label>
                                <Input
                                    id="bank"
                                    type="text"
                                    value={data.bank}
                                    onChange={(e) =>
                                        setData('bank', e.target.value)
                                    }
                                    className="h-10"
                                />
                                <InputError message={errors.bank} />
                            </div>

                            {/* No Rekening */}
                            <div className="space-y-2">
                                <Label htmlFor="no_rekening">
                                    No. Rekening
                                </Label>
                                <Input
                                    id="no_rekening"
                                    type="text"
                                    value={data.no_rekening}
                                    onChange={(e) =>
                                        setData('no_rekening', e.target.value)
                                    }
                                    className="h-10"
                                />
                                <InputError message={errors.no_rekening} />
                            </div>

                            {/* Nama Rekening */}
                            <div className="space-y-2">
                                <Label htmlFor="nama_rekening">
                                    Nama Rekening
                                </Label>
                                <Input
                                    id="nama_rekening"
                                    type="text"
                                    value={data.nama_rekening}
                                    onChange={(e) =>
                                        setData('nama_rekening', e.target.value)
                                    }
                                    className="h-10"
                                />
                                <InputError message={errors.nama_rekening} />
                            </div>

                            {/* Alamat */}
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="alamat">
                                    Alamat{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Textarea
                                    id="alamat"
                                    value={data.alamat}
                                    onChange={(e) =>
                                        setData('alamat', e.target.value)
                                    }
                                    rows={3}
                                    required
                                />
                                <InputError message={errors.alamat} />
                            </div>

                            {/* Kecamatan */}
                            <div className="space-y-2">
                                <Label htmlFor="kecamatan">Kecamatan</Label>
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
                                    <SelectTrigger className="h-10">
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
                                <InputError message={errors.kecamatan} />
                            </div>

                            {/* Desa / Kelurahan */}
                            <div className="space-y-2">
                                <Label htmlFor="desa_kelurahan">
                                    Desa / Kelurahan
                                </Label>
                                <Select
                                    value={data.desa_kelurahan}
                                    onValueChange={(value) =>
                                        setData('desa_kelurahan', value)
                                    }
                                    disabled={!data.kecamatan}
                                >
                                    <SelectTrigger className="h-10">
                                        <SelectValue
                                            placeholder={
                                                data.kecamatan
                                                    ? 'Pilih Desa/Kelurahan'
                                                    : 'Pilih kecamatan terlebih dahulu'
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
                                <InputError message={errors.desa_kelurahan} />
                            </div>

                            {/* Catatan */}
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="catatan">Catatan</Label>
                                <Textarea
                                    id="catatan"
                                    value={data.catatan}
                                    onChange={(e) =>
                                        setData('catatan', e.target.value)
                                    }
                                    rows={3}
                                />
                                <InputError message={errors.catatan} />
                            </div>
                        </div>

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
                                className="min-w-[180px] gap-2"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="h-5 w-5 animate-spin" />
                                        Menyimpan...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-5 w-5" />
                                        Simpan
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
