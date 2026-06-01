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
    target_unit_sampel: Record<string, number>;
}

interface UnitSampelItem {
    id: number;
    nama: string;
}

interface Props {
    kegiatan: Kegiatan;
    frames: KegiatanFrameSampel[];
    unitSampelPencacahanItems: UnitSampelItem[];
    unitSampelListingItems: UnitSampelItem[];
}

export default function FrameSampel({
    kegiatan,
    frames,
    unitSampelPencacahanItems,
    unitSampelListingItems,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/kegiatan' },
        {
            title: 'Frame Sampel Kegiatan',
            href: `/kegiatan/${kegiatan.hashed_id}/frame-sampel`,
        },
    ];

    const [targetError, setTargetError] = useState<string | null>(null);
    const isListingEnabled = Boolean(kegiatan.has_listing_updating);
    const [activeTab, setActiveTab] = useState<'listing' | 'pencacahan'>(
        isListingEnabled ? 'listing' : 'pencacahan',
    );

    const [form, setForm] = useState({
        tahapan: (isListingEnabled ? 'listing' : 'pencacahan') as
            | 'listing'
            | 'pencacahan',
        nama_frame: '',
        kode_kecamatan: '',
        kode_desa: '',
        kode_sls: '',
        kode_sub_sls: '',
        kode_segmen: '',
        target_unit_sampel: {} as Record<string, string>,
    });

    const activeUnitSampelItems =
        activeTab === 'listing'
            ? unitSampelListingItems
            : unitSampelPencacahanItems;

    const visibleFrames = useMemo(
        () => frames.filter((frame) => frame.tahapan === activeTab),
        [activeTab, frames],
    );

    const totalTarget = useMemo(
        () =>
            visibleFrames.reduce(
                (sum, frame) =>
                    sum +
                    Object.values(frame.target_unit_sampel || {}).reduce(
                        (s, v) => s + Number(v),
                        0,
                    ),
                0,
            ),
        [visibleFrames],
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
                            <Label>Mode Tahapan</Label>
                            <div className="grid grid-cols-2 gap-2">
                                {isListingEnabled && (
                                    <Button
                                        type="button"
                                        variant={
                                            activeTab === 'listing'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() => {
                                            setActiveTab('listing');
                                            setForm((prev) => ({
                                                ...prev,
                                                tahapan: 'listing',
                                                target_unit_sampel: {},
                                            }));
                                            setTargetError(null);
                                        }}
                                    >
                                        Listing
                                    </Button>
                                )}
                                <Button
                                    type="button"
                                    variant={
                                        activeTab === 'pencacahan'
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className={
                                        isListingEnabled ? '' : 'col-span-2'
                                    }
                                    onClick={() => {
                                        setActiveTab('pencacahan');
                                        setForm((prev) => ({
                                            ...prev,
                                            tahapan: 'pencacahan',
                                            target_unit_sampel: {},
                                        }));
                                        setTargetError(null);
                                    }}
                                >
                                    Pencacahan
                                </Button>
                            </div>
                            {!isListingEnabled && (
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Listing tidak aktif di master kegiatan, jadi
                                    hanya mode pencacahan yang tersedia.
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label>Tahapan Aktif</Label>
                            <Input
                                value={
                                    activeTab === 'listing'
                                        ? 'Listing'
                                        : 'Pencacahan'
                                }
                                readOnly
                            />
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
                            {targetError && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {targetError}
                                </p>
                            )}
                            {activeUnitSampelItems.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada unit sampel untuk tahapan ini.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {activeUnitSampelItems.map((unitSampel) => (
                                        <div key={unitSampel.id}>
                                            <Label className="text-sm font-normal">
                                                {unitSampel.nama}
                                            </Label>
                                            <Input
                                                type="number"
                                                min={0}
                                                value={
                                                    form.target_unit_sampel[
                                                        String(unitSampel.id)
                                                    ] ?? ''
                                                }
                                                onChange={(event) =>
                                                    setForm((prev) => ({
                                                        ...prev,
                                                        target_unit_sampel: {
                                                            ...prev.target_unit_sampel,
                                                            [String(
                                                                unitSampel.id,
                                                            )]:
                                                                event.target
                                                                    .value,
                                                        },
                                                    }))
                                                }
                                                placeholder="Contoh: 2"
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                        <Button
                            onClick={() => {
                                const filtered = Object.fromEntries(
                                    Object.entries(form.target_unit_sampel)
                                        .filter(
                                            ([, v]) =>
                                                v !== '' && Number(v) >= 0,
                                        )
                                        .map(([k, v]) => [k, Number(v)]),
                                );
                                const total = Object.values(filtered).reduce(
                                    (s, v) => s + v,
                                    0,
                                );
                                if (total <= 0) {
                                    setTargetError(
                                        'Total target unit sampel harus lebih dari 0.',
                                    );
                                    return;
                                }
                                setTargetError(null);
                                router.post(
                                    `/kegiatan/${kegiatan.hashed_id}/frame-sampel`,
                                    {
                                        ...form,
                                        tahapan: activeTab,
                                        target_unit_sampel: filtered,
                                    },
                                );
                            }}
                            className="w-full"
                        >
                            Simpan
                        </Button>
                    </div>
                </ContentCard>

                <ContentCard className="lg:col-span-2">
                    <div className="space-y-3">
                        <h3 className="text-lg font-semibold">
                            Daftar Frame Sampel{' '}
                            {activeTab === 'listing' ? 'Listing' : 'Pencacahan'}
                        </h3>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Total target unit sampel: {totalTarget}
                        </p>

                        <div className="space-y-2">
                            {visibleFrames.map((frame) => (
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
                                        <div className="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-neutral-500">
                                            {(frame.tahapan === 'listing'
                                                ? unitSampelListingItems
                                                : unitSampelPencacahanItems
                                            ).length > 0 ? (
                                                (frame.tahapan === 'listing'
                                                    ? unitSampelListingItems
                                                    : unitSampelPencacahanItems
                                                ).map((u) => (
                                                    <span key={u.id}>
                                                        {u.nama}:{' '}
                                                        {(frame.target_unit_sampel ||
                                                            {})[String(u.id)] ??
                                                            0}
                                                    </span>
                                                ))
                                            ) : (
                                                <span>
                                                    Target:{' '}
                                                    {Object.values(
                                                        frame.target_unit_sampel ||
                                                            {},
                                                    ).reduce(
                                                        (s, v) => s + Number(v),
                                                        0,
                                                    )}
                                                </span>
                                            )}
                                        </div>
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
