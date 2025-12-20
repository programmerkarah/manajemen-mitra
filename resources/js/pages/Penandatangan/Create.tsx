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
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Penandatangan', href: '/penandatangan' },
    { title: 'Tambah Penandatangan', href: '/penandatangan/create' },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Penandatangan" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah Penandatangan"
                    description="Masukkan informasi Penandatangan"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/penandatangan">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <Form action="/penandatangan" method="post">
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
                                            placeholder="Contoh: Dr. Ahmad Sutrisno, M.Si"
                                        />
                                        <InputError message={errors.nama} />
                                    </div>

                                    {/* NIP */}
                                    <div className="space-y-2">
                                        <Label htmlFor="nip">NIP</Label>
                                        <Input
                                            id="nip"
                                            type="text"
                                            name="nip"
                                            className="h-10"
                                            placeholder="Contoh: 197001011990031001"
                                        />
                                        <InputError message={errors.nip} />
                                    </div>

                                    {/* Jenis Penandatangan */}
                                    <div className="space-y-2">
                                        <Label htmlFor="jenis_penandatangan">
                                            Jenis Penandatangan <span className="text-red-600">*</span>
                                        </Label>
                                        <Select name="jenis_penandatangan" required>
                                            <SelectTrigger className="h-10">
                                                <SelectValue placeholder="Pilih jenis penandatangan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="kepala">Kepala (untuk SK Petugas)</SelectItem>
                                                <SelectItem value="ppk">PPK (untuk SPK dan BAST)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.jenis_penandatangan} />
                                        <p className="text-xs text-neutral-500">
                                            Kepala menandatangani SK, PPK menandatangani SPK dan BAST
                                        </p>
                                    </div>

                                    {/* Jabatan */}
                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="jabatan">
                                            Jabatan <span className="text-red-600">*</span>
                                        </Label>
                                        <Input
                                            id="jabatan"
                                            type="text"
                                            name="jabatan"
                                            required
                                            className="h-10"
                                            defaultValue="Penandatangan Kota Sawahlunto"
                                        />
                                        <InputError message={errors.jabatan} />
                                    </div>

                                    {/* Periode Mulai */}
                                    <div className="space-y-2">
                                        <Label htmlFor="periode_mulai">Periode Mulai</Label>
                                        <Input
                                            id="periode_mulai"
                                            type="date"
                                            name="periode_mulai"
                                            className="h-10"
                                        />
                                        <InputError message={errors.periode_mulai} />
                                    </div>

                                    {/* Periode Selesai */}
                                    <div className="space-y-2">
                                        <Label htmlFor="periode_selesai">Periode Selesai</Label>
                                        <Input
                                            id="periode_selesai"
                                            type="date"
                                            name="periode_selesai"
                                            className="h-10"
                                        />
                                        <InputError message={errors.periode_selesai} />
                                    </div>

                                    {/* Is Active */}
                                    <div className="flex items-center space-x-2 md:col-span-2">
                                        <Checkbox id="is_active" name="is_active" value="1" defaultChecked />
                                        <Label
                                            htmlFor="is_active"
                                            className="cursor-pointer text-sm font-normal"
                                        >
                                            Status Aktif
                                        </Label>
                                    </div>
                                </div>

                                <div className="mt-6 flex justify-end gap-3">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href="/penandatangan">Batal</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
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

