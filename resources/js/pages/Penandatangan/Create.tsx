import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';

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
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href="/penandatangan">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <Form action="/penandatangan/store" method="post">
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-6 md:grid-cols-2">
                                    {/* Nama */}
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="nama"
                                            className="text-base font-semibold"
                                        >
                                            Nama Lengkap{' '}
                                            <span className="text-red-600">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="nama"
                                            type="text"
                                            name="nama"
                                            required
                                            className="h-11 text-base"
                                            placeholder="Contoh: Dr. Ahmad Sutrisno, M.Si"
                                        />
                                        <InputError message={errors.nama} />
                                    </div>

                                    {/* NIP */}
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="nip"
                                            className="text-base font-semibold"
                                        >
                                            NIP
                                        </Label>
                                        <Input
                                            id="nip"
                                            type="text"
                                            name="nip"
                                            className="h-11 text-base"
                                            placeholder="Contoh: 197001011990031001"
                                        />
                                        <InputError message={errors.nip} />
                                    </div>

                                    {/* Jenis Penandatangan */}
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="jenis_penandatangan"
                                            className="text-base font-semibold"
                                        >
                                            Jenis Penandatangan{' '}
                                            <span className="text-red-600">
                                                *
                                            </span>
                                        </Label>
                                        <Select
                                            name="jenis_penandatangan"
                                            required
                                        >
                                            <SelectTrigger className="h-11">
                                                <SelectValue placeholder="Pilih jenis penandatangan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="kepala">
                                                    Kepala (untuk SK Petugas)
                                                </SelectItem>
                                                <SelectItem value="ppk">
                                                    PPK (untuk Perjanjian Kerja
                                                    dan BAST)
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.jenis_penandatangan}
                                        />
                                        <p className="text-xs text-neutral-500">
                                            Kepala menandatangani SK, PPK
                                            menandatangani Perjanjian Kerja dan
                                            BAST
                                        </p>
                                    </div>

                                    {/* Jabatan */}
                                    <div className="space-y-2 md:col-span-2">
                                        <Label
                                            htmlFor="jabatan"
                                            className="text-base font-semibold"
                                        >
                                            Jabatan{' '}
                                            <span className="text-red-600">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="jabatan"
                                            type="text"
                                            name="jabatan"
                                            required
                                            className="h-11 text-base"
                                            defaultValue="Penandatangan Kota Sawahlunto"
                                        />
                                        <InputError message={errors.jabatan} />
                                    </div>

                                    {/* Periode Mulai */}
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="periode_mulai"
                                            className="text-base font-semibold"
                                        >
                                            Periode Mulai
                                        </Label>
                                        <Input
                                            id="periode_mulai"
                                            type="date"
                                            name="periode_mulai"
                                            className="h-11 text-base"
                                        />
                                        <InputError
                                            message={errors.periode_mulai}
                                        />
                                    </div>

                                    {/* Periode Selesai */}
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="periode_selesai"
                                            className="text-base font-semibold"
                                        >
                                            Periode Selesai
                                        </Label>
                                        <Input
                                            id="periode_selesai"
                                            type="date"
                                            name="periode_selesai"
                                            className="h-11 text-base"
                                        />
                                        <InputError
                                            message={errors.periode_selesai}
                                        />
                                    </div>

                                    {/* Is Active */}
                                    <div className="flex items-center space-x-2 md:col-span-2">
                                        <Checkbox
                                            id="is_active"
                                            name="is_active"
                                            value="1"
                                            defaultChecked
                                            className="h-5 w-5"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="cursor-pointer text-base font-normal"
                                        >
                                            Status Aktif
                                        </Label>
                                    </div>
                                </div>

                                <div className="mt-6 flex justify-end gap-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                        className="min-w-[180px] gap-2"
                                    >
                                        <Link href="/penandatangan">
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
                            </>
                        )}
                    </Form>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
