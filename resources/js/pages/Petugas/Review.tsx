import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SummaryCard } from '@/components/summary-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import ClipboardCheck from 'lucide-react/icons/clipboard-check';
import Clock3 from 'lucide-react/icons/clock3';
import Search from 'lucide-react/icons/search';
import Star from 'lucide-react/icons/star';
import Users from 'lucide-react/icons/users';

import { useEffect, useMemo, useState } from 'react';

interface ReviewRow {
    petugas_id: number;
    petugas_hashed_id: string;
    petugas_nama: string;
    kegiatan_id: number;
    kegiatan_hashed_id: string;
    kegiatan_kode: string;
    kegiatan_nama: string;
    peran: string;
    periode_alokasi_id: number;
    periode_tahun: number;
    periode_bulan: number;
    periode_mulai_tahun: number;
    periode_mulai_bulan: number;
    periode_selesai_tahun: number;
    periode_selesai_bulan: number;
    periode_bulan_terlibat: string[];
    tanggal_selesai: string | null;
    can_review_now: boolean;
    user_can_submit: boolean;
    existing_review: {
        rating: number;
        ulasan: string | null;
        reviewed_at: string | null;
    } | null;
}

interface PetugasOption {
    petugas_id: number;
    petugas_hashed_id: string;
    petugas_nama: string;
    total_review: number;
}

interface ReviewProps {
    rows: ReviewRow[];
    petugas_options: PetugasOption[];
    active_year: number;
    can_submit_review: boolean;
}

const MONTH_NAMES = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

function formatEpisodePeriod(row: ReviewRow): string {
    const startMonth =
        MONTH_NAMES[Math.max(0, Number(row.periode_mulai_bulan) - 1)] ??
        String(row.periode_mulai_bulan);
    const endMonth =
        MONTH_NAMES[Math.max(0, Number(row.periode_selesai_bulan) - 1)] ??
        String(row.periode_selesai_bulan);

    if (
        row.periode_mulai_bulan === row.periode_selesai_bulan &&
        row.periode_mulai_tahun === row.periode_selesai_tahun
    ) {
        return `${startMonth} ${row.periode_mulai_tahun}`;
    }

    if (row.periode_mulai_tahun === row.periode_selesai_tahun) {
        return `${startMonth}–${endMonth} ${row.periode_mulai_tahun}`;
    }

    return `${startMonth} ${row.periode_mulai_tahun}–${endMonth} ${row.periode_selesai_tahun}`;
}

