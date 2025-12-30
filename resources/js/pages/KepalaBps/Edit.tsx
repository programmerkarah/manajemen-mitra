import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Kepala BPS', href: '/kepala-bps' },
    { title: 'Edit Kepala BPS', href: '#' },
];

interface KepalaBps {
    id: number;
    nama: string;
    nip: string | null;
    jabatan: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    is_active: boolean;
}

interface EditProps {
    kepalaBps: KepalaBps;
}

export default function Edit({ kepalaBps }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        nama: kepalaBps.nama || '',
        nip: kepalaBps.nip || '',
        jabatan: kepalaBps.jabatan || '',
        periode_mulai: kepalaBps.periode_mulai || '',
        periode_selesai: kepalaBps.periode_selesai || '',
        is_active: kepalaBps.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/kepala-bps/${kepalaBps.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Kepala BPS - ${kepalaBps.nama}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Kepala BPS"
                    description={`Perbarui informasi untuk ${kepalaBps.nama}`}
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

                            {/* NIP */}
                            <div className="space-y-2">
                                <Label htmlFor="nip">NIP</Label>
                                <Input
                                    id="nip"
                                    type="text"
                                    value={data.nip}
                                    onChange={(e) =>
                                        setData('nip', e.target.value)
                                    }
                                    className="h-10"
                                />
                                <InputError message={errors.nip} />
                            </div>

                            {/* Jabatan */}
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="jabatan">
                                    Jabatan{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Input
                                    id="jabatan"
                                    type="text"
                                    value={data.jabatan}
                                    onChange={(e) =>
                                        setData('jabatan', e.target.value)
                                    }
                                    required
                                    className="h-10"
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
                                    value={data.periode_mulai}
                                    onChange={(e) =>
                                        setData('periode_mulai', e.target.value)
                                    }
                                    className="h-10"
                                />
                                <InputError message={errors.periode_mulai} />
                            </div>

                            {/* Periode Selesai */}
                            <div className="space-y-2">
                                <Label htmlFor="periode_selesai">
                                    Periode Selesai
                                </Label>
                                <Input
                                    id="periode_selesai"
                                    type="date"
                                    value={data.periode_selesai}
                                    onChange={(e) =>
                                        setData(
                                            'periode_selesai',
                                            e.target.value,
                                        )
                                    }
                                    className="h-10"
                                />
                                <InputError message={errors.periode_selesai} />
                            </div>

                            {/* Is Active */}
                            <div className="flex items-center space-x-2 md:col-span-2">
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
