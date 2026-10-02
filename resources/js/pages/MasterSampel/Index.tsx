import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Boxes,
    Database,
    Layers3,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface MasterItem {
    id: number;
    hashed_id: string;
    nama: string;
    kode: string;
    deskripsi?: string | null;
    is_active: boolean;
}

interface Props {
    frames: MasterItem[];
    units: MasterItem[];
}

type FormData = {
    nama: string;
    kode: string;
    deskripsi: string;
    is_active: boolean;
};

type MasterType = 'frame' | 'unit';

const emptyForm: FormData = {
    nama: '',
    kode: '',
    deskripsi: '',
    is_active: true,
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data Sampel', href: '/master-sampel' },
];

export default function Index({ frames, units }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [activeType, setActiveType] = useState<MasterType>('frame');
    const [search, setSearch] = useState('');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<MasterItem | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<MasterItem | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm<FormData>(emptyForm);

    const config = {
        frame: {
            title: 'Frame Sampel',
            description:
                'Daftar jenis frame yang dapat dipilih pada konfigurasi kegiatan.',
            icon: Layers3,
            items: frames,
            storeUrl: '/master-sampel/frame',
            updateUrl: (hashedId: string) =>
                `/master-sampel/frame/${hashedId}`,
            destroyUrl: (hashedId: string) =>
                `/master-sampel/frame/${hashedId}`,
        },
        unit: {
            title: 'Unit Sampel',
            description:
                'Daftar jenis unit sampel yang digunakan untuk target dan alokasi kegiatan.',
            icon: Boxes,
            items: units,
            storeUrl: '/master-sampel/unit',
            updateUrl: (hashedId: string) =>
                `/master-sampel/unit/${hashedId}`,
            destroyUrl: (hashedId: string) =>
                `/master-sampel/unit/${hashedId}`,
        },
    } satisfies Record<
        MasterType,
        {
            title: string;
            description: string;
            icon: typeof Layers3;
            items: MasterItem[];
            storeUrl: string;
            updateUrl: (hashedId: string) => string;
            destroyUrl: (hashedId: string) => string;
        }
    >;

    const activeConfig = config[activeType];
    const ActiveIcon = activeConfig.icon;

    const filteredItems = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return activeConfig.items;

        return activeConfig.items.filter(
            (item) =>
                item.nama.toLowerCase().includes(query) ||
                item.kode.toLowerCase().includes(query) ||
                item.deskripsi?.toLowerCase().includes(query),
        );
    }, [activeConfig.items, search]);

    const activeCount = activeConfig.items.filter(
        (item) => item.is_active,
    ).length;

    function switchType(type: MasterType) {
        setActiveType(type);
        setSearch('');
    }

    function openCreate() {
        setEditingItem(null);
        reset();
        clearErrors();
        setDialogOpen(true);
    }

    function openEdit(item: MasterItem) {
        setEditingItem(item);
        setData({
            nama: item.nama,
            kode: item.kode,
            deskripsi: item.deskripsi ?? '',
            is_active: item.is_active,
        });
        clearErrors();
        setDialogOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                setEditingItem(null);
                reset();
            },
        };

        if (editingItem) {
            put(activeConfig.updateUrl(editingItem.hashed_id), options);
            return;
        }

        post(activeConfig.storeUrl, options);
    }

    function handleDelete() {
        if (!deleteTarget) return;

        router.delete(activeConfig.destroyUrl(deleteTarget.hashed_id), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Data Sampel" />

            <PageHeader
                title="Master Data Sampel"
                description="Kelola referensi frame dan unit sampel yang digunakan pada konfigurasi kegiatan."
            >
                <Button className="gap-2" onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    Tambah {activeConfig.title}
                </Button>
            </PageHeader>

            {(flash.success || flash.error) && (
                <div
                    className={`rounded-xl border px-4 py-3 text-sm ${
                        flash.success
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200'
                            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200'
                    }`}
                >
                    {flash.success ?? flash.error}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                {(
                    [
                        ['frame', config.frame],
                        ['unit', config.unit],
                    ] as const
                ).map(([type, item]) => {
                    const Icon = item.icon;
                    const selected = activeType === type;
                    const itemActiveCount = item.items.filter(
                        (entry) => entry.is_active,
                    ).length;

                    return (
                        <button
                            key={type}
                            type="button"
                            onClick={() => switchType(type)}
                            className={[
                                'group rounded-2xl border p-4 text-left transition-all',
                                selected
                                    ? 'border-primary bg-primary/5 shadow-sm ring-1 ring-primary/20'
                                    : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900/50 dark:hover:border-neutral-700',
                            ].join(' ')}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex min-w-0 items-start gap-3">
                                    <div
                                        className={[
                                            'rounded-xl p-2.5',
                                            selected
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300',
                                        ].join(' ')}
                                    >
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="font-semibold text-neutral-900 dark:text-white">
                                            {item.title}
                                        </div>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {item.description}
                                        </p>
                                    </div>
                                </div>
                                <Badge variant={selected ? 'default' : 'secondary'}>
                                    {item.items.length}
                                </Badge>
                            </div>
                            <div className="mt-4 flex items-center gap-4 text-xs text-muted-foreground">
                                <span>{itemActiveCount} aktif</span>
                                <span>
                                    {item.items.length - itemActiveCount} nonaktif
                                </span>
                            </div>
                        </button>
                    );
                })}
            </div>

            <ContentCard>
                <div className="space-y-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="rounded-xl bg-neutral-100 p-2.5 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                <ActiveIcon className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        {activeConfig.title}
                                    </h2>
                                    <Badge variant="outline">
                                        {activeConfig.items.length} data
                                    </Badge>
                                    <Badge variant="secondary">
                                        {activeCount} aktif
                                    </Badge>
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {activeConfig.description}
                                </p>
                            </div>
                        </div>

                        <div className="relative w-full lg:w-80">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama, kode, atau deskripsi..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </div>

                    {filteredItems.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 py-12 text-center dark:border-neutral-700">
                            <Database className="mb-3 h-9 w-9 text-muted-foreground/40" />
                            <p className="font-medium text-neutral-700 dark:text-neutral-200">
                                {search
                                    ? 'Data tidak ditemukan'
                                    : `Belum ada ${activeConfig.title.toLowerCase()}`}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {search
                                    ? 'Coba gunakan kata kunci lain.'
                                    : 'Tambahkan data pertama untuk mulai digunakan pada kegiatan.'}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800">
                            <div className="hidden grid-cols-[minmax(0,1.4fr)_minmax(10rem,.55fr)_minmax(0,1fr)_7rem] gap-4 bg-neutral-50 px-4 py-2.5 text-xs font-medium tracking-wide text-muted-foreground uppercase md:grid dark:bg-neutral-900/70">
                                <span>Nama</span>
                                <span>Kode</span>
                                <span>Deskripsi</span>
                                <span className="text-right">Aksi</span>
                            </div>

                            <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {filteredItems.map((item) => (
                                    <div
                                        key={item.id}
                                        className="grid gap-3 px-4 py-3 transition-colors hover:bg-neutral-50 md:grid-cols-[minmax(0,1.4fr)_minmax(10rem,.55fr)_minmax(0,1fr)_7rem] md:items-center md:gap-4 dark:hover:bg-neutral-900/50"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="truncate font-medium text-neutral-900 dark:text-white">
                                                    {item.nama}
                                                </span>
                                                <Badge
                                                    variant={
                                                        item.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                    className="shrink-0 text-[11px]"
                                                >
                                                    {item.is_active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </Badge>
                                            </div>
                                        </div>

                                        <div>
                                            <span className="rounded-md bg-neutral-100 px-2 py-1 font-mono text-xs text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                                {item.kode}
                                            </span>
                                        </div>

                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {item.deskripsi || '—'}
                                        </p>

                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8"
                                                onClick={() => openEdit(item)}
                                                aria-label={`Edit ${item.nama}`}
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                onClick={() =>
                                                    setDeleteTarget(item)
                                                }
                                                aria-label={`Hapus ${item.nama}`}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </ContentCard>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingItem
                                ? `Edit ${activeConfig.title}`
                                : `Tambah ${activeConfig.title}`}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-5 pt-1">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="nama">
                                    Nama <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="nama"
                                    value={data.nama}
                                    onChange={(e) =>
                                        setData('nama', e.target.value)
                                    }
                                    placeholder={`Nama ${activeConfig.title.toLowerCase()}`}
                                    autoFocus
                                />
                                <InputError message={errors.nama} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="kode">
                                    Kode <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="kode"
                                    value={data.kode}
                                    onChange={(e) =>
                                        setData('kode', e.target.value)
                                    }
                                    placeholder="Kode unik"
                                    className="font-mono"
                                />
                                <InputError message={errors.kode} />
                            </div>

                            <div className="flex items-end">
                                <div className="flex w-full items-center justify-between rounded-xl border border-neutral-200 px-3 py-2.5 dark:border-neutral-800">
                                    <div>
                                        <Label htmlFor="is_active">
                                            Status aktif
                                        </Label>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            Tampilkan sebagai pilihan pada kegiatan.
                                        </p>
                                    </div>
                                    <Switch
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) =>
                                            setData('is_active', checked)
                                        }
                                    />
                                </div>
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="deskripsi">Deskripsi</Label>
                                <Textarea
                                    id="deskripsi"
                                    value={data.deskripsi}
                                    onChange={(e) =>
                                        setData('deskripsi', e.target.value)
                                    }
                                    placeholder="Keterangan singkat penggunaan data ini"
                                    rows={3}
                                />
                                <InputError message={errors.deskripsi} />
                            </div>
                        </div>

                        <DialogFooter className="border-t border-neutral-200 pt-4 dark:border-neutral-800">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? 'Menyimpan...'
                                    : editingItem
                                      ? 'Simpan Perubahan'
                                      : 'Tambah Data'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
            >
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Hapus {activeConfig.title}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        Yakin ingin menghapus{' '}
                        <span className="font-medium text-foreground">
                            {deleteTarget?.nama}
                        </span>
                        ? Data yang sudah digunakan pada kegiatan mungkin tidak
                        dapat dihapus.
                    </p>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteTarget(null)}
                        >
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
