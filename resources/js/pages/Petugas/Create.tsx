import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Petugas', href: '/petugas' },
    { title: 'Tambah Petugas', href: '/petugas/create' },
];

export default function Create() {
    const [jenisPetugas, setJenisPetugas] = useState('non-organik');

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
        post('/petugas');
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
                                <select
                                    id="pendidikan"
                                    value={data.pendidikan}
                                    onChange={(e) =>
                                        setData('pendidikan', e.target.value)
                                    }
                                    required
                                    className="h-10 w-full rounded-lg border border-neutral-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                >
                                    <option value="">Pilih Pendidikan</option>
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                </select>
                                <InputError message={errors.pendidikan} />
                            </div>

                            {/* Tahun Bergabung */}
                            <div className="space-y-2">
                                <Label htmlFor="tahun_bergabung">
                                    Tahun Bergabung{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <select
                                    id="tahun_bergabung"
                                    value={data.tahun_bergabung}
                                    onChange={(e) =>
                                        setData(
                                            'tahun_bergabung',
                                            e.target.value,
                                        )
                                    }
                                    required
                                    className="h-10 w-full rounded-lg border border-neutral-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                >
                                    <option value="">Pilih Tahun</option>
                                    {Array.from(
                                        {
                                            length:
                                                new Date().getFullYear() -
                                                2000 +
                                                2,
                                        },
                                        (_, i) =>
                                            new Date().getFullYear() + 1 - i,
                                    ).map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.tahun_bergabung} />
                            </div>

                            {/* Status */}
                            <div className="space-y-2">
                                <Label htmlFor="status">
                                    Status{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) =>
                                        setData('status', e.target.value)
                                    }
                                    required
                                    className="h-10 w-full rounded-lg border border-neutral-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                >
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            {/* Jenis Petugas */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_petugas">
                                    Jenis Petugas{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <select
                                    id="jenis_petugas"
                                    value={data.jenis_petugas}
                                    onChange={(e) =>
                                        handleJenisPetugasChange(e.target.value)
                                    }
                                    required
                                    className="h-10 w-full rounded-lg border border-neutral-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                >
                                    <option value="organik">Organik</option>
                                    <option value="non-organik">
                                        Non-Organik
                                    </option>
                                </select>
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
