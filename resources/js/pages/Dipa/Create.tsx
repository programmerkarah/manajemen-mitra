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
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';
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
    { title: 'DIPA', href: '/dipa' },
    { title: 'Tambah DIPA', href: '/dipa/create' },
];

interface CreateProps {
    tahunOptions: number[];
}

export default function Create({ tahunOptions }: CreateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah DIPA" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah DIPA"
                    description="Masukkan informasi DIPA (Daftar Isian Pelaksanaan Anggaran)"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/dipa">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <Form action="/dipa" method="post">
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-6 md:grid-cols-2">
                                    {/* Nomor DIPA */}
                                    <div className="space-y-2">
                                        <Label htmlFor="nomor_dipa" className="text-base font-semibold">
                                            Nomor DIPA <span className="text-red-600">*</span>
                                        </Label>
                                        <Input
                                            id="nomor_dipa"
                                            type="text"
                                            name="nomor_dipa"
                                            required
                                            className="h-11 text-base"
                                            placeholder="Contoh: SP DIPA-025.04.1.652512/2024"
                                        />
                                        <InputError message={errors.nomor_dipa} />
                                    </div>

                                    {/* Tahun */}
                                    <div className="space-y-2">
                                        <Label htmlFor="tahun" className="text-base font-semibold">
                                            Tahun <span className="text-red-600">*</span>
                                        </Label>
                                        <Select name="tahun" required>
                                            <SelectTrigger className="h-11">
                                                <SelectValue placeholder="Pilih tahun" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tahunOptions.map((year) => (
                                                    <SelectItem key={year} value={year.toString()}>
                                                        {year}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.tahun} />
                                    </div>

                                    {/* Tanggal DIPA */}
                                    <div className="space-y-2">
                                        <Label htmlFor="tanggal_dipa" className="text-base font-semibold">
                                            Tanggal DIPA <span className="text-red-600">*</span>
                                        </Label>
                                        <Input
                                            id="tanggal_dipa"
                                            type="date"
                                            name="tanggal_dipa"
                                            required
                                            className="h-11 text-base"
                                        />
                                        <InputError message={errors.tanggal_dipa} />
                                    </div>

                                    {/* Is Active */}
                                    <div className="flex items-center space-x-2">
                                        <Checkbox id="is_active" name="is_active" value="1" defaultChecked className="h-5 w-5" />
                                        <Label
                                            htmlFor="is_active"
                                            className="cursor-pointer text-base font-normal"
                                        >
                                            Status Aktif
                                        </Label>
                                    </div>
                                </div>

                                <div className="mt-6 flex justify-end gap-3">
                                    <Button type="button" variant="outline" asChild className="gap-2 min-w-[180px]">
                                        <Link href="/dipa">
                                            <X className="h-5 w-5" />
                                            Batal
                                        </Link>
                                    </Button>
                                    <Button type="submit" disabled={processing} className="gap-2 min-w-[180px]">
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
