import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
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
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Star } from 'lucide-react';
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
    const [savingKey, setSavingKey] = useState<string | null>(null);
    const [modalAlert, setModalAlert] = useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };

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

    const selectedRows = useMemo(() => {
        if (!selectedPetugasId) {
            return [];
        }

        return rows.filter((row) => row.petugas_id === selectedPetugasId);
    }, [rows, selectedPetugasId]);

    const setRating = (key: string, rating: number) => {
        setDrafts((prev) => ({
            ...prev,
            [key]: {
                ...prev[key],
                rating,
            },
        }));
    };

    const setUlasan = (key: string, ulasan: string) => {
        setDrafts((prev) => ({
            ...prev,
            [key]: {
                ...prev[key],
                ulasan,
            },
        }));
    };

    const saveReview = (row: ReviewRow) => {
        const key = `${row.kegiatan_id}-${row.petugas_id}-${row.periode_alokasi_id}`;
        const draft = drafts[key];

        if (!draft || draft.rating < 1 || draft.rating > 5) {
            showModalAlert(
                'Input Belum Valid',
                'Nilai bintang wajib diisi antara 1 sampai 5.',
            );
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

    const totalPetugas = petugas_options.length;

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

            <div className="space-y-6">
                <PageHeader
                    title="Penilaian Mitra Statistik"
                    description={`Penilaian petugas untuk tahun aktif ${active_year}`}
                />

                <div className="grid gap-4 md:grid-cols-2">
                    <ContentCard>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Total Petugas Dinilai
                        </p>
                        <p className="mt-1 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {totalPetugas}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Total Kegiatan x Petugas
                        </p>
                        <p className="mt-1 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {rows.length}
                        </p>
                    </ContentCard>
                </div>

                {!can_submit_review && (
                    <ContentCard className="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20">
                        <p className="text-sm text-amber-800 dark:text-amber-200">
                            Anda dapat melihat daftar review, tetapi hanya PML
                            atau ketua tim yang bisa mengisi penilaian.
                        </p>
                    </ContentCard>
                )}

                <ContentCard>
                    <div className="space-y-4">
                        <div className="max-w-xl space-y-2">
                            <Label>Pilih Mitra Statistik</Label>
                            <SearchableSelect
                                value={selectedPetugasId?.toString() ?? ''}
                                onValueChange={(value) =>
                                    setSelectedPetugasId(Number(value))
                                }
                                options={petugas_options.map((option) => ({
                                    value: option.petugas_id.toString(),
                                    label: option.petugas_nama,
                                    searchKeywords: `${option.petugas_nama} ${option.petugas_hashed_id}`,
                                }))}
                                placeholder="Pilih petugas untuk direview"
                                searchPlaceholder="Cari nama petugas..."
                                className="h-9 rounded-md border-input bg-transparent shadow-xs"
                            />
                        </div>

                        {selectedRows.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-neutral-300 px-4 py-8 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-300">
                                Tidak ada data review untuk petugas yang
                                dipilih.
                            </div>
                        ) : (
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="font-semibold text-neutral-900 dark:text-neutral-100">
                                            {selectedRows.length} kegiatan
                                            siap/selesai review
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-4 space-y-4">
                                    {selectedRows.map((row) => {
                                        const key = `${row.kegiatan_id}-${row.petugas_id}-${row.periode_alokasi_id}`;
                                        const draft = drafts[key] ?? {
                                            rating: 0,
                                            ulasan: '',
                                        };
                                        const isFinal =
                                            row.existing_review !== null;
                                        const disabled =
                                            isFinal ||
                                            !row.can_review_now ||
                                            !row.user_can_submit;

                                        return (
                                            <div
                                                key={key}
                                                className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700"
                                            >
                                                <div className="flex flex-wrap items-start justify-between gap-3">
                                                    <div>
                                                        <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                                            {row.kegiatan_nama}
                                                        </p>
                                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                            Bulan{' '}
                                                            {row.periode_bulan}/
                                                            {row.periode_tahun}
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                                                            {peranLabel(
                                                                row.peran,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Badge
                                                            variant={
                                                                row.can_review_now
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {row.can_review_now
                                                                ? 'Bisa direview'
                                                                : 'Belum bisa direview'}
                                                        </Badge>
                                                        {isFinal && (
                                                            <Badge variant="outline">
                                                                Final
                                                            </Badge>
                                                        )}
                                                        {!row.user_can_submit && (
                                                            <Badge variant="outline">
                                                                Hanya PML/Ketua
                                                                Tim
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="mt-4 space-y-3">
                                                    <div>
                                                        <Label>
                                                            Berikan Penilaian
                                                        </Label>
                                                        <div className="mt-2 flex items-center gap-1">
                                                            {[
                                                                1, 2, 3, 4, 5,
                                                            ].map((value) => {
                                                                const active =
                                                                    draft.rating >=
                                                                    value;
                                                                return (
                                                                    <button
                                                                        key={
                                                                            value
                                                                        }
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
                                                                        className="rounded-md p-1 disabled:cursor-not-allowed disabled:opacity-50"
                                                                    >
                                                                        <Star
                                                                            className={`h-6 w-6 ${
                                                                                active
                                                                                    ? 'fill-amber-400 text-amber-400'
                                                                                    : 'text-neutral-300 dark:text-neutral-600'
                                                                            }`}
                                                                        />
                                                                    </button>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <Label
                                                            htmlFor={`ulasan-${key}`}
                                                        >
                                                            Ulasan Singkat
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
                                                            rows={3}
                                                            maxLength={500}
                                                            className="mt-2 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                                            placeholder="Tulis catatan singkat untuk petugas ini"
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                            {row.existing_review
                                                                ?.reviewed_at
                                                                ? `Review final: ${row.existing_review.reviewed_at}`
                                                                : 'Belum ada review'}
                                                        </p>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            onClick={() =>
                                                                saveReview(row)
                                                            }
                                                            disabled={
                                                                disabled ||
                                                                savingKey ===
                                                                    key
                                                            }
                                                        >
                                                            {savingKey === key
                                                                ? 'Menyimpan...'
                                                                : isFinal
                                                                  ? 'Sudah Dinilai'
                                                                  : 'Simpan Penilaian'}
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
