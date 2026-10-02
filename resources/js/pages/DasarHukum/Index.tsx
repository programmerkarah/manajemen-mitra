import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { encryptData } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookOpenText,
    ChevronDown,
    ChevronRight,
    FileText,
    History,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
];

interface DasarHukum {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    status: 'aktif' | 'nonaktif';
    jenis: 'pertama' | 'perubahan';
    induk_id: number | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    dasarHukum: {
        encrypted: string;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}

interface RegulationChain {
    key: string;
    root: DasarHukum;
    amendments: DasarHukum[];
    current: DasarHukum;
}

export default function Index({ dasarHukum }: Props) {
    const { auth, flash } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';
    const allDasarHukum = useDecryptedData<DasarHukum>(dasarHukum.encrypted);

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<'all' | 'aktif' | 'nonaktif'>('all');
    const [expandedChains, setExpandedChains] = useState<
        Record<string, boolean>
    >({});

    const getKategoriLabel = (item: DasarHukum): string => {
        switch (item.kategori) {
            case 'undang_undang':
                return 'Undang-Undang';
            case 'peraturan_pemerintah':
                return 'Peraturan Pemerintah';
            case 'peraturan_presiden':
                return 'Peraturan Presiden';
            case 'peraturan_menteri_badan':
                return item.instansi
                    ? `Peraturan ${item.instansi}`
                    : 'Peraturan Menteri/Badan';
            case 'keputusan_menteri_kepala_badan':
                return item.instansi
                    ? `Keputusan ${item.instansi}`
                    : 'Keputusan Menteri/Kepala Badan';
            case 'peraturan_kepala_badan':
                return 'Peraturan Kepala BPS';
            default:
                return item.kategori;
        }
    };

    const getShortLabel = (item: DasarHukum) =>
        `${getKategoriLabel(item)} No. ${item.nomor} Tahun ${item.tahun}`;

    const chains = useMemo<RegulationChain[]>(() => {
        const roots = allDasarHukum.filter((item) => item.jenis === 'pertama');
        const amendmentsByRoot = new Map<number, DasarHukum[]>();

        allDasarHukum
            .filter((item) => item.jenis === 'perubahan' && item.induk_id)
            .forEach((item) => {
                const rootId = Number(item.induk_id);
                if (!amendmentsByRoot.has(rootId)) {
                    amendmentsByRoot.set(rootId, []);
                }
                amendmentsByRoot.get(rootId)!.push(item);
            });

        return roots
            .map((root) => {
                const amendments = (amendmentsByRoot.get(root.id) ?? []).sort(
                    (left, right) =>
                        left.tahun - right.tahun || left.id - right.id,
                );
                const activeItem =
                    [...amendments]
                        .reverse()
                        .find((item) => item.status === 'aktif') ??
                    (root.status === 'aktif' ? root : null);
                const latest = amendments[amendments.length - 1] ?? root;

                return {
                    key: String(root.id),
                    root,
                    amendments,
                    current: activeItem ?? latest,
                };
            })
            .sort(
                (left, right) =>
                    right.current.tahun - left.current.tahun ||
                    right.current.id - left.current.id,
            );
    }, [allDasarHukum]);

    const filteredChains = useMemo(() => {
        const query = search.trim().toLowerCase();

        return chains.filter((chain) => {
            const allItems = [chain.root, ...chain.amendments];
            const matchesSearch =
                !query ||
                allItems.some((item) =>
                    [
                        item.nomor,
                        item.tentang,
                        item.instansi ?? '',
                        getKategoriLabel(item),
                        String(item.tahun),
                    ]
                        .join(' ')
                        .toLowerCase()
                        .includes(query),
                );

            if (!matchesSearch) return false;
            if (status === 'all') return true;

            return chain.current.status === status;
        });
    }, [chains, search, status]);

    const stats = useMemo(() => {
        const active = allDasarHukum.filter(
            (item) => item.status === 'aktif',
        ).length;
        const amendments = allDasarHukum.filter(
            (item) => item.jenis === 'perubahan',
        ).length;

        return {
            references: chains.length,
            active,
            amendments,
            total: allDasarHukum.length,
        };
    }, [allDasarHukum, chains]);

    const toggleChain = (key: string) => {
        setExpandedChains((current) => ({
            ...current,
            [key]: !current[key],
        }));
    };

    const editItem = (item: DasarHukum) => {
        router.post('/dasar-hukum/edit', {
            encrypted: encryptData({ id: item.id }),
        });
    };

    const deleteItem = (item: DasarHukum) => {
        if (
            confirm(
                `Apakah Anda yakin ingin menghapus "${getShortLabel(item)}"?`,
            )
        ) {
            router.delete(`/dasar-hukum/${item.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasar Hukum SK" />

            <div className="space-y-6">
                <PageHeader
                    title="Dasar Hukum SK"
                    description="Kelola dasar peraturan dan seluruh riwayat perubahannya sebagai satu rangkaian referensi."
                >
                    {!isPJ && (
                        <Button asChild className="gap-2">
                            <Link href="/dasar-hukum/create">
                                <Plus className="h-4 w-4" />
                                Tambah Peraturan
                            </Link>
                        </Button>
                    )}
                </PageHeader>

                {(flash?.success || flash?.error) && (
                    <div
                        className={[
                            'rounded-xl border px-4 py-3 text-sm',
                            flash.success
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200'
                                : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200',
                        ].join(' ')}
                    >
                        {flash.success ?? flash.error}
                    </div>
                )}

                <div className="summary-grid">
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Referensi utama
                                </p>
                                <p className="mt-1 text-2xl font-bold">
                                    {stats.references}
                                </p>
                            </div>
                            <BookOpenText className="h-5 w-5 text-muted-foreground" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Peraturan aktif
                                </p>
                                <p className="mt-1 text-2xl font-bold">
                                    {stats.active}
                                </p>
                            </div>
                            <ShieldCheck className="h-5 w-5 text-emerald-600" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Riwayat perubahan
                                </p>
                                <p className="mt-1 text-2xl font-bold">
                                    {stats.amendments}
                                </p>
                            </div>
                            <History className="h-5 w-5 text-amber-600" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Total dokumen
                                </p>
                                <p className="mt-1 text-2xl font-bold">
                                    {stats.total}
                                </p>
                            </div>
                            <FileText className="h-5 w-5 text-muted-foreground" />
                        </div>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    Rangkaian Peraturan
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Setiap kartu mewakili satu referensi dasar.
                                    Perubahan ditampilkan sebagai riwayat di
                                    dalamnya.
                                </p>
                            </div>

                            <div className="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                                <div className="relative min-w-0 sm:w-80">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder="Cari nomor, tahun, tentang..."
                                        className="pl-9"
                                    />
                                </div>
                                <Select
                                    value={status}
                                    onValueChange={(value) =>
                                        setStatus(
                                            value as
                                                | 'all'
                                                | 'aktif'
                                                | 'nonaktif',
                                        )
                                    }
                                >
                                    <SelectTrigger className="sm:w-44">
                                        <SelectValue placeholder="Semua status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua status
                                        </SelectItem>
                                        <SelectItem value="aktif">
                                            Aktif
                                        </SelectItem>
                                        <SelectItem value="nonaktif">
                                            Nonaktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {filteredChains.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-neutral-300 py-12 text-center dark:border-neutral-700">
                                <FileText className="mx-auto h-9 w-9 text-muted-foreground/40" />
                                <p className="mt-3 font-medium">
                                    Tidak ada dasar hukum yang sesuai
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Ubah kata kunci atau filter status.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {filteredChains.map((chain) => {
                                    const expanded = Boolean(
                                        expandedChains[chain.key],
                                    );
                                    const current = chain.current;
                                    const hasAmendments =
                                        chain.amendments.length > 0;

                                    return (
                                        <div
                                            key={chain.key}
                                            className="overflow-hidden rounded-2xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900/50"
                                        >
                                            <div className="p-4">
                                                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <Badge variant="outline">
                                                                {getKategoriLabel(
                                                                    chain.root,
                                                                )}
                                                            </Badge>
                                                            <Badge
                                                                className={
                                                                    current.status ===
                                                                    'aktif'
                                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                                        : ''
                                                                }
                                                                variant={
                                                                    current.status ===
                                                                    'aktif'
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                            >
                                                                {current.status ===
                                                                'aktif'
                                                                    ? 'Aktif'
                                                                    : 'Nonaktif'}
                                                            </Badge>
                                                            {hasAmendments && (
                                                                <Badge variant="secondary">
                                                                    {
                                                                        chain
                                                                            .amendments
                                                                            .length
                                                                    }{' '}
                                                                    perubahan
                                                                </Badge>
                                                            )}
                                                        </div>

                                                        <p className="mt-2 text-base font-semibold text-neutral-900 dark:text-white">
                                                            {getShortLabel(
                                                                current,
                                                            )}
                                                        </p>
                                                        <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                                            {current.tentang}
                                                        </p>

                                                        {current.id !==
                                                            chain.root.id && (
                                                            <p className="mt-2 text-xs text-muted-foreground">
                                                                Referensi dasar:{' '}
                                                                <span className="font-medium text-foreground">
                                                                    {getShortLabel(
                                                                        chain.root,
                                                                    )}
                                                                </span>
                                                            </p>
                                                        )}
                                                    </div>

                                                    <div className="flex shrink-0 flex-wrap items-center gap-2">
                                                        {hasAmendments && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    toggleChain(
                                                                        chain.key,
                                                                    )
                                                                }
                                                                className="gap-2"
                                                            >
                                                                {expanded ? (
                                                                    <ChevronDown className="h-4 w-4" />
                                                                ) : (
                                                                    <ChevronRight className="h-4 w-4" />
                                                                )}
                                                                Riwayat
                                                            </Button>
                                                        )}

                                                        {!isPJ && (
                                                            <>
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="gap-2"
                                                                    onClick={() =>
                                                                        editItem(
                                                                            current,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                    Edit
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/30"
                                                                    onClick={() =>
                                                                        deleteItem(
                                                                            current,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                    Hapus
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            {expanded && (
                                                <div className="border-t border-neutral-200 bg-neutral-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30">
                                                    <div className="relative ml-2 space-y-0 border-l border-neutral-300 pl-5 dark:border-neutral-700">
                                                        {[
                                                            chain.root,
                                                            ...chain.amendments,
                                                        ].map((item, index) => {
                                                            const isCurrent =
                                                                item.id ===
                                                                current.id;
                                                            return (
                                                                <div
                                                                    key={
                                                                        item.id
                                                                    }
                                                                    className="relative pb-5 last:pb-0"
                                                                >
                                                                    <span
                                                                        className={[
                                                                            'absolute top-1.5 -left-[27px] h-3 w-3 rounded-full border-2',
                                                                            isCurrent
                                                                                ? 'border-emerald-500 bg-emerald-500'
                                                                                : 'border-neutral-300 bg-white dark:border-neutral-600 dark:bg-neutral-900',
                                                                        ].join(
                                                                            ' ',
                                                                        )}
                                                                    />
                                                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                                        <div className="min-w-0">
                                                                            <div className="flex flex-wrap items-center gap-2">
                                                                                <span className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                                                    {index ===
                                                                                    0
                                                                                        ? 'Dasar'
                                                                                        : `Perubahan ${index}`}
                                                                                </span>
                                                                                <Badge
                                                                                    variant={
                                                                                        item.status ===
                                                                                        'aktif'
                                                                                            ? 'default'
                                                                                            : 'secondary'
                                                                                    }
                                                                                    className={
                                                                                        item.status ===
                                                                                        'aktif'
                                                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                                                            : ''
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        item.status
                                                                                    }
                                                                                </Badge>
                                                                            </div>
                                                                            <p className="mt-1 text-sm font-medium text-neutral-900 dark:text-white">
                                                                                {getShortLabel(
                                                                                    item,
                                                                                )}
                                                                            </p>
                                                                            <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                                                {
                                                                                    item.tentang
                                                                                }
                                                                            </p>
                                                                        </div>

                                                                        {!isPJ && (
                                                                            <div className="flex shrink-0 gap-1">
                                                                                <Button
                                                                                    variant="ghost"
                                                                                    size="icon"
                                                                                    className="h-8 w-8"
                                                                                    onClick={() =>
                                                                                        editItem(
                                                                                            item,
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                                </Button>
                                                                                <Button
                                                                                    variant="ghost"
                                                                                    size="icon"
                                                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/30"
                                                                                    onClick={() =>
                                                                                        deleteItem(
                                                                                            item,
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                                </Button>
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
