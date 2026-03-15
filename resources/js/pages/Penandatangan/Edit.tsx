import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Penandatangan', href: '/penandatangan' },
    { title: 'Edit Penandatangan', href: '#' },
];

interface Penandatangan {
    id: number;
    nama: string;
    nip: string | null;
    jenis_penandatangan: 'kepala' | 'ppk';
    jabatan: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    is_active: boolean;
}

interface EditProps {
    Penandatangan: Penandatangan;
}

export default function Edit({ Penandatangan }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        nama: Penandatangan.nama || '',
        nip: Penandatangan.nip || '',
        jenis_penandatangan: Penandatangan.jenis_penandatangan || 'kepala',
        jabatan: Penandatangan.jabatan || '',
        periode_mulai: Penandatangan.periode_mulai || '',
        periode_selesai: Penandatangan.periode_selesai || '',
        is_active: Penandatangan.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/penandatangan/${Penandatangan.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Penandatangan - ${Penandatangan.nama}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Penandatangan"
                    description={`Perbarui informasi untuk ${Penandatangan.nama}`}
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

                            {/* Jenis Penandatangan */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_penandatangan">
                                    Jenis Penandatangan{' '}
                                    <span className="text-red-600">*</span>
                                </Label>
                                <Select
                                    value={data.jenis_penandatangan}
                                    onValueChange={(value) =>
                                        setData(
                                            'jenis_penandatangan',
                                            value as 'kepala' | 'ppk',
                                        )
                                    }
                                >
                                    <SelectTrigger className="h-10">
                                        <SelectValue placeholder="Pilih jenis penandatangan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="kepala">
                                            Kepala (untuk SK Petugas)
                                        </SelectItem>
                                        <SelectItem value="ppk">
                                            PPK (untuk Perjanjian Kerja dan
                                            BAST)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.jenis_penandatangan}
                                />
                                <p className="text-xs text-neutral-500">
                                    Kepala menandatangani SK, PPK menandatangani
                                    Perjanjian Kerja dan BAST
                                </p>
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
                                <DatePicker
                                    id="periode_mulai"
                                    value={data.periode_mulai}
                                    onChange={(v) =>
                                        setData('periode_mulai', v)
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
                                <DatePicker
                                    id="periode_selesai"
                                    value={data.periode_selesai}
                                    onChange={(v) =>
                                        setData(
                                            'periode_selesai',
                                            v,
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
                                <Link href="/penandatangan">
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
