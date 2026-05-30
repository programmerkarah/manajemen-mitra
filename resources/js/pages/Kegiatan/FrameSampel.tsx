import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Kegiatan } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

interface KegiatanFrameSampel {
    id: number;
    tahapan: 'listing' | 'pencacahan';
    nama_frame?: string | null;
    kode_kecamatan?: string | null;
    kode_desa?: string | null;
    kode_sls?: string | null;
    kode_sub_sls?: string | null;
    kode_segmen?: string | null;
    target_unit_sampel: number;
}

interface Props {
    kegiatan: Kegiatan;
    frames: KegiatanFrameSampel[];
}

export default function FrameSampel({ kegiatan, frames }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/kegiatan' },
        {
            title: 'Frame Sampel Kegiatan',
            href: `/kegiatan/${kegiatan.hashed_id}/frame-sampel`,
        },
    ];

    const [form, setForm] = useState({
        tahapan: 'pencacahan' as 'listing' | 'pencacahan',
        nama_frame: '',
        kode_kecamatan: '',
        kode_desa: '',
        kode_sls: '',
        kode_sub_sls: '',
        kode_segmen: '',
        target_unit_sampel: 0,
    });

    const totalTarget = useMemo(
        () =>
            frames.reduce(
                (sum, frame) => sum + Number(frame.target_unit_sampel || 0),
                0,
            ),
        [frames],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Frame Sampel Kegiatan" />

            <PageHeader
                title="Frame Sampel Kegiatan"
                description={`Kelola daftar frame sampel untuk ${kegiatan.nama_kegiatan}`}
            >
                <Button variant="outline" asChild>
                    <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <ContentCard>
                    <div className="space-y-3">
                        <h3 className="text-lg font-semibold">Tambah Frame</h3>
                        <div className="space-y-2">
                            <Label>Tahapan</Label>
                            <select
                                value={form.tahapan}
                                onChange={(event) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        tahapan: event.target.value as
                                            | 'listing'
                                            | 'pencacahan',
                                    }))
                                }
                                className="h-10 w-full rounded border border-neutral-300 px-3 dark:border-neutral-700 dark:bg-neutral-900"
                            >
                                <option value="listing">Listing</option>
                                <option value="pencacahan">Pencacahan</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Nama Frame</Label>
                            <Input
                                value={form.nama_frame}
                                onChange={(event) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        nama_frame: event.target.value,
                                    }))
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div className="space-y-2">
                                <Label>Kode Kec</Label>
                                <Input
                                    value={form.kode_kecamatan}
                                    onChange={(event) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            kode_kecamatan: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Kode Desa</Label>
                                <Input
                                    value={form.kode_desa}
                                    onChange={(event) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            kode_desa: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Kode SLS</Label>
                                <Input
                                    value={form.kode_sls}
                                    onChange={(event) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            kode_sls: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Kode Sub SLS</Label>
                                <Input
                                    value={form.kode_sub_sls}
                                    onChange={(event) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            kode_sub_sls: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="col-span-2 space-y-2">
                                <Label>Kode Segmen</Label>
                                <Input
                                    value={form.kode_segmen}
                                    onChange={(event) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            kode_segmen: event.target.value,
                                        }))
                                    }
                                />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label>Target Unit Sampel</Label>
                            <Input
                                type="number"
                                min={0}
                                value={form.target_unit_sampel}
                                onChange={(event) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        target_unit_sampel:
                                            Number(event.target.value) || 0,
                                    }))
                                }
                            />
                        </div>
                        <Button
                            onClick={() =>
                                router.post(
                                    `/kegiatan/${kegiatan.hashed_id}/frame-sampel`,
                                    form,
                                )
                            }
                            className="w-full"
                        >
                            Simpan
                        </Button>
                    </div>
                </ContentCard>

                <ContentCard className="lg:col-span-2">
                    <div className="space-y-3">
                        <h3 className="text-lg font-semibold">
                            Daftar Frame Sampel
                        </h3>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Total target unit sampel: {totalTarget}
                        </p>

                        <div className="space-y-2">
                            {frames.map((frame) => (
                                <div
                                    key={frame.id}
                                    className="flex items-center justify-between rounded border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {frame.nama_frame || '-'} (
                                            {frame.tahapan})
                                        </p>
                                        <p className="text-xs text-neutral-500">
                                            Kec: {frame.kode_kecamatan || '-'} |
                                            Desa: {frame.kode_desa || '-'} |
                                            SLS: {frame.kode_sls || '-'} | Sub
                                            SLS: {frame.kode_sub_sls || '-'} |
                                            Segmen: {frame.kode_segmen || '-'}
                                        </p>
                                        <p className="text-xs text-neutral-500">
                                            Target unit:{' '}
                                            {frame.target_unit_sampel}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.delete(
                                                `/kegiatan/${kegiatan.hashed_id}/frame-sampel/${frame.id}`,
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
