import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kepala BPS', href: '/kepala-bps' },
    { title: 'Tambah Kepala BPS', href: '/kepala-bps/create' },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Kepala BPS" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah Kepala BPS"
                    description="Masukkan informasi Kepala BPS"
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href="/kepala-bps">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <Form action="/kepala-bps" method="post">
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-6 md:grid-cols-2">
                                    {/* Nama */}
                                    <div className="space-y-2">
                                        <Label htmlFor="nama">
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

                                    {/* Jabatan */}
                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="jabatan">
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
                                            className="h-10"
                                            defaultValue="Kepala BPS Kota Sawahlunto"
                                        />
                                        <InputError message={errors.jabatan} />
                                    </div>

                                    {/* Periode Mulai */}
                                    <div className="space-y-2">
                                        <Label htmlFor="periode_mulai">
                                            Periode Mulai
                                        </Label>
                                        <Input
                                            id="periode_mulai"
                                            type="date"
                                            name="periode_mulai"
                                            className="h-10"
                                        />
                                        <InputError
                                            message={errors.periode_mulai}
                                        />
                                    </div>

                                    {/* Periode Selesai */}
                                    <div className="space-y-2">
                                        <Label htmlFor="periode_selesai">
                                            Periode Selesai
                                        </Label>
                                        <Input
                                            id="periode_selesai"
                                            type="date"
                                            name="periode_selesai"
                                            className="h-10"
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
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="cursor-pointer text-sm font-normal"
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
                                        <Link href="/kepala-bps">
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
