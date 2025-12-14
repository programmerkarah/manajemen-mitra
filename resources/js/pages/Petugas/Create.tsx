import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Petugas', href: '/petugas' },
    { title: 'Tambah Petugas', href: '/petugas/create' },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Petugas" />
            
            <div className="space-y-6">
                <PageHeader
                    title="Tambah Petugas Baru"
                    description="Masukkan informasi lengkap petugas mitra"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/petugas">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <Form
                        action="/petugas"
                        method="post"
                    >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-6 md:grid-cols-2">
                                {/* Nama */}
                                <div className="space-y-2">
                                    <Label htmlFor="nama">
                                        Nama Lengkap <span className="text-red-600">*</span>
                                    </Label>
                                    <Input
                                        id="nama"
                                        type="text"
                                        name="nama"
                                        required
                                        className="h-10"
                                    />
                                    <InputError message={errors.nama} />
                                </div>

                                {/* NIK */}
                                <div className="space-y-2">
                                    <Label htmlFor="nik">
                                        NIK <span className="text-red-600">*</span>
                                    </Label>
                                    <Input
                                        id="nik"
                                        type="text"
                                        name="nik"
                                        maxLength={16}
                                        required
                                        className="h-10"
                                    />
                                    <InputError message={errors.nik} />
                                </div>

                                {/* Email */}
                                <div className="space-y-2">
                                    <Label htmlFor="email">
                                        Email <span className="text-red-600">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        className="h-10"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {/* Telepon */}
                                <div className="space-y-2">
                                    <Label htmlFor="telepon">
                                        Telepon <span className="text-red-600">*</span>
                                    </Label>
                                    <Input
                                        id="telepon"
                                        type="text"
                                        name="telepon"
                                        required
                                        className="h-10"
                                    />
                                    <InputError message={errors.telepon} />
                                </div>

                                {/* Pendidikan */}
                                <div className="space-y-2">
                                    <Label htmlFor="pendidikan">
                                        Pendidikan <span className="text-red-600">*</span>
                                    </Label>
                                    <select
                                        id="pendidikan"
                                        name="pendidikan"
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
                                        Tahun Bergabung <span className="text-red-600">*</span>
                                    </Label>
                                    <select
                                        id="tahun_bergabung"
                                        name="tahun_bergabung"
                                        required
                                        defaultValue={new Date().getFullYear().toString()}
                                        className="h-10 w-full rounded-lg border border-neutral-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    >
                                        <option value="">Pilih Tahun</option>
                                        {Array.from({ length: new Date().getFullYear() - 2000 + 2 }, (_, i) => new Date().getFullYear() + 1 - i).map(year => (
                                            <option key={year} value={year}>{year}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.tahun_bergabung} />
                                </div>

                                {/* Status */}
                                <div className="space-y-2">
                                    <Label htmlFor="status">
                                        Status <span className="text-red-600">*</span>
                                    </Label>
                                    <select
                                        id="status"
                                        name="status"
                                        required
                                        defaultValue="aktif"
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
                                        Jenis Petugas <span className="text-red-600">*</span>
                                    </Label>
                                    <select
                                        id="jenis_petugas"
                                        name="jenis_petugas"
                                        required
                                        defaultValue="non-organik"
                                        className="h-10 w-full rounded-lg border border-neutral-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    >
                                        <option value="organik">Organik</option>
                                        <option value="non-organik">Non-Organik</option>
                                    </select>
                                    <InputError message={errors.jenis_petugas} />
                                </div>

                                {/* NPWP */}
                                <div className="space-y-2">
                                    <Label htmlFor="npwp">NPWP</Label>
                                    <Input
                                        id="npwp"
                                        type="text"
                                        name="npwp"
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
                                        name="bank"
                                        className="h-10"
                                    />
                                    <InputError message={errors.bank} />
                                </div>

                                {/* No Rekening */}
                                <div className="space-y-2">
                                    <Label htmlFor="no_rekening">No. Rekening</Label>
                                    <Input
                                        id="no_rekening"
                                        type="text"
                                        name="no_rekening"
                                        className="h-10"
                                    />
                                    <InputError message={errors.no_rekening} />
                                </div>

                                {/* Nama Rekening */}
                                <div className="space-y-2">
                                    <Label htmlFor="nama_rekening">Nama Rekening</Label>
                                    <Input
                                        id="nama_rekening"
                                        type="text"
                                        name="nama_rekening"
                                        className="h-10"
                                    />
                                    <InputError message={errors.nama_rekening} />
                                </div>

                                {/* Alamat */}
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="alamat">
                                        Alamat <span className="text-red-600">*</span>
                                    </Label>
                                    <textarea
                                        id="alamat"
                                        name="alamat"
                                        rows={3}
                                        required
                                        className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    />
                                    <InputError message={errors.alamat} />
                                </div>

                                {/* Catatan */}
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="catatan">Catatan</Label>
                                    <textarea
                                        id="catatan"
                                        name="catatan"
                                        rows={3}
                                        className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    />
                                    <InputError message={errors.catatan} />
                                </div>
                            </div>

                            <div className="mt-6 flex justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                                <Button
                                    type="button"
                                    variant="outline"
                                    asChild
                                >
                                    <Link href="/petugas">Batal</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
                </ContentCard>
            </div>
        </AppLayout>
    );
}


