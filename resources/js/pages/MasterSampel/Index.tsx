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
import { Layers, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';

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

const emptyForm: FormData = {
    nama: '',
    kode: '',
    deskripsi: '',
    is_active: true,
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data Sampel', href: '/master-sampel' },
];

function SampelSection({
    title,
    items,
    storeUrl,
    updateUrl,
    destroyUrl,
}: {
    title: string;
    items: MasterItem[];
    storeUrl: string;
    updateUrl: (hashedId: string) => string;
    destroyUrl: (hashedId: string) => string;
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<MasterItem | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<MasterItem | null>(null);
    const [search, setSearch] = useState('');

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm<FormData>(emptyForm);

    const filtered = items.filter(
        (item) =>
            item.nama.toLowerCase().includes(search.toLowerCase()) ||
            item.kode.toLowerCase().includes(search.toLowerCase()),
    );

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
        if (editingItem) {
            put(updateUrl(editingItem.hashed_id), {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            post(storeUrl, {
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        }
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(destroyUrl(deleteTarget.hashed_id), {
            onFinish: () => setDeleteTarget(null),
        });
    }

    return (
        <>
            <ContentCard>
                <div className="space-y-4">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <Layers className="h-5 w-5 text-muted-foreground" />
                            <h3 className="text-base font-semibold">{title}</h3>
                            <Badge variant="secondary">{items.length}</Badge>
                        </div>
                        <Button
                            size="sm"
                            className="gap-2"
                            onClick={openCreate}
                        >
                            <Plus className="h-4 w-4" />
                            Tambah
                        </Button>
                    </div>

                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Cari nama atau kode..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>

                    {filtered.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                            {search
                                ? 'Tidak ada hasil yang cocok.'
                                : 'Belum ada data.'}
                        </p>
                    ) : (
                        <div className="divide-y rounded-md border">
                            {filtered.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-center justify-between gap-3 px-4 py-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="truncate font-medium">
                                                {item.nama}
                                            </span>
                                            <Badge
                                                variant={
                                                    item.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                                className="shrink-0 text-xs"
                                            >
                                                {item.is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'}
                                            </Badge>
                                        </div>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            Kode:{' '}
                                            <span className="font-mono">
                                                {item.kode}
                                            </span>
                                            {item.deskripsi &&
                                                ` — ${item.deskripsi}`}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8"
                                            onClick={() => openEdit(item)}
                                        >
                                            <Pencil className="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-destructive hover:text-destructive"
                                            onClick={() =>
                                                setDeleteTarget(item)
                                            }
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </ContentCard>

            {/* Create / Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {editingItem ? `Edit ${title}` : `Tambah ${title}`}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="nama">
                                Nama <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="nama"
                                value={data.nama}
                                onChange={(e) =>
                                    setData('nama', e.target.value)
                                }
                                placeholder="Nama lengkap"
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
                        <div className="space-y-2">
                            <Label htmlFor="deskripsi">Deskripsi</Label>
                            <Textarea
                                id="deskripsi"
                                value={data.deskripsi}
                                onChange={(e) =>
                                    setData('deskripsi', e.target.value)
                                }
                                placeholder="Deskripsi opsional"
                                rows={3}
                            />
                            <InputError message={errors.deskripsi} />
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked)
                                }
                            />
                            <Label
                                htmlFor="is_active"
                                className="cursor-pointer"
                            >
                                Aktif
                            </Label>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={!!deleteTarget}
                onOpenChange={() => setDeleteTarget(null)}
            >
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Hapus Data</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Yakin ingin menghapus{' '}
                        <span className="font-medium text-foreground">
                            {deleteTarget?.nama}
                        </span>
                        ? Tindakan ini tidak dapat dibatalkan.
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
        </>
    );
}

export default function Index({ frames, units }: Props) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Data Sampel" />

            <PageHeader
                title="Master Data Sampel"
                description="Kelola master frame sampel dan unit sampel"
            />

            {(flash.success || flash.error) && (
                <div
                    className={`rounded-md border px-4 py-3 text-sm ${
                        flash.success
                            ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200'
                            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200'
                    }`}
                >
                    {flash.success ?? flash.error}
                </div>
            )}

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <SampelSection
                    title="Frame Sampel"
                    items={frames}
                    storeUrl="/master-sampel/frame"
                    updateUrl={(hashedId) => `/master-sampel/frame/${hashedId}`}
                    destroyUrl={(hashedId) =>
                        `/master-sampel/frame/${hashedId}`
                    }
                />
                <SampelSection
                    title="Unit Sampel"
                    items={units}
                    storeUrl="/master-sampel/unit"
                    updateUrl={(hashedId) => `/master-sampel/unit/${hashedId}`}
                    destroyUrl={(hashedId) => `/master-sampel/unit/${hashedId}`}
                />
            </div>
        </AppLayout>
    );
}
