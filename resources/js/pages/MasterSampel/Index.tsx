import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

interface MasterItem {
    id: number;
    nama: string;
    kode: string;
    deskripsi?: string | null;
    is_active: boolean;
}

interface Props {
    frames: MasterItem[];
    units: MasterItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data Sampel', href: '/master-sampel' },
];

export default function Index({ frames, units }: Props) {
    const [frameForm, setFrameForm] = useState({
        nama: '',
        kode: '',
        deskripsi: '',
    });

    const [unitForm, setUnitForm] = useState({
        nama: '',
        kode: '',
        deskripsi: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Data Sampel" />

            <PageHeader
                title="Master Data Sampel"
                description="Kelola master frame sampel dan unit sampel"
            />

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <ContentCard>
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold">
                            Master Frame Sampel
                        </h3>
                        <div className="grid grid-cols-1 gap-3">
                            <div className="space-y-2">
                                <Label>Nama</Label>
                                <Input
                                    value={frameForm.nama}
                                    onChange={(event) =>
                                        setFrameForm((prev) => ({
                                            ...prev,
                                            nama: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Kode</Label>
                                <Input
                                    value={frameForm.kode}
                                    onChange={(event) =>
                                        setFrameForm((prev) => ({
                                            ...prev,
                                            kode: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Deskripsi</Label>
                                <Input
                                    value={frameForm.deskripsi}
                                    onChange={(event) =>
                                        setFrameForm((prev) => ({
                                            ...prev,
                                            deskripsi: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <Button
                                onClick={() =>
                                    router.post(
                                        '/master-sampel/frame',
                                        frameForm,
                                        {
                                            onSuccess: () =>
                                                setFrameForm({
                                                    nama: '',
                                                    kode: '',
                                                    deskripsi: '',
                                                }),
                                        },
                                    )
                                }
                            >
                                Simpan Frame
                            </Button>
                        </div>

                        <div className="space-y-2 pt-2">
                            {frames.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-center justify-between rounded border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {item.nama}
                                        </p>
                                        <p className="text-xs text-neutral-500">
                                            {item.kode}
                                            {item.deskripsi
                                                ? ` - ${item.deskripsi}`
                                                : ''}
                                        </p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                `/master-sampel/frame/${item.id}`,
                                            )
                                        }
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold">
                            Master Unit Sampel
                        </h3>
                        <div className="grid grid-cols-1 gap-3">
                            <div className="space-y-2">
                                <Label>Nama</Label>
                                <Input
                                    value={unitForm.nama}
                                    onChange={(event) =>
                                        setUnitForm((prev) => ({
                                            ...prev,
                                            nama: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Kode</Label>
                                <Input
                                    value={unitForm.kode}
                                    onChange={(event) =>
                                        setUnitForm((prev) => ({
                                            ...prev,
                                            kode: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Deskripsi</Label>
                                <Input
                                    value={unitForm.deskripsi}
                                    onChange={(event) =>
                                        setUnitForm((prev) => ({
                                            ...prev,
                                            deskripsi: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <Button
                                onClick={() =>
                                    router.post(
                                        '/master-sampel/unit',
                                        unitForm,
                                        {
                                            onSuccess: () =>
                                                setUnitForm({
                                                    nama: '',
                                                    kode: '',
                                                    deskripsi: '',
                                                }),
                                        },
                                    )
                                }
                            >
                                Simpan Unit
                            </Button>
                        </div>

                        <div className="space-y-2 pt-2">
                            {units.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-center justify-between rounded border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {item.nama}
                                        </p>
                                        <p className="text-xs text-neutral-500">
                                            {item.kode}
                                            {item.deskripsi
                                                ? ` - ${item.deskripsi}`
                                                : ''}
                                        </p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                `/master-sampel/unit/${item.id}`,
                                            )
                                        }
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