function peranLabel(peran: string): string {
    switch (peran) {
        case 'pcl_ppl':
            return 'Petugas Pendataan';
        case 'pml':
            return 'Petugas Pemeriksaan';
        case 'pengolahan':
            return 'Petugas Pengolahan';
        default:
            return peran;
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Petugas', href: '/petugas' },
    { title: 'Penilaian Mitra Statistik', href: '/petugas/review' },
];

export default function Review({
    rows,
    petugas_options,
    active_year,
    can_submit_review,
}: ReviewProps) {
    const [selectedPetugasId, setSelectedPetugasId] = useState<number | null>(
        null,
    );
    const [search, setSearch] = useState('');
    const [savingKey, setSavingKey] = useState<string | null>(null);
    const [modalAlert, setModalAlert] = useState({
        open: false,
        title: '',
        message: '',
    });

    const initialDrafts = useMemo(() => {
        const map: Record<string, { rating: number; ulasan: string }> = {};

        rows.forEach((row) => {
            const key = `${row.kegiatan_id}-${row.petugas_id}-${row.periode_alokasi_id}`;
            map[key] = {
                rating: row.existing_review?.rating ?? 0,
                ulasan: row.existing_review?.ulasan ?? '',
            };
        });

        return map;
    }, [rows]);

    const [drafts, setDrafts] = useState(initialDrafts);

    useEffect(() => {
        setDrafts(initialDrafts);
    }, [initialDrafts]);

    useEffect(() => {
        if (selectedPetugasId === null && petugas_options.length > 0) {
            setSelectedPetugasId(petugas_options[0].petugas_id);
        }
    }, [petugas_options, selectedPetugasId]);

    const filteredOptions = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return petugas_options;

        return petugas_options.filter((item) =>
            item.petugas_nama.toLowerCase().includes(query),
        );
    }, [petugas_options, search]);

    const selectedRows = useMemo(
        () =>
            selectedPetugasId
                ? rows.filter((row) => row.petugas_id === selectedPetugasId)
                : [],
        [rows, selectedPetugasId],
    );

    const selectedOption = petugas_options.find(
        (item) => item.petugas_id === selectedPetugasId,
    );

    const completedCount = rows.filter(
        (row) => row.existing_review !== null,
    ).length;
    const readyCount = rows.filter(
        (row) =>
            row.existing_review === null &&
            row.can_review_now &&
            row.user_can_submit,
    ).length;
    const pendingCount = Math.max(rows.length - completedCount - readyCount, 0);

    const setRating = (key: string, rating: number) => {
        setDrafts((prev) => ({
            ...prev,
            [key]: { ...prev[key], rating },
        }));
    };

    const setUlasan = (key: string, ulasan: string) => {
        setDrafts((prev) => ({
            ...prev,
            [key]: { ...prev[key], ulasan },
        }));
    };

    const saveReview = (row: ReviewRow) => {
        const key = `${row.kegiatan_id}-${row.petugas_id}-${row.periode_alokasi_id}`;
        const draft = drafts[key];

        if (!draft || draft.rating < 1 || draft.rating > 5) {
            setModalAlert({
                open: true,
                title: 'Input Belum Valid',
                message: 'Nilai bintang wajib diisi antara 1 sampai 5.',
            });
            return;
        }

        setSavingKey(key);
        router.post(
            '/petugas/review',
            {
                kegiatan_id: row.kegiatan_id,
                petugas_id: row.petugas_id,
                periode_alokasi_id: row.periode_alokasi_id,
                rating: draft.rating,
                ulasan: draft.ulasan,
            },
            {
                preserveScroll: true,
                onFinish: () => setSavingKey(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penilaian Mitra Statistik" />

            <Dialog
                open={modalAlert.open}
                onOpenChange={(open) =>
                    setModalAlert((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{modalAlert.title}</DialogTitle>
                        <DialogDescription>
                            {modalAlert.message}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() =>
                                setModalAlert((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="space-y-5">
                <PageHeader
                    title="Penilaian Mitra Statistik"
                    description={`Nilai kinerja mitra non-organik berdasarkan penugasan yang sudah selesai pada tahun ${active_year}.`}
                />

                <div className="summary-grid">
                    <SummaryCard
                        label="Mitra tersedia"
                        value={petugas_options.length}
                        icon={<Users className="size-4" />}
                        accent="neutral"
                    />
                    <SummaryCard
                        label="Siap dinilai"
                        value={readyCount}
                        icon={<ClipboardCheck className="size-4" />}
                        accent="blue"
                    />
                    <SummaryCard
                        label="Sudah final"
                        value={completedCount}
                        icon={<CheckCircle2 className="size-4" />}
                        accent="green"
                    />
                    <SummaryCard
                        label="Belum tersedia"
                        value={pendingCount}
                        icon={<Clock3 className="size-4" />}
                        accent="orange"
                    />
                </div>

                {!can_submit_review && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
                        Anda dapat melihat penilaian. Pengisian hanya tersedia
                        untuk PML atau ketua tim yang memenuhi konteks
                        penugasan.
                    </div>
                )}

                <div className="grid min-h-[560px] gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <ContentCard className="h-fit lg:sticky lg:top-4">
                        <div className="space-y-4">
                            <div>
                                <h2 className="font-semibold">Pilih Mitra</h2>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Hanya mitra non-organik yang memenuhi syarat
                                    penilaian ditampilkan.
                                </p>
                            </div>

                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Cari nama mitra..."
                                    className="pl-9"
                                />
                            </div>

                            <div className="max-h-[430px] space-y-1 overflow-y-auto pr-1">
                                {filteredOptions.length === 0 ? (
                                    <div className="rounded-xl border border-dashed p-5 text-center text-sm text-muted-foreground">
                                        Mitra tidak ditemukan.
                                    </div>
                                ) : (
                                    filteredOptions.map((option) => {
                                        const selected =
                                            option.petugas_id ===
                                            selectedPetugasId;
                                        const optionRows = rows.filter(
                                            (row) =>
                                                row.petugas_id ===
                                                option.petugas_id,
                                        );
                                        const finalCount = optionRows.filter(
                                            (row) =>
                                                row.existing_review !== null,
                                        ).length;

                                        return (
                                            <button
                                                key={option.petugas_id}
                                                type="button"
                                                onClick={() =>
                                                    setSelectedPetugasId(
                                                        option.petugas_id,
                                                    )
                                                }
                                                className={[
                                                    'w-full rounded-xl border px-3 py-2.5 text-left transition-colors',
                                                    selected
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-transparent hover:border-neutral-200 hover:bg-neutral-50 dark:hover:border-neutral-800 dark:hover:bg-neutral-900/60',
                                                ].join(' ')}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">
                                                            {
                                                                option.petugas_nama
                                                            }
                                                        </p>
                                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                                            {optionRows.length}{' '}
                                                            episode penugasan
                                                        </p>
                                                    </div>
                                                    {finalCount > 0 && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="shrink-0"
                                                        >
                                                            {finalCount} final
                                                        </Badge>
                                                    )}
                                                </div>
                                            </button>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    </ContentCard>

                    <div className="space-y-4">
                        <ContentCard>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Mitra terpilih
                                    </p>
                                    <h2 className="mt-1 text-lg font-semibold">
                                        {selectedOption?.petugas_nama ??
                                            'Belum memilih mitra'}
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {selectedRows.length} episode/kegiatan
                                        tersedia untuk ditinjau.
                                    </p>
                                </div>
                                {selectedRows.length > 0 && (
                                    <Badge variant="outline">
                                        Tahun {active_year}
                                    </Badge>
                                )}
                            </div>
                        </ContentCard>

                        {selectedRows.length === 0 ? (
                            <ContentCard>
                                <div className="flex min-h-64 flex-col items-center justify-center text-center">
                                    <ClipboardCheck className="h-10 w-10 text-muted-foreground/30" />
                                    <p className="mt-3 font-medium">
                                        Tidak ada penilaian yang tersedia
                                    </p>
                                    <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                        Pilih mitra lain atau tunggu sampai
                                        periode/kegiatan memenuhi syarat untuk
                                        dinilai.
                                    </p>
                                </div>
                            </ContentCard>
                        ) : (
                            selectedRows.map((row) => {
                                const key = `${row.kegiatan_id}-${row.petugas_id}-${row.periode_alokasi_id}`;
                                const draft = drafts[key] ?? {
                                    rating: 0,
                                    ulasan: '',
                                };
                                const isFinal = row.existing_review !== null;
                                const disabled =
                                    isFinal ||
                                    !row.can_review_now ||
                                    !row.user_can_submit;

                                return (
                                    <ContentCard key={key}>
                                        <div className="space-y-5">
                                            <div className="flex flex-col gap-3 border-b border-neutral-200 pb-4 sm:flex-row sm:items-start sm:justify-between dark:border-neutral-800">
                                                <div>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <h3 className="font-semibold">
                                                            {row.kegiatan_nama}
                                                        </h3>
                                                        <Badge variant="outline">
                                                            {row.kegiatan_kode}
                                                        </Badge>
                                                    </div>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {peranLabel(row.peran)}{' '}
                                                        · Periode penugasan{' '}
                                                        <span className="font-medium text-foreground">
                                                            {formatEpisodePeriod(
                                                                row,
                                                            )}
                                                        </span>
                                                    </p>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    {isFinal ? (
                                                        <Badge className="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                            Final
                                                        </Badge>
                                                    ) : (
                                                        <Badge
                                                            variant={
                                                                row.can_review_now
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {row.can_review_now
                                                                ? 'Siap dinilai'
                                                                : 'Belum dapat dinilai'}
                                                        </Badge>
                                                    )}
                                                    {!row.user_can_submit && (
                                                        <Badge variant="outline">
                                                            Lihat saja
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="grid gap-5 xl:grid-cols-[280px_minmax(0,1fr)]">
                                                <div>
                                                    <Label>Nilai kinerja</Label>
                                                    <div className="mt-2 flex items-center gap-1">
                                                        {[1, 2, 3, 4, 5].map(
                                                            (value) => (
                                                                <button
                                                                    key={value}
                                                                    type="button"
                                                                    onClick={() =>
                                                                        setRating(
                                                                            key,
                                                                            value,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        disabled
                                                                    }
                                                                    className="rounded-lg p-1.5 transition-colors hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:bg-amber-950/20"
                                                                    aria-label={`Nilai ${value}`}
                                                                >
                                                                    <Star
                                                                        className={[
                                                                            'h-7 w-7',
                                                                            draft.rating >=
                                                                            value
                                                                                ? 'fill-amber-400 text-amber-400'
                                                                                : 'text-neutral-300 dark:text-neutral-600',
                                                                        ].join(
                                                                            ' ',
                                                                        )}
                                                                    />
                                                                </button>
                                                            ),
                                                        )}
                                                    </div>
                                                    <p className="mt-2 text-xs text-muted-foreground">
                                                        {draft.rating > 0
                                                            ? `${draft.rating} dari 5`
                                                            : 'Pilih 1–5 bintang'}
                                                    </p>
                                                </div>

                                                <div>
                                                    <Label
                                                        htmlFor={`ulasan-${key}`}
                                                    >
                                                        Catatan / ulasan
                                                    </Label>
                                                    <textarea
                                                        id={`ulasan-${key}`}
                                                        value={draft.ulasan}
                                                        onChange={(event) =>
                                                            setUlasan(
                                                                key,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        disabled={disabled}
                                                        rows={4}
                                                        maxLength={500}
                                                        className="mt-2 w-full resize-y rounded-xl border border-neutral-300 bg-background px-3 py-2.5 text-sm transition outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700"
                                                        placeholder="Tuliskan catatan singkat yang relevan dengan kinerja mitra..."
                                                    />
                                                    <div className="mt-1 flex justify-between text-xs text-muted-foreground">
                                                        <span>
                                                            {isFinal &&
                                                            row.existing_review
                                                                ?.reviewed_at
                                                                ? `Final pada ${row.existing_review.reviewed_at}`
                                                                : 'Ulasan bersifat opsional'}
                                                        </span>
                                                        <span>
                                                            {
                                                                draft.ulasan
                                                                    .length
                                                            }
                                                            /500
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex justify-end border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                                <Button
                                                    type="button"
                                                    onClick={() =>
                                                        saveReview(row)
                                                    }
                                                    disabled={
                                                        disabled ||
                                                        savingKey === key
                                                    }
                                                >
                                                    {savingKey === key
                                                        ? 'Menyimpan...'
                                                        : isFinal
                                                          ? 'Penilaian Final'
                                                          : 'Simpan Penilaian'}
                                                </Button>
                                            </div>
                                        </div>
                                    </ContentCard>
                                );
                            })
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
