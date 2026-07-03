import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Kegiatan } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    Search,
    Trash2,
} from 'lucide-react';
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
    const [search, setSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(10);

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

    const filteredFrames = useMemo(() => {
        if (!search.trim()) {
            return visibleFrames;
        }

        const query = search.toLowerCase();

        return visibleFrames.filter((frame) => {
            const targetText = Object.values(frame.target_unit_sampel || {})
                .map((value) => String(value))
                .join(' ');

            return (
                frame.nama_frame?.toLowerCase().includes(query) ||
                frame.kode_kecamatan?.toLowerCase().includes(query) ||
                frame.kode_desa?.toLowerCase().includes(query) ||
                frame.kode_sls?.toLowerCase().includes(query) ||
                frame.kode_sub_sls?.toLowerCase().includes(query) ||
                frame.kode_segmen?.toLowerCase().includes(query) ||
                targetText.includes(query)
            );
        });
    }, [search, visibleFrames]);

    const totalPages = Math.ceil(filteredFrames.length / perPage);
    const effectiveCurrentPage =
        totalPages > 0 ? Math.min(currentPage, totalPages) : 1;

    const paginatedFrames = useMemo(() => {
        const start = (effectiveCurrentPage - 1) * perPage;
        return filteredFrames.slice(start, start + perPage);
    }, [effectiveCurrentPage, filteredFrames, perPage]);

    const totalTarget = useMemo(
        () =>
            filteredFrames.reduce(
                (sum, frame) =>
                    sum +
                    Object.values(frame.target_unit_sampel || {}).reduce(
                        (s, v) => s + Number(v),
                        0,
                    ),
                0,
            ),
        [filteredFrames],
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
                    <div className="space-y-4">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Tambah Frame
                            </h3>
                            <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                Tambahkan frame baru untuk tahapan yang sedang
                                aktif.
                            </p>
                        </div>
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
                                    {
                                        preserveState: true,
                                        onSuccess: () => {
                                            setActiveTab(activeTab);
                                            setCurrentPage(1);
                                            setSearch('');
                                            setForm((prev) => ({
                                                ...prev,
                                                tahapan: activeTab,
                                                nama_frame: '',
                                                kode_kecamatan: '',
                                                kode_desa: '',
                                                kode_sls: '',
                                                kode_sub_sls: '',
                                                kode_segmen: '',
                                                target_unit_sampel: {},
                                            }));
                                        },
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
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    Daftar Frame Sampel{' '}
                                    {activeTab === 'listing'
                                        ? 'Listing'
                                        : 'Pencacahan'}
                                </h3>
                                <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                    {filteredFrames.length} frame tampil · total
                                    target unit sampel {totalTarget}
                                </p>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2 lg:w-[28rem]">
                                <div className="relative sm:col-span-2 lg:col-span-1">
                                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                    <Input
                                        value={search}
                                        onChange={(event) => {
                                            setSearch(event.target.value);
                                            setCurrentPage(1);
                                        }}
                                        placeholder="Cari nama, kode, atau target..."
                                        className="pl-9"
                                    />
                                </div>
                                <div className="rounded-2xl border border-neutral-200 bg-neutral-50/80 px-4 py-2.5 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900/40 dark:text-neutral-300">
                                    Halaman {effectiveCurrentPage}
                                    {totalPages > 0
                                        ? ` dari ${totalPages}`
                                        : ''}
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-neutral-200/80 bg-neutral-50/70 px-4 py-3 text-sm text-neutral-600 dark:border-neutral-700/70 dark:bg-neutral-900/40 dark:text-neutral-300">
                            Menampilkan{' '}
                            {filteredFrames.length === 0
                                ? 0
                                : (effectiveCurrentPage - 1) * perPage + 1}
                            -
                            {Math.min(
                                effectiveCurrentPage * perPage,
                                filteredFrames.length,
                            )}{' '}
                            dari {filteredFrames.length} frame
                            {search.trim() &&
                            filteredFrames.length !== visibleFrames.length
                                ? ` (difilter dari ${visibleFrames.length} total tahapan ini)`
                                : ''}
                        </div>

                        <div className="space-y-3">
                            {paginatedFrames.length === 0 ? (
                                <div className="rounded-2xl border border-dashed border-neutral-300 px-4 py-10 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                    {search.trim()
                                        ? 'Tidak ada frame yang cocok dengan pencarian.'
                                        : 'Belum ada frame pada tahapan ini.'}
                                </div>
                            ) : (
                                paginatedFrames.map((frame) => (
                                    <div
                                        key={frame.id}
                                        className="flex flex-col gap-4 rounded-2xl border border-neutral-200 bg-white/70 p-4 transition-colors hover:border-neutral-300 hover:bg-white lg:flex-row lg:items-start lg:justify-between dark:border-neutral-700 dark:bg-neutral-900/50 dark:hover:border-neutral-600"
                                    >
                                        <div className="min-w-0 flex-1 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold text-neutral-900 dark:text-white">
                                                    {frame.nama_frame || '-'}
                                                </p>
                                                <span className="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300">
                                                    {frame.tahapan}
                                                </span>
                                            </div>
                                            <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                                Kec:{' '}
                                                {frame.kode_kecamatan || '-'}
                                                {' · '}Desa:{' '}
                                                {frame.kode_desa || '-'}
                                                {' · '}SLS:{' '}
                                                {frame.kode_sls || '-'}
                                                {' · '}Sub SLS:{' '}
                                                {frame.kode_sub_sls || '-'}
                                                {' · '}Segmen:{' '}
                                                {frame.kode_segmen || '-'}
                                            </p>
                                            <div className="flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                                                {(frame.tahapan === 'listing'
                                                    ? unitSampelListingItems
                                                    : unitSampelPencacahanItems
                                                ).length > 0 ? (
                                                    (frame.tahapan === 'listing'
                                                        ? unitSampelListingItems
                                                        : unitSampelPencacahanItems
                                                    ).map((u) => (
                                                        <span
                                                            key={u.id}
                                                            className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                                                        >
                                                            {u.nama}:{' '}
                                                            {(frame.target_unit_sampel ||
                                                                {})[
                                                                String(u.id)
                                                            ] ?? 0}
                                                        </span>
                                                    ))
                                                ) : (
                                                    <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 dark:border-neutral-700 dark:bg-neutral-800">
                                                        Total target:{' '}
                                                        {Object.values(
                                                            frame.target_unit_sampel ||
                                                                {},
                                                        ).reduce(
                                                            (s, v) =>
                                                                s + Number(v),
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
                                            className="w-full shrink-0 gap-2 lg:w-auto"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Hapus
                                        </Button>
                                    </div>
                                ))
                            )}
                        </div>

                        {totalPages > 1 && (
                            <div className="flex flex-col gap-3 border-t border-neutral-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Halaman {effectiveCurrentPage} dari{' '}
                                    {totalPages}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setCurrentPage((prev) =>
                                                Math.max(prev - 1, 1),
                                            )
                                        }
                                        disabled={effectiveCurrentPage <= 1}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setCurrentPage((prev) =>
                                                Math.min(prev + 1, totalPages),
                                            )
                                        }
                                        disabled={
                                            effectiveCurrentPage >= totalPages
                                        }
                                    >
                                        Berikutnya
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
