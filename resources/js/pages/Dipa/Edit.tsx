import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
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
    { title: 'DIPA', href: '/dipa' },
    { title: 'Edit DIPA', href: '#' },
];

interface Dipa {
    id: number;
    nomor_dipa: string;
    tahun: number;
    tanggal_dipa: string;
    is_active: boolean;
}

interface EditProps {
    dipa: Dipa;
    tahunOptions: number[];
}

export default function Edit({ dipa, tahunOptions }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        nomor_dipa: dipa.nomor_dipa || '',
        tahun: dipa.tahun || '',
        tanggal_dipa: dipa.tanggal_dipa || '',
        is_active: dipa.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/dipa/${dipa.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit DIPA - ${dipa.nomor_dipa}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit DIPA"
                    description={`Perbarui informasi DIPA ${dipa.nomor_dipa}`}
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/dipa">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <form onSubmit={handleSubmit}>
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Nomor DIPA */}
                            <div className="space-y-2">
                                <Label htmlFor="nomor_dipa">
                                    Nomor DIPA <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="nomor_dipa"
                                    type="text"
                                    value={data.nomor_dipa}
                                    onChange={(e) => setData('nomor_dipa', e.target.value)}
                                    required
                                    className="h-10"
                                />
                                <InputError message={errors.nomor_dipa} />
                            </div>

                            {/* Tahun */}
                            <div className="space-y-2">
                                <Label htmlFor="tahun">
                                    Tahun <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.tahun.toString()}
                                    onValueChange={(value) => setData('tahun', parseInt(value))}
                                >
                                    <SelectTrigger className="h-10">
                                        <SelectValue />
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
                                <Label htmlFor="tanggal_dipa">
                                    Tanggal DIPA <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="tanggal_dipa"
                                    type="date"
                                    value={data.tanggal_dipa}
                                    onChange={(e) => setData('tanggal_dipa', e.target.value)}
                                    required
                                    className="h-10"
                                />
                                <InputError message={errors.tanggal_dipa} />
                            </div>

                            {/* Is Active */}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
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
                            <Button type="button" variant="outline" asChild>
                                <Link href="/dipa">Batal</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                            </Button>
                        </div>
                    </form>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
